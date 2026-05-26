const { getZeusMysqlConfig } = require('./dbConnectionService');

const SPEAKWELL_LCMS_COURSE_IDS = [346, 563, 595, 1084];
const DEFAULT_CHUNK_SIZE = 500;

function chunkArray(items = [], size = DEFAULT_CHUNK_SIZE) {
    const result = [];
    for (let index = 0; index < items.length; index += size) {
        result.push(items.slice(index, index + size));
    }
    return result;
}

async function withMysql(callback) {
    let mysql;
    try {
        mysql = require('mysql2/promise');
    } catch (error) {
        throw new Error('mysql2 is required for LCMS data access.');
    }

    const connection = await mysql.createConnection(getZeusMysqlConfig());
    try {
        return await callback(connection);
    } finally {
        await connection.end();
    }
}

function sectionTypeLabel(sectionType) {
    if (Number(sectionType) === 2) return 'BTVN';
    if (Number(sectionType) === 3) return 'BKT';
    if (Number(sectionType) === 1) return 'Lecture';
    if (Number(sectionType) === 4) return 'Resource';
    return 'Khác';
}

async function getStudentLcmsStatsBatch(userIds = []) {
    const ids = [...new Set((userIds || []).map(value => Number(value)).filter(Number.isFinite).filter(value => value > 0))];
    if (!ids.length) return {};

    const courseIds = SPEAKWELL_LCMS_COURSE_IDS.join(',');
    const result = {};

    await withMysql(async (connection) => {
        for (const chunk of chunkArray(ids)) {
            const placeholders = chunk.map(() => '?').join(',');
            const bindings = chunk.map(String);

            const completionSql = `
                SELECT
                    CAST(ls.stu_user_id AS UNSIGNED) AS user_id,
                    ROUND(
                        SUM(CASE WHEN c.cou_section_type = 2 AND sub_completed = 1 THEN 1 ELSE 0 END) * 100.0 /
                        NULLIF(SUM(CASE WHEN c.cou_section_type = 2 THEN 1 ELSE 0 END), 0),
                        1
                    ) AS hw_completion_rate
                FROM (
                    SELECT
                        ua.usrasi_student_id,
                        ua.usrasi_section_id,
                        ua.usrasi_course_id,
                        CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS sub_completed
                    FROM lcms_user_assignments ua
                    WHERE ua.usrasi_course_id IN (${courseIds})
                      AND ua.usrasi_student_id IN (
                          SELECT stu_id FROM lcms_students WHERE stu_user_id IN (${placeholders})
                      )
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
                ) sub
                JOIN lcms_courses c ON sub.usrasi_section_id = c.cou_id
                JOIN lcms_students ls ON sub.usrasi_student_id = ls.stu_id
                WHERE c.cou_section_type = 2
                GROUP BY ls.stu_user_id
            `;

            const scoreSql = `
                SELECT
                    CAST(ls.stu_user_id AS UNSIGNED) AS user_id,
                    ROUND(AVG(CASE WHEN c.cou_section_type = 2 THEN ssa.avg_score END), 2) AS hw_avg_score,
                    ROUND(AVG(CASE WHEN c.cou_section_type = 3 THEN ssa.avg_score END), 2) AS test_avg_score
                FROM (
                    SELECT
                        qm.stusco_student_id,
                        qm.section_id,
                        AVG(qm.max_score) AS avg_score,
                        COUNT(qm.quiz_id) AS quizzes_done,
                        sqc.total_quizzes
                    FROM (
                        SELECT
                            ss.stusco_student_id,
                            c_child.cou_parent_id AS section_id,
                            c_child.cou_id AS quiz_id,
                            MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS max_score
                        FROM lcms_student_scores ss
                        JOIN lcms_courses c_child ON ss.stusco_course_id = c_child.cou_id
                        WHERE c_child.cou_type = 'quiz'
                          AND ss.stusco_student_id IN (
                              SELECT stu_id FROM lcms_students WHERE stu_user_id IN (${placeholders})
                          )
                        GROUP BY ss.stusco_student_id, c_child.cou_parent_id, c_child.cou_id
                    ) qm
                    JOIN (
                        SELECT cou_parent_id AS section_id, COUNT(*) AS total_quizzes
                        FROM lcms_courses
                        WHERE cou_type = 'quiz'
                        GROUP BY cou_parent_id
                    ) sqc ON qm.section_id = sqc.section_id
                    GROUP BY qm.stusco_student_id, qm.section_id, sqc.total_quizzes
                    HAVING quizzes_done >= total_quizzes
                ) ssa
                JOIN lcms_courses c ON ssa.section_id = c.cou_id
                JOIN lcms_students ls ON ssa.stusco_student_id = ls.stu_id
                WHERE c.cou_section_type IN (2, 3)
                GROUP BY ls.stu_user_id
            `;

            const [completionRows] = await connection.query(completionSql, bindings);
            completionRows.forEach((row) => {
                const userId = Number(row.user_id);
                result[userId] = {
                    hwCompletionRate: row.hw_completion_rate !== null ? Number(row.hw_completion_rate) : null,
                    hwAvgScore: null,
                    testAvgScore: null,
                };
            });

            const [scoreRows] = await connection.query(scoreSql, bindings);
            scoreRows.forEach((row) => {
                const userId = Number(row.user_id);
                if (!result[userId]) {
                    result[userId] = {
                        hwCompletionRate: null,
                        hwAvgScore: null,
                        testAvgScore: null,
                    };
                }
                result[userId].hwAvgScore = row.hw_avg_score !== null ? Number(row.hw_avg_score) : null;
                result[userId].testAvgScore = row.test_avg_score !== null ? Number(row.test_avg_score) : null;
            });
        }
    });

    return result;
}

