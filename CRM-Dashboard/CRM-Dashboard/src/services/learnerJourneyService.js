const fs = require('fs');
const path = require('path');
const db = require('../db/database');

const ZEUS_SUBJECT_IDS = [
    533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
    416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
    390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
    584, 582, 404, 403, 583, 471,
];
const TRIAL_SUBJECT_IDS = [533];
const LEARNER_JOURNEY_SUBJECT_IDS = ZEUS_SUBJECT_IDS.filter(id => !TRIAL_SUBJECT_IDS.includes(id));

function dbAll(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

function dbRun(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.run(sql, params, function onRun(err) {
            if (err) reject(err);
            else resolve(this);
        });
    });
}

function toInt(value) {
    const number = Number(value);
    return Number.isFinite(number) ? Math.round(number) : 0;
}

function toAmount(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
}

function cleanText(value) {
    return String(value || '').trim();
}

function deriveJourneyStatus(row) {
    const remaining = toInt(row.remainingSessions);
    const completed = toInt(row.completedSessions);
    const scheduled = toInt(row.scheduledSessions);
    const unscheduled = toInt(row.unscheduledSessions);

    if (remaining > 0 && completed === 0) return 'Onboarding';
    if (remaining > 0 && (completed > 0 || scheduled > 0)) return 'Active';
    if (remaining === 0 && completed > 0) return 'Expired';
    if (remaining === 0 && scheduled > 0) return 'Scheduled only';
    if (remaining > 0 && unscheduled > 0 && completed === 0 && scheduled === 0) return 'Pending start';
    return 'Unknown';
}

function mergeUniqueText(existing, incoming, separator = ' | ') {
    const values = new Set();
    [existing, incoming].forEach(source => {
        String(source || '')
            .split(separator)
            .map(item => item.trim())
            .filter(Boolean)
            .forEach(item => values.add(item));
    });
    return [...values].join(separator);
}

function splitTextValues(text, separator = ' | ') {
    return String(text || '')
        .split(separator)
        .map(item => item.trim())
        .filter(Boolean);
}

function normalizePackageName(packageName) {
    return String(packageName || '').trim();
}

function isTrialPackage(packageName) {
    return /^trial lesson$/i.test(normalizePackageName(packageName));
}

function stripTrialPackages(packageNames) {
    return splitTextValues(packageNames).filter(item => !isTrialPackage(item));
}

function detectPackageGroup(packageName) {
    const text = normalizePackageName(packageName).toLowerCase();
    if (!text) return '';
    if (text.includes('get ready')) return 'Get Ready';
    if (['starters', 'movers', 'flyers', 'beginners'].some(keyword => text.includes(keyword))) return 'Cambridge';
    if (text.includes('solution')) return 'Solution';
    if (text.includes('chat room')) return 'Chat Room';
    if (text.includes('freetalk')) return 'Freetalk';
    if (text.includes('easy speak')) return 'Easy Speak';
    if (text.includes('oxford discover')) return 'Oxford Discover';
    if (text.includes('speak your mind')) return 'Speak Your Mind';
    if (text.includes('teen talk')) return 'Teen Talk';
    if (text.includes('business')) return 'Business';
    if (text.includes('four corners')) return 'Four Corners';
    return 'Other';
}

function derivePackageGroupFromBreadcrumb(breadcrumb, packageName = '') {
    const parts = String(breadcrumb || '')
        .split('>')
        .map(item => item.trim())
        .filter(Boolean);

    if (parts.length >= 2 && /^speakwell$/i.test(parts[0])) return parts[1];
    if (parts.length >= 2) return parts[0];
    return detectPackageGroup(packageName);
}

function derivePackageGroups(packageNames, packageBreadcrumbs = '') {
    const groups = new Set();
    const packages = stripTrialPackages(packageNames);
    const breadcrumbs = splitTextValues(packageBreadcrumbs);

    if (breadcrumbs.length) {
        breadcrumbs.forEach((breadcrumb, index) => {
            const packageName = packages[index] || packages[0] || '';
            const group = derivePackageGroupFromBreadcrumb(breadcrumb, packageName);
            if (group) groups.add(group);
        });
    }

    if (!groups.size) {
        packages.map(detectPackageGroup).filter(Boolean).forEach(group => groups.add(group));
    }

    return [...groups].sort();
}

