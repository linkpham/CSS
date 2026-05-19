<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CsiService
{
    private string $conn = 'mysql';

    /**
     * Phase 219: EASYSPEAK subject IDs (8 subjects)
     * Used to classify ordles_tlang_id into program names (SPEAKWELL vs EASYSPEAK).
     */
    private const EASYSPEAK_IDS = [403, 404, 471, 582, 583, 584, 585, 586];

    /**
     * Return the FIND_IN_SET SQL fragment that dynamically reads SPEAKWELL subject IDs
     * from tbl_configurations (conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS').
     *
     * @param string $column  The column to check, e.g. 'l.ordles_tlang_id'
     */
    private function speakwellFindInSet(string $column): string
    {
        return "FIND_IN_SET({$column}, (SELECT REPLACE(conf_val, ' ', '') FROM tbl_configurations WHERE conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS' LIMIT 1))";
    }

    /**
     * Return the SQL fragment that excludes trial lessons by comparing against
     * CONF_TRIAL_SUBJECT_ID from tbl_configurations.
     * Phase 173: First lesson should be after trial (post-payment), not the trial itself.
     *
     * @param string $column  The column to check, e.g. 'l.ordles_tlang_id'
     */
    private function excludeTrialSubject(string $column): string
    {
        return "{$column} != (SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID' LIMIT 1)";
    }

    /**
     * Check if zeus_core CSI data is available
     * Phase 167: Use FIND_IN_SET with CONF_SPEAKWELL_SUBJECT_IDS from config to exclude TRIAL
     */
    public function isAvailable(): bool
    {
        $findInSet = $this->speakwellFindInSet('l.ordles_tlang_id');
        try {
            $result = DB::connection($this->conn)->select("
                SELECT COUNT(*) as cnt
                FROM tbl_order_lessons l
                WHERE l.ordles_beneficiary_id IS NOT NULL
                  AND l.ordles_beneficiary_id > 0
                  AND l.ordles_status IN (3)
                  AND {$findInSet}
                  AND l.ordles_lesson_starttime > '2025-11-04'
                  AND l.ordles_lesson_starttime IS NOT NULL
                  AND l.ordles_lesson_starttime <= NOW()
                LIMIT 1
            ");
            return !empty($result) && $result[0]->cnt > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get metadata (live data from zeus_core MySQL)
     */
    public function getMeta(): array
    {
        return [
            'imported_at' => now()->format('Y-m-d H:i:s') . ' (trực tiếp từ Zeus Core)',
            'source' => 'zeus_core MySQL',
        ];
    }

    /**
     * Build the base CTE SQL for CSI calculation
     * Tables: tbl_order_lessons, tbl_order_lessons_extras, tbl_users, tbl_user_settings, tbl_user_extras, tbl_admin
     * Phase 167: Use FIND_IN_SET with CONF_SPEAKWELL_SUBJECT_IDS from tbl_configurations
     *            to exclude TRIAL students (replaces INNER JOIN tbl_orders + order_payment_status).
     *            Extras: ORDER BY ole_id DESC to get the latest extras row.
     * Phase 165: Optimized – single 'joined' CTE with correlated scalar subquery for acceptance_code.
     * Phase 181: Reverted Phase 174 – no longer exclude students with ordpay_pmethod_id = 0.
     *
     * @param array $filters  Optional filters; supports 'date_from' and 'date_to' (Y-m-d)
     */
    private function baseCte(array $filters = []): string
    {
        $findInSet = $this->speakwellFindInSet('l.ordles_tlang_id');

        // Date range: default '2025-11-04' to NOW() unless overridden by filters
        $dateFrom = '2025-11-04';
        if (!empty($filters['date_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_from']);
            if ($d && $d->format('Y-m-d') === $filters['date_from']) {
                $dateFrom = $filters['date_from'];
            }
        }

        $dateToExpr = 'NOW()';
        if (!empty($filters['date_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_to']);
            if ($d && $d->format('Y-m-d') === $filters['date_to']) {
                $dateToExpr = "'" . $filters['date_to'] . " 23:59:59'";
            }
        }

        return "
            WITH joined AS (
                SELECT
                    l.ordles_id,
                    l.ordles_beneficiary_id,
                    l.ordles_lesson_starttime,
                    l.ordles_tlang_id,
                    (
                        SELECT ex.ole_acceptance_code
                        FROM tbl_order_lessons_extras ex
                        WHERE ex.ole_ordles_id = l.ordles_id
                        ORDER BY ex.ole_id DESC
                        LIMIT 1
                    ) AS ole_acceptance_code
                FROM tbl_order_lessons l
                WHERE l.ordles_beneficiary_id IS NOT NULL
                  AND l.ordles_beneficiary_id > 0
                  AND l.ordles_status IN (3)
                  AND {$findInSet}
                  AND l.ordles_lesson_starttime >= '{$dateFrom}'
                  AND l.ordles_lesson_starttime IS NOT NULL
                  AND l.ordles_lesson_starttime <= {$dateToExpr}
            )
        ";
    }

    /**
     * Build the full CTE with aggregated student-level CSI data
     * All metrics (total_scheduled, total_success, student_noshow, student_half,
     * teacher_noshow, health_score, success_rate) are computed
     * from filtered lesson data (date range from baseCte).
     * Additional filters (health_category, css_staff, teacher_warning, search) are
     * applied via buildWhereClause() on the resulting csi_full CTE.
     *
     * Phase 167: Use FIND_IN_SET with CONF_SPEAKWELL_SUBJECT_IDS from config;
     *            removed tbl_orders join; extras ORDER BY ole_id DESC.
     * Phase 165: Optimized – eliminated first3_all_lessons & first3_extras CTEs.
     *   - No date_from filter: reuse 'joined' CTE directly for first-3 ranking
     *   - With date_from filter: use a single optimized CTE with correlated subquery
     *     and INNER JOIN instead of slow IN (SELECT … FROM CTE) patterns.
     *
     * @param array $filters  Optional filters; passed through to baseCte() for date range
     */
    private function fullCte(array $filters = []): string
    {
        $findInSet = $this->speakwellFindInSet('l.ordles_tlang_id');
        $dateToExpr = 'NOW()';
        if (!empty($filters['date_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_to']);
            if ($d && $d->format('Y-m-d') === $filters['date_to']) {
                $dateToExpr = "'" . $filters['date_to'] . " 23:59:59'";
            }
        }

        // Calculate total weeks in filter period for avg_per_week metric (Phase 169)
        $dateFromForWeeks = !empty($filters['date_from']) ? $filters['date_from'] : '2025-11-04';
        $dateToForWeeks = !empty($filters['date_to']) ? $filters['date_to'] : date('Y-m-d');
        $diffDays = max((int) ((new \DateTime($dateToForWeeks))->diff(new \DateTime($dateFromForWeeks))->days), 1);
        $totalWeeks = $diffDays / 7;

        // Check if date_from filter is applied (needs wider date range for first-3 lessons)
        $hasDateFromFilter = false;
        if (!empty($filters['date_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_from']);
            if ($d && $d->format('Y-m-d') === $filters['date_from']) {
                $hasDateFromFilter = true;
            }
        }

        if ($hasDateFromFilter) {
            // date_from filter is set: joined has a narrower date range than needed
            // for first-3 calculation. We need a separate CTE with the full date range
            // (from 2025-11-04) but only for students already in joined.
            $excludeTrial = $this->excludeTrialSubject('l.ordles_tlang_id');
            $first3Sql = "
            first3_source AS (
                SELECT
                    l.ordles_id,
                    l.ordles_beneficiary_id,
                    l.ordles_lesson_starttime,
                    COALESCE(
                        (SELECT ex.ole_acceptance_code
                         FROM tbl_order_lessons_extras ex
                         WHERE ex.ole_ordles_id = l.ordles_id
                         ORDER BY ex.ole_id DESC
                         LIMIT 1),
                        0
                    ) AS f3_acceptance_code
                FROM tbl_order_lessons l
                INNER JOIN (SELECT DISTINCT ordles_beneficiary_id FROM joined) j_stu
                    ON l.ordles_beneficiary_id = j_stu.ordles_beneficiary_id
                WHERE l.ordles_status IN (3)
                  AND {$findInSet}
                  AND {$excludeTrial}
                  AND l.ordles_lesson_starttime >= '2025-11-04'
                  AND l.ordles_lesson_starttime IS NOT NULL
                  AND l.ordles_lesson_starttime <= {$dateToExpr}
            ),
            first_3_ranked AS (
                SELECT
                    f.ordles_beneficiary_id,
                    f.ordles_lesson_starttime,
                    f.f3_acceptance_code,
                    ROW_NUMBER() OVER (
                        PARTITION BY f.ordles_beneficiary_id
                        ORDER BY f.ordles_lesson_starttime ASC, f.ordles_id ASC
                    ) AS lesson_no
                FROM first3_source f
            ),";
        } else {
            // No date_from filter: joined already contains ALL lessons from 2025-11-04.
            // Reuse joined directly for first-3 ranking – no extra table scan needed.
            // Phase 173: Exclude trial lessons so first lesson is post-trial (after payment).
            $excludeTrialJoined = $this->excludeTrialSubject('j.ordles_tlang_id');
            $first3Sql = "
            first_3_ranked AS (
                SELECT
                    j.ordles_beneficiary_id,
                    j.ordles_lesson_starttime,
                    COALESCE(j.ole_acceptance_code, 0) AS f3_acceptance_code,
                    ROW_NUMBER() OVER (
                        PARTITION BY j.ordles_beneficiary_id
                        ORDER BY j.ordles_lesson_starttime ASC, j.ordles_id ASC
                    ) AS lesson_no
                FROM joined j
                WHERE {$excludeTrialJoined}
            ),";
        }

        return $this->baseCte($filters) . ",
            {$first3Sql}
            first_3_pivot AS (
                SELECT
                    f.ordles_beneficiary_id AS f3_student_id,
                    MAX(CASE WHEN f.lesson_no = 1 THEN f.ordles_lesson_starttime END) AS lesson_1_date,
                    MAX(CASE WHEN f.lesson_no = 1 THEN f.f3_acceptance_code END) AS lesson_1_code,
                    MAX(CASE WHEN f.lesson_no = 2 THEN f.ordles_lesson_starttime END) AS lesson_2_date,
                    MAX(CASE WHEN f.lesson_no = 2 THEN f.f3_acceptance_code END) AS lesson_2_code,
                    MAX(CASE WHEN f.lesson_no = 3 THEN f.ordles_lesson_starttime END) AS lesson_3_date,
                    MAX(CASE WHEN f.lesson_no = 3 THEN f.f3_acceptance_code END) AS lesson_3_code,
                    SUM(CASE WHEN f.f3_acceptance_code IN (9, 12) THEN 1 ELSE 0 END) AS first_3_success,
                    COUNT(*) AS first_3_total
                FROM first_3_ranked f
                WHERE f.lesson_no <= 3
                GROUP BY f.ordles_beneficiary_id
            ),
            leave_per_student AS (
                SELECT
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) AS learner_id,
                    COUNT(*) AS leave_sessions
                FROM tbl_teacher_leave_requests lr
                INNER JOIN tbl_teacher_leave_request_sessions lrs ON lr.tlr_id = lrs.tlrs_leave_request_id
                WHERE lr.tlr_status IN (2, 3)
                  AND lrs.tlrs_session_date >= '{$dateFromForWeeks}'
                  AND lrs.tlrs_session_date <= '{$dateToForWeeks}'
                GROUP BY learner_id
                HAVING learner_id IS NOT NULL AND learner_id > 0
            ),
            csi_data AS (
                SELECT
                    j.ordles_beneficiary_id as student_id,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) as student_name,
                    u.user_email as email,
                    MAX(us.user_phone_number) as phone,
                    MAX(a.admin_username) as css_staff,

                    COUNT(*) AS total_scheduled,

                    SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) AS total_success,

                    SUM(CASE WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS student_noshow,

                    SUM(CASE WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 1 ELSE 0 END) AS student_half,

                    SUM(CASE WHEN j.ole_acceptance_code IN (0, 2, 3) OR j.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS teacher_noshow,

                    COALESCE(MAX(lps.leave_sessions), 0) AS leave_sessions,

                    ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score,

                    SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 1.0 / COUNT(*) AS success_rate,

                    MAX(f3p.lesson_1_date) AS lesson_1_date,
                    MAX(f3p.lesson_1_code) AS lesson_1_code,
                    MAX(f3p.lesson_2_date) AS lesson_2_date,
                    MAX(f3p.lesson_2_code) AS lesson_2_code,
                    MAX(f3p.lesson_3_date) AS lesson_3_date,
                    MAX(f3p.lesson_3_code) AS lesson_3_code,
                    MAX(f3p.first_3_success) AS first_3_success,
                    MAX(f3p.first_3_total) AS first_3_total,
                    CASE WHEN MAX(f3p.first_3_total) > 0
                        THEN ROUND(MAX(f3p.first_3_success) * 100.0 / MAX(f3p.first_3_total), 1)
                        ELSE NULL
                    END AS first_3_success_rate,

                    ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) / {$totalWeeks}, 2) AS avg_per_week,

                    SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) AS total_success_12,
                    ROUND(SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS ontrack_score,

                    GROUP_CONCAT(DISTINCT
                        CASE WHEN j.ordles_tlang_id IN (" . implode(',', self::EASYSPEAK_IDS) . ")
                            THEN 'EASYSPEAK' ELSE 'SPEAKWELL' END
                        ORDER BY 1 SEPARATOR ', '
                    ) AS course_names

                FROM joined j
                INNER JOIN tbl_users u ON j.ordles_beneficiary_id = u.user_id
                LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
                LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
                LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
                LEFT JOIN first_3_pivot f3p ON f3p.f3_student_id = j.ordles_beneficiary_id
                LEFT JOIN leave_per_student lps ON lps.learner_id = j.ordles_beneficiary_id
                GROUP BY j.ordles_beneficiary_id, u.user_last_name, u.user_first_name, u.user_email
            ),
            csi_full AS (
                SELECT
                    d.*,
                    CASE
                        WHEN d.health_score >= 85 THEN 'Xanh (Khỏe mạnh)'
                        WHEN d.health_score >= 60 THEN 'Vàng (Cảnh báo)'
                        ELSE 'Đỏ (Báo động)'
                    END AS health_category,
                    CASE
                        WHEN d.teacher_noshow >= 4 THEN 'Khẩn cấp (GV nghỉ >= 4 buổi)'
                        WHEN d.teacher_noshow >= 2 THEN 'Nghiêm trọng (GV nghỉ >=2b)'
                        WHEN d.teacher_noshow = 1 THEN 'Có ảnh hưởng (GV nghỉ 1b)'
                        ELSE 'Bình thường'
                    END AS teacher_warning
                FROM csi_data d
            )
        ";
    }

    /**
     * Build WHERE clause and bindings from filter array
     */
    private function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $bindings = [];

        if (!empty($filters['health_category'])) {
            $cat = $filters['health_category'];
            if ($cat === 'red_yellow') {
                $conditions[] = "health_category IN ('Đỏ (Báo động)', 'Vàng (Cảnh báo)')";
            } elseif ($cat === 'red') {
                $conditions[] = "health_category = ?";
                $bindings[] = 'Đỏ (Báo động)';
            } elseif ($cat === 'yellow') {
                $conditions[] = "health_category = ?";
                $bindings[] = 'Vàng (Cảnh báo)';
            } elseif ($cat === 'green') {
                $conditions[] = "health_category = ?";
                $bindings[] = 'Xanh (Khỏe mạnh)';
            }
            // 'all' or 'no_class' → no filter (live query only returns students with lessons)
        }

        if (!empty($filters['css_staff'])) {
            $conditions[] = "css_staff = ?";
            $bindings[] = $filters['css_staff'];
        }

        if (!empty($filters['teacher_warning'])) {
            $tw = $filters['teacher_warning'];
            if ($tw === 'has_warning') {
                $conditions[] = "teacher_warning != 'Bình thường'";
            } else {
                $conditions[] = "teacher_warning = ?";
                $bindings[] = $tw;
            }
        }

        // Phase 202: Ontrack status filter
        if (!empty($filters['ontrack_status'])) {
            $ot = $filters['ontrack_status'];
            if ($ot === 'ontrack') {
                $conditions[] = "health_score >= 90";
            } elseif ($ot === 'not_ontrack') {
                $conditions[] = "health_score < 90";
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $conditions[] = "(CAST(student_id AS CHAR) LIKE ? OR email LIKE ? OR student_name LIKE ? OR phone LIKE ?)";
            $bindings = array_merge($bindings, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
        }

        // Filter by first lesson date (lesson_1_date)
        if (!empty($filters['lesson_1_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['lesson_1_from']);
            if ($d && $d->format('Y-m-d') === $filters['lesson_1_from']) {
                $conditions[] = "lesson_1_date >= ?";
                $bindings[] = $filters['lesson_1_from'] . ' 00:00:00';
            }
        }
        if (!empty($filters['lesson_1_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['lesson_1_to']);
            if ($d && $d->format('Y-m-d') === $filters['lesson_1_to']) {
                $conditions[] = "lesson_1_date <= ?";
                $bindings[] = $filters['lesson_1_to'] . ' 23:59:59';
            }
        }

        // Filter by first-3-sessions date range (students who completed 3 first lessons within a period)
        // first_3_from: lesson_1_date >= this date (first lesson on or after)
        // first_3_to:   lesson_3_date <= this date (third lesson on or before)
        // Both require first_3_total >= 3 (student has at least 3 lessons)
        if (!empty($filters['first_3_from']) || !empty($filters['first_3_to'])) {
            $conditions[] = "first_3_total >= 3";
        }
        if (!empty($filters['first_3_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['first_3_from']);
            if ($d && $d->format('Y-m-d') === $filters['first_3_from']) {
                $conditions[] = "lesson_1_date >= ?";
                $bindings[] = $filters['first_3_from'] . ' 00:00:00';
            }
        }
        if (!empty($filters['first_3_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['first_3_to']);
            if ($d && $d->format('Y-m-d') === $filters['first_3_to']) {
                $conditions[] = "lesson_3_date <= ?";
                $bindings[] = $filters['first_3_to'] . ' 23:59:59';
            }
        }

        // Phase 220: Program filter (SPEAKWELL / EASYSPEAK)
        if (!empty($filters['program'])) {
            $prog = $filters['program'];
            if (in_array($prog, ['SPEAKWELL', 'EASYSPEAK'])) {
                $conditions[] = "course_names LIKE ?";
                $bindings[] = "%{$prog}%";
            }
        }

        $whereStr = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$whereStr, $bindings];
    }

    /**
     * Get summary statistics for KPI cards.
     * ALL metrics are computed from filtered data:
     * - Date range (date_from, date_to) → applied at lesson level in baseCte
     * - health_category, css_staff, teacher_warning, search → applied at student level via WHERE
     * Metrics returned: total_students, green/yellow/red counts, total_scheduled, total_success,
     * total_noshow, total_half, total_teacher_noshow, avg_score, success_rate, avg_lessons_per_week
     */
    public function getSummary(array $filters = []): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        $sql = "{$cte}
            SELECT
                COUNT(*) as total_students,
                SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) as green_count,
                SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) as yellow_count,
                SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) as red_count,
                SUM(total_scheduled) as total_scheduled,
                SUM(total_success) as total_success,
                SUM(student_noshow) as total_noshow,
                SUM(student_half) as total_half,
                SUM(teacher_noshow) as total_teacher_noshow,
                ROUND(AVG(health_score), 1) as avg_score,
                SUM(CASE WHEN teacher_warning = 'Bình thường' THEN 1 ELSE 0 END) as tw_normal,
                SUM(CASE WHEN teacher_warning = 'Có ảnh hưởng (GV nghỉ 1b)' THEN 1 ELSE 0 END) as tw_affect_1,
                SUM(CASE WHEN teacher_warning = 'Nghiêm trọng (GV nghỉ >=2b)' THEN 1 ELSE 0 END) as tw_serious_2,
                SUM(CASE WHEN teacher_warning = 'Khẩn cấp (GV nghỉ >= 4 buổi)' THEN 1 ELSE 0 END) as tw_critical_4,
                SUM(CASE WHEN total_success_12 > 0 THEN 1 ELSE 0 END) as total_active,
                SUM(CASE WHEN ontrack_score >= 90 THEN 1 ELSE 0 END) as ontrack_count,
                ROUND(AVG(avg_per_week), 2) as avg_lessons_per_week_avg
            FROM csi_full
            {$where}
        ";

        try {
            $result = DB::connection($this->conn)->select($sql, $bindings);
            $row = $result[0] ?? null;

            if (!$row || $row->total_students == 0) {
                return $this->emptySummary();
            }

            $total = (int) $row->total_students;
            $green = (int) $row->green_count;
            $yellow = (int) $row->yellow_count;
            $red = (int) $row->red_count;
            $totalScheduled = (int) $row->total_scheduled;
            $totalSuccess = (int) $row->total_success;

            // Phase 185: avg_lessons_per_week = average of per-student avg_per_week values
            $avgLessonsPerWeek = round((float) ($row->avg_lessons_per_week_avg ?? 0), 2);

            // Phase 200: Ontrack rate = average of per-period ontrack rates from detailed table
            // Each period's ontrack = HV ontrack (≥90% success) / Tổng HV active × 100
            $totalActive = (int) $row->total_active;
            $ontrackCount = (int) $row->ontrack_count;
            $ontrackTrends = $this->getOntrackTrends($filters, 'week');
            $ontrackRate = 0;
            if (!empty($ontrackTrends)) {
                $rates = array_map(fn($t) => $t['ontrack_rate'] ?? 0, $ontrackTrends);
                $ontrackRate = count($rates) > 0 ? round(array_sum($rates) / count($rates), 1) : 0;
            }

            // Phase 189: SpeakWell student stats (Total, Active, Inactive)
            $speakwellStats = $this->getSpeakwellStudentStats();

            return [
                'total_students' => $total,
                'green' => $green,
                'yellow' => $yellow,
                'red' => $red,
                'no_class' => 0,
                'green_pct' => $total > 0 ? round($green / $total * 100, 1) : 0,
                'yellow_pct' => $total > 0 ? round($yellow / $total * 100, 1) : 0,
                'red_pct' => $total > 0 ? round($red / $total * 100, 1) : 0,
                'total_scheduled' => $totalScheduled,
                'total_success' => $totalSuccess,
                'total_noshow' => (int) $row->total_noshow,
                'total_half' => (int) $row->total_half,
                'total_teacher_noshow' => (int) $row->total_teacher_noshow,
                'avg_score' => round((float) ($row->avg_score ?? 0), 1),
                'success_rate' => $totalScheduled > 0 ? round($totalSuccess / $totalScheduled * 100, 1) : 0,
                'avg_lessons_per_week' => $avgLessonsPerWeek,
                'total_active' => $totalActive,
                'ontrack_count' => $ontrackCount,
                'ontrack_rate' => $ontrackRate,
                'teacher_warning' => [
                    'normal' => (int) $row->tw_normal,
                    'affect_1' => (int) $row->tw_affect_1,
                    'serious_2' => (int) $row->tw_serious_2,
                    'critical_4' => (int) $row->tw_critical_4,
                ],
                'leave_affected' => $this->getLeaveAffectedSessions($filters),
                'speakwell_total' => $speakwellStats['speakwell_total'],
                'speakwell_active' => $speakwellStats['speakwell_active'],
                'speakwell_inactive' => $speakwellStats['speakwell_inactive'],
            ];
        } catch (\Exception $e) {
            return $this->emptySummary();
        }
    }

    /**
     * Get student list with filters and pagination
     */
    public function getStudents(array $filters = [], int $page = 1, int $perPage = 50, string $sortBy = 'health_score', string $sortDir = 'asc'): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        // Validate sort column
        $allowedSorts = [
            'health_score', 'student_id', 'student_name', 'total_scheduled', 'total_success',
            'student_noshow', 'student_half', 'success_rate', 'teacher_noshow', 'leave_sessions',
            'css_staff', 'lesson_1_date', 'lesson_2_date', 'lesson_3_date', 'first_3_success_rate', 'avg_per_week',
        ];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'health_score';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;

        // Count total
        $countSql = "{$cte} SELECT COUNT(*) as total FROM csi_full {$where}";
        try {
            $countResult = DB::connection($this->conn)->select($countSql, $bindings);
            $total = (int) ($countResult[0]->total ?? 0);
        } catch (\Exception $e) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 1];
        }

        // Fetch paginated data
        $dataSql = "{$cte}
            SELECT * FROM csi_full
            {$where}
            ORDER BY {$sortBy} {$sortDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        try {
            $students = DB::connection($this->conn)->select($dataSql, $bindings);
        } catch (\Exception $e) {
            $students = [];
        }

        return [
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Get unique CSS staff list for filter dropdown
     */
    public function getCssStaffList(): array
    {
        $cte = $this->fullCte();
        $sql = "{$cte}
            SELECT DISTINCT css_staff
            FROM csi_full
            WHERE css_staff IS NOT NULL AND css_staff != ''
            ORDER BY css_staff
        ";

        try {
            $results = DB::connection($this->conn)->select($sql);
            return array_map(fn($r) => $r->css_staff, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get EWS (Early Warning System) data - students with consecutive missed lessons
     * Computed by counting consecutive noshow/halftime from the most recent lesson backwards
     */
    public function getEwsStudents(array $filters = [], int $page = 1, int $perPage = 50, string $sortBy = 'total_missed', string $sortDir = 'desc'): array
    {
        $cte = $this->baseCte($filters) . ",
            student_lessons_ranked AS (
                SELECT
                    j.ordles_beneficiary_id as user_id,
                    CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 0 ELSE 1 END AS is_missed,
                    ROW_NUMBER() OVER (
                        PARTITION BY j.ordles_beneficiary_id
                        ORDER BY j.ordles_lesson_starttime DESC
                    ) as rn
                FROM joined j
            ),
            first_non_missed AS (
                SELECT user_id, MIN(rn) as first_ok_rn
                FROM student_lessons_ranked
                WHERE is_missed = 0
                GROUP BY user_id
            ),
            student_totals AS (
                SELECT user_id, COUNT(*) as total_lessons
                FROM student_lessons_ranked
                GROUP BY user_id
            ),
            ews_calc AS (
                SELECT user_id, total_missed FROM (
                    SELECT
                        st.user_id,
                        COALESCE(fnm.first_ok_rn, st.total_lessons + 1) - 1 as total_missed
                    FROM student_totals st
                    LEFT JOIN first_non_missed fnm ON st.user_id = fnm.user_id
                ) ews_sub
                WHERE total_missed > 0
            ),
            last_success AS (
                SELECT
                    j.ordles_beneficiary_id as user_id,
                    MAX(j.ordles_lesson_starttime) as last_success_time
                FROM joined j
                WHERE j.ole_acceptance_code IN (3, 6, 9, 12)
                GROUP BY j.ordles_beneficiary_id
            ),
            ews_full AS (
                SELECT
                    e.user_id as student_id,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) as student_name,
                    us.user_phone_number as phone,
                    u.user_email as email,
                    e.total_missed,
                    a.admin_username as css_staff,
                    ls.last_success_time
                FROM ews_calc e
                INNER JOIN tbl_users u ON e.user_id = u.user_id
                LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
                LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
                LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
                LEFT JOIN last_success ls ON e.user_id = ls.user_id
            )
        ";

        // Build EWS-specific filters
        $conditions = [];
        $bindings = [];

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $conditions[] = "(CAST(student_id AS CHAR) LIKE ? OR student_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $bindings = array_merge($bindings, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
        }
        if (!empty($filters['css_staff'])) {
            $conditions[] = "css_staff = ?";
            $bindings[] = $filters['css_staff'];
        }
        if (!empty($filters['min_missed']) && (int) $filters['min_missed'] > 0) {
            $conditions[] = "total_missed >= ?";
            $bindings[] = (int) $filters['min_missed'];
        }

        $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Validate sort
        $allowedSorts = ['total_missed', 'student_id', 'student_name', 'css_staff', 'last_success_time'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'total_missed';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;

        // Count
        $countSql = "{$cte} SELECT COUNT(*) as total FROM ews_full {$where}";
        try {
            $countResult = DB::connection($this->conn)->select($countSql, $bindings);
            $total = (int) ($countResult[0]->total ?? 0);
        } catch (\Exception $e) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 1];
        }

        // Fetch data
        $dataSql = "{$cte}
            SELECT * FROM ews_full
            {$where}
            ORDER BY {$sortBy} {$sortDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        try {
            $students = DB::connection($this->conn)->select($dataSql, $bindings);
        } catch (\Exception $e) {
            $students = [];
        }

        return [
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Get health distribution for chart
     */
    public function getHealthDistribution(array $filters = []): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        $sql = "{$cte}
            SELECT health_category, COUNT(*) as count
            FROM csi_full
            {$where}
            GROUP BY health_category
            ORDER BY count DESC
        ";

        try {
            return DB::connection($this->conn)->select($sql, $bindings);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get CSS staff performance breakdown
     */
    public function getCssPerformance(array $filters = []): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        // Add css_staff non-null filter
        $extraCondition = "css_staff IS NOT NULL AND css_staff != ''";
        if (!empty($where)) {
            $where .= " AND {$extraCondition}";
        } else {
            $where = "WHERE {$extraCondition}";
        }

        $sql = "{$cte}
            SELECT
                css_staff,
                COUNT(*) as total,
                SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) as green,
                SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) as yellow,
                SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) as red,
                ROUND(AVG(health_score), 1) as avg_score,
                ROUND(AVG(success_rate) * 100, 1) as avg_success_rate
            FROM csi_full
            {$where}
            GROUP BY css_staff
            ORDER BY css_staff
        ";

        try {
            return DB::connection($this->conn)->select($sql, $bindings);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get score distribution histogram data
     */
    public function getScoreDistribution(array $filters = []): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        $sql = "{$cte}
            SELECT
                CASE
                    WHEN health_score >= 0 AND health_score <= 20 THEN '0-20'
                    WHEN health_score > 20 AND health_score <= 40 THEN '21-40'
                    WHEN health_score > 40 AND health_score <= 60 THEN '41-60'
                    WHEN health_score > 60 AND health_score <= 80 THEN '61-80'
                    WHEN health_score > 80 AND health_score <= 100 THEN '81-100'
                    ELSE '< 0'
                END as label,
                COUNT(*) as count,
                CASE
                    WHEN health_score >= 0 AND health_score <= 20 THEN 1
                    WHEN health_score > 20 AND health_score <= 40 THEN 2
                    WHEN health_score > 40 AND health_score <= 60 THEN 3
                    WHEN health_score > 60 AND health_score <= 80 THEN 4
                    WHEN health_score > 80 AND health_score <= 100 THEN 5
                    ELSE 0
                END as sort_order
            FROM csi_full
            {$where}
            GROUP BY label, sort_order
            ORDER BY sort_order
        ";

        try {
            $results = DB::connection($this->conn)->select($sql, $bindings);
            // Ensure all ranges are represented
            $rangeMap = [];
            foreach ($results as $row) {
                $rangeMap[$row->label] = (int) $row->count;
            }

            $ranges = ['0-20', '21-40', '41-60', '61-80', '81-100'];
            $output = [];
            foreach ($ranges as $label) {
                $output[] = [
                    'label' => $label,
                    'count' => $rangeMap[$label] ?? 0,
                ];
            }
            // Add "< 0" bucket if any scores are negative
            if (isset($rangeMap['< 0']) && $rangeMap['< 0'] > 0) {
                array_unshift($output, [
                    'label' => '< 0',
                    'count' => $rangeMap['< 0'],
                ]);
            }
            return $output;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get teacher warning distribution
     */
    public function getTeacherWarningDistribution(array $filters = []): array
    {
        $cte = $this->fullCte($filters);
        [$where, $bindings] = $this->buildWhereClause($filters);

        $sql = "{$cte}
            SELECT teacher_warning, COUNT(*) as count
            FROM csi_full
            {$where}
            GROUP BY teacher_warning
            ORDER BY count DESC
        ";

        try {
            return DB::connection($this->conn)->select($sql, $bindings);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get detailed information for a single student:
     *   - CSI summary (health_score, category, success_rate, etc.)
     *   - Full lesson history with acceptance codes (ordered DESC)
     *   - Consecutive noshow streak from the most recent lesson
     *
     * @param int   $studentId  The student (beneficiary) user ID
     * @param array $filters    Optional filters; supports 'date_from' and 'date_to' (Y-m-d)
     */
    public function getStudentDetail(int $studentId, array $filters = []): array
    {
        $cte = $this->fullCte($filters);

        // Student CSI summary
        $infoSql = "{$cte}
            SELECT * FROM csi_full WHERE student_id = ?
        ";

        // All lessons with status
        $baseCte = $this->baseCte($filters);
        $lessonsSql = "{$baseCte}
            SELECT
                j.ordles_id as lesson_id,
                j.ordles_lesson_starttime as lesson_time,
                j.ole_acceptance_code as acceptance_code,
                CASE
                    WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 'success'
                    WHEN j.ole_acceptance_code IN (0, 4, 7, 10) THEN 'noshow'
                    WHEN j.ole_acceptance_code IS NULL THEN 'unknown'
                    WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 'half'
                    ELSE 'unknown'
                END as status,
                CASE
                    WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 'Thành công'
                    WHEN j.ole_acceptance_code IN (0, 4, 7, 10) THEN 'HV Noshow'
                    WHEN j.ole_acceptance_code IS NULL THEN 'Chưa có dữ liệu'
                    WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 'HV < 1/2 giờ'
                    ELSE 'Không xác định'
                END as status_label,
                CASE
                    WHEN j.ole_acceptance_code IN (0, 2, 3) OR j.ole_acceptance_code IS NULL THEN 1
                    ELSE 0
                END as is_teacher_noshow
            FROM joined j
            WHERE j.ordles_beneficiary_id = ?
            ORDER BY j.ordles_lesson_starttime DESC
        ";

        try {
            $info = DB::connection($this->conn)->select($infoSql, [$studentId]);
            $lessons = DB::connection($this->conn)->select($lessonsSql, [$studentId]);

            if (empty($info)) {
                return ['student' => null, 'lessons' => [], 'consecutive_streak' => 0];
            }

            // Calculate consecutive streak from most recent lesson backwards
            $streak = 0;
            foreach ($lessons as $lesson) {
                if ($lesson->status !== 'success') {
                    $streak++;
                } else {
                    break;
                }
            }

            // Fetch payment & package info for this student
            $orders = $this->getStudentOrders($studentId);
            $packages = $this->getStudentPackages($studentId);

            // Phase 190: Fetch teacher leave sessions for session map
            $leaveSessions = $this->getStudentLeaveSessions($studentId, $filters);

            return [
                'student' => $info[0],
                'lessons' => $lessons,
                'consecutive_streak' => $streak,
                'orders' => $orders,
                'packages' => $packages,
                'leave_sessions' => $leaveSessions,
            ];
        } catch (\Exception $e) {
            return ['student' => null, 'lessons' => [], 'consecutive_streak' => 0, 'orders' => [], 'packages' => [], 'leave_sessions' => []];
        }
    }

    /**
     * Get orders associated with a student (beneficiary).
     * Finds orders through tbl_order_lessons where the student is the beneficiary.
     */
    private function getStudentOrders(int $studentId): array
    {
        $sql = "
            SELECT
                o.order_id,
                o.order_type,
                o.order_total_amount,
                o.order_net_amount,
                o.order_discount_value,
                o.order_payment_status,
                o.order_status,
                o.order_addedon,
                o.order_item_count,
                o.order_currency_code,
                pm.pmethod_code AS payment_method
            FROM tbl_orders o
            LEFT JOIN tbl_payment_methods pm ON o.order_pmethod_id = pm.pmethod_id
            WHERE o.order_id IN (
                SELECT DISTINCT ol.ordles_order_id
                FROM tbl_order_lessons ol
                WHERE ol.ordles_beneficiary_id = ?
            )
            ORDER BY o.order_addedon DESC
        ";

        try {
            return DB::connection($this->conn)->select($sql, [$studentId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get subscription plans (packages) associated with a student.
     * Checks both ordsplan_beneficiary_id and orders linked to the student's lessons.
     */
    private function getStudentPackages(int $studentId): array
    {
        $sql = "
            SELECT
                sp.ordsplan_id,
                sp.ordsplan_order_id,
                sp.ordsplan_plan_id,
                sp.ordsplan_amount,
                sp.ordsplan_lesson_amount,
                sp.ordsplan_lessons,
                sp.ordsplan_used_lesson_count,
                sp.ordsplan_validity,
                sp.ordsplan_duration,
                sp.ordsplan_start_date,
                sp.ordsplan_end_date,
                sp.ordsplan_status,
                sp.ordsplan_created,
                sp.ordsplan_refund,
                spl.subplan_title AS plan_title,
                spl.subplan_lesson_count AS plan_lesson_count,
                spl.subplan_price AS plan_price
            FROM tbl_order_subscription_plans sp
            LEFT JOIN tbl_subscription_plans spl ON sp.ordsplan_plan_id = spl.subplan_id
            WHERE sp.ordsplan_beneficiary_id = ?
               OR sp.ordsplan_order_id IN (
                   SELECT DISTINCT ol.ordles_order_id
                   FROM tbl_order_lessons ol
                   WHERE ol.ordles_beneficiary_id = ?
               )
            ORDER BY sp.ordsplan_created DESC
        ";

        try {
            return DB::connection($this->conn)->select($sql, [$studentId, $studentId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get teacher leave sessions that affect a specific student.
     * Phase 190: Used to show teacher leave sessions in the "Bản đồ buổi học" (session map).
     * Queries tbl_teacher_leave_request_sessions joined with tbl_teacher_leave_requests (approved: status 2,3).
     * Returns leave session data including the linked ordles_id and session date.
     *
     * @param int   $studentId  The student (beneficiary) user ID
     * @param array $filters    Optional filters; supports 'date_from' and 'date_to' (Y-m-d)
     */
    private function getStudentLeaveSessions(int $studentId, array $filters = []): array
    {
        $dateFrom = '2025-11-04';
        if (!empty($filters['date_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_from']);
            if ($d && $d->format('Y-m-d') === $filters['date_from']) {
                $dateFrom = $filters['date_from'];
            }
        }

        $dateTo = date('Y-m-d');
        if (!empty($filters['date_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_to']);
            if ($d && $d->format('Y-m-d') === $filters['date_to']) {
                $dateTo = $filters['date_to'];
            }
        }

        $sql = "
            SELECT
                lrs.tlrs_session_id AS lesson_id,
                lrs.tlrs_session_date AS session_date,
                lrs.tlrs_need_replacement,
                lrs.tlrs_replacement_type,
                CONCAT(COALESCE(tu.user_last_name, ''), ' ', COALESCE(tu.user_first_name, '')) AS teacher_name
            FROM tbl_teacher_leave_request_sessions lrs
            INNER JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
            LEFT JOIN tbl_users tu ON lr.tlr_teacher_id = tu.user_id
            WHERE lr.tlr_status IN (2, 3)
              AND lrs.tlrs_session_type = 1
              AND CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) = ?
              AND lrs.tlrs_session_date >= ?
              AND lrs.tlrs_session_date <= ?
            ORDER BY lrs.tlrs_session_date ASC
        ";

        try {
            return DB::connection($this->conn)->select($sql, [$studentId, $dateFrom, $dateTo]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Search student by ID, email, name, or phone
     */
    public function searchStudent(string $keyword): array
    {
        $cte = $this->fullCte();
        $sql = "{$cte}
            SELECT * FROM csi_full
            WHERE CAST(student_id AS CHAR) LIKE ?
               OR email LIKE ?
               OR student_name LIKE ?
               OR phone LIKE ?
            ORDER BY health_score ASC
            LIMIT 100
        ";

        $like = "%{$keyword}%";
        try {
            return DB::connection($this->conn)->select($sql, [$like, $like, $like, $like]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get EWS detail for a specific student: all lessons with status, ordered by time DESC
     * Returns student info + lesson list with acceptance codes for the detail modal
     *
     * @param int   $studentId  The student (beneficiary) user ID
     * @param array $filters    Optional filters; supports 'date_from' and 'date_to' (Y-m-d)
     */
    public function getEwsStudentDetail(int $studentId, array $filters = []): array
    {
        $cte = $this->baseCte($filters);

        // Get student info
        $infoSql = "{$cte}
            SELECT
                j.ordles_beneficiary_id as student_id,
                CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) as student_name,
                u.user_email as email,
                MAX(us.user_phone_number) as phone,
                MAX(a.admin_username) as css_staff,
                COUNT(*) as total_lessons,
                SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) as total_success,
                SUM(CASE WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) as total_noshow,
                SUM(CASE WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 1 ELSE 0 END) as total_half,
                MAX(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN j.ordles_lesson_starttime ELSE NULL END) as last_success_time
            FROM joined j
            INNER JOIN tbl_users u ON j.ordles_beneficiary_id = u.user_id
            LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
            LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
            LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
            WHERE j.ordles_beneficiary_id = ?
            GROUP BY j.ordles_beneficiary_id, u.user_last_name, u.user_first_name, u.user_email
        ";

        // Get all lessons with their status
        $lessonsSql = "{$cte}
            SELECT
                j.ordles_id as lesson_id,
                j.ordles_lesson_starttime as lesson_time,
                j.ole_acceptance_code as acceptance_code,
                CASE
                    WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 'success'
                    WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 'noshow'
                    WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 'half'
                    ELSE 'unknown'
                END as status,
                CASE
                    WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 'Thành công'
                    WHEN j.ole_acceptance_code IN (0, 4, 7, 10) THEN 'HV Noshow'
                    WHEN j.ole_acceptance_code IS NULL THEN 'Chưa có dữ liệu'
                    WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 'HV < 1/2 giờ'
                    ELSE 'Không xác định'
                END as status_label
            FROM joined j
            WHERE j.ordles_beneficiary_id = ?
            ORDER BY j.ordles_lesson_starttime DESC
        ";

        try {
            $info = DB::connection($this->conn)->select($infoSql, [$studentId]);
            $lessons = DB::connection($this->conn)->select($lessonsSql, [$studentId]);

            if (empty($info)) {
                return ['student' => null, 'lessons' => [], 'consecutive_streak' => 0];
            }

            // Calculate consecutive streak from most recent lesson backwards
            $streak = 0;
            foreach ($lessons as $lesson) {
                if ($lesson->status !== 'success') {
                    $streak++;
                } else {
                    break;
                }
            }

            // Phase 190: Fetch teacher leave sessions for session map
            $leaveSessions = $this->getStudentLeaveSessions($studentId, $filters);

            return [
                'student' => $info[0],
                'lessons' => $lessons,
                'consecutive_streak' => $streak,
                'leave_sessions' => $leaveSessions,
            ];
        } catch (\Exception $e) {
            return ['student' => null, 'lessons' => [], 'consecutive_streak' => 0, 'leave_sessions' => []];
        }
    }

    /**
     * Get weekly or monthly trend data for comparison charts.
     * Returns an array of time-period buckets, each containing:
     *   - period label (e.g. "W01 2026" or "01/2026")
     *   - total_scheduled, total_success, total_noshow, total_half, success_rate
     *
     * @param array  $filters  Standard CSI filters (date_from, date_to, css_staff, etc.)
     * @param string $groupBy  'week' or 'month'
     */
    public function getTrends(array $filters = [], string $groupBy = 'week'): array
    {
        $cte = $this->baseCte($filters);

        // Choose grouping expression
        if ($groupBy === 'month') {
            $periodExpr = "DATE_FORMAT(j.ordles_lesson_starttime, '%Y-%m')";
            $periodLabel = "DATE_FORMAT(j.ordles_lesson_starttime, '%m/%Y')";
            $orderExpr = "DATE_FORMAT(j.ordles_lesson_starttime, '%Y-%m')";
        } else {
            // ISO week: YEARWEEK(date, 3) gives YYYYWW with ISO weeks
            $periodExpr = "YEARWEEK(j.ordles_lesson_starttime, 3)";
            $periodLabel = "CONCAT('W', LPAD(WEEK(j.ordles_lesson_starttime, 3), 2, '0'), ' ', YEAR(j.ordles_lesson_starttime))";
            $orderExpr = "YEARWEEK(j.ordles_lesson_starttime, 3)";
        }

        // Build optional student filter (css_staff, health_category, etc.)
        // For trends, we aggregate at the lesson level, so we join user info for css_staff filter
        $joinUser = '';
        $extraConditions = '';
        $bindings = [];

        if (!empty($filters['css_staff'])) {
            $joinUser = "
                INNER JOIN tbl_users u_t ON j.ordles_beneficiary_id = u_t.user_id
                LEFT JOIN tbl_user_extras ue_t ON u_t.user_id = ue_t.usrextra_user_id
                LEFT JOIN tbl_admin a_t ON ue_t.usrextra_css_id = a_t.admin_id
            ";
            $extraConditions .= " AND a_t.admin_username = ?";
            $bindings[] = $filters['css_staff'];
        }

        $sql = "{$cte}
            SELECT
                {$periodLabel} as period_label,
                {$orderExpr} as period_order,
                COUNT(*) as total_scheduled,
                SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) as total_success,
                SUM(CASE WHEN j.ole_acceptance_code IN (0, 4, 7, 10) OR j.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) as total_noshow,
                SUM(CASE WHEN j.ole_acceptance_code IN (2, 5, 8, 11) THEN 1 ELSE 0 END) as total_half,
                ROUND(
                    SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                    1
                ) as success_rate,
                COUNT(DISTINCT j.ordles_beneficiary_id) as unique_students
            FROM joined j
            {$joinUser}
            WHERE 1=1 {$extraConditions}
            GROUP BY period_label, period_order
            ORDER BY period_order ASC
        ";

        try {
            $results = DB::connection($this->conn)->select($sql, $bindings);
            return array_map(function ($row) {
                return [
                    'period' => $row->period_label,
                    'total_scheduled' => (int) $row->total_scheduled,
                    'total_success' => (int) $row->total_success,
                    'total_noshow' => (int) $row->total_noshow,
                    'total_half' => (int) $row->total_half,
                    'success_rate' => (float) $row->success_rate,
                    'unique_students' => (int) $row->unique_students,
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get weekly or monthly health-category trend data.
     * For each period, calculates per-student health scores using only that period's lessons,
     * then counts how many students fall into each category (green/yellow/red).
     *
     * @param array  $filters  Standard CSI filters (date_from, date_to, css_staff, etc.)
     * @param string $groupBy  'week' or 'month'
     */
    public function getHealthTrends(array $filters = [], string $groupBy = 'week'): array
    {
        $cte = $this->baseCte($filters);

        // Choose grouping expression
        if ($groupBy === 'month') {
            $periodExpr = "DATE_FORMAT(j.ordles_lesson_starttime, '%Y-%m')";
            $periodLabel = "DATE_FORMAT(j.ordles_lesson_starttime, '%m/%Y')";
            $orderExpr = "DATE_FORMAT(j.ordles_lesson_starttime, '%Y-%m')";
        } else {
            $periodExpr = "YEARWEEK(j.ordles_lesson_starttime, 3)";
            $periodLabel = "CONCAT('W', LPAD(WEEK(j.ordles_lesson_starttime, 3), 2, '0'), ' ', YEAR(j.ordles_lesson_starttime))";
            $orderExpr = "YEARWEEK(j.ordles_lesson_starttime, 3)";
        }

        // Optional css_staff filter
        $joinUser = '';
        $extraConditions = '';
        $bindings = [];

        if (!empty($filters['css_staff'])) {
            $joinUser = "
                INNER JOIN tbl_users u_t ON j.ordles_beneficiary_id = u_t.user_id
                LEFT JOIN tbl_user_extras ue_t ON u_t.user_id = ue_t.usrextra_user_id
                LEFT JOIN tbl_admin a_t ON ue_t.usrextra_css_id = a_t.admin_id
            ";
            $extraConditions .= " AND a_t.admin_username = ?";
            $bindings[] = $filters['css_staff'];
        }

        $sql = "{$cte},
            period_student_scores AS (
                SELECT
                    {$periodLabel} as period_label,
                    {$orderExpr} as period_order,
                    j.ordles_beneficiary_id as student_id,
                    ROUND(
                        SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                        1
                    ) AS health_score
                FROM joined j
                {$joinUser}
                WHERE 1=1 {$extraConditions}
                GROUP BY period_label, period_order, j.ordles_beneficiary_id
            )
            SELECT
                period_label,
                period_order,
                COUNT(*) as total_students,
                SUM(CASE WHEN health_score >= 85 THEN 1 ELSE 0 END) as green_count,
                SUM(CASE WHEN health_score >= 60 AND health_score < 85 THEN 1 ELSE 0 END) as yellow_count,
                SUM(CASE WHEN health_score < 60 THEN 1 ELSE 0 END) as red_count
            FROM period_student_scores
            GROUP BY period_label, period_order
            ORDER BY period_order ASC
        ";

        try {
            $results = DB::connection($this->conn)->select($sql, $bindings);
            return array_map(function ($row) {
                return [
                    'period' => $row->period_label,
                    'total_students' => (int) $row->total_students,
                    'green' => (int) $row->green_count,
                    'yellow' => (int) $row->yellow_count,
                    'red' => (int) $row->red_count,
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get weekly or monthly OnTrack trend data.
     * Phase 200: Ontrack rate = HV ontrack / Tổng HV active × 100 (student/student, not student/session).
     * For each period, calculates per-student success rate, then aggregates:
     *   total_students = all distinct students in the period (= HV active)
     *   ontrack_by_success = students with ≥90% successful sessions (codes 3,6,9,12) in the period
     *   ontrack_rate = ontrack_by_success / total_students * 100
     *
     * @param array  $filters  Standard CSI filters (date_from, date_to, css_staff, etc.)
     * @param string $groupBy  'week' or 'month'
     */
    public function getOntrackTrends(array $filters = [], string $groupBy = 'week'): array
    {
        $cte = $this->baseCte($filters);

        // Choose grouping expression
        if ($groupBy === 'month') {
            $periodLabel = "DATE_FORMAT(j.ordles_lesson_starttime, '%m/%Y')";
            $orderExpr = "DATE_FORMAT(j.ordles_lesson_starttime, '%Y-%m')";
        } else {
            $periodLabel = "CONCAT('W', LPAD(WEEK(j.ordles_lesson_starttime, 3), 2, '0'), ' ', YEAR(j.ordles_lesson_starttime))";
            $orderExpr = "YEARWEEK(j.ordles_lesson_starttime, 3)";
        }

        // Optional css_staff filter
        $joinUser = '';
        $extraConditions = '';
        $bindings = [];

        if (!empty($filters['css_staff'])) {
            $joinUser = "
                INNER JOIN tbl_users u_t ON j.ordles_beneficiary_id = u_t.user_id
                LEFT JOIN tbl_user_extras ue_t ON u_t.user_id = ue_t.usrextra_user_id
                LEFT JOIN tbl_admin a_t ON ue_t.usrextra_css_id = a_t.admin_id
            ";
            $extraConditions .= " AND a_t.admin_username = ?";
            $bindings[] = $filters['css_staff'];
        }

        // Phase 200: Ontrack rate = HV ontrack (≥90% success) / Tổng HV active × 100
        $sql = "{$cte},
            period_student_scores AS (
                SELECT
                    {$periodLabel} as period_label,
                    {$orderExpr} as period_order,
                    j.ordles_beneficiary_id as student_id,
                    COUNT(*) as total_scheduled,
                    SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) as total_success_12,
                    ROUND(SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as ontrack_score,
                    SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) as total_success,
                    ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as success_rate
                FROM joined j
                {$joinUser}
                WHERE 1=1 {$extraConditions}
                GROUP BY period_label, period_order, j.ordles_beneficiary_id
            )
            SELECT
                period_label,
                period_order,
                COUNT(*) as total_students,
                SUM(CASE WHEN total_success_12 > 0 THEN 1 ELSE 0 END) as total_active,
                SUM(total_scheduled) as total_scheduled,
                SUM(CASE WHEN ontrack_score >= 90 THEN 1 ELSE 0 END) as ontrack_count,
                SUM(CASE WHEN success_rate >= 90 THEN 1 ELSE 0 END) as ontrack_by_success,
                ROUND(
                    CASE WHEN COUNT(*) > 0
                    THEN SUM(CASE WHEN success_rate >= 90 THEN 1 ELSE 0 END) * 100.0
                         / COUNT(*)
                    ELSE 0 END, 1
                ) as ontrack_rate
            FROM period_student_scores
            GROUP BY period_label, period_order
            ORDER BY period_order ASC
        ";

        try {
            $results = DB::connection($this->conn)->select($sql, $bindings);
            return array_map(function ($row) {
                return [
                    'period' => $row->period_label,
                    'total_students' => (int) $row->total_students,
                    'total_active' => (int) $row->total_active,
                    'total_scheduled' => (int) $row->total_scheduled,
                    'ontrack_count' => (int) $row->ontrack_count,
                    'ontrack_by_success' => (int) $row->ontrack_by_success,
                    'ontrack_rate' => (float) $row->ontrack_rate,
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get count of sessions affected by approved teacher leave requests
     * within the given date range. Uses tbl_teacher_leave_requests (status 2,3 = approved)
     * joined with tbl_teacher_leave_request_sessions.
     *
     * @param array $filters  Optional filters; supports 'date_from' and 'date_to' (Y-m-d)
     */
    public function getLeaveAffectedSessions(array $filters = []): array
    {
        // Date range: default to CSI baseline start → now
        $dateFrom = '2025-11-04';
        if (!empty($filters['date_from'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_from']);
            if ($d && $d->format('Y-m-d') === $filters['date_from']) {
                $dateFrom = $filters['date_from'];
            }
        }

        $dateTo = date('Y-m-d');
        if (!empty($filters['date_to'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $filters['date_to']);
            if ($d && $d->format('Y-m-d') === $filters['date_to']) {
                $dateTo = $filters['date_to'];
            }
        }

        $sql = "
            SELECT
                COUNT(*) as total_affected_sessions,
                SUM(CASE WHEN lrs.tlrs_need_replacement = 1 THEN 1 ELSE 0 END) as need_replacement,
                SUM(CASE WHEN lrs.tlrs_need_replacement = 0 OR lrs.tlrs_need_replacement IS NULL THEN 1 ELSE 0 END) as no_replacement
            FROM tbl_teacher_leave_requests lr
            INNER JOIN tbl_teacher_leave_request_sessions lrs ON lr.tlr_id = lrs.tlrs_leave_request_id
            WHERE lr.tlr_status IN (2, 3)
              AND lrs.tlrs_session_date >= ?
              AND lrs.tlrs_session_date <= ?
        ";

        try {
            $result = DB::connection($this->conn)->select($sql, [$dateFrom, $dateTo]);
            $row = $result[0] ?? null;
            return [
                'total_affected_sessions' => (int) ($row->total_affected_sessions ?? 0),
                'need_replacement' => (int) ($row->need_replacement ?? 0),
                'no_replacement' => (int) ($row->no_replacement ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'total_affected_sessions' => 0,
                'need_replacement' => 0,
                'no_replacement' => 0,
            ];
        }
    }

    /**
     * SpeakWell + EasySpeak subject IDs (shared between stats and inactive list).
     */
    private const SPW_TLANG_IDS = '389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586';

    /**
     * Get SpeakWell (incl. Easy Speak) student counts: Total, Active (last 30 days), Inactive.
     * Phase 189: Uses hardcoded ordles_tlang_id list as specified.
     * Phase 221: Updated to include tbl_order_classes (group classes) via UNION ALL.
     */
    public function getSpeakwellStudentStats(): array
    {
        $tlangIds = self::SPW_TLANG_IDS;

        // Phase 221: Total SpeakWell students (distinct) from BOTH order_lessons AND order_classes
        $totalSql = "
            SELECT COUNT(DISTINCT t.student_id) AS total FROM (
                SELECT DISTINCT ol.ordles_beneficiary_id AS student_id
                FROM tbl_order_lessons AS ol
                WHERE ol.ordles_tlang_id IN ({$tlangIds})
                UNION ALL
                SELECT DISTINCT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                INNER JOIN tbl_group_classes gce ON oc.ordcls_grpcls_id = gce.grpcls_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
            ) t
        ";

        // Phase 221: Active SpeakWell students (last 30 days) from BOTH lessons AND classes
        $activeSql = "
            SELECT COUNT(DISTINCT t.student_id) AS total FROM (
                SELECT DISTINCT u.user_id AS student_id
                FROM tbl_users AS u
                INNER JOIN tbl_orders AS o ON o.order_user_id = u.user_id
                INNER JOIN tbl_order_lessons AS ol ON ol.ordles_order_id = o.order_id
                WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
                  AND ol.ordles_status = 3
                  AND ol.ordles_tlang_id IN ({$tlangIds})
                  AND ol.ordles_lesson_endtime >= NOW() - INTERVAL 30 DAY
                  AND ol.ordles_lesson_endtime <= NOW()
                UNION ALL
                SELECT DISTINCT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                INNER JOIN tbl_group_classes gce ON oc.ordcls_grpcls_id = gce.grpcls_id
                INNER JOIN tbl_users u ON oc.ordcls_beneficiary_id = u.user_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
                  AND gce.grpcls_status = 2
                  AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
            ) t
        ";

        // Phase 221: Inactive count = all students minus active students
        $inactiveSql = "
            WITH q1 AS (
                SELECT ol.ordles_beneficiary_id AS student_id
                FROM tbl_order_lessons ol
                WHERE ol.ordles_tlang_id IN ({$tlangIds})
                UNION
                SELECT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
            ),
            q2 AS (
                SELECT u.user_id AS student_id
                FROM tbl_users u
                JOIN tbl_orders o ON o.order_user_id = u.user_id
                JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
                WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
                  AND ol.ordles_status = 3
                  AND ol.ordles_tlang_id IN ({$tlangIds})
                  AND ol.ordles_lesson_endtime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()
                UNION
                SELECT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
                JOIN tbl_users u ON u.user_id = oc.ordcls_beneficiary_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
                  AND gce.grpcls_status = 2
                  AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
            )
            SELECT COUNT(DISTINCT q1.student_id) AS total_inactive_users
            FROM q1
            WHERE NOT EXISTS (
                SELECT 1 FROM q2 WHERE q2.student_id = q1.student_id
            )
        ";

        try {
            $totalResult = DB::connection($this->conn)->select($totalSql);
            $activeResult = DB::connection($this->conn)->select($activeSql);
            $inactiveResult = DB::connection($this->conn)->select($inactiveSql);

            $total = (int) ($totalResult[0]->total ?? 0);
            $active = (int) ($activeResult[0]->total ?? 0);
            $inactive = (int) ($inactiveResult[0]->total_inactive_users ?? 0);

            return [
                'speakwell_total' => $total,
                'speakwell_active' => $active,
                'speakwell_inactive' => $inactive,
            ];
        } catch (\Exception $e) {
            return [
                'speakwell_total' => 0,
                'speakwell_active' => 0,
                'speakwell_inactive' => 0,
            ];
        }
    }

    /**
     * Get paginated list of inactive SpeakWell students with remaining lesson counts.
     * Phase 221: Returns student details + scheduled/unscheduled lesson counts.
     * Also returns the count of students where (unscheduled + scheduled) = 0.
     *
     * @param int    $page     Current page
     * @param int    $perPage  Items per page
     * @param string $search   Optional search keyword (ID/name/email)
     * @param string $sortBy   Sort column (Phase 222)
     * @param string $sortDir  Sort direction asc/desc (Phase 222)
     */
    public function getInactiveStudentsList(int $page = 1, int $perPage = 50, string $search = '', string $sortBy = 'remaining_total', string $sortDir = 'asc'): array
    {
        $tlangIds = self::SPW_TLANG_IDS;

        $baseCte = "
            WITH q1 AS (
                SELECT ol.ordles_beneficiary_id AS student_id
                FROM tbl_order_lessons ol
                WHERE ol.ordles_tlang_id IN ({$tlangIds})
                UNION
                SELECT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
            ),
            q2 AS (
                SELECT u.user_id AS student_id
                FROM tbl_users u
                JOIN tbl_orders o ON o.order_user_id = u.user_id
                JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
                WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
                  AND ol.ordles_status = 3
                  AND ol.ordles_tlang_id IN ({$tlangIds})
                  AND ol.ordles_lesson_endtime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()
                UNION
                SELECT oc.ordcls_beneficiary_id AS student_id
                FROM tbl_order_classes oc
                JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
                JOIN tbl_users u ON u.user_id = oc.ordcls_beneficiary_id
                WHERE gce.grpcls_tlang_id IN ({$tlangIds})
                  AND gce.grpcls_status = 2
                  AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
            ),
            inactive_students AS (
                SELECT q1.student_id
                FROM q1
                WHERE NOT EXISTS (
                    SELECT 1 FROM q2 WHERE q2.student_id = q1.student_id
                )
            ),
            remaining_lessons AS (
                SELECT
                    ol.ordles_beneficiary_id AS student_id,
                    SUM(CASE WHEN ol.ordles_status = 1 THEN 1 ELSE 0 END) AS unscheduled_count,
                    SUM(CASE WHEN ol.ordles_status = 2 THEN 1 ELSE 0 END) AS scheduled_count
                FROM tbl_order_lessons ol
                WHERE ol.ordles_tlang_id IN ({$tlangIds})
                  AND ol.ordles_status IN (1, 2)
                GROUP BY ol.ordles_beneficiary_id
            ),
            inactive_full AS (
                SELECT
                    ist.student_id,
                    u.user_username,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                    u.user_email,
                    COALESCE(rl.unscheduled_count, 0) AS unscheduled_count,
                    COALESCE(rl.scheduled_count, 0) AS scheduled_count
                FROM inactive_students ist
                JOIN tbl_users u ON u.user_id = ist.student_id
                LEFT JOIN remaining_lessons rl ON rl.student_id = ist.student_id
            )
        ";

        // Build search condition
        $searchCondition = '';
        $bindings = [];
        if (!empty($search)) {
            $searchCondition = "WHERE (CAST(student_id AS CHAR) LIKE ? OR student_name LIKE ? OR user_email LIKE ? OR user_username LIKE ?)";
            $bindings = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
        }

        try {
            // Count total inactive students (with search)
            $countSql = "{$baseCte} SELECT COUNT(*) AS total FROM inactive_full {$searchCondition}";
            $countResult = DB::connection($this->conn)->select($countSql, $bindings);
            $total = (int) ($countResult[0]->total ?? 0);

            // Count students with zero remaining lessons (unscheduled + scheduled = 0)
            $zeroCountSql = "{$baseCte}
                SELECT COUNT(*) AS zero_count
                FROM inactive_full
                {$searchCondition}" . (!empty($searchCondition) ? " AND " : " WHERE ") . "(unscheduled_count + scheduled_count) = 0";
            $zeroResult = DB::connection($this->conn)->select($zeroCountSql, $bindings);
            $zeroLessonsCount = (int) ($zeroResult[0]->zero_count ?? 0);

            // Phase 222: Build ORDER BY clause from sort params
            $allowedSortCols = [
                'student_id'       => 'student_id',
                'user_username'    => 'user_username',
                'student_name'     => 'student_name',
                'user_email'       => 'user_email',
                'unscheduled_count'=> 'unscheduled_count',
                'scheduled_count'  => 'scheduled_count',
                'remaining_total'  => '(unscheduled_count + scheduled_count)',
            ];
            $orderDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
            $orderCol = $allowedSortCols[$sortBy] ?? '(unscheduled_count + scheduled_count)';
            $orderClause = "ORDER BY {$orderCol} {$orderDir}, student_id ASC";

            // Fetch paginated data
            $offset = ($page - 1) * $perPage;
            $dataSql = "{$baseCte}
                SELECT *,
                       (unscheduled_count + scheduled_count) AS remaining_total
                FROM inactive_full
                {$searchCondition}
                {$orderClause}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            $students = DB::connection($this->conn)->select($dataSql, $bindings);

            // Ensure numeric types for JavaScript (Phase 222)
            $students = array_map(function ($row) {
                $row->unscheduled_count = (int) $row->unscheduled_count;
                $row->scheduled_count   = (int) $row->scheduled_count;
                $row->remaining_total   = (int) $row->remaining_total;
                $row->student_id        = (int) $row->student_id;
                return $row;
            }, $students);

            return [
                'data' => $students,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
                'zero_lessons_count' => $zeroLessonsCount,
            ];
        } catch (\Exception $e) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1,
                'zero_lessons_count' => 0,
            ];
        }
    }

    /**
     * Return empty summary structure
     */
    private function emptySummary(): array
    {
        return [
            'total_students' => 0,
            'green' => 0,
            'yellow' => 0,
            'red' => 0,
            'no_class' => 0,
            'green_pct' => 0,
            'yellow_pct' => 0,
            'red_pct' => 0,
            'total_scheduled' => 0,
            'total_success' => 0,
            'total_noshow' => 0,
            'total_half' => 0,
            'total_teacher_noshow' => 0,
            'avg_score' => 0,
            'success_rate' => 0,
            'avg_lessons_per_week' => 0,
            'total_active' => 0,
            'ontrack_count' => 0,
            'ontrack_rate' => 0,
            'teacher_warning' => [
                'normal' => 0,
                'affect_1' => 0,
                'serious_2' => 0,
                'critical_4' => 0,
            ],
            'leave_affected' => [
                'total_affected_sessions' => 0,
                'need_replacement' => 0,
                'no_replacement' => 0,
            ],
            'speakwell_total' => 0,
            'speakwell_active' => 0,
            'speakwell_inactive' => 0,
        ];
    }
}
