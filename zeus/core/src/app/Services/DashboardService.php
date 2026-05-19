<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderLesson;
use App\Models\OrderLessonExtra;
use App\Models\GroupClass;
use App\Models\TeacherStat;
use App\Models\ReportedIssue;
use App\Models\RatingReview;
use App\Models\Transaction;
use App\Models\TeacherFeedback;
use App\Models\Curriculum;
use App\Models\CurriculumSession;
use App\Models\Program;
use App\Models\ProgramUser;
use App\Models\TeacherLeaveRequest;
use App\Models\TeacherLeaveQuota;
use App\Models\TeacherLeaveViolation;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserLoginLog;
use App\Models\Coupon;
use App\Models\CouponHistory;
use App\Models\CouponLog;
use App\Models\SessionLog;
use App\Models\TeacherLeaveRequestSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardService
{
    /**
     * ALL subject IDs (36 subjects including trial with id 533)
     * Used to filter lessons for session statistics - ALL programs combined
     */
    public const SPEAKWELL_SUBJECT_IDS = [
        533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
        390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
        584, 582, 404, 403, 583, 471
    ];

    /**
     * SPEAKWELL-only subject IDs (28 subjects)
     */
    public const SPEAKWELL_ONLY_SUBJECT_IDS = [
        533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
        390, 392, 405, 406, 407, 411, 412, 577
    ];

    /**
     * Phase 208: SPEAKWELL-only subject IDs excluding trial (533)
     * Used for teacher change detection to exclude trial lessons.
     */
    public const SPEAKWELL_NO_TRIAL_SUBJECT_IDS = [
        558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
        390, 392, 405, 406, 407, 411, 412, 577
    ];

    /**
     * EASY SPEAK subject IDs (8 subjects)
     */
    public const EASY_SPEAK_SUBJECT_IDS = [
        403, 404, 471, 582, 583, 584, 585, 586
    ];

    /**
     * Valid program filter values
     * Phase 142: Removed 'speakwell' and 'easyspeak' (caching too slow), kept only 'all'
     */
    public const VALID_PROGRAMS = ['all'];

    /**
     * Current active program filter
     * Phase 142: Only 'all' is used now
     */
    protected string $activeProgram = 'all';

    /**
     * Set the active program filter
     */
    public function setProgram(string $program): self
    {
        if (in_array($program, self::VALID_PROGRAMS)) {
            $this->activeProgram = $program;
        }
        return $this;
    }

    /**
     * Get the active program filter
     */
    public function getProgram(): string
    {
        return $this->activeProgram;
    }

    /**
     * Get subject IDs for the currently active program
     */
    public function getActiveSubjectIds(): array
    {
        return match ($this->activeProgram) {
            'speakwell' => self::SPEAKWELL_ONLY_SUBJECT_IDS,
            'easyspeak' => self::EASY_SPEAK_SUBJECT_IDS,
            default => self::SPEAKWELL_SUBJECT_IDS, // 'all' = all subjects combined
        };
    }

    /**
     * Acceptance codes that result in teacher penalty (bị phạt)
     * - 1: GV No-show + HV No-show
     * - 2: GV No-show + HV < 1/2
     * - 3: GV No-show + HV bình thường
     * - 6: GV < 1/2 + HV bình thường
     * - 14: Hủy: GV nghỉ sai quy định
     * - 17: Hủy: Lỗi kỹ thuật / bất khả kháng
     */
    public const PENALTY_CODES = [1, 2, 3, 6, 14, 17];

    /**
     * Get stats for a specific date range
     */
    public function getStatsForDateRange(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        return [
            'users' => $this->getUserStatsForRange($start, $end),
            'lessons' => $this->getLessonStatsForRange($start, $end),
            'revenue' => $this->getRevenueStatsForRange($start, $end),
            'orders' => $this->getOrderStatsForRange($start, $end),
        ];
    }

    /**
     * Get user stats for a date range
     */
    public function getUserStatsForRange(Carbon $start, Carbon $end): array
    {
        return [
            'new_users' => User::active()
                ->whereBetween('user_created', [$start, $end])
                ->count(),
            'new_teachers' => User::active()->teachers()
                ->whereBetween('user_created', [$start, $end])
                ->count(),
            'new_learners' => User::active()->learners()
                ->whereBetween('user_created', [$start, $end])
                ->count(),
        ];
    }

    /**
     * Get lesson stats for a date range
     */
    public function getLessonStatsForRange(Carbon $start, Carbon $end): array
    {
        $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$start, $end]);

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->completed()->count(),
            'cancelled' => (clone $baseQuery)->cancelled()->count(),
            'scheduled' => (clone $baseQuery)->scheduled()->count(),
            'completion_rate' => $this->calculateCompletionRateForRange($start, $end),
        ];
    }

    /**
     * Calculate completion rate for date range
     */
    private function calculateCompletionRateForRange(Carbon $start, Carbon $end): float
    {
        $total = OrderLesson::whereBetween('ordles_lesson_starttime', [$start, $end])
            ->whereIn('ordles_status', [OrderLesson::STATUS_SCHEDULED, OrderLesson::STATUS_COMPLETED])
            ->count();

        if ($total === 0) return 0;

        $completed = OrderLesson::whereBetween('ordles_lesson_starttime', [$start, $end])
            ->completed()
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get revenue stats for a date range
     */
    public function getRevenueStatsForRange(Carbon $start, Carbon $end): array
    {
        $paidOrders = Order::paid()->whereBetween('order_addedon', [$start, $end]);

        return [
            'total' => $paidOrders->sum('order_total_amount'),
            'order_count' => $paidOrders->count(),
            'average_order' => $paidOrders->count() > 0
                ? round($paidOrders->sum('order_total_amount') / $paidOrders->count(), 0)
                : 0,
        ];
    }

    /**
     * Get order stats for a date range
     */
    public function getOrderStatsForRange(Carbon $start, Carbon $end): array
    {
        $baseQuery = Order::whereBetween('order_addedon', [$start, $end]);

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('order_status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->where('order_status', Order::STATUS_CANCELLED)->count(),
            'in_process' => (clone $baseQuery)->where('order_status', Order::STATUS_INPROCESS)->count(),
        ];
    }

    /**
     * Get top teachers by lessons taught
     */
    public function getTopTeachers(int $limit = 10): array
    {
        return TeacherStat::select('testat_user_id', 'testat_lessons', 'testat_ratings', 'testat_reviewes', 'testat_students')
            ->join('tbl_users', 'tbl_teacher_stats.testat_user_id', '=', 'tbl_users.user_id')
            ->where('tbl_users.user_deleted', null)
            ->orderByDesc('testat_lessons')
            ->limit($limit)
            ->get()
            ->map(function ($stat) {
                $user = User::find($stat->testat_user_id);
                return [
                    'id' => $stat->testat_user_id,
                    'name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'email' => $user?->user_email ?? '',
                    'lessons' => $stat->testat_lessons,
                    'rating' => round($stat->testat_ratings ?? 0, 2),
                    'reviews' => $stat->testat_reviewes,
                    'students' => $stat->testat_students,
                ];
            })
            ->toArray();
    }

    /**
     * Get top learners by order count
     */
    public function getTopLearners(int $limit = 10): array
    {
        return Order::select('order_user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(order_total_amount) as total_spent'))
            ->paid()
            ->groupBy('order_user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $user = User::find($order->order_user_id);
                return [
                    'id' => $order->order_user_id,
                    'name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'email' => $user?->user_email ?? '',
                    'orders' => $order->order_count,
                    'total_spent' => $order->total_spent,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 10): array
    {
        return Order::select('order_id', 'order_user_id', 'order_type', 'order_status', 'order_payment_status', 'order_total_amount', 'order_addedon')
            ->orderByDesc('order_addedon')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $user = User::find($order->order_user_id);
                return [
                    'id' => $order->order_id,
                    'user_name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'type' => Order::getTypeLabels()[$order->order_type] ?? 'Unknown',
                    'status' => $order->order_status,
                    'payment_status' => $order->order_payment_status,
                    'amount' => $order->order_total_amount,
                    'date' => Carbon::parse($order->order_addedon)->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent lessons
     */
    public function getRecentLessons(int $limit = 10): array
    {
        return OrderLesson::select('tbl_order_lessons.ordles_id', 'tbl_order_lessons.ordles_order_id', 'tbl_order_lessons.ordles_teacher_id', 'tbl_order_lessons.ordles_status', 'tbl_order_lessons.ordles_lesson_starttime', 'tbl_order_lessons.ordles_duration', 'tbl_orders.order_user_id')
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->orderByDesc('tbl_order_lessons.ordles_lesson_starttime')
            ->limit($limit)
            ->get()
            ->map(function ($lesson) {
                $teacher = User::find($lesson->ordles_teacher_id);
                $student = User::find($lesson->order_user_id);
                return [
                    'id' => $lesson->ordles_id,
                    'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                    'status' => $lesson->ordles_status,
                    'status_label' => OrderLesson::getStatusLabel($lesson->ordles_status),
                    'start_time' => $lesson->ordles_lesson_starttime ? Carbon::parse($lesson->ordles_lesson_starttime)->format('d/m/Y H:i') : '',
                    'duration' => $lesson->ordles_duration,
                ];
            })
            ->toArray();
    }

    /**
     * Get pending issues
     */
    public function getPendingIssues(int $limit = 10): array
    {
        return ReportedIssue::select('repiss_id', 'repiss_reported_by', 'repiss_record_type', 'repiss_status', 'repiss_reported_on', 'repiss_title')
            ->whereIn('repiss_status', [ReportedIssue::STATUS_PROGRESS, ReportedIssue::STATUS_ESCALATED])
            ->orderByDesc('repiss_reported_on')
            ->limit($limit)
            ->get()
            ->map(function ($issue) {
                $user = User::find($issue->repiss_reported_by);
                return [
                    'id' => $issue->repiss_id,
                    'user_name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'title' => $issue->repiss_title ?? '',
                    'type' => $issue->repiss_record_type,
                    'status' => $issue->repiss_status,
                    'reported_on' => Carbon::parse($issue->repiss_reported_on)->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Search users by keyword
     */
    public function searchUsers(string $keyword, int $limit = 20): array
    {
        return User::active()
            ->where(function ($query) use ($keyword) {
                $query->where('user_email', 'like', "%{$keyword}%")
                    ->orWhere('user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('user_last_name', 'like', "%{$keyword}%")
                    ->orWhere('user_username', 'like', "%{$keyword}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->user_id,
                    'name' => $user->user_first_name . ' ' . $user->user_last_name,
                    'email' => $user->user_email,
                    'phone' => '', // Phone column doesn't exist in tbl_users
                    'is_teacher' => (bool) $user->user_is_teacher,
                    'is_learner' => !$user->user_is_teacher,
                ];
            })
            ->toArray();
    }

    /**
     * Get conversion funnel stats (Trial -> Paid)
     */
    public function getConversionFunnelStats(): array
    {
        return $this->getCached('dashboard.conversion_funnel', function () {
            $totalTrials = OrderLesson::trial()->count();
            $completedTrials = OrderLesson::trial()->completed()->count();

            // Users who had trial and then made a purchase
            // Get order_user_id via join with tbl_orders through ordles_order_id
            $trialUserIds = OrderLesson::trial()
                ->completed()
                ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
                ->pluck('tbl_orders.order_user_id')
                ->unique();
            
            $convertedUsers = Order::whereIn('order_user_id', $trialUserIds)
                ->where('order_type', '!=', Order::TYPE_LESSON)
                ->paid()
                ->distinct('order_user_id')
                ->count('order_user_id');

            return [
                'total_trials' => $totalTrials,
                'completed_trials' => $completedTrials,
                'trial_completion_rate' => $totalTrials > 0 ? round(($completedTrials / $totalTrials) * 100, 2) : 0,
                'converted_users' => $convertedUsers,
                'conversion_rate' => $trialUserIds->count() > 0 ? round(($convertedUsers / $trialUserIds->count()) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get wallet statistics
     */
    public function getWalletStats(): array
    {
        return $this->getCached('dashboard.wallet_stats', function () {
            $deposits = Transaction::where('usrtxn_type', Transaction::TYPE_MONEY_DEPOSIT)->sum('usrtxn_amount');
            $withdrawals = Transaction::where('usrtxn_type', Transaction::TYPE_MONEY_WITHDRAW)->sum('usrtxn_amount');
            $teacherPayments = Transaction::where('usrtxn_type', Transaction::TYPE_TEACHER_PAYMENT)->sum('usrtxn_amount');
            $refunds = Transaction::where('usrtxn_type', Transaction::TYPE_LEARNER_REFUND)->sum('usrtxn_amount');

            return [
                'total_deposits' => $deposits,
                'total_withdrawals' => $withdrawals,
                'teacher_payments' => $teacherPayments,
                'refunds' => $refunds,
                'net_balance' => $deposits - $withdrawals,
            ];
        });
    }

    /**
     * Get lesson chart data for date range
     */
    public function getLessonChartData(int $days = 30): array
    {
        return $this->getCached("dashboard.lesson_chart_{$days}", function () use ($days) {
            $labels = [];
            $completed = [];
            $cancelled = [];
            $scheduled = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                $dayLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayStart, $dayEnd]);
                $completed[] = (clone $dayLessons)->completed()->count();
                $cancelled[] = (clone $dayLessons)->cancelled()->count();
                $scheduled[] = (clone $dayLessons)->scheduled()->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'scheduled' => $scheduled,
                ],
            ];
        });
    }
    /**
     * Cache TTL in seconds (5 minutes = 300 seconds)
     * Used for Redis caching of dashboard data
     */
    protected const CACHE_TTL = 300;

    /**
     * All cache keys used by dashboard (for bulk clearing)
     * Note: This list includes base keys; keys with parameters like {$days} or {$limit}
     * will be handled by pattern-based clearing
     */
    protected const CACHE_KEY_PREFIX = 'dashboard.';

    /**
     * Get cached data or compute and cache it
     * Uses Redis for automatic caching with 5-minute TTL
     * Cache key automatically includes the active program filter
     */
    protected function getCached(string $key, callable $callback): mixed
    {
        $programKey = $key . '.prog_' . $this->activeProgram;
        return Cache::remember($programKey, self::CACHE_TTL, $callback);
    }

    /**
     * Clear all dashboard cache entries
     * This is called when user clicks "Làm mới" (Refresh) button
     * 
     * @return bool True if cache was cleared successfully
     */
    public function clearDashboardCache(): bool
    {
        // List of all known cache keys (static ones)
        $staticKeys = [
            'dashboard.index_data',
            'dashboard.overview',
            'dashboard.user_stats',
            'dashboard.teacher_stats',
            'dashboard.today_lessons',
            'dashboard.revenue_stats',
            'dashboard.orders_by_type',
            'dashboard.orders_by_status',
            'dashboard.issues',
            'dashboard.ratings',
            'dashboard.conversion_funnel',
            'dashboard.wallet_stats',
            'dashboard.teacher_payment_stats',
            'dashboard.lesson_quality',
            'dashboard.trial_stats',
            'dashboard.trial_conversion',
            'dashboard.trial_by_status',
            'dashboard.learning_path_stats',
            'dashboard.curriculum_session_distribution',
            'dashboard.feedback_stats',
            'dashboard.feedback_status',
            'dashboard.program_enrollment_stats',
            'dashboard.session_outcome',
            'dashboard.session_quality',
            'dashboard.leave_stats',
            'dashboard.leave_by_status',
            'dashboard.leave_violation_stats',
            'dashboard.quiz_stats',
            'dashboard.quiz_attempt_stats',
            'dashboard.leave_quota_summary',
            'dashboard.login_stats',
            'dashboard.comparison_stats',
            'dashboard.session_stats',
            'dashboard.acceptance_codes_list',
            'dashboard.never_logged_in_stats',
            'dashboard.acceptance_code_stats',
            'dashboard.acceptance_code_map_stats',
            'dashboard.monthly_session_trend',
            'dashboard.monthly_acceptance_codes_trend',
            'dashboard.never_logged_in_trend',
            'dashboard.voucher_stats',
        ];

        // Dynamic keys with common parameter values
        $dynamicKeyPatterns = [
            'dashboard.lesson_chart_',
            'dashboard.revenue_chart_',
            'dashboard.user_chart_',
            'dashboard.trial_trend_chart_',
            'dashboard.recent_trial_lessons_',
            'dashboard.top_teachers_by_trial_',
            'dashboard.feedback_trend_',
            'dashboard.top_teachers_by_feedback_',
            'dashboard.recent_feedback_',
            'dashboard.curriculum_session_chart_',
            'dashboard.late_start_lessons_',
            'dashboard.attendance_issues_',
            'dashboard.recent_leave_requests_',
            'dashboard.teachers_most_leave_',
            'dashboard.leave_trend_',
            'dashboard.recent_quiz_attempts_',
            'dashboard.quiz_chart_',
            'dashboard.cancellation_breakdown_',
        ];

        // Common parameter values used in dynamic keys
        $commonDays = [7, 14, 30, 60, 90];
        $commonLimits = [5, 10, 20, 50];

        // Program suffixes for cache keys (Phase 142: only 'all' program, kept old suffixes for cleanup)
        $programSuffixes = ['.prog_all', '.prog_speakwell', '.prog_easyspeak'];

        try {
            // Clear static keys for all program variants
            foreach ($staticKeys as $key) {
                Cache::forget($key);
                foreach ($programSuffixes as $suffix) {
                    Cache::forget($key . $suffix);
                }
            }

            // Clear dynamic keys with common parameter values for all program variants
            foreach ($dynamicKeyPatterns as $pattern) {
                foreach ($commonDays as $days) {
                    Cache::forget($pattern . $days);
                    foreach ($programSuffixes as $suffix) {
                        Cache::forget($pattern . $days . $suffix);
                    }
                }
                foreach ($commonLimits as $limit) {
                    Cache::forget($pattern . $limit);
                    foreach ($programSuffixes as $suffix) {
                        Cache::forget($pattern . $limit . $suffix);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            // Log error but don't throw - cache clearing is not critical
            \Log::warning('Failed to clear dashboard cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all dashboard index data with cache-first approach
     * This is the main method used when loading the dashboard after login.
     * Returns cached data if available, only computes fresh data if cache is empty.
     * 
     * @return array All data required for the dashboard index view
     */
    public function getDashboardIndexData(): array
    {
        return $this->getCached('dashboard.index_data', function () {
            return [
                // Core overview stats
                'overview' => $this->getOverviewStats(),
                'ordersByType' => $this->getOrderStatsByType(),
                'ordersByStatus' => $this->getOrderStatsByStatus(),
                'issues' => $this->getIssueStats(),
                'ratings' => $this->getRatingStats(),
                'revenueChart' => $this->getRevenueChartData(),
                'userChart' => $this->getUserRegistrationChartData(),

                // Phase 2: Top performers and recent data
                'topTeachers' => $this->getTopTeachers(5),
                'topLearners' => $this->getTopLearners(5),
                'recentOrders' => $this->getRecentOrders(5),
                'conversionFunnel' => $this->getConversionFunnelStats(),

                // User Login Statistics
                'loginStats' => $this->getLoginStats(),
                'loginStatsByUserType' => $this->getLoginStatsByUserType(),
                'loginTrendChart' => $this->getLoginTrendChartData(14),
                'recentLogins' => $this->getRecentLogins(5),
                'topActiveUsers' => $this->getTopActiveUsers(5),
                'loginsByHour' => $this->getLoginsByHour(),
                'loginsByDayOfWeek' => $this->getLoginsByDayOfWeek(),
                'loginsBySource' => $this->getLoginsBySource(),

                // Trial Lessons Statistics
                'trialStats' => $this->getTrialLessonStats(),
                'trialByStatus' => $this->getTrialLessonsByStatus(),
                'trialTrendChart' => $this->getTrialLessonTrendChart(14),
                'trialConversion' => $this->getTrialConversionStats(),
                'recentTrialLessons' => $this->getRecentTrialLessons(5),
                'topTeachersByTrial' => $this->getTopTeachersByTrialLessons(5),

                // Program/Curriculum Statistics
                'programStatsSummary' => $this->getProgramStatsSummary(),
                'programCategoryStats' => $this->getProgramCategoryStats(),

                // Session Success/Failure Stats (prioritized)
                'sessionStats' => $this->getMultiPeriodSessionStats(),

                // Never Logged In Students (students with lessons who never logged in)
                'neverLoggedInStats' => $this->getMultiPeriodNeverLoggedInStats(),

                // Teacher Login Status (no-show, late entry, early exit)
                'teacherLoginStatus' => $this->getMultiPeriodTeacherLoginStatusStats(),

                // Acceptance Codes
                'acceptanceCodesList' => $this->getAcceptanceCodesList(),
                'acceptanceCodeStats' => $this->getMultiPeriodAcceptanceCodeStats(),
                'acceptanceCodeMapStats' => $this->getMultiPeriodAcceptanceCodeMapStats(),

                // Monthly Session Trend Chart (success rate, cancel rate, no-show rates)
                'monthlySessionTrendChart' => $this->getMonthlySessionTrendChart(),

                // Monthly Acceptance Codes Trend Chart (chargeable vs compensate codes)
                'monthlyAcceptanceCodesTrendChart' => $this->getMonthlyAcceptanceCodesTrendChart(),

                // Never Logged In Students Trend Chart (daily counts and rates for the month)
                'neverLoggedInTrendChart' => $this->getNeverLoggedInTrendChart(),

                // Voucher (Coupon) Statistics
                'voucherStats' => $this->getMultiPeriodVoucherStats(),

                // Phase 38: Students grouped by class size (1:1, 1:2, 1:3, etc.)
                'studentsByClassSize' => $this->getStudentsByClassSize(),
            ];
        });
    }

    /**
     * Get overview statistics
     */
    public function getOverviewStats(): array
    {
        return $this->getCached('dashboard.overview', function () {
            return [
                'users' => $this->getUserStats(),
                'teachers' => $this->getTeacherStats(),
                'lessons_today' => $this->getTodayLessonStats(),
                'revenue' => $this->getRevenueStats(),
            ];
        });
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        return $this->getCached('dashboard.user_stats', function () {
            $baseQuery = User::active();

            return [
                'total_teachers' => (clone $baseQuery)->teachers()->count(),
                'total_learners' => (clone $baseQuery)->learners()->count(),
                'total_parents' => (clone $baseQuery)->parents()->count(),
                'total_affiliates' => (clone $baseQuery)->affiliates()->count(),
                'new_today' => (clone $baseQuery)->whereDate('user_created', today())->count(),
                'new_this_week' => (clone $baseQuery)->whereBetween('user_created', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_this_month' => (clone $baseQuery)->whereMonth('user_created', now()->month)->whereYear('user_created', now()->year)->count(),
                'active_users' => (clone $baseQuery)->where('user_active', 1)->count(),
                'verified_rate' => $this->calculateVerifiedRate(),
            ];
        });
    }

    /**
     * Calculate verified user rate
     */
    private function calculateVerifiedRate(): float
    {
        $total = User::active()->count();
        if ($total === 0) return 0;

        $verified = User::active()->verified()->count();
        return round(($verified / $total) * 100, 2);
    }

    /**
     * Get teacher statistics
     */
    public function getTeacherStats(): array
    {
        return $this->getCached('dashboard.teacher_stats', function () {
            return [
                'total_lessons_taught' => TeacherStat::sum('testat_lessons'),
                'total_classes_taught' => TeacherStat::sum('testat_classes'),
                'unique_students' => TeacherStat::sum('testat_students'),
                'average_rating' => round(TeacherStat::avg('testat_ratings') ?? 0, 2),
                'total_reviews' => TeacherStat::sum('testat_reviewes'),
                'with_availability' => TeacherStat::withAvailability()->count(),
                'complete_profile' => TeacherStat::completeProfile()->count(),
            ];
        });
    }

    /**
     * Get today's lesson statistics
     */
    public function getTodayLessonStats(): array
    {
        return $this->getCached('dashboard.today_lessons', function () {
            $todayLessons = OrderLesson::today();
            $todayGroupClasses = GroupClass::today();

            return [
                'total_sessions' => (clone $todayLessons)->count() + (clone $todayGroupClasses)->count(),
                'lessons' => [
                    'total' => (clone $todayLessons)->count(),
                    'completed' => (clone $todayLessons)->completed()->count(),
                    'scheduled' => (clone $todayLessons)->scheduled()->count(),
                    'cancelled' => (clone $todayLessons)->cancelled()->count(),
                    'unscheduled' => (clone $todayLessons)->where('ordles_status', OrderLesson::STATUS_UNSCHEDULED)->count(),
                ],
                'group_classes' => [
                    'total' => (clone $todayGroupClasses)->count(),
                    'scheduled' => (clone $todayGroupClasses)->scheduled()->count(),
                    'completed' => (clone $todayGroupClasses)->completed()->count(),
                ],
                'completion_rate' => $this->calculateCompletionRate(),
            ];
        });
    }

    /**
     * Calculate lesson completion rate
     */
    private function calculateCompletionRate(): float
    {
        $total = OrderLesson::today()->whereIn('ordles_status', [
            OrderLesson::STATUS_SCHEDULED,
            OrderLesson::STATUS_COMPLETED
        ])->count();

        if ($total === 0) return 0;

        $completed = OrderLesson::today()->completed()->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get revenue statistics
     * Includes metrics from the standard KPI list:
     * - Tổng số đơn hàng, Tổng doanh thu, Doanh thu trung bình trên mỗi đơn
     * - Tỷ lệ đơn 0đ, Tỷ lệ đơn có giá trị > 0đ
     * - Số lượng đơn Đã TT / Chờ TT, Tỷ lệ hoàn thành thanh toán
     * - Tổng doanh thu đã thu / đang chờ thu
     * - Giá trị trung bình của đơn Chờ TT
     * - Số khách hàng duy nhất phát sinh đơn
     */
    public function getRevenueStats(): array
    {
        return $this->getCached('dashboard.revenue_stats', function () {
            $paidOrders = Order::paid();
            
            // Basic revenue stats
            $todayRevenue = (clone $paidOrders)->whereDate('order_addedon', today())->sum('order_total_amount');
            $thisWeekRevenue = (clone $paidOrders)->whereBetween('order_addedon', [now()->startOfWeek(), now()->endOfWeek()])->sum('order_total_amount');
            $thisMonthRevenue = (clone $paidOrders)->whereMonth('order_addedon', now()->month)->whereYear('order_addedon', now()->year)->sum('order_total_amount');
            
            // Total orders count (all orders, not just paid)
            $totalOrders = Order::count();
            $paidOrdersCount = Order::paid()->count();
            $unpaidOrdersCount = Order::where('order_payment_status', Order::PAYMENT_UNPAID)->count();
            
            // Zero-value orders (đơn 0đ)
            $zeroValueOrders = Order::where('order_total_amount', 0)->count();
            $nonZeroValueOrders = Order::where('order_total_amount', '>', 0)->count();
            $zeroValueRate = $totalOrders > 0 ? round(($zeroValueOrders / $totalOrders) * 100, 1) : 0;
            $nonZeroValueRate = $totalOrders > 0 ? round(($nonZeroValueOrders / $totalOrders) * 100, 1) : 0;
            
            // Average order value (doanh thu trung bình trên mỗi đơn)
            $totalPaidRevenue = Order::paid()->sum('order_total_amount');
            $averageOrderValue = $paidOrdersCount > 0 ? round($totalPaidRevenue / $paidOrdersCount, 0) : 0;
            
            // Payment completion rate (tỷ lệ hoàn thành thanh toán)
            $paymentCompletionRate = $totalOrders > 0 ? round(($paidOrdersCount / $totalOrders) * 100, 1) : 0;
            
            // Pending revenue (doanh thu đang chờ thu)
            $pendingRevenue = Order::where('order_payment_status', Order::PAYMENT_UNPAID)->sum('order_total_amount');
            
            // Average pending order value (giá trị trung bình của đơn Chờ TT)
            $averagePendingOrderValue = $unpaidOrdersCount > 0 ? round($pendingRevenue / $unpaidOrdersCount, 0) : 0;
            
            // Unique customers (số khách hàng duy nhất phát sinh đơn)
            $uniqueCustomers = Order::distinct('order_user_id')->count('order_user_id');
            $avgOrdersPerCustomer = $uniqueCustomers > 0 ? round($totalOrders / $uniqueCustomers, 2) : 0;

            return [
                // Existing metrics
                'today' => $todayRevenue,
                'this_week' => $thisWeekRevenue,
                'this_month' => $thisMonthRevenue,
                'total_discount' => Order::sum('order_discount_value'),
                'total_rewards_used' => Order::sum('order_reward_value'),
                
                // New metrics - Order overview
                'total_orders' => $totalOrders,
                'paid_orders_count' => $paidOrdersCount,
                'unpaid_orders_count' => $unpaidOrdersCount,
                
                // New metrics - Zero-value orders
                'zero_value_orders' => $zeroValueOrders,
                'non_zero_value_orders' => $nonZeroValueOrders,
                'zero_value_rate' => $zeroValueRate,
                'non_zero_value_rate' => $nonZeroValueRate,
                
                // New metrics - Revenue analysis
                'total_paid_revenue' => $totalPaidRevenue,
                'average_order_value' => $averageOrderValue,
                'payment_completion_rate' => $paymentCompletionRate,
                'pending_revenue' => $pendingRevenue,
                'average_pending_order_value' => $averagePendingOrderValue,
                
                // New metrics - Customer analysis
                'unique_customers' => $uniqueCustomers,
                'avg_orders_per_customer' => $avgOrdersPerCustomer,
            ];
        });
    }

    /**
     * Get order statistics by type
     */
    public function getOrderStatsByType(): array
    {
        return $this->getCached('dashboard.orders_by_type', function () {
            $stats = [];
            $typeLabels = Order::getTypeLabels();

            foreach ($typeLabels as $type => $label) {
                $stats[$label] = Order::where('order_type', $type)->count();
            }

            return $stats;
        });
    }

    /**
     * Get order statistics by status
     */
    public function getOrderStatsByStatus(): array
    {
        return $this->getCached('dashboard.orders_by_status', function () {
            return [
                'in_process' => Order::where('order_status', Order::STATUS_INPROCESS)->count(),
                'completed' => Order::where('order_status', Order::STATUS_COMPLETED)->count(),
                'cancelled' => Order::where('order_status', Order::STATUS_CANCELLED)->count(),
                'unpaid' => Order::where('order_payment_status', Order::PAYMENT_UNPAID)->count(),
                'paid' => Order::where('order_payment_status', Order::PAYMENT_PAID)->count(),
            ];
        });
    }

    /**
     * Get issue statistics
     */
    public function getIssueStats(): array
    {
        return $this->getCached('dashboard.issues', function () {
            return [
                'in_progress' => ReportedIssue::inProgress()->count(),
                'resolved' => ReportedIssue::resolved()->count(),
                'escalated' => ReportedIssue::where('repiss_status', ReportedIssue::STATUS_ESCALATED)->count(),
                'closed' => ReportedIssue::where('repiss_status', ReportedIssue::STATUS_CLOSED)->count(),
                'new_today' => ReportedIssue::today()->count(),
            ];
        });
    }

    /**
     * Get rating review statistics
     */
    public function getRatingStats(): array
    {
        return $this->getCached('dashboard.ratings', function () {
            return [
                'average_rating' => round(RatingReview::approved()->avg('ratrev_overall') ?? 0, 2),
                'pending_reviews' => RatingReview::pending()->count(),
                'approved_reviews' => RatingReview::approved()->count(),
                'declined_reviews' => RatingReview::where('ratrev_status', RatingReview::STATUS_DECLINED)->count(),
            ];
        });
    }

    /**
     * Get revenue chart data (last 30 days)
     */
    public function getRevenueChartData(int $days = 30): array
    {
        return $this->getCached("dashboard.revenue_chart_{$days}", function () use ($days) {
            $data = Order::paid()
                ->where('order_addedon', '>=', now()->subDays($days))
                ->select(
                    DB::raw('DATE(order_addedon) as date'),
                    DB::raw('SUM(order_total_amount) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $labels = [];
            $values = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $labels[] = now()->subDays($i)->format('d/m');
                $values[] = $data->firstWhere('date', $date)?->total ?? 0;
            }

            return [
                'labels' => $labels,
                'values' => $values,
            ];
        });
    }

    /**
     * Get user registration chart data (last 30 days)
     */
    public function getUserRegistrationChartData(int $days = 30): array
    {
        return $this->getCached("dashboard.user_chart_{$days}", function () use ($days) {
            $data = User::active()
                ->where('user_created', '>=', now()->subDays($days))
                ->select(
                    DB::raw('DATE(user_created) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $labels = [];
            $values = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $labels[] = now()->subDays($i)->format('d/m');
                $values[] = $data->firstWhere('date', $date)?->count ?? 0;
            }

            return [
                'labels' => $labels,
                'values' => $values,
            ];
        });
    }

    /**
     * Get teacher payment statistics
     */
    public function getTeacherPaymentStats(): array
    {
        return $this->getCached('dashboard.teacher_payment_stats', function () {
            return [
                'total_paid' => OrderLesson::sum('ordles_teacher_paid'),
                'pending_payment' => OrderLesson::completed()
                    ->whereNull('ordles_teacher_paid')
                    ->count(),
                'system_commission' => OrderLesson::sum('ordles_commission_amount'),
                'affiliate_commission' => OrderLesson::sum('ordles_affiliate_commission'),
            ];
        });
    }

    /**
     * Get lesson quality statistics
     */
    public function getLessonQualityStats(): array
    {
        return $this->getCached('dashboard.lesson_quality', function () {
            $total = OrderLesson::count();
            $completed = OrderLesson::completed()->count();
            $cancelled = OrderLesson::cancelled()->count();

            return [
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
                'average_duration' => round(OrderLesson::avg('ordles_duration') ?? 0, 0),
                'trial_lessons' => OrderLesson::trial()->count(),
                'regular_lessons' => OrderLesson::regular()->count(),
            ];
        });
    }

    // ========================================
    // Trial Lessons Statistics
    // ========================================

    /**
     * Get comprehensive Trial Lessons statistics
     */
    public function getTrialLessonStats(): array
    {
        return $this->getCached('dashboard.trial_stats', function () {
            // All time stats
            $totalTrials = OrderLesson::trial()->count();
        $completedTrials = OrderLesson::trial()->completed()->count();
        $cancelledTrials = OrderLesson::trial()->cancelled()->count();
        $scheduledTrials = OrderLesson::trial()->scheduled()->count();
        $unscheduledTrials = OrderLesson::trial()->where('ordles_status', OrderLesson::STATUS_UNSCHEDULED)->count();

        // Time-based stats
        $todayTrials = OrderLesson::trial()->today()->count();
        $todayCompletedTrials = OrderLesson::trial()->today()->completed()->count();
        
        $thisWeekStart = now()->startOfWeek();
        $thisWeekTrials = OrderLesson::trial()
            ->whereBetween('ordles_lesson_starttime', [$thisWeekStart, now()])
            ->count();
        $thisWeekCompletedTrials = OrderLesson::trial()
            ->completed()
            ->whereBetween('ordles_lesson_starttime', [$thisWeekStart, now()])
            ->count();
        
        $thisMonthStart = now()->startOfMonth();
        $thisMonthTrials = OrderLesson::trial()
            ->whereBetween('ordles_lesson_starttime', [$thisMonthStart, now()])
            ->count();
        $thisMonthCompletedTrials = OrderLesson::trial()
            ->completed()
            ->whereBetween('ordles_lesson_starttime', [$thisMonthStart, now()])
            ->count();

        // 30 days stats
        $last30Days = now()->subDays(30);
        $last30DaysTrials = OrderLesson::trial()
            ->where('ordles_lesson_starttime', '>=', $last30Days)
            ->count();
        $last30DaysCompletedTrials = OrderLesson::trial()
            ->completed()
            ->where('ordles_lesson_starttime', '>=', $last30Days)
            ->count();
        $last30DaysCancelledTrials = OrderLesson::trial()
            ->cancelled()
            ->where('ordles_lesson_starttime', '>=', $last30Days)
            ->count();

        // Average duration
        $avgDuration = OrderLesson::trial()->completed()->avg('ordles_duration');

        return [
            'total' => $totalTrials,
            'completed' => $completedTrials,
            'cancelled' => $cancelledTrials,
            'scheduled' => $scheduledTrials,
            'unscheduled' => $unscheduledTrials,
            'completion_rate' => $totalTrials > 0 ? round(($completedTrials / $totalTrials) * 100, 1) : 0,
            'cancellation_rate' => $totalTrials > 0 ? round(($cancelledTrials / $totalTrials) * 100, 1) : 0,
            'avg_duration' => round($avgDuration ?? 0, 0),
            'today' => [
                'total' => $todayTrials,
                'completed' => $todayCompletedTrials,
                'rate' => $todayTrials > 0 ? round(($todayCompletedTrials / $todayTrials) * 100, 1) : 0,
            ],
            'this_week' => [
                'total' => $thisWeekTrials,
                'completed' => $thisWeekCompletedTrials,
                'rate' => $thisWeekTrials > 0 ? round(($thisWeekCompletedTrials / $thisWeekTrials) * 100, 1) : 0,
            ],
            'this_month' => [
                'total' => $thisMonthTrials,
                'completed' => $thisMonthCompletedTrials,
                'rate' => $thisMonthTrials > 0 ? round(($thisMonthCompletedTrials / $thisMonthTrials) * 100, 1) : 0,
            ],
            'last_30_days' => [
                'total' => $last30DaysTrials,
                'completed' => $last30DaysCompletedTrials,
                'cancelled' => $last30DaysCancelledTrials,
                'rate' => $last30DaysTrials > 0 ? round(($last30DaysCompletedTrials / $last30DaysTrials) * 100, 1) : 0,
            ],
        ];
        });
    }

    /**
     * Get Trial Lesson trend chart data (daily for last X days)
     */
    public function getTrialLessonTrendChart(int $days = 14): array
    {
        return $this->getCached("dashboard.trial_trend_chart_{$days}", function () use ($days) {
            $labels = [];
            $scheduled = [];
            $completed = [];
            $cancelled = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                $dayQuery = OrderLesson::trial()
                    ->whereBetween('ordles_lesson_starttime', [$dayStart, $dayEnd]);

                $scheduled[] = (clone $dayQuery)->scheduled()->count();
                $completed[] = (clone $dayQuery)->completed()->count();
                $cancelled[] = (clone $dayQuery)->cancelled()->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'scheduled' => $scheduled,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                ],
            ];
        });
    }

    /**
     * Get Trial to Paid conversion statistics (detailed)
     */
    public function getTrialConversionStats(): array
    {
        return $this->getCached('dashboard.trial_conversion', function () {
            // Total trial users who completed at least one trial
            $trialUserIds = OrderLesson::trial()
            ->completed()
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->pluck('tbl_orders.order_user_id')
            ->unique();
        
        $totalTrialUsers = $trialUserIds->count();

        // Users who purchased (any paid order after trial)
        $convertedUsers = Order::whereIn('order_user_id', $trialUserIds)
            ->where('order_type', '!=', Order::TYPE_LESSON) // Exclude trial lesson type
            ->paid()
            ->distinct('order_user_id')
            ->count('order_user_id');

        // Users who purchased regular lessons specifically
        $convertedToRegular = OrderLesson::regular()
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->whereIn('tbl_orders.order_user_id', $trialUserIds)
            ->where('tbl_orders.order_payment_status', Order::PAYMENT_PAID)
            ->distinct('tbl_orders.order_user_id')
            ->count('tbl_orders.order_user_id');

        // 30 days conversion stats
        $last30Days = now()->subDays(30);
        $trial30DaysUserIds = OrderLesson::trial()
            ->completed()
            ->where('ordles_lesson_starttime', '>=', $last30Days)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->pluck('tbl_orders.order_user_id')
            ->unique();
        
        $total30DaysTrialUsers = $trial30DaysUserIds->count();
        $converted30Days = Order::whereIn('order_user_id', $trial30DaysUserIds)
            ->where('order_type', '!=', Order::TYPE_LESSON)
            ->paid()
            ->where('order_addedon', '>=', $last30Days)
            ->distinct('order_user_id')
            ->count('order_user_id');

        return [
            'all_time' => [
                'trial_users' => $totalTrialUsers,
                'converted_users' => $convertedUsers,
                'converted_to_regular' => $convertedToRegular,
                'conversion_rate' => $totalTrialUsers > 0 ? round(($convertedUsers / $totalTrialUsers) * 100, 1) : 0,
                'regular_conversion_rate' => $totalTrialUsers > 0 ? round(($convertedToRegular / $totalTrialUsers) * 100, 1) : 0,
            ],
            'last_30_days' => [
                'trial_users' => $total30DaysTrialUsers,
                'converted_users' => $converted30Days,
                'conversion_rate' => $total30DaysTrialUsers > 0 ? round(($converted30Days / $total30DaysTrialUsers) * 100, 1) : 0,
            ],
        ];
        });
    }

    /**
     * Get Recent Trial Lessons list
     */
    public function getRecentTrialLessons(int $limit = 10): array
    {
        return $this->getCached("dashboard.recent_trial_lessons_{$limit}", function () use ($limit) {
            return OrderLesson::trial()
            ->select('tbl_order_lessons.ordles_id', 'tbl_order_lessons.ordles_order_id', 'tbl_order_lessons.ordles_teacher_id', 'tbl_order_lessons.ordles_status', 'tbl_order_lessons.ordles_lesson_starttime', 'tbl_order_lessons.ordles_duration', 'tbl_orders.order_user_id')
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->orderByDesc('tbl_order_lessons.ordles_lesson_starttime')
            ->limit($limit)
            ->get()
            ->map(function ($lesson) {
                $teacher = User::find($lesson->ordles_teacher_id);
                $student = User::find($lesson->order_user_id);
                return [
                    'id' => $lesson->ordles_id,
                    'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                    'student_email' => $student?->user_email ?? '',
                    'status' => $lesson->ordles_status,
                    'status_label' => OrderLesson::getStatusLabel($lesson->ordles_status),
                    'start_time' => $lesson->ordles_lesson_starttime ? Carbon::parse($lesson->ordles_lesson_starttime)->format('d/m/Y H:i') : '',
                    'duration' => $lesson->ordles_duration,
                ];
            })
            ->toArray();
        });
    }

    /**
     * Get Trial Lessons by Status (for pie chart)
     */
    public function getTrialLessonsByStatus(): array
    {
        return $this->getCached('dashboard.trial_by_status', function () {
            return [
                'unscheduled' => OrderLesson::trial()->where('ordles_status', OrderLesson::STATUS_UNSCHEDULED)->count(),
                'scheduled' => OrderLesson::trial()->scheduled()->count(),
                'completed' => OrderLesson::trial()->completed()->count(),
                'cancelled' => OrderLesson::trial()->cancelled()->count(),
            ];
        });
    }

    /**
     * Get Teachers with most Trial Lessons
     */
    public function getTopTeachersByTrialLessons(int $limit = 10): array
    {
        return $this->getCached("dashboard.top_teachers_by_trial_{$limit}", function () use ($limit) {
            return OrderLesson::trial()
            ->select('ordles_teacher_id', DB::raw('COUNT(*) as total_trials'), DB::raw('SUM(CASE WHEN ordles_status = 3 THEN 1 ELSE 0 END) as completed_trials'))
            ->groupBy('ordles_teacher_id')
            ->orderByDesc('total_trials')
            ->limit($limit)
            ->get()
            ->map(function ($stat) {
                $teacher = User::find($stat->ordles_teacher_id);
                return [
                    'id' => $stat->ordles_teacher_id,
                    'name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'email' => $teacher?->user_email ?? '',
                    'total_trials' => $stat->total_trials,
                    'completed_trials' => $stat->completed_trials,
                    'completion_rate' => $stat->total_trials > 0 ? round(($stat->completed_trials / $stat->total_trials) * 100, 1) : 0,
                ];
            })
            ->toArray();
        });
    }

    // ========================================
    // Learning Path & Teacher Feedback Stats
    // ========================================

    /**
     * Get learning path (curriculum) statistics
     */
    public function getLearningPathStats(): array
    {
        return $this->getCached('dashboard.learning_path_stats', function () {
            $totalSessions = CurriculumSession::count();
            $completedSessions = CurriculumSession::completed()->count();
            $upcomingSessions = CurriculumSession::upcoming()->count();
            $incompleteSessions = CurriculumSession::incomplete()->count();

            // Users with assigned learning path
            $usersWithProgram = ProgramUser::distinct('user_id')->count('user_id');
            
            // Total programs
            $totalPrograms = Program::published()->count();
            $totalCurriculums = Curriculum::count();

            return [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'upcoming_sessions' => $upcomingSessions,
                'incomplete_sessions' => $incompleteSessions,
                'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 2) : 0,
                'users_with_program' => $usersWithProgram,
                'total_programs' => $totalPrograms,
                'total_curriculums' => $totalCurriculums,
            ];
        });
    }

    /**
     * Get curriculum session distribution by status
     */
    public function getCurriculumSessionDistribution(): array
    {
        return $this->getCached('dashboard.curriculum_session_distribution', function () {
            return [
                'completed' => CurriculumSession::completed()->count(),
                'upcoming' => CurriculumSession::upcoming()->count(),
                'incomplete' => CurriculumSession::incomplete()->count(),
            ];
        });
    }

    /**
     * Get teacher feedback statistics
     * Note: Trial/Regular feedback is determined by lesson type (ordles_type) in tbl_order_lessons,
     * NOT by teafeed_type in tbl_teacher_feedbacks
     */
    public function getTeacherFeedbackStats(): array
    {
        return $this->getCached('dashboard.feedback_stats', function () {
            $totalFeedback = TeacherFeedback::count();
            $pendingFeedback = TeacherFeedback::pending()->count();
            $approvedFeedback = TeacherFeedback::approved()->count();
            // Use trialByLesson() - joins with tbl_order_lessons.ordles_type = 1
            $trialFeedback = TeacherFeedback::trialByLesson()->count();
            // Use regularByLesson() - joins with tbl_order_lessons.ordles_type = 2
            $regularFeedback = TeacherFeedback::regularByLesson()->count();
            $todayFeedback = TeacherFeedback::today()->count();

            return [
                'total' => $totalFeedback,
                'pending' => $pendingFeedback,
                'approved' => $approvedFeedback,
                'trial' => $trialFeedback,
                'regular' => $regularFeedback,
                'today' => $todayFeedback,
                'approval_rate' => $totalFeedback > 0 ? round(($approvedFeedback / $totalFeedback) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get teachers with pending/overdue feedback
     * Feedback should be submitted within 12 hours of lesson end (T+1 12:00)
     */
    public function getTeachersFeedbackStatus(): array
    {
        return $this->getCached('dashboard.feedback_status', function () {
            // Get completed lessons from yesterday that might need feedback
            $yesterday = now()->subDay();
            $feedbackDeadline = now()->subHours(12);

            // Get completed lessons without feedback (potential overdue)
            $completedLessonsWithoutFeedback = OrderLesson::completed()
                ->whereDate('ordles_lesson_starttime', '<=', $yesterday)
                ->whereNotIn('ordles_id', function($query) {
                    $query->select('teafeed_record_id')
                        ->from('tbl_teacher_feedbacks')
                        ->where('teafeed_record_type', TeacherFeedback::RECORD_TYPE_ONE_ON_ONE);
                })
                ->count();

            // Get feedback submitted today
            $feedbackSubmittedToday = TeacherFeedback::whereDate('teafeed_created_at', today())->count();

            // Get lessons today that will need feedback
            $lessonsNeedingFeedback = OrderLesson::completed()
                ->whereDate('ordles_lesson_starttime', today())
                ->count();

            return [
                'pending_feedback' => $completedLessonsWithoutFeedback,
                'submitted_today' => $feedbackSubmittedToday,
                'lessons_needing_feedback' => $lessonsNeedingFeedback,
                'on_time_rate' => $lessonsNeedingFeedback > 0 
                    ? round(($feedbackSubmittedToday / max($lessonsNeedingFeedback, 1)) * 100, 2) 
                    : 100,
            ];
        });
    }

    /**
     * Get feedback submission trend (last 14 days)
     */
    public function getFeedbackSubmissionTrend(int $days = 14): array
    {
        return $this->getCached("dashboard.feedback_trend_{$days}", function () use ($days) {
            $labels = [];
            $submitted = [];
            $pending = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                // Feedback submitted on this day
                $submitted[] = TeacherFeedback::whereBetween('teafeed_created_at', [$dayStart, $dayEnd])->count();

                // Lessons completed on this day (should have feedback)
                $lessonsOnDay = OrderLesson::completed()
                    ->whereBetween('ordles_lesson_starttime', [$dayStart, $dayEnd])
                    ->count();
                
                // Lessons that got feedback for that day
                $feedbackForDay = TeacherFeedback::whereIn('teafeed_record_id', function($query) use ($dayStart, $dayEnd) {
                    $query->select('ordles_id')
                        ->from('tbl_order_lessons')
                        ->whereBetween('ordles_lesson_starttime', [$dayStart, $dayEnd]);
                })->count();

                $pending[] = max(0, $lessonsOnDay - $feedbackForDay);
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'submitted' => $submitted,
                    'pending' => $pending,
                ],
            ];
        });
    }

    /**
     * Get top teachers by feedback count
     */
    public function getTopTeachersByFeedback(int $limit = 10): array
    {
        return $this->getCached("dashboard.top_teachers_by_feedback_{$limit}", function () use ($limit) {
            return TeacherFeedback::select('teafeed_teacher_id', DB::raw('COUNT(*) as feedback_count'))
                ->groupBy('teafeed_teacher_id')
                ->orderByDesc('feedback_count')
                ->limit($limit)
                ->get()
                ->map(function ($feedback) {
                    $user = User::find($feedback->teafeed_teacher_id);
                    return [
                        'id' => $feedback->teafeed_teacher_id,
                        'name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                        'email' => $user?->user_email ?? '',
                        'feedback_count' => $feedback->feedback_count,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get recent feedback
     */
    public function getRecentFeedback(int $limit = 10): array
    {
        return $this->getCached("dashboard.recent_feedback_{$limit}", function () use ($limit) {
            return TeacherFeedback::select('teafeed_id', 'teafeed_teacher_id', 'teafeed_learner_id', 'teafeed_type', 'teafeed_status', 'teafeed_created_at')
                ->orderByDesc('teafeed_created_at')
                ->limit($limit)
                ->get()
                ->map(function ($feedback) {
                    $teacher = User::find($feedback->teafeed_teacher_id);
                    $learner = User::find($feedback->teafeed_learner_id);
                    return [
                        'id' => $feedback->teafeed_id,
                        'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                        'learner_name' => $learner ? ($learner->user_first_name . ' ' . $learner->user_last_name) : 'Unknown',
                        'type' => TeacherFeedback::getTypeLabel($feedback->teafeed_type),
                        'status' => TeacherFeedback::getStatusLabel($feedback->teafeed_status),
                        'created_at' => Carbon::parse($feedback->teafeed_created_at)->format('d/m/Y H:i'),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get program enrollment statistics
     */
    public function getProgramEnrollmentStats(): array
    {
        return $this->getCached('dashboard.program_enrollment_stats', function () {
            $programs = Program::published()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->limit(10)
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'title' => $program->title,
                        'users_count' => $program->users_count,
                        'type' => $program->type,
                    ];
                })
                ->toArray();

            return [
                'programs' => $programs,
                'total_enrollments' => ProgramUser::count(),
                'unique_learners' => ProgramUser::distinct('user_id')->count('user_id'),
            ];
        });
    }

    /**
     * Get curriculum session chart data
     */
    public function getCurriculumSessionChartData(int $days = 30): array
    {
        return $this->getCached("dashboard.curriculum_session_chart_{$days}", function () use ($days) {
            $labels = [];
            $completed = [];
            $created = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                // Sessions created on this day
                $created[] = CurriculumSession::whereBetween('created_at', [$dayStart, $dayEnd])->count();

                // Sessions completed - we'll track by checking lessons that have completed status
                // Since status is updated when lesson is completed
                $completed[] = CurriculumSession::completed()
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'created' => $created,
                    'completed' => $completed,
                ],
            ];
        });
    }

    // ========================================
    // Feedback Detail & Session Quality Stats
    // ========================================

    /**
     * Get feedback detail by ID (with content)
     */
    public function getFeedbackDetail(int $feedbackId): ?array
    {
        $feedback = TeacherFeedback::find($feedbackId);
        
        if (!$feedback) {
            return null;
        }

        $teacher = User::find($feedback->teafeed_teacher_id);
        $learner = User::find($feedback->teafeed_learner_id);

        // Get lesson info if available
        $lesson = null;
        if ($feedback->teafeed_record_type == TeacherFeedback::RECORD_TYPE_ONE_ON_ONE) {
            $lesson = OrderLesson::find($feedback->teafeed_record_id);
        }

        return [
            'id' => $feedback->teafeed_id,
            'teacher_id' => $feedback->teafeed_teacher_id,
            'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
            'teacher_email' => $teacher?->user_email ?? '',
            'learner_id' => $feedback->teafeed_learner_id,
            'learner_name' => $learner ? ($learner->user_first_name . ' ' . $learner->user_last_name) : 'Unknown',
            'learner_email' => $learner?->user_email ?? '',
            'type' => TeacherFeedback::getTypeLabel($feedback->teafeed_type),
            'type_raw' => $feedback->teafeed_type,
            'status' => TeacherFeedback::getStatusLabel($feedback->teafeed_status),
            'status_raw' => $feedback->teafeed_status,
            'record_type' => $feedback->teafeed_record_type == TeacherFeedback::RECORD_TYPE_ONE_ON_ONE ? '1-on-1' : 'Group',
            'values' => $feedback->teafeed_values ?? [],
            'lesson_info' => $lesson ? [
                'id' => $lesson->ordles_id,
                'start_time' => $lesson->ordles_lesson_starttime ? Carbon::parse($lesson->ordles_lesson_starttime)->format('d/m/Y H:i') : '',
                'duration' => $lesson->ordles_duration,
                'status' => OrderLesson::getStatusLabel($lesson->ordles_status),
            ] : null,
            'created_at' => Carbon::parse($feedback->teafeed_created_at)->format('d/m/Y H:i'),
            'notify_status' => $feedback->teafeed_notify_status,
            'ignore_feedback' => $feedback->teafeed_ignore_feedback,
        ];
    }

    /**
     * Get recent feedback with content preview
     */
    public function getRecentFeedbackWithContent(int $limit = 10): array
    {
        return TeacherFeedback::select('teafeed_id', 'teafeed_teacher_id', 'teafeed_learner_id', 'teafeed_type', 'teafeed_status', 'teafeed_values', 'teafeed_record_id', 'teafeed_record_type', 'teafeed_created_at')
            ->orderByDesc('teafeed_created_at')
            ->limit($limit)
            ->get()
            ->map(function ($feedback) {
                $teacher = User::find($feedback->teafeed_teacher_id);
                $learner = User::find($feedback->teafeed_learner_id);
                
                // Extract key feedback info from teafeed_values
                $values = $feedback->teafeed_values ?? [];
                $contentPreview = $this->extractFeedbackPreview($values);
                
                return [
                    'id' => $feedback->teafeed_id,
                    'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'learner_name' => $learner ? ($learner->user_first_name . ' ' . $learner->user_last_name) : 'Unknown',
                    'type' => TeacherFeedback::getTypeLabel($feedback->teafeed_type),
                    'status' => TeacherFeedback::getStatusLabel($feedback->teafeed_status),
                    'status_raw' => $feedback->teafeed_status,
                    'content_preview' => $contentPreview,
                    'has_content' => !empty($values),
                    'created_at' => Carbon::parse($feedback->teafeed_created_at)->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Extract a preview from feedback values
     */
    private function extractFeedbackPreview(array $values): string
    {
        if (empty($values)) {
            return 'Không có nội dung';
        }

        // Common fields to extract
        $previewFields = ['overall_comment', 'comment', 'note', 'homework', 'lesson_content', 'summary'];
        
        foreach ($previewFields as $field) {
            if (isset($values[$field]) && is_string($values[$field]) && !empty(trim($values[$field]))) {
                $text = strip_tags($values[$field]);
                return strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
            }
        }

        // If no text field found, count the number of ratings/fields
        $fieldCount = count($values);
        return "Đã đánh giá {$fieldCount} tiêu chí";
    }

    /**
     * Get session outcome statistics (success, failure, no-show)
     */
    public function getSessionOutcomeStats(): array
    {
        return $this->getCached('dashboard.session_outcome', function () {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            // Today stats
            $todayLessons = OrderLesson::whereDate('ordles_lesson_starttime', today());
            $todayTotal = (clone $todayLessons)->count();
            $todayCompleted = (clone $todayLessons)->completed()->count();
            $todayCancelled = (clone $todayLessons)->cancelled()->count();
            $todayScheduled = (clone $todayLessons)->scheduled()->count();
            
            // Calculate no-show: scheduled lessons that should have completed but didn't
            // (start time + duration has passed, but still scheduled)
            $todayNoShow = (clone $todayLessons)
                ->scheduled()
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->count();

            // This week stats
            $weekLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$thisWeek, now()]);
            $weekTotal = (clone $weekLessons)->count();
            $weekCompleted = (clone $weekLessons)->completed()->count();
            $weekCancelled = (clone $weekLessons)->cancelled()->count();

            // This month stats
            $monthLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$thisMonth, now()]);
            $monthTotal = (clone $monthLessons)->count();
            $monthCompleted = (clone $monthLessons)->completed()->count();
            $monthCancelled = (clone $monthLessons)->cancelled()->count();

            // All time stats
            $allTotal = OrderLesson::count();
            $allCompleted = OrderLesson::completed()->count();
            $allCancelled = OrderLesson::cancelled()->count();

            return [
                'today' => [
                    'total' => $todayTotal,
                    'completed' => $todayCompleted,
                    'cancelled' => $todayCancelled,
                    'scheduled' => $todayScheduled,
                    'no_show' => $todayNoShow,
                    'success_rate' => $todayTotal > 0 ? round(($todayCompleted / $todayTotal) * 100, 1) : 0,
                    'failure_rate' => $todayTotal > 0 ? round((($todayCancelled + $todayNoShow) / $todayTotal) * 100, 1) : 0,
                ],
                'this_week' => [
                    'total' => $weekTotal,
                    'completed' => $weekCompleted,
                    'cancelled' => $weekCancelled,
                    'success_rate' => $weekTotal > 0 ? round(($weekCompleted / $weekTotal) * 100, 1) : 0,
                ],
                'this_month' => [
                    'total' => $monthTotal,
                    'completed' => $monthCompleted,
                    'cancelled' => $monthCancelled,
                    'success_rate' => $monthTotal > 0 ? round(($monthCompleted / $monthTotal) * 100, 1) : 0,
                ],
                'all_time' => [
                    'total' => $allTotal,
                    'completed' => $allCompleted,
                    'cancelled' => $allCancelled,
                    'success_rate' => $allTotal > 0 ? round(($allCompleted / $allTotal) * 100, 1) : 0,
                ],
            ];
        });
    }

    /**
     * Get lessons that haven't started on time (teacher or student late)
     */
    public function getLateStartLessons(int $limit = 10): array
    {
        return $this->getCached("dashboard.late_start_lessons_{$limit}", function () use ($limit) {
            // Get completed lessons where teacher or student joined late (> 5 min after start time)
            return OrderLesson::completed()
                ->whereDate('ordles_lesson_starttime', '>=', now()->subDays(7))
                ->where(function($query) {
                    $query->whereRaw('TIMESTAMPDIFF(MINUTE, ordles_lesson_starttime, ordles_teacher_starttime) > 5')
                        ->orWhereRaw('TIMESTAMPDIFF(MINUTE, ordles_lesson_starttime, ordles_student_starttime) > 5');
            })
            ->orderByDesc('ordles_lesson_starttime')
            ->limit($limit)
            ->get()
            ->map(function($lesson) {
                $teacher = User::find($lesson->ordles_teacher_id);
                $teacherLate = 0;
                $studentLate = 0;
                
                if ($lesson->ordles_teacher_starttime && $lesson->ordles_lesson_starttime) {
                    $teacherLate = Carbon::parse($lesson->ordles_lesson_starttime)
                        ->diffInMinutes(Carbon::parse($lesson->ordles_teacher_starttime), false);
                }
                if ($lesson->ordles_student_starttime && $lesson->ordles_lesson_starttime) {
                    $studentLate = Carbon::parse($lesson->ordles_lesson_starttime)
                        ->diffInMinutes(Carbon::parse($lesson->ordles_student_starttime), false);
                }

                return [
                    'id' => $lesson->ordles_id,
                    'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'start_time' => $lesson->ordles_lesson_starttime ? Carbon::parse($lesson->ordles_lesson_starttime)->format('d/m/Y H:i') : '',
                    'teacher_late_minutes' => max(0, $teacherLate),
                    'student_late_minutes' => max(0, $studentLate),
                ];
            })
            ->toArray();
        });
    }

    /**
     * Get lessons with attendance issues (teacher or student didn't join)
     */
    public function getAttendanceIssues(int $limit = 10): array
    {
        return $this->getCached("dashboard.attendance_issues_{$limit}", function () use ($limit) {
            // Get scheduled lessons that should have happened but have no join times
            return OrderLesson::scheduled()
                ->whereDate('ordles_lesson_starttime', '>=', now()->subDays(3))
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->orderByDesc('ordles_lesson_starttime')
                ->limit($limit)
                ->get()
                ->map(function($lesson) {
                    $teacher = User::find($lesson->ordles_teacher_id);
                    
                    $teacherJoined = !is_null($lesson->ordles_teacher_starttime);
                    $studentJoined = !is_null($lesson->ordles_student_starttime);
                    
                    $issue = 'unknown';
                    if (!$teacherJoined && !$studentJoined) {
                        $issue = 'Cả hai không tham gia';
                    } elseif (!$teacherJoined) {
                        $issue = 'GV không tham gia';
                    } elseif (!$studentJoined) {
                        $issue = 'HV không tham gia';
                    }

                    return [
                        'id' => $lesson->ordles_id,
                        'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                        'start_time' => $lesson->ordles_lesson_starttime ? Carbon::parse($lesson->ordles_lesson_starttime)->format('d/m/Y H:i') : '',
                        'duration' => $lesson->ordles_duration,
                        'teacher_joined' => $teacherJoined,
                        'student_joined' => $studentJoined,
                        'issue' => $issue,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get session quality summary for quality dashboard
     */
    public function getSessionQualitySummary(): array
    {
        return $this->getCached('dashboard.session_quality', function () {
            // Last 30 days
            $startDate = now()->subDays(30);
            
            $totalLessons = OrderLesson::where('ordles_lesson_starttime', '>=', $startDate)->count();
            $completedLessons = OrderLesson::completed()->where('ordles_lesson_starttime', '>=', $startDate)->count();
            $cancelledLessons = OrderLesson::cancelled()->where('ordles_lesson_starttime', '>=', $startDate)->count();
            
            // Lessons with issues (still scheduled but past end time)
            $missedLessons = OrderLesson::scheduled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->count();

            // Teacher attendance rate
            $lessonsWithTeacherJoin = OrderLesson::completed()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereNotNull('ordles_teacher_starttime')
                ->count();

            // Student attendance rate
            $lessonsWithStudentJoin = OrderLesson::completed()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereNotNull('ordles_student_starttime')
                ->count();

            // Trial vs Regular completion
            $trialTotal = OrderLesson::trial()->where('ordles_lesson_starttime', '>=', $startDate)->count();
            $trialCompleted = OrderLesson::trial()->completed()->where('ordles_lesson_starttime', '>=', $startDate)->count();
            $regularTotal = OrderLesson::regular()->where('ordles_lesson_starttime', '>=', $startDate)->count();
            $regularCompleted = OrderLesson::regular()->completed()->where('ordles_lesson_starttime', '>=', $startDate)->count();

            return [
                'period' => '30 ngày gần nhất',
                'total_lessons' => $totalLessons,
                'completed' => $completedLessons,
                'cancelled' => $cancelledLessons,
                'missed' => $missedLessons,
                'completion_rate' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0,
                'cancellation_rate' => $totalLessons > 0 ? round(($cancelledLessons / $totalLessons) * 100, 1) : 0,
                'missed_rate' => $totalLessons > 0 ? round(($missedLessons / $totalLessons) * 100, 1) : 0,
                'teacher_attendance_rate' => $completedLessons > 0 ? round(($lessonsWithTeacherJoin / $completedLessons) * 100, 1) : 0,
                'student_attendance_rate' => $completedLessons > 0 ? round(($lessonsWithStudentJoin / $completedLessons) * 100, 1) : 0,
                'trial' => [
                    'total' => $trialTotal,
                    'completed' => $trialCompleted,
                    'rate' => $trialTotal > 0 ? round(($trialCompleted / $trialTotal) * 100, 1) : 0,
                ],
                'regular' => [
                    'total' => $regularTotal,
                    'completed' => $regularCompleted,
                    'rate' => $regularTotal > 0 ? round(($regularCompleted / $regularTotal) * 100, 1) : 0,
                ],
            ];
        });
    }

    // ========================================
    // Phase 4: Teacher Leave Management & Quiz Stats
    // ========================================

    /**
     * Get teacher leave request statistics
     */
    public function getLeaveRequestStats(): array
    {
        return $this->getCached('dashboard.leave_stats', function () {
            $totalLeaveRequests = TeacherLeaveRequest::count();
            $pendingRequests = TeacherLeaveRequest::pending()->count();
            $autoApproved = TeacherLeaveRequest::where('tlr_status', TeacherLeaveRequest::STATUS_AUTO_APPROVED)->count();
            $approved = TeacherLeaveRequest::where('tlr_status', TeacherLeaveRequest::STATUS_APPROVED)->count();
            $rejected = TeacherLeaveRequest::rejected()->count();
            $canceled = TeacherLeaveRequest::canceled()->count();
            $shortTerm = TeacherLeaveRequest::shortTerm()->count();
            $longTerm = TeacherLeaveRequest::longTerm()->count();
            $thisMonth = TeacherLeaveRequest::thisMonth()->count();
            $today = TeacherLeaveRequest::today()->count();

            // Total approved days
            $totalApprovedDays = TeacherLeaveRequest::approved()->sum('tlr_total_days');

            return [
                'total' => $totalLeaveRequests,
                'pending' => $pendingRequests,
                'auto_approved' => $autoApproved,
                'approved' => $approved,
                'total_approved' => $autoApproved + $approved,
                'rejected' => $rejected,
                'canceled' => $canceled,
                'short_term' => $shortTerm,
                'long_term' => $longTerm,
                'this_month' => $thisMonth,
                'today' => $today,
                'total_approved_days' => $totalApprovedDays,
                'approval_rate' => $totalLeaveRequests > 0 ? round((($autoApproved + $approved) / $totalLeaveRequests) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get leave requests by status for chart
     */
    public function getLeaveRequestsByStatus(): array
    {
        return $this->getCached('dashboard.leave_by_status', function () {
            return [
                'pending' => TeacherLeaveRequest::pending()->count(),
                'auto_approved' => TeacherLeaveRequest::where('tlr_status', TeacherLeaveRequest::STATUS_AUTO_APPROVED)->count(),
                'approved' => TeacherLeaveRequest::where('tlr_status', TeacherLeaveRequest::STATUS_APPROVED)->count(),
                'rejected' => TeacherLeaveRequest::rejected()->count(),
                'canceled' => TeacherLeaveRequest::canceled()->count(),
            ];
        });
    }

    /**
     * Get recent leave requests
     */
    public function getRecentLeaveRequests(int $limit = 10): array
    {
        return $this->getCached("dashboard.recent_leave_requests_{$limit}", function () use ($limit) {
            return TeacherLeaveRequest::select('tlr_id', 'tlr_teacher_id', 'tlr_start_date', 'tlr_end_date', 'tlr_leave_type', 'tlr_status', 'tlr_total_days', 'tlr_reason_type', 'tlr_created_at')
                ->orderByDesc('tlr_created_at')
                ->limit($limit)
                ->get()
                ->map(function ($request) {
                    $teacher = User::find($request->tlr_teacher_id);
                    return [
                        'id' => $request->tlr_id,
                        'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                        'start_date' => $request->tlr_start_date ? Carbon::parse($request->tlr_start_date)->format('d/m/Y') : '',
                        'end_date' => $request->tlr_end_date ? Carbon::parse($request->tlr_end_date)->format('d/m/Y') : '',
                        'total_days' => $request->tlr_total_days,
                        'leave_type' => TeacherLeaveRequest::getLeaveTypeLabel($request->tlr_leave_type),
                        'reason_type' => TeacherLeaveRequest::getReasonTypeLabel($request->tlr_reason_type),
                        'status' => TeacherLeaveRequest::getStatusLabel($request->tlr_status),
                        'status_raw' => $request->tlr_status,
                        'submitted_at' => $request->tlr_created_at ? Carbon::parse($request->tlr_created_at)->format('d/m/Y H:i') : '',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get leave violation statistics
     */
    public function getLeaveViolationStats(): array
    {
        return $this->getCached('dashboard.leave_violation_stats', function () {
            $totalViolations = TeacherLeaveViolation::count();
            $noShowViolations = TeacherLeaveViolation::noShow()->count();
            $lateSubmissionViolations = TeacherLeaveViolation::lateSubmission()->count();
            $exceededQuotaViolations = TeacherLeaveViolation::exceededQuota()->count();
            $thisMonthViolations = TeacherLeaveViolation::thisMonth()->count();

            return [
                'total' => $totalViolations,
                'no_show' => $noShowViolations,
                'late_submission' => $lateSubmissionViolations,
                'exceeded_quota' => $exceededQuotaViolations,
                'this_month' => $thisMonthViolations,
            ];
        });
    }

    /**
     * Get teachers with most leave days
     */
    public function getTeachersWithMostLeave(int $limit = 10): array
    {
        return $this->getCached("dashboard.teachers_most_leave_{$limit}", function () use ($limit) {
            return TeacherLeaveRequest::select('tlr_teacher_id', DB::raw('SUM(tlr_total_days) as total_days'), DB::raw('COUNT(*) as request_count'))
                ->approved()
                ->groupBy('tlr_teacher_id')
                ->orderByDesc('total_days')
                ->limit($limit)
                ->get()
                ->map(function ($leave) {
                    $teacher = User::find($leave->tlr_teacher_id);
                    return [
                        'id' => $leave->tlr_teacher_id,
                        'name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                        'email' => $teacher?->user_email ?? '',
                        'total_days' => $leave->total_days,
                        'request_count' => $leave->request_count,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get leave request trend (last 14 days)
     */
    public function getLeaveRequestTrend(int $days = 14): array
    {
        return $this->getCached("dashboard.leave_trend_{$days}", function () use ($days) {
            $labels = [];
            $submitted = [];
            $approved = [];
            $rejected = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                // Requests submitted on this day
                $submitted[] = TeacherLeaveRequest::whereBetween('tlr_created_at', [$dayStart, $dayEnd])->count();

                // Requests approved on this day
                $approved[] = TeacherLeaveRequest::approved()
                    ->whereBetween('tlr_approved_at', [$dayStart, $dayEnd])
                    ->count();

                // Requests rejected on this day
                $rejected[] = TeacherLeaveRequest::rejected()
                    ->whereBetween('tlr_updated_at', [$dayStart, $dayEnd])
                    ->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'submitted' => $submitted,
                    'approved' => $approved,
                    'rejected' => $rejected,
                ],
            ];
        });
    }

    /**
     * Get quiz statistics
     */
    public function getQuizStats(): array
    {
        return $this->getCached('dashboard.quiz_stats', function () {
            $totalQuizzes = Quiz::whereNull('quiz_deleted')->count();
            $activeQuizzes = Quiz::active()->count();
            $draftQuizzes = Quiz::draft()->count();
            $upcomingQuizzes = Quiz::upcoming()->count();
            $thisMonthQuizzes = Quiz::thisMonth()->count();

            // Average stats
            $avgDuration = Quiz::whereNull('quiz_deleted')->avg('quiz_duration');
            $avgQuestions = Quiz::whereNull('quiz_deleted')->avg('quiz_questions');
            $avgTotalMarks = Quiz::whereNull('quiz_deleted')->avg('quiz_marks');

            return [
                'total' => $totalQuizzes,
                'active' => $activeQuizzes,
                'draft' => $draftQuizzes,
                'upcoming' => $upcomingQuizzes,
                'this_month' => $thisMonthQuizzes,
                'avg_duration' => round($avgDuration ?? 0, 0),
                'avg_questions' => round($avgQuestions ?? 0, 0),
                'avg_total_marks' => round($avgTotalMarks ?? 0, 0),
            ];
        });
    }

    /**
     * Get quiz attempt statistics
     */
    public function getQuizAttemptStats(): array
    {
        return $this->getCached('dashboard.quiz_attempt_stats', function () {
            $totalAttempts = QuizAttempt::count();
            $completedAttempts = QuizAttempt::completed()->count();
            $inProgressAttempts = QuizAttempt::inProgress()->count();
            $passedAttempts = QuizAttempt::passed()->count();
            $failedAttempts = QuizAttempt::failed()->count();
            $todayAttempts = QuizAttempt::today()->count();
            $thisMonthAttempts = QuizAttempt::thisMonth()->count();

            // Average score
            $avgScore = QuizAttempt::completed()->avg('quizat_scored');

            return [
                'total' => $totalAttempts,
                'completed' => $completedAttempts,
                'in_progress' => $inProgressAttempts,
                'passed' => $passedAttempts,
                'failed' => $failedAttempts,
                'today' => $todayAttempts,
                'this_month' => $thisMonthAttempts,
                'avg_score' => round($avgScore ?? 0, 1),
                'pass_rate' => $completedAttempts > 0 ? round(($passedAttempts / $completedAttempts) * 100, 1) : 0,
                'fail_rate' => $completedAttempts > 0 ? round(($failedAttempts / $completedAttempts) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get recent quiz attempts
     */
    public function getRecentQuizAttempts(int $limit = 10): array
    {
        return $this->getCached("dashboard.recent_quiz_attempts_{$limit}", function () use ($limit) {
            return QuizAttempt::select('quizat_id', 'quizat_quilin_id', 'quizat_user_id', 'quizat_status', 'quizat_scored', 'quizat_marks', 'quizat_evaluation', 'quizat_started', 'quizat_progress')
                ->orderByDesc('quizat_started')
                ->limit($limit)
                ->get()
                ->map(function ($attempt) {
                    $user = User::find($attempt->quizat_user_id);
                    return [
                        'id' => $attempt->quizat_id,
                        'user_name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                        'quiz_title' => 'Quiz #' . $attempt->quizat_quilin_id,
                        'score' => $attempt->quizat_scored ?? 0,
                        'total_marks' => $attempt->quizat_marks,
                        'is_passed' => $attempt->quizat_evaluation == QuizAttempt::EVAL_PASSED,
                        'status' => QuizAttempt::getStatusLabel($attempt->quizat_status),
                        'time_taken' => round($attempt->quizat_progress, 0) . '%',
                        'start_time' => $attempt->quizat_started ? Carbon::parse($attempt->quizat_started)->format('d/m/Y H:i') : '',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get quiz pass/fail rate chart data
     */
    public function getQuizPassFailChartData(int $days = 14): array
    {
        return $this->getCached("dashboard.quiz_chart_{$days}", function () use ($days) {
            $labels = [];
            $passed = [];
            $failed = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();

                $labels[] = $date->format('d/m');

                $passed[] = QuizAttempt::passed()
                    ->whereBetween('quizat_started', [$dayStart, $dayEnd])
                    ->count();

                $failed[] = QuizAttempt::failed()
                    ->whereBetween('quizat_started', [$dayStart, $dayEnd])
                    ->count();
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'passed' => $passed,
                    'failed' => $failed,
                ],
            ];
        });
    }

    /**
     * Get leave quota summary for current year
     */
    public function getLeaveQuotaSummary(): array
    {
        return $this->getCached('dashboard.leave_quota_summary', function () {
            $currentYear = now()->year;
            
            $quotaData = TeacherLeaveQuota::where('tlq_year', $currentYear)
                ->select(
                    DB::raw('SUM(tlq_base_quota) as total_quota'),
                    DB::raw('SUM(tlq_used_days) as total_used'),
                    DB::raw('SUM(tlq_base_quota - tlq_used_days) as total_remaining'),
                    DB::raw('COUNT(DISTINCT tlq_teacher_id) as teachers_count')
                )
                ->first();

            return [
                'year' => $currentYear,
                'total_quota' => $quotaData->total_quota ?? 0,
                'total_used' => $quotaData->total_used ?? 0,
                'total_remaining' => $quotaData->total_remaining ?? 0,
                'teachers_count' => $quotaData->teachers_count ?? 0,
                'usage_rate' => $quotaData->total_quota > 0 
                    ? round(($quotaData->total_used / $quotaData->total_quota) * 100, 1) 
                    : 0,
            ];
        });
    }

    /**
     * Get cancellation breakdown statistics
     * Categorizes cancellations by:
     * - Initiator (who cancelled: student, teacher, system)
     * - Timing (when cancelled: before 24h, within 24h, no-show)
     * - Type (lesson type: trial, regular)
     */
    public function getCancellationBreakdown(int $days = 30): array
    {
        return $this->getCached("dashboard.cancellation_breakdown_{$days}", function () use ($days) {
            $startDate = now()->subDays($days);
            
            // Get all cancelled lessons in the period
            $cancelledLessons = OrderLesson::cancelled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->get();
            
            $totalCancelled = $cancelledLessons->count();
            
            // Cancellation by lesson type
            $trialCancelled = $cancelledLessons->where('ordles_type', OrderLesson::TYPE_TRIAL)->count();
            $regularCancelled = $cancelledLessons->where('ordles_type', OrderLesson::TYPE_REGULAR)->count();
            
            // Cancellation by timing
            $earlyCancellation = 0;
            $lateCancellation = 0;
            
            foreach ($cancelledLessons as $lesson) {
                $startTime = $lesson->ordles_lesson_starttime;
                $earlyCancellation++;
            }
            
            // Get no-show lessons
            $noShowLessons = OrderLesson::scheduled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->count();
            
            // Lessons where teacher didn't join
            $teacherNoShow = OrderLesson::scheduled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->whereNull('ordles_teacher_starttime')
                ->count();
            
            // Lessons where student didn't join
            $studentNoShow = OrderLesson::scheduled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->whereNull('ordles_student_starttime')
                ->count();
            
            // Both didn't join
            $bothNoShow = OrderLesson::scheduled()
                ->where('ordles_lesson_starttime', '>=', $startDate)
                ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW()')
                ->whereNull('ordles_teacher_starttime')
                ->whereNull('ordles_student_starttime')
                ->count();
            
            // Get order cancellations
            $cancelledOrders = Order::where('order_status', Order::STATUS_CANCELLED)
                ->where('order_addedon', '>=', $startDate)
                ->get();
            
            $totalOrdersCancelled = $cancelledOrders->count();
            
            // Order cancellation by type
            $ordersByType = [];
            $typeLabels = Order::getTypeLabels();
            foreach ($typeLabels as $type => $label) {
                $count = $cancelledOrders->where('order_type', $type)->count();
                if ($count > 0) {
                    $ordersByType[$label] = $count;
                }
            }
            
            return [
                'period' => $days . ' ngày gần đây',
                'lessons' => [
                    'total_cancelled' => $totalCancelled,
                    'trial_cancelled' => $trialCancelled,
                    'regular_cancelled' => $regularCancelled,
                    'trial_rate' => $totalCancelled > 0 ? round(($trialCancelled / $totalCancelled) * 100, 1) : 0,
                    'regular_rate' => $totalCancelled > 0 ? round(($regularCancelled / $totalCancelled) * 100, 1) : 0,
                ],
                'no_show' => [
                    'total' => $noShowLessons,
                    'teacher_no_show' => $teacherNoShow,
                    'student_no_show' => $studentNoShow,
                    'both_no_show' => $bothNoShow,
                    'teacher_only' => max(0, $teacherNoShow - $bothNoShow),
                    'student_only' => max(0, $studentNoShow - $bothNoShow),
                ],
                'orders' => [
                    'total_cancelled' => $totalOrdersCancelled,
                    'by_type' => $ordersByType,
                ],
            ];
        });
    }

    // ========================================
    // User Login Statistics
    // ========================================

    /**
     * Get user login statistics overview
     */
    public function getLoginStats(): array
    {
        return $this->getCached('dashboard.login_stats', function () {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();
            $last30Days = now()->subDays(30);

            // Total logins
            $totalLogins = UserLoginLog::loginSuccess()->count();
            $loginsToday = UserLoginLog::loginSuccess()->whereDate('sllg_occurred_at', today())->count();
            $loginsThisWeek = UserLoginLog::loginSuccess()->whereBetween('sllg_occurred_at', [$thisWeek, now()])->count();
            $loginsThisMonth = UserLoginLog::loginSuccess()->whereBetween('sllg_occurred_at', [$thisMonth, now()])->count();
            $loginsLast30Days = UserLoginLog::loginSuccess()->where('sllg_occurred_at', '>=', $last30Days)->count();

            // Unique users who logged in
            $uniqueUsersToday = UserLoginLog::loginSuccess()
                ->whereDate('sllg_occurred_at', today())
                ->distinct('sllg_user_id')
                ->count('sllg_user_id');
            
            $uniqueUsersThisWeek = UserLoginLog::loginSuccess()
                ->whereBetween('sllg_occurred_at', [$thisWeek, now()])
                ->distinct('sllg_user_id')
                ->count('sllg_user_id');
                
            $uniqueUsersThisMonth = UserLoginLog::loginSuccess()
                ->whereBetween('sllg_occurred_at', [$thisMonth, now()])
                ->distinct('sllg_user_id')
                ->count('sllg_user_id');

            return [
                'total_logins' => $totalLogins,
                'today' => $loginsToday,
                'this_week' => $loginsThisWeek,
                'this_month' => $loginsThisMonth,
                'last_30_days' => $loginsLast30Days,
                'unique_users_today' => $uniqueUsersToday,
                'unique_users_this_week' => $uniqueUsersThisWeek,
                'unique_users_this_month' => $uniqueUsersThisMonth,
                'avg_logins_per_day' => $loginsLast30Days > 0 ? round($loginsLast30Days / 30, 1) : 0,
            ];
        });
    }

    /**
     * Get login statistics by user type (Teacher/Learner)
     */
    public function getLoginStatsByUserType(): array
    {
        $last30Days = now()->subDays(30);

        // Get all logins with user info
        $logins = UserLoginLog::loginSuccess()
            ->where('sllg_occurred_at', '>=', $last30Days)
            ->with(['user' => function ($query) {
                $query->select('user_id', 'user_is_teacher', 'user_is_parent', 'user_is_affiliate');
            }])
            ->get();

        $teacherLogins = 0;
        $learnerLogins = 0;
        $parentLogins = 0;
        $affiliateLogins = 0;
        $unknownLogins = 0;

        $uniqueTeachers = [];
        $uniqueLearners = [];
        $uniqueParents = [];
        $uniqueAffiliates = [];

        foreach ($logins as $login) {
            if ($login->user) {
                $userId = $login->sllg_user_id;
                
                if ($login->user->user_is_teacher) {
                    $teacherLogins++;
                    $uniqueTeachers[$userId] = true;
                } else {
                    $learnerLogins++;
                    $uniqueLearners[$userId] = true;
                }

                if ($login->user->user_is_parent) {
                    $parentLogins++;
                    $uniqueParents[$userId] = true;
                }

                if ($login->user->user_is_affiliate) {
                    $affiliateLogins++;
                    $uniqueAffiliates[$userId] = true;
                }
            } else {
                $unknownLogins++;
            }
        }

        $totalLogins = $logins->count();

        return [
            'total' => $totalLogins,
            'teachers' => [
                'logins' => $teacherLogins,
                'unique_users' => count($uniqueTeachers),
                'rate' => $totalLogins > 0 ? round(($teacherLogins / $totalLogins) * 100, 1) : 0,
            ],
            'learners' => [
                'logins' => $learnerLogins,
                'unique_users' => count($uniqueLearners),
                'rate' => $totalLogins > 0 ? round(($learnerLogins / $totalLogins) * 100, 1) : 0,
            ],
            'parents' => [
                'logins' => $parentLogins,
                'unique_users' => count($uniqueParents),
                'rate' => $totalLogins > 0 ? round(($parentLogins / $totalLogins) * 100, 1) : 0,
            ],
            'affiliates' => [
                'logins' => $affiliateLogins,
                'unique_users' => count($uniqueAffiliates),
                'rate' => $totalLogins > 0 ? round(($affiliateLogins / $totalLogins) * 100, 1) : 0,
            ],
            'unknown' => $unknownLogins,
        ];
    }

    /**
     * Get login trend chart data (last X days)
     */
    public function getLoginTrendChartData(int $days = 14): array
    {
        $labels = [];
        $totalLogins = [];
        $uniqueUsers = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $labels[] = $date->format('d/m');

            // Total logins on this day
            $dayLogins = UserLoginLog::loginSuccess()
                ->whereBetween('sllg_occurred_at', [$dayStart, $dayEnd])
                ->count();
            $totalLogins[] = $dayLogins;

            // Unique users on this day
            $uniqueCount = UserLoginLog::loginSuccess()
                ->whereBetween('sllg_occurred_at', [$dayStart, $dayEnd])
                ->distinct('sllg_user_id')
                ->count('sllg_user_id');
            $uniqueUsers[] = $uniqueCount;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'total_logins' => $totalLogins,
                'unique_users' => $uniqueUsers,
            ],
        ];
    }

    /**
     * Get recent login activities
     */
    public function getRecentLogins(int $limit = 10): array
    {
        return UserLoginLog::loginSuccess()
            ->orderByDesc('sllg_occurred_at')
            ->limit($limit)
            ->get()
            ->map(function ($login) {
                $user = User::find($login->sllg_user_id);
                $metadata = $login->sllg_metadata ?? [];
                
                return [
                    'id' => $login->sllg_id,
                    'user_id' => $login->sllg_user_id,
                    'user_name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'user_email' => $metadata['user_email'] ?? ($user?->user_email ?? ''),
                    'user_type' => $user ? ($user->user_is_teacher ? 'Giáo viên' : 'Học viên') : 'Unknown',
                    'login_time' => Carbon::parse($login->sllg_occurred_at)->format('d/m/Y H:i'),
                    'source' => $login->sllg_source ?? 'Unknown',
                ];
            })
            ->toArray();
    }

    /**
     * Get users with most logins (top active users)
     */
    public function getTopActiveUsers(int $limit = 10): array
    {
        $last30Days = now()->subDays(30);

        return UserLoginLog::loginSuccess()
            ->where('sllg_occurred_at', '>=', $last30Days)
            ->select('sllg_user_id', DB::raw('COUNT(*) as login_count'))
            ->groupBy('sllg_user_id')
            ->orderByDesc('login_count')
            ->limit($limit)
            ->get()
            ->map(function ($login) {
                $user = User::find($login->sllg_user_id);
                return [
                    'user_id' => $login->sllg_user_id,
                    'name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'email' => $user?->user_email ?? '',
                    'user_type' => $user ? ($user->user_is_teacher ? 'Giáo viên' : 'Học viên') : 'Unknown',
                    'login_count' => $login->login_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get login statistics by hour of day (to understand peak usage times)
     * Time is converted to GMT+7 (Vietnam timezone)
     */
    public function getLoginsByHour(): array
    {
        $last30Days = now()->subDays(30);

        // Convert to GMT+7 (Vietnam timezone) before extracting hour
        // Using CONVERT_TZ if timezone tables are available, otherwise use +7 hours offset
        $hourlyStats = UserLoginLog::loginSuccess()
            ->where('sllg_occurred_at', '>=', $last30Days)
            ->select(
                DB::raw('HOUR(DATE_ADD(sllg_occurred_at, INTERVAL 7 HOUR)) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours with 0
        $hours = [];
        $counts = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = sprintf('%02d:00', $h);
            $counts[] = $hourlyStats[$h] ?? 0;
        }

        return [
            'labels' => $hours,
            'data' => $counts,
        ];
    }

    /**
     * Get login statistics by day of week
     */
    public function getLoginsByDayOfWeek(): array
    {
        $last30Days = now()->subDays(30);
        $dayNames = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];

        $dailyStats = UserLoginLog::loginSuccess()
            ->where('sllg_occurred_at', '>=', $last30Days)
            ->select(DB::raw('DAYOFWEEK(sllg_occurred_at) as day_of_week'), DB::raw('COUNT(*) as count'))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->pluck('count', 'day_of_week')
            ->toArray();

        // Fill in missing days with 0
        $counts = [];
        for ($d = 1; $d <= 7; $d++) {
            $counts[] = $dailyStats[$d] ?? 0;
        }

        return [
            'labels' => $dayNames,
            'data' => $counts,
        ];
    }

    /**
     * Get login source statistics (platform/website)
     */
    public function getLoginsBySource(): array
    {
        $last30Days = now()->subDays(30);

        return UserLoginLog::loginSuccess()
            ->where('sllg_occurred_at', '>=', $last30Days)
            ->whereNotNull('sllg_source')
            ->select('sllg_source', DB::raw('COUNT(*) as count'))
            ->groupBy('sllg_source')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'sllg_source')
            ->toArray();
    }

    /**
     * Get comprehensive stats for a specific period (today, yesterday, week, month, all)
     */
    public function getStatsForPeriod(string $period): array
    {
        $dates = $this->getPeriodDates($period);
        $start = $dates['start'];
        $end = $dates['end'];

        return [
            'period' => $period,
            'start_date' => $start?->format('Y-m-d'),
            'end_date' => $end?->format('Y-m-d'),
            'users' => $this->getUserStatsForPeriod($start, $end),
            'lessons' => $this->getLessonStatsForPeriod($start, $end),
            'revenue' => $this->getRevenueStatsForPeriod($start, $end),
            'orders' => $this->getOrderStatsForPeriod($start, $end),
            'logins' => $this->getLoginStatsForPeriod($start, $end),
        ];
    }

    /**
     * Get date range for a period
     */
    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'all' => [
                'start' => null,
                'end' => null,
            ],
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }

    /**
     * Get user stats for a period
     */
    private function getUserStatsForPeriod(?Carbon $start, ?Carbon $end): array
    {
        $baseQuery = User::active();

        if ($start && $end) {
            $newUsers = (clone $baseQuery)->whereBetween('user_created', [$start, $end])->count();
            $newTeachers = (clone $baseQuery)->teachers()->whereBetween('user_created', [$start, $end])->count();
            $newLearners = (clone $baseQuery)->learners()->whereBetween('user_created', [$start, $end])->count();
        } else {
            $newUsers = (clone $baseQuery)->count();
            $newTeachers = (clone $baseQuery)->teachers()->count();
            $newLearners = (clone $baseQuery)->learners()->count();
        }

        return [
            'new_users' => $newUsers,
            'new_teachers' => $newTeachers,
            'new_learners' => $newLearners,
        ];
    }

    /**
     * Get lesson stats for a period
     */
    private function getLessonStatsForPeriod(?Carbon $start, ?Carbon $end): array
    {
        if ($start && $end) {
            $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$start, $end]);
        } else {
            $baseQuery = OrderLesson::query();
        }

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->completed()->count();
        $cancelled = (clone $baseQuery)->cancelled()->count();
        $scheduled = (clone $baseQuery)->scheduled()->count();
        $trial = (clone $baseQuery)->trial()->count();
        $regular = (clone $baseQuery)->regular()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'scheduled' => $scheduled,
            'trial' => $trial,
            'regular' => $regular,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get revenue stats for a period
     */
    private function getRevenueStatsForPeriod(?Carbon $start, ?Carbon $end): array
    {
        if ($start && $end) {
            $paidOrders = Order::paid()->whereBetween('order_addedon', [$start, $end]);
        } else {
            $paidOrders = Order::paid();
        }

        $total = $paidOrders->sum('order_total_amount');
        $count = $paidOrders->count();

        return [
            'total' => $total,
            'order_count' => $count,
            'average_order' => $count > 0 ? round($total / $count, 0) : 0,
        ];
    }

    /**
     * Get order stats for a period
     */
    private function getOrderStatsForPeriod(?Carbon $start, ?Carbon $end): array
    {
        if ($start && $end) {
            $baseQuery = Order::whereBetween('order_addedon', [$start, $end]);
        } else {
            $baseQuery = Order::query();
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('order_status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->where('order_status', Order::STATUS_CANCELLED)->count(),
            'in_process' => (clone $baseQuery)->where('order_status', Order::STATUS_INPROCESS)->count(),
        ];
    }

    /**
     * Get login stats for a period
     */
    private function getLoginStatsForPeriod(?Carbon $start, ?Carbon $end): array
    {
        if ($start && $end) {
            $baseQuery = UserLoginLog::loginSuccess()->whereBetween('sllg_occurred_at', [$start, $end]);
        } else {
            $baseQuery = UserLoginLog::loginSuccess();
        }

        $totalLogins = (clone $baseQuery)->count();
        $uniqueUsers = (clone $baseQuery)->distinct('sllg_user_id')->count('sllg_user_id');

        // Get logins by user type
        $logins = (clone $baseQuery)
            ->with(['user' => function ($query) {
                $query->select('user_id', 'user_is_teacher', 'user_is_parent', 'user_is_affiliate');
            }])
            ->get();

        $teacherLogins = 0;
        $learnerLogins = 0;
        $uniqueTeachers = [];
        $uniqueLearners = [];

        foreach ($logins as $login) {
            if ($login->user) {
                $userId = $login->sllg_user_id;
                if ($login->user->user_is_teacher) {
                    $teacherLogins++;
                    $uniqueTeachers[$userId] = true;
                } else {
                    $learnerLogins++;
                    $uniqueLearners[$userId] = true;
                }
            }
        }

        return [
            'total' => $totalLogins,
            'unique_users' => $uniqueUsers,
            'teachers' => $teacherLogins,
            'learners' => $learnerLogins,
            'unique_teachers' => count($uniqueTeachers),
            'unique_learners' => count($uniqueLearners),
        ];
    }

    // ========================================
    // Program/Curriculum Statistics
    // ========================================

    /**
     * Get comprehensive statistics by program
     * Returns stats for all programs including enrollments, lessons, completion rates
     */
    public function getProgramStats(): array
    {
        $programs = Program::select('id', 'title', 'parent', 'status')
            ->withCount('users')
            ->orderBy('parent')
            ->orderBy('id')
            ->get();

        $programStats = [];
        $parentPrograms = [];

        foreach ($programs as $program) {
            // Get curriculum IDs for this program
            $curriculumIds = DB::table('tbl_program_curriculum')
                ->where('program_id', $program->id)
                ->pluck('curriculum_id')
                ->toArray();

            // Get lecture IDs for these curriculums
            $lectureIds = [];
            if (!empty($curriculumIds)) {
                $lectureIds = DB::table('tbl_curriculum_lecture')
                    ->whereIn('curriculum_id', $curriculumIds)
                    ->pluck('id')
                    ->toArray();
            }

            // Get session stats (lessons linked to curriculum lectures)
            $sessionStats = [
                'total' => 0,
                'completed' => 0,
                'incomplete' => 0,
                'upcoming' => 0,
            ];

            if (!empty($lectureIds)) {
                $sessionStats['total'] = CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->count();
                $sessionStats['completed'] = CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->completed()->count();
                $sessionStats['incomplete'] = CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->incomplete()->count();
                $sessionStats['upcoming'] = CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->upcoming()->count();
            }

            // Get unique teachers assigned to this program
            $teacherCount = ProgramUser::where('program_id', $program->id)
                ->distinct('teacher_id')
                ->count('teacher_id');

            // Calculate completion rate
            $completionRate = $sessionStats['total'] > 0 
                ? round(($sessionStats['completed'] / $sessionStats['total']) * 100, 1) 
                : 0;

            $stat = [
                'id' => $program->id,
                'title' => $program->title,
                'parent' => $program->parent,
                'status' => $program->status,
                'is_parent' => $program->parent == 0,
                'enrollments' => $program->users_count,
                'teachers' => $teacherCount,
                'sessions' => $sessionStats,
                'completion_rate' => $completionRate,
            ];

            if ($program->parent == 0) {
                $parentPrograms[$program->id] = $stat;
                $parentPrograms[$program->id]['children'] = [];
            } else {
                if (isset($parentPrograms[$program->parent])) {
                    $parentPrograms[$program->parent]['children'][] = $stat;
                    // Aggregate stats to parent
                    $parentPrograms[$program->parent]['enrollments'] += $stat['enrollments'];
                    $parentPrograms[$program->parent]['teachers'] = max($parentPrograms[$program->parent]['teachers'], $stat['teachers']);
                    $parentPrograms[$program->parent]['sessions']['total'] += $stat['sessions']['total'];
                    $parentPrograms[$program->parent]['sessions']['completed'] += $stat['sessions']['completed'];
                    $parentPrograms[$program->parent]['sessions']['incomplete'] += $stat['sessions']['incomplete'];
                    $parentPrograms[$program->parent]['sessions']['upcoming'] += $stat['sessions']['upcoming'];
                } else {
                    $programStats[] = $stat;
                }
            }
        }

        // Recalculate parent completion rates after aggregation
        foreach ($parentPrograms as &$parent) {
            if ($parent['sessions']['total'] > 0) {
                $parent['completion_rate'] = round(($parent['sessions']['completed'] / $parent['sessions']['total']) * 100, 1);
            }
        }

        return array_merge(array_values($parentPrograms), $programStats);
    }

    /**
     * Get program statistics summary
     */
    public function getProgramStatsSummary(): array
    {
        $totalPrograms = Program::count();
        $publishedPrograms = Program::published()->count();
        $totalEnrollments = ProgramUser::count();
        $uniqueLearners = ProgramUser::distinct('user_id')->count('user_id');
        $uniqueTeachers = ProgramUser::distinct('teacher_id')->count('teacher_id');

        // Parent programs (main categories)
        $parentPrograms = Program::where('parent', 0)->count();
        $childPrograms = Program::where('parent', '>', 0)->count();

        // Top programs by enrollment
        $topPrograms = Program::select('id', 'title')
            ->withCount('users')
            ->orderByDesc('users_count')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'enrollments' => $p->users_count,
                ];
            })
            ->toArray();

        return [
            'total_programs' => $totalPrograms,
            'published_programs' => $publishedPrograms,
            'parent_programs' => $parentPrograms,
            'child_programs' => $childPrograms,
            'total_enrollments' => $totalEnrollments,
            'unique_learners' => $uniqueLearners,
            'unique_teachers' => $uniqueTeachers,
            'top_programs' => $topPrograms,
        ];
    }

    /**
     * Get program enrollment trend (last X days)
     */
    public function getProgramEnrollmentTrend(int $days = 30): array
    {
        $labels = [];
        $enrollments = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $labels[] = $date->format('d/m');
            $enrollments[] = ProgramUser::whereBetween('created_at', [$dayStart, $dayEnd])->count();
        }

        return [
            'labels' => $labels,
            'data' => $enrollments,
        ];
    }

    /**
     * Get detailed stats for a specific program
     */
    public function getProgramDetailStats(int $programId): ?array
    {
        $program = Program::find($programId);
        if (!$program) {
            return null;
        }

        // Get curriculum IDs for this program
        $curriculumIds = DB::table('tbl_program_curriculum')
            ->where('program_id', $programId)
            ->pluck('curriculum_id')
            ->toArray();

        // Get lecture IDs for these curriculums
        $lectureIds = [];
        if (!empty($curriculumIds)) {
            $lectureIds = DB::table('tbl_curriculum_lecture')
                ->whereIn('curriculum_id', $curriculumIds)
                ->pluck('id')
                ->toArray();
        }

        // Session stats
        $sessionTotal = !empty($lectureIds) ? CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->count() : 0;
        $sessionCompleted = !empty($lectureIds) ? CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->completed()->count() : 0;

        // Enrollments
        $enrollments = ProgramUser::where('program_id', $programId)->count();
        $uniqueTeachers = ProgramUser::where('program_id', $programId)->distinct('teacher_id')->count('teacher_id');

        // Recent enrollments
        $recentEnrollments = ProgramUser::where('program_id', $programId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($pu) {
                $user = User::find($pu->user_id);
                $teacher = User::find($pu->teacher_id);
                return [
                    'user_name' => $user ? ($user->user_first_name . ' ' . $user->user_last_name) : 'Unknown',
                    'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                    'enrolled_at' => $pu->created_at ? Carbon::parse($pu->created_at)->format('d/m/Y H:i') : '',
                ];
            })
            ->toArray();

        // Child programs if this is a parent
        $childPrograms = [];
        if ($program->parent == 0) {
            $childPrograms = Program::where('parent', $programId)
                ->withCount('users')
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'enrollments' => $p->users_count,
                    ];
                })
                ->toArray();
        }

        return [
            'id' => $program->id,
            'title' => $program->title,
            'description' => $program->description,
            'status' => $program->status,
            'is_parent' => $program->parent == 0,
            'enrollments' => $enrollments,
            'unique_teachers' => $uniqueTeachers,
            'sessions' => [
                'total' => $sessionTotal,
                'completed' => $sessionCompleted,
                'completion_rate' => $sessionTotal > 0 ? round(($sessionCompleted / $sessionTotal) * 100, 1) : 0,
            ],
            'curriculum_count' => count($curriculumIds),
            'lecture_count' => count($lectureIds),
            'recent_enrollments' => $recentEnrollments,
            'child_programs' => $childPrograms,
        ];
    }

    /**
     * Get statistics grouped by parent program (program categories)
     */
    public function getProgramCategoryStats(): array
    {
        $parentPrograms = Program::where('parent', 0)
            ->select('id', 'title', 'status')
            ->get();

        $result = [];

        foreach ($parentPrograms as $parent) {
            // Get all child program IDs
            $childIds = Program::where('parent', $parent->id)->pluck('id')->toArray();
            $allProgramIds = array_merge([$parent->id], $childIds);

            // Get enrollments for all programs in this category
            $enrollments = ProgramUser::whereIn('program_id', $allProgramIds)->count();
            $uniqueLearners = ProgramUser::whereIn('program_id', $allProgramIds)->distinct('user_id')->count('user_id');
            $uniqueTeachers = ProgramUser::whereIn('program_id', $allProgramIds)->distinct('teacher_id')->count('teacher_id');

            // Get curriculum IDs for all programs in this category
            $curriculumIds = DB::table('tbl_program_curriculum')
                ->whereIn('program_id', $allProgramIds)
                ->pluck('curriculum_id')
                ->toArray();

            // Get session stats
            $lectureIds = [];
            if (!empty($curriculumIds)) {
                $lectureIds = DB::table('tbl_curriculum_lecture')
                    ->whereIn('curriculum_id', $curriculumIds)
                    ->pluck('id')
                    ->toArray();
            }

            $sessionTotal = !empty($lectureIds) ? CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->count() : 0;
            $sessionCompleted = !empty($lectureIds) ? CurriculumSession::whereIn('curriculum_lecture_id', $lectureIds)->completed()->count() : 0;

            $result[] = [
                'id' => $parent->id,
                'title' => $parent->title,
                'status' => $parent->status,
                'child_count' => count($childIds),
                'enrollments' => $enrollments,
                'unique_learners' => $uniqueLearners,
                'unique_teachers' => $uniqueTeachers,
                'sessions_total' => $sessionTotal,
                'sessions_completed' => $sessionCompleted,
                'completion_rate' => $sessionTotal > 0 ? round(($sessionCompleted / $sessionTotal) * 100, 1) : 0,
            ];
        }

        // Sort by enrollments descending
        usort($result, function ($a, $b) {
            return $b['enrollments'] - $a['enrollments'];
        });

        return $result;
    }

    /**
     * Get all programs list (for dropdown/filter)
     */
    public function getAllProgramsList(): array
    {
        return Program::select('id', 'title', 'parent', 'status')
            ->orderBy('parent')
            ->orderBy('title')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'parent' => $p->parent,
                    'status' => $p->status,
                    'is_parent' => $p->parent == 0,
                ];
            })
            ->toArray();
    }

    // ========================================
    // Comparison Statistics (Today vs Yesterday, etc.)
    // ========================================

    /**
     * Get comparison statistics between two periods
     * Returns stats for current period vs previous period with percentage change
     */
    public function getComparisonStats(): array
    {
        return $this->getCached('dashboard.comparison_stats', function () {
            return [
                'today_vs_yesterday' => $this->getTodayVsYesterdayComparison(),
                'yesterday_vs_day_before' => $this->getYesterdayVsDayBeforeComparison(),
                'this_week_vs_last_week' => $this->getWeekComparison(),
                'last_week_vs_week_before' => $this->getLastWeekVsWeekBeforeComparison(),
                'this_month_vs_last_month' => $this->getMonthComparison(),
            ];
        });
    }

    /**
     * Compare today vs yesterday
     */
    private function getTodayVsYesterdayComparison(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        // Today's sessions
        $todayLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$todayStart, $todayEnd]);
        $todayTotal = (clone $todayLessons)->count();
        $todayCompleted = (clone $todayLessons)->completed()->count();
        $todayCancelled = (clone $todayLessons)->cancelled()->count();
        $todayScheduled = (clone $todayLessons)->scheduled()->count();
        $todayFailed = $this->getFailedSessionsCount($todayStart, $todayEnd);

        // Yesterday's sessions
        $yesterdayLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$yesterdayStart, $yesterdayEnd]);
        $yesterdayTotal = (clone $yesterdayLessons)->count();
        $yesterdayCompleted = (clone $yesterdayLessons)->completed()->count();
        $yesterdayCancelled = (clone $yesterdayLessons)->cancelled()->count();
        $yesterdayFailed = $this->getFailedSessionsCount($yesterdayStart, $yesterdayEnd);

        // Revenue
        $todayRevenue = Order::paid()->whereBetween('order_addedon', [$todayStart, $todayEnd])->sum('order_total_amount');
        $yesterdayRevenue = Order::paid()->whereBetween('order_addedon', [$yesterdayStart, $yesterdayEnd])->sum('order_total_amount');

        // Users
        $todayNewUsers = User::active()->whereBetween('user_created', [$todayStart, $todayEnd])->count();
        $yesterdayNewUsers = User::active()->whereBetween('user_created', [$yesterdayStart, $yesterdayEnd])->count();

        return [
            'sessions' => [
                'today' => [
                    'total' => $todayTotal,
                    'completed' => $todayCompleted,
                    'cancelled' => $todayCancelled,
                    'failed' => $todayFailed,
                    'scheduled' => $todayScheduled,
                    'success_rate' => $todayTotal > 0 ? round(($todayCompleted / $todayTotal) * 100, 1) : 0,
                ],
                'yesterday' => [
                    'total' => $yesterdayTotal,
                    'completed' => $yesterdayCompleted,
                    'cancelled' => $yesterdayCancelled,
                    'failed' => $yesterdayFailed,
                ],
                'change' => [
                    'total' => $this->calculatePercentChange($yesterdayTotal, $todayTotal),
                    'completed' => $this->calculatePercentChange($yesterdayCompleted, $todayCompleted),
                    'cancelled' => $this->calculatePercentChange($yesterdayCancelled, $todayCancelled),
                    'failed' => $this->calculatePercentChange($yesterdayFailed, $todayFailed),
                ],
            ],
            'revenue' => [
                'today' => $todayRevenue,
                'yesterday' => $yesterdayRevenue,
                'change' => $this->calculatePercentChange($yesterdayRevenue, $todayRevenue),
            ],
            'users' => [
                'today' => $todayNewUsers,
                'yesterday' => $yesterdayNewUsers,
                'change' => $this->calculatePercentChange($yesterdayNewUsers, $todayNewUsers),
            ],
        ];
    }

    /**
     * Compare yesterday vs day before yesterday (hôm qua vs hôm kia)
     */
    private function getYesterdayVsDayBeforeComparison(): array
    {
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();
        $dayBeforeStart = now()->subDays(2)->startOfDay();
        $dayBeforeEnd = now()->subDays(2)->endOfDay();

        // Yesterday's sessions
        $yesterdayLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$yesterdayStart, $yesterdayEnd]);
        $yesterdayTotal = (clone $yesterdayLessons)->count();
        $yesterdayCompleted = (clone $yesterdayLessons)->completed()->count();
        $yesterdayCancelled = (clone $yesterdayLessons)->cancelled()->count();
        $yesterdayScheduled = (clone $yesterdayLessons)->scheduled()->count();
        $yesterdayFailed = $this->getFailedSessionsCount($yesterdayStart, $yesterdayEnd);

        // Day before yesterday's sessions
        $dayBeforeLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayBeforeStart, $dayBeforeEnd]);
        $dayBeforeTotal = (clone $dayBeforeLessons)->count();
        $dayBeforeCompleted = (clone $dayBeforeLessons)->completed()->count();
        $dayBeforeCancelled = (clone $dayBeforeLessons)->cancelled()->count();
        $dayBeforeFailed = $this->getFailedSessionsCount($dayBeforeStart, $dayBeforeEnd);

        // Revenue
        $yesterdayRevenue = Order::paid()->whereBetween('order_addedon', [$yesterdayStart, $yesterdayEnd])->sum('order_total_amount');
        $dayBeforeRevenue = Order::paid()->whereBetween('order_addedon', [$dayBeforeStart, $dayBeforeEnd])->sum('order_total_amount');

        // Users
        $yesterdayNewUsers = User::active()->whereBetween('user_created', [$yesterdayStart, $yesterdayEnd])->count();
        $dayBeforeNewUsers = User::active()->whereBetween('user_created', [$dayBeforeStart, $dayBeforeEnd])->count();

        return [
            'sessions' => [
                'yesterday' => [
                    'total' => $yesterdayTotal,
                    'completed' => $yesterdayCompleted,
                    'cancelled' => $yesterdayCancelled,
                    'failed' => $yesterdayFailed,
                    'scheduled' => $yesterdayScheduled,
                    'success_rate' => $yesterdayTotal > 0 ? round(($yesterdayCompleted / $yesterdayTotal) * 100, 1) : 0,
                ],
                'day_before' => [
                    'total' => $dayBeforeTotal,
                    'completed' => $dayBeforeCompleted,
                    'cancelled' => $dayBeforeCancelled,
                    'failed' => $dayBeforeFailed,
                ],
                'change' => [
                    'total' => $this->calculatePercentChange($dayBeforeTotal, $yesterdayTotal),
                    'completed' => $this->calculatePercentChange($dayBeforeCompleted, $yesterdayCompleted),
                    'cancelled' => $this->calculatePercentChange($dayBeforeCancelled, $yesterdayCancelled),
                    'failed' => $this->calculatePercentChange($dayBeforeFailed, $yesterdayFailed),
                ],
            ],
            'revenue' => [
                'yesterday' => $yesterdayRevenue,
                'day_before' => $dayBeforeRevenue,
                'change' => $this->calculatePercentChange($dayBeforeRevenue, $yesterdayRevenue),
            ],
            'users' => [
                'yesterday' => $yesterdayNewUsers,
                'day_before' => $dayBeforeNewUsers,
                'change' => $this->calculatePercentChange($dayBeforeNewUsers, $yesterdayNewUsers),
            ],
        ];
    }

    /**
     * Compare this week vs last week
     */
    private function getWeekComparison(): array
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        // This week sessions
        $thisWeekLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$thisWeekStart, $thisWeekEnd]);
        $thisWeekTotal = (clone $thisWeekLessons)->count();
        $thisWeekCompleted = (clone $thisWeekLessons)->completed()->count();
        $thisWeekCancelled = (clone $thisWeekLessons)->cancelled()->count();
        $thisWeekFailed = $this->getFailedSessionsCount($thisWeekStart, $thisWeekEnd);

        // Last week sessions
        $lastWeekLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$lastWeekStart, $lastWeekEnd]);
        $lastWeekTotal = (clone $lastWeekLessons)->count();
        $lastWeekCompleted = (clone $lastWeekLessons)->completed()->count();
        $lastWeekCancelled = (clone $lastWeekLessons)->cancelled()->count();
        $lastWeekFailed = $this->getFailedSessionsCount($lastWeekStart, $lastWeekEnd);

        // Revenue
        $thisWeekRevenue = Order::paid()->whereBetween('order_addedon', [$thisWeekStart, $thisWeekEnd])->sum('order_total_amount');
        $lastWeekRevenue = Order::paid()->whereBetween('order_addedon', [$lastWeekStart, $lastWeekEnd])->sum('order_total_amount');

        // Users
        $thisWeekNewUsers = User::active()->whereBetween('user_created', [$thisWeekStart, $thisWeekEnd])->count();
        $lastWeekNewUsers = User::active()->whereBetween('user_created', [$lastWeekStart, $lastWeekEnd])->count();

        return [
            'sessions' => [
                'current' => [
                    'total' => $thisWeekTotal,
                    'completed' => $thisWeekCompleted,
                    'cancelled' => $thisWeekCancelled,
                    'failed' => $thisWeekFailed,
                    'success_rate' => $thisWeekTotal > 0 ? round(($thisWeekCompleted / $thisWeekTotal) * 100, 1) : 0,
                ],
                'previous' => [
                    'total' => $lastWeekTotal,
                    'completed' => $lastWeekCompleted,
                    'cancelled' => $lastWeekCancelled,
                    'failed' => $lastWeekFailed,
                ],
                'change' => [
                    'total' => $this->calculatePercentChange($lastWeekTotal, $thisWeekTotal),
                    'completed' => $this->calculatePercentChange($lastWeekCompleted, $thisWeekCompleted),
                    'cancelled' => $this->calculatePercentChange($lastWeekCancelled, $thisWeekCancelled),
                    'failed' => $this->calculatePercentChange($lastWeekFailed, $thisWeekFailed),
                ],
            ],
            'revenue' => [
                'current' => $thisWeekRevenue,
                'previous' => $lastWeekRevenue,
                'change' => $this->calculatePercentChange($lastWeekRevenue, $thisWeekRevenue),
            ],
            'users' => [
                'current' => $thisWeekNewUsers,
                'previous' => $lastWeekNewUsers,
                'change' => $this->calculatePercentChange($lastWeekNewUsers, $thisWeekNewUsers),
            ],
        ];
    }

    /**
     * Compare last week vs week before last (Tuần trước vs Tuần trước nữa)
     */
    private function getLastWeekVsWeekBeforeComparison(): array
    {
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();
        $weekBeforeStart = now()->subWeeks(2)->startOfWeek();
        $weekBeforeEnd = now()->subWeeks(2)->endOfWeek();

        // Last week sessions
        $lastWeekLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$lastWeekStart, $lastWeekEnd]);
        $lastWeekTotal = (clone $lastWeekLessons)->count();
        $lastWeekCompleted = (clone $lastWeekLessons)->completed()->count();
        $lastWeekCancelled = (clone $lastWeekLessons)->cancelled()->count();
        $lastWeekFailed = $this->getFailedSessionsCount($lastWeekStart, $lastWeekEnd);

        // Week before last sessions
        $weekBeforeLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$weekBeforeStart, $weekBeforeEnd]);
        $weekBeforeTotal = (clone $weekBeforeLessons)->count();
        $weekBeforeCompleted = (clone $weekBeforeLessons)->completed()->count();
        $weekBeforeCancelled = (clone $weekBeforeLessons)->cancelled()->count();
        $weekBeforeFailed = $this->getFailedSessionsCount($weekBeforeStart, $weekBeforeEnd);

        // Revenue
        $lastWeekRevenue = Order::paid()->whereBetween('order_addedon', [$lastWeekStart, $lastWeekEnd])->sum('order_total_amount');
        $weekBeforeRevenue = Order::paid()->whereBetween('order_addedon', [$weekBeforeStart, $weekBeforeEnd])->sum('order_total_amount');

        // Users
        $lastWeekNewUsers = User::active()->whereBetween('user_created', [$lastWeekStart, $lastWeekEnd])->count();
        $weekBeforeNewUsers = User::active()->whereBetween('user_created', [$weekBeforeStart, $weekBeforeEnd])->count();

        return [
            'sessions' => [
                'current' => [
                    'total' => $lastWeekTotal,
                    'completed' => $lastWeekCompleted,
                    'cancelled' => $lastWeekCancelled,
                    'failed' => $lastWeekFailed,
                    'success_rate' => $lastWeekTotal > 0 ? round(($lastWeekCompleted / $lastWeekTotal) * 100, 1) : 0,
                ],
                'previous' => [
                    'total' => $weekBeforeTotal,
                    'completed' => $weekBeforeCompleted,
                    'cancelled' => $weekBeforeCancelled,
                    'failed' => $weekBeforeFailed,
                ],
                'change' => [
                    'total' => $this->calculatePercentChange($weekBeforeTotal, $lastWeekTotal),
                    'completed' => $this->calculatePercentChange($weekBeforeCompleted, $lastWeekCompleted),
                    'cancelled' => $this->calculatePercentChange($weekBeforeCancelled, $lastWeekCancelled),
                    'failed' => $this->calculatePercentChange($weekBeforeFailed, $lastWeekFailed),
                ],
            ],
            'revenue' => [
                'current' => $lastWeekRevenue,
                'previous' => $weekBeforeRevenue,
                'change' => $this->calculatePercentChange($weekBeforeRevenue, $lastWeekRevenue),
            ],
            'users' => [
                'current' => $lastWeekNewUsers,
                'previous' => $weekBeforeNewUsers,
                'change' => $this->calculatePercentChange($weekBeforeNewUsers, $lastWeekNewUsers),
            ],
        ];
    }

    /**
     * Compare this month vs last month
     */
    private function getMonthComparison(): array
    {
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // This month sessions
        $thisMonthLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$thisMonthStart, $thisMonthEnd]);
        $thisMonthTotal = (clone $thisMonthLessons)->count();
        $thisMonthCompleted = (clone $thisMonthLessons)->completed()->count();
        $thisMonthCancelled = (clone $thisMonthLessons)->cancelled()->count();
        $thisMonthFailed = $this->getFailedSessionsCount($thisMonthStart, $thisMonthEnd);

        // Last month sessions
        $lastMonthLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$lastMonthStart, $lastMonthEnd]);
        $lastMonthTotal = (clone $lastMonthLessons)->count();
        $lastMonthCompleted = (clone $lastMonthLessons)->completed()->count();
        $lastMonthCancelled = (clone $lastMonthLessons)->cancelled()->count();
        $lastMonthFailed = $this->getFailedSessionsCount($lastMonthStart, $lastMonthEnd);

        // Revenue
        $thisMonthRevenue = Order::paid()->whereBetween('order_addedon', [$thisMonthStart, $thisMonthEnd])->sum('order_total_amount');
        $lastMonthRevenue = Order::paid()->whereBetween('order_addedon', [$lastMonthStart, $lastMonthEnd])->sum('order_total_amount');

        // Users
        $thisMonthNewUsers = User::active()->whereBetween('user_created', [$thisMonthStart, $thisMonthEnd])->count();
        $lastMonthNewUsers = User::active()->whereBetween('user_created', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'sessions' => [
                'current' => [
                    'total' => $thisMonthTotal,
                    'completed' => $thisMonthCompleted,
                    'cancelled' => $thisMonthCancelled,
                    'failed' => $thisMonthFailed,
                    'success_rate' => $thisMonthTotal > 0 ? round(($thisMonthCompleted / $thisMonthTotal) * 100, 1) : 0,
                ],
                'previous' => [
                    'total' => $lastMonthTotal,
                    'completed' => $lastMonthCompleted,
                    'cancelled' => $lastMonthCancelled,
                    'failed' => $lastMonthFailed,
                ],
                'change' => [
                    'total' => $this->calculatePercentChange($lastMonthTotal, $thisMonthTotal),
                    'completed' => $this->calculatePercentChange($lastMonthCompleted, $thisMonthCompleted),
                    'cancelled' => $this->calculatePercentChange($lastMonthCancelled, $thisMonthCancelled),
                    'failed' => $this->calculatePercentChange($lastMonthFailed, $thisMonthFailed),
                ],
            ],
            'revenue' => [
                'current' => $thisMonthRevenue,
                'previous' => $lastMonthRevenue,
                'change' => $this->calculatePercentChange($lastMonthRevenue, $thisMonthRevenue),
            ],
            'users' => [
                'current' => $thisMonthNewUsers,
                'previous' => $lastMonthNewUsers,
                'change' => $this->calculatePercentChange($lastMonthNewUsers, $thisMonthNewUsers),
            ],
        ];
    }

    /**
     * Get failed sessions count for a date range
     * Failed = status = 3 (Completed) AND acceptance_code != 12
     */
    private function getFailedSessionsCount($start, $end): int
    {
        return OrderLesson::whereBetween('ordles_lesson_starttime', [$start, $end])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->where('tbl_order_lessons_extras.ole_acceptance_code', '!=', OrderLessonExtra::ACCEPTANCE_SUCCESS)
            ->count();
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentChange($previous, $current): array
    {
        if ($previous == 0 && $current == 0) {
            return ['value' => 0, 'direction' => 'neutral'];
        }
        
        if ($previous == 0) {
            return ['value' => 100, 'direction' => 'up'];
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        return [
            'value' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get session success/failure breakdown by type
     * Based on SQL Query.md definitions:
     * - Scheduled: ordles_status = 2 and speakwell subjects
     * - Success (Thành công): ordles_status = 3 AND ole_acceptance_code = 12
     * - Failure (Thất bại): ordles_status = 3 AND ole_acceptance_code != 12
     * - Cancelled (Bị hủy): ordles_status = 4
     * - Teacher no-show: ordles_status = 3 AND ole_teacher_first_join IS NULL
     * - Student no-show: ordles_status = 3 AND ole_student_first_join IS NULL
     */
    public function getSessionSuccessFailureBreakdown(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (!$start) {
            $start = now()->startOfDay();
        }
        if (!$end) {
            $end = now()->endOfDay();
        }

        // Convert Vietnam timezone to UTC for database query
        // The database stores times in UTC, so we need to adjust
        // Vietnam is UTC+7, so we subtract 7 hours
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Base query with speakwell subjects filter
        $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds());

        // Total scheduled lessons (ordles_status = 2)
        $scheduled = (clone $baseQuery)->scheduled()->count();

        // Phase 199: Total cancelled lessons (ALL with ordles_status = 4) - used for total calculation
        $cancelledAll = (clone $baseQuery)->cancelled()->count();

        // Urgent cancellation (Hủy gấp): cancelled with session_logs + 24h condition
        // Conditions: ordles_status = 4 AND sesslog_changed_status = 4 (in session_logs)
        // AND ordles_updated > DATE_SUB(ordles_lesson_starttime, INTERVAL 1 DAY)
        $urgentCancelled = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->cancelled()
            ->whereRaw('ordles_updated > DATE_SUB(ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbl_session_logs')
                    ->whereRaw('tbl_session_logs.sesslog_record_id = tbl_order_lessons.ordles_id')
                    ->where('tbl_session_logs.sesslog_changed_status', SessionLog::STATUS_CANCELLED)
                    ->where('tbl_session_logs.sesslog_record_type', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->count();

        // Phase 102: Breakdown of urgent cancellations by who cancelled (Teacher/Student/Admin)
        $urgentBreakdown = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->selectRaw('sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) as cnt')
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->groupBy('sl.sesslog_user_type')
            ->get()
            ->keyBy('sesslog_user_type');

        $urgentByTeacher = (int) ($urgentBreakdown->get(SessionLog::USER_TYPE_TEACHER)?->cnt ?? 0);
        $urgentByStudent = (int) ($urgentBreakdown->get(SessionLog::USER_TYPE_STUDENT)?->cnt ?? 0);
        $urgentByAdmin = (int) ($urgentBreakdown->get(SessionLog::USER_TYPE_ADMIN)?->cnt ?? 0);

        // Total completed lessons (ordles_status = 3) - used as base for success/failure breakdown
        $completed = (clone $baseQuery)->completed()->count();

        // Success: completed (status=3) AND acceptance_code = 12
        // Need to join with tbl_order_lessons_extras
        $success = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->where('tbl_order_lessons_extras.ole_acceptance_code', OrderLessonExtra::ACCEPTANCE_SUCCESS)
            ->count();

        // Failure (Thất bại): completed (status=3) AND acceptance_code != 12
        $failure = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->where('tbl_order_lessons_extras.ole_acceptance_code', '!=', OrderLessonExtra::ACCEPTANCE_SUCCESS)
            ->count();

        // Teacher no-show: completed (status=3) AND ole_teacher_first_join IS NULL
        $teacherNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->count();

        // Student no-show: completed (status=3) AND ole_student_first_join IS NULL
        $studentNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_student_first_join')
            ->count();

        // Both no-show: completed (status=3) AND both joins are NULL
        $bothNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNull('tbl_order_lessons_extras.ole_student_first_join')
            ->count();

        // Unscheduled lessons (ordles_status = 1)
        $unscheduled = (clone $baseQuery)->where('ordles_status', OrderLesson::STATUS_UNSCHEDULED)->count();

        // Chargeable sessions: completed with acceptance codes that charge the student (100% or 50%)
        // Codes that charge students: 4,5,6,7,8,9,10,11,12,16,17
        $chargeable = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', self::CHARGEABLE_CODES)
            ->count();

        // Compensate sessions: completed with acceptance codes that require compensation (make-up lessons)
        // Codes that require compensation: 1,2,3,13,14,15
        $compensate = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', self::COMPENSATE_CODES)
            ->count();

        // Sessions awaiting ClassIn data: completed but no extras data
        // Split into two categories based on time since lesson ended:
        // 1. awaiting_within_30min: <= 30 minutes since lesson ended (still waiting for ClassIn to send data)
        // 2. no_data_over_30min: > 30 minutes since lesson ended (data likely missing on ClassIn)
        
        // Current time in UTC for comparison (database stores times in UTC)
        $nowUtc = now()->subHours(7);
        
        // Get completed sessions with NO extras data, ended <= 30 minutes ago
        // Need to use LEFT JOIN and check for NULL to find sessions without extras data
        $awaitingWithin30min = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->leftJoin('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_ordles_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, ordles_lesson_endtime, ?) <= 30', [$nowUtc])
            ->count();
        
        // Get completed sessions with NO extras data, ended > 30 minutes ago
        $noDataOver30min = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->leftJoin('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_ordles_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, ordles_lesson_endtime, ?) > 30', [$nowUtc])
            ->count();
        
        // Total awaiting ClassIn data (for backward compatibility)
        $awaitingClassInData = $awaitingWithin30min + $noDataOver30min;

        // Phase 199: Total sessions = scheduled + completed + ALL cancelled (status 2+3+4)
        // Previously used $urgentCancelled (restrictive) which caused total to be lower than expected
        $total = $scheduled + $completed + $cancelledAll;

        // Total no-shows (from the completed lessons that have no-show records)
        $totalNoShow = $teacherNoShow + $studentNoShow - $bothNoShow; // Avoid double counting

        // Total failure = failure (bad acceptance code) + ALL cancelled + no-shows
        // Note: No-shows are a subset of completed lessons, so we don't double count with cancelled
        // Phase 199: Use $cancelledAll instead of $urgentCancelled for consistency with $total
        $totalFailure = $failure + $cancelledAll + $totalNoShow;

        // Success rate is based on successful completions vs total sessions
        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
        $failureRate = $total > 0 ? round(($totalFailure / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            // Phase 199: Status breakdown - cancelled now counts ALL ordles_status=4 (not just urgent)
            'status_breakdown' => [
                'scheduled' => $scheduled,      // Status = 2
                'completed' => $completed,      // Status = 3
                'cancelled' => $cancelledAll,   // Status = 4 (ALL cancelled sessions)
                'urgent_cancelled' => $urgentCancelled,  // Status = 4 with session_logs + 24h condition
                'urgent_by_teacher' => $urgentByTeacher,  // Phase 102: cancelled by teacher
                'urgent_by_student' => $urgentByStudent,  // Phase 102: cancelled by student
                'urgent_by_admin' => $urgentByAdmin,      // Phase 102: cancelled by admin
            ],
            // Hierarchical breakdown for completed sessions (Phase 5 - KPI hierarchy)
            'completed_breakdown' => [
                'total' => $completed,
                'chargeable' => $chargeable,           // Sessions that charge the student
                'compensate' => $compensate,           // Sessions that require make-up (bù buổi)
                'awaiting_classin_data' => $awaitingClassInData,  // Completed but waiting for ClassIn data
                'awaiting_within_30min' => $awaitingWithin30min,  // No extras data, ended <= 30 min ago
                'no_data_over_30min' => $noDataOver30min,         // No extras data, ended > 30 min ago (likely missing on ClassIn)
            ],
            'success' => [
                'count' => $success,
                'rate' => $successRate,
            ],
            'failure' => [
                'count' => $totalFailure,
                'rate' => $failureRate,
                'breakdown' => [
                    'cancelled' => $cancelledAll,
                    'failed' => $failure, // Completed but acceptance_code != 12
                    'no_show' => [
                        'total' => $totalNoShow,
                        'teacher_only' => max(0, $teacherNoShow - $bothNoShow),
                        'student_only' => max(0, $studentNoShow - $bothNoShow),
                        'both' => $bothNoShow,
                    ],
                ],
            ],
            'pending' => [
                'scheduled' => $scheduled,
                'unscheduled' => $unscheduled,
            ],
            // Phase 143: Program-specific breakdowns (SPEAKWELL & EASYSPEAK)
            'program_breakdown' => $this->getPerProgramBreakdown($startUtc, $endUtc),
            // Phase 225: Class type breakdown (1:1 from tbl_order_lessons, 1:2 from tbl_group_classes)
            'class_type_breakdown' => $this->getClassTypeBreakdown($startUtc, $endUtc, [
                'total' => $total,
                'scheduled' => $scheduled,
                'completed' => $completed,
                'cancelled' => $cancelledAll,
                'chargeable' => $chargeable,
                'compensate' => $compensate,
                'awaiting_within_30min' => $awaitingWithin30min,
                'no_data_over_30min' => $noDataOver30min,
                'urgent_cancelled' => $urgentCancelled,
                'urgent_by_teacher' => $urgentByTeacher,
                'urgent_by_student' => $urgentByStudent,
                'urgent_by_admin' => $urgentByAdmin,
            ]),
        ];
    }

    /**
     * Phase 225: Get class type breakdown stats (1:1 from tbl_order_lessons, 1:2 from tbl_group_classes)
     *
     * @param Carbon $startUtc Start time in UTC
     * @param Carbon $endUtc End time in UTC
     * @param array $oneOnOneData Already-computed 1:1 data from getSessionSuccessFailureBreakdown
     * @return array ['one_on_one' => [...], 'one_on_two' => [...]]
     */
    protected function getClassTypeBreakdown(Carbon $startUtc, Carbon $endUtc, array $oneOnOneData): array
    {
        $subjectIds = $this->getActiveSubjectIds();

        // ===== 1:2 Stats from tbl_group_classes =====

        // Query 1: Status counts (scheduled, completed, cancelled)
        $gcStatusCounts = DB::connection('mysql')
            ->table('tbl_group_classes')
            ->selectRaw('grpcls_status, COUNT(*) as cnt')
            ->whereIn('grpcls_status', [
                GroupClass::STATUS_SCHEDULED,
                GroupClass::STATUS_COMPLETED,
                GroupClass::STATUS_CANCELLED,
            ])
            ->whereBetween('grpcls_start_datetime', [$startUtc, $endUtc])
            ->whereIn('grpcls_tlang_id', $subjectIds)
            ->groupBy('grpcls_status')
            ->get()
            ->keyBy('grpcls_status');

        $gcScheduled = (int) ($gcStatusCounts->get(GroupClass::STATUS_SCHEDULED)?->cnt ?? 0);
        $gcCompleted = (int) ($gcStatusCounts->get(GroupClass::STATUS_COMPLETED)?->cnt ?? 0);
        $gcCancelled = (int) ($gcStatusCounts->get(GroupClass::STATUS_CANCELLED)?->cnt ?? 0);
        $gcTotal = $gcScheduled + $gcCompleted + $gcCancelled;

        // Query 2: Urgent cancellation (cancelled with session_logs within 24h)
        $gcUrgentCancelled = DB::connection('mysql')
            ->table('tbl_group_classes as gc')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('gc.grpcls_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', GroupClass::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_GROUP_CLASS);
            })
            ->where('gc.grpcls_status', GroupClass::STATUS_CANCELLED)
            ->whereIn('gc.grpcls_tlang_id', $subjectIds)
            ->whereRaw('sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)')
            ->whereBetween('gc.grpcls_start_datetime', [$startUtc, $endUtc])
            ->distinct()
            ->count('gc.grpcls_id');

        // Query 3: Urgent cancellation breakdown by user type
        $gcUrgentBreakdown = DB::connection('mysql')
            ->table('tbl_group_classes as gc')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('gc.grpcls_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', GroupClass::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_GROUP_CLASS);
            })
            ->selectRaw('sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id) as cnt')
            ->where('gc.grpcls_status', GroupClass::STATUS_CANCELLED)
            ->whereIn('gc.grpcls_tlang_id', $subjectIds)
            ->whereRaw('sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)')
            ->whereBetween('gc.grpcls_start_datetime', [$startUtc, $endUtc])
            ->groupBy('sl.sesslog_user_type')
            ->get()
            ->keyBy('sesslog_user_type');

        $gcUrgentByTeacher = (int) ($gcUrgentBreakdown->get(SessionLog::USER_TYPE_TEACHER)?->cnt ?? 0);
        $gcUrgentByStudent = (int) ($gcUrgentBreakdown->get(SessionLog::USER_TYPE_STUDENT)?->cnt ?? 0);
        $gcUrgentByAdmin = (int) ($gcUrgentBreakdown->get(SessionLog::USER_TYPE_ADMIN)?->cnt ?? 0);

        return [
            'one_on_one' => $oneOnOneData,
            'one_on_two' => [
                'total' => $gcTotal,
                'scheduled' => $gcScheduled,
                'completed' => $gcCompleted,
                'cancelled' => $gcCancelled,
                'urgent_cancelled' => $gcUrgentCancelled,
                'urgent_by_teacher' => $gcUrgentByTeacher,
                'urgent_by_student' => $gcUrgentByStudent,
                'urgent_by_admin' => $gcUrgentByAdmin,
            ],
        ];
    }

    /**
     * Phase 143: Get per-program breakdown stats for SPEAKWELL & EASYSPEAK
     * Uses efficient grouped queries with CASE WHEN to minimize DB calls
     *
     * @param Carbon $startUtc Start time in UTC
     * @param Carbon $endUtc End time in UTC
     * @return array ['speakwell' => [...], 'easyspeak' => [...]]
     */
    protected function getPerProgramBreakdown(Carbon $startUtc, Carbon $endUtc): array
    {
        $swIds = self::SPEAKWELL_ONLY_SUBJECT_IDS;
        $esIds = self::EASY_SPEAK_SUBJECT_IDS;
        $allIds = array_merge($swIds, $esIds);
        $nowUtc = now()->subHours(7);

        // Build the CASE expression for program classification
        $swPlaceholders = implode(',', array_fill(0, count($swIds), '?'));
        $programCase = "CASE WHEN ol.ordles_tlang_id IN ($swPlaceholders) THEN 'speakwell' ELSE 'easyspeak' END";
        // For queries without alias
        $programCaseNoAlias = "CASE WHEN ordles_tlang_id IN ($swPlaceholders) THEN 'speakwell' ELSE 'easyspeak' END";

        // Query 1: Status counts by program (scheduled, completed, cancelled in one query)
        // Use positional GROUP BY (1, 2) to avoid ONLY_FULL_GROUP_BY issues
        $statusCounts = DB::connection('mysql')
            ->table('tbl_order_lessons')
            ->selectRaw("$programCaseNoAlias as program, ordles_status, COUNT(*) as cnt", $swIds)
            ->whereIn('ordles_status', [
                OrderLesson::STATUS_SCHEDULED,
                OrderLesson::STATUS_COMPLETED,
                OrderLesson::STATUS_CANCELLED,
            ])
            ->whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $allIds)
            ->groupByRaw("1, 2")
            ->get();

        // Parse status counts into structured array
        $programStats = [
            'speakwell' => ['scheduled' => 0, 'completed' => 0, 'cancelled_raw' => 0],
            'easyspeak' => ['scheduled' => 0, 'completed' => 0, 'cancelled_raw' => 0],
        ];
        foreach ($statusCounts as $row) {
            $p = $row->program;
            match ((int) $row->ordles_status) {
                OrderLesson::STATUS_SCHEDULED => $programStats[$p]['scheduled'] = (int) $row->cnt,
                OrderLesson::STATUS_COMPLETED => $programStats[$p]['completed'] = (int) $row->cnt,
                OrderLesson::STATUS_CANCELLED => $programStats[$p]['cancelled_raw'] = (int) $row->cnt,
                default => null,
            };
        }

        // Query 2: Chargeable sessions by program
        $chargeableCounts = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_order_lessons_extras as ole', 'ol.ordles_id', '=', 'ole.ole_ordles_id')
            ->selectRaw("$programCase as program, COUNT(*) as cnt", $swIds)
            ->where('ol.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $allIds)
            ->whereIn('ole.ole_acceptance_code', self::CHARGEABLE_CODES)
            ->groupByRaw("1")
            ->get()
            ->keyBy('program');

        // Query 3a: Awaiting ClassIn data within 30 min by program
        $awaitingWithin30minCounts = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->leftJoin('tbl_order_lessons_extras as ole', 'ol.ordles_id', '=', 'ole.ole_ordles_id')
            ->selectRaw("$programCase as program, COUNT(*) as cnt", $swIds)
            ->where('ol.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $allIds)
            ->whereNull('ole.ole_ordles_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_endtime, ?) <= 30', [$nowUtc])
            ->groupByRaw("1")
            ->get()
            ->keyBy('program');

        // Query 3b: No data over 30 min by program
        $noDataOver30minCounts = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->leftJoin('tbl_order_lessons_extras as ole', 'ol.ordles_id', '=', 'ole.ole_ordles_id')
            ->selectRaw("$programCase as program, COUNT(*) as cnt", $swIds)
            ->where('ol.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $allIds)
            ->whereNull('ole.ole_ordles_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_endtime, ?) > 30', [$nowUtc])
            ->groupByRaw("1")
            ->get()
            ->keyBy('program');

        // Query 4: Cancelled with session_logs (urgent cancellation) by program
        $cancelledCounts = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->selectRaw("$programCase as program, COUNT(DISTINCT ol.ordles_id) as cnt", $swIds)
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $allIds)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->groupByRaw("1")
            ->get()
            ->keyBy('program');

        // Query 5: Urgent cancellation breakdown by user type and program
        $urgentBreakdown = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->selectRaw("$programCase as program, sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) as cnt", $swIds)
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ol.ordles_tlang_id', $allIds)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->groupByRaw("1, 2")
            ->get();

        // Parse urgent breakdown into program -> user_type -> count
        $urgentByProgramAndType = ['speakwell' => [], 'easyspeak' => []];
        foreach ($urgentBreakdown as $row) {
            $urgentByProgramAndType[$row->program][$row->sesslog_user_type] = (int) $row->cnt;
        }

        // Phase 199: Build final result for each program
        // Use cancelled_raw (ALL status=4) for total and cancelled display
        // Use cancelledCounts (with session_logs + 24h) for urgent_cancelled only
        $result = [];
        foreach (['speakwell', 'easyspeak'] as $prog) {
            $cancelledAll = $programStats[$prog]['cancelled_raw'];  // ALL cancelled (status=4)
            $urgentCancelled = (int) ($cancelledCounts->get($prog)?->cnt ?? 0);  // Urgent only
            $scheduled = $programStats[$prog]['scheduled'];
            $completed = $programStats[$prog]['completed'];
            $chargeable = (int) ($chargeableCounts->get($prog)?->cnt ?? 0);
            $awaitingWithin30min = (int) ($awaitingWithin30minCounts->get($prog)?->cnt ?? 0);
            $noDataOver30min = (int) ($noDataOver30minCounts->get($prog)?->cnt ?? 0);
            $urgentByTeacher = (int) ($urgentByProgramAndType[$prog][SessionLog::USER_TYPE_TEACHER] ?? 0);
            $urgentByStudent = (int) ($urgentByProgramAndType[$prog][SessionLog::USER_TYPE_STUDENT] ?? 0);
            $urgentByAdmin = (int) ($urgentByProgramAndType[$prog][SessionLog::USER_TYPE_ADMIN] ?? 0);

            $result[$prog] = [
                'total' => $scheduled + $completed + $cancelledAll,
                'chargeable' => $chargeable,
                'awaiting_within_30min' => $awaitingWithin30min,
                'no_data_over_30min' => $noDataOver30min,
                'scheduled' => $scheduled,
                'cancelled' => $cancelledAll,
                'urgent_cancelled' => $urgentCancelled,
                'urgent_by_teacher' => $urgentByTeacher,
                'urgent_by_student' => $urgentByStudent,
                'urgent_by_admin' => $urgentByAdmin,
            ];
        }

        return $result;
    }

    /**
     * Get session stats for multiple periods at once
     */
    public function getMultiPeriodSessionStats(): array
    {
        return $this->getCached('dashboard.session_stats', function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $dayBeforeYesterday = now()->subDays(2)->startOfDay();
            $thisWeekStart = now()->startOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $thisMonthStart = now()->startOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();

            $todayStats = $this->getSessionSuccessFailureBreakdown($today, now()->endOfDay());
            $yesterdayStats = $this->getSessionSuccessFailureBreakdown($yesterday, now()->subDay()->endOfDay());
            $dayBeforeYesterdayStats = $this->getSessionSuccessFailureBreakdown(
                $dayBeforeYesterday,
                now()->subDays(2)->endOfDay()
            );

            // Calculate comparison between yesterday and day before yesterday
            $comparison = $this->calculateDayComparison($yesterdayStats, $dayBeforeYesterdayStats);

            return [
                'today' => $todayStats,
                'yesterday' => array_merge($yesterdayStats, [
                    'comparison_with_day_before' => $comparison
                ]),
                'day_before_yesterday' => $dayBeforeYesterdayStats,
                'this_week' => $this->getSessionSuccessFailureBreakdown($thisWeekStart, now()->endOfWeek()),
                'last_week' => $this->getSessionSuccessFailureBreakdown($lastWeekStart, now()->subWeek()->endOfWeek()),
                'this_month' => $this->getSessionSuccessFailureBreakdown($thisMonthStart, now()->endOfMonth()),
                'last_month' => $this->getSessionSuccessFailureBreakdown($lastMonthStart, now()->subMonth()->endOfMonth()),
                'all_time' => $this->getSessionSuccessFailureBreakdown(
                    Carbon::createFromDate(2020, 1, 1),
                    now()->endOfDay()
                ),
            ];
        });
    }

    /**
     * Get session stats for a month range (for CSV export)
     * Returns array of monthly data with: total_sessions, chargeable_sessions, successful_sessions, teacher_no_show
     * 
     * @param string $fromMonth Format: YYYY-MM
     * @param string $toMonth Format: YYYY-MM
     * @return array
     */
    public function getSessionStatsForMonthRange(string $fromMonth, string $toMonth): array
    {
        $result = [];
        
        // Parse the months
        $startDate = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth();
        
        // Iterate through each month
        $currentMonth = $startDate->copy();
        while ($currentMonth->lte($endDate)) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            // Convert to UTC for database queries
            $monthStartUtc = $monthStart->copy()->subHours(7);
            $monthEndUtc = $monthEnd->copy()->subHours(7);
            
            // Base query with speakwell subjects filter
            $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$monthStartUtc, $monthEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds());
            
            // Phase 199: Total sessions (status 2, 3, 4 only - exclude unscheduled status=1)
            $totalSessions = (clone $baseQuery)->whereIn('ordles_status', [
                OrderLesson::STATUS_SCHEDULED,
                OrderLesson::STATUS_COMPLETED,
                OrderLesson::STATUS_CANCELLED,
            ])->count();
            
            // Completed sessions (status = 3)
            $completedSessions = (clone $baseQuery)->completed()->count();
            
            // Successful sessions: completed (status=3) AND acceptance_code = 12
            $successfulSessions = OrderLesson::whereBetween('ordles_lesson_starttime', [$monthStartUtc, $monthEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) {
                    $q->where('ole_acceptance_code', 12);
                })
                ->count();
            
            // Chargeable sessions: completed with acceptance codes that charge the student (100% or 50%)
            // Based on acceptance code definitions, chargeable = student_fee_percent > 0
            // Codes that charge students: 4,5,6,7,8,9,10,11,12,16,17
            $chargeableCodes = [4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17];
            $chargeableSessions = OrderLesson::whereBetween('ordles_lesson_starttime', [$monthStartUtc, $monthEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) use ($chargeableCodes) {
                    $q->whereIn('ole_acceptance_code', $chargeableCodes);
                })
                ->count();
            
            // Teacher no-show: completed sessions where teacher didn't join
            // Consistent with dashboard display in getSessionSuccessFailureBreakdown()
            // Definition: ordles_status = 3 AND ole_teacher_first_join IS NULL
            $teacherNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$monthStartUtc, $monthEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) {
                    $q->whereNull('ole_teacher_first_join');
                })
                ->count();
            
            $result[] = [
                'month' => $currentMonth->format('Y-m'),
                'month_label' => $currentMonth->format('m/Y'),
                'total_sessions' => $totalSessions,
                'chargeable_sessions' => $chargeableSessions,
                'successful_sessions' => $successfulSessions,
                'teacher_no_show' => $teacherNoShow,
            ];
            
            $currentMonth->addMonth();
        }
        
        return $result;
    }

    /**
     * Get session stats for a date range (for daily CSV export)
     * Returns array of daily data with: total_sessions, chargeable_sessions, successful_sessions, teacher_no_show
     * 
     * @param string $fromDate Format: YYYY-MM-DD
     * @param string $toDate Format: YYYY-MM-DD
     * @return array
     */
    public function getSessionStatsForDateRange(string $fromDate, string $toDate): array
    {
        $result = [];
        
        // Parse the dates
        $startDate = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $toDate)->startOfDay();
        
        // Iterate through each day
        $currentDay = $startDate->copy();
        while ($currentDay->lte($endDate)) {
            $dayStart = $currentDay->copy()->startOfDay();
            $dayEnd = $currentDay->copy()->endOfDay();
            
            // Convert Vietnam local time to UTC for database queries
            // Database stores times in UTC, Vietnam is UTC+7
            // So 2026-01-11 00:00:00 Vietnam = 2026-01-10 17:00:00 UTC
            $dayStartUtc = $dayStart->copy()->subHours(7);
            $dayEndUtc = $dayEnd->copy()->subHours(7);
            
            // Base query with speakwell subjects filter
            $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayStartUtc, $dayEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds());
            
            // Phase 199: Total sessions (status 2, 3, 4 only - exclude unscheduled status=1)
            $totalSessions = (clone $baseQuery)->whereIn('ordles_status', [
                OrderLesson::STATUS_SCHEDULED,
                OrderLesson::STATUS_COMPLETED,
                OrderLesson::STATUS_CANCELLED,
            ])->count();
            
            // Completed sessions (status = 3)
            $completedSessions = (clone $baseQuery)->completed()->count();
            
            // Successful sessions: completed (status=3) AND acceptance_code = 12
            $successfulSessions = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayStartUtc, $dayEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) {
                    $q->where('ole_acceptance_code', 12);
                })
                ->count();
            
            // Chargeable sessions: completed with acceptance codes that charge the student (100% or 50%)
            // Based on acceptance code definitions, chargeable = student_fee_percent > 0
            // Codes that charge students: 4,5,6,7,8,9,10,11,12,16,17
            $chargeableCodes = [4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17];
            $chargeableSessions = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayStartUtc, $dayEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) use ($chargeableCodes) {
                    $q->whereIn('ole_acceptance_code', $chargeableCodes);
                })
                ->count();
            
            // Teacher no-show: completed sessions where teacher didn't join
            // Definition: ordles_status = 3 AND ole_teacher_first_join IS NULL
            $teacherNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$dayStartUtc, $dayEndUtc])
                ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
                ->completed()
                ->whereHas('extras', function($q) {
                    $q->whereNull('ole_teacher_first_join');
                })
                ->count();
            
            $result[] = [
                'date' => $currentDay->format('Y-m-d'),
                'date_label' => $currentDay->format('d/m/Y'),
                'total_sessions' => $totalSessions,
                'chargeable_sessions' => $chargeableSessions,
                'successful_sessions' => $successfulSessions,
                'teacher_no_show' => $teacherNoShow,
            ];
            
            $currentDay->addDay();
        }
        
        return $result;
    }

    /**
     * Calculate comparison between two day stats
     */
    private function calculateDayComparison(array $current, array $previous): array
    {
        $calcChange = function($currentVal, $previousVal) {
            if ($previousVal == 0) {
                return $currentVal > 0 ? ['value' => 100, 'direction' => 'up'] : ['value' => 0, 'direction' => 'same'];
            }
            $change = (($currentVal - $previousVal) / $previousVal) * 100;
            return [
                'value' => abs(round($change, 1)),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
                'diff' => $currentVal - $previousVal
            ];
        };

        return [
            'total' => $calcChange($current['total'] ?? 0, $previous['total'] ?? 0),
            'success' => [
                'count' => $calcChange($current['success']['count'] ?? 0, $previous['success']['count'] ?? 0),
                'rate' => $calcChange($current['success']['rate'] ?? 0, $previous['success']['rate'] ?? 0),
            ],
            'failure' => [
                'count' => $calcChange($current['failure']['count'] ?? 0, $previous['failure']['count'] ?? 0),
            ],
        ];
    }

    /**
     * Get acceptance code breakdown statistics
     * Returns count for each acceptance code with details
     */
    public function getAcceptanceCodeBreakdown(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (!$start) {
            $start = now()->startOfDay();
        }
        if (!$end) {
            $end = now()->endOfDay();
        }

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Get total completed lessons from tbl_order_lessons directly (accurate count)
        // This is the same method used in getSessionSuccessFailureBreakdown
        $totalCompleted = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->count();

        // Get acceptance code breakdown for completed lessons that have acceptance code records
        $breakdown = OrderLessonExtra::select('ole_acceptance_code', DB::raw('COUNT(*) as count'))
            ->join('tbl_order_lessons', 'tbl_order_lessons_extras.ole_ordles_id', '=', 'tbl_order_lessons.ordles_id')
            ->whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->groupBy('ole_acceptance_code')
            ->orderBy('ole_acceptance_code')
            ->pluck('count', 'ole_acceptance_code')
            ->toArray();

        // Build result with labels for each code
        $result = [];
        $totalSuccess = $breakdown[OrderLessonExtra::ACCEPTANCE_SUCCESS] ?? 0;
        $totalFailure = $totalCompleted - $totalSuccess;

        foreach (OrderLessonExtra::ACCEPTANCE_CODES as $code => $info) {
            $count = $breakdown[$code] ?? 0;
            $result[] = [
                'code' => $code,
                'label' => OrderLessonExtra::getAcceptanceCodeLabel($code),
                'session' => $info['session'],
                'teacher' => $info['teacher'],
                'student' => $info['student'],
                'is_success' => $code === OrderLessonExtra::ACCEPTANCE_SUCCESS,
                'count' => $count,
                'rate' => $totalCompleted > 0 ? round(($count / $totalCompleted) * 100, 2) : 0,
            ];
        }

        // Count penalized sessions (codes 1,2,3,6 from completed + codes 14,17 from cancelled)
        $penaltyCompletedCodes = array_intersect(self::PENALTY_CODES, range(1, 12));
        $penaltyCancelledCodes = array_intersect(self::PENALTY_CODES, range(13, 17));

        $penalizedSessions = 0;
        $penalizedTeacherIds = collect();

        // Penalty codes for completed sessions (1, 2, 3, 6)
        if (!empty($penaltyCompletedCodes)) {
            $completedPenalty = OrderLessonExtra::join('tbl_order_lessons', 'tbl_order_lessons_extras.ole_ordles_id', '=', 'tbl_order_lessons.ordles_id')
                ->whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
                ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
                ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
                ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', $penaltyCompletedCodes);

            $penalizedSessions += (clone $completedPenalty)->count();
            $penalizedTeacherIds = $penalizedTeacherIds->merge(
                (clone $completedPenalty)->pluck('tbl_order_lessons.ordles_teacher_id')
            );
        }

        // Penalty codes for cancelled sessions (14, 17)
        if (!empty($penaltyCancelledCodes)) {
            $cancelledPenalty = OrderLessonExtra::join('tbl_order_lessons', 'tbl_order_lessons_extras.ole_ordles_id', '=', 'tbl_order_lessons.ordles_id')
                ->whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
                ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
                ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_CANCELLED)
                ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', $penaltyCancelledCodes);

            $penalizedSessions += (clone $cancelledPenalty)->count();
            $penalizedTeacherIds = $penalizedTeacherIds->merge(
                (clone $cancelledPenalty)->pluck('tbl_order_lessons.ordles_teacher_id')
            );
        }

        $penalizedTeachers = $penalizedTeacherIds->unique()->count();

        return [
            'total_completed' => $totalCompleted,
            'total_success' => $totalSuccess,
            'total_failure' => $totalFailure,
            'success_rate' => $totalCompleted > 0 ? round(($totalSuccess / $totalCompleted) * 100, 2) : 0,
            'failure_rate' => $totalCompleted > 0 ? round(($totalFailure / $totalCompleted) * 100, 2) : 0,
            'penalized_sessions' => $penalizedSessions,
            'penalized_teachers' => $penalizedTeachers,
            'codes' => $result,
        ];
    }

    /**
     * Get acceptance code list with labels (for reference display)
     */
    public function getAcceptanceCodesList(): array
    {
        return $this->getCached('dashboard.acceptance_codes_list', function () {
            return OrderLessonExtra::getAllAcceptanceCodesWithLabels();
        });
    }

    /**
     * Get detailed list of penalized teachers for a specific period
     * Penalty codes: 1, 2, 3, 6 (completed sessions) and 14, 17 (cancelled sessions)
     * 
     * @param string $period Period identifier (today, yesterday, day_before_yesterday, this_week, last_week, this_month, last_month)
     * @return array List of penalized teacher sessions with details
     */
    public function getPenalizedTeachersDetails(string $period): array
    {
        // Calculate date range based on period
        $nowVn = now();
        switch ($period) {
            case 'today':
                $startUtc = $nowVn->copy()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->endOfDay()->subHours(7);
                break;
            case 'yesterday':
                $startUtc = $nowVn->copy()->subDay()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDay()->endOfDay()->subHours(7);
                break;
            case 'day_before_yesterday':
                $startUtc = $nowVn->copy()->subDays(2)->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDays(2)->endOfDay()->subHours(7);
                break;
            case 'this_week':
                $startUtc = $nowVn->copy()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->endOfWeek()->subHours(7);
                break;
            case 'last_week':
                $startUtc = $nowVn->copy()->subWeek()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->subWeek()->endOfWeek()->subHours(7);
                break;
            case 'this_month':
                $startUtc = $nowVn->copy()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->endOfMonth()->subHours(7);
                break;
            case 'last_month':
                $startUtc = $nowVn->copy()->subMonth()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->subMonth()->endOfMonth()->subHours(7);
                break;
            default:
                return [];
        }

        $penaltyCompletedCodes = array_intersect(self::PENALTY_CODES, range(1, 12));
        $penaltyCancelledCodes = array_intersect(self::PENALTY_CODES, range(13, 17));

        $lessons = collect();

        // Get penalized lessons from completed sessions (codes 1, 2, 3, 6)
        if (!empty($penaltyCompletedCodes)) {
            $completedLessons = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
                ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
                ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
                ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
                ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', $penaltyCompletedCodes)
                ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
                ->select(
                    'tbl_order_lessons.ordles_id',
                    'tbl_order_lessons.ordles_teacher_id',
                    'tbl_order_lessons.ordles_lesson_starttime',
                    'tbl_order_lessons.ordles_duration',
                    'tbl_order_lessons.ordles_status',
                    'tbl_order_lessons_extras.ole_acceptance_code',
                    'tbl_orders.order_user_id'
                )
                ->orderBy('tbl_order_lessons.ordles_lesson_starttime', 'desc')
                ->get();

            $lessons = $lessons->merge($completedLessons);
        }

        // Get penalized lessons from cancelled sessions (codes 14, 17)
        if (!empty($penaltyCancelledCodes)) {
            $cancelledLessons = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
                ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
                ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_CANCELLED)
                ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
                ->whereIn('tbl_order_lessons_extras.ole_acceptance_code', $penaltyCancelledCodes)
                ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
                ->select(
                    'tbl_order_lessons.ordles_id',
                    'tbl_order_lessons.ordles_teacher_id',
                    'tbl_order_lessons.ordles_lesson_starttime',
                    'tbl_order_lessons.ordles_duration',
                    'tbl_order_lessons.ordles_status',
                    'tbl_order_lessons_extras.ole_acceptance_code',
                    'tbl_orders.order_user_id'
                )
                ->orderBy('tbl_order_lessons.ordles_lesson_starttime', 'desc')
                ->get();

            $lessons = $lessons->merge($cancelledLessons);
        }

        // Sort by lesson start time descending
        $lessons = $lessons->sortByDesc('ordles_lesson_starttime');

        $result = [];
        foreach ($lessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);

            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $code = $lesson->ole_acceptance_code;

            $result[] = [
                'lesson_id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'teacher_email' => $teacher->user_email ?? '',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'lesson_date' => $startTimeVn->format('d/m/Y'),
                'lesson_time' => $startTimeVn->format('H:i'),
                'duration' => $lesson->ordles_duration,
                'acceptance_code' => $code,
                'acceptance_label' => OrderLessonExtra::getAcceptanceCodeLabel($code),
                'session_status' => $lesson->ordles_status == OrderLesson::STATUS_COMPLETED ? 'Hoàn thành' : 'Đã hủy',
            ];
        }

        return $result;
    }

    /**
     * Get statistics for students who have scheduled lessons but have never logged in
     * These are users with user_lastseen IS NULL AND no entry in tbl_user_auth_token
     * Phase 29: Added check for tbl_user_auth_token to accurately determine login status
     * 
     * @param Carbon|null $start Start of date range for lessons
     * @param Carbon|null $end End of date range for lessons
     * @return array Statistics about never-logged-in students with scheduled lessons
     */
    public function getNeverLoggedInStudentsWithLessons(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (!$start) {
            $start = now()->startOfDay();
        }
        if (!$end) {
            $end = now()->endOfDay();
        }

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Get user IDs who have lessons scheduled in the date range
        // Join with tbl_orders to get order_user_id (the student)
        $userIdsWithLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->pluck('tbl_orders.order_user_id')
            ->unique()
            ->toArray();

        if (empty($userIdsWithLessons)) {
            return [
                'count' => 0,
                'total_students_with_lessons' => 0,
                'rate' => 0,
            ];
        }

        // Phase 29: Get user IDs who have auth tokens (these users have logged in before)
        // Check the tbl_user_auth_token table for login evidence
        $userIdsWithAuthToken = DB::connection('mysql')
            ->table('tbl_user_auth_token')
            ->whereIn('usrtok_user_id', $userIdsWithLessons)
            ->pluck('usrtok_user_id')
            ->unique()
            ->toArray();

        // Count students who have never logged in:
        // user_lastseen IS NULL AND no entry in tbl_user_auth_token
        $neverLoggedInCount = User::whereIn('user_id', $userIdsWithLessons)
            ->whereNull('user_lastseen')
            ->whereNull('user_deleted')
            ->whereNotIn('user_id', $userIdsWithAuthToken) // Phase 29: Exclude users with auth tokens
            ->count();

        $totalStudentsWithLessons = count(array_unique($userIdsWithLessons));

        // Count students with multiple lessons (2+) in this period
        $studentsWithMultipleLessons = $this->getStudentsWithMultipleLessonsCount($start, $end);

        return [
            'count' => $neverLoggedInCount,
            'total_students_with_lessons' => $totalStudentsWithLessons,
            'students_with_multiple_lessons' => $studentsWithMultipleLessons,
            'rate' => $totalStudentsWithLessons > 0 
                ? round(($neverLoggedInCount / $totalStudentsWithLessons) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Get never-logged-in students statistics for multiple periods
     * Returns stats for: today, yesterday, day before yesterday, this week, this month
     * 
     * @return array Multi-period statistics
     */
    public function getMultiPeriodNeverLoggedInStats(): array
    {
        return $this->getCached('dashboard.never_logged_in_stats', function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $dayBeforeYesterday = now()->subDays(2)->startOfDay();
            $thisWeekStart = now()->startOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $thisMonthStart = now()->startOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();

            return [
                'today' => $this->getNeverLoggedInStudentsWithLessons($today, now()->endOfDay()),
                'yesterday' => $this->getNeverLoggedInStudentsWithLessons(
                    $yesterday,
                    now()->subDay()->endOfDay()
                ),
                'day_before_yesterday' => $this->getNeverLoggedInStudentsWithLessons(
                    $dayBeforeYesterday,
                    now()->subDays(2)->endOfDay()
                ),
                'this_week' => $this->getNeverLoggedInStudentsWithLessons($thisWeekStart, now()->endOfWeek()),
                'last_week' => $this->getNeverLoggedInStudentsWithLessons($lastWeekStart, now()->subWeek()->endOfWeek()),
                'this_month' => $this->getNeverLoggedInStudentsWithLessons($thisMonthStart, now()->endOfMonth()),
                'last_month' => $this->getNeverLoggedInStudentsWithLessons($lastMonthStart, now()->subMonth()->endOfMonth()),
            ];
        });
    }

    /**
     * Get acceptance code breakdown for multiple periods
     */
    public function getMultiPeriodAcceptanceCodeStats(): array
    {
        return $this->getCached('dashboard.acceptance_code_stats', function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $dayBeforeYesterday = now()->subDays(2)->startOfDay();
            $thisWeekStart = now()->startOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $thisMonthStart = now()->startOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();

            return [
                'today' => $this->getAcceptanceCodeBreakdown($today, now()->endOfDay()),
                'yesterday' => $this->getAcceptanceCodeBreakdown(
                    $yesterday,
                    now()->subDay()->endOfDay()
                ),
                'day_before_yesterday' => $this->getAcceptanceCodeBreakdown(
                    $dayBeforeYesterday,
                    now()->subDays(2)->endOfDay()
                ),
                'this_week' => $this->getAcceptanceCodeBreakdown($thisWeekStart, now()->endOfWeek()),
                'last_week' => $this->getAcceptanceCodeBreakdown($lastWeekStart, now()->subWeek()->endOfWeek()),
                'this_month' => $this->getAcceptanceCodeBreakdown($thisMonthStart, now()->endOfMonth()),
                'last_month' => $this->getAcceptanceCodeBreakdown($lastMonthStart, now()->subMonth()->endOfMonth()),
            ];
        });
    }

    /**
     * Acceptance codes categorized by billing type
     * 
     * Chargeable codes (tính phí học sinh - positive):
     * - Codes 4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17
     * 
     * Compensate codes (phải bù buổi cho học sinh - negative):
     * - Codes 1, 2, 3, 13, 14, 15
     */
    public const CHARGEABLE_CODES = [4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17];
    public const COMPENSATE_CODES = [1, 2, 3, 13, 14, 15];

    /**
     * Get acceptance code map statistics grouped by billing type
     * Returns chargeable (positive) vs compensate (negative) counts for each period
     * 
     * @param Carbon|null $start Start date
     * @param Carbon|null $end End date
     * @return array Map statistics with chargeable and compensate counts
     */
    public function getAcceptanceCodeMapForPeriod(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (!$start) {
            $start = now()->startOfDay();
        }
        if (!$end) {
            $end = now()->endOfDay();
        }

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Get all completed lessons with acceptance codes
        $breakdown = OrderLessonExtra::select('ole_acceptance_code', DB::raw('COUNT(*) as count'))
            ->join('tbl_order_lessons', 'tbl_order_lessons_extras.ole_ordles_id', '=', 'tbl_order_lessons.ordles_id')
            ->whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->groupBy('ole_acceptance_code')
            ->pluck('count', 'ole_acceptance_code')
            ->toArray();

        // Calculate totals for each category
        $chargeableTotal = 0;
        $compensateTotal = 0;
        $chargeableBreakdown = [];
        $compensateBreakdown = [];

        foreach (self::CHARGEABLE_CODES as $code) {
            $count = $breakdown[$code] ?? 0;
            $chargeableTotal += $count;
            if ($count > 0) {
                $chargeableBreakdown[$code] = [
                    'code' => $code,
                    'count' => $count,
                    'label' => OrderLessonExtra::getAcceptanceCodeLabel($code),
                ];
            }
        }

        foreach (self::COMPENSATE_CODES as $code) {
            $count = $breakdown[$code] ?? 0;
            $compensateTotal += $count;
            if ($count > 0) {
                $compensateBreakdown[$code] = [
                    'code' => $code,
                    'count' => $count,
                    'label' => OrderLessonExtra::getAcceptanceCodeLabel($code),
                ];
            }
        }

        $total = $chargeableTotal + $compensateTotal;

        return [
            'total' => $total,
            'chargeable' => [
                'total' => $chargeableTotal,
                'rate' => $total > 0 ? round(($chargeableTotal / $total) * 100, 1) : 0,
                'codes' => self::CHARGEABLE_CODES,
                'breakdown' => $chargeableBreakdown,
            ],
            'compensate' => [
                'total' => $compensateTotal,
                'rate' => $total > 0 ? round(($compensateTotal / $total) * 100, 1) : 0,
                'codes' => self::COMPENSATE_CODES,
                'breakdown' => $compensateBreakdown,
            ],
        ];
    }

    /**
     * Get acceptance code map for multiple periods
     * Groups completed sessions into chargeable (positive) vs compensate (negative)
     * 
     * @return array Multi-period map statistics
     */
    public function getMultiPeriodAcceptanceCodeMapStats(): array
    {
        return $this->getCached('dashboard.acceptance_code_map_stats', function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $dayBeforeYesterday = now()->subDays(2)->startOfDay();
            $thisWeekStart = now()->startOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $thisMonthStart = now()->startOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();

            return [
                'today' => $this->getAcceptanceCodeMapForPeriod($today, now()->endOfDay()),
                'yesterday' => $this->getAcceptanceCodeMapForPeriod(
                    $yesterday,
                    now()->subDay()->endOfDay()
                ),
                'day_before_yesterday' => $this->getAcceptanceCodeMapForPeriod(
                    $dayBeforeYesterday,
                    now()->subDays(2)->endOfDay()
                ),
                'this_week' => $this->getAcceptanceCodeMapForPeriod($thisWeekStart, now()->endOfWeek()),
                'last_week' => $this->getAcceptanceCodeMapForPeriod($lastWeekStart, now()->subWeek()->endOfWeek()),
                'this_month' => $this->getAcceptanceCodeMapForPeriod($thisMonthStart, now()->endOfMonth()),
                'last_month' => $this->getAcceptanceCodeMapForPeriod($lastMonthStart, now()->subMonth()->endOfMonth()),
            ];
        });
    }

    /**
     * Get detailed list of never-logged-in students with lessons for a specific period
     * Returns user info: username, name, email, phone
     * Phase 29: Also checks tbl_user_auth_token to exclude users who have logged in
     * 
     * @param string $period Period key: today, yesterday, day_before_yesterday, this_week, this_month
     * @return array List of never-logged-in students
     */
    public function getNeverLoggedInStudentsDetail(string $period): array
    {
        $dates = $this->getPeriodDatesForNeverLoggedIn($period);
        $start = $dates['start'];
        $end = $dates['end'];

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Get user IDs who have lessons scheduled in the date range
        $userIdsWithLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->pluck('tbl_orders.order_user_id')
            ->unique()
            ->toArray();

        if (empty($userIdsWithLessons)) {
            return [];
        }

        // Phase 29: Get user IDs who have auth tokens (these users have logged in before)
        $userIdsWithAuthToken = DB::connection('mysql')
            ->table('tbl_user_auth_token')
            ->whereIn('usrtok_user_id', $userIdsWithLessons)
            ->pluck('usrtok_user_id')
            ->unique()
            ->toArray();

        // Get students who have never logged in with their details
        // Phase 29: Also exclude users who have auth tokens
        return User::whereIn('user_id', $userIdsWithLessons)
            ->whereNull('user_lastseen')
            ->whereNull('user_deleted')
            ->whereNotIn('user_id', $userIdsWithAuthToken) // Phase 29: Exclude users with auth tokens
            ->select('user_id', 'user_username', 'user_first_name', 'user_last_name', 'user_email')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'username' => $user->user_username ?? '',
                    'name' => trim(($user->user_first_name ?? '') . ' ' . ($user->user_last_name ?? '')),
                    'email' => $user->user_email ?? '',
                    'phone' => '', // Phone column doesn't exist in tbl_users
                ];
            })
            ->toArray();
    }

    /**
     * Get students with multiple lessons (2+) in a day
     * 
     * @param string $period Period key: today, yesterday, day_before_yesterday, this_week, this_month
     * @return array Stats and list of students with multiple lessons
     */
    public function getStudentsWithMultipleLessons(string $period): array
    {
        $dates = $this->getPeriodDatesForNeverLoggedIn($period);
        $start = $dates['start'];
        $end = $dates['end'];

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // For weekly/monthly periods, we need to check day by day
        // For daily periods (today, yesterday, day_before), we check the specific day
        
        // Get lesson counts per user per day
        $lessonCounts = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_SCHEDULED)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_orders.order_user_id',
                DB::raw('DATE(tbl_order_lessons.ordles_lesson_starttime) as lesson_date'),
                DB::raw('COUNT(*) as lesson_count')
            )
            ->groupBy('tbl_orders.order_user_id', 'lesson_date')
            ->having('lesson_count', '>=', 2)
            ->get();

        if ($lessonCounts->isEmpty()) {
            return [
                'count' => 0,
                'students' => [],
            ];
        }

        // Get unique user IDs
        $userIds = $lessonCounts->pluck('order_user_id')->unique()->toArray();

        // Get user details
        $users = User::whereIn('user_id', $userIds)
            ->whereNull('user_deleted')
            ->select('user_id', 'user_username', 'user_first_name', 'user_last_name', 'user_email')
            ->get()
            ->keyBy('user_id');

        // Get detailed lesson time slots for each user on each day
        // This helps show the specific time slots for each lesson
        $lessonDetails = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_SCHEDULED)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->whereIn('tbl_orders.order_user_id', $userIds)
            ->select(
                'tbl_orders.order_user_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                DB::raw('DATE(tbl_order_lessons.ordles_lesson_starttime) as lesson_date')
            )
            ->orderBy('tbl_order_lessons.ordles_lesson_starttime')
            ->get()
            ->groupBy(function ($item) {
                return $item->order_user_id . '_' . $item->lesson_date;
            });

        // Build result with lesson counts and time slots
        $students = [];
        foreach ($lessonCounts as $lc) {
            $userId = $lc->order_user_id;
            $user = $users->get($userId);
            if (!$user) continue;

            $key = $userId . '_' . $lc->lesson_date;
            
            // Get the time slots for this user on this day
            $timeSlots = [];
            if (isset($lessonDetails[$key])) {
                foreach ($lessonDetails[$key] as $lesson) {
                    $startTime = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7); // Convert to Vietnam timezone
                    $duration = $lesson->ordles_duration ?? 25;
                    $endTime = $startTime->copy()->addMinutes($duration);
                    $timeSlots[] = $startTime->format('H:i') . '-' . $endTime->format('H:i');
                }
            }
            
            $students[$key] = [
                'user_id' => $userId,
                'username' => $user->user_username ?? '',
                'name' => trim(($user->user_first_name ?? '') . ' ' . ($user->user_last_name ?? '')),
                'email' => $user->user_email ?? '',
                'phone' => '', // Phone column doesn't exist in tbl_users
                'lesson_date' => Carbon::parse($lc->lesson_date)->format('d/m/Y'),
                'lesson_count' => $lc->lesson_count,
                'time_slots' => $timeSlots,
                'time_slots_display' => implode(', ', $timeSlots),
            ];
        }

        // Sort by lesson_count desc
        usort($students, function ($a, $b) {
            return $b['lesson_count'] - $a['lesson_count'];
        });

        return [
            'count' => count($userIds),
            'total_records' => count($students),
            'students' => array_values($students),
        ];
    }

    /**
     * Get period dates for never-logged-in stats
     */
    private function getPeriodDatesForNeverLoggedIn(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'day_before_yesterday' => [
                'start' => now()->subDays(2)->startOfDay(),
                'end' => now()->subDays(2)->endOfDay(),
            ],
            'this_week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'last_week' => [
                'start' => now()->subWeek()->startOfWeek(),
                'end' => now()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            default => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    /**
     * Get count of students with multiple lessons for a date range
     * 
     * @param Carbon|null $start Start of date range
     * @param Carbon|null $end End of date range
     * @return int Count of unique students with 2+ lessons
     */
    private function getStudentsWithMultipleLessonsCount(?Carbon $start = null, ?Carbon $end = null): int
    {
        if (!$start) {
            $start = now()->startOfDay();
        }
        if (!$end) {
            $end = now()->endOfDay();
        }

        // Convert Vietnam timezone to UTC for database query
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);

        // Get lesson counts per user per day, filter for 2+ lessons
        $userIds = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_SCHEDULED)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_orders.order_user_id',
                DB::raw('DATE(tbl_order_lessons.ordles_lesson_starttime) as lesson_date'),
                DB::raw('COUNT(*) as lesson_count')
            )
            ->groupBy('tbl_orders.order_user_id', 'lesson_date')
            ->having('lesson_count', '>=', 2)
            ->pluck('order_user_id')
            ->unique()
            ->count();

        return $userIds;
    }

    // ========================================
    // Real-time Lesson Status (Ongoing, Upcoming, Remaining)
    // ========================================

    /**
     * Get lessons that are currently ongoing (happening right now)
     * A lesson is ongoing if: now() is between start_time and start_time + duration
     * Only for scheduled or completed lessons with Speakwell subjects
     * 
     * @return array Count and list of ongoing lessons
     */
    public function getOngoingLessons(): array
    {
        // Current time in UTC (database stores in UTC, which is Vietnam time - 7 hours)
        $nowUtc = now()->subHours(7);
        $nowVn = now();
        $currentHour = (int) $nowVn->format('H');
        
        // Get lessons where: start_time <= now AND start_time + duration >= now
        // Only scheduled (2) status lessons are truly "ongoing" - completed (3) already ended
        $lessons = OrderLesson::whereIn('ordles_status', [OrderLesson::STATUS_SCHEDULED])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->whereDate('ordles_lesson_starttime', $nowUtc->toDateString())
            ->where('ordles_lesson_starttime', '<=', $nowUtc)
            ->whereRaw('DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) >= ?', [$nowUtc])
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_order_lessons.ordles_status',
                'tbl_orders.order_user_id'
            )
            ->get();

        $result = [];
        $uniqueStudents = [];
        foreach ($lessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $endTimeVn = $startTimeVn->copy()->addMinutes($lesson->ordles_duration);
            
            // Track unique students
            $uniqueStudents[$lesson->order_user_id] = true;
            
            $result[] = [
                'id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'student_email' => $student->user_email ?? '',
                'start_time' => $startTimeVn->format('H:i'),
                'end_time' => $endTimeVn->format('H:i'),
                'time_slot' => $startTimeVn->format('H:i') . ' - ' . $endTimeVn->format('H:i'),
                'duration' => $lesson->ordles_duration,
                'status' => OrderLesson::getStatusLabel($lesson->ordles_status),
            ];
        }

        // Get stats for the current hour slot (all lessons in this hour, not just ongoing)
        $hourStartUtc = now()->startOfHour()->subHours(7);
        $hourEndUtc = now()->endOfHour()->subHours(7);
        
        $hourStats = $this->getTimeSlotStats($hourStartUtc, $hourEndUtc);

        return [
            'count' => count($result),
            'unique_students' => count($uniqueStudents),
            'lessons' => $result,
            // Summary stats for the current time slot
            'slot_stats' => $hourStats,
        ];
    }

    /**
     * Get stats for a specific time slot (used by ongoing lessons)
     * Returns: total, unique_students, success, failed, cancelled
     */
    private function getTimeSlotStats(Carbon $startUtc, Carbon $endUtc): array
    {
        $lessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->leftJoin('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_status',
                'tbl_orders.order_user_id',
                'tbl_order_lessons_extras.ole_acceptance_code'
            )
            ->get();

        $uniqueStudents = [];
        $total = 0;
        $success = 0;
        $failed = 0;
        $cancelled = 0;

        foreach ($lessons as $lesson) {
            $total++;
            $uniqueStudents[$lesson->order_user_id] = true;

            switch ($lesson->ordles_status) {
                case OrderLesson::STATUS_COMPLETED:
                    if ($lesson->ole_acceptance_code == OrderLessonExtra::ACCEPTANCE_SUCCESS) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;
                case OrderLesson::STATUS_CANCELLED:
                    $cancelled++;
                    break;
            }
        }

        return [
            'total' => $total,
            'unique_students' => count($uniqueStudents),
            'success' => $success,
            'failed' => $failed,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Get lessons that are upcoming (starting within the next time slot)
     * Next time slot = within the next 60 minutes
     * Only scheduled lessons that haven't started yet
     * 
     * @param int $minutes Number of minutes ahead to look (default 60)
     * @return array Count and list of upcoming lessons
     */
    public function getUpcomingLessons(int $minutes = 60): array
    {
        // Current time in UTC
        $nowUtc = now()->subHours(7);
        $futureUtc = $nowUtc->copy()->addMinutes($minutes);
        
        // Get scheduled lessons that start between now and now + $minutes
        $scheduledLessons = OrderLesson::where('ordles_status', OrderLesson::STATUS_SCHEDULED)
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_lesson_starttime', '>', $nowUtc)
            ->where('ordles_lesson_starttime', '<=', $futureUtc)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_orders.order_user_id'
            )
            ->orderBy('ordles_lesson_starttime')
            ->get();

        // Also get cancelled lessons in this time period for the stats
        $cancelledCount = OrderLesson::where('ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_lesson_starttime', '>', $nowUtc)
            ->where('ordles_lesson_starttime', '<=', $futureUtc)
            ->count();

        $result = [];
        $uniqueStudents = [];
        foreach ($scheduledLessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $endTimeVn = $startTimeVn->copy()->addMinutes($lesson->ordles_duration);
            $minutesUntilStart = $nowUtc->diffInMinutes($lesson->ordles_lesson_starttime, false);
            
            // Track unique students
            $uniqueStudents[$lesson->order_user_id] = true;
            
            $result[] = [
                'id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'student_email' => $student->user_email ?? '',
                'start_time' => $startTimeVn->format('H:i'),
                'end_time' => $endTimeVn->format('H:i'),
                'time_slot' => $startTimeVn->format('H:i') . ' - ' . $endTimeVn->format('H:i'),
                'duration' => $lesson->ordles_duration,
                'minutes_until_start' => max(0, $minutesUntilStart),
            ];
        }

        return [
            'count' => count($result),
            'unique_students' => count($uniqueStudents),
            'cancelled' => $cancelledCount,
            'lessons' => $result,
        ];
    }

    /**
     * Get remaining lessons for today that haven't started yet
     * Only scheduled lessons for today with start time > now
     * 
     * @return array Count and breakdown by time slot
     */
    public function getRemainingLessonsToday(): array
    {
        // Current time in UTC
        $nowUtc = now()->subHours(7);
        $todayStartUtc = now()->startOfDay()->subHours(7);
        $todayEndUtc = now()->endOfDay()->subHours(7);
        
        // Get scheduled lessons for today that haven't started yet
        $lessons = OrderLesson::where('ordles_status', OrderLesson::STATUS_SCHEDULED)
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->whereBetween('ordles_lesson_starttime', [$todayStartUtc, $todayEndUtc])
            ->where('ordles_lesson_starttime', '>', $nowUtc)
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_orders.order_user_id'
            )
            ->orderBy('ordles_lesson_starttime')
            ->get();

        $result = [];
        $byHour = [];
        
        foreach ($lessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $endTimeVn = $startTimeVn->copy()->addMinutes($lesson->ordles_duration);
            $hour = $startTimeVn->format('H:00');
            
            // Group by hour
            if (!isset($byHour[$hour])) {
                $byHour[$hour] = 0;
            }
            $byHour[$hour]++;
            
            $result[] = [
                'id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'student_email' => $student->user_email ?? '',
                'start_time' => $startTimeVn->format('H:i'),
                'end_time' => $endTimeVn->format('H:i'),
                'time_slot' => $startTimeVn->format('H:i') . ' - ' . $endTimeVn->format('H:i'),
                'duration' => $lesson->ordles_duration,
            ];
        }

        // Sort by hour
        ksort($byHour);

        return [
            'count' => count($result),
            'by_hour' => $byHour,
            'lessons' => $result,
        ];
    }

    /**
     * Get heatmap data for today's time slots
     * Shows for each hour: student count, success count, failure count
     * 
     * @return array Heatmap data for 24-hour time slots
     */
    public function getTodayTimeSlotHeatmap(): array
    {
        // Today's date range in UTC
        $todayStartUtc = now()->startOfDay()->subHours(7);
        $todayEndUtc = now()->endOfDay()->subHours(7);
        
        // Current time in Vietnam timezone for marking current hour
        $nowVn = now();
        $currentHour = (int) $nowVn->format('H');
        
        // Initialize all 24 hours with zeros
        $heatmap = [];
        for ($h = 0; $h < 24; $h++) {
            $hourLabel = sprintf('%02d:00', $h);
            $heatmap[$hourLabel] = [
                'hour' => $h,
                'label' => $hourLabel,
                'total' => 0,
                'completed' => 0,
                'success' => 0,
                'failed' => 0,
                'cancelled' => 0,
                'scheduled' => 0,
                'unique_students' => 0,
                'is_past' => $h < $currentHour,
                'is_current' => $h === $currentHour,
                'is_future' => $h > $currentHour,
            ];
        }

        // Get all lessons for today grouped by hour
        $lessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$todayStartUtc, $todayEndUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->leftJoin('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_status',
                'tbl_orders.order_user_id',
                'tbl_order_lessons_extras.ole_acceptance_code'
            )
            ->get();

        // Group by hour and calculate stats
        $studentsByHour = [];
        foreach ($lessons as $lesson) {
            // Convert to Vietnam time and get hour
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $hour = (int) $startTimeVn->format('H');
            $hourLabel = sprintf('%02d:00', $hour);
            
            if (!isset($heatmap[$hourLabel])) continue;
            
            $heatmap[$hourLabel]['total']++;
            
            // Track unique students per hour
            if (!isset($studentsByHour[$hourLabel])) {
                $studentsByHour[$hourLabel] = [];
            }
            $studentsByHour[$hourLabel][$lesson->order_user_id] = true;
            
            // Status breakdown
            switch ($lesson->ordles_status) {
                case OrderLesson::STATUS_COMPLETED:
                    $heatmap[$hourLabel]['completed']++;
                    // Check acceptance code for success/failure
                    if ($lesson->ole_acceptance_code == OrderLessonExtra::ACCEPTANCE_SUCCESS) {
                        $heatmap[$hourLabel]['success']++;
                    } else {
                        $heatmap[$hourLabel]['failed']++;
                    }
                    break;
                case OrderLesson::STATUS_CANCELLED:
                    $heatmap[$hourLabel]['cancelled']++;
                    break;
                case OrderLesson::STATUS_SCHEDULED:
                    $heatmap[$hourLabel]['scheduled']++;
                    break;
            }
        }

        // Update unique student counts
        foreach ($studentsByHour as $hourLabel => $students) {
            $heatmap[$hourLabel]['unique_students'] = count($students);
        }

        // Calculate intensity levels for heatmap coloring (0-5 scale)
        // Ensure minimum intensity of 1 for any slot with at least 1 lesson
        $maxTotal = max(array_column($heatmap, 'total')) ?: 1;
        foreach ($heatmap as $hourLabel => &$data) {
            if ($data['total'] > 0) {
                // Calculate relative intensity (1-5 scale), minimum 1 for visibility
                $relativeIntensity = round(($data['total'] / $maxTotal) * 5);
                $data['intensity'] = max(1, $relativeIntensity);
            } else {
                $data['intensity'] = 0;
            }
            $data['success_rate'] = $data['completed'] > 0 
                ? round(($data['success'] / $data['completed']) * 100, 1) 
                : 0;
            $data['failure_rate'] = $data['completed'] > 0 
                ? round(($data['failed'] / $data['completed']) * 100, 1) 
                : 0;
        }

        return [
            'current_hour' => $currentHour,
            'max_lessons' => $maxTotal,
            'slots' => array_values($heatmap),
        ];
    }

    /**
     * Get heatmap data for yesterday's time slots
     * Shows for each hour: student count, success count, failure count
     * 
     * @return array Heatmap data for 24-hour time slots (yesterday)
     */
    public function getYesterdayTimeSlotHeatmap(): array
    {
        // Yesterday's date range in UTC
        $yesterdayStartUtc = now()->subDay()->startOfDay()->subHours(7);
        $yesterdayEndUtc = now()->subDay()->endOfDay()->subHours(7);
        
        // Initialize all 24 hours with zeros
        $heatmap = [];
        for ($h = 0; $h < 24; $h++) {
            $hourLabel = sprintf('%02d:00', $h);
            $heatmap[$hourLabel] = [
                'hour' => $h,
                'label' => $hourLabel,
                'total' => 0,
                'completed' => 0,
                'success' => 0,
                'failed' => 0,
                'cancelled' => 0,
                'scheduled' => 0,
                'unique_students' => 0,
                'is_past' => true, // All hours are past for yesterday
                'is_current' => false,
                'is_future' => false,
            ];
        }

        // Get all lessons for yesterday grouped by hour
        $lessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$yesterdayStartUtc, $yesterdayEndUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->leftJoin('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_status',
                'tbl_orders.order_user_id',
                'tbl_order_lessons_extras.ole_acceptance_code'
            )
            ->get();

        // Group by hour and calculate stats
        $studentsByHour = [];
        foreach ($lessons as $lesson) {
            // Convert to Vietnam time and get hour
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $hour = (int) $startTimeVn->format('H');
            $hourLabel = sprintf('%02d:00', $hour);
            
            if (!isset($heatmap[$hourLabel])) continue;
            
            $heatmap[$hourLabel]['total']++;
            
            // Track unique students per hour
            if (!isset($studentsByHour[$hourLabel])) {
                $studentsByHour[$hourLabel] = [];
            }
            $studentsByHour[$hourLabel][$lesson->order_user_id] = true;
            
            // Status breakdown
            switch ($lesson->ordles_status) {
                case OrderLesson::STATUS_COMPLETED:
                    $heatmap[$hourLabel]['completed']++;
                    // Check acceptance code for success/failure
                    if ($lesson->ole_acceptance_code == OrderLessonExtra::ACCEPTANCE_SUCCESS) {
                        $heatmap[$hourLabel]['success']++;
                    } else {
                        $heatmap[$hourLabel]['failed']++;
                    }
                    break;
                case OrderLesson::STATUS_CANCELLED:
                    $heatmap[$hourLabel]['cancelled']++;
                    break;
                case OrderLesson::STATUS_SCHEDULED:
                    $heatmap[$hourLabel]['scheduled']++;
                    break;
            }
        }

        // Update unique student counts
        foreach ($studentsByHour as $hourLabel => $students) {
            $heatmap[$hourLabel]['unique_students'] = count($students);
        }

        // Calculate intensity levels for heatmap coloring (0-5 scale)
        // Ensure minimum intensity of 1 for any slot with at least 1 lesson
        $maxTotal = max(array_column($heatmap, 'total')) ?: 1;
        foreach ($heatmap as $hourLabel => &$data) {
            if ($data['total'] > 0) {
                // Calculate relative intensity (1-5 scale), minimum 1 for visibility
                $relativeIntensity = round(($data['total'] / $maxTotal) * 5);
                $data['intensity'] = max(1, $relativeIntensity);
            } else {
                $data['intensity'] = 0;
            }
            $data['success_rate'] = $data['completed'] > 0 
                ? round(($data['success'] / $data['completed']) * 100, 1) 
                : 0;
            $data['failure_rate'] = $data['completed'] > 0 
                ? round(($data['failed'] / $data['completed']) * 100, 1) 
                : 0;
        }

        return [
            'date' => now()->subDay()->format('d/m/Y'),
            'max_lessons' => $maxTotal,
            'slots' => array_values($heatmap),
        ];
    }

    /**
     * Get real-time lesson status summary for dashboard
     * Combines ongoing, upcoming, and remaining counts
     * 
     * @return array Summary of real-time lesson status
     */
    public function getRealTimeLessonStatus(): array
    {
        $ongoing = $this->getOngoingLessons();
        $upcoming = $this->getUpcomingLessons(60); // Next 60 minutes
        $remaining = $this->getRemainingLessonsToday();
        $heatmap = $this->getTodayTimeSlotHeatmap();

        return [
            'ongoing' => $ongoing,
            'upcoming' => $upcoming,
            'remaining' => $remaining,
            'heatmap' => $heatmap,
            'generated_at' => now()->format('H:i:s d/m/Y'),
        ];
    }

    // ========================================
    // Teacher Login Status (No-show & Entry/Exit Violations)
    // ========================================

    /**
     * Get teacher login status statistics for multiple periods
     * Includes:
     * - Teacher no-show: Teachers who didn't join scheduled lessons (ole_teacher_first_join IS NULL)
     * - Late entry: Teachers who joined > 5 minutes after lesson start time
     * - Early exit: Teachers who left before lesson end time (joined but left early)
     * 
     * @return array Multi-period teacher login status stats
     */
    public function getMultiPeriodTeacherLoginStatusStats(): array
    {
        return $this->getCached('dashboard.teacher_login_status', function () {
            // Vietnam timezone is UTC+7, database stores in UTC (VN time - 7 hours)
            $nowVn = now();
            
            return [
                'today' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->startOfDay()->subHours(7),
                    $nowVn->copy()->endOfDay()->subHours(7),
                    'today'
                ),
                'yesterday' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->subDay()->startOfDay()->subHours(7),
                    $nowVn->copy()->subDay()->endOfDay()->subHours(7),
                    'yesterday'
                ),
                'day_before_yesterday' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->subDays(2)->startOfDay()->subHours(7),
                    $nowVn->copy()->subDays(2)->endOfDay()->subHours(7),
                    'day_before_yesterday'
                ),
                'this_week' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->startOfWeek()->subHours(7),
                    $nowVn->copy()->endOfWeek()->subHours(7),
                    'this_week'
                ),
                'last_week' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->subWeek()->startOfWeek()->subHours(7),
                    $nowVn->copy()->subWeek()->endOfWeek()->subHours(7),
                    'last_week'
                ),
                'this_month' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->startOfMonth()->subHours(7),
                    $nowVn->copy()->endOfMonth()->subHours(7),
                    'this_month'
                ),
                'last_month' => $this->getTeacherLoginStatusForPeriod(
                    $nowVn->copy()->subMonth()->startOfMonth()->subHours(7),
                    $nowVn->copy()->subMonth()->endOfMonth()->subHours(7),
                    'last_month'
                ),
            ];
        });
    }

    /**
     * Get teacher login status for a specific period
     * Uses tbl_order_lessons and tbl_order_lessons_extras to determine:
     * - No-show: Teacher didn't join (ole_teacher_first_join IS NULL) for completed lesson
     * - Late entry: Teacher joined > 5 minutes after scheduled start time
     * - Early exit: Teacher left before lesson should have ended
     * 
     * @param Carbon $startUtc Start time in UTC
     * @param Carbon $endUtc End time in UTC
     * @param string $periodKey Period identifier for caching
     * @return array Teacher login status for the period
     */
    private function getTeacherLoginStatusForPeriod(Carbon $startUtc, Carbon $endUtc, string $periodKey): array
    {
        // Get completed lessons in this period (only completed lessons can have violations)
        $completedLessons = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->count();

        // Teacher no-show: Completed lessons where teacher didn't join BUT student did join
        // (ole_teacher_first_join IS NULL AND ole_student_first_join IS NOT NULL)
        $teacherNoShow = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_student_first_join')
            ->count();

        // Late entry: Teacher joined > 5 minutes after lesson start time
        // Compare ole_teacher_first_join with ordles_lesson_starttime
        $lateEntry = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tbl_order_lessons.ordles_lesson_starttime, tbl_order_lessons_extras.ole_teacher_first_join) > 5')
            ->count();

        // Early exit: Teacher left before lesson should have ended
        // Compare ole_teacher_last_leave with (ordles_lesson_starttime + ordles_duration)
        $earlyExit = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_last_leave')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tbl_order_lessons.ordles_lesson_starttime, tbl_order_lessons_extras.ole_teacher_last_leave) < (tbl_order_lessons.ordles_duration - 5)')
            ->count();

        // Get total unique teachers with lessons in this period
        $totalTeachers = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->distinct('ordles_teacher_id')
            ->count('ordles_teacher_id');

        // Get unique teachers with violations
        $teachersWithNoShow = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_student_first_join')
            ->distinct('tbl_order_lessons.ordles_teacher_id')
            ->count('tbl_order_lessons.ordles_teacher_id');

        $teachersWithLateEntry = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tbl_order_lessons.ordles_lesson_starttime, tbl_order_lessons_extras.ole_teacher_first_join) > 5')
            ->distinct('tbl_order_lessons.ordles_teacher_id')
            ->count('tbl_order_lessons.ordles_teacher_id');

        $totalViolations = $teacherNoShow + $lateEntry + $earlyExit;
        $violationRate = $completedLessons > 0 ? round(($totalViolations / $completedLessons) * 100, 1) : 0;
        $noShowRate = $completedLessons > 0 ? round(($teacherNoShow / $completedLessons) * 100, 1) : 0;

        return [
            'total_completed_lessons' => $completedLessons,
            'total_teachers' => $totalTeachers,
            'no_show' => [
                'count' => $teacherNoShow,
                'unique_teachers' => $teachersWithNoShow,
                'rate' => $noShowRate,
            ],
            'late_entry' => [
                'count' => $lateEntry,
                'unique_teachers' => $teachersWithLateEntry,
            ],
            'early_exit' => [
                'count' => $earlyExit,
            ],
            'total_violations' => $totalViolations,
            'violation_rate' => $violationRate,
        ];
    }

    /**
     * Get detailed teacher no-show list for a specific period
     * Used in API for modal display
     * 
     * @param string $period Period identifier (today, yesterday, etc.)
     * @return array List of teachers with no-show violations
     */
    public function getTeacherNoShowDetails(string $period): array
    {
        // Calculate date range based on period
        $nowVn = now();
        switch ($period) {
            case 'today':
                $startUtc = $nowVn->copy()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->endOfDay()->subHours(7);
                break;
            case 'yesterday':
                $startUtc = $nowVn->copy()->subDay()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDay()->endOfDay()->subHours(7);
                break;
            case 'day_before_yesterday':
                $startUtc = $nowVn->copy()->subDays(2)->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDays(2)->endOfDay()->subHours(7);
                break;
            case 'this_week':
                $startUtc = $nowVn->copy()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->endOfWeek()->subHours(7);
                break;
            case 'last_week':
                $startUtc = $nowVn->copy()->subWeek()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->subWeek()->endOfWeek()->subHours(7);
                break;
            case 'this_month':
                $startUtc = $nowVn->copy()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->endOfMonth()->subHours(7);
                break;
            case 'last_month':
                $startUtc = $nowVn->copy()->subMonth()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->subMonth()->endOfMonth()->subHours(7);
                break;
            default:
                return [];
        }

        // Get no-show lessons with teacher details (teacher didn't join BUT student did)
        $noShowLessons = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_student_first_join')
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_orders.order_user_id'
            )
            ->orderBy('tbl_order_lessons.ordles_lesson_starttime', 'desc')
            ->limit(100)
            ->get();

        $result = [];
        foreach ($noShowLessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            
            $result[] = [
                'lesson_id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'teacher_email' => $teacher->user_email ?? '',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'lesson_date' => $startTimeVn->format('d/m/Y'),
                'lesson_time' => $startTimeVn->format('H:i'),
                'duration' => $lesson->ordles_duration,
            ];
        }

        return $result;
    }

    /**
     * Get detailed teacher late entry list for a specific period
     * 
     * @param string $period Period identifier
     * @return array List of teachers with late entry violations
     */
    public function getTeacherLateEntryDetails(string $period): array
    {
        $nowVn = now();
        switch ($period) {
            case 'today':
                $startUtc = $nowVn->copy()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->endOfDay()->subHours(7);
                break;
            case 'yesterday':
                $startUtc = $nowVn->copy()->subDay()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDay()->endOfDay()->subHours(7);
                break;
            case 'day_before_yesterday':
                $startUtc = $nowVn->copy()->subDays(2)->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDays(2)->endOfDay()->subHours(7);
                break;
            case 'this_week':
                $startUtc = $nowVn->copy()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->endOfWeek()->subHours(7);
                break;
            case 'last_week':
                $startUtc = $nowVn->copy()->subWeek()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->subWeek()->endOfWeek()->subHours(7);
                break;
            case 'this_month':
                $startUtc = $nowVn->copy()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->endOfMonth()->subHours(7);
                break;
            case 'last_month':
                $startUtc = $nowVn->copy()->subMonth()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->subMonth()->endOfMonth()->subHours(7);
                break;
            default:
                return [];
        }

        $lateEntryLessons = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tbl_order_lessons.ordles_lesson_starttime, tbl_order_lessons_extras.ole_teacher_first_join) > 5')
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_order_lessons_extras.ole_teacher_first_join',
                'tbl_orders.order_user_id'
            )
            ->orderBy('tbl_order_lessons.ordles_lesson_starttime', 'desc')
            ->limit(100)
            ->get();

        $result = [];
        foreach ($lateEntryLessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $teacherJoinVn = Carbon::parse($lesson->ole_teacher_first_join)->addHours(7);
            $lateMinutes = $startTimeVn->diffInMinutes($teacherJoinVn);
            
            $result[] = [
                'lesson_id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'teacher_email' => $teacher->user_email ?? '',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'lesson_date' => $startTimeVn->format('d/m/Y'),
                'scheduled_time' => $startTimeVn->format('H:i'),
                'actual_join_time' => $teacherJoinVn->format('H:i'),
                'late_minutes' => $lateMinutes,
                'duration' => $lesson->ordles_duration,
            ];
        }

        return $result;
    }

    /**
     * Get detailed teacher early exit list for a specific period
     * Early exit: Teacher left before lesson end time (joined but left >5 minutes early)
     * 
     * @param string $period Period identifier (today, yesterday, etc.)
     * @return array List of teachers with early exit violations
     */
    public function getTeacherEarlyExitDetails(string $period): array
    {
        $nowVn = now();
        switch ($period) {
            case 'today':
                $startUtc = $nowVn->copy()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->endOfDay()->subHours(7);
                break;
            case 'yesterday':
                $startUtc = $nowVn->copy()->subDay()->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDay()->endOfDay()->subHours(7);
                break;
            case 'day_before_yesterday':
                $startUtc = $nowVn->copy()->subDays(2)->startOfDay()->subHours(7);
                $endUtc = $nowVn->copy()->subDays(2)->endOfDay()->subHours(7);
                break;
            case 'this_week':
                $startUtc = $nowVn->copy()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->endOfWeek()->subHours(7);
                break;
            case 'last_week':
                $startUtc = $nowVn->copy()->subWeek()->startOfWeek()->subHours(7);
                $endUtc = $nowVn->copy()->subWeek()->endOfWeek()->subHours(7);
                break;
            case 'this_month':
                $startUtc = $nowVn->copy()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->endOfMonth()->subHours(7);
                break;
            case 'last_month':
                $startUtc = $nowVn->copy()->subMonth()->startOfMonth()->subHours(7);
                $endUtc = $nowVn->copy()->subMonth()->endOfMonth()->subHours(7);
                break;
            default:
                return [];
        }

        // Get early exit lessons: teacher left before lesson should have ended (>5 minutes early)
        $earlyExitLessons = OrderLesson::whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_last_leave')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tbl_order_lessons.ordles_lesson_starttime, tbl_order_lessons_extras.ole_teacher_last_leave) < (tbl_order_lessons.ordles_duration - 5)')
            ->join('tbl_orders', 'tbl_order_lessons.ordles_order_id', '=', 'tbl_orders.order_id')
            ->select(
                'tbl_order_lessons.ordles_id',
                'tbl_order_lessons.ordles_teacher_id',
                'tbl_order_lessons.ordles_lesson_starttime',
                'tbl_order_lessons.ordles_duration',
                'tbl_order_lessons_extras.ole_teacher_first_join',
                'tbl_order_lessons_extras.ole_teacher_last_leave',
                'tbl_orders.order_user_id'
            )
            ->orderBy('tbl_order_lessons.ordles_lesson_starttime', 'desc')
            ->limit(100)
            ->get();

        $result = [];
        foreach ($earlyExitLessons as $lesson) {
            $teacher = User::find($lesson->ordles_teacher_id);
            $student = User::find($lesson->order_user_id);
            
            $startTimeVn = Carbon::parse($lesson->ordles_lesson_starttime)->addHours(7);
            $expectedEndVn = $startTimeVn->copy()->addMinutes($lesson->ordles_duration);
            $actualLeaveVn = Carbon::parse($lesson->ole_teacher_last_leave)->addHours(7);
            $earlyMinutes = $actualLeaveVn->diffInMinutes($expectedEndVn);
            
            $result[] = [
                'lesson_id' => $lesson->ordles_id,
                'teacher_id' => $lesson->ordles_teacher_id,
                'teacher_name' => $teacher ? ($teacher->user_first_name . ' ' . $teacher->user_last_name) : 'Unknown',
                'teacher_email' => $teacher->user_email ?? '',
                'student_id' => $lesson->order_user_id,
                'student_name' => $student ? ($student->user_first_name . ' ' . $student->user_last_name) : 'Unknown',
                'lesson_date' => $startTimeVn->format('d/m/Y'),
                'scheduled_time' => $startTimeVn->format('H:i'),
                'expected_end_time' => $expectedEndVn->format('H:i'),
                'actual_leave_time' => $actualLeaveVn->format('H:i'),
                'early_minutes' => $earlyMinutes,
                'duration' => $lesson->ordles_duration,
            ];
        }

        return $result;
    }

    // ========================================
    // Monthly Session Trend Charts
    // ========================================

    /**
     * Get monthly session trend chart data
     * Returns daily stats for all days in the current month:
     * - Success rate (tỷ lệ thành công)
     * - Cancellation rate (tỷ lệ hủy)
     * - Teacher no-show rate (tỷ lệ GV no-show)
     * - Student no-show rate (tỷ lệ HV no-show)
     * 
     * @return array Chart data with labels and datasets
     */
    public function getMonthlySessionTrendChart(): array
    {
        return $this->getCached('dashboard.monthly_session_trend', function () {
            $monthStart = now()->startOfMonth();
            $today = now()->endOfDay();
            
            $labels = [];
            $successRates = [];
            $cancelRates = [];
            $teacherNoShowRates = [];
            $studentNoShowRates = [];
            
            // Get data for each day from the start of month to today
            $currentDay = $monthStart->copy();
            while ($currentDay <= $today) {
                $dayStart = $currentDay->copy()->startOfDay();
                $dayEnd = $currentDay->copy()->endOfDay();
                
                // Convert to UTC for database query (Vietnam is UTC+7)
                $dayStartUtc = $dayStart->copy()->subHours(7);
                $dayEndUtc = $dayEnd->copy()->subHours(7);
                
                $labels[] = $currentDay->format('d/m');
                
                // Get stats for this day using speakwell subjects
                $dayStats = $this->getDaySessionStats($dayStartUtc, $dayEndUtc);
                
                $successRates[] = $dayStats['success_rate'];
                $cancelRates[] = $dayStats['cancel_rate'];
                $teacherNoShowRates[] = $dayStats['teacher_no_show_rate'];
                $studentNoShowRates[] = $dayStats['student_no_show_rate'];
                
                $currentDay->addDay();
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    'success_rate' => $successRates,
                    'cancel_rate' => $cancelRates,
                    'teacher_no_show_rate' => $teacherNoShowRates,
                    'student_no_show_rate' => $studentNoShowRates,
                ],
                'month_label' => now()->format('m/Y'),
            ];
        });
    }

    /**
     * Get session stats for a specific day (used by monthly trend chart)
     * 
     * @param Carbon $startUtc Start of day in UTC
     * @param Carbon $endUtc End of day in UTC
     * @return array Day stats with rates
     */
    private function getDaySessionStats(Carbon $startUtc, Carbon $endUtc): array
    {
        // Base query with speakwell subjects filter
        $baseQuery = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds());
        
        // Total sessions (scheduled + completed + cancelled)
        $scheduled = (clone $baseQuery)->scheduled()->count();
        $completed = (clone $baseQuery)->completed()->count();
        $cancelled = (clone $baseQuery)->cancelled()->count();
        $total = $scheduled + $completed + $cancelled;
        
        // Success: completed (status=3) AND acceptance_code = 12
        $success = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->where('tbl_order_lessons_extras.ole_acceptance_code', OrderLessonExtra::ACCEPTANCE_SUCCESS)
            ->count();
        
        // Teacher no-show: completed (status=3) AND ole_teacher_first_join IS NULL AND ole_student_first_join IS NOT NULL
        $teacherNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_student_first_join')
            ->count();
        
        // Student no-show: completed (status=3) AND ole_student_first_join IS NULL AND ole_teacher_first_join IS NOT NULL
        $studentNoShow = OrderLesson::whereBetween('ordles_lesson_starttime', [$startUtc, $endUtc])
            ->whereIn('ordles_tlang_id', $this->getActiveSubjectIds())
            ->where('ordles_status', OrderLesson::STATUS_COMPLETED)
            ->join('tbl_order_lessons_extras', 'tbl_order_lessons.ordles_id', '=', 'tbl_order_lessons_extras.ole_ordles_id')
            ->whereNull('tbl_order_lessons_extras.ole_student_first_join')
            ->whereNotNull('tbl_order_lessons_extras.ole_teacher_first_join')
            ->count();
        
        // Calculate rates (percentages)
        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
        $teacherNoShowRate = $total > 0 ? round(($teacherNoShow / $total) * 100, 1) : 0;
        $studentNoShowRate = $total > 0 ? round(($studentNoShow / $total) * 100, 1) : 0;
        
        return [
            'total' => $total,
            'success' => $success,
            'cancelled' => $cancelled,
            'teacher_no_show' => $teacherNoShow,
            'student_no_show' => $studentNoShow,
            'success_rate' => $successRate,
            'cancel_rate' => $cancelRate,
            'teacher_no_show_rate' => $teacherNoShowRate,
            'student_no_show_rate' => $studentNoShowRate,
        ];
    }

    /**
     * Get monthly acceptance codes trend chart data
     * Returns daily counts for each acceptance code in the current month
     * Codes are grouped by billing type:
     * - Chargeable (positive): 4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17 (tính phí học viên)
     * - Compensate (negative): 1, 2, 3, 13, 14, 15 (phải bù buổi cho học viên)
     * 
     * @return array Chart data with labels, datasets for each code, and color mappings
     */
    public function getMonthlyAcceptanceCodesTrendChart(): array
    {
        return $this->getCached('dashboard.monthly_acceptance_codes_trend', function () {
            $monthStart = now()->startOfMonth();
            $today = now()->endOfDay();
            
            $labels = [];
            $chargeableCounts = [];
            $compensateCounts = [];
            
            // Initialize arrays for each code
            $codeData = [];
            foreach (array_merge(self::CHARGEABLE_CODES, self::COMPENSATE_CODES) as $code) {
                $codeData[$code] = [];
            }
            
            // Get data for each day from the start of month to today
            $currentDay = $monthStart->copy();
            while ($currentDay <= $today) {
                $dayStart = $currentDay->copy()->startOfDay();
                $dayEnd = $currentDay->copy()->endOfDay();
                
                // Convert to UTC for database query (Vietnam is UTC+7)
                $dayStartUtc = $dayStart->copy()->subHours(7);
                $dayEndUtc = $dayEnd->copy()->subHours(7);
                
                $labels[] = $currentDay->format('d/m');
                
                // Get acceptance code breakdown for this day
                $breakdown = OrderLessonExtra::select('ole_acceptance_code', DB::raw('COUNT(*) as count'))
                    ->join('tbl_order_lessons', 'tbl_order_lessons_extras.ole_ordles_id', '=', 'tbl_order_lessons.ordles_id')
                    ->whereBetween('tbl_order_lessons.ordles_lesson_starttime', [$dayStartUtc, $dayEndUtc])
                    ->whereIn('tbl_order_lessons.ordles_tlang_id', $this->getActiveSubjectIds())
                    ->where('tbl_order_lessons.ordles_status', OrderLesson::STATUS_COMPLETED)
                    ->groupBy('ole_acceptance_code')
                    ->pluck('count', 'ole_acceptance_code')
                    ->toArray();
                
                // Calculate chargeable and compensate totals for this day
                $dayChargeable = 0;
                $dayCompensate = 0;
                
                foreach (self::CHARGEABLE_CODES as $code) {
                    $count = $breakdown[$code] ?? 0;
                    $codeData[$code][] = $count;
                    $dayChargeable += $count;
                }
                
                foreach (self::COMPENSATE_CODES as $code) {
                    $count = $breakdown[$code] ?? 0;
                    $codeData[$code][] = $count;
                    $dayCompensate += $count;
                }
                
                $chargeableCounts[] = $dayChargeable;
                $compensateCounts[] = $dayCompensate;
                
                $currentDay->addDay();
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    'chargeable' => $chargeableCounts,
                    'compensate' => $compensateCounts,
                    'by_code' => $codeData,
                ],
                'month_label' => now()->format('m/Y'),
                'code_info' => [
                    'chargeable' => [
                        'codes' => self::CHARGEABLE_CODES,
                        'label' => 'Có tính phí học viên',
                        'color' => '#22c55e', // green-500
                    ],
                    'compensate' => [
                        'codes' => self::COMPENSATE_CODES,
                        'label' => 'Phải bù buổi cho học viên',
                        'color' => '#ef4444', // red-500
                    ],
                ],
            ];
        });
    }

    // ========================================
    // Never Logged In Students Trend Chart
    // ========================================

    /**
     * Get never-logged-in students trend chart data for the current month
     * Returns daily stats for all days from start of month to today:
     * - count: Number of students who never logged in that day
     * - rate: Percentage of students who never logged in
     * 
     * @return array Chart data with labels and datasets
     */
    public function getNeverLoggedInTrendChart(): array
    {
        return $this->getCached('dashboard.never_logged_in_trend', function () {
            $monthStart = now()->startOfMonth();
            $today = now()->endOfDay();
            
            $labels = [];
            $counts = [];
            $rates = [];
            $totals = [];
            
            // Get data for each day from the start of month to today
            $currentDay = $monthStart->copy();
            while ($currentDay <= $today) {
                $dayStart = $currentDay->copy()->startOfDay();
                $dayEnd = $currentDay->copy()->endOfDay();
                
                $labels[] = $currentDay->format('d/m');
                
                // Get never-logged-in stats for this day
                $dayStats = $this->getNeverLoggedInStudentsWithLessons($dayStart, $dayEnd);
                
                $counts[] = $dayStats['count'];
                $rates[] = $dayStats['rate'];
                $totals[] = $dayStats['total_students_with_lessons'];
                
                $currentDay->addDay();
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    'counts' => $counts,
                    'rates' => $rates,
                    'totals' => $totals,
                ],
                'month_label' => now()->format('m/Y'),
            ];
        });
    }

    // ========================================
    // Voucher (Coupon) Statistics
    // ========================================

    /**
     * Get voucher stats for a specific date range
     * 
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Voucher statistics
     */
    public function getVoucherStatsForRange(Carbon $start, Carbon $end): array
    {
        // Total coupons in system (all time)
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::active()->valid()->count();
        $expiredCoupons = Coupon::expired()->count();
        
        // Coupon usage in period (from coupon history - applied to orders)
        $usageInPeriod = CouponHistory::createdBetween($start, $end)->count();
        
        // Unique coupons used in period
        $uniqueCouponsUsed = CouponHistory::createdBetween($start, $end)
            ->distinct('couhis_coupon_id')
            ->count('couhis_coupon_id');
        
        // Unique users who used coupons in period (beneficiary from logs)
        $uniqueUsersWithCoupon = CouponLog::applied()
            ->createdBetween($start, $end)
            ->distinct('clog_beneficiary_id')
            ->count('clog_beneficiary_id');
        
        // Orders with coupons in period
        $ordersWithCoupon = CouponHistory::createdBetween($start, $end)
            ->distinct('couhis_order_id')
            ->count('couhis_order_id');
        
        // Released/cancelled coupons in period
        $releasedInPeriod = CouponLog::released()
            ->createdBetween($start, $end)
            ->count();
        
        // Net applied (applied - released in period)
        $appliedInPeriod = CouponLog::applied()
            ->createdBetween($start, $end)
            ->count();
        
        // Calculate discount amount from coupon history
        $totalDiscountAmount = $this->calculateTotalDiscountForRange($start, $end);
        
        // Top coupons by usage in period
        $topCoupons = $this->getTopCouponsForRange($start, $end, 5);
        
        // Usage rate (% of coupons that have been used at all)
        $couponsWithUsage = Coupon::where('coupon_used_uses', '>', 0)->count();
        $overallUsageRate = $totalCoupons > 0 ? round(($couponsWithUsage / $totalCoupons) * 100, 1) : 0;
        
        return [
            // Overview
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'expired_coupons' => $expiredCoupons,
            
            // Period-specific
            'usage_count' => $usageInPeriod,
            'unique_coupons_used' => $uniqueCouponsUsed,
            'unique_users_with_coupon' => $uniqueUsersWithCoupon,
            'orders_with_coupon' => $ordersWithCoupon,
            
            // Actions
            'applied_count' => $appliedInPeriod,
            'released_count' => $releasedInPeriod,
            'net_applied' => $appliedInPeriod - $releasedInPeriod,
            
            // Value
            'total_discount_amount' => $totalDiscountAmount,
            
            // Rates
            'overall_usage_rate' => $overallUsageRate,
            
            // Top coupons
            'top_coupons' => $topCoupons,
        ];
    }
    
    /**
     * Calculate total discount amount for a date range
     * 
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Total discount amount
     */
    private function calculateTotalDiscountForRange(Carbon $start, Carbon $end): float
    {
        $histories = CouponHistory::createdBetween($start, $end)
            ->whereNotNull('couhis_coupon')
            ->get();
        
        $totalDiscount = 0;
        foreach ($histories as $history) {
            $couponData = $history->couhis_coupon;
            if (is_array($couponData) && isset($couponData['coupon_discount'])) {
                $totalDiscount += (float) $couponData['coupon_discount'];
            } elseif (is_string($couponData)) {
                $decoded = json_decode($couponData, true);
                if (is_array($decoded) && isset($decoded['coupon_discount'])) {
                    $totalDiscount += (float) $decoded['coupon_discount'];
                }
            }
        }
        
        return $totalDiscount;
    }
    
    /**
     * Get top coupons by usage for a date range
     * 
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @param int $limit Number of top coupons to return
     * @return array Top coupons with usage stats
     */
    private function getTopCouponsForRange(Carbon $start, Carbon $end, int $limit = 5): array
    {
        return CouponHistory::select('couhis_coupon_id', DB::raw('COUNT(*) as usage_count'))
            ->createdBetween($start, $end)
            ->groupBy('couhis_coupon_id')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $coupon = Coupon::find($item->couhis_coupon_id);
                return [
                    'coupon_id' => $item->couhis_coupon_id,
                    'coupon_code' => $coupon?->coupon_code ?? 'Unknown',
                    'coupon_identifier' => $coupon?->coupon_identifier ?? '',
                    'usage_count' => $item->usage_count,
                    'discount_type' => $coupon?->coupon_discount_type ?? 0,
                    'discount_value' => $coupon?->coupon_discount_value ?? 0,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get multi-period voucher stats (like session stats pattern)
     * Periods: today, yesterday, day_before_yesterday, this_week, last_week, this_month
     * 
     * @return array Multi-period voucher statistics
     */
    public function getMultiPeriodVoucherStats(): array
    {
        return $this->getCached('dashboard.voucher_stats', function () {
            // Today
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            
            // Yesterday
            $yesterdayStart = now()->subDay()->startOfDay();
            $yesterdayEnd = now()->subDay()->endOfDay();
            
            // Day before yesterday (Hôm kia)
            $dayBeforeStart = now()->subDays(2)->startOfDay();
            $dayBeforeEnd = now()->subDays(2)->endOfDay();
            
            // This week
            $thisWeekStart = now()->startOfWeek();
            $thisWeekEnd = now()->endOfWeek();
            
            // Last week
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $lastWeekEnd = now()->subWeek()->endOfWeek();
            
            // This month
            $thisMonthStart = now()->startOfMonth();
            $thisMonthEnd = now()->endOfMonth();
            
            return [
                'today' => $this->getVoucherStatsForRange($todayStart, $todayEnd),
                'yesterday' => $this->getVoucherStatsForRange($yesterdayStart, $yesterdayEnd),
                'day_before_yesterday' => $this->getVoucherStatsForRange($dayBeforeStart, $dayBeforeEnd),
                'this_week' => $this->getVoucherStatsForRange($thisWeekStart, $thisWeekEnd),
                'last_week' => $this->getVoucherStatsForRange($lastWeekStart, $lastWeekEnd),
                'this_month' => $this->getVoucherStatsForRange($thisMonthStart, $thisMonthEnd),
            ];
        });
    }

    /**
     * Phase 38: Get students grouped by class size
     * Shows how many students (learners) are in 1:1, 1:2, 1:3, etc. lessons
     * 
     * This counts distinct learners who have at least one lesson with each class size
     * based on tbl_group_classes.grpcls_total_seats
     * 
     * @return array Class size statistics
     */
    public function getStudentsByClassSize(): array
    {
        return $this->getCached('dashboard.students_by_class_size', function () {
            // Query to get student counts by class size
            // Join order_lessons with group_classes via teacher_id and tlang_id
            // Then group by the class size (grpcls_total_seats)
            // Phase 39: Filter by SpeakWell subjects only
            $classSizeStats = DB::connection('mysql')
                ->table('tbl_order_lessons as ol')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
                ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
                ->leftJoin(DB::raw('(SELECT gc_inner.grpcls_tlang_id, gc_inner.grpcls_teacher_id, MAX(gc_inner.grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes gc_inner GROUP BY gc_inner.grpcls_tlang_id, gc_inner.grpcls_teacher_id) as gc'), function ($join) {
                    $join->on('gc.grpcls_tlang_id', '=', 'ol.ordles_tlang_id')
                         ->on('gc.grpcls_teacher_id', '=', 'ol.ordles_teacher_id');
                })
                ->whereNull('u.user_deleted')
                ->where(function ($query) {
                    $query->where('u.user_is_teacher', 0)
                          ->orWhereNull('u.user_is_teacher');
                })
                // Only count lessons that are scheduled or completed
                ->whereIn('ol.ordles_status', [2, 3])
                // Phase 39: Filter by SpeakWell subjects only
                ->whereIn('ol.ordles_tlang_id', $this->getActiveSubjectIds())
                ->selectRaw('
                    CASE 
                        WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN "1:1"
                        WHEN gc.grpcls_total_seats = 2 THEN "1:2"
                        WHEN gc.grpcls_total_seats = 3 THEN "1:3"
                        WHEN gc.grpcls_total_seats BETWEEN 4 AND 6 THEN "1:6"
                        WHEN gc.grpcls_total_seats BETWEEN 7 AND 10 THEN "1:8"
                        ELSE "Group"
                    END as class_size
                ')
                ->selectRaw('COUNT(DISTINCT o.order_user_id) as student_count')
                ->groupBy('class_size')
                ->orderByRaw("FIELD(class_size, '1:1', '1:2', '1:3', '1:6', '1:8', 'Group')")
                ->get()
                ->toArray();

            // Transform to key-value array and ensure all sizes are present
            $result = [
                '1:1' => 0,
                '1:2' => 0,
                '1:3' => 0,
                '1:6' => 0,
                '1:8' => 0,
                'Group' => 0,
            ];

            foreach ($classSizeStats as $stat) {
                $result[$stat->class_size] = (int) $stat->student_count;
            }

            // Calculate total unique students across all class sizes
            // (Note: a student can be in multiple class sizes)
            // Phase 39: Filter by SpeakWell subjects only
            $totalUniqueStudents = DB::connection('mysql')
                ->table('tbl_order_lessons as ol')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
                ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
                ->whereNull('u.user_deleted')
                ->where(function ($query) {
                    $query->where('u.user_is_teacher', 0)
                          ->orWhereNull('u.user_is_teacher');
                })
                ->whereIn('ol.ordles_status', [2, 3])
                // Phase 39: Filter by SpeakWell subjects only
                ->whereIn('ol.ordles_tlang_id', $this->getActiveSubjectIds())
                ->distinct()
                ->count('o.order_user_id');

            return [
                'by_size' => $result,
                'total_with_lessons' => $totalUniqueStudents,
            ];
        });
    }

    /**
     * Phase 98: Get cancellation statistics from tbl_session_logs
     * Phase 99: Added pagination, user_type filter, search, and priority ordering
     * Phase 223: Classify cancellations by class type: 1:1 (order_lessons) and 1:2 (group_classes)
     * 
     * Queries tbl_session_logs for cancelled sessions.
     * 1:1: sesslog_record_type=1, sesslog_changed_status=4, ordles_status=4
     * 1:2: sesslog_record_type=2, sesslog_changed_status=3, grpcls_status=3
     * Groups by sesslog_user_type (1=student, 2=teacher, 3=admin).
     *
     * @param Carbon $start Start of period (Vietnam timezone)
     * @param Carbon $end End of period (Vietnam timezone)
     * @param array $options Optional: page, per_page, user_type_filter, search
     * @return array
     */
    public function getCancellationStats(Carbon $start, Carbon $end, array $options = []): array
    {
        $startUtc = $start->copy()->subHours(7);
        $endUtc = $end->copy()->subHours(7);
        $subjectIds = $this->getActiveSubjectIds();

        // ===== 1:1 Summary (tbl_order_lessons) =====
        $summary1v1 = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->selectRaw('sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) as count')
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereIn('ol.ordles_tlang_id', $subjectIds)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->groupBy('sl.sesslog_user_type')
            ->get()
            ->keyBy('sesslog_user_type');

        $byStudent1v1 = (int) ($summary1v1->get(SessionLog::USER_TYPE_STUDENT)?->count ?? 0);
        $byTeacher1v1 = (int) ($summary1v1->get(SessionLog::USER_TYPE_TEACHER)?->count ?? 0);
        $byAdmin1v1 = (int) ($summary1v1->get(SessionLog::USER_TYPE_ADMIN)?->count ?? 0);

        $total1v1 = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereIn('ol.ordles_tlang_id', $subjectIds)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->distinct()
            ->count('ol.ordles_id');

        // ===== 1:2 Summary (tbl_group_classes) =====
        $summary1v2 = DB::connection('mysql')
            ->table('tbl_group_classes as gc')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('gc.grpcls_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', GroupClass::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_GROUP_CLASS);
            })
            ->selectRaw('sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id) as count')
            ->where('gc.grpcls_status', GroupClass::STATUS_CANCELLED)
            ->whereIn('gc.grpcls_tlang_id', $subjectIds)
            ->whereRaw('sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)')
            ->whereBetween('gc.grpcls_start_datetime', [$startUtc, $endUtc])
            ->groupBy('sl.sesslog_user_type')
            ->get()
            ->keyBy('sesslog_user_type');

        $byStudent1v2 = (int) ($summary1v2->get(SessionLog::USER_TYPE_STUDENT)?->count ?? 0);
        $byTeacher1v2 = (int) ($summary1v2->get(SessionLog::USER_TYPE_TEACHER)?->count ?? 0);
        $byAdmin1v2 = (int) ($summary1v2->get(SessionLog::USER_TYPE_ADMIN)?->count ?? 0);

        $total1v2 = DB::connection('mysql')
            ->table('tbl_group_classes as gc')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('gc.grpcls_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', GroupClass::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_GROUP_CLASS);
            })
            ->where('gc.grpcls_status', GroupClass::STATUS_CANCELLED)
            ->whereIn('gc.grpcls_tlang_id', $subjectIds)
            ->whereRaw('sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)')
            ->whereBetween('gc.grpcls_start_datetime', [$startUtc, $endUtc])
            ->distinct()
            ->count('gc.grpcls_id');

        // ===== Combined totals =====
        $total = $total1v1 + $total1v2;
        $byStudent = $byStudent1v1 + $byStudent1v2;
        $byTeacher = $byTeacher1v1 + $byTeacher1v2;
        $byAdmin = $byAdmin1v1 + $byAdmin1v2;

        // ===== Details (merged 1:1 + 1:2, paginated in PHP) =====
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = max(1, min(10000, (int) ($options['per_page'] ?? 50)));
        $userTypeFilter = $options['user_type_filter'] ?? null;
        $search = trim($options['search'] ?? '');

        $details1v1 = $this->getCancellation1v1Details($startUtc, $endUtc, $subjectIds, $userTypeFilter, $search);
        $details1v2 = $this->getCancellation1v2Details($startUtc, $endUtc, $subjectIds, $userTypeFilter, $search);

        // Merge and sort: Teacher (2) > Student (1) > Admin (3), then by cancelled_at desc
        $userTypePriority = [2 => 0, 1 => 1, 3 => 2];
        $allDetails = collect(array_merge($details1v1, $details1v2))
            ->sort(function ($a, $b) use ($userTypePriority) {
                $priorityA = $userTypePriority[$a['user_type']] ?? 9;
                $priorityB = $userTypePriority[$b['user_type']] ?? 9;
                if ($priorityA !== $priorityB) {
                    return $priorityA - $priorityB;
                }
                return strcmp($b['cancelled_at_raw'], $a['cancelled_at_raw']);
            })
            ->values();

        $totalFiltered = $allDetails->count();
        $lastPage = max(1, (int) ceil($totalFiltered / $perPage));
        $page = min($page, $lastPage);
        $pageItems = $allDetails->slice(($page - 1) * $perPage, $perPage)->values()->toArray();

        return [
            'total' => $total,
            'by_student' => $byStudent,
            'by_teacher' => $byTeacher,
            'by_admin' => $byAdmin,
            'one_on_one' => [
                'total' => $total1v1,
                'by_student' => $byStudent1v1,
                'by_teacher' => $byTeacher1v1,
                'by_admin' => $byAdmin1v1,
            ],
            'one_on_two' => [
                'total' => $total1v2,
                'by_student' => $byStudent1v2,
                'by_teacher' => $byTeacher1v2,
                'by_admin' => $byAdmin1v2,
            ],
            'details' => $pageItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiltered,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * Phase 223: Get 1:1 cancellation detail records
     */
    private function getCancellation1v1Details($startUtc, $endUtc, array $subjectIds, $userTypeFilter, string $search): array
    {
        $query = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('ol.ordles_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', SessionLog::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_ORDER_LESSON);
            })
            ->join('tbl_orders as o', 'ol.ordles_order_id', '=', 'o.order_id')
            ->leftJoin('tbl_users as t', 'ol.ordles_teacher_id', '=', 't.user_id')
            ->leftJoin('tbl_users as s', 'o.order_user_id', '=', 's.user_id')
            ->where('ol.ordles_status', OrderLesson::STATUS_CANCELLED)
            ->whereIn('ol.ordles_tlang_id', $subjectIds)
            ->whereRaw('ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)')
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc]);

        if ($userTypeFilter && in_array((int) $userTypeFilter, [1, 2, 3])) {
            $query->where('sl.sesslog_user_type', (int) $userTypeFilter);
        }
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) LIKE ?", [$searchTerm])
                  ->orWhereRaw("CONCAT(COALESCE(s.user_first_name, ''), ' ', COALESCE(s.user_last_name, '')) LIKE ?", [$searchTerm]);
            });
        }

        return $query->select([
            'sl.sesslog_id',
            'sl.sesslog_record_id',
            'sl.sesslog_user_type',
            'sl.sesslog_comment',
            'sl.sesslog_created',
            'ol.ordles_lesson_starttime',
            'ol.ordles_lesson_endtime',
            'ol.ordles_duration',
            DB::raw("CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) as teacher_name"),
            DB::raw("COALESCE(t.user_email, '') as teacher_email"),
            DB::raw("CONCAT(COALESCE(s.user_first_name, ''), ' ', COALESCE(s.user_last_name, '')) as student_name"),
            DB::raw("COALESCE(s.user_email, '') as student_email"),
        ])
        ->get()
        ->map(function ($row) {
            $lessonStart = $row->ordles_lesson_starttime
                ? Carbon::parse($row->ordles_lesson_starttime)->addHours(7)->format('d/m/Y H:i')
                : '';
            $lessonEnd = $row->ordles_lesson_endtime
                ? Carbon::parse($row->ordles_lesson_endtime)->addHours(7)->format('H:i')
                : '';

            return [
                'sesslog_id' => $row->sesslog_id,
                'ordles_id' => $row->sesslog_record_id,
                'class_type' => '1:1',
                'user_type' => (int) $row->sesslog_user_type,
                'user_type_label' => SessionLog::getUserTypeLabel((int) $row->sesslog_user_type),
                'comment' => $row->sesslog_comment ?? '',
                'cancelled_at' => Carbon::parse($row->sesslog_created)->format('d/m/Y H:i'),
                'cancelled_at_raw' => $row->sesslog_created,
                'lesson_time' => $lessonStart . ($lessonEnd ? ' - ' . $lessonEnd : ''),
                'lesson_duration' => $row->ordles_duration ?? 0,
                'teacher_name' => trim($row->teacher_name) ?: 'N/A',
                'teacher_email' => $row->teacher_email ?: '',
                'student_name' => trim($row->student_name) ?: 'N/A',
                'student_email' => $row->student_email ?: '',
            ];
        })
        ->toArray();
    }

    /**
     * Phase 223: Get 1:2 cancellation detail records from tbl_group_classes
     */
    private function getCancellation1v2Details($startUtc, $endUtc, array $subjectIds, $userTypeFilter, string $search): array
    {
        $query = DB::connection('mysql')
            ->table('tbl_group_classes as gc')
            ->join('tbl_session_logs as sl', function ($join) {
                $join->on('gc.grpcls_id', '=', 'sl.sesslog_record_id')
                    ->where('sl.sesslog_changed_status', '=', GroupClass::STATUS_CANCELLED)
                    ->where('sl.sesslog_record_type', '=', SessionLog::RECORD_TYPE_GROUP_CLASS);
            })
            ->leftJoin('tbl_users as t', 'gc.grpcls_teacher_id', '=', 't.user_id')
            ->where('gc.grpcls_status', GroupClass::STATUS_CANCELLED)
            ->whereIn('gc.grpcls_tlang_id', $subjectIds)
            ->whereRaw('sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)')
            ->whereBetween('gc.grpcls_start_datetime', [$startUtc, $endUtc]);

        if ($userTypeFilter && in_array((int) $userTypeFilter, [1, 2, 3])) {
            $query->where('sl.sesslog_user_type', (int) $userTypeFilter);
        }
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) LIKE ?", [$searchTerm])
                  ->orWhereRaw("EXISTS (SELECT 1 FROM tbl_order_classes oc_s INNER JOIN tbl_users us_s ON oc_s.ordcls_beneficiary_id = us_s.user_id WHERE oc_s.ordcls_grpcls_id = gc.grpcls_id AND CONCAT(COALESCE(us_s.user_first_name, ''), ' ', COALESCE(us_s.user_last_name, '')) LIKE ?)", [$searchTerm]);
            });
        }

        return $query->select([
            'sl.sesslog_id',
            'gc.grpcls_id',
            'sl.sesslog_user_type',
            'sl.sesslog_comment',
            'sl.sesslog_created',
            'gc.grpcls_start_datetime',
            DB::raw("CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) as teacher_name"),
            DB::raw("COALESCE(t.user_email, '') as teacher_email"),
            DB::raw("(SELECT GROUP_CONCAT(CONCAT(COALESCE(us.user_first_name, ''), ' ', COALESCE(us.user_last_name, '')) SEPARATOR ', ') FROM tbl_order_classes oc INNER JOIN tbl_users us ON oc.ordcls_beneficiary_id = us.user_id WHERE oc.ordcls_grpcls_id = gc.grpcls_id) as student_name"),
            DB::raw("(SELECT GROUP_CONCAT(COALESCE(us2.user_email, '') SEPARATOR ', ') FROM tbl_order_classes oc2 INNER JOIN tbl_users us2 ON oc2.ordcls_beneficiary_id = us2.user_id WHERE oc2.ordcls_grpcls_id = gc.grpcls_id) as student_email"),
        ])
        ->get()
        ->map(function ($row) {
            $lessonStart = $row->grpcls_start_datetime
                ? Carbon::parse($row->grpcls_start_datetime)->addHours(7)->format('d/m/Y H:i')
                : '';

            return [
                'sesslog_id' => $row->sesslog_id,
                'ordles_id' => $row->grpcls_id,
                'class_type' => '1:2',
                'user_type' => (int) $row->sesslog_user_type,
                'user_type_label' => SessionLog::getUserTypeLabel((int) $row->sesslog_user_type),
                'comment' => $row->sesslog_comment ?? '',
                'cancelled_at' => Carbon::parse($row->sesslog_created)->format('d/m/Y H:i'),
                'cancelled_at_raw' => $row->sesslog_created,
                'lesson_time' => $lessonStart,
                'lesson_duration' => 0,
                'teacher_name' => trim($row->teacher_name) ?: 'N/A',
                'teacher_email' => $row->teacher_email ?: '',
                'student_name' => trim($row->student_name) ?: 'N/A',
                'student_email' => $row->student_email ?: '',
            ];
        })
        ->toArray();
    }

    /**
     * Get leave request affected sessions summary stats
     * Counts total sessions, by replacement type, etc.
     */
    public function getLeaveAffectedSessionsStats(): array
    {
        return $this->getCached('dashboard.leave_affected_sessions_stats', function () {
            $total = TeacherLeaveRequestSession::count();
            $lessonSessions = TeacherLeaveRequestSession::where('tlrs_session_type', TeacherLeaveRequestSession::SESSION_TYPE_LESSON)->count();
            $classSessions = TeacherLeaveRequestSession::where('tlrs_session_type', TeacherLeaveRequestSession::SESSION_TYPE_CLASS)->count();
            $needReplacement = TeacherLeaveRequestSession::where('tlrs_need_replacement', 1)->count();
            $substitute = TeacherLeaveRequestSession::where('tlrs_replacement_type', TeacherLeaveRequestSession::REPLACEMENT_TYPE_SUBSTITUTE)->count();
            $replace = TeacherLeaveRequestSession::where('tlrs_replacement_type', TeacherLeaveRequestSession::REPLACEMENT_TYPE_REPLACE)->count();
            $noReplacement = TeacherLeaveRequestSession::whereNull('tlrs_replacement_type')->count();

            return [
                'total' => $total,
                'lesson_sessions' => $lessonSessions,
                'class_sessions' => $classSessions,
                'need_replacement' => $needReplacement,
                'substitute' => $substitute,
                'replace' => $replace,
                'no_replacement' => $noReplacement,
            ];
        });
    }

    /**
     * Get leave request affected sessions detail list
     * Returns paginated list of sessions affected by teacher leave requests
     * With teacher, learner info from joins and tlrs_session_info JSON
     */
    public function getLeaveAffectedSessionsDetail(int $page = 1, int $perPage = 20, ?string $search = null, ?string $replacementFilter = null): array
    {
        $query = DB::connection('mysql')->table('tbl_teacher_leave_request_sessions as tlrs')
            ->join('tbl_teacher_leave_requests as tlr', 'tlrs.tlrs_leave_request_id', '=', 'tlr.tlr_id')
            ->join('tbl_users as teacher', 'tlr.tlr_teacher_id', '=', 'teacher.user_id')
            ->leftJoin('tbl_order_lessons as ol', function ($join) {
                $join->on('tlrs.tlrs_session_id', '=', 'ol.ordles_id')
                    ->where('tlrs.tlrs_session_type', '=', TeacherLeaveRequestSession::SESSION_TYPE_LESSON);
            })
            ->leftJoin('tbl_orders as o', 'ol.ordles_order_id', '=', 'o.order_id')
            ->leftJoin('tbl_users as learner', 'o.order_user_id', '=', 'learner.user_id')
            ->select(
                'tlrs.tlrs_id',
                'tlrs.tlrs_leave_request_id',
                'tlrs.tlrs_session_id',
                'tlrs.tlrs_session_type',
                'tlrs.tlrs_session_date',
                'tlrs.tlrs_need_replacement',
                'tlrs.tlrs_replacement_type',
                'tlrs.tlrs_created_at',
                'tlr.tlr_teacher_id',
                'tlr.tlr_start_date',
                'tlr.tlr_end_date',
                'tlr.tlr_status',
                'tlr.tlr_reason',
                'tlr.tlr_reason_type',
                DB::raw("CONCAT(teacher.user_first_name, ' ', teacher.user_last_name) as teacher_name"),
                'teacher.user_email as teacher_email',
                'ol.ordles_lesson_starttime',
                'ol.ordles_duration',
                'ol.ordles_status as lesson_status',
                DB::raw("CONCAT(COALESCE(learner.user_first_name, ''), ' ', COALESCE(learner.user_last_name, '')) as learner_name"),
                'learner.user_email as learner_email'
            );

        // Try to select tlrs_session_info if it exists (may contain learner JSON)
        try {
            $query->addSelect('tlrs.tlrs_session_info');
        } catch (\Exception $e) {
            // Column may not exist in older schema versions
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(teacher.user_first_name, ' ', teacher.user_last_name)"), 'LIKE', "%{$search}%")
                    ->orWhere('teacher.user_email', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw("CONCAT(COALESCE(learner.user_first_name, ''), ' ', COALESCE(learner.user_last_name, ''))"), 'LIKE', "%{$search}%")
                    ->orWhere('learner.user_email', 'LIKE', "%{$search}%");
            });
        }

        // Apply replacement type filter
        if ($replacementFilter !== null) {
            if ($replacementFilter === 'null') {
                $query->whereNull('tlrs.tlrs_replacement_type');
            } else {
                $query->where('tlrs.tlrs_replacement_type', (int) $replacementFilter);
            }
        }

        $totalFiltered = $query->count();
        $lastPage = max(1, (int) ceil($totalFiltered / $perPage));

        $sessions = $query->orderByDesc('tlrs.tlrs_session_date')
            ->orderByDesc('tlrs.tlrs_created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $details = $sessions->map(function ($session) {
            // Try to extract learner info from tlrs_session_info JSON
            $sessionInfo = null;
            $learnersFromJson = [];
            if (isset($session->tlrs_session_info) && $session->tlrs_session_info) {
                $sessionInfo = is_string($session->tlrs_session_info)
                    ? json_decode($session->tlrs_session_info, true)
                    : (array) $session->tlrs_session_info;

                // JSON has "learners" array (can have multiple learners)
                if (isset($sessionInfo['learners']) && is_array($sessionInfo['learners'])) {
                    $learnersFromJson = $sessionInfo['learners'];
                }
            }

            // Use learner from JSON if available, otherwise from join
            $learnerName = '';
            $learnerEmail = '';
            if (!empty($learnersFromJson)) {
                // Get first learner from the array (1-1 sessions usually have 1 learner)
                $firstLearner = $learnersFromJson[0];
                $learnerName = $firstLearner['full_name'] ?? '';
                if (!$learnerName) {
                    $learnerName = trim(($firstLearner['first_name'] ?? '') . ' ' . ($firstLearner['last_name'] ?? ''));
                }
                $learnerEmail = $firstLearner['email'] ?? '';

                // If multiple learners (group class), show count
                if (count($learnersFromJson) > 1) {
                    $learnerName .= ' (+' . (count($learnersFromJson) - 1) . ')';
                }
            }
            if ((!$learnerName || $learnerName === ' ') && ($session->learner_name ?? null)) {
                $learnerName = trim($session->learner_name);
            }
            if (!$learnerEmail && ($session->learner_email ?? null)) {
                $learnerEmail = $session->learner_email;
            }

            // Format session date (VN timezone)
            $sessionDate = $session->tlrs_session_date
                ? Carbon::parse($session->tlrs_session_date)->format('d/m/Y')
                : '';

            // Format lesson time - prefer from JSON session_start_time, fallback to order_lessons
            $lessonTime = '';
            $lessonDuration = '';
            if ($sessionInfo && isset($sessionInfo['session_start_time'])) {
                $startTimeVn = Carbon::parse($sessionInfo['session_start_time'])->addHours(7);
                $lessonTime = $startTimeVn->format('H:i');
                if (isset($sessionInfo['session_end_time'])) {
                    $endTime = Carbon::parse($sessionInfo['session_end_time']);
                    $startTime = Carbon::parse($sessionInfo['session_start_time']);
                    $durationMinutes = $startTime->diffInMinutes($endTime);
                    $lessonDuration = $durationMinutes . ' phút';
                }
            }
            if (!$lessonTime && $session->ordles_lesson_starttime) {
                $startTimeVn = Carbon::parse($session->ordles_lesson_starttime)->addHours(7);
                $lessonTime = $startTimeVn->format('H:i');
            }
            if (!$lessonDuration && $session->ordles_duration) {
                $lessonDuration = $session->ordles_duration . ' phút';
            }

            // Lesson status
            $lessonStatusLabel = '';
            if ($session->lesson_status !== null) {
                $lessonStatusLabel = OrderLesson::getStatusLabel((int) $session->lesson_status);
            }

            return [
                'id' => $session->tlrs_id,
                'leave_request_id' => $session->tlrs_leave_request_id,
                'session_id' => $session->tlrs_session_id,
                'session_type' => $session->tlrs_session_type,
                'session_type_label' => TeacherLeaveRequestSession::getSessionTypeLabel($session->tlrs_session_type),
                'session_date' => $sessionDate,
                'need_replacement' => (bool) $session->tlrs_need_replacement,
                'replacement_type' => $session->tlrs_replacement_type,
                'replacement_type_label' => TeacherLeaveRequestSession::getReplacementTypeShortLabel($session->tlrs_replacement_type),
                'teacher_name' => $session->teacher_name ?? 'Unknown',
                'teacher_email' => $session->teacher_email ?? '',
                'learner_name' => $learnerName ?: '—',
                'learner_email' => $learnerEmail,
                'lesson_time' => $lessonTime,
                'lesson_duration' => $lessonDuration,
                'lesson_status' => $lessonStatusLabel,
                'subject_name' => $sessionInfo['subject_name'] ?? '',
                'leave_status' => TeacherLeaveRequest::getStatusLabel($session->tlr_status),
                'leave_status_raw' => $session->tlr_status,
                'leave_reason' => $session->tlr_reason ?? '',
                'leave_reason_type' => TeacherLeaveRequest::getReasonTypeLabel($session->tlr_reason_type),
                'leave_period' => ($session->tlr_start_date ? Carbon::parse($session->tlr_start_date)->format('d/m/Y') : '')
                    . ' - '
                    . ($session->tlr_end_date ? Carbon::parse($session->tlr_end_date)->format('d/m/Y') : ''),
                'created_at' => $session->tlrs_created_at
                    ? Carbon::parse($session->tlrs_created_at)->addHours(7)->format('d/m/Y H:i')
                    : '',
            ];
        })->toArray();

        return [
            'details' => $details,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiltered,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * Phase 103: Get first orders with successful lessons after payment
     * Returns paginated list of students who had successful lessons (acceptance_code IN (9,12))
     * after their first paid order via ordpay_pmethod_id=13
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $search
     * @return array
     */
    public function getFirstOrdersWithSuccessfulLessons(int $page = 1, int $perPage = 20, ?string $search = null, ?string $sortBy = null, string $sortDir = 'desc', bool $filterNullLesson = false, ?int $daysDifference = null, ?string $orderDateFrom = null, ?string $orderDateTo = null): array
    {
        // Raw SQL using CTEs for the complex query
        $sql = "
            WITH first_orders AS (
                SELECT 
                    o.order_id, 
                    o.order_user_id, 
                    o.order_item_count, 
                    o.order_net_amount, 
                    o.order_addedon, 
                    op.ordpay_datetime,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.order_user_id 
                        ORDER BY op.ordpay_datetime ASC, o.order_id ASC
                    ) AS rn
                FROM tbl_orders o
                INNER JOIN tbl_order_payments op 
                    ON o.order_id = op.ordpay_order_id
                WHERE 
                    op.ordpay_pmethod_id = 13
                    AND op.ordpay_amount > 0
                    AND o.order_net_amount > 0
                    AND op.ordpay_datetime >= '2026-01-04 17:00:00'
                    AND o.order_payment_status = 1
                    AND o.order_status = 2
            ),
            first_lessons AS (
                SELECT
                    fo.order_id,
                    ol.ordles_id,
                    ol.ordles_lesson_starttime,
                    ROW_NUMBER() OVER (
                        PARTITION BY fo.order_id
                        ORDER BY ol.ordles_lesson_starttime ASC, ol.ordles_id ASC
                    ) AS rn_lesson
                FROM first_orders fo
                INNER JOIN tbl_order_lessons ol 
                    ON ol.ordles_order_id = fo.order_id
                INNER JOIN tbl_order_lessons_extras ole
                    ON ole.ole_ordles_id = ol.ordles_id
                WHERE 
                    fo.rn = 1
                    AND ole.ole_acceptance_code IN (9,12)
            )
            SELECT 
                fo.order_id, 
                fo.order_user_id, 
                fo.order_item_count, 
                fo.order_net_amount, 
                fo.order_addedon,
                fl.ordles_lesson_starttime AS first_lesson_start_time,
                DATEDIFF(fl.ordles_lesson_starttime, fo.order_addedon) AS days_difference,
                TIMEDIFF(fl.ordles_lesson_starttime, fo.order_addedon) AS time_difference,
                u.user_first_name,
                u.user_last_name,
                u.user_email
            FROM first_orders fo
            LEFT JOIN first_lessons fl 
                ON fo.order_id = fl.order_id
                AND fl.rn_lesson = 1
            LEFT JOIN tbl_users u
                ON fo.order_user_id = u.user_id
            WHERE fo.rn = 1
        ";

        $bindings = [];

        // Add search filter
        if ($search) {
            $sql .= " AND (
                u.user_first_name LIKE ?
                OR u.user_last_name LIKE ?
                OR u.user_email LIKE ?
                OR CONCAT(u.user_first_name, ' ', u.user_last_name) LIKE ?
                OR CAST(fo.order_id AS CHAR) LIKE ?
                OR CAST(fo.order_user_id AS CHAR) LIKE ?
            )";
            $searchTerm = "%{$search}%";
            $bindings = array_merge($bindings, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Filter for NULL first lesson (cancelled orders)
        if ($filterNullLesson) {
            $sql .= " AND fl.ordles_lesson_starttime IS NULL";
        }

        // Phase 105: Filter by days difference (days since payment without class assignment)
        if ($daysDifference !== null) {
            $sql .= " AND DATEDIFF(fl.ordles_lesson_starttime, fo.order_addedon) >= ?";
            $bindings[] = $daysDifference;
        }

        // Phase 105: Filter by order date range (purchase date)
        if ($orderDateFrom) {
            $sql .= " AND fo.order_addedon >= ?";
            $bindings[] = $orderDateFrom . ' 00:00:00';
        }
        if ($orderDateTo) {
            $sql .= " AND fo.order_addedon <= ?";
            $bindings[] = $orderDateTo . ' 23:59:59';
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) as total FROM ({$sql} ORDER BY fo.order_addedon) AS sub";
        $totalResult = DB::connection('mysql')->select($countSql, $bindings);
        $total = $totalResult[0]->total ?? 0;

        // Determine sort column (whitelist allowed columns)
        $allowedSorts = [
            'order_item_count' => 'fo.order_item_count',
            'order_net_amount' => 'fo.order_net_amount',
            'order_addedon' => 'fo.order_addedon',
            'first_lesson_start_time' => 'fl.ordles_lesson_starttime',
            'days_difference' => 'days_difference',
        ];
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $orderColumn = $allowedSorts[$sortBy] ?? 'fo.order_addedon';

        // Add ordering and pagination
        $sql .= " ORDER BY {$orderColumn} {$sortDir}";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT ? OFFSET ?";
        $bindings[] = $perPage;
        $bindings[] = $offset;

        $results = DB::connection('mysql')->select($sql, $bindings);

        $lastPage = max(1, (int) ceil($total / $perPage));

        $data = collect($results)->map(function ($row) {
            $fullName = trim(($row->user_first_name ?? '') . ' ' . ($row->user_last_name ?? ''));
            return [
                'order_id' => $row->order_id,
                'order_user_id' => $row->order_user_id,
                'student_name' => $fullName ?: 'Unknown',
                'student_email' => $row->user_email ?? '',
                'order_item_count' => $row->order_item_count,
                'order_net_amount' => $row->order_net_amount,
                'order_addedon' => $row->order_addedon
                    ? Carbon::parse($row->order_addedon)->format('d/m/Y H:i')
                    : '',
                'first_lesson_start_time' => $row->first_lesson_start_time
                    ? Carbon::parse($row->first_lesson_start_time)->format('d/m/Y H:i')
                    : null,
                'days_difference' => $row->days_difference ?? null,
                'time_difference' => $row->time_difference ?? null,
            ];
        })->toArray();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    // ========================================
    // Phase 112: Trial Lessons Statistics Table
    // ========================================

    /**
     * Get trial lessons list with teacher feedback/assessment details
     * Used in the Revenue page (/revenue?program=speakwell)
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string|null $search Search keyword
     * @param string|null $sortBy Sort column
     * @param string $sortDir Sort direction (asc/desc)
     * @param string|null $startDate Filter start date (YYYY-MM-DD)
     * @param string|null $endDate Filter end date (YYYY-MM-DD)
     * @return array Paginated trial lessons data
     */
    public function getTrialLessonsList(int $page = 1, int $perPage = 20, ?string $search = null, ?string $sortBy = null, string $sortDir = 'desc', ?string $startDate = null, ?string $endDate = null): array
    {
        // Default date range: last 30 days
        if (!$startDate) {
            $startDate = now()->subDays(30)->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = now()->addDay()->format('Y-m-d');
        }

        // Convert to UTC timestamps for query (input is UTC+7 dates)
        $startDatetime = Carbon::parse($startDate)->startOfDay()->subHours(7)->format('Y-m-d H:i:s');
        $endDatetime = Carbon::parse($endDate)->startOfDay()->subHours(7)->format('Y-m-d H:i:s');

        $sql = "
            SELECT
                ol.ordles_id AS trial_id,
                ol.ordles_beneficiary_id AS trial_user_id,

                -- User info
                u.user_username AS trial_user_username,
                u.user_email AS trial_user_email,
                CONCAT(u.user_first_name, ' ', u.user_last_name) AS trial_user_fullname,

                -- Trial request date (UTC+7)
                DATE(CONVERT_TZ(o.order_addedon, '+00:00', '+07:00')) AS trial_request_date,

                -- Trial lesson date (UTC+7)
                DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) AS trial_date,

                -- Trial note
                ln.lesnote_content AS trial_note,

                -- Trial program name from feedback
                JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.name')) AS trial_program_name,

                -- Lesson status
                CASE ol.ordles_status
                    WHEN 1 THEN 'UNSCHEDULED'
                    WHEN 2 THEN 'SCHEDULED'
                    WHEN 3 THEN 'COMPLETED'
                    WHEN 4 THEN 'CANCELLED'
                    ELSE NULL
                END AS trial_status,

                -- Feedback date (UTC+7)
                DATE(CONVERT_TZ(tf.teafeed_created_at, '+00:00', '+07:00')) AS trial_feedback_date,

                -- Feedback language id
                tf.teafeed_lang_id AS trial_lang_id,

                -- Assessment content string
                CONCAT_WS(
                    '\\n',
                    CONCAT('- Name: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.name'))),
                    CONCAT('- Score: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.score'))),
                    CONCAT('- Level: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.level'))),
                    CONCAT('- Expected: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.expected'))),
                    CONCAT('- Lookup link: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.lookup_link'))),
                    CONCAT('- Suggested subject: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.name'))),
                    CONCAT('- Suggested duration: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.duration'))),
                    CONCAT('- Suggested package: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.package'))),
                    CONCAT('- Suggested pathway: ', JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.pathway'))),
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT('- ', jt.ass_name, ': ', jt.ass_value)
                            SEPARATOR '\\n'
                        )
                        FROM JSON_TABLE(
                            tf.teafeed_assessment_detail,
                            '$.assessment[*]'
                            COLUMNS (
                                ass_name  VARCHAR(255) PATH '$.name',
                                ass_value TEXT         PATH '$.value'
                            )
                        ) AS jt
                    )
                ) AS trial_feedback_content,

                -- Level from feedback
                JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.level')) AS trial_feedback_level,

                -- Suggested subject id
                CAST(
                    JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.subject'))
                    AS UNSIGNED
                ) AS trial_suggested_subject_id,

                -- Suggested subject name
                JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.suggestions.name')) AS trial_suggested_subject_name

            FROM tbl_order_lessons AS ol
            INNER JOIN tbl_orders AS o
                ON o.order_id = ol.ordles_order_id
            LEFT JOIN tbl_users AS u
                ON u.user_id = ol.ordles_beneficiary_id
            LEFT JOIN tbl_lesson_notes AS ln
                ON ln.lesnote_ordles_id = ol.ordles_id
            LEFT JOIN tbl_teacher_feedbacks AS tf
                ON tf.teafeed_learner_id = ol.ordles_beneficiary_id
               AND tf.teafeed_record_id = ol.ordles_id
               AND tf.teafeed_record_type = 1
               AND tf.teafeed_type = 1
            WHERE
                ol.ordles_tlang_id = (
                    SELECT conf_val
                    FROM tbl_configurations
                    WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID'
                    LIMIT 1
                )
                AND FIND_IN_SET(ol.ordles_status, '1,2,3,4') > 0
                AND ol.ordles_lesson_starttime >= ?
                AND ol.ordles_lesson_starttime < ?
        ";

        $bindings = [$startDatetime, $endDatetime];

        // Search filter
        if ($search) {
            $sql .= " AND (
                u.user_username LIKE ?
                OR u.user_email LIKE ?
                OR u.user_first_name LIKE ?
                OR u.user_last_name LIKE ?
                OR CONCAT(u.user_first_name, ' ', u.user_last_name) LIKE ?
                OR CAST(ol.ordles_id AS CHAR) LIKE ?
                OR CAST(ol.ordles_beneficiary_id AS CHAR) LIKE ?
            )";
            $searchTerm = "%{$search}%";
            $bindings = array_merge($bindings, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Count total for pagination
        $countSql = "SELECT COUNT(*) as total FROM ({$sql} ORDER BY o.order_addedon ASC) AS sub";
        $totalResult = DB::connection('mysql')->select($countSql, $bindings);
        $total = $totalResult[0]->total ?? 0;

        // Sort column whitelist
        $allowedSorts = [
            'trial_id' => 'ol.ordles_id',
            'trial_user_id' => 'ol.ordles_beneficiary_id',
            'trial_request_date' => 'o.order_addedon',
            'trial_date' => 'ol.ordles_lesson_starttime',
            'trial_status' => 'ol.ordles_status',
            'trial_feedback_date' => 'tf.teafeed_created_at',
            'trial_feedback_level' => 'trial_feedback_level',
        ];
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $orderColumn = $allowedSorts[$sortBy] ?? 'o.order_addedon';

        // Pagination
        $sql .= " ORDER BY {$orderColumn} {$sortDir}";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT ? OFFSET ?";
        $bindings[] = $perPage;
        $bindings[] = $offset;

        $results = DB::connection('mysql')->select($sql, $bindings);

        $lastPage = max(1, (int) ceil($total / $perPage));

        $data = collect($results)->map(function ($row) {
            return [
                'trial_id' => $row->trial_id,
                'trial_user_id' => $row->trial_user_id,
                'trial_user_username' => $row->trial_user_username ?? '',
                'trial_user_email' => $row->trial_user_email ?? '',
                'trial_user_fullname' => $row->trial_user_fullname ?? 'Unknown',
                'trial_request_date' => $row->trial_request_date ?? '',
                'trial_date' => $row->trial_date ?? '',
                'trial_note' => $row->trial_note ?? '',
                'trial_program_name' => $row->trial_program_name ?? '',
                'trial_status' => $row->trial_status ?? '',
                'trial_feedback_date' => $row->trial_feedback_date ?? '',
                'trial_lang_id' => $row->trial_lang_id ?? null,
                'trial_feedback_content' => $row->trial_feedback_content ?? '',
                'trial_feedback_level' => $row->trial_feedback_level ?? '',
                'trial_suggested_subject_id' => $row->trial_suggested_subject_id ?? null,
                'trial_suggested_subject_name' => $row->trial_suggested_subject_name ?? '',
            ];
        })->toArray();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    // ========================================
    // Phase 113: Teacher Availability Grid
    // ========================================

    /**
     * Native English speaker country codes
     * US, GB, UK, CA, AU, NZ, IE, ZA → NN
     */
    public const NATIVE_ENGLISH_COUNTRY_CODES = ['US', 'GB', 'UK', 'CA', 'AU', 'NZ', 'IE', 'ZA'];

    /**
     * Phase 207: Native 1 country codes (primary native English: US, GB, UK, CA, AU)
     */
    public const NATIVE_1_COUNTRY_CODES = ['US', 'GB', 'UK', 'CA', 'AU'];

    /**
     * Phase 207: Native 2 country codes (other native English: NZ, IE, ZA)
     */
    public const NATIVE_2_COUNTRY_CODES = ['NZ', 'IE', 'ZA'];

    /**
     * Phase 207: Determine teacher nationality type from country code.
     * Returns Vietnamese, Philippines, Native 1, Native 2, or Khác.
     */
    public static function getTeacherNationalityType(string $countryCode): string
    {
        $cc = strtoupper(trim($countryCode));
        if ($cc === 'VN') return 'Vietnamese';
        if ($cc === 'PH') return 'Philippines';
        if (in_array($cc, self::NATIVE_1_COUNTRY_CODES)) return 'Native 1';
        if (in_array($cc, self::NATIVE_2_COUNTRY_CODES)) return 'Native 2';
        return 'Khác';
    }

    /**
     * Phase 207: Map MySQL DAYOFWEEK to Vietnamese short label.
     * MySQL: 1=Sun, 2=Mon, 3=Tue, 4=Wed, 5=Thu, 6=Fri, 7=Sat
     */
    public static function getDayOfWeekLabel(int $dow): string
    {
        return match ($dow) {
            1 => 'CN',
            2 => 'T2',
            3 => 'T3',
            4 => 'T4',
            5 => 'T5',
            6 => 'T6',
            7 => 'T7',
            default => '??',
        };
    }

    /**
     * Get Speakwell teacher IDs from tbl_user_teach_languages
     * Only teachers whose utlang_tlang_id is in SPEAKWELL_SUBJECT_IDS
     *
     * @param bool $trialOnly If true, only return teachers who can teach trial (utlang_tlang_id = 533)
     * @return array Array of teacher user IDs
     */
    public function getSpeakwellTeacherIds(bool $trialOnly = false): array
    {
        $subjectIds = $trialOnly ? [533] : $this->getActiveSubjectIds();

        return DB::connection('mysql')
            ->table('tbl_user_teach_languages')
            ->whereIn('utlang_tlang_id', $subjectIds)
            ->distinct()
            ->pluck('utlang_user_id')
            ->toArray();
    }

    /**
     * Get Speakwell teacher IDs excluding trial teachers
     * Returns all Speakwell teachers minus those who can teach trial (utlang_tlang_id = 533)
     *
     * @return array Array of teacher user IDs
     */
    public function getSpeakwellTeacherIdsExcludingTrial(): array
    {
        $allIds = $this->getSpeakwellTeacherIds(false);
        $trialIds = $this->getSpeakwellTeacherIds(true);

        return array_values(array_diff($allIds, $trialIds));
    }

    /**
     * Filter teacher IDs by qualification stats from tbl_teacher_stats.
     * Teachers with any testat_ field = 0 are not qualified to appear in search.
     *
     * @param array $teacherIds
     * @return array Filtered array of qualified teacher IDs
     */
    public function filterQualifiedTeachers(array $teacherIds): array
    {
        if (empty($teacherIds)) return [];

        return DB::connection('mysql')
            ->table('tbl_teacher_stats')
            ->whereIn('testat_user_id', $teacherIds)
            ->where('testat_preference', '>', 0)
            ->where('testat_qualification', '>', 0)
            ->where('testat_teachlang', '>', 0)
            ->where('testat_speaklang', '>', 0)
            ->where('testat_availability', '>', 0)
            ->pluck('testat_user_id')
            ->toArray();
    }

    /**
     * Get teacher info (name, email, trial capability) for given teacher IDs
     *
     * @param array $teacherIds
     * @return array Keyed by user_id
     */
    public function getTeacherInfo(array $teacherIds): array
    {
        if (empty($teacherIds)) return [];

        // Get teacher names with country info
        $teachers = DB::connection('mysql')
            ->table('tbl_users')
            ->leftJoin('tbl_countries', 'tbl_countries.country_id', '=', 'tbl_users.user_country_id')
            ->whereIn('user_id', $teacherIds)
            ->whereNull('user_deleted')
            ->where('user_is_teacher', 1)
            ->select('user_id', 'user_first_name', 'user_last_name', 'user_email', 'user_username', 'tbl_countries.country_code', 'tbl_countries.country_identifier')
            ->get()
            ->keyBy('user_id');

        // Get trial capability (utlang_tlang_id = 533)
        $trialTeacherIds = DB::connection('mysql')
            ->table('tbl_user_teach_languages')
            ->whereIn('utlang_user_id', $teacherIds)
            ->where('utlang_tlang_id', 533)
            ->distinct()
            ->pluck('utlang_user_id')
            ->toArray();

        $result = [];
        foreach ($teachers as $id => $teacher) {
            $countryCode = strtoupper($teacher->country_code ?? '');
            $teacherType = 'N/A';
            if ($countryCode === 'VN') {
                $teacherType = 'VN';
            } elseif ($countryCode === 'PH') {
                $teacherType = 'PHIL';
            } elseif (in_array($countryCode, self::NATIVE_ENGLISH_COUNTRY_CODES)) {
                $teacherType = 'NN';
            }

            $result[$id] = [
                'id' => $teacher->user_id,
                'name' => trim($teacher->user_first_name . ' ' . $teacher->user_last_name),
                'email' => $teacher->user_email ?? '',
                'username' => $teacher->user_username ?? '',
                'can_teach_trial' => in_array($teacher->user_id, $trialTeacherIds),
                'country_code' => $countryCode,
                'country_identifier' => $teacher->country_identifier ?? '',
                'teacher_type' => $teacherType,
            ];
        }

        return $result;
    }

    /**
     * Get teacher availability grid for a given week
     * Shows available teacher count per time slot (30-min intervals) per day
     *
     * @param string $weekStart Start date (YYYY-MM-DD) in UTC+7
     * @param string|null $teacherSearch Teacher name/email search filter
     * @param string $trialFilter Trial filter: 'all', 'exclude', 'only'
     * @param array $timeSlotFilter Only show certain time slots (e.g., ['07:00', '07:30'])
     * @return array Grid data with teachers, days, time_slots, and availability matrix
     */
    public function getTeacherAvailabilityGrid(
        string $weekStart,
        ?string $teacherSearch = null,
        string $trialFilter = 'all',
        array $timeSlotFilter = [],
        string $slotMode = 'odd',
        string $teacherType = ''
    ): array {
        // Parse the week date range (UTC+7)
        $startDate = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Convert to UTC for DB queries (UTC+7 → UTC: subtract 7 hours)
        $startUtc = $startDate->copy()->subHours(7)->format('Y-m-d H:i:s');
        $endUtc = $endDate->copy()->subHours(7)->format('Y-m-d H:i:s');

        // 1. Get Speakwell teacher IDs
        if ($trialFilter === 'exclude') {
            $teacherIds = $this->getSpeakwellTeacherIdsExcludingTrial();
        } elseif ($trialFilter === 'only') {
            $teacherIds = $this->getSpeakwellTeacherIds(true);
        } else {
            $teacherIds = $this->getSpeakwellTeacherIds(false);
        }

        if (empty($teacherIds)) {
            return $this->emptyAvailabilityGrid($startDate, $endDate);
        }

        // 1b. Filter by qualification stats (tbl_teacher_stats)
        $teacherIds = $this->filterQualifiedTeachers($teacherIds);

        if (empty($teacherIds)) {
            return $this->emptyAvailabilityGrid($startDate, $endDate);
        }

        // 2. Get teacher info
        $teacherInfo = $this->getTeacherInfo($teacherIds);

        // Filter by active teachers only (those who exist in tbl_users and not deleted)
        $teacherIds = array_keys($teacherInfo);

        if (empty($teacherIds)) {
            return $this->emptyAvailabilityGrid($startDate, $endDate);
        }

        // 3. Apply teacher search filter
        if ($teacherSearch) {
            $searchLower = mb_strtolower($teacherSearch);
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($searchLower) {
                return str_contains(mb_strtolower($t['name']), $searchLower)
                    || str_contains(mb_strtolower($t['email']), $searchLower)
                    || str_contains(mb_strtolower($t['username']), $searchLower);
            });
            $teacherIds = array_keys($teacherInfo);
        }

        // 3b. Apply teacher type filter (VN, PHIL, NN)
        if ($teacherType && in_array($teacherType, ['VN', 'PHIL', 'NN'])) {
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($teacherType) {
                return ($t['teacher_type'] ?? '') === $teacherType;
            });
            $teacherIds = array_keys($teacherInfo);
        }

        if (empty($teacherIds)) {
            return $this->emptyAvailabilityGrid($startDate, $endDate);
        }

        // 4. Get availability slots for these teachers
        $availabilitySlots = DB::connection('mysql')
            ->table('tbl_availability')
            ->whereIn('avail_user_id', $teacherIds)
            ->where('avail_starttime', '<', $endUtc)
            ->where('avail_endtime', '>', $startUtc)
            ->select('avail_user_id', 'avail_starttime', 'avail_endtime')
            ->get();

        // 5. Get booked lessons (status = SCHEDULED or COMPLETED)
        // Phase 119: For past dates, also count COMPLETED (status=3) as busy
        $bookedLessons = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
            ->whereIn('ol.ordles_teacher_id', $teacherIds)
            ->whereIn('ol.ordles_status', [2, 3]) // SCHEDULED or COMPLETED
            ->where('o.order_payment_status', 1) // PAID
            ->where('o.order_status', 2) // COMPLETED (confirmed)
            ->where('ol.ordles_lesson_starttime', '<', $endUtc)
            ->where('ol.ordles_lesson_endtime', '>', $startUtc)
            ->select('ol.ordles_teacher_id', 'ol.ordles_lesson_starttime', 'ol.ordles_lesson_endtime', 'ol.ordles_status')
            ->get();

        // 6. Get booked group classes (status = SCHEDULED)
        $bookedClasses = DB::connection('mysql')
            ->table('tbl_group_classes')
            ->whereIn('grpcls_teacher_id', $teacherIds)
            ->where('grpcls_status', 1) // SCHEDULED
            ->where('grpcls_start_datetime', '<', $endUtc)
            ->where('grpcls_end_datetime', '>', $startUtc)
            ->select('grpcls_teacher_id', 'grpcls_start_datetime', 'grpcls_end_datetime')
            ->get();

        // Phase 119: Determine today's date in UTC+7 for past-date logic
        $todayLocal = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        // 7. Build per-teacher busy intervals (with status for lessons)
        // teacher_id => [[start_utc, end_utc, ordles_status], ...]
        $busyIntervals = [];
        foreach ($bookedLessons as $lesson) {
            $busyIntervals[$lesson->ordles_teacher_id][] = [
                strtotime($lesson->ordles_lesson_starttime),
                strtotime($lesson->ordles_lesson_endtime),
                (int) $lesson->ordles_status, // 2=SCHEDULED, 3=COMPLETED
            ];
        }
        foreach ($bookedClasses as $class) {
            $busyIntervals[$class->grpcls_teacher_id][] = [
                strtotime($class->grpcls_start_datetime),
                strtotime($class->grpcls_end_datetime),
                0, // group class, always counts as busy
            ];
        }

        // 8. Build per-teacher available intervals
        $availIntervals = []; // teacher_id => [[start_utc, end_utc], ...]
        foreach ($availabilitySlots as $slot) {
            $availIntervals[$slot->avail_user_id][] = [
                strtotime($slot->avail_starttime),
                strtotime($slot->avail_endtime),
            ];
        }

        // 9. Generate time grid: days and 30-min slots (UTC+7)
        $days = [];
        $dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        for ($d = 0; $d < 7; $d++) {
            $day = $startDate->copy()->addDays($d);
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $dayLabels[$d] . ' ' . $day->format('d/m'),
                'day_name' => $dayLabels[$d],
            ];
        }

        // Time slots generation based on mode
        if ($slotMode === 'odd') {
            // Odd mode: Get distinct time slots from actual lesson data (ordles_teacher_starttime)
            $allTimeSlots = $this->getOddTimeSlots($startUtc, $endUtc, $teacherIds);
        } else {
            // Even mode: Standard 30-min slots from 06:00 to 23:30 UTC+7
            $allTimeSlots = [];
            for ($h = 6; $h <= 23; $h++) {
                $allTimeSlots[] = sprintf('%02d:00', $h);
                $allTimeSlots[] = sprintf('%02d:30', $h);
            }
        }

        // Apply time slot filter if provided
        $timeSlots = $allTimeSlots;
        if (!empty($timeSlotFilter)) {
            $timeSlots = array_values(array_intersect($allTimeSlots, $timeSlotFilter));
        }

        // 10. For each day × time slot, compute available teachers
        $grid = [];
        $teacherDetail = []; // For detailed view: day -> slot -> teacher list

        foreach ($days as $dayInfo) {
            $dateStr = $dayInfo['date'];
            $grid[$dateStr] = [];
            $teacherDetail[$dateStr] = [];

            // Phase 119: Check if this day is in the past (before today in UTC+7)
            $isPastDate = $dateStr < $todayLocal;

            foreach ($timeSlots as $slot) {
                // Parse slot start/end in UTC+7
                $slotStartLocal = Carbon::parse("{$dateStr} {$slot}", 'Asia/Ho_Chi_Minh');
                $slotEndLocal = $slotStartLocal->copy()->addMinutes(30);

                // Convert to UTC timestamps for comparison
                $slotStartUtc = $slotStartLocal->copy()->subHours(7)->timestamp;
                $slotEndUtc = $slotEndLocal->copy()->subHours(7)->timestamp;

                $availableTeachers = [];
                $totalSlotsCount = 0;

                foreach ($teacherIds as $tid) {
                    // Check if teacher has availability covering this slot
                    $hasAvailability = false;
                    foreach ($availIntervals[$tid] ?? [] as $interval) {
                        if ($interval[0] <= $slotStartUtc && $interval[1] >= $slotEndUtc) {
                            $hasAvailability = true;
                            break;
                        }
                    }

                    if (!$hasAvailability) continue;

                    $totalSlotsCount++;

                    // Check if teacher is busy during this slot
                    // Phase 119: For past dates, include COMPLETED (status=3) as busy
                    // For today/future, only SCHEDULED (status=2) and group classes count
                    $isBusy = false;
                    foreach ($busyIntervals[$tid] ?? [] as $busy) {
                        if ($busy[0] < $slotEndUtc && $busy[1] > $slotStartUtc) {
                            // $busy[2]: 0=group class (always busy), 2=SCHEDULED, 3=COMPLETED
                            if ($busy[2] === 0 || $busy[2] === 2) {
                                // Group classes and SCHEDULED always count as busy
                                $isBusy = true;
                                break;
                            }
                            if ($isPastDate && $busy[2] === 3) {
                                // COMPLETED lessons count as busy only for past dates
                                $isBusy = true;
                                break;
                            }
                        }
                    }

                    if (!$isBusy) {
                        $availableTeachers[] = $tid;
                    }
                }

                $grid[$dateStr][$slot] = [
                    'available' => count($availableTeachers),
                    'total_slots' => $totalSlotsCount,
                ];
                $teacherDetail[$dateStr][$slot] = $availableTeachers;
            }
        }

        return [
            'teachers' => array_values($teacherInfo),
            'teacher_count' => count($teacherIds),
            'days' => $days,
            'time_slots' => $timeSlots,
            'grid' => $grid,
            'teacher_detail' => $teacherDetail,
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'week_label' => $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y'),
        ];
    }

    /**
     * Get slot detail: list of available teachers for a specific day and time slot
     *
     * @param string $date Date (YYYY-MM-DD) in UTC+7
     * @param string $timeSlot Time slot (HH:MM) in UTC+7
     * @param string $trialFilter Trial filter: 'all', 'exclude', 'only'
     * @param string|null $teacherSearch Teacher name search filter
     * @param string $teacherType Filter by teacher type (VN, PHIL, NN)
     * @return array List of available teachers
     */
    public function getTeacherAvailabilitySlotDetail(
        string $date,
        string $timeSlot,
        string $trialFilter = 'all',
        ?string $teacherSearch = null,
        string $teacherType = ''
    ): array {
        // Compute slot start/end in UTC
        $slotStartLocal = Carbon::parse("{$date} {$timeSlot}", 'Asia/Ho_Chi_Minh');
        $slotEndLocal = $slotStartLocal->copy()->addMinutes(30);
        $slotStartUtc = $slotStartLocal->copy()->subHours(7)->format('Y-m-d H:i:s');
        $slotEndUtc = $slotEndLocal->copy()->subHours(7)->format('Y-m-d H:i:s');

        // Get Speakwell teacher IDs
        if ($trialFilter === 'exclude') {
            $teacherIds = $this->getSpeakwellTeacherIdsExcludingTrial();
        } elseif ($trialFilter === 'only') {
            $teacherIds = $this->getSpeakwellTeacherIds(true);
        } else {
            $teacherIds = $this->getSpeakwellTeacherIds(false);
        }
        if (empty($teacherIds)) return [];

        // Filter by qualification stats (tbl_teacher_stats)
        $teacherIds = $this->filterQualifiedTeachers($teacherIds);
        if (empty($teacherIds)) return [];

        $teacherInfo = $this->getTeacherInfo($teacherIds);
        $teacherIds = array_keys($teacherInfo);

        // Apply search
        if ($teacherSearch) {
            $searchLower = mb_strtolower($teacherSearch);
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($searchLower) {
                return str_contains(mb_strtolower($t['name']), $searchLower)
                    || str_contains(mb_strtolower($t['email']), $searchLower);
            });
            $teacherIds = array_keys($teacherInfo);
        }

        // Apply teacher type filter
        if ($teacherType && in_array($teacherType, ['VN', 'PHIL', 'NN'])) {
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($teacherType) {
                return ($t['teacher_type'] ?? '') === $teacherType;
            });
            $teacherIds = array_keys($teacherInfo);
        }

        if (empty($teacherIds)) return [];

        // Get availability
        $availTeachers = DB::connection('mysql')
            ->table('tbl_availability')
            ->whereIn('avail_user_id', $teacherIds)
            ->where('avail_starttime', '<=', $slotStartUtc)
            ->where('avail_endtime', '>=', $slotEndUtc)
            ->distinct()
            ->pluck('avail_user_id')
            ->toArray();

        if (empty($availTeachers)) return [];

        // Get user_trial_enabled from tbl_user_settings
        $trialSettings = DB::connection('mysql')
            ->table('tbl_user_settings')
            ->whereIn('user_id', $availTeachers)
            ->pluck('user_trial_enabled', 'user_id')
            ->toArray();

        // Get lesson details for teachers who have booked lessons at this slot
        // Uses LEFT JOIN approach similar to the reference SQL in phase_117
        $lessonDetails = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->leftJoin('tbl_users as student', 'student.user_id', '=', 'ol.ordles_beneficiary_id')
            ->leftJoin('tbl_teach_languages as tl', 'tl.tlang_id', '=', 'ol.ordles_tlang_id')
            ->whereIn('ol.ordles_teacher_id', $availTeachers)
            ->whereIn('ol.ordles_status', [2, 3]) // SCHEDULED or COMPLETED
            ->where('ol.ordles_lesson_starttime', '<=', $slotStartUtc)
            ->where('ol.ordles_lesson_endtime', '>=', $slotEndUtc)
            ->where(function ($q) {
                $q->whereIn('ol.ordles_tlang_id', $this->getActiveSubjectIds())
                  ->orWhereNull('ol.ordles_tlang_id');
            })
            ->select(
                'ol.ordles_teacher_id',
                'ol.ordles_id as lesson_id',
                'ol.ordles_beneficiary_id as student_id',
                DB::raw("CONCAT(IFNULL(student.user_last_name, ''), CASE WHEN student.user_last_name IS NOT NULL AND student.user_first_name IS NOT NULL THEN ' ' ELSE '' END, IFNULL(student.user_first_name, '')) AS student_full_name"),
                'student.user_username as student_username',
                'student.user_email as student_email',
                'tl.tlang_identifier',
                'ol.ordles_status'
            )
            ->get()
            ->keyBy('ordles_teacher_id');

        // Get busy teachers from group classes
        $busyFromClasses = DB::connection('mysql')
            ->table('tbl_group_classes')
            ->whereIn('grpcls_teacher_id', $availTeachers)
            ->where('grpcls_status', 1) // SCHEDULED
            ->where('grpcls_start_datetime', '<', $slotEndUtc)
            ->where('grpcls_end_datetime', '>', $slotStartUtc)
            ->distinct()
            ->pluck('grpcls_teacher_id')
            ->toArray();

        // Build result with all teachers who have availability at this slot
        $result = [];
        foreach ($availTeachers as $tid) {
            if (!isset($teacherInfo[$tid])) continue;

            $info = $teacherInfo[$tid];
            $info['teacher_trial'] = $trialSettings[$tid] ?? 0;

            // Check if teacher has a booked lesson at this slot
            $lesson = $lessonDetails[$tid] ?? null;
            $isBusyFromClass = in_array($tid, $busyFromClasses);

            if ($lesson) {
                $info['lesson_id'] = $lesson->lesson_id;
                $info['student_id'] = $lesson->student_id;
                $info['student_full_name'] = trim($lesson->student_full_name ?? '');
                $info['student_username'] = $lesson->student_username ?? '';
                $info['student_email'] = $lesson->student_email ?? '';
                $info['tlang_identifier'] = $lesson->tlang_identifier ?? '';
                $info['ordles_status'] = $lesson->ordles_status;
                $info['is_busy'] = true;
            } elseif ($isBusyFromClass) {
                $info['lesson_id'] = null;
                $info['student_id'] = null;
                $info['student_full_name'] = '';
                $info['student_username'] = '';
                $info['student_email'] = '';
                $info['tlang_identifier'] = '';
                $info['ordles_status'] = null;
                $info['is_busy'] = true;
            } else {
                $info['lesson_id'] = null;
                $info['student_id'] = null;
                $info['student_full_name'] = '';
                $info['student_username'] = '';
                $info['student_email'] = '';
                $info['tlang_identifier'] = '';
                $info['ordles_status'] = null;
                $info['is_busy'] = false;
            }

            $result[] = $info;
        }

        // Sort: free teachers first, then busy; within each group sort by lesson_id (busy teachers with lesson first)
        usort($result, function ($a, $b) {
            // Free teachers first
            if ($a['is_busy'] !== $b['is_busy']) {
                return $a['is_busy'] ? 1 : -1;
            }
            // Among busy teachers, those with lessons first
            if ($a['is_busy'] && $b['is_busy']) {
                if ($a['lesson_id'] && !$b['lesson_id']) return -1;
                if (!$a['lesson_id'] && $b['lesson_id']) return 1;
            }
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Get odd time slots: distinct HH:MM patterns from actual lesson data
     * Uses ordles_teacher_starttime from tbl_order_lessons (converted UTC → UTC+7)
     * Falls back to ordles_lesson_starttime if teacher start times are mostly NULL
     *
     * @param string $startUtc Window start in UTC
     * @param string $endUtc Window end in UTC
     * @param array $teacherIds Speakwell teacher IDs
     * @return array Sorted array of time slot strings (HH:MM)
     */
    public function getOddTimeSlots(string $startUtc, string $endUtc, array $teacherIds): array
    {
        if (empty($teacherIds)) return [];

        // Try ordles_teacher_starttime first, fall back to ordles_lesson_starttime
        $timeSlots = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
            ->whereIn('ol.ordles_teacher_id', $teacherIds)
            ->whereIn('ol.ordles_status', [2, 3]) // SCHEDULED or COMPLETED
            ->where('o.order_payment_status', 1)
            ->where('o.order_status', 2)
            ->whereNotNull('ol.ordles_teacher_starttime')
            ->where('ol.ordles_teacher_starttime', '>=', Carbon::now()->subMonths(3)->format('Y-m-d H:i:s'))
            ->selectRaw("DISTINCT DATE_FORMAT(DATE_ADD(ol.ordles_teacher_starttime, INTERVAL 7 HOUR), '%H:%i') as time_slot")
            ->pluck('time_slot')
            ->toArray();

        // If not enough data from teacher start times, use lesson start times
        if (count($timeSlots) < 5) {
            $timeSlots = DB::connection('mysql')
                ->table('tbl_order_lessons as ol')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
                ->whereIn('ol.ordles_teacher_id', $teacherIds)
                ->whereIn('ol.ordles_status', [2, 3])
                ->where('o.order_payment_status', 1)
                ->where('o.order_status', 2)
                ->whereNotNull('ol.ordles_lesson_starttime')
                ->where('ol.ordles_lesson_starttime', '>=', Carbon::now()->subMonths(3)->format('Y-m-d H:i:s'))
                ->selectRaw("DISTINCT DATE_FORMAT(DATE_ADD(ol.ordles_lesson_starttime, INTERVAL 7 HOUR), '%H:%i') as time_slot")
                ->pluck('time_slot')
                ->toArray();
        }

        // Round to nearest 5 minutes and deduplicate
        $rounded = [];
        foreach ($timeSlots as $ts) {
            $parts = explode(':', $ts);
            $h = (int) $parts[0];
            $m = (int) $parts[1];
            // Round to nearest 5 minutes
            $m = (int) round($m / 5) * 5;
            if ($m >= 60) {
                $m = 0;
                $h++;
            }
            if ($h >= 6 && $h <= 23) {
                $rounded[] = sprintf('%02d:%02d', $h, $m);
            }
        }

        $rounded = array_unique($rounded);
        sort($rounded);

        // If still empty, fall back to standard even slots
        if (empty($rounded)) {
            for ($h = 6; $h <= 23; $h++) {
                $rounded[] = sprintf('%02d:00', $h);
                $rounded[] = sprintf('%02d:30', $h);
            }
        }

        return array_values($rounded);
    }

    /**
     * Search available teachers for a specific schedule pattern
     * E.g., Find teachers available at 19:10 on Tuesday and Thursday from 1/4/2026
     *
     * @param string $time Time slot (HH:MM) in UTC+7
     * @param array $daysOfWeek Day numbers (1=Mon, 2=Tue, ..., 7=Sun)
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $trialFilter Trial filter: 'all', 'exclude', 'only'
     * @param string $teacherType Filter by teacher type
     * @param string|null $teacherSearch Search by name/email
     * @return array List of available teachers with schedule details
     */
    public function searchTeacherSchedule(
        string $time,
        array $daysOfWeek,
        string $startDate,
        string $trialFilter = 'all',
        string $teacherType = '',
        ?string $teacherSearch = null
    ): array {
        // Parse start date and determine the first 4 weeks of dates to check
        $start = Carbon::parse($startDate);
        $checkDates = [];
        $current = $start->copy();
        $endCheck = $start->copy()->addWeeks(4);

        while ($current->lte($endCheck)) {
            // Carbon: 1=Mon, 2=Tue, ..., 7=Sun (ISO)
            if (in_array($current->dayOfWeekIso, $daysOfWeek)) {
                $checkDates[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }

        if (empty($checkDates)) return [];

        // Get teacher IDs
        if ($trialFilter === 'exclude') {
            $teacherIds = $this->getSpeakwellTeacherIdsExcludingTrial();
        } elseif ($trialFilter === 'only') {
            $teacherIds = $this->getSpeakwellTeacherIds(true);
        } else {
            $teacherIds = $this->getSpeakwellTeacherIds(false);
        }
        if (empty($teacherIds)) return [];

        // Filter by qualification stats (tbl_teacher_stats)
        $teacherIds = $this->filterQualifiedTeachers($teacherIds);
        if (empty($teacherIds)) return [];

        $teacherInfo = $this->getTeacherInfo($teacherIds);
        $teacherIds = array_keys($teacherInfo);

        // Apply search filter
        if ($teacherSearch) {
            $searchLower = mb_strtolower($teacherSearch);
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($searchLower) {
                return str_contains(mb_strtolower($t['name']), $searchLower)
                    || str_contains(mb_strtolower($t['email']), $searchLower);
            });
            $teacherIds = array_keys($teacherInfo);
        }

        // Apply teacher type filter
        if ($teacherType && in_array($teacherType, ['VN', 'PHIL', 'NN'])) {
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($teacherType) {
                return ($t['teacher_type'] ?? '') === $teacherType;
            });
            $teacherIds = array_keys($teacherInfo);
        }

        if (empty($teacherIds)) return [];

        // For each check date, find available teachers at the specified time
        $teacherAvailCountMap = []; // teacher_id => count of dates available
        $totalDates = count($checkDates);

        // Phase 119: Determine today for past-date logic
        $todayLocal = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        foreach ($checkDates as $dateStr) {
            $slotStartLocal = Carbon::parse("{$dateStr} {$time}", 'Asia/Ho_Chi_Minh');
            $slotEndLocal = $slotStartLocal->copy()->addMinutes(30);
            $slotStartUtc = $slotStartLocal->copy()->subHours(7)->format('Y-m-d H:i:s');
            $slotEndUtc = $slotEndLocal->copy()->subHours(7)->format('Y-m-d H:i:s');

            // Phase 119: For past dates, also count COMPLETED lessons as busy
            $isPastDate = $dateStr < $todayLocal;
            $lessonStatuses = $isPastDate ? [2, 3] : [2];

            // Get teachers with availability
            $availTeachers = DB::connection('mysql')
                ->table('tbl_availability')
                ->whereIn('avail_user_id', $teacherIds)
                ->where('avail_starttime', '<=', $slotStartUtc)
                ->where('avail_endtime', '>=', $slotEndUtc)
                ->distinct()
                ->pluck('avail_user_id')
                ->toArray();

            if (empty($availTeachers)) continue;

            // Get busy teachers
            $busyFromLessons = DB::connection('mysql')
                ->table('tbl_order_lessons as ol')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
                ->whereIn('ol.ordles_teacher_id', $availTeachers)
                ->whereIn('ol.ordles_status', $lessonStatuses)
                ->where('o.order_payment_status', 1)
                ->where('o.order_status', 2)
                ->where('ol.ordles_lesson_starttime', '<', $slotEndUtc)
                ->where('ol.ordles_lesson_endtime', '>', $slotStartUtc)
                ->distinct()
                ->pluck('ol.ordles_teacher_id')
                ->toArray();

            $busyFromClasses = DB::connection('mysql')
                ->table('tbl_group_classes')
                ->whereIn('grpcls_teacher_id', $availTeachers)
                ->where('grpcls_status', 1)
                ->where('grpcls_start_datetime', '<', $slotEndUtc)
                ->where('grpcls_end_datetime', '>', $slotStartUtc)
                ->distinct()
                ->pluck('grpcls_teacher_id')
                ->toArray();

            $busyIds = array_unique(array_merge($busyFromLessons, $busyFromClasses));
            $freeIds = array_diff($availTeachers, $busyIds);

            foreach ($freeIds as $tid) {
                if (!isset($teacherAvailCountMap[$tid])) {
                    $teacherAvailCountMap[$tid] = 0;
                }
                $teacherAvailCountMap[$tid]++;
            }
        }

        // Build result: teachers who are available on ALL checked dates
        $result = [];
        foreach ($teacherAvailCountMap as $tid => $count) {
            if (isset($teacherInfo[$tid])) {
                $info = $teacherInfo[$tid];
                $info['available_dates'] = $count;
                $info['total_dates'] = $totalDates;
                $info['availability_rate'] = $totalDates > 0 ? round(($count / $totalDates) * 100) : 0;
                $result[] = $info;
            }
        }

        // Sort: teachers available on ALL dates first, then by availability count desc
        usort($result, function ($a, $b) {
            if ($b['available_dates'] !== $a['available_dates']) {
                return $b['available_dates'] - $a['available_dates'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Search teachers available across multiple time slot patterns (multi-slot).
     * Each slot is a { time, day } pair. Checks 4 weeks from start_date.
     * Teachers must be available at ALL slot-date combos to rank highly.
     *
     * @param array $slots Array of ['time' => 'HH:MM', 'day' => int(1-7 ISO)]
     * @param string $startDate Start date (Y-m-d)
     * @param string $trialFilter Trial filter: 'all', 'exclude', 'only'
     * @param string $teacherType
     * @param string|null $teacherSearch
     * @return array
     */
    public function searchTeacherScheduleMultiSlot(
        array $slots,
        string $startDate,
        string $trialFilter = 'all',
        string $teacherType = '',
        ?string $teacherSearch = null
    ): array {
        $start = Carbon::parse($startDate);
        $endCheck = $start->copy()->addWeeks(4);

        // Build list of (date, time) checks from all slots
        $dateTimeChecks = [];
        $current = $start->copy();
        while ($current->lte($endCheck)) {
            foreach ($slots as $slot) {
                if ($current->dayOfWeekIso === $slot['day']) {
                    $dateTimeChecks[] = [
                        'date' => $current->format('Y-m-d'),
                        'time' => $slot['time'],
                    ];
                }
            }
            $current->addDay();
        }

        if (empty($dateTimeChecks)) return [];

        // Get teacher IDs
        if ($trialFilter === 'exclude') {
            $teacherIds = $this->getSpeakwellTeacherIdsExcludingTrial();
        } elseif ($trialFilter === 'only') {
            $teacherIds = $this->getSpeakwellTeacherIds(true);
        } else {
            $teacherIds = $this->getSpeakwellTeacherIds(false);
        }
        if (empty($teacherIds)) return [];

        // Filter by qualification stats (tbl_teacher_stats)
        $teacherIds = $this->filterQualifiedTeachers($teacherIds);
        if (empty($teacherIds)) return [];

        $teacherInfo = $this->getTeacherInfo($teacherIds);
        $teacherIds = array_keys($teacherInfo);

        // Apply search filter
        if ($teacherSearch) {
            $searchLower = mb_strtolower($teacherSearch);
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($searchLower) {
                return str_contains(mb_strtolower($t['name']), $searchLower)
                    || str_contains(mb_strtolower($t['email']), $searchLower);
            });
            $teacherIds = array_keys($teacherInfo);
        }

        // Apply teacher type filter
        if ($teacherType && in_array($teacherType, ['VN', 'PHIL', 'NN'])) {
            $teacherInfo = array_filter($teacherInfo, function ($t) use ($teacherType) {
                return ($t['teacher_type'] ?? '') === $teacherType;
            });
            $teacherIds = array_keys($teacherInfo);
        }

        if (empty($teacherIds)) return [];

        $totalChecks = count($dateTimeChecks);
        $teacherAvailCountMap = []; // teacher_id => count of checks available

        // Phase 119: Determine today for past-date logic
        $todayLocal = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        foreach ($dateTimeChecks as $check) {
            $dateStr = $check['date'];
            $time = $check['time'];

            // Phase 119: For past dates, also count COMPLETED lessons as busy
            $isPastDate = $dateStr < $todayLocal;
            $lessonStatuses = $isPastDate ? [2, 3] : [2];

            $slotStartLocal = Carbon::parse("{$dateStr} {$time}", 'Asia/Ho_Chi_Minh');
            $slotEndLocal = $slotStartLocal->copy()->addMinutes(30);
            $slotStartUtc = $slotStartLocal->copy()->subHours(7)->format('Y-m-d H:i:s');
            $slotEndUtc = $slotEndLocal->copy()->subHours(7)->format('Y-m-d H:i:s');

            // Get teachers with availability
            $availTeachers = DB::connection('mysql')
                ->table('tbl_availability')
                ->whereIn('avail_user_id', $teacherIds)
                ->where('avail_starttime', '<=', $slotStartUtc)
                ->where('avail_endtime', '>=', $slotEndUtc)
                ->distinct()
                ->pluck('avail_user_id')
                ->toArray();

            if (empty($availTeachers)) continue;

            // Get busy teachers from lessons
            $busyFromLessons = DB::connection('mysql')
                ->table('tbl_order_lessons as ol')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
                ->whereIn('ol.ordles_teacher_id', $availTeachers)
                ->whereIn('ol.ordles_status', $lessonStatuses)
                ->where('o.order_payment_status', 1)
                ->where('o.order_status', 2)
                ->where('ol.ordles_lesson_starttime', '<', $slotEndUtc)
                ->where('ol.ordles_lesson_endtime', '>', $slotStartUtc)
                ->distinct()
                ->pluck('ol.ordles_teacher_id')
                ->toArray();

            // Get busy teachers from group classes
            $busyFromClasses = DB::connection('mysql')
                ->table('tbl_group_classes')
                ->whereIn('grpcls_teacher_id', $availTeachers)
                ->where('grpcls_status', 1)
                ->where('grpcls_start_datetime', '<', $slotEndUtc)
                ->where('grpcls_end_datetime', '>', $slotStartUtc)
                ->distinct()
                ->pluck('grpcls_teacher_id')
                ->toArray();

            $busyIds = array_unique(array_merge($busyFromLessons, $busyFromClasses));
            $freeIds = array_diff($availTeachers, $busyIds);

            foreach ($freeIds as $tid) {
                if (!isset($teacherAvailCountMap[$tid])) {
                    $teacherAvailCountMap[$tid] = 0;
                }
                $teacherAvailCountMap[$tid]++;
            }
        }

        // Build result
        $result = [];
        foreach ($teacherAvailCountMap as $tid => $count) {
            if (isset($teacherInfo[$tid])) {
                $info = $teacherInfo[$tid];
                $info['available_dates'] = $count;
                $info['total_dates'] = $totalChecks;
                $info['availability_rate'] = $totalChecks > 0 ? round(($count / $totalChecks) * 100) : 0;
                $result[] = $info;
            }
        }

        // Sort: highest availability first, then alphabetical
        usort($result, function ($a, $b) {
            if ($b['available_dates'] !== $a['available_dates']) {
                return $b['available_dates'] - $a['available_dates'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Return empty availability grid structure
     */
    private function emptyAvailabilityGrid(Carbon $startDate, Carbon $endDate): array
    {
        $days = [];
        $dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        for ($d = 0; $d < 7; $d++) {
            $day = $startDate->copy()->addDays($d);
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $dayLabels[$d] . ' ' . $day->format('d/m'),
                'day_name' => $dayLabels[$d],
            ];
        }

        $timeSlots = [];
        for ($h = 6; $h <= 23; $h++) {
            $timeSlots[] = sprintf('%02d:00', $h);
            $timeSlots[] = sprintf('%02d:30', $h);
        }

        return [
            'teachers' => [],
            'teacher_count' => 0,
            'days' => $days,
            'time_slots' => $timeSlots,
            'grid' => [],
            'teacher_detail' => [],
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'week_label' => $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y'),
        ];
    }

    /**
     * Get students who had their teacher changed within a date range.
     * Phase 204: Detects teacher changes by comparing consecutive lessons
     * for each student (beneficiary). A "change" occurs when the teacher_id
     * differs between two consecutive completed lessons.
     * Phase 206: Enhanced with leave-related change count per student.
     * Phase 209: Simple sequential detection — partitions by student only.
     * For SPW, each student typically has ONE teacher throughout, so any teacher
     * change = GV nghỉ (teacher absent) or PH yêu cầu đổi (parent requested change).
     *
     * @param string $dateFrom  Start date (Y-m-d)
     * @param string $dateTo    End date (Y-m-d)
     * @param string|null $search  Optional search term (student name, email, ID)
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDir
     * @param string|null $teacherNationality  Phase 208: Filter by teacher nationality (Vietnamese, Philippines, Native 1, Native 2)
     * @return array
     */
    public function getStudentsWithTeacherChanges(
        string $dateFrom,
        string $dateTo,
        ?string $search = null,
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'change_count',
        string $sortDir = 'desc',
        ?string $teacherNationality = null
    ): array {
        try {
            // Use LAG window function to detect teacher changes.
            // A teacher change = when ordles_teacher_id differs from the previous lesson's teacher
            // for the same student (beneficiary), ordered by lesson start time.
            // Phase 205: Only SPW subjects and only completed sessions (status=3)
            // Phase 208: Exclude trial subject 533
            $subjectIds = implode(',', self::SPEAKWELL_NO_TRIAL_SUBJECT_IDS);

            $searchCondition = '';
            $searchBindings = [];
            if ($search) {
                $searchCondition = "
                    AND (
                        u.user_id = ?
                        OR CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) LIKE ?
                        OR u.user_email LIKE ?
                        OR us.user_phone_number LIKE ?
                    )
                ";
                $searchBindings = [
                    is_numeric($search) ? (int) $search : 0,
                    "%{$search}%",
                    "%{$search}%",
                    "%{$search}%",
                ];
            }

            // Allowed sort columns
            // Phase 206: Added leave_change_count as allowed sort
            // Phase 210: Added order_count as allowed sort
            $allowedSorts = ['change_count', 'student_name', 'student_id', 'total_lessons', 'distinct_teachers', 'leave_change_count', 'order_count'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'change_count';
            }
            $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

            // Phase 208: Build teacher nationality filter condition (applied to change_events)
            $nationalityCondition = '';
            if ($teacherNationality) {
                $countryCodes = match ($teacherNationality) {
                    'Vietnamese' => "'VN'",
                    'Philippines' => "'PH'",
                    'Native 1' => "'" . implode("','", self::NATIVE_1_COUNTRY_CODES) . "'",
                    'Native 2' => "'" . implode("','", self::NATIVE_2_COUNTRY_CODES) . "'",
                    default => null,
                };
                if ($countryCodes) {
                    // Filter: either the new teacher OR the previous teacher matches the nationality
                    $nationalityCondition = "
                        AND (
                            EXISTS (
                                SELECT 1 FROM tbl_users tu
                                INNER JOIN tbl_countries tc ON tu.user_country_id = tc.country_id
                                WHERE tu.user_id = ce.ordles_teacher_id
                                  AND UPPER(tc.country_code) IN ({$countryCodes})
                            )
                            OR EXISTS (
                                SELECT 1 FROM tbl_users tu2
                                INNER JOIN tbl_countries tc2 ON tu2.user_country_id = tc2.country_id
                                WHERE tu2.user_id = ce.prev_teacher_id
                                  AND UPPER(tc2.country_code) IN ({$countryCodes})
                            )
                        )
                    ";
                }
            }

            // Phase 213: Teacher change detection partitioned by order_id.
            // Each order represents a distinct course enrollment with its own teacher.
            // This prevents false positives when a student has multiple courses with different teachers.
            // Phase 206: Enhanced CTE with leave-related detection
            // Phase 208: Exclude trial subject 533 + nationality filter
            // Phase 210: Added ordles_order_id to lesson_sequence and order_count to lesson_totals
            $sql = "
                WITH lesson_sequence AS (
                    SELECT
                        l.ordles_id,
                        l.ordles_beneficiary_id AS student_id,
                        l.ordles_teacher_id,
                        l.ordles_lesson_starttime,
                        l.ordles_order_id,
                        l.ordles_tlang_id,
                        LAG(l.ordles_teacher_id) OVER (
                            PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
                            ORDER BY l.ordles_lesson_starttime, l.ordles_id
                        ) AS prev_teacher_id
                    FROM tbl_order_lessons l
                    WHERE l.ordles_beneficiary_id IS NOT NULL
                      AND l.ordles_beneficiary_id > 0
                      AND l.ordles_status = 3
                      AND l.ordles_tlang_id IN ({$subjectIds})
                      AND l.ordles_lesson_starttime >= ?
                      AND l.ordles_lesson_starttime <= ?
                      AND l.ordles_teacher_id IS NOT NULL
                      AND l.ordles_teacher_id > 0
                ),
                change_events AS (
                    SELECT ls.*
                    FROM lesson_sequence ls
                    WHERE ls.prev_teacher_id IS NOT NULL
                      AND ls.ordles_teacher_id != ls.prev_teacher_id
                ),
                change_events_filtered AS (
                    SELECT ce.*
                    FROM change_events ce
                    WHERE 1=1
                    {$nationalityCondition}
                ),
                change_with_leave AS (
                    SELECT
                        ce.*,
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM tbl_teacher_leave_request_sessions lrs
                                INNER JOIN tbl_teacher_leave_requests lr ON lrs.tlrs_leave_request_id = lr.tlr_id
                                WHERE lrs.tlrs_session_id = ce.ordles_id
                                  AND lr.tlr_status IN (2, 3)
                            ) THEN 1
                            WHEN EXISTS (
                                SELECT 1 FROM tbl_teacher_leave_requests lr
                                WHERE lr.tlr_teacher_id = ce.prev_teacher_id
                                  AND lr.tlr_status IN (2, 3)
                                  AND DATE(lr.tlr_start_date) <= DATE(ce.ordles_lesson_starttime)
                                  AND DATE(lr.tlr_start_date) >= DATE(ce.ordles_lesson_starttime) - INTERVAL 30 DAY
                            ) THEN 1
                            ELSE 0
                        END AS is_leave_related
                    FROM change_events_filtered ce
                ),
                change_events_dedup AS (
                    SELECT
                        student_id,
                        ordles_teacher_id,
                        DATE_FORMAT(MIN(ordles_lesson_starttime), '%Y-%m') AS change_month,
                        MAX(is_leave_related) AS is_leave_related
                    FROM change_with_leave
                    GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
                ),
                lesson_totals AS (
                    SELECT
                        student_id,
                        COUNT(*) AS total_lessons,
                        COUNT(DISTINCT ordles_teacher_id) AS distinct_teachers,
                        COUNT(DISTINCT ordles_order_id) AS order_count
                    FROM lesson_sequence
                    GROUP BY student_id
                ),
                student_courses AS (
                    SELECT
                        ls.student_id,
                        GROUP_CONCAT(DISTINCT tl.tlang_identifier ORDER BY tl.tlang_identifier SEPARATOR ', ') AS course_names
                    FROM lesson_sequence ls
                    INNER JOIN tbl_teach_languages tl ON ls.ordles_tlang_id = tl.tlang_id
                    GROUP BY ls.student_id
                ),
                change_counts AS (
                    SELECT
                        ced.student_id,
                        COUNT(*) AS change_count,
                        SUM(ced.is_leave_related) AS leave_change_count,
                        lt.total_lessons,
                        lt.distinct_teachers,
                        lt.order_count
                    FROM change_events_dedup ced
                    INNER JOIN lesson_totals lt ON ced.student_id = lt.student_id
                    GROUP BY ced.student_id, lt.total_lessons, lt.distinct_teachers, lt.order_count
                )
                SELECT
                    cc.student_id,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                    u.user_email,
                    COALESCE(us.user_phone_number, '') AS phone,
                    cc.change_count,
                    cc.leave_change_count,
                    cc.total_lessons,
                    cc.distinct_teachers,
                    cc.order_count,
                    COALESCE(sc.course_names, '') AS course_names
                FROM change_counts cc
                INNER JOIN tbl_users u ON cc.student_id = u.user_id
                LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
                LEFT JOIN student_courses sc ON cc.student_id = sc.student_id
                WHERE 1=1
                {$searchCondition}
                ORDER BY {$sortBy} {$sortDir}
            ";

            $bindings = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
            $bindings = array_merge($bindings, $searchBindings);

            $allResults = DB::connection('mysql')->select($sql, $bindings);

            $total = count($allResults);
            $totalPages = max(1, (int) ceil($total / $perPage));
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;
            $pagedResults = array_slice($allResults, $offset, $perPage);

            $data = array_map(function ($row) {
                return [
                    'student_id' => (int) $row->student_id,
                    'student_name' => trim($row->student_name),
                    'email' => $row->user_email ?? '',
                    'phone' => $row->phone ?? '',
                    'change_count' => (int) $row->change_count,
                    'leave_change_count' => (int) $row->leave_change_count,
                    'total_lessons' => (int) $row->total_lessons,
                    'distinct_teachers' => (int) $row->distinct_teachers,
                    'order_count' => (int) ($row->order_count ?? 0), // Phase 210
                    'course_names' => $row->course_names ?? '', // Phase 213
                ];
            }, $pagedResults);

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("getStudentsWithTeacherChanges failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }
    }

    /**
     * Get teacher change detail for a specific student within a date range.
     * Phase 204: Returns the list of lessons with teacher info, highlighting changes.
     * Phase 206: Enhanced with leave request info and previous teacher details for each change event.
     * Phase 209: Simple sequential change detection (no schedule-slot partitioning).
     *
     * @param int $studentId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getStudentTeacherChangeDetail(int $studentId, string $dateFrom, string $dateTo): array
    {
        try {
            // Phase 208: Exclude trial subject 533
            $subjectIds = implode(',', self::SPEAKWELL_NO_TRIAL_SUBJECT_IDS);

            // Phase 213: Removed cancelled sessions (status=4) from display.
            // Phase 213: Added ordles_order_id and course_name for per-course change detection.
            // Change detection is done in PHP, partitioned by order_id to avoid
            // false positives when a student has multiple courses with different teachers.
            $sql = "
                SELECT
                    l.ordles_id,
                    l.ordles_teacher_id,
                    l.ordles_order_id,
                    l.ordles_tlang_id,
                    COALESCE(tl.tlang_identifier, '') AS course_name,
                    CONCAT(COALESCE(t.user_last_name, ''), ' ', COALESCE(t.user_first_name, '')) AS teacher_name,
                    t.user_email AS teacher_email,
                    COALESCE(c.country_code, '') AS teacher_country_code,
                    l.ordles_lesson_starttime,
                    l.ordles_status,
                    DAYOFWEEK(l.ordles_lesson_starttime) AS lesson_dow,
                    TIME_FORMAT(l.ordles_lesson_starttime, '%H:%i') AS lesson_time_slot
                FROM tbl_order_lessons l
                LEFT JOIN tbl_users t ON l.ordles_teacher_id = t.user_id
                LEFT JOIN tbl_countries c ON t.user_country_id = c.country_id
                LEFT JOIN tbl_teach_languages tl ON l.ordles_tlang_id = tl.tlang_id
                WHERE l.ordles_beneficiary_id = ?
                  AND l.ordles_status IN (2, 3)
                  AND l.ordles_tlang_id IN ({$subjectIds})
                  AND l.ordles_lesson_starttime >= ?
                  AND l.ordles_lesson_starttime <= ?
                  AND l.ordles_teacher_id IS NOT NULL
                  AND l.ordles_teacher_id > 0
                ORDER BY l.ordles_lesson_starttime, l.ordles_id
            ";

            $results = DB::connection('mysql')->select($sql, [
                $studentId,
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

            // Phase 213: Compute prev_teacher_id in PHP, partitioned by order_id.
            // Each order represents a distinct course enrollment with its own teacher.
            // This prevents false teacher-change detection when a student has multiple courses.
            $lastCompletedTeacherByOrder = []; // order_id => last completed teacher_id
            foreach ($results as $row) {
                if ((int) $row->ordles_status === 3) {
                    $orderId = (int) $row->ordles_order_id;
                    $row->prev_teacher_id = $lastCompletedTeacherByOrder[$orderId] ?? null;
                    $lastCompletedTeacherByOrder[$orderId] = (int) $row->ordles_teacher_id;
                } else {
                    $row->prev_teacher_id = null;
                }
            }

            // Get student info
            $studentInfo = DB::connection('mysql')->selectOne("
                SELECT
                    u.user_id,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                    u.user_email
                FROM tbl_users u
                WHERE u.user_id = ?
            ", [$studentId]);

            // Phase 206: Collect change event lesson IDs and previous teacher IDs
            $changeLessonIds = [];
            $prevTeacherMap = []; // lesson_id => prev_teacher_id
            $lessonDates = [];   // lesson_id => lesson_date
            foreach ($results as $row) {
                $isChange = $row->prev_teacher_id !== null && (int) $row->ordles_teacher_id !== (int) $row->prev_teacher_id;
                if ($isChange) {
                    $lid = (int) $row->ordles_id;
                    $changeLessonIds[] = $lid;
                    $prevTeacherMap[$lid] = (int) $row->prev_teacher_id;
                    $lessonDates[$lid] = $row->ordles_lesson_starttime;
                }
            }

            // Phase 206: Look up leave request info for change events
            $leaveInfoMap = []; // lesson_id => leave info
            if (!empty($changeLessonIds)) {
                // Method 1: Direct match via tbl_teacher_leave_request_sessions
                // Phase 215: Added date overlap check — leave period must cover the lesson date
                $placeholders = implode(',', array_fill(0, count($changeLessonIds), '?'));
                $leaveDirectResults = DB::connection('mysql')->select("
                    SELECT
                        lrs.tlrs_session_id AS lesson_id,
                        lr.tlr_teacher_id,
                        lr.tlr_reason,
                        lr.tlr_reason_type,
                        lr.tlr_start_date,
                        lr.tlr_end_date,
                        lr.tlr_status,
                        lrs.tlrs_replacement_type,
                        ol.ordles_lesson_starttime
                    FROM tbl_teacher_leave_request_sessions lrs
                    INNER JOIN tbl_teacher_leave_requests lr ON lrs.tlrs_leave_request_id = lr.tlr_id
                    INNER JOIN tbl_order_lessons ol ON lrs.tlrs_session_id = ol.ordles_id
                    WHERE lrs.tlrs_session_id IN ({$placeholders})
                      AND lr.tlr_status IN (2, 3)
                ", $changeLessonIds);

                foreach ($leaveDirectResults as $lr) {
                    $lid = (int) $lr->lesson_id;
                    $leaveInfoMap[$lid] = [
                        'leave_teacher_id' => (int) $lr->tlr_teacher_id,
                        'leave_reason' => $lr->tlr_reason ?? '',
                        'leave_reason_type' => TeacherLeaveRequest::getReasonTypeLabel($lr->tlr_reason_type),
                        'leave_period' => ($lr->tlr_start_date ? Carbon::parse($lr->tlr_start_date)->format('d/m/Y') : '')
                            . ' - ' . ($lr->tlr_end_date ? Carbon::parse($lr->tlr_end_date)->format('d/m/Y') : ''),
                        'replacement_type' => TeacherLeaveRequestSession::getReplacementTypeShortLabel($lr->tlrs_replacement_type),
                        'match_type' => 'direct',
                    ];
                }

                // Method 2: Date-range fallback for unmatched change events
                $unmatchedIds = array_diff($changeLessonIds, array_keys($leaveInfoMap));
                if (!empty($unmatchedIds)) {
                    // Build conditions: prev_teacher had approved leave covering lesson date
                    $conditions = [];
                    $bindings = [];
                    foreach ($unmatchedIds as $lid) {
                        if (isset($prevTeacherMap[$lid]) && isset($lessonDates[$lid])) {
                            $conditions[] = "(lr.tlr_teacher_id = ? AND DATE(lr.tlr_start_date) <= DATE(?) AND DATE(lr.tlr_start_date) >= DATE(?) - INTERVAL 30 DAY)";
                            $bindings[] = $prevTeacherMap[$lid];
                            $bindings[] = $lessonDates[$lid];
                            $bindings[] = $lessonDates[$lid];
                        }
                    }
                    if (!empty($conditions)) {
                        $orConditions = implode(' OR ', $conditions);
                        $dateLeaveResults = DB::connection('mysql')->select("
                            SELECT
                                lr.tlr_teacher_id,
                                lr.tlr_reason,
                                lr.tlr_reason_type,
                                lr.tlr_start_date,
                                lr.tlr_end_date
                            FROM tbl_teacher_leave_requests lr
                            WHERE lr.tlr_status IN (2, 3)
                              AND ({$orConditions})
                        ", $bindings);

                        // Map results back to lesson IDs
                        $teacherLeaveMap = []; // teacher_id => leave info (last found)
                        foreach ($dateLeaveResults as $lr) {
                            $teacherLeaveMap[(int) $lr->tlr_teacher_id] = $lr;
                        }
                        foreach ($unmatchedIds as $lid) {
                            $prevTid = $prevTeacherMap[$lid] ?? null;
                            if ($prevTid && isset($teacherLeaveMap[$prevTid])) {
                                $lr = $teacherLeaveMap[$prevTid];
                                $leaveInfoMap[$lid] = [
                                    'leave_teacher_id' => (int) $lr->tlr_teacher_id,
                                    'leave_reason' => $lr->tlr_reason ?? '',
                                    'leave_reason_type' => TeacherLeaveRequest::getReasonTypeLabel($lr->tlr_reason_type),
                                    'leave_period' => ($lr->tlr_start_date ? Carbon::parse($lr->tlr_start_date)->format('d/m/Y') : '')
                                        . ' - ' . ($lr->tlr_end_date ? Carbon::parse($lr->tlr_end_date)->format('d/m/Y') : ''),
                                    'replacement_type' => '',
                                    'match_type' => 'date_range',
                                ];
                            }
                        }
                    }
                }
            }

            // Phase 206/207: Get previous teacher names + country info for change events
            // Phase 211: Fix SQLSTATE[HY093] — array_unique preserves keys, causing non-sequential
            // array indices that PDO can't bind to positional ? placeholders. Re-index with array_values().
            $prevTeacherNames = [];
            $uniquePrevTeacherIds = array_values(array_unique(array_values($prevTeacherMap)));
            if (!empty($uniquePrevTeacherIds)) {
                $placeholders = implode(',', array_fill(0, count($uniquePrevTeacherIds), '?'));
                $teacherNameResults = DB::connection('mysql')->select("
                    SELECT u.user_id, CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS teacher_name,
                           u.user_email, COALESCE(c.country_code, '') AS country_code
                    FROM tbl_users u
                    LEFT JOIN tbl_countries c ON u.user_country_id = c.country_id
                    WHERE u.user_id IN ({$placeholders})
                ", $uniquePrevTeacherIds);
                foreach ($teacherNameResults as $tn) {
                    $prevTeacherNames[(int) $tn->user_id] = [
                        'name' => trim($tn->teacher_name),
                        'email' => $tn->user_email ?? '',
                        'country_code' => strtoupper($tn->country_code ?? ''),
                        'teacher_type' => self::getTeacherNationalityType($tn->country_code ?? ''),
                    ];
                }
            }

            $lessons = [];
            $leaveChangeCount = 0;
            // Phase 207: Track teacher nationality stats
            $teacherNationalityStats = [];
            // Phase 215: Track seen change events for deduplication across orders.
            // Key = "prevTeacherId-newTeacherId-YYYY-MM" to count each unique change event once.
            $seenChangeEvents = [];
            foreach ($results as $row) {
                $isChange = $row->prev_teacher_id !== null && (int) $row->ordles_teacher_id !== (int) $row->prev_teacher_id;
                $isChangeContinuation = false;
                $lid = (int) $row->ordles_id;

                // Phase 215: Deduplicate change events across orders.
                // Same (prev_teacher, new_teacher) pair within the same month = one event.
                if ($isChange) {
                    $changeKey = (int) $row->ordles_teacher_id . '-' . date('Y-m', strtotime($row->ordles_lesson_starttime));
                    if (isset($seenChangeEvents[$changeKey])) {
                        // This is a continuation/duplicate of an already-counted change event
                        $isChange = false;
                        $isChangeContinuation = true;
                    } else {
                        $seenChangeEvents[$changeKey] = true;
                    }
                }

                // Phase 207: Teacher nationality type
                $teacherCountryCode = strtoupper($row->teacher_country_code ?? '');
                $teacherType = self::getTeacherNationalityType($teacherCountryCode);

                // Phase 207: Schedule slot label (e.g. "T2 10:00")
                $dowLabel = self::getDayOfWeekLabel((int) ($row->lesson_dow ?? 0));
                $scheduleSlot = $dowLabel . ' ' . ($row->lesson_time_slot ?? '');

                $lessonData = [
                    'lesson_id' => $lid,
                    'teacher_id' => (int) $row->ordles_teacher_id,
                    'teacher_name' => trim($row->teacher_name),
                    'teacher_email' => $row->teacher_email ?? '',
                    'teacher_type' => $teacherType,
                    'lesson_time' => $row->ordles_lesson_starttime,
                    'status' => (int) $row->ordles_status,
                    'status_label' => OrderLesson::getStatusLabel((int) $row->ordles_status),
                    'is_change' => $isChange,
                    // Phase 215: Mark sessions that are part of the same change event but not the primary one
                    'is_change_continuation' => $isChangeContinuation,
                    'schedule_slot' => $scheduleSlot,
                    // Phase 213: Course name per lesson
                    'course_name' => $row->course_name ?? '',
                    'order_id' => (int) $row->ordles_order_id,
                    // Phase 206: Previous teacher and leave info
                    'prev_teacher_id' => ($isChange || $isChangeContinuation) ? (int) $row->prev_teacher_id : null,
                    'prev_teacher_name' => '',
                    'prev_teacher_email' => '',
                    'prev_teacher_type' => '',
                    'leave_info' => null,
                ];

                if ($isChange || $isChangeContinuation) {
                    $prevTid = (int) $row->prev_teacher_id;
                    if (isset($prevTeacherNames[$prevTid])) {
                        $lessonData['prev_teacher_name'] = $prevTeacherNames[$prevTid]['name'];
                        $lessonData['prev_teacher_email'] = $prevTeacherNames[$prevTid]['email'];
                        $lessonData['prev_teacher_type'] = $prevTeacherNames[$prevTid]['teacher_type'];
                    }
                    if ($isChange && isset($leaveInfoMap[$lid])) {
                        $lessonData['leave_info'] = $leaveInfoMap[$lid];
                        $leaveChangeCount++;
                    }
                }

                // Phase 207: Track nationality of involved teachers
                $tid = (int) $row->ordles_teacher_id;
                if (!isset($teacherNationalityStats[$tid])) {
                    $teacherNationalityStats[$tid] = $teacherType;
                }

                $lessons[] = $lessonData;
            }

            $totalChanges = count(array_filter($lessons, fn($l) => $l['is_change']));

            // Phase 207: Aggregate nationality breakdown
            $nationalityBreakdown = [];
            foreach ($teacherNationalityStats as $type) {
                $nationalityBreakdown[$type] = ($nationalityBreakdown[$type] ?? 0) + 1;
            }

            return [
                'student' => $studentInfo ? [
                    'id' => (int) $studentInfo->user_id,
                    'name' => trim($studentInfo->student_name),
                    'email' => $studentInfo->user_email ?? '',
                ] : null,
                'lessons' => $lessons,
                'total_lessons' => count($lessons),
                'change_count' => $totalChanges,
                // Phase 206: Leave-related change summary
                'leave_change_count' => $leaveChangeCount,
                'other_change_count' => $totalChanges - $leaveChangeCount,
                // Phase 207: Teacher nationality breakdown
                'nationality_breakdown' => $nationalityBreakdown,
            ];
        } catch (\Exception $e) {
            // Phase 210: Log error AND re-throw so the controller can return a proper error response.
            // Previously this silently returned empty data with success=true, hiding errors from users.
            \Illuminate\Support\Facades\Log::error("getStudentTeacherChangeDetail failed", [
                'error' => $e->getMessage(),
                'student_id' => $studentId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get chart data for student teacher changes.
     * Phase 205: Returns aggregated data for charts:
     *   - Distribution of change counts (how many students have 1, 2, 3... changes)
     *   - Top students with most teacher changes
     *   - Monthly trend of teacher changes
     * Phase 206: Added leave reason breakdown (pie chart data) and leave-related trend
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getStudentTeacherChangeChartData(string $dateFrom, string $dateTo): array
    {
        try {
            // Phase 205: Only SPW subjects and only completed sessions (status=3)
            // Phase 208: Exclude trial subject 533
            $subjectIds = implode(',', self::SPEAKWELL_NO_TRIAL_SUBJECT_IDS);

            // Phase 213: All chart queries partitioned by student + order_id
            // to avoid false positives from multi-course students.

            // 1. Distribution: how many students have N changes
            // Phase 215: Deduplicate change events by (student, prev_teacher, new_teacher, month)
            $distributionSql = "
                WITH lesson_sequence AS (
                    SELECT
                        l.ordles_beneficiary_id AS student_id,
                        l.ordles_teacher_id,
                        l.ordles_lesson_starttime,
                        LAG(l.ordles_teacher_id) OVER (
                            PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
                            ORDER BY l.ordles_lesson_starttime, l.ordles_id
                        ) AS prev_teacher_id
                    FROM tbl_order_lessons l
                    WHERE l.ordles_beneficiary_id IS NOT NULL
                      AND l.ordles_beneficiary_id > 0
                      AND l.ordles_status = 3
                      AND l.ordles_tlang_id IN ({$subjectIds})
                      AND l.ordles_lesson_starttime >= ?
                      AND l.ordles_lesson_starttime <= ?
                      AND l.ordles_teacher_id IS NOT NULL
                      AND l.ordles_teacher_id > 0
                ),
                change_events AS (
                    SELECT student_id, ordles_teacher_id, ordles_lesson_starttime
                    FROM lesson_sequence
                    WHERE prev_teacher_id IS NOT NULL AND ordles_teacher_id != prev_teacher_id
                ),
                change_events_dedup AS (
                    SELECT student_id
                    FROM change_events
                    GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
                ),
                change_counts AS (
                    SELECT
                        student_id,
                        COUNT(*) AS change_count
                    FROM change_events_dedup
                    GROUP BY student_id
                    HAVING change_count > 0
                )
                SELECT change_count, COUNT(*) AS student_count
                FROM change_counts
                GROUP BY change_count
                ORDER BY change_count ASC
            ";

            $distributionResults = DB::connection('mysql')->select($distributionSql, [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

            $distribution = [];
            foreach ($distributionResults as $row) {
                $distribution[] = [
                    'change_count' => (int) $row->change_count,
                    'student_count' => (int) $row->student_count,
                ];
            }

            // 2. Top 10 students with most changes
            // Phase 215: Deduplicate change events by (student, prev_teacher, new_teacher, month)
            $topStudentsSql = "
                WITH lesson_sequence AS (
                    SELECT
                        l.ordles_beneficiary_id AS student_id,
                        l.ordles_teacher_id,
                        l.ordles_lesson_starttime,
                        LAG(l.ordles_teacher_id) OVER (
                            PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
                            ORDER BY l.ordles_lesson_starttime, l.ordles_id
                        ) AS prev_teacher_id
                    FROM tbl_order_lessons l
                    WHERE l.ordles_beneficiary_id IS NOT NULL
                      AND l.ordles_beneficiary_id > 0
                      AND l.ordles_status = 3
                      AND l.ordles_tlang_id IN ({$subjectIds})
                      AND l.ordles_lesson_starttime >= ?
                      AND l.ordles_lesson_starttime <= ?
                      AND l.ordles_teacher_id IS NOT NULL
                      AND l.ordles_teacher_id > 0
                ),
                change_events AS (
                    SELECT student_id, ordles_teacher_id, ordles_lesson_starttime
                    FROM lesson_sequence
                    WHERE prev_teacher_id IS NOT NULL AND ordles_teacher_id != prev_teacher_id
                ),
                change_events_dedup AS (
                    SELECT student_id
                    FROM change_events
                    GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
                ),
                lesson_teachers AS (
                    SELECT student_id, COUNT(DISTINCT ordles_teacher_id) AS distinct_teachers
                    FROM lesson_sequence
                    GROUP BY student_id
                ),
                change_counts AS (
                    SELECT
                        ced.student_id,
                        COUNT(*) AS change_count,
                        lt.distinct_teachers
                    FROM change_events_dedup ced
                    INNER JOIN lesson_teachers lt ON ced.student_id = lt.student_id
                    GROUP BY ced.student_id, lt.distinct_teachers
                    HAVING change_count > 0
                )
                SELECT
                    cc.student_id,
                    CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                    cc.change_count,
                    cc.distinct_teachers
                FROM change_counts cc
                INNER JOIN tbl_users u ON cc.student_id = u.user_id
                ORDER BY cc.change_count DESC
                LIMIT 10
            ";

            $topStudentsResults = DB::connection('mysql')->select($topStudentsSql, [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

            $topStudents = [];
            foreach ($topStudentsResults as $row) {
                $topStudents[] = [
                    'student_id' => (int) $row->student_id,
                    'student_name' => trim($row->student_name),
                    'change_count' => (int) $row->change_count,
                    'distinct_teachers' => (int) $row->distinct_teachers,
                ];
            }

            // 3. Monthly trend: teacher changes per month with leave breakdown (Phase 206)
            // Phase 213: Partitioned by student + order_id
            // Phase 215: Deduplicate change events + leave date overlap check
            $trendSql = "
                WITH lesson_sequence AS (
                    SELECT
                        l.ordles_id,
                        l.ordles_beneficiary_id AS student_id,
                        l.ordles_teacher_id,
                        l.ordles_lesson_starttime,
                        LAG(l.ordles_teacher_id) OVER (
                            PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
                            ORDER BY l.ordles_lesson_starttime, l.ordles_id
                        ) AS prev_teacher_id
                    FROM tbl_order_lessons l
                    WHERE l.ordles_beneficiary_id IS NOT NULL
                      AND l.ordles_beneficiary_id > 0
                      AND l.ordles_status = 3
                      AND l.ordles_tlang_id IN ({$subjectIds})
                      AND l.ordles_lesson_starttime >= ?
                      AND l.ordles_lesson_starttime <= ?
                      AND l.ordles_teacher_id IS NOT NULL
                      AND l.ordles_teacher_id > 0
                ),
                change_events AS (
                    SELECT ls.*,
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM tbl_teacher_leave_request_sessions lrs
                                INNER JOIN tbl_teacher_leave_requests lr ON lrs.tlrs_leave_request_id = lr.tlr_id
                                WHERE lrs.tlrs_session_id = ls.ordles_id
                                  AND lr.tlr_status IN (2, 3)
                            ) THEN 1
                            WHEN EXISTS (
                                SELECT 1 FROM tbl_teacher_leave_requests lr
                                WHERE lr.tlr_teacher_id = ls.prev_teacher_id
                                  AND lr.tlr_status IN (2, 3)
                                  AND DATE(lr.tlr_start_date) <= DATE(ls.ordles_lesson_starttime)
                                  AND DATE(lr.tlr_start_date) >= DATE(ls.ordles_lesson_starttime) - INTERVAL 30 DAY
                            ) THEN 1
                            ELSE 0
                        END AS is_leave_related
                    FROM lesson_sequence ls
                    WHERE ls.prev_teacher_id IS NOT NULL
                      AND ls.ordles_teacher_id != ls.prev_teacher_id
                ),
                change_events_dedup AS (
                    SELECT
                        student_id,
                        DATE_FORMAT(MIN(ordles_lesson_starttime), '%Y-%m') AS change_month,
                        MAX(is_leave_related) AS is_leave_related
                    FROM change_events
                    GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
                )
                SELECT
                    change_month AS month,
                    COUNT(DISTINCT student_id) AS students_affected,
                    COUNT(*) AS change_events,
                    SUM(is_leave_related) AS leave_changes,
                    SUM(CASE WHEN is_leave_related = 0 THEN 1 ELSE 0 END) AS other_changes
                FROM change_events_dedup
                GROUP BY month
                ORDER BY month ASC
            ";

            $trendResults = DB::connection('mysql')->select($trendSql, [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

            $trend = [
                'labels' => [],
                'students_affected' => [],
                'change_events' => [],
                'leave_changes' => [],
                'other_changes' => [],
            ];
            $totalLeaveChanges = 0;
            $totalOtherChanges = 0;
            foreach ($trendResults as $row) {
                $trend['labels'][] = $row->month;
                $trend['students_affected'][] = (int) $row->students_affected;
                $trend['change_events'][] = (int) $row->change_events;
                $trend['leave_changes'][] = (int) $row->leave_changes;
                $trend['other_changes'][] = (int) $row->other_changes;
                $totalLeaveChanges += (int) $row->leave_changes;
                $totalOtherChanges += (int) $row->other_changes;
            }

            // Phase 206: Reason breakdown for pie chart
            $reasonBreakdown = [
                'leave_related' => $totalLeaveChanges,
                'other' => $totalOtherChanges,
            ];

            // Phase 212/213: Teacher nationality breakdown — count CHANGE EVENTS (not distinct teachers)
            // For each change event, classify by the old teacher's nationality and the new teacher's nationality.
            // Returns: change_from (lần đổi TỪ GV quốc tịch X) and change_to (lần đổi SANG GV quốc tịch X)
            // Phase 213: Partitioned by student + order_id
            // Phase 215: Deduplicate change events by (student, prev_teacher, new_teacher, month)
            $nationalitySql = "
                WITH lesson_sequence AS (
                    SELECT
                        l.ordles_id,
                        l.ordles_beneficiary_id AS student_id,
                        l.ordles_teacher_id,
                        l.ordles_lesson_starttime,
                        LAG(l.ordles_teacher_id) OVER (
                            PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
                            ORDER BY l.ordles_lesson_starttime, l.ordles_id
                        ) AS prev_teacher_id
                    FROM tbl_order_lessons l
                    WHERE l.ordles_beneficiary_id IS NOT NULL
                      AND l.ordles_beneficiary_id > 0
                      AND l.ordles_status = 3
                      AND l.ordles_tlang_id IN ({$subjectIds})
                      AND l.ordles_lesson_starttime >= ?
                      AND l.ordles_lesson_starttime <= ?
                      AND l.ordles_teacher_id IS NOT NULL
                      AND l.ordles_teacher_id > 0
                ),
                change_events AS (
                    SELECT ls.ordles_teacher_id AS new_teacher_id, ls.prev_teacher_id AS old_teacher_id,
                           ls.student_id, ls.ordles_lesson_starttime
                    FROM lesson_sequence ls
                    WHERE ls.prev_teacher_id IS NOT NULL
                      AND ls.ordles_teacher_id != ls.prev_teacher_id
                ),
                change_events_dedup AS (
                    SELECT MIN(old_teacher_id) AS old_teacher_id, new_teacher_id
                    FROM change_events
                    GROUP BY student_id, new_teacher_id, DATE_FORMAT(ordles_lesson_starttime, '%Y-%m')
                )
                SELECT
                    ce.old_teacher_id,
                    COALESCE(co_old.country_code, '') AS old_country_code,
                    ce.new_teacher_id,
                    COALESCE(co_new.country_code, '') AS new_country_code
                FROM change_events_dedup ce
                INNER JOIN tbl_users t_old ON ce.old_teacher_id = t_old.user_id
                LEFT JOIN tbl_countries co_old ON t_old.user_country_id = co_old.country_id
                INNER JOIN tbl_users t_new ON ce.new_teacher_id = t_new.user_id
                LEFT JOIN tbl_countries co_new ON t_new.user_country_id = co_new.country_id
            ";

            $nationalityResults = DB::connection('mysql')->select($nationalitySql, [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);

            // Phase 212: Aggregate change events into "Việt Nam" vs "Nước ngoài" for clarity
            $changeFrom = ['GV Việt Nam' => 0, 'GV Nước ngoài' => 0];
            $changeTo = ['GV Việt Nam' => 0, 'GV Nước ngoài' => 0];
            $detailedFrom = [];
            $detailedTo = [];
            foreach ($nationalityResults as $row) {
                $oldType = self::getTeacherNationalityType($row->old_country_code ?? '');
                $newType = self::getTeacherNationalityType($row->new_country_code ?? '');

                // Simplified: Vietnamese vs Foreign
                $oldGroup = ($oldType === 'Vietnamese') ? 'GV Việt Nam' : 'GV Nước ngoài';
                $newGroup = ($newType === 'Vietnamese') ? 'GV Việt Nam' : 'GV Nước ngoài';
                $changeFrom[$oldGroup] = ($changeFrom[$oldGroup] ?? 0) + 1;
                $changeTo[$newGroup] = ($changeTo[$newGroup] ?? 0) + 1;

                // Detailed by nationality type
                $detailedFrom[$oldType] = ($detailedFrom[$oldType] ?? 0) + 1;
                $detailedTo[$newType] = ($detailedTo[$newType] ?? 0) + 1;
            }

            $teacherNationality = [
                'change_from' => $changeFrom,
                'change_to' => $changeTo,
                'detailed_from' => array_filter($detailedFrom, fn($v) => $v > 0),
                'detailed_to' => array_filter($detailedTo, fn($v) => $v > 0),
                'total_changes' => count($nationalityResults),
            ];

            return [
                'distribution' => $distribution,
                'top_students' => $topStudents,
                'trend' => $trend,
                'reason_breakdown' => $reasonBreakdown,
                'teacher_nationality' => $teacherNationality,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("getStudentTeacherChangeChartData failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'distribution' => [],
                'top_students' => [],
                'trend' => ['labels' => [], 'students_affected' => [], 'change_events' => [], 'leave_changes' => [], 'other_changes' => []],
                'reason_breakdown' => ['leave_related' => 0, 'other' => 0],
                'teacher_nationality' => [],
            ];
        }
    }
}