function normalizePurchaseHistoryRows(rows) {
    return (rows || [])
        .map(raw => {
            const studentId = cleanText(raw.student_id || raw.user_id || raw.id);
            const orderId = cleanText(raw.order_id);
            if (!studentId || !orderId) return null;

            const packageNamesList = stripTrialPackages(raw.package_names || raw.package_name);
            if (!packageNamesList.length) return null;
            const packageNamesText = packageNamesList.join(' | ');
            const packageBreadcrumbsText = cleanText(raw.package_breadcrumbs || raw.package_breadcrumb || '');

            return {
                studentId,
                orderId,
                orderAmount: toAmount(raw.order_amount),
                orderDate: cleanText(raw.order_date),
                renewalDate: cleanText(raw.renewal_date || raw.order_date),
                classSizes: cleanText(raw.class_sizes || raw.class_size),
                packageNames: packageNamesText,
                packageGroups: derivePackageGroups(packageNamesText, packageBreadcrumbsText).join(' | '),
                teacherTypes: cleanText(raw.teacher_types || raw.teacher_type),
                purchasedSessions: toInt(raw.purchased_sessions || raw.purchasedSessions),
                unscheduledSessions: toInt(raw.unscheduled_sessions || raw.unscheduledSessions),
                scheduledSessions: toInt(raw.scheduled_sessions || raw.scheduledSessions),
                completedSessions: toInt(raw.completed_sessions || raw.completedSessions),
                cancelledSessions: toInt(raw.cancelled_sessions || raw.cancelledSessions),
                remainingSessions: toInt(raw.remaining_sessions || raw.remainingSessions),
            };
        })
        .filter(Boolean)
        .sort((a, b) => String(b.orderDate || '').localeCompare(String(a.orderDate || '')) || String(b.orderId).localeCompare(String(a.orderId)));
}

function normalizeJourneyRows(rows, purchaseHistoryRows = []) {
    const byStudent = new Map();

    for (const raw of rows || []) {
        const studentId = cleanText(raw.student_id || raw.user_id || raw.id);
        if (!studentId) continue;

        const packageNamesList = stripTrialPackages(raw.package_names || raw.package_name);
        if (!packageNamesList.length) continue;
        const packageNamesText = packageNamesList.join(' | ');
        const packageBreadcrumbsText = cleanText(raw.package_breadcrumbs || raw.package_breadcrumb || '');

        const next = {
            studentId,
            studentName: cleanText(raw.student_name),
            email: cleanText(raw.user_email || raw.email),
            phone: cleanText(raw.phone),
            css: cleanText(raw.css || raw.css_staff),
            classSizes: cleanText(raw.class_sizes || raw.class_size),
            packageNames: packageNamesText,
            packageBreadcrumbs: packageBreadcrumbsText,
            packageGroups: derivePackageGroups(packageNamesText, packageBreadcrumbsText).join(' | '),
            teacherTypes: cleanText(raw.teacher_types || raw.teacher_type),
            purchasedSessions: toInt(raw.purchased_sessions || raw.purchasedSessions),
            unscheduledSessions: toInt(raw.unscheduled_sessions || raw.unscheduledSessions),
            scheduledSessions: toInt(raw.scheduled_sessions || raw.scheduledSessions),
            completedSessions: toInt(raw.completed_sessions || raw.completedSessions),
            cancelledSessions: toInt(raw.cancelled_sessions || raw.cancelledSessions),
            remainingSessions: toInt(raw.remaining_sessions || raw.remainingSessions),
            firstLessonStarttime: cleanText(raw.first_lesson_starttime || raw.firstLessonStarttime),
            lastLessonStarttime: cleanText(raw.last_lesson_starttime || raw.lastLessonStarttime),
            latestOrderAmount: toAmount(raw.latest_order_amount),
            latestOrderDate: cleanText(raw.latest_order_date),
        };

        if (!byStudent.has(studentId)) {
            byStudent.set(studentId, next);
            continue;
        }

        const current = byStudent.get(studentId);
        current.studentName = current.studentName || next.studentName;
        current.email = current.email || next.email;
        current.phone = current.phone || next.phone;
        current.css = current.css || next.css;
        current.classSizes = mergeUniqueText(current.classSizes, next.classSizes, ', ');
        current.packageNames = mergeUniqueText(current.packageNames, next.packageNames);
        current.packageBreadcrumbs = mergeUniqueText(current.packageBreadcrumbs, next.packageBreadcrumbs);
        current.packageGroups = mergeUniqueText(current.packageGroups, next.packageGroups);
        current.teacherTypes = mergeUniqueText(current.teacherTypes, next.teacherTypes);
        current.purchasedSessions += next.purchasedSessions;
        current.unscheduledSessions += next.unscheduledSessions;
        current.scheduledSessions += next.scheduledSessions;
        current.completedSessions += next.completedSessions;
        current.cancelledSessions += next.cancelledSessions;
        current.remainingSessions += next.remainingSessions;
        current.firstLessonStarttime = [current.firstLessonStarttime, next.firstLessonStarttime].filter(Boolean).sort()[0] || '';
        current.lastLessonStarttime = [current.lastLessonStarttime, next.lastLessonStarttime].filter(Boolean).sort().slice(-1)[0] || '';
        if (String(next.latestOrderDate || '') >= String(current.latestOrderDate || '')) {
            current.latestOrderDate = next.latestOrderDate;
            current.latestOrderAmount = next.latestOrderAmount;
        }
    }

    const latestHistoryByStudent = new Map();
    purchaseHistoryRows.forEach(row => {
        const current = latestHistoryByStudent.get(row.studentId);
        if (!current || String(row.orderDate || '') > String(current.orderDate || '') || (String(row.orderDate || '') === String(current.orderDate || '') && Number(row.orderAmount || 0) >= Number(current.orderAmount || 0))) {
            latestHistoryByStudent.set(row.studentId, row);
        }
    });

    return [...byStudent.values()].map(item => {
        const latestHistory = latestHistoryByStudent.get(item.studentId);
        return {
            ...item,
            packageGroups: derivePackageGroups(item.packageNames, item.packageBreadcrumbs || '').join(' | '),
            teacherTypes: mergeUniqueText(item.teacherTypes, latestHistory?.teacherTypes || ''),
            journeyStatus: deriveJourneyStatus(item),
            latestOrderAmount: latestHistory ? latestHistory.orderAmount : item.latestOrderAmount,
            latestOrderDate: latestHistory ? latestHistory.orderDate : item.latestOrderDate,
        };
    });
}

