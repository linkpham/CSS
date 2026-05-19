<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LcmsService
{
    /**
     * SpeakWell course IDs in LCMS
     */
    public const SPEAKWELL_COURSE_IDS = [346, 563, 595, 1084];



    /**
     * Section type constants
     */
    public const SECTION_TYPE_LECTURE = 1;
    public const SECTION_TYPE_HOMEWORK = 2;
    public const SECTION_TYPE_TEST = 3;
    public const SECTION_TYPE_RESOURCE = 4;

    /**
     * Cache TTL (3 hours / 10800 seconds) — Phase 180: increased from 60 min to 3 hours
     */
    private const CACHE_TTL = 10800;

    /**
     * Get DB connection
     */
    private function db()
    {
        return DB::connection('mysql');
    }



    /**
     * Get overall LCMS stats for all SpeakWell courses (Grand Total)
     * Combines homework and test completion rates + average scores
     */
    public function getOverviewStats(): array
    {
        return Cache::remember('lcms:overview_stats', self::CACHE_TTL, function () {
            $homework = $this->getGrandTotalStats(self::SECTION_TYPE_HOMEWORK);
            $test = $this->getGrandTotalStats(self::SECTION_TYPE_TEST);

            return [
                'homework' => $homework,
                'test' => $test,
                'total_students' => $this->getTotalStudents(),
                'total_courses' => count(self::SPEAKWELL_COURSE_IDS),
                'course_list' => $this->getCourseNames(),
            ];
        });
    }

    /**
     * Get grand total stats for a section type across all SpeakWell courses
     * Based on "theo các khóa của sản phẩm SpeakWell" queries
     */
    private function getGrandTotalStats(int $sectionType): array
    {
        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);
        // Completion rate query
        $completionSql = "
            SELECT
                COUNT(sub.section_id) AS total_sections,
                SUM(sub.is_section_completed) AS completed_sections,
                ROUND(
                    (SUM(sub.is_section_completed) / NULLIF(COUNT(sub.section_id), 0)) * 100,
                    2
                ) AS completion_ratio
            FROM (
                SELECT
                    ua.usrasi_student_id,
                    ua.usrasi_course_id AS course_id,
                    ua.usrasi_section_id AS section_id,
                    CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_course_id IN ({$courseIds})
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
        ";

        $completion = $this->db()->selectOne($completionSql, [$sectionType]);

        // Average score query (only for sections where student completed all quizzes)
        $scoreSql = "
            SELECT
                ROUND(AVG(sub.avg_section_score), 2) AS avg_score
            FROM (
                SELECT
                    ua.usrasi_student_id,
                    ua.usrasi_course_id AS course_id,
                    ua.usrasi_section_id AS section_id,
                    (
                        SELECT
                            CASE
                                WHEN COUNT(max_scores.highest_score) >= (
                                    SELECT COUNT(*)
                                    FROM lcms_courses
                                    WHERE cou_parent_id = ua.usrasi_section_id
                                      AND cou_type = 'quiz'
                                )
                                THEN AVG(max_scores.highest_score)
                                ELSE NULL
                            END
                        FROM (
                            SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                            FROM lcms_courses c_child
                            JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                            WHERE c_child.cou_parent_id = ua.usrasi_section_id
                              AND c_child.cou_type = 'quiz'
                              AND ss.stusco_student_id = ua.usrasi_student_id
                            GROUP BY c_child.cou_id
                        ) AS max_scores
                    ) AS avg_section_score
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_course_id IN ({$courseIds})
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
        ";

        $score = $this->db()->selectOne($scoreSql, [$sectionType]);

        return [
            'total_sections' => (int) ($completion->total_sections ?? 0),
            'completed_sections' => (int) ($completion->completed_sections ?? 0),
            'completion_ratio' => (float) ($completion->completion_ratio ?? 0),
            'avg_score' => $score->avg_score !== null ? (float) $score->avg_score : null,
        ];
    }

    /**
     * Get total unique students across SpeakWell courses
     */
    private function getTotalStudents(): int
    {
        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

        $result = $this->db()->selectOne("
            SELECT COUNT(DISTINCT ua.usrasi_student_id) AS total
            FROM lcms_user_assignments ua
            WHERE ua.usrasi_course_id IN ({$courseIds})
        ");

        return (int) ($result->total ?? 0);
    }

    /**
     * Get course names/IDs
     */
    private function getCourseNames(): array
    {
        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

        $results = $this->db()->select("
            SELECT DISTINCT c.cou_id, c.cou_name
            FROM lcms_courses c
            WHERE c.cou_id IN ({$courseIds})
            ORDER BY c.cou_id
        ");

        return collect($results)->map(fn($r) => [
            'id' => $r->cou_id,
            'title' => $r->cou_name ?? "Course #{$r->cou_id}",
        ])->toArray();
    }

    /**
     * Get stats broken down by course
     * Returns homework + test stats for each course
     */
    public function getCourseBreakdownStats(): array
    {
        return Cache::remember('lcms:course_breakdown', self::CACHE_TTL, function () {
            $courses = [];

            foreach (self::SPEAKWELL_COURSE_IDS as $courseId) {
                $hwCompletion = $this->getCourseCompletionStats($courseId, self::SECTION_TYPE_HOMEWORK);
                $hwScore = $this->getCourseScoreStats($courseId, self::SECTION_TYPE_HOMEWORK);
                $testCompletion = $this->getCourseCompletionStats($courseId, self::SECTION_TYPE_TEST);
                $testScore = $this->getCourseScoreStats($courseId, self::SECTION_TYPE_TEST);

                // Get course name
                $courseInfo = $this->db()->selectOne("
                    SELECT cou_name FROM lcms_courses WHERE cou_id = ?
                ", [$courseId]);
                $studentCount = $this->db()->selectOne("
                    SELECT COUNT(DISTINCT usrasi_student_id) AS total
                    FROM lcms_user_assignments
                    WHERE usrasi_course_id = ?
                ", [$courseId]);

                $courses[] = [
                    'course_id' => $courseId,
                    'course_name' => $courseInfo->cou_name ?? "Course #{$courseId}",
                    'student_count' => (int) ($studentCount->total ?? 0),
                    'homework' => [
                        'total_sections' => (int) ($hwCompletion->total_sections ?? 0),
                        'completed_sections' => (int) ($hwCompletion->completed_sections ?? 0),
                        'completion_ratio' => (float) ($hwCompletion->completion_ratio ?? 0),
                        'avg_score' => $hwScore->avg_score !== null ? (float) $hwScore->avg_score : null,
                    ],
                    'test' => [
                        'total_sections' => (int) ($testCompletion->total_sections ?? 0),
                        'completed_sections' => (int) ($testCompletion->completed_sections ?? 0),
                        'completion_ratio' => (float) ($testCompletion->completion_ratio ?? 0),
                        'avg_score' => $testScore->avg_score !== null ? (float) $testScore->avg_score : null,
                    ],
                ];
            }

            return $courses;
        });
    }

    /**
     * Get completion stats for a single course
     */
    private function getCourseCompletionStats(int $courseId, int $sectionType): object
    {

        $sql = "
            SELECT
                sub.course_id,
                COUNT(sub.section_id) AS total_sections,
                SUM(CASE WHEN sub.is_section_completed = 1 THEN 1 ELSE 0 END) AS completed_sections,
                ROUND(
                    (SUM(CASE WHEN sub.is_section_completed = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(sub.section_id), 0)) * 100,
                    2
                ) AS completion_ratio
            FROM (
                SELECT
                    ua.usrasi_student_id,
                    ua.usrasi_course_id AS course_id,
                    ua.usrasi_section_id AS section_id,
                    CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_course_id = ?
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
            GROUP BY sub.course_id
        ";

        return $this->db()->selectOne($sql, [$sectionType, $courseId])
            ?? (object) ['course_id' => $courseId, 'total_sections' => 0, 'completed_sections' => 0, 'completion_ratio' => 0];
    }

    /**
     * Get average score stats for a single course
     */
    private function getCourseScoreStats(int $courseId, int $sectionType): object
    {

        $sql = "
            SELECT
                ROUND(AVG(sub.avg_section_score), 2) AS avg_score
            FROM (
                SELECT
                    ua.usrasi_student_id,
                    ua.usrasi_course_id AS course_id,
                    ua.usrasi_section_id AS section_id,
                    (
                        SELECT
                            CASE
                                WHEN COUNT(max_scores.highest_score) >= (
                                    SELECT COUNT(*)
                                    FROM lcms_courses
                                    WHERE cou_parent_id = ua.usrasi_section_id
                                      AND cou_type = 'quiz'
                                )
                                THEN AVG(max_scores.highest_score)
                                ELSE NULL
                            END
                        FROM (
                            SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                            FROM lcms_courses c_child
                            JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                            WHERE c_child.cou_parent_id = ua.usrasi_section_id
                              AND c_child.cou_type = 'quiz'
                              AND ss.stusco_student_id = ua.usrasi_student_id
                            GROUP BY c_child.cou_id
                        ) AS max_scores
                    ) AS avg_section_score
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_course_id = ?
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
        ";

        return $this->db()->selectOne($sql, [$sectionType, $courseId])
            ?? (object) ['avg_score' => null];
    }

    /**
     * Get student-level stats for a specific course (paginated via API)
     */
    public function getStudentStats(?int $courseId = null, int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        $courseIds = $courseId ? [$courseId] : self::SPEAKWELL_COURSE_IDS;
        $courseIdsList = implode(',', $courseIds);

        // Build where clause for search
        // Phase 128: Search by stu_user_id (user-facing ID) instead of stu_id/usrasi_student_id
        $searchWhere = '';
        $bindings = [];
        if ($search) {
            $searchWhere = 'WHERE sub.student_id IN (SELECT stu_id FROM lcms_students WHERE stu_user_id = ?)';
            $bindings[] = (string) $search;
        }

        // Count total
        $countSql = "
            SELECT COUNT(*) AS total FROM (
                SELECT DISTINCT sub.student_id, sub.course_id
                FROM (
                    SELECT
                        ua.usrasi_student_id AS student_id,
                        ua.usrasi_course_id AS course_id
                    FROM lcms_user_assignments ua
                    WHERE ua.usrasi_course_id IN ({$courseIdsList})
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id
                ) AS sub
                {$searchWhere}
            ) AS cnt
        ";

        $totalResult = $this->db()->selectOne($countSql, $bindings);
        $total = (int) ($totalResult->total ?? 0);

        // Get paginated student list
        $offset = ($page - 1) * $perPage;
        $studentListSql = "
            SELECT DISTINCT sub.student_id, sub.course_id
            FROM (
                SELECT
                    ua.usrasi_student_id AS student_id,
                    ua.usrasi_course_id AS course_id
                FROM lcms_user_assignments ua
                WHERE ua.usrasi_course_id IN ({$courseIdsList})
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id
            ) AS sub
            {$searchWhere}
            ORDER BY sub.student_id ASC, sub.course_id ASC
            LIMIT ? OFFSET ?
        ";

        $paginationBindings = array_merge($bindings, [$perPage, $offset]);
        $studentList = $this->db()->select($studentListSql, $paginationBindings);

        // For each student-course pair, get homework & test stats
        $students = [];
        foreach ($studentList as $row) {
            $hw = $this->getStudentSectionStats($row->student_id, $row->course_id, self::SECTION_TYPE_HOMEWORK);
            $test = $this->getStudentSectionStats($row->student_id, $row->course_id, self::SECTION_TYPE_TEST);

            // Get student info (lcms_students first, fallback to tbl_users)
            $studentInfo = $this->getStudentInfo($row->student_id);

            // Get course name
            $courseInfo = $this->db()->selectOne("
                SELECT cou_name FROM lcms_courses WHERE cou_id = ?
            ", [$row->course_id]);

            $students[] = [
                'student_id' => (int) $row->student_id,
                'student_user_id' => $studentInfo['user_id'],
                'student_name' => $studentInfo['name'],
                'student_email' => $studentInfo['email'],
                'student_gender' => $studentInfo['gender'],
                'course_id' => (int) $row->course_id,
                'course_name' => $courseInfo->cou_name ?? "Course #{$row->course_id}",
                'homework' => $hw,
                'test' => $test,
            ];
        }

        return [
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    /**
     * Get homework/test stats for a single student in a course
     */
    private function getStudentSectionStats(int $studentId, int $courseId, int $sectionType): array
    {
        // Completion stats
        $completionSql = "
            SELECT
                COUNT(sub.section_id) AS total_sections,
                SUM(CASE WHEN sub.is_section_completed = 1 THEN 1 ELSE 0 END) AS completed_sections,
                ROUND(
                    (SUM(CASE WHEN sub.is_section_completed = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(sub.section_id), 0)) * 100,
                    2
                ) AS completion_ratio
            FROM (
                SELECT
                    ua.usrasi_section_id AS section_id,
                    CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_student_id = ?
                  AND ua.usrasi_course_id = ?
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
        ";

        $completion = $this->db()->selectOne($completionSql, [$sectionType, $studentId, $courseId]);

        // Score stats
        $scoreSql = "
            SELECT
                ROUND(AVG(sub.avg_section_score), 2) AS avg_score
            FROM (
                SELECT
                    ua.usrasi_section_id AS section_id,
                    (
                        SELECT
                            CASE
                                WHEN COUNT(max_scores.highest_score) >= (
                                    SELECT COUNT(*)
                                    FROM lcms_courses
                                    WHERE cou_parent_id = ua.usrasi_section_id
                                      AND cou_type = 'quiz'
                                )
                                THEN AVG(max_scores.highest_score)
                                ELSE NULL
                            END
                        FROM (
                            SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                            FROM lcms_courses c_child
                            JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                            WHERE c_child.cou_parent_id = ua.usrasi_section_id
                              AND c_child.cou_type = 'quiz'
                              AND ss.stusco_student_id = ua.usrasi_student_id
                            GROUP BY c_child.cou_id
                        ) AS max_scores
                    ) AS avg_section_score
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE c.cou_section_type = ?
                  AND ua.usrasi_student_id = ?
                  AND ua.usrasi_course_id = ?
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) AS sub
        ";

        $score = $this->db()->selectOne($scoreSql, [$sectionType, $studentId, $courseId]);

        return [
            'total_sections' => (int) ($completion->total_sections ?? 0),
            'completed_sections' => (int) ($completion->completed_sections ?? 0),
            'completion_ratio' => (float) ($completion->completion_ratio ?? 0),
            'avg_score' => $score->avg_score !== null ? (float) $score->avg_score : null,
        ];
    }

    /**
     * Get section type distribution (lectures, homework, tests, resources) per course
     * Phase 180: Added 3-hour caching
     */
    public function getSectionTypeDistribution(): array
    {
        return Cache::remember('lcms:section_distribution', self::CACHE_TTL, function () {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            $results = $this->db()->select("
                SELECT
                    c.cou_section_type,
                    COUNT(DISTINCT c.cou_id) AS section_count
                FROM lcms_courses c
                WHERE c.cou_section_type IN (1, 2, 3, 4)
                  AND c.cou_id IN (
                      SELECT DISTINCT usrasi_section_id
                      FROM lcms_user_assignments
                      WHERE usrasi_course_id IN ({$courseIds})
                  )
                GROUP BY c.cou_section_type
                ORDER BY c.cou_section_type
            ");

            $labels = [
                1 => 'Bài giảng',
                2 => 'BTVN',
                3 => 'Bài kiểm tra',
                4 => 'Tài nguyên',
            ];

            return collect($results)->map(fn($r) => [
                'type' => (int) $r->cou_section_type,
                'label' => $labels[(int) $r->cou_section_type] ?? 'Khác',
                'count' => (int) $r->section_count,
            ])->toArray();
        });
    }

    /**
     * Get top performing students (by homework + test scores)
     * Phase 180: Added 3-hour caching
     */
    public function getTopStudents(int $limit = 10): array
    {
        return Cache::remember("lcms:top_students:{$limit}", self::CACHE_TTL, function () use ($limit) {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            $sql = "
                SELECT
                    sub.student_id,
                    ROUND(AVG(sub.avg_section_score), 2) AS overall_avg_score,
                    COUNT(sub.section_id) AS total_sections,
                    SUM(sub.is_section_completed) AS completed_sections,
                    ROUND(
                        (SUM(sub.is_section_completed) / NULLIF(COUNT(sub.section_id), 0)) * 100,
                        2
                    ) AS completion_ratio
                FROM (
                    SELECT
                        ua.usrasi_student_id AS student_id,
                        ua.usrasi_section_id AS section_id,
                        CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed,
                        (
                            SELECT
                                CASE
                                    WHEN COUNT(max_scores.highest_score) >= (
                                        SELECT COUNT(*)
                                        FROM lcms_courses
                                        WHERE cou_parent_id = ua.usrasi_section_id
                                          AND cou_type = 'quiz'
                                    )
                                    THEN AVG(max_scores.highest_score)
                                    ELSE NULL
                                END
                            FROM (
                                SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                                FROM lcms_courses c_child
                                JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                                WHERE c_child.cou_parent_id = ua.usrasi_section_id
                                  AND c_child.cou_type = 'quiz'
                                  AND ss.stusco_student_id = ua.usrasi_student_id
                                GROUP BY c_child.cou_id
                            ) AS max_scores
                        ) AS avg_section_score
                    FROM lcms_user_assignments ua
                    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                    WHERE c.cou_section_type IN (2, 3)
                      AND ua.usrasi_course_id IN ({$courseIds})
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
                ) AS sub
                GROUP BY sub.student_id
                HAVING overall_avg_score IS NOT NULL
                ORDER BY overall_avg_score DESC, completion_ratio DESC
                LIMIT ?
            ";

            $results = $this->db()->select($sql, [$limit]);

            // Enrich with student names (lcms_students first, fallback tbl_users)
            return collect($results)->map(function ($r) {
                $info = $this->getStudentInfo($r->student_id);

                return [
                    'student_id' => (int) $r->student_id,
                    'student_user_id' => $info['user_id'],
                    'student_name' => $info['name'],
                    'student_email' => $info['email'],
                    'overall_avg_score' => (float) $r->overall_avg_score,
                    'total_sections' => (int) $r->total_sections,
                    'completed_sections' => (int) $r->completed_sections,
                    'completion_ratio' => (float) $r->completion_ratio,
                ];
            })->toArray();
        });
    }

    /**
     * Get students with lowest completion rates (at risk)
     * Phase 180: Added 3-hour caching
     */
    public function getAtRiskStudents(int $limit = 10): array
    {
        return Cache::remember("lcms:at_risk_students:{$limit}", self::CACHE_TTL, function () use ($limit) {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            $sql = "
                SELECT
                    sub.student_id,
                    COUNT(sub.section_id) AS total_sections,
                    SUM(sub.is_section_completed) AS completed_sections,
                    ROUND(
                        (SUM(sub.is_section_completed) / NULLIF(COUNT(sub.section_id), 0)) * 100,
                        2
                    ) AS completion_ratio
                FROM (
                    SELECT
                        ua.usrasi_student_id AS student_id,
                        ua.usrasi_section_id AS section_id,
                        CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed
                    FROM lcms_user_assignments ua
                    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                    WHERE c.cou_section_type IN (2, 3)
                      AND ua.usrasi_course_id IN ({$courseIds})
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
                ) AS sub
                GROUP BY sub.student_id
                HAVING total_sections > 0
                ORDER BY completion_ratio ASC, total_sections DESC
                LIMIT ?
            ";

            $results = $this->db()->select($sql, [$limit]);

            return collect($results)->map(function ($r) {
                $info = $this->getStudentInfo($r->student_id);

                return [
                    'student_id' => (int) $r->student_id,
                    'student_user_id' => $info['user_id'],
                    'student_name' => $info['name'],
                    'student_email' => $info['email'],
                    'total_sections' => (int) $r->total_sections,
                    'completed_sections' => (int) $r->completed_sections,
                    'completion_ratio' => (float) $r->completion_ratio,
                ];
            })->toArray();
        });
    }

    /**
     * Get score distribution (number of students in each score range)
     * Ranges: 0-2, 2-4, 4-6, 6-8, 8-10 (thang điểm 10)
     * Separate for homework and tests
     */
    public function getScoreDistribution(): array
    {
        return Cache::remember('lcms:score_distribution', self::CACHE_TTL, function () {
            $result = [];
            foreach ([self::SECTION_TYPE_HOMEWORK => 'homework', self::SECTION_TYPE_TEST => 'test'] as $type => $label) {
                $result[$label] = $this->getScoreDistributionByType($type);
            }
            return $result;
        });
    }

    /**
     * Get score distribution for a specific section type
     */
    private function getScoreDistributionByType(int $sectionType): array
    {
        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

        $sql = "
            SELECT
                CASE
                    WHEN student_avg < 2 THEN '0-2'
                    WHEN student_avg < 4 THEN '2-4'
                    WHEN student_avg < 6 THEN '4-6'
                    WHEN student_avg < 8 THEN '6-8'
                    ELSE '8-10'
                END AS score_range,
                COUNT(*) AS student_count
            FROM (
                SELECT
                    sub.student_id,
                    ROUND(AVG(sub.avg_section_score), 2) AS student_avg
                FROM (
                    SELECT
                        ua.usrasi_student_id AS student_id,
                        ua.usrasi_section_id AS section_id,
                        (
                            SELECT
                                CASE
                                    WHEN COUNT(max_scores.highest_score) >= (
                                        SELECT COUNT(*)
                                        FROM lcms_courses
                                        WHERE cou_parent_id = ua.usrasi_section_id
                                          AND cou_type = 'quiz'
                                    )
                                    THEN AVG(max_scores.highest_score)
                                    ELSE NULL
                                END
                            FROM (
                                SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                                FROM lcms_courses c_child
                                JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                                WHERE c_child.cou_parent_id = ua.usrasi_section_id
                                  AND c_child.cou_type = 'quiz'
                                  AND ss.stusco_student_id = ua.usrasi_student_id
                                GROUP BY c_child.cou_id
                            ) AS max_scores
                        ) AS avg_section_score
                    FROM lcms_user_assignments ua
                    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                    WHERE c.cou_section_type = ?
                      AND ua.usrasi_course_id IN ({$courseIds})
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
                ) AS sub
                GROUP BY sub.student_id
                HAVING student_avg IS NOT NULL
            ) AS dist
            GROUP BY score_range
            ORDER BY FIELD(score_range, '0-2', '2-4', '4-6', '6-8', '8-10')
        ";

        $results = $this->db()->select($sql, [$sectionType]);

        // Ensure all ranges exist
        $ranges = ['0-2' => 0, '2-4' => 0, '4-6' => 0, '6-8' => 0, '8-10' => 0];
        foreach ($results as $r) {
            $ranges[$r->score_range] = (int) $r->student_count;
        }

        return array_map(fn($range, $count) => [
            'range' => $range,
            'count' => $count,
        ], array_keys($ranges), array_values($ranges));
    }

    /**
     * Get completion trend over time (monthly)
     * Uses usrasi_completion_time from lcms_user_assignments
     */
    public function getCompletionTrend(): array
    {
        return Cache::remember('lcms:completion_trend', self::CACHE_TTL, function () {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            $sql = "
                SELECT
                    DATE_FORMAT(ua.usrasi_completion_time, '%Y-%m') AS month,
                    COUNT(*) AS completions
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE ua.usrasi_completion_state = 1
                  AND ua.usrasi_completion_time IS NOT NULL
                  AND ua.usrasi_course_id IN ({$courseIds})
                  AND c.cou_section_type IN (2, 3)
                GROUP BY DATE_FORMAT(ua.usrasi_completion_time, '%Y-%m')
                ORDER BY month ASC
            ";

            $results = $this->db()->select($sql);

            return collect($results)->map(fn($r) => [
                'month' => $r->month,
                'completions' => (int) $r->completions,
            ])->toArray();
        });
    }

    /**
     * Get course enrollment overview from lcms_course_student table
     * Shows enrollment counts, sync status, and completion info per course
     */
    public function getEnrollmentOverview(): array
    {
        return Cache::remember('lcms:enrollment_overview', self::CACHE_TTL, function () {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            $sql = "
                SELECT
                    cs.coustu_course_id AS course_id,
                    c.cou_name AS course_name,
                    COUNT(*) AS total_enrolled,
                    SUM(CASE WHEN cs.coustu_course_end IS NOT NULL AND cs.coustu_course_end != '' THEN 1 ELSE 0 END) AS completed_course,
                    SUM(CASE WHEN cs.coustu_is_sync = 1 THEN 1 ELSE 0 END) AS synced_count,
                    SUM(CASE WHEN cs.coustu_is_sync = 0 THEN 1 ELSE 0 END) AS unsynced_count
                FROM lcms_course_student cs
                LEFT JOIN lcms_courses c ON cs.coustu_course_id = c.cou_id
                WHERE cs.coustu_course_id IN ({$courseIds})
                GROUP BY cs.coustu_course_id, c.cou_name
                ORDER BY cs.coustu_course_id
            ";

            $results = $this->db()->select($sql);

            return collect($results)->map(fn($r) => [
                'course_id' => (int) $r->course_id,
                'course_name' => $r->course_name ?? "Course #{$r->course_id}",
                'total_enrolled' => (int) $r->total_enrolled,
                'completed_course' => (int) $r->completed_course,
                'synced_count' => (int) $r->synced_count,
                'unsynced_count' => (int) $r->unsynced_count,
            ])->toArray();
        });
    }

    /**
     * Get student demographics from lcms_students table
     * Returns gender distribution and student count linked to SpeakWell courses
     */
    public function getStudentDemographics(): array
    {
        return Cache::remember('lcms:student_demographics', self::CACHE_TTL, function () {
            $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);

            // Total LCMS students in SpeakWell courses
            // Phase 128: usrasi_student_id maps to stu_id (PK), not stu_user_id
            $totalSql = "
                SELECT COUNT(DISTINCT s.stu_id) AS total
                FROM lcms_students s
                WHERE s.stu_id IN (
                    SELECT DISTINCT ua.usrasi_student_id
                    FROM lcms_user_assignments ua
                    WHERE ua.usrasi_course_id IN ({$courseIds})
                )
            ";
            $total = (int) ($this->db()->selectOne($totalSql)->total ?? 0);

            // Gender distribution
            $genderSql = "
                SELECT
                    COALESCE(NULLIF(s.stu_gender, ''), 'Không xác định') AS gender,
                    COUNT(*) AS count
                FROM lcms_students s
                WHERE s.stu_id IN (
                    SELECT DISTINCT ua.usrasi_student_id
                    FROM lcms_user_assignments ua
                    WHERE ua.usrasi_course_id IN ({$courseIds})
                )
                GROUP BY gender
                ORDER BY count DESC
            ";
            $genderResults = $this->db()->select($genderSql);

            // Total active assignments (base for unique_students)
            $assignmentSql = "
                SELECT
                    COUNT(*) AS total_assignments,
                    COUNT(DISTINCT usrasi_student_id) AS unique_students,
                    COUNT(DISTINCT usrasi_section_id) AS unique_sections
                FROM lcms_user_assignments
                WHERE usrasi_course_id IN ({$courseIds})
            ";
            $assignments = $this->db()->selectOne($assignmentSql);
            $uniqueStudents = (int) ($assignments->unique_students ?? 0);

            // Students with scores (use same base: usrasi_student_id from assignments)
            $scoreStatusSql = "
                SELECT
                    COUNT(DISTINCT ua.usrasi_student_id) AS students_with_scores
                FROM lcms_user_assignments ua
                WHERE ua.usrasi_course_id IN ({$courseIds})
                  AND ua.usrasi_student_id IN (
                      SELECT DISTINCT ss.stusco_student_id
                      FROM lcms_student_scores ss
                  )
            ";
            $withScores = (int) ($this->db()->selectOne($scoreStatusSql)->students_with_scores ?? 0);
            $withoutScores = max(0, $uniqueStudents - $withScores);

            return [
                'total_lcms_students' => $total,
                'gender_distribution' => collect($genderResults)->map(fn($r) => [
                    'gender' => $r->gender,
                    'count' => (int) $r->count,
                ])->toArray(),
                'students_with_scores' => $withScores,
                'students_without_scores' => $withoutScores,
                'total_assignments' => (int) ($assignments->total_assignments ?? 0),
                'unique_students' => $uniqueStudents,
                'unique_sections' => (int) ($assignments->unique_sections ?? 0),
            ];
        });
    }

    /**
     * Clear all LCMS cache entries (Phase 122)
     * Called by the "Làm mới" button on the LCMS page
     * Phase 180: Added new cache keys for top_students, at_risk_students, section_distribution
     */
    public function clearLcmsCache(): bool
    {
        try {
            $keys = [
                'lcms:overview_stats',
                'lcms:course_breakdown',
                'lcms:score_distribution',
                'lcms:completion_trend',
                'lcms:enrollment_overview',
                'lcms:student_demographics',
                'lcms:section_distribution',
                'lcms:top_students:10',
                'lcms:at_risk_students:10',
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            // Record the refresh timestamp
            Cache::put('lcms:cache_refreshed_at', now()->toIso8601String(), 86400);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to clear LCMS cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get LCMS cache refresh timestamp (Phase 122)
     */
    public function getCacheRefreshedAt(): ?string
    {
        return Cache::get('lcms:cache_refreshed_at');
    }

    /**
     * Enhanced student search with multiple criteria (Phase 122)
     * Supports: multiple IDs, name search, gender filter, completion/score range filters
     */
    public function getStudentStatsAdvanced(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $courseIds = !empty($filters['course_id'])
            ? [(int) $filters['course_id']]
            : self::SPEAKWELL_COURSE_IDS;
        $courseIdsList = implode(',', $courseIds);

        // Build conditions
        $havingClauses = [];
        $outerWhere = [];
        $bindings = [];

        // Multi-ID search (comma-separated)
        // Phase 128: Search by stu_user_id (user-facing ID) instead of stu_id/usrasi_student_id
        if (!empty($filters['student_ids'])) {
            $ids = array_filter(array_map('trim', explode(',', $filters['student_ids'])));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $outerWhere[] = "sub.student_id IN (SELECT stu_id FROM lcms_students WHERE stu_user_id IN ({$placeholders}))";
                $bindings = array_merge($bindings, $ids);
            }
        }

        // Name search (search in lcms_students and tbl_users)
        $nameSearchJoin = '';
        if (!empty($filters['search_name'])) {
            $nameSearchJoin = "
                LEFT JOIN (
                    SELECT CAST(stu_user_id AS UNSIGNED) AS uid, stu_name, stu_gender
                    FROM lcms_students
                ) ls ON ls.uid = sub.student_id
                LEFT JOIN (
                    SELECT user_id AS uid, CONCAT(user_first_name, ' ', user_last_name) AS full_name
                    FROM tbl_users
                ) tu ON tu.uid = sub.student_id
            ";
            $outerWhere[] = "(ls.stu_name LIKE ? OR tu.full_name LIKE ?)";
            $searchTerm = '%' . $filters['search_name'] . '%';
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        // Gender filter
        $genderJoin = '';
        if (!empty($filters['gender']) && empty($nameSearchJoin)) {
            $genderJoin = "
                LEFT JOIN (
                    SELECT CAST(stu_user_id AS UNSIGNED) AS uid, stu_gender
                    FROM lcms_students
                ) ls ON ls.uid = sub.student_id
            ";
            $outerWhere[] = "ls.stu_gender = ?";
            $bindings[] = $filters['gender'];
        } elseif (!empty($filters['gender']) && !empty($nameSearchJoin)) {
            $outerWhere[] = "ls.stu_gender = ?";
            $bindings[] = $filters['gender'];
        }

        $joinClause = $nameSearchJoin ?: $genderJoin;
        $whereClause = !empty($outerWhere) ? 'WHERE ' . implode(' AND ', $outerWhere) : '';

        // Count total
        $countSql = "
            SELECT COUNT(*) AS total FROM (
                SELECT sub.student_id, sub.course_id
                FROM (
                    SELECT
                        ua.usrasi_student_id AS student_id,
                        ua.usrasi_course_id AS course_id
                    FROM lcms_user_assignments ua
                    WHERE ua.usrasi_course_id IN ({$courseIdsList})
                    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id
                ) AS sub
                {$joinClause}
                {$whereClause}
            ) AS cnt
        ";

        $totalResult = $this->db()->selectOne($countSql, $bindings);
        $total = (int) ($totalResult->total ?? 0);

        // Get paginated student list
        $offset = ($page - 1) * $perPage;
        $studentListSql = "
            SELECT sub.student_id, sub.course_id
            FROM (
                SELECT
                    ua.usrasi_student_id AS student_id,
                    ua.usrasi_course_id AS course_id
                FROM lcms_user_assignments ua
                WHERE ua.usrasi_course_id IN ({$courseIdsList})
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id
            ) AS sub
            {$joinClause}
            {$whereClause}
            ORDER BY sub.student_id ASC, sub.course_id ASC
            LIMIT ? OFFSET ?
        ";

        $paginationBindings = array_merge($bindings, [$perPage, $offset]);
        $studentList = $this->db()->select($studentListSql, $paginationBindings);

        // For each student-course pair, get homework & test stats
        $students = [];
        foreach ($studentList as $row) {
            $hw = $this->getStudentSectionStats($row->student_id, $row->course_id, self::SECTION_TYPE_HOMEWORK);
            $test = $this->getStudentSectionStats($row->student_id, $row->course_id, self::SECTION_TYPE_TEST);
            $studentInfo = $this->getStudentInfo($row->student_id);
            $courseInfo = $this->db()->selectOne("SELECT cou_name FROM lcms_courses WHERE cou_id = ?", [$row->course_id]);

            // Apply post-fetch filters for completion/score ranges
            if (!empty($filters['min_hw_completion']) || !empty($filters['max_hw_completion'])) {
                $minHw = (float) ($filters['min_hw_completion'] ?? 0);
                $maxHw = (float) ($filters['max_hw_completion'] ?? 100);
                if ($hw['completion_ratio'] < $minHw || $hw['completion_ratio'] > $maxHw) continue;
            }
            if (!empty($filters['min_score']) || !empty($filters['max_score'])) {
                $minScore = (float) ($filters['min_score'] ?? 0);
                $maxScore = (float) ($filters['max_score'] ?? 10);
                $avgScore = $hw['avg_score'] ?? $test['avg_score'];
                if ($avgScore === null) {
                    if (!empty($filters['min_score'])) continue; // Skip students with no score if min score is set
                } elseif ($avgScore < $minScore || $avgScore > $maxScore) {
                    continue;
                }
            }

            $students[] = [
                'student_id' => (int) $row->student_id,
                'student_user_id' => $studentInfo['user_id'],
                'student_name' => $studentInfo['name'],
                'student_email' => $studentInfo['email'],
                'student_gender' => $studentInfo['gender'],
                'course_id' => (int) $row->course_id,
                'course_name' => $courseInfo->cou_name ?? "Course #{$row->course_id}",
                'homework' => $hw,
                'test' => $test,
            ];
        }

        return [
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    /**
     * Get deep per-student report for a single student across all SpeakWell courses (Phase 122)
     * Provides section-level breakdown with scores
     */
    public function getStudentDetailReport(int $studentId): array
    {
        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);
        $studentInfo = $this->getStudentInfo($studentId);

        // Get all courses this student is enrolled in
        $coursesSql = "
            SELECT DISTINCT ua.usrasi_course_id AS course_id, c.cou_name
            FROM lcms_user_assignments ua
            LEFT JOIN lcms_courses c ON ua.usrasi_course_id = c.cou_id
            WHERE ua.usrasi_student_id = ?
              AND ua.usrasi_course_id IN ({$courseIds})
            ORDER BY ua.usrasi_course_id
        ";
        $coursesResult = $this->db()->select($coursesSql, [$studentId]);

        $courses = [];
        $overallHwTotal = 0;
        $overallHwCompleted = 0;
        $overallTestTotal = 0;
        $overallTestCompleted = 0;
        $allHwScores = [];
        $allTestScores = [];

        foreach ($coursesResult as $course) {
            $hw = $this->getStudentSectionStats($studentId, $course->course_id, self::SECTION_TYPE_HOMEWORK);
            $test = $this->getStudentSectionStats($studentId, $course->course_id, self::SECTION_TYPE_TEST);

            // Get section-level detail for this course
            $sectionDetailSql = "
                SELECT
                    ua.usrasi_section_id AS section_id,
                    c.cou_name AS section_name,
                    c.cou_section_type AS section_type,
                    CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_completed,
                    MIN(ua.usrasi_completion_time) AS completion_time,
                    (
                        SELECT
                            CASE
                                WHEN COUNT(max_scores.highest_score) >= (
                                    SELECT COUNT(*)
                                    FROM lcms_courses
                                    WHERE cou_parent_id = ua.usrasi_section_id
                                      AND cou_type = 'quiz'
                                )
                                THEN ROUND(AVG(max_scores.highest_score), 2)
                                ELSE NULL
                            END
                        FROM (
                            SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) AS highest_score
                            FROM lcms_courses c_child
                            JOIN lcms_student_scores ss ON c_child.cou_id = ss.stusco_course_id
                            WHERE c_child.cou_parent_id = ua.usrasi_section_id
                              AND c_child.cou_type = 'quiz'
                              AND ss.stusco_student_id = ua.usrasi_student_id
                            GROUP BY c_child.cou_id
                        ) AS max_scores
                    ) AS section_score
                FROM lcms_user_assignments ua
                JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
                WHERE ua.usrasi_student_id = ?
                  AND ua.usrasi_course_id = ?
                  AND c.cou_section_type IN (2, 3)
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
                ORDER BY c.cou_section_type, ua.usrasi_section_id
            ";

            $sections = $this->db()->select($sectionDetailSql, [$studentId, $course->course_id]);

            $sectionDetails = collect($sections)->map(fn($s) => [
                'section_id' => (int) $s->section_id,
                'section_name' => $s->section_name ?? "Section #{$s->section_id}",
                'section_type' => (int) $s->section_type,
                'section_type_label' => $s->section_type == 2 ? 'BTVN' : 'BKT',
                'is_completed' => (int) $s->is_completed,
                'completion_time' => $s->completion_time,
                'score' => $s->section_score !== null ? (float) $s->section_score : null,
            ])->toArray();

            $overallHwTotal += $hw['total_sections'];
            $overallHwCompleted += $hw['completed_sections'];
            $overallTestTotal += $test['total_sections'];
            $overallTestCompleted += $test['completed_sections'];
            if ($hw['avg_score'] !== null) $allHwScores[] = $hw['avg_score'];
            if ($test['avg_score'] !== null) $allTestScores[] = $test['avg_score'];

            $courses[] = [
                'course_id' => (int) $course->course_id,
                'course_name' => $course->cou_name ?? "Course #{$course->course_id}",
                'homework' => $hw,
                'test' => $test,
                'sections' => $sectionDetails,
            ];
        }

        // Get enrollment status from lcms_course_student
        $enrollmentSql = "
            SELECT coustu_course_id, coustu_course_end, coustu_is_sync
            FROM lcms_course_student
            WHERE coustu_student_id = ?
              AND coustu_course_id IN ({$courseIds})
        ";
        $enrollments = $this->db()->select($enrollmentSql, [$studentId]);
        $enrollmentMap = collect($enrollments)->keyBy('coustu_course_id')->toArray();

        return [
            'student_id' => $studentId,
            'student_user_id' => $studentInfo['user_id'],
            'student_name' => $studentInfo['name'],
            'student_email' => $studentInfo['email'],
            'student_gender' => $studentInfo['gender'],
            'student_dob' => $studentInfo['dob'],
            'summary' => [
                'total_courses' => count($courses),
                'homework' => [
                    'total_sections' => $overallHwTotal,
                    'completed_sections' => $overallHwCompleted,
                    'completion_ratio' => $overallHwTotal > 0 ? round(($overallHwCompleted / $overallHwTotal) * 100, 2) : 0,
                    'avg_score' => !empty($allHwScores) ? round(array_sum($allHwScores) / count($allHwScores), 2) : null,
                ],
                'test' => [
                    'total_sections' => $overallTestTotal,
                    'completed_sections' => $overallTestCompleted,
                    'completion_ratio' => $overallTestTotal > 0 ? round(($overallTestCompleted / $overallTestTotal) * 100, 2) : 0,
                    'avg_score' => !empty($allTestScores) ? round(array_sum($allTestScores) / count($allTestScores), 2) : null,
                ],
            ],
            'courses' => $courses,
            'enrollment' => collect($enrollments)->map(fn($e) => [
                'course_id' => (int) $e->coustu_course_id,
                'course_end' => $e->coustu_course_end,
                'is_sync' => (int) $e->coustu_is_sync,
            ])->toArray(),
        ];
    }

    /**
     * Phase 202: Get LCMS homework/test stats for a batch of Zeus Core user_ids.
     * Returns an assoc array keyed by user_id with:
     *   hw_completion_rate, hw_avg_score, test_avg_score
     *
     * @param array $userIds  Array of user IDs from tbl_users (= stu_user_id in lcms_students)
     */
    public function getStudentLcmsStatsBatch(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $courseIds = implode(',', self::SPEAKWELL_COURSE_IDS);
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $bindings = array_map('strval', $userIds);

        // Query 1: Completion rates (homework & test)
        $completionSql = "
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
                WHERE ua.usrasi_course_id IN ({$courseIds})
                  AND ua.usrasi_student_id IN (
                      SELECT stu_id FROM lcms_students WHERE stu_user_id IN ({$placeholders})
                  )
                GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
            ) sub
            JOIN lcms_courses c ON sub.usrasi_section_id = c.cou_id
            JOIN lcms_students ls ON sub.usrasi_student_id = ls.stu_id
            WHERE c.cou_section_type = 2
            GROUP BY ls.stu_user_id
        ";

        // Query 2: Average scores (homework & test) using join-based approach
        $scoreSql = "
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
                          SELECT stu_id FROM lcms_students WHERE stu_user_id IN ({$placeholders})
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
        ";

        $result = [];
        try {
            // Execute completion query
            $completionRows = $this->db()->select($completionSql, $bindings);
            foreach ($completionRows as $row) {
                $uid = (int) $row->user_id;
                $result[$uid] = [
                    'hw_completion_rate' => $row->hw_completion_rate !== null ? (float) $row->hw_completion_rate : null,
                    'hw_avg_score' => null,
                    'test_avg_score' => null,
                ];
            }

            // Execute score query
            $scoreRows = $this->db()->select($scoreSql, $bindings);
            foreach ($scoreRows as $row) {
                $uid = (int) $row->user_id;
                if (!isset($result[$uid])) {
                    $result[$uid] = [
                        'hw_completion_rate' => null,
                        'hw_avg_score' => null,
                        'test_avg_score' => null,
                    ];
                }
                $result[$uid]['hw_avg_score'] = $row->hw_avg_score !== null ? (float) $row->hw_avg_score : null;
                $result[$uid]['test_avg_score'] = $row->test_avg_score !== null ? (float) $row->test_avg_score : null;
            }
        } catch (\Exception $e) {
            Log::warning('LCMS batch stats error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Phase 202: Get LCMS summary for a single Zeus Core user_id.
     * Returns hw_completion_rate, hw_avg_score, test_avg_score.
     *
     * @param int $userId  user_id from tbl_users (= stu_user_id in lcms_students)
     */
    public function getStudentLcmsSummary(int $userId): array
    {
        $result = $this->getStudentLcmsStatsBatch([$userId]);
        return $result[$userId] ?? [
            'hw_completion_rate' => null,
            'hw_avg_score' => null,
            'test_avg_score' => null,
        ];
    }

    /**
     * Lookup student info from lcms_students table
     * Falls back to tbl_users if not found in lcms_students
     * Phase 128: usrasi_student_id maps to stu_id (PK), not stu_user_id
     */
    public function getStudentInfo(int $studentId): array
    {
        // Try lcms_students first (usrasi_student_id maps to stu_id PK)
        $lcmsStudent = $this->db()->selectOne("
            SELECT stu_id, stu_user_id, stu_name, stu_email, stu_gender, stu_dob
            FROM lcms_students
            WHERE stu_id = ?
        ", [$studentId]);

        if ($lcmsStudent && $lcmsStudent->stu_name) {
            return [
                'name' => $lcmsStudent->stu_name,
                'email' => $lcmsStudent->stu_email ?? '',
                'gender' => $lcmsStudent->stu_gender ?? '',
                'dob' => $lcmsStudent->stu_dob ?? '',
                'user_id' => $lcmsStudent->stu_user_id ?? (string) $studentId,
            ];
        }

        // Fallback to tbl_users
        $user = $this->db()->selectOne("
            SELECT user_first_name, user_last_name, user_email FROM tbl_users WHERE user_id = ?
        ", [$studentId]);

        if ($user) {
            return [
                'name' => trim(($user->user_first_name ?? '') . ' ' . ($user->user_last_name ?? '')) ?: "Student #{$studentId}",
                'email' => $user->user_email ?? '',
                'gender' => '',
                'dob' => '',
                'user_id' => (string) $studentId,
            ];
        }

        return [
            'name' => "Student #{$studentId}",
            'email' => '',
            'gender' => '',
            'dob' => '',
            'user_id' => (string) $studentId,
        ];
    }

}