async function getStudentLcmsDetailByUserId(userId) {
    const numericUserId = Number(userId);
    if (!Number.isFinite(numericUserId) || numericUserId <= 0) return null;

    return withMysql(async (connection) => {
        const [studentRows] = await connection.query(`
            SELECT stu_id, stu_user_id, stu_name, stu_email, stu_gender, stu_dob
            FROM lcms_students
            WHERE CAST(stu_user_id AS UNSIGNED) = ?
            LIMIT 1
        `, [numericUserId]);

        const lcmsStudent = studentRows?.[0];
        if (!lcmsStudent) {
            return {
                userId: numericUserId,
                available: false,
                summary: {
                    hwCompletionRate: null,
                    hwAvgScore: null,
                    testAvgScore: null,
                },
                sections: [],
                courses: [],
            };
        }

        const studentId = Number(lcmsStudent.stu_id);
        const [assignmentRows] = await connection.query(`
            SELECT
                ua.usrasi_course_id AS course_id,
                course.cou_name AS course_name,
                ua.usrasi_section_id AS section_id,
                section.cou_name AS section_name,
                section.cou_section_type AS section_type,
                CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_completed,
                MAX(ua.usrasi_completion_time) AS completion_time
            FROM lcms_user_assignments ua
            JOIN lcms_courses section ON ua.usrasi_section_id = section.cou_id
            LEFT JOIN lcms_courses course ON ua.usrasi_course_id = course.cou_id
            WHERE ua.usrasi_student_id = ?
              AND ua.usrasi_course_id IN (${SPEAKWELL_LCMS_COURSE_IDS.join(',')})
            GROUP BY ua.usrasi_course_id, course.cou_name, ua.usrasi_section_id, section.cou_name, section.cou_section_type
            ORDER BY ua.usrasi_course_id ASC, ua.usrasi_section_id ASC
        `, [studentId]);

        const [scoreRows] = await connection.query(`
            SELECT
                qm.section_id,
                ROUND(AVG(qm.max_score), 2) AS avg_score
            FROM (
                SELECT
                    c_child.cou_parent_id AS section_id,
                    c_child.cou_id AS quiz_id,
                    MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS max_score
                FROM lcms_student_scores ss
                JOIN lcms_courses c_child ON ss.stusco_course_id = c_child.cou_id
                WHERE ss.stusco_student_id = ?
                  AND c_child.cou_type = 'quiz'
                GROUP BY c_child.cou_parent_id, c_child.cou_id
            ) qm
            GROUP BY qm.section_id
        `, [studentId]);

        const scoreBySection = new Map(scoreRows.map(row => [String(row.section_id), row.avg_score !== null ? Number(row.avg_score) : null]));
        const sections = assignmentRows.map(row => ({
            courseId: Number(row.course_id),
            courseName: row.course_name || `Course #${row.course_id}`,
            sectionId: Number(row.section_id),
            sectionName: row.section_name || `Section #${row.section_id}`,
            sectionType: Number(row.section_type) || 0,
            sectionTypeLabel: sectionTypeLabel(row.section_type),
            isCompleted: Number(row.is_completed) === 1,
            completionTime: row.completion_time || '',
            score: scoreBySection.has(String(row.section_id)) ? scoreBySection.get(String(row.section_id)) : null,
        }));

        const summary = {
            hwCompletionRate: null,
            hwAvgScore: null,
            testAvgScore: null,
        };
        const batchSummary = await getStudentLcmsStatsBatch([numericUserId]);
        const existingSummary = batchSummary[numericUserId] || {};
        summary.hwCompletionRate = existingSummary.hwCompletionRate ?? null;
        summary.hwAvgScore = existingSummary.hwAvgScore ?? null;
        summary.testAvgScore = existingSummary.testAvgScore ?? null;

        const courseMap = new Map();
        sections.forEach((section) => {
            const current = courseMap.get(section.courseId) || {
                courseId: section.courseId,
                courseName: section.courseName,
                homework: { totalSections: 0, completedSections: 0 },
                test: { totalSections: 0, completedSections: 0 },
                sections: [],
            };
            if (section.sectionType === 2) {
                current.homework.totalSections += 1;
                if (section.isCompleted) current.homework.completedSections += 1;
            }
            if (section.sectionType === 3) {
                current.test.totalSections += 1;
                if (section.isCompleted) current.test.completedSections += 1;
            }
            current.sections.push(section);
            courseMap.set(section.courseId, current);
        });

        const courses = [...courseMap.values()].map((course) => ({
            ...course,
            homework: {
                ...course.homework,
                completionRatio: course.homework.totalSections ? Number(((course.homework.completedSections / course.homework.totalSections) * 100).toFixed(2)) : 0,
            },
            test: {
                ...course.test,
                completionRatio: course.test.totalSections ? Number(((course.test.completedSections / course.test.totalSections) * 100).toFixed(2)) : 0,
            },
        }));

        return {
            available: true,
            studentId,
            userId: numericUserId,
            studentName: lcmsStudent.stu_name || '',
            studentEmail: lcmsStudent.stu_email || '',
            studentGender: lcmsStudent.stu_gender || '',
            studentDob: lcmsStudent.stu_dob || '',
            summary,
            courses,
            sections,
        };
    });
}

module.exports = {
    SPEAKWELL_LCMS_COURSE_IDS,
    getStudentLcmsStatsBatch,
    getStudentLcmsDetailByUserId,
};