async function fetchJourneyRowsFromJson(jsonPath) {
    const resolvedPath = path.resolve(jsonPath);
    if (!fs.existsSync(resolvedPath)) {
        throw new Error(`Learner Journey JSON not found: ${resolvedPath}`);
    }
    const rows = JSON.parse(fs.readFileSync(resolvedPath, 'utf8'));
    return { studentRows: normalizeJourneyRows(rows, []), purchaseHistoryRows: [] };
}

function buildMysqlLessonPoolSql(finalSelect) {
    return `
        WITH root_subject AS (
            SELECT tlang_id
            FROM tbl_teach_languages
            WHERE tlang_identifier = 'SpeakWell'
              AND tlang_active = 1
            LIMIT 1
        ), hierarchy_map AS (
            SELECT
                leaf.tlang_id AS child_tlang_id,
                leaf.tlang_identifier AS child_identifier,
                (
                    SELECT GROUP_CONCAT(node.tlang_identifier ORDER BY node.tlang_level SEPARATOR ' > ')
                    FROM tbl_teach_languages AS node
                    CROSS JOIN root_subject AS r
                    WHERE node.tlang_active = 1
                      AND (
                          node.tlang_id = r.tlang_id
                          OR FIND_IN_SET(node.tlang_id, leaf.tlang_parentids)
                          OR node.tlang_id = leaf.tlang_id
                      )
                ) AS breadcrumb
            FROM tbl_teach_languages AS leaf
            CROSS JOIN root_subject AS r
            WHERE leaf.tlang_subcategories = 0
              AND leaf.tlang_active = 1
              AND FIND_IN_SET(r.tlang_id, leaf.tlang_parentids)
        ), gc_map AS (
            SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) AS grpcls_total_seats
            FROM tbl_group_classes
            GROUP BY grpcls_tlang_id, grpcls_teacher_id
        ), lesson_pool AS (
            SELECT
                u.user_id AS student_id,
                CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                u.user_email AS email,
                COALESCE(us.user_phone_number, '') AS phone,
                COALESCE(a.admin_username, '') AS css_staff,
                o.order_id AS order_id,
                o.order_total_amount AS order_amount,
                DATE_FORMAT(o.order_addedon, '%Y-%m-%d %H:%i:%s') AS order_date,
                COALESCE(tl.tlang_identifier, CONCAT('SUBJECT#', ol.ordles_tlang_id)) AS package_name,
                COALESCE(hm.breadcrumb, '') AS package_breadcrumb,
                ol.ordles_status,
                DATE_FORMAT(ol.ordles_lesson_starttime, '%Y-%m-%d %H:%i:%s') AS ordles_lesson_starttime,
                CASE
                    WHEN gc.grpcls_total_seats = 2 THEN '1:2'
                    WHEN gc.grpcls_total_seats = 3 THEN '1:3'
                    WHEN gc.grpcls_total_seats BETWEEN 4 AND 6 THEN '1:6'
                    WHEN gc.grpcls_total_seats BETWEEN 7 AND 10 THEN '1:8'
                    ELSE '1:1'
                END AS class_size,
                CASE
                    WHEN UPPER(COALESCE(tc.country_code, '')) = 'VN' THEN 'GV Việt Nam'
                    WHEN UPPER(COALESCE(tc.country_code, '')) = 'PH' THEN 'GV Philippines'
                    WHEN UPPER(COALESCE(tc.country_code, '')) IN ('US', 'GB', 'UK', 'CA', 'AU') THEN 'GV Native 1'
                    WHEN UPPER(COALESCE(tc.country_code, '')) IN ('NZ', 'IE', 'ZA') THEN 'GV Native 2'
                    ELSE NULL
                END AS teacher_type
            FROM tbl_order_lessons ol
            JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
            JOIN tbl_users u ON u.user_id = o.order_user_id
            LEFT JOIN tbl_teach_languages tl ON tl.tlang_id = ol.ordles_tlang_id
            LEFT JOIN hierarchy_map hm ON hm.child_tlang_id = ol.ordles_tlang_id
            LEFT JOIN tbl_user_settings us ON us.user_id = u.user_id
            LEFT JOIN tbl_user_extras ue ON ue.usrextra_user_id = u.user_id
            LEFT JOIN tbl_admin a ON a.admin_id = ue.usrextra_css_id
            LEFT JOIN gc_map gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
            LEFT JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id
            LEFT JOIN tbl_countries tc ON tc.country_id = teacher.user_country_id
            WHERE o.order_payment_status = 1
              AND o.order_status = 2
              AND u.user_deleted IS NULL
              AND (u.user_is_teacher = 0 OR u.user_is_teacher IS NULL)
              AND ol.ordles_tlang_id IN (${LEARNER_JOURNEY_SUBJECT_IDS.join(',')})
        )
        ${finalSelect}
    `;
}

async function fetchJourneyRowsFromMysql() {
    let mysql;
    try {
        mysql = require('mysql2/promise');
    } catch (error) {
        throw new Error('mysql2 is required for ZEUS_DB_SOURCE=mysql. Run npm install.');
    }

    const connection = await mysql.createConnection({
        host: process.env.ZEUS_DB_HOST || '127.0.0.1',
        port: Number(process.env.ZEUS_DB_PORT || 3306),
        user: process.env.ZEUS_DB_USERNAME || process.env.ZEUS_DB_USER || 'forge',
        password: process.env.ZEUS_DB_PASSWORD || '',
        database: process.env.ZEUS_DB_DATABASE || 'zeus_core',
        charset: 'utf8mb4',
    });

    const studentSql = buildMysqlLessonPoolSql(`
        SELECT
            student_id,
            student_name,
            email,
            MAX(phone) AS phone,
            MAX(css_staff) AS css_staff,
            GROUP_CONCAT(DISTINCT class_size ORDER BY class_size SEPARATOR ', ') AS class_sizes,
            GROUP_CONCAT(DISTINCT package_name ORDER BY package_name SEPARATOR ' | ') AS package_names,
            GROUP_CONCAT(DISTINCT package_breadcrumb ORDER BY package_breadcrumb SEPARATOR ' | ') AS package_breadcrumbs,
            GROUP_CONCAT(DISTINCT teacher_type ORDER BY teacher_type SEPARATOR ' | ') AS teacher_types,
            COUNT(*) AS purchased_sessions,
            SUM(CASE WHEN ordles_status = 1 THEN 1 ELSE 0 END) AS unscheduled_sessions,
            SUM(CASE WHEN ordles_status = 2 THEN 1 ELSE 0 END) AS scheduled_sessions,
            SUM(CASE WHEN ordles_status = 3 THEN 1 ELSE 0 END) AS completed_sessions,
            SUM(CASE WHEN ordles_status = 4 THEN 1 ELSE 0 END) AS cancelled_sessions,
            SUM(CASE WHEN ordles_status IN (1,2) THEN 1 ELSE 0 END) AS remaining_sessions,
            MIN(ordles_lesson_starttime) AS first_lesson_starttime,
            MAX(ordles_lesson_starttime) AS last_lesson_starttime
        FROM lesson_pool
        WHERE class_size IN ('1:1', '1:2')
        GROUP BY student_id, student_name, email
        ORDER BY remaining_sessions DESC, purchased_sessions DESC, student_id ASC
    `);

    const historySql = buildMysqlLessonPoolSql(`
        SELECT
            student_id,
            MAX(student_name) AS student_name,
            MAX(email) AS email,
            MAX(phone) AS phone,
            MAX(css_staff) AS css_staff,
            CAST(order_id AS CHAR) AS order_id,
            MAX(order_amount) AS order_amount,
            MAX(order_date) AS order_date,
            MAX(order_date) AS renewal_date,
            GROUP_CONCAT(DISTINCT class_size ORDER BY class_size SEPARATOR ', ') AS class_sizes,
            GROUP_CONCAT(DISTINCT package_name ORDER BY package_name SEPARATOR ' | ') AS package_names,
            GROUP_CONCAT(DISTINCT package_breadcrumb ORDER BY package_breadcrumb SEPARATOR ' | ') AS package_breadcrumbs,
            GROUP_CONCAT(DISTINCT teacher_type ORDER BY teacher_type SEPARATOR ' | ') AS teacher_types,
            COUNT(*) AS purchased_sessions,
            SUM(CASE WHEN ordles_status = 1 THEN 1 ELSE 0 END) AS unscheduled_sessions,
            SUM(CASE WHEN ordles_status = 2 THEN 1 ELSE 0 END) AS scheduled_sessions,
            SUM(CASE WHEN ordles_status = 3 THEN 1 ELSE 0 END) AS completed_sessions,
            SUM(CASE WHEN ordles_status = 4 THEN 1 ELSE 0 END) AS cancelled_sessions,
            SUM(CASE WHEN ordles_status IN (1,2) THEN 1 ELSE 0 END) AS remaining_sessions
        FROM lesson_pool
        WHERE class_size IN ('1:1', '1:2')
        GROUP BY student_id, order_id
        ORDER BY MAX(order_date) DESC, student_id ASC, CAST(order_id AS CHAR) DESC
    `);

    try {
        const [studentRows] = await connection.query(studentSql);
        const [purchaseHistoryRawRows] = await connection.query(historySql);
        const purchaseHistoryRows = normalizePurchaseHistoryRows(purchaseHistoryRawRows);
        return {
            studentRows: normalizeJourneyRows(studentRows, purchaseHistoryRows),
            purchaseHistoryRows,
        };
    } finally {
        await connection.end();
    }
}

async function replaceLearnerJourneyStudents(rows, purchaseHistoryRows = []) {
    const syncedAt = new Date().toISOString();
    await dbRun('BEGIN TRANSACTION');
    try {
        await dbRun('DELETE FROM learner_journey_students');
        await dbRun('DELETE FROM learner_journey_purchase_history');

        const studentSql = `INSERT INTO learner_journey_students (
            student_id, student_name, email, phone, css,
            class_sizes, package_names, package_groups, teacher_types,
            purchased_sessions, unscheduled_sessions, scheduled_sessions, completed_sessions, cancelled_sessions, remaining_sessions,
            journey_status, first_lesson_starttime, last_lesson_starttime, latest_order_amount, latest_order_date, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

        for (const row of rows) {
            await dbRun(studentSql, [
                row.studentId,
                row.studentName,
                row.email,
                row.phone,
                row.css,
                row.classSizes,
                row.packageNames,
                row.packageGroups,
                row.teacherTypes,
                row.purchasedSessions,
                row.unscheduledSessions,
                row.scheduledSessions,
                row.completedSessions,
                row.cancelledSessions,
                row.remainingSessions,
                row.journeyStatus,
                row.firstLessonStarttime,
                row.lastLessonStarttime,
                row.latestOrderAmount,
                row.latestOrderDate,
                syncedAt,
            ]);
        }

        const historySql = `INSERT INTO learner_journey_purchase_history (
            student_id, order_id, order_amount, order_date, renewal_date,
            class_sizes, package_names, package_groups, teacher_types,
            purchased_sessions, unscheduled_sessions, scheduled_sessions, completed_sessions, cancelled_sessions, remaining_sessions, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

        for (const row of purchaseHistoryRows) {
            await dbRun(historySql, [
                row.studentId,
                row.orderId,
                row.orderAmount,
                row.orderDate,
                row.renewalDate,
                row.classSizes,
                row.packageNames,
                row.packageGroups,
                row.teacherTypes,
                row.purchasedSessions,
                row.unscheduledSessions,
                row.scheduledSessions,
                row.completedSessions,
                row.cancelledSessions,
                row.remainingSessions,
                syncedAt,
            ]);
        }

        await dbRun('COMMIT');
        return { rows: rows.length, syncedAt, purchaseHistoryRows: purchaseHistoryRows.length };
    } catch (error) {
        await dbRun('ROLLBACK').catch(() => {});
        throw error;
    }
}

async function syncLearnerJourneyData(options = {}) {
    const source = options.source || process.env.LEARNER_JOURNEY_SOURCE || 'json';
    let studentRows;
    let purchaseHistoryRows;

    if (source === 'mysql') {
        ({ studentRows, purchaseHistoryRows } = await fetchJourneyRowsFromMysql());
    } else {
        const jsonPath = options.jsonPath || process.env.LEARNER_JOURNEY_JSON_PATH;
        if (!jsonPath) {
            throw new Error('LEARNER_JOURNEY_JSON_PATH is required when LEARNER_JOURNEY_SOURCE=json');
        }
        ({ studentRows, purchaseHistoryRows } = await fetchJourneyRowsFromJson(jsonPath));
    }

    const result = await replaceLearnerJourneyStudents(studentRows, purchaseHistoryRows);
    return { ...result, source };
}

async function listLearnerJourneyStudents() {
    return dbAll(`
        SELECT
            student_id,
            student_name,
            email,
            phone,
            css,
            class_sizes,
            package_names,
            package_groups,
            teacher_types,
            purchased_sessions,
            unscheduled_sessions,
            scheduled_sessions,
            completed_sessions,
            cancelled_sessions,
            remaining_sessions,
            journey_status,
            first_lesson_starttime,
            last_lesson_starttime,
            latest_order_amount,
            latest_order_date,
            synced_at
        FROM learner_journey_students
        ORDER BY remaining_sessions DESC, purchased_sessions DESC, student_name ASC
    `);
}

async function listLearnerJourneyPurchaseHistory(studentId = '') {
    const clauses = [];
    const params = [];
    if (studentId) {
        clauses.push('student_id = ?');
        params.push(String(studentId));
    }
    const where = clauses.length ? `WHERE ${clauses.join(' AND ')}` : '';
    return dbAll(`
        SELECT
            student_id,
            order_id,
            order_amount,
            order_date,
            renewal_date,
            class_sizes,
            package_names,
            package_groups,
            teacher_types,
            purchased_sessions,
            unscheduled_sessions,
            scheduled_sessions,
            completed_sessions,
            cancelled_sessions,
            remaining_sessions,
            synced_at
        FROM learner_journey_purchase_history
        ${where}
        ORDER BY order_date DESC, order_id DESC
    `, params);
}

module.exports = {
    detectPackageGroup,
    derivePackageGroups,
    normalizeJourneyRows,
    normalizePurchaseHistoryRows,
    syncLearnerJourneyData,
    listLearnerJourneyStudents,
    listLearnerJourneyPurchaseHistory,
};
