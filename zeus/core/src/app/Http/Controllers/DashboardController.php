<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\LcmsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected LcmsService $lcmsService,
    ) {}

    /**
     * Display the main dashboard
     * Uses cache-first approach: returns cached data if available,
     * only computes fresh data if cache is empty
     * Phase 137: Support program filter
     * Phase 142: Removed SPEAKWELL/EASY SPEAK tabs, only 'all' program remains
     */
    public function index(Request $request)
    {
        // Phase 142: Only 'all' program (removed speakwell/easyspeak tabs for caching performance)
        $program = 'all';
        $this->dashboardService->setProgram($program);

        $data = $this->dashboardService->getDashboardIndexData();
        $data['activeProgram'] = $program;

        return view('dashboard.index', $data);
    }

    /**
     * Display daily operations dashboard
     * Phase 142: Removed SPEAKWELL/EASY SPEAK tabs, only 'all' program remains
     */
    public function dailyOps(Request $request)
    {
        // Phase 142: Only 'all' program (removed speakwell/easyspeak tabs for caching performance)
        $program = 'all';
        $this->dashboardService->setProgram($program);

        $data = [
            'todayStats' => $this->dashboardService->getTodayLessonStats(),
            'issues' => $this->dashboardService->getIssueStats(),
            'recentLessons' => $this->dashboardService->getRecentLessons(10),
            'pendingIssues' => $this->dashboardService->getPendingIssues(10),
            'lessonChart' => $this->dashboardService->getLessonChartData(14),
            'sessionStats' => $this->dashboardService->getMultiPeriodSessionStats(),
            'neverLoggedInStats' => $this->dashboardService->getMultiPeriodNeverLoggedInStats(),
            'activeProgram' => $program,
        ];

        return view('dashboard.daily-ops', $data);
    }

    /**
     * Display teacher management dashboard
     */
    public function teachers(Request $request)
    {
        // Phase 138: Set active program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $teacherStats = $this->dashboardService->getTeacherStats();
        $paymentStats = $this->dashboardService->getTeacherPaymentStats();
        $ratings = $this->dashboardService->getRatingStats();

        // New data for Phase 2
        $topTeachers = $this->dashboardService->getTopTeachers(10);

        return view('dashboard.teachers', compact(
            'teacherStats',
            'paymentStats',
            'ratings',
            'topTeachers'
        ));
    }

    /**
     * Display revenue dashboard
     */
    public function revenue(Request $request)
    {
        // Phase 138: Set active program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $revenue = $this->dashboardService->getRevenueStats();
        $ordersByType = $this->dashboardService->getOrderStatsByType();
        $ordersByStatus = $this->dashboardService->getOrderStatsByStatus();
        $revenueChart = $this->dashboardService->getRevenueChartData();
        $paymentStats = $this->dashboardService->getTeacherPaymentStats();

        // New data for Phase 2
        $walletStats = $this->dashboardService->getWalletStats();
        $recentOrders = $this->dashboardService->getRecentOrders(10);
        $topLearners = $this->dashboardService->getTopLearners(10);

        return view('dashboard.revenue', compact(
            'revenue',
            'ordersByType',
            'ordersByStatus',
            'revenueChart',
            'paymentStats',
            'walletStats',
            'recentOrders',
            'topLearners'
        ));
    }

    /**
     * Display quality dashboard
     */
    public function quality(Request $request)
    {
        // Phase 138: Set active program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $lessonQuality = $this->dashboardService->getLessonQualityStats();
        $ratings = $this->dashboardService->getRatingStats();
        $issues = $this->dashboardService->getIssueStats();

        // New data for Phase 2
        $conversionFunnel = $this->dashboardService->getConversionFunnelStats();
        $lessonChart = $this->dashboardService->getLessonChartData(30);

        // New data for Phase 3 - Teacher Feedback Quality
        $feedbackStats = $this->dashboardService->getTeacherFeedbackStats();
        $feedbackStatus = $this->dashboardService->getTeachersFeedbackStatus();

        // New session quality summary
        $sessionQuality = $this->dashboardService->getSessionQualitySummary();

        // Cancellation breakdown (categorizes cancellation types)
        $cancellationBreakdown = $this->dashboardService->getCancellationBreakdown(30);

        return view('dashboard.quality', compact(
            'lessonQuality',
            'ratings',
            'issues',
            'conversionFunnel',
            'lessonChart',
            'feedbackStats',
            'feedbackStatus',
            'sessionQuality',
            'cancellationBreakdown'
        ));
    }

    /**
     * API: Get overview stats
     */
    public function apiOverview()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getOverviewStats(),
        ]);
    }

    /**
     * API: Get revenue chart data
     */
    public function apiRevenueChart(Request $request)
    {
        $days = $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRevenueChartData($days),
        ]);
    }

    /**
     * API: Get user registration chart data
     */
    public function apiUserChart(Request $request)
    {
        $days = $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getUserRegistrationChartData($days),
        ]);
    }

    /**
     * API: Search users
     */
    public function apiSearchUsers(Request $request)
    {
        $keyword = $request->input('q', '');
        if (strlen($keyword) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->searchUsers($keyword),
        ]);
    }

    /**
     * API: Get stats for date range
     */
    public function apiStatsForRange(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getStatsForDateRange($startDate, $endDate),
        ]);
    }

    /**
     * API: Get lesson chart data
     */
    public function apiLessonChart(Request $request)
    {
        $days = $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLessonChartData($days),
        ]);
    }

    /**
     * API: Get top teachers
     */
    public function apiTopTeachers(Request $request)
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTopTeachers($limit),
        ]);
    }

    /**
     * API: Get top learners
     */
    public function apiTopLearners(Request $request)
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTopLearners($limit),
        ]);
    }

    /**
     * Display learning path dashboard
     */
    public function learningPath(Request $request)
    {
        // Phase 138: Set active program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $learningPathStats = $this->dashboardService->getLearningPathStats();
        $sessionDistribution = $this->dashboardService->getCurriculumSessionDistribution();
        $programStats = $this->dashboardService->getProgramEnrollmentStats();
        $sessionChart = $this->dashboardService->getCurriculumSessionChartData(30);
        $feedbackStats = $this->dashboardService->getTeacherFeedbackStats();
        $feedbackStatus = $this->dashboardService->getTeachersFeedbackStatus();
        $feedbackTrend = $this->dashboardService->getFeedbackSubmissionTrend(14);
        $recentFeedback = $this->dashboardService->getRecentFeedbackWithContent(10);
        $topTeachersByFeedback = $this->dashboardService->getTopTeachersByFeedback(10);
        $sessionOutcome = $this->dashboardService->getSessionOutcomeStats();
        $attendanceIssues = $this->dashboardService->getAttendanceIssues(10);

        return view('dashboard.learning-path', compact(
            'learningPathStats',
            'sessionDistribution',
            'programStats',
            'sessionChart',
            'feedbackStats',
            'feedbackStatus',
            'feedbackTrend',
            'recentFeedback',
            'topTeachersByFeedback',
            'sessionOutcome',
            'attendanceIssues'
        ));
    }

    /**
     * API: Get learning path stats
     */
    public function apiLearningPathStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLearningPathStats(),
        ]);
    }

    /**
     * API: Get teacher feedback stats
     */
    public function apiFeedbackStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTeacherFeedbackStats(),
        ]);
    }

    /**
     * API: Get feedback submission trend
     */
    public function apiFeedbackTrend(Request $request)
    {
        $days = $request->input('days', 14);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getFeedbackSubmissionTrend($days),
        ]);
    }

    /**
     * API: Get feedback detail by ID
     */
    public function apiFeedbackDetail(Request $request, int $id)
    {
        $feedback = $this->dashboardService->getFeedbackDetail($id);

        if (! $feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    /**
     * API: Get session outcome stats
     */
    public function apiSessionOutcome()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getSessionOutcomeStats(),
        ]);
    }

    /**
     * API: Get session quality summary
     */
    public function apiSessionQuality()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getSessionQualitySummary(),
        ]);
    }

    /**
     * API: Get attendance issues
     */
    public function apiAttendanceIssues(Request $request)
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAttendanceIssues($limit),
        ]);
    }

    // ========================================
    // Phase 4: Teacher Management & Quiz
    // ========================================

    /**
     * Display teacher management dashboard
     */
    public function teacherManagement(Request $request)
    {
        // Phase 138: Set active program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $leaveStats = $this->dashboardService->getLeaveRequestStats();
        $leaveByStatus = $this->dashboardService->getLeaveRequestsByStatus();
        $recentLeaveRequests = $this->dashboardService->getRecentLeaveRequests(10);
        $violationStats = $this->dashboardService->getLeaveViolationStats();
        $teachersWithMostLeave = $this->dashboardService->getTeachersWithMostLeave(10);
        $leaveTrend = $this->dashboardService->getLeaveRequestTrend(14);
        $leaveQuotaSummary = $this->dashboardService->getLeaveQuotaSummary();

        $quizStats = $this->dashboardService->getQuizStats();
        $quizAttemptStats = $this->dashboardService->getQuizAttemptStats();
        $recentQuizAttempts = $this->dashboardService->getRecentQuizAttempts(10);
        $quizPassFailChart = $this->dashboardService->getQuizPassFailChartData(14);

        // Acceptance Code Stats (Phase 100)
        $acceptanceCodeStats = $this->dashboardService->getMultiPeriodAcceptanceCodeStats();
        $acceptanceCodesList = $this->dashboardService->getAcceptanceCodesList();

        // Leave Affected Sessions Stats (Phase 101)
        $leaveAffectedStats = $this->dashboardService->getLeaveAffectedSessionsStats();

        return view('dashboard.teacher-management', compact(
            'leaveStats',
            'leaveByStatus',
            'recentLeaveRequests',
            'violationStats',
            'teachersWithMostLeave',
            'leaveTrend',
            'leaveQuotaSummary',
            'quizStats',
            'quizAttemptStats',
            'recentQuizAttempts',
            'quizPassFailChart',
            'acceptanceCodeStats',
            'acceptanceCodesList',
            'leaveAffectedStats'
        ));
    }

    /**
     * API: Get leave request stats
     */
    public function apiLeaveStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLeaveRequestStats(),
        ]);
    }

    /**
     * API: Get leave request trend
     */
    public function apiLeaveTrend(Request $request)
    {
        $days = $request->input('days', 14);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLeaveRequestTrend($days),
        ]);
    }

    /**
     * API: Get quiz stats
     */
    public function apiQuizStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getQuizStats(),
        ]);
    }

    /**
     * API: Get quiz attempt stats
     */
    public function apiQuizAttempts()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getQuizAttemptStats(),
        ]);
    }

    /**
     * API: Get stats for a specific period (today, yesterday, week, month, all)
     */
    public function apiPeriodStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'all');

        // Validate period
        if (! in_array($period, ['today', 'yesterday', 'week', 'month', 'all'])) {
            $period = 'all';
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getStatsForPeriod($period),
        ]);
    }

    /**
     * API: Get login stats for a period
     */
    public function apiLoginStats(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginStats(),
        ]);
    }

    /**
     * API: Get login stats by user type
     */
    public function apiLoginStatsByType(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginStatsByUserType(),
        ]);
    }

    /**
     * API: Get login trend chart data
     */
    public function apiLoginTrend(Request $request)
    {
        $days = $request->input('days', 14);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginTrendChartData($days),
        ]);
    }

    /**
     * API: Get login by hour
     */
    public function apiLoginsByHour()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginsByHour(),
        ]);
    }

    /**
     * API: Get login by day of week
     */
    public function apiLoginsByDayOfWeek()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginsByDayOfWeek(),
        ]);
    }

    /**
     * API: Get login by source
     */
    public function apiLoginsBySource()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLoginsBySource(),
        ]);
    }

    // ========================================
    // Program/Curriculum Statistics
    // ========================================

    /**
     * API: Get program statistics
     */
    public function apiProgramStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getProgramStats(),
        ]);
    }

    /**
     * API: Get program statistics summary
     */
    public function apiProgramStatsSummary()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getProgramStatsSummary(),
        ]);
    }

    /**
     * API: Get program category stats
     */
    public function apiProgramCategoryStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getProgramCategoryStats(),
        ]);
    }

    /**
     * API: Get program enrollment trend
     */
    public function apiProgramEnrollmentTrend(Request $request)
    {
        $days = $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getProgramEnrollmentTrend($days),
        ]);
    }

    /**
     * API: Get all programs list
     */
    public function apiProgramsList()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAllProgramsList(),
        ]);
    }

    /**
     * API: Get specific program details
     */
    public function apiProgramDetail(Request $request, int $id)
    {
        $data = $this->dashboardService->getProgramDetailStats($id);

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Get comparison stats (today vs yesterday, week vs week, month vs month)
     */
    public function apiComparisonStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getComparisonStats(),
        ]);
    }

    /**
     * API: Get session success/failure breakdown for a period
     */
    public function apiSessionStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getMultiPeriodSessionStats(),
        ]);
    }

    /**
     * API: Export session stats by month range
     * Required params: from (YYYY-MM), to (YYYY-MM)
     * Returns: Array of monthly stats with total_sessions, chargeable_sessions, successful_sessions, teacher_no_show
     */
    public function apiExportSessionStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $from = $request->get('from');
        $to = $request->get('to');

        if (!$from || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn khoảng thời gian',
            ], 400);
        }

        try {
            $data = $this->dashboardService->getSessionStatsForMonthRange($from, $to);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất dữ liệu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Export session stats by date range (daily breakdown)
     * Required params: from (YYYY-MM-DD), to (YYYY-MM-DD)
     * Returns: Array of daily stats with total_sessions, chargeable_sessions, successful_sessions, teacher_no_show
     */
    public function apiExportDailySessionStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $from = $request->get('from');
        $to = $request->get('to');

        if (!$from || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn khoảng thời gian (from và to dạng YYYY-MM-DD)',
            ], 400);
        }

        try {
            $data = $this->dashboardService->getSessionStatsForDateRange($from, $to);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất dữ liệu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get acceptance codes list with labels
     */
    public function apiAcceptanceCodesList()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAcceptanceCodesList(),
        ]);
    }

    /**
     * API: Get acceptance code breakdown statistics
     */
    public function apiAcceptanceCodeStats(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getMultiPeriodAcceptanceCodeStats(),
        ]);
    }

    /**
     * API: Get penalized teachers details for a specific period
     */
    public function apiPenalizedTeachersDetails(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getPenalizedTeachersDetails($period),
        ]);
    }

    /**
     * API: Get never-logged-in students detail list
     */
    public function apiNeverLoggedInStudentsDetail(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        // Validate period
        if (! in_array($period, ['today', 'yesterday', 'day_before_yesterday', 'this_week', 'this_month'])) {
            $period = 'today';
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getNeverLoggedInStudentsDetail($period),
        ]);
    }

    /**
     * API: Get students with multiple lessons (2+) per day
     */
    public function apiStudentsWithMultipleLessons(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        // Validate period
        if (! in_array($period, ['today', 'yesterday', 'day_before_yesterday', 'this_week', 'this_month'])) {
            $period = 'today';
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getStudentsWithMultipleLessons($period),
        ]);
    }

    /**
     * API: Clear dashboard cache
     * Called by the "Làm mới" (Refresh) button to force fresh data
     */
    public function apiClearCache()
    {
        $success = $this->dashboardService->clearDashboardCache();

        // Also clear LCMS cache (Phase 123: single global refresh button)
        $this->lcmsService->clearLcmsCache();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Cache đã được xóa thành công' : 'Có lỗi khi xóa cache',
        ]);
    }

    // ========================================
    // Real-time Lesson Status APIs
    // ========================================

    /**
     * API: Get real-time lesson status (ongoing, upcoming, remaining, heatmap)
     */
    public function apiRealTimeStatus(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRealTimeLessonStatus(),
        ]);
    }

    /**
     * API: Get ongoing lessons (currently happening)
     */
    public function apiOngoingLessons()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getOngoingLessons(),
        ]);
    }

    /**
     * API: Get upcoming lessons (starting within next 60 minutes)
     */
    public function apiUpcomingLessons(Request $request)
    {
        $minutes = $request->input('minutes', 60);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getUpcomingLessons($minutes),
        ]);
    }

    /**
     * API: Get remaining lessons for today
     */
    public function apiRemainingLessons()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRemainingLessonsToday(),
        ]);
    }

    /**
     * API: Get time slot heatmap for today
     */
    public function apiTimeSlotHeatmap()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTodayTimeSlotHeatmap(),
        ]);
    }

    /**
     * API: Get time slot heatmap for yesterday
     */
    public function apiYesterdayTimeSlotHeatmap(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getYesterdayTimeSlotHeatmap(),
        ]);
    }

    // ========================================
    // Teacher Login Status APIs
    // ========================================

    /**
     * API: Get teacher no-show details for a specific period
     */
    public function apiTeacherNoShowDetails(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTeacherNoShowDetails($period),
        ]);
    }

    /**
     * API: Get teacher late entry details for a specific period
     */
    public function apiTeacherLateEntryDetails(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTeacherLateEntryDetails($period),
        ]);
    }

    /**
     * API: Get teacher early exit details for a specific period
     */
    public function apiTeacherEarlyExitDetails(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $period = $request->input('period', 'today');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getTeacherEarlyExitDetails($period),
        ]);
    }

    /**
     * API: Get cache refresh timestamp
     * Returns when the dashboard data was last refreshed by the scheduler
     */
    public function apiCacheRefreshedAt()
    {
        $refreshedAt = \Illuminate\Support\Facades\Cache::get('dashboard.cache_refreshed_at');

        return response()->json([
            'success' => true,
            'data' => [
                'refreshed_at' => $refreshedAt,
                'refreshed_at_formatted' => $refreshedAt ? \Carbon\Carbon::parse($refreshedAt)->format('H:i d/m') : null,
                'cache_ttl_minutes' => 15, // Scheduler runs every 15 minutes
            ],
        ]);
    }

    /**
     * API: Get session stats for a custom date
     * Used by "Tùy chọn" date picker to get stats for a specific day
     */
    public function apiCustomDateSessionStats(Request $request)
    {
        // Phase 137: Support program filter
        $program = $request->input('program', 'all');
        $this->dashboardService->setProgram($program);

        $date = $request->input('date');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Support date range (from_date + to_date) or single date
        if ($fromDate && $toDate) {
            try {
                $start = \Carbon\Carbon::parse($fromDate)->startOfDay();
                $end = \Carbon\Carbon::parse($toDate)->endOfDay();

                return response()->json([
                    'success' => true,
                    'data' => $this->dashboardService->getSessionSuccessFailureBreakdown($start, $end),
                    'from_date' => $start->format('Y-m-d'),
                    'to_date' => $end->format('Y-m-d'),
                    'date_formatted' => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format',
                ], 400);
            }
        }

        if (! $date) {
            return response()->json([
                'success' => false,
                'message' => 'Date parameter is required',
            ], 400);
        }

        try {
            $parsedDate = \Carbon\Carbon::parse($date);
            $start = $parsedDate->copy()->startOfDay();
            $end = $parsedDate->copy()->endOfDay();

            return response()->json([
                'success' => true,
                'data' => $this->dashboardService->getSessionSuccessFailureBreakdown($start, $end),
                'date' => $parsedDate->format('Y-m-d'),
                'date_formatted' => $parsedDate->format('d/m/Y'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format',
            ], 400);
        }
    }

    /**
     * API: Get usage report data (JSON)
     */
    public function apiUsageReport(Request $request)
    {
        $startDate = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        $service = new \App\Services\UsageReportService($startDate, $endDate);
        $report = $service->generateReport();

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * API: Export usage report as Excel (CSV)
     */
    public function apiExportUsageReport(Request $request)
    {
        $startDate = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        $service = new \App\Services\UsageReportService($startDate, $endDate);
        $report = $service->generateReport();

        // Generate CSV content
        $columns = $report['columns'];
        $headers = array_map(fn($col) => $col['label'], $columns);

        $filename = 'bao-cao-su-dung_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv';

        $callback = function() use ($report, $columns) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, array_map(fn($col) => $col['label'], $columns));

            // Data rows
            foreach ($report['data'] as $row) {
                $rowData = [];
                foreach ($columns as $col) {
                    $value = $row[$col['key']] ?? '';
                    // Format dates
                    if (in_array($col['key'], ['payment_date', 'start_date', 'end_date', 'cancel_date']) && $value) {
                        $value = \Carbon\Carbon::parse($value)->format('d/m/Y H:i:s');
                    }
                    // Format amounts
                    if (str_contains($col['key'], '_amount') && is_numeric($value)) {
                        $value = number_format($value, 0, ',', '.');
                    }
                    $rowData[] = $value;
                }
                fputcsv($file, $rowData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ========================================
    // Phase 28: Async Export APIs
    // ========================================

    /**
     * API: Start async usage report export
     * Returns export ID for progress tracking
     * Phase 29: Added queue driver check and sync fallback
     * Phase 32: Enhanced error handling and logging
     * Phase 43: Removed database tracking - uses only Redis cache (database is readonly)
     */
    public function apiStartExportUsageReport(Request $request)
    {
        // Phase 33: Increase PHP timeout and memory for sync mode export
        // This prevents 500 errors caused by query timeout or memory exhaustion
        set_time_limit(600); // 10 minutes max
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1G'); // Increase memory for large Excel files
        
        // Phase 32: Wrap entire method in try-catch for unexpected errors
        try {
            $startDate = $request->input('start_date')
                ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
                : now()->startOfMonth();
            $endDate = $request->input('end_date')
                ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
                : now()->endOfMonth();

            // Generate unique export ID
            $exportId = 'exp_' . uniqid() . '_' . time();

            // Get current user ID (for tracking)
            $userId = auth()->id() ?? 0;

            // Phase 29: Check if queue driver is async (redis or database)
            $queueDriver = config('queue.default');
            $isAsyncQueue = in_array($queueDriver, ['redis', 'database', 'beanstalkd', 'sqs']);

            // Phase 43: Removed database tracking - use only Redis cache
            // Database is readonly on server, cannot create export_jobs table

            // Initialize export status in cache (for real-time polling)
            \Illuminate\Support\Facades\Cache::put("export_job:{$exportId}", [
                'status' => 'pending',
                'progress' => 0,
                'message' => $isAsyncQueue ? 'Đang chờ xử lý...' : 'Đang xử lý trực tiếp...',
                'created_at' => now()->toIso8601String(),
                'period' => [
                    'start' => $startDate->format('d/m/Y'),
                    'end' => $endDate->format('d/m/Y'),
                ],
                'queue_driver' => $queueDriver,
            ], now()->addHours(24));

            if ($isAsyncQueue) {
                // Async mode: Dispatch the job to queue
                \App\Jobs\ProcessUsageReportExport::dispatch($exportId, $startDate, $endDate, $userId);
            } else {
                // Sync mode: Run the job inline with increased timeout
                // Phase 29: Handle sync queue by running in the same request with progress updates
                try {
                    // Update status to processing
                    \Illuminate\Support\Facades\Cache::put("export_job:{$exportId}", [
                        'status' => 'processing',
                        'progress' => 10,
                        'message' => 'Đang truy vấn dữ liệu...',
                        'created_at' => now()->toIso8601String(),
                        'period' => [
                            'start' => $startDate->format('d/m/Y'),
                            'end' => $endDate->format('d/m/Y'),
                        ],
                    ], now()->addHours(24));

                    // Generate report
                    $service = new \App\Services\UsageReportService($startDate, $endDate);
                    $report = $service->generateReport();

                    // Update progress
                    \Illuminate\Support\Facades\Cache::put("export_job:{$exportId}", [
                        'status' => 'processing',
                        'progress' => 60,
                        'message' => 'Đang tạo file Excel... (' . count($report['data']) . ' bản ghi)',
                        'created_at' => now()->toIso8601String(),
                        'period' => [
                            'start' => $startDate->format('d/m/Y'),
                            'end' => $endDate->format('d/m/Y'),
                        ],
                    ], now()->addHours(24));

                    // Phase 31: Generate Excel file instead of CSV
                    $filename = "bao-cao-su-dung_{$exportId}.xlsx";
                    $filepath = "exports/{$filename}";
                    
                    // Use the job's Excel generation logic
                    $job = new \App\Jobs\ProcessUsageReportExport($exportId, $startDate, $endDate, $userId);
                    $generatedFilename = $this->generateExcelForSync($report, $exportId);
                    $filename = $generatedFilename;

                    // Mark as completed
                    \Illuminate\Support\Facades\Cache::put("export_job:{$exportId}", [
                        'status' => 'completed',
                        'progress' => 100,
                        'message' => 'Xuất báo cáo thành công!',
                        'filename' => $filename,
                        'download_url' => "/api/download-export/{$exportId}",
                        'record_count' => count($report['data']),
                        'completed_at' => now()->toIso8601String(),
                        'period' => [
                            'start' => $startDate->format('d/m/Y'),
                            'end' => $endDate->format('d/m/Y'),
                        ],
                    ], now()->addHours(24));

                    // Phase 43: Removed database update - use only Redis cache

                } catch (\Exception $e) {
                    // Phase 32: Add logging for sync export errors
                    \Illuminate\Support\Facades\Log::error("Export sync failed: {$exportId}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'period' => [
                            'start' => $startDate->format('Y-m-d'),
                            'end' => $endDate->format('Y-m-d'),
                        ],
                    ]);

                    \Illuminate\Support\Facades\Cache::put("export_job:{$exportId}", [
                        'status' => 'failed',
                        'progress' => 0,
                        'message' => 'Lỗi: ' . $e->getMessage(),
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ], now()->addHours(1));

                    // Phase 43: Removed database update - use only Redis cache

                    return response()->json([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
                        'export_id' => $exportId,
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'export_id' => $exportId,
                'message' => $isAsyncQueue 
                    ? 'Đã bắt đầu xuất báo cáo. Vui lòng theo dõi tiến trình.'
                    : 'Đang xử lý báo cáo. Vui lòng đợi...',
                'status_url' => "/api/export-status/{$exportId}",
                'is_async' => $isAsyncQueue,
            ]);
        } catch (\Throwable $e) {
            // Phase 32: Catch any unexpected errors and return valid JSON
            \Illuminate\Support\Facades\Log::error("Export unexpected error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi không mong đợi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get export job status
     * Phase 43: Use only Redis cache (removed database fallback due to readonly DB)
     */
    public function apiExportStatus(string $exportId)
    {
        $cacheKey = "export_job:{$exportId}";
        $data = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // Phase 43: Fallback to file-based check if cache is empty
        if (!$data) {
            // Check if export file exists
            $files = \Illuminate\Support\Facades\Storage::disk('local')->files('exports');
            $foundFile = null;
            foreach ($files as $file) {
                if (str_contains($file, $exportId)) {
                    $foundFile = basename($file);
                    break;
                }
            }
            
            if ($foundFile) {
                // File exists, export was completed
                $data = [
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => 'Xuất báo cáo thành công!',
                    'filename' => $foundFile,
                    'download_url' => "/api/download-export/{$exportId}",
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin xuất báo cáo',
                ], 404);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Download completed export file
     * Phase 43: Use only Redis cache + file check (removed database fallback)
     */
    public function apiDownloadExport(string $exportId)
    {
        $cacheKey = "export_job:{$exportId}";
        $data = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // Phase 43: Fallback to file-based check
        if (!$data) {
            // Check if export file exists
            $files = \Illuminate\Support\Facades\Storage::disk('local')->files('exports');
            $foundFile = null;
            foreach ($files as $file) {
                if (str_contains($file, $exportId)) {
                    $foundFile = basename($file);
                    break;
                }
            }
            
            if ($foundFile) {
                $data = [
                    'status' => 'completed',
                    'filename' => $foundFile,
                    'period' => ['start' => '', 'end' => ''],
                ];
            }
        }

        if (!$data || $data['status'] !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'File chưa sẵn sàng hoặc không tồn tại',
            ], 404);
        }

        $filename = $data['filename'] ?? '';
        $filepath = "exports/{$filename}";

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File không tồn tại trên server',
            ], 404);
        }

        $period = $data['period'] ?? ['start' => '', 'end' => ''];
        
        // Phase 31: Support Excel format (.xlsx)
        $isExcel = str_ends_with($filename, '.xlsx');
        $downloadName = 'bao-cao-su-dung_' . str_replace('/', '-', $period['start']) . '_' . str_replace('/', '-', $period['end']) . ($isExcel ? '.xlsx' : '.csv');

        return \Illuminate\Support\Facades\Storage::disk('local')->download($filepath, $downloadName, [
            'Content-Type' => $isExcel 
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
                : 'text/csv; charset=utf-8',
        ]);
    }

    /**
     * API: Cancel export job (cleanup)
     */
    public function apiCancelExport(string $exportId)
    {
        $cacheKey = "export_job:{$exportId}";
        $data = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin xuất báo cáo',
            ], 404);
        }

        // Delete cache entry
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // Delete file if exists
        if (isset($data['filename'])) {
            $filepath = "exports/{$data['filename']}";
            \Illuminate\Support\Facades\Storage::disk('local')->delete($filepath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy xuất báo cáo',
        ]);
    }

    /**
     * API: Get list of completed export files
     * Phase 31: Added export file list for user to download previously generated files
     */
    public function apiExportList()
    {
        $exports = [];
        
        // Get all export files from storage
        $files = \Illuminate\Support\Facades\Storage::disk('local')->files('exports');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Parse export ID from filename
            if (preg_match('/bao-cao-su-dung_(.+)\.(xlsx|csv)$/', $filename, $matches)) {
                $exportId = $matches[1];
                $extension = $matches[2];
                
                // Get metadata from cache if available
                $cacheKey = "export_job:{$exportId}";
                $cacheData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                
                // Get file info
                $size = \Illuminate\Support\Facades\Storage::disk('local')->size($file);
                $lastModified = \Illuminate\Support\Facades\Storage::disk('local')->lastModified($file);
                
                $exports[] = [
                    'export_id' => $exportId,
                    'filename' => $filename,
                    'extension' => $extension,
                    'size' => $size,
                    'size_formatted' => $this->formatFileSize($size),
                    'created_at' => date('Y-m-d H:i:s', $lastModified),
                    'created_at_formatted' => \Carbon\Carbon::createFromTimestamp($lastModified)->format('d/m/Y H:i'),
                    'download_url' => "/api/download-export/{$exportId}",
                    'period' => $cacheData['period'] ?? null,
                    'record_count' => $cacheData['record_count'] ?? null,
                ];
            }
        }
        
        // Sort by created_at descending (newest first)
        usort($exports, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    /**
     * API: Get pending/running exports for current user
     * Phase 43: Use only Redis cache (removed database due to readonly DB)
     * Note: This won't persist across Redis restarts, but that's acceptable
     */
    public function apiPendingExports()
    {
        // Phase 43: Return empty array - pending exports are tracked in cache
        // and will be lost if Redis restarts, but this is acceptable for this use case
        // The frontend will start a new export if needed
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    /**
     * Format file size to human readable
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        $factor = min($factor, count($units) - 1);
        
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Generate Excel file for sync mode export
     * Phase 31: Helper method for synchronous Excel generation
     */
    protected function generateExcelForSync(array $report, string $exportId): string
    {
        $filename = "bao-cao-su-dung_{$exportId}.xlsx";
        $filepath = "exports/{$filename}";
        $columns = $report['columns'];
        $columnComments = $this->getColumnCommentsForExcel();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo sử dụng');

        // Set header row
        $colIndex = 1;
        foreach ($columns as $col) {
            $cellRef = $this->getColumnLetter($colIndex) . '1';
            $sheet->setCellValue($cellRef, $col['label']);

            // Add comment explaining data source if available
            if (isset($columnComments[$col['key']])) {
                $comment = $sheet->getComment($cellRef);
                $comment->getText()->createTextRun($columnComments[$col['key']]);
                $comment->setWidth('300pt');
                $comment->setHeight('100pt');
            }

            $colIndex++;
        }

        // Style header row
        $lastColLetter = $this->getColumnLetter(count($columns));
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Track column indices for formula columns
        $colMap = [];
        foreach ($columns as $idx => $col) {
            $colMap[$col['key']] = $idx + 1;
        }

        // Data rows
        $rowIndex = 2;
        foreach ($report['data'] as $row) {
            $colIndex = 1;
            foreach ($columns as $col) {
                $cellRef = $this->getColumnLetter($colIndex) . $rowIndex;
                $value = $row[$col['key']] ?? '';

                // Handle different column types
                if ($this->isFormulaColumnForExcel($col['key'])) {
                    $formula = $this->getFormulaForColumnExcel($col['key'], $rowIndex, $colMap);
                    if ($formula) {
                        $sheet->setCellValue($cellRef, $formula);
                    } else {
                        $sheet->setCellValue($cellRef, is_numeric($value) ? (float)$value : $value);
                    }
                } elseif (in_array($col['key'], ['payment_date', 'start_date', 'end_date', 'cancel_date']) && $value) {
                    $dateValue = \Carbon\Carbon::parse($value)->format('d/m/Y H:i:s');
                    $sheet->setCellValue($cellRef, $dateValue);
                } elseif ((str_contains($col['key'], '_amount') || $col['key'] === 'price_per_lesson') && is_numeric($value)) {
                    $sheet->setCellValue($cellRef, (float)$value);
                    $sheet->getStyle($cellRef)->getNumberFormat()->setFormatCode('#,##0');
                } elseif (str_contains($col['key'], '_lessons') && is_numeric($value)) {
                    $sheet->setCellValue($cellRef, (int)$value);
                } else {
                    $sheet->setCellValue($cellRef, $value);
                }

                $colIndex++;
            }
            $rowIndex++;
        }

        // Apply borders to data area
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("A2:{$lastColLetter}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);
        }

        // Auto-size columns
        foreach (range(1, count($columns)) as $colIdx) {
            $colLetter = $this->getColumnLetter($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Save to temp file then move to storage
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        \Illuminate\Support\Facades\Storage::disk('local')->put($filepath, file_get_contents($tempFile));
        unlink($tempFile);

        return $filename;
    }

    /**
     * Get column letter from index (1=A, 2=B, ... 27=AA, etc)
     */
    protected function getColumnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $mod = ($columnIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnIndex = intval(($columnIndex - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Check if column should have a formula
     * Phase 106: Removed formula columns - all values are pre-calculated in PHP
     * since intermediate columns (total_increase, total_decrease) were removed
     */
    protected function isFormulaColumnForExcel(string $key): bool
    {
        // Phase 106: No formula columns - closing balance uses pre-calculated values
        // because the intermediate columns are no longer in the output
        return false;
    }

    /**
     * Get Excel formula for calculated columns
     * Phase 106: Deprecated - no formula columns in reduced column set
     */
    protected function getFormulaForColumnExcel(string $key, int $rowIndex, array $colMap): ?string
    {
        return null;
    }

    /**
     * Get comments for each column explaining data source
     * Phase 106: Updated to match reduced column set
     */
    protected function getColumnCommentsForExcel(): array
    {
        return [
            'package_type' => "Phân loại gói học\nNguồn: Phân tích từ tên môn học (tlang_identifier)\nGiá trị: SPW, IE, EP, ES, FT, OTHER",
            'student_code' => "Mã học viên\nNguồn: tbl_users.user_username",
            'student_name' => "Tên đầy đủ của học viên\nNguồn: tbl_users.user_first_name + user_last_name",
            'email' => "Email học viên\nNguồn: tbl_users.user_email",
            'package_name' => "Tên gói học/môn học\nNguồn: tbl_teach_languages.tlang_identifier",
            'total_sessions' => "Tổng số buổi học hoàn thành trong kỳ\nNguồn: COUNT(tbl_order_lessons.ordles_id) WHERE ordles_status = 3 AND ordles_lesson_starttime trong kỳ",
            'billing_id' => "ID giao dịch thanh toán\nNguồn: ordpay_txn_id (billing) hoặc order_extra_data.package_id (import)",
            'payment_date' => "Ngày thanh toán đơn hàng\nNguồn: tbl_orders.order_addedon",
            'start_date' => "Ngày buổi học đầu tiên\nNguồn: MIN(ordles_lesson_starttime)",
            'end_date' => "Ngày buổi học cuối cùng\nNguồn: MAX(ordles_lesson_endtime)",
            'status' => "Trạng thái gói học\nGiá trị: Completed, Active, Pending, Unknown",
            'price_per_lesson' => "Giá thực tế mỗi buổi học\nCông thức: (SUM(ordles_amount) - SUM(ordles_discount)) / COUNT(ordles_id)",
            'opening_lessons' => "Số buổi dư đầu kỳ\n= 0 nếu đơn hàng được tạo sau ngày đầu kỳ",
            'opening_amount' => "Số tiền dư đầu kỳ\nCông thức: Số buổi × Giá/buổi\n= 0 nếu đơn hàng được tạo sau ngày đầu kỳ",
            'purchased_lessons' => "Số buổi mua trong kỳ",
            'purchased_amount' => "Số tiền mua trong kỳ",
            'used_lessons' => "Số buổi đã sử dụng trong kỳ",
            'used_amount' => "Số tiền đã sử dụng",
            'cancelled_lessons' => "Số buổi bị hủy\nNguồn: COUNT WHERE ordles_status=4",
            'closing_lessons' => "Số buổi cuối kỳ\nĐầu kỳ + Tổng tăng - Tổng giảm",
            'closing_amount' => "Số tiền cuối kỳ\nĐầu kỳ + Tổng tăng - Tổng giảm",
            'zeus_order_id' => "Mã đơn hàng Zeus\nNguồn: tbl_orders.order_id",
        ];
    }

    // ========================================
    // Phase 60: Weekly Plan Export
    // ========================================

    /**
     * API: Export weekly plan to Excel
     * Required params: from (YYYY-MM), to (YYYY-MM) OR from_date (YYYY-MM-DD), to_date (YYYY-MM-DD)
     * Phase 224: Added support for day-level date range (from_date, to_date)
     * Returns: Excel file download
     */
    public function apiExportWeeklyPlan(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Phase 224: Support day-level date range
        if ($fromDate && $toDate) {
            try {
                $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();

                $service = new \App\Services\WeeklyPlanService($startDate->copy()->startOfMonth(), $endDate->copy()->endOfMonth());
                $filename = $service->generateExcel($fromDate, $toDate);

                return response()->json([
                    'success' => true,
                    'message' => 'Xuất file thành công!',
                    'filename' => $filename,
                    'download_url' => "/api/download-plan/{$filename}",
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Export weekly plan failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xuất file: ' . $e->getMessage(),
                ], 500);
            }
        }

        if (!$from || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn khoảng thời gian (from và to dạng YYYY-MM)',
            ], 400);
        }

        try {
            $startMonth = \Carbon\Carbon::createFromFormat('Y-m', $from)->startOfMonth();
            $endMonth = \Carbon\Carbon::createFromFormat('Y-m', $to)->endOfMonth();

            $service = new \App\Services\WeeklyPlanService($startMonth, $endMonth);
            $filename = $service->generateExcel();

            // Return download URL
            return response()->json([
                'success' => true,
                'message' => 'Xuất file thành công!',
                'filename' => $filename,
                'download_url' => "/api/download-plan/{$filename}",
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Export weekly plan failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Download weekly plan Excel file
     */
    public function apiDownloadPlan(string $filename)
    {
        $filepath = "exports/{$filename}";

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File không tồn tại trên server',
            ], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * API: Get teacher country weekly summary
     * Phase 62: Returns scheduled sessions count by teacher country and week
     * Phase 74: Updated to return data with class size breakdown
     * Phase 224: Added optional from_date/to_date filtering
     */
    public function apiTeacherCountryWeekly(Request $request)
    {
        try {
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $data = \App\Services\WeeklyPlanService::getTeacherCountryWeeklyWithClassSize($fromDate, $toDate);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get teacher country weekly failed", [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get teacher country unscheduled summary
     * Phase 66: Returns unscheduled sessions count by teacher country
     */
    public function apiTeacherCountryUnscheduled()
    {
        try {
            $data = \App\Services\WeeklyPlanService::getTeacherCountryUnscheduledSummary();
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get teacher country unscheduled failed", [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get weekly unscheduled breakdown
     * Phase 80: Returns unscheduled sessions by week = (active_students × lessons_per_week) - scheduled
     */
    public function apiWeeklyUnscheduledBreakdown(\Illuminate\Http\Request $request)
    {
        try {
            $lessonsPerWeek = (int) $request->input('lessons_per_week', 2);
            $lessonsPerWeek = max(1, min(7, $lessonsPerWeek)); // Clamp between 1-7
            
            $data = \App\Services\WeeklyPlanService::getWeeklyUnscheduledBreakdown($lessonsPerWeek);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get weekly unscheduled breakdown failed", [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // Phase 98: Cancellation Stats from tbl_session_logs
    // ========================================

    /**
     * API: Get cancellation statistics for a given period
     * Queries tbl_session_logs for sesslog_changed_status = 4
     * Supports: today, yesterday, day_before, this_week, last_week, this_month
     * Also supports custom date range via from_date + to_date parameters
     */
    public function apiCancellationStats(Request $request)
    {
        try {
            // Phase 138: Support program filter
            $program = $request->input('program', 'all');
            $this->dashboardService->setProgram($program);

            $period = $request->input('period', 'today');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Custom date range takes priority
            if ($fromDate && $toDate) {
                $start = \Carbon\Carbon::parse($fromDate)->startOfDay();
                $end = \Carbon\Carbon::parse($toDate)->endOfDay();
            } else {
                // Pre-defined periods
                switch ($period) {
                    case 'yesterday':
                        $start = now()->subDay()->startOfDay();
                        $end = now()->subDay()->endOfDay();
                        break;
                    case 'day_before':
                        $start = now()->subDays(2)->startOfDay();
                        $end = now()->subDays(2)->endOfDay();
                        break;
                    case 'this_week':
                        $start = now()->startOfWeek();
                        $end = now()->endOfWeek();
                        break;
                    case 'last_week':
                        $start = now()->subWeek()->startOfWeek();
                        $end = now()->subWeek()->endOfWeek();
                        break;
                    case 'this_month':
                        $start = now()->startOfMonth();
                        $end = now()->endOfMonth();
                        break;
                    case 'today':
                    default:
                        $start = now()->startOfDay();
                        $end = now()->endOfDay();
                        break;
                }
            }

            // Phase 99: pagination, filter, search options
            $options = [
                'page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('per_page', 50),
                'user_type_filter' => $request->input('user_type_filter'),
                'search' => $request->input('search'),
            ];

            $data = $this->dashboardService->getCancellationStats($start, $end, $options);

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period,
                'from_date' => $start->format('Y-m-d'),
                'to_date' => $end->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get cancellation stats failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get leave affected sessions detail list (Phase 101)
     */
    public function apiLeaveAffectedSessions(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
            $search = $request->input('search');
            $replacementFilter = $request->input('replacement_type');

            $data = $this->dashboardService->getLeaveAffectedSessionsDetail($page, $perPage, $search, $replacementFilter);

            return response()->json([
                'success' => true,
                'data' => $data['details'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get leave affected sessions failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get first orders with successful lessons after payment (Phase 103)
     * Returns paginated list with search support
     */
    public function apiFirstOrdersWithLessons(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(10000, max(1, (int) $request->input('per_page', 20)));
            $search = $request->input('search');
            $sortBy = $request->input('sort_by');
            $sortDir = $request->input('sort_dir', 'desc');
            $filterNullLesson = $request->boolean('filter_null_lesson', false);
            $daysDifference = $request->has('days_difference') ? (int) $request->input('days_difference') : null;
            $orderDateFrom = $request->input('order_date_from');
            $orderDateTo = $request->input('order_date_to');

            $data = $this->dashboardService->getFirstOrdersWithSuccessfulLessons($page, $perPage, $search, $sortBy, $sortDir, $filterNullLesson, $daysDifference, $orderDateFrom, $orderDateTo);

            return response()->json([
                'success' => true,
                'data' => $data['data'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get first orders with lessons failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // Phase 112: Trial Lessons Statistics Table
    // ========================================

    /**
     * API: Get trial lessons list with teacher feedback/assessment
     * Used in the Revenue page for trial statistics table
     */
    public function apiTrialLessonsList(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(10000, max(1, (int) $request->input('per_page', 20)));
            $search = $request->input('search');
            $sortBy = $request->input('sort_by');
            $sortDir = $request->input('sort_dir', 'desc');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $data = $this->dashboardService->getTrialLessonsList($page, $perPage, $search, $sortBy, $sortDir, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data['data'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get trial lessons list failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // Phase 113: Teacher Availability Grid
    // ========================================

    /**
     * API: Get teacher availability grid for a given week
     * Returns a slot booking layout showing available teacher count per time slot
     */
    public function apiTeacherAvailability(Request $request)
    {
        try {
            $weekStart = $request->input('week_start', now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'));
            $teacherSearch = $request->input('teacher_search');
            $trialFilter = $request->input('trial_filter', 'all'); // 'all', 'exclude', 'only'
            $timeSlotsParam = $request->input('time_slots'); // comma-separated: "07:00,07:30,08:00"
            $timeSlotFilter = $timeSlotsParam ? explode(',', $timeSlotsParam) : [];
            $slotMode = $request->input('slot_mode', 'odd'); // 'even' or 'odd'
            $teacherType = $request->input('teacher_type', ''); // 'VN', 'PHIL', 'NN'

            $data = $this->dashboardService->getTeacherAvailabilityGrid(
                $weekStart,
                $teacherSearch,
                $trialFilter,
                $timeSlotFilter,
                $slotMode,
                $teacherType
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get teacher availability failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get slot detail - list of available teachers for a specific day and time slot
     */
    public function apiTeacherAvailabilitySlotDetail(Request $request)
    {
        try {
            $date = $request->input('date');
            $timeSlot = $request->input('time_slot');
            $trialFilter = $request->input('trial_filter', 'all'); // 'all', 'exclude', 'only'
            $teacherSearch = $request->input('teacher_search');
            $teacherType = $request->input('teacher_type', '');

            if (!$date || !$timeSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu tham số date và time_slot',
                ], 400);
            }

            $data = $this->dashboardService->getTeacherAvailabilitySlotDetail(
                $date,
                $timeSlot,
                $trialFilter,
                $teacherSearch,
                $teacherType
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Get teacher availability slot detail failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Export teacher availability grid to Excel (CSV)
     */
    public function apiExportTeacherAvailability(Request $request)
    {
        try {
            $weekStart = $request->input('week_start', now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'));
            $teacherSearch = $request->input('teacher_search');
            $trialFilter = $request->input('trial_filter', 'all'); // 'all', 'exclude', 'only'
            $timeSlotsParam = $request->input('time_slots');
            $timeSlotFilter = $timeSlotsParam ? explode(',', $timeSlotsParam) : [];
            $slotMode = $request->input('slot_mode', 'odd');
            $teacherType = $request->input('teacher_type', '');

            $data = $this->dashboardService->getTeacherAvailabilityGrid(
                $weekStart,
                $teacherSearch,
                $trialFilter,
                $timeSlotFilter,
                $slotMode,
                $teacherType
            );

            // Build CSV with slot booking layout
            $days = $data['days'];
            $timeSlots = $data['time_slots'];
            $grid = $data['grid'];
            $teacherDetail = $data['teacher_detail'];
            $teacherInfoMap = [];
            foreach ($data['teachers'] as $t) {
                $teacherInfoMap[$t['id']] = $t;
            }

            $filename = 'GV_kha_dung_' . $data['week_start'] . '_' . $data['week_end'] . '.csv';

            $callback = function () use ($days, $timeSlots, $grid, $teacherDetail, $teacherInfoMap, $data) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM for Excel
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Summary row
                fputcsv($file, ['Danh sách GV khả dụng theo khung giờ', 'Tuần: ' . $data['week_label'], 'Tổng GV: ' . $data['teacher_count']]);
                fputcsv($file, []);

                // === SHEET 1: Summary Grid ===
                fputcsv($file, ['=== BẢNG TỔNG HỢP (Số GV khả dụng / Tổng ca trống) ===']);

                // Header row: Khung giờ | Day1 | Day2 | ...
                $headerRow = ['Khung giờ (UTC+7)'];
                foreach ($days as $day) {
                    $headerRow[] = $day['label'];
                }
                fputcsv($file, $headerRow);

                // Data rows
                foreach ($timeSlots as $slot) {
                    $row = [$slot];
                    foreach ($days as $day) {
                        $dateStr = $day['date'];
                        $cellData = $grid[$dateStr][$slot] ?? ['available' => 0, 'total_slots' => 0];
                        $row[] = $cellData['available'] . '/' . $cellData['total_slots'];
                    }
                    fputcsv($file, $row);
                }

                fputcsv($file, []);
                fputcsv($file, ['=== CHI TIẾT GV KHẢ DỤNG ===']);
                fputcsv($file, []);

                // === SHEET 2: Detail per slot ===
                // For each day and time slot, list the available teachers
                foreach ($days as $day) {
                    $dateStr = $day['date'];
                    fputcsv($file, ['--- ' . $day['label'] . ' (' . $dateStr . ') ---']);
                    fputcsv($file, ['Khung giờ', 'Số GV khả dụng', 'Danh sách GV']);

                    foreach ($timeSlots as $slot) {
                        $teacherIds = $teacherDetail[$dateStr][$slot] ?? [];
                        $teacherNames = [];
                        foreach ($teacherIds as $tid) {
                            if (isset($teacherInfoMap[$tid])) {
                                $t = $teacherInfoMap[$tid];
                                $trialFlag = $t['can_teach_trial'] ? ' [Trial]' : '';
                                $typeFlag = isset($t['teacher_type']) && $t['teacher_type'] !== 'N/A' ? ' [' . $t['teacher_type'] . ']' : '';
                                $teacherNames[] = $t['name'] . ' (' . $t['email'] . ')' . $typeFlag . $trialFlag;
                            }
                        }
                        fputcsv($file, [$slot, count($teacherIds), implode('; ', $teacherNames)]);
                    }

                    fputcsv($file, []);
                }
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Export teacher availability failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Search available teachers for specific schedule patterns (multi-slot)
     * Supports multiple khung giờ, e.g., 19:10 T3, 19:10 T5, 20:20 T3
     * Also backward compatible with single time+days format
     */
    public function apiSearchTeacherSchedule(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $trialFilter = $request->input('trial_filter', 'all'); // 'all', 'exclude', 'only'
            $teacherType = $request->input('teacher_type', '');
            $teacherSearch = $request->input('teacher_search');

            if (!$startDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu tham số start_date',
                ], 400);
            }

            // New multi-slot format: slots=[{"time":"19:10","day":"2"},{"time":"19:10","day":"4"}]
            $slotsParam = $request->input('slots');
            if ($slotsParam) {
                $slots = json_decode($slotsParam, true);
                if (!is_array($slots) || empty($slots)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tham số slots không hợp lệ',
                    ], 400);
                }

                // Validate and sanitize slots (max 7)
                $slots = array_slice($slots, 0, 7);
                $validSlots = [];
                foreach ($slots as $slot) {
                    if (!empty($slot['time']) && !empty($slot['day'])) {
                        $validSlots[] = [
                            'time' => $slot['time'],
                            'day' => (int) $slot['day'],
                        ];
                    }
                }

                if (empty($validSlots)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có khung giờ hợp lệ',
                    ], 400);
                }

                $data = $this->dashboardService->searchTeacherScheduleMultiSlot(
                    $validSlots,
                    $startDate,
                    $trialFilter,
                    $teacherType,
                    $teacherSearch
                );
            } else {
                // Backward compatible: single time + days format
                $time = $request->input('time');
                $daysParam = $request->input('days');

                if (!$time || !$daysParam) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Thiếu tham số slots hoặc time+days',
                    ], 400);
                }

                $daysOfWeek = array_map('intval', explode(',', $daysParam));

                $data = $this->dashboardService->searchTeacherSchedule(
                    $time,
                    $daysOfWeek,
                    $startDate,
                    $trialFilter,
                    $teacherType,
                    $teacherSearch
                );
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Search teacher schedule failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display LCMS learning progress report page (Phase 120)
     */
    public function lcms(Request $request)
    {
        return view('dashboard.lcms');
    }

    /**
     * API: LCMS overview stats (grand total for all SpeakWell courses)
     */
    public function apiLcmsOverview()
    {
        try {
            $data = $this->lcmsService->getOverviewStats();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS overview stats failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS course breakdown stats
     */
    public function apiLcmsCourseBreakdown()
    {
        try {
            $data = $this->lcmsService->getCourseBreakdownStats();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS course breakdown failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS student-level stats (paginated)
     */
    public function apiLcmsStudentStats(Request $request)
    {
        try {
            $courseId = $request->input('course_id') ? (int) $request->input('course_id') : null;
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 20);
            $search = $request->input('search');

            $data = $this->lcmsService->getStudentStats($courseId, $page, $perPage, $search);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS student stats failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS top performing students
     */
    public function apiLcmsTopStudents(Request $request)
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $data = $this->lcmsService->getTopStudents($limit);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS top students failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS at-risk students (lowest completion)
     */
    public function apiLcmsAtRiskStudents(Request $request)
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $data = $this->lcmsService->getAtRiskStudents($limit);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS at-risk students failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS section type distribution (Phase 121)
     */
    public function apiLcmsSectionDistribution()
    {
        try {
            $data = $this->lcmsService->getSectionTypeDistribution();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS section distribution failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS score distribution (Phase 121)
     */
    public function apiLcmsScoreDistribution()
    {
        try {
            $data = $this->lcmsService->getScoreDistribution();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS score distribution failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS completion trend over time (Phase 121)
     */
    public function apiLcmsCompletionTrend()
    {
        try {
            $data = $this->lcmsService->getCompletionTrend();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS completion trend failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS enrollment overview from lcms_course_student (Phase 121)
     */
    public function apiLcmsEnrollmentOverview()
    {
        try {
            $data = $this->lcmsService->getEnrollmentOverview();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS enrollment overview failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS student demographics (Phase 121)
     */
    public function apiLcmsStudentDemographics()
    {
        try {
            $data = $this->lcmsService->getStudentDemographics();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS student demographics failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Clear LCMS cache (Phase 122)
     */
    public function apiLcmsClearCache()
    {
        $success = $this->lcmsService->clearLcmsCache();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Cache LCMS đã được xóa thành công' : 'Có lỗi khi xóa cache LCMS',
        ]);
    }

    /**
     * API: Get LCMS cache refresh timestamp (Phase 122)
     * Phase 180: Updated cache_ttl_minutes to 180 (3 hours)
     */
    public function apiLcmsCacheRefreshedAt()
    {
        $refreshedAt = $this->lcmsService->getCacheRefreshedAt();

        return response()->json([
            'success' => true,
            'data' => [
                'refreshed_at' => $refreshedAt,
                'cache_ttl_minutes' => 180,
            ],
        ]);
    }

    /**
     * API: LCMS advanced student search (Phase 122)
     * Supports: multiple IDs, name search, gender, completion/score range
     */
    public function apiLcmsStudentStatsAdvanced(Request $request)
    {
        try {
            $filters = [
                'course_id' => $request->input('course_id'),
                'student_ids' => $request->input('student_ids'),
                'search_name' => $request->input('search_name'),
                'gender' => $request->input('gender'),
                'min_hw_completion' => $request->input('min_hw_completion'),
                'max_hw_completion' => $request->input('max_hw_completion'),
                'min_score' => $request->input('min_score'),
                'max_score' => $request->input('max_score'),
            ];

            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 20);

            $data = $this->lcmsService->getStudentStatsAdvanced($filters, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS advanced student search failed", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: LCMS deep per-student detail report (Phase 122)
     * Returns section-level breakdown with scores for a single student
     */
    public function apiLcmsStudentDetail(Request $request)
    {
        try {
            $studentId = (int) $request->input('student_id');
            if ($studentId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID không hợp lệ',
                ], 400);
            }

            $data = $this->lcmsService->getStudentDetailReport($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LCMS student detail failed", [
                'error' => $e->getMessage(),
                'student_id' => $request->input('student_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // Phase 204: Student Teacher Changes
    // ========================================

    /**
     * API: Get students who had teacher changes within a date range
     * Phase 204: Lists students with count of teacher changes, supports search/pagination/sort
     */
    public function apiStudentTeacherChanges(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn khoảng thời gian (date_from và date_to)',
                ], 400);
            }

            // Validate date format
            $dfrom = \DateTime::createFromFormat('Y-m-d', $dateFrom);
            $dto = \DateTime::createFromFormat('Y-m-d', $dateTo);
            if (!$dfrom || !$dto || $dfrom->format('Y-m-d') !== $dateFrom || $dto->format('Y-m-d') !== $dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Định dạng ngày không hợp lệ (YYYY-MM-DD)',
                ], 400);
            }

            $search = $request->input('search');
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(200, (int) $request->input('per_page', 50)));
            $sortBy = $request->input('sort_by', 'change_count');
            $sortDir = $request->input('sort_dir', 'desc');
            // Phase 208: Teacher nationality filter
            $teacherNationality = $request->input('teacher_nationality');

            $data = $this->dashboardService->getStudentsWithTeacherChanges(
                $dateFrom,
                $dateTo,
                $search,
                $page,
                $perPage,
                $sortBy,
                $sortDir,
                $teacherNationality
            );

            return response()->json([
                'success' => true,
                'data' => $data['data'],
                'total' => $data['total'],
                'page' => $data['page'],
                'per_page' => $data['per_page'],
                'total_pages' => $data['total_pages'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student teacher changes failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get teacher change detail for a specific student
     * Phase 204: Returns lesson-by-lesson timeline showing teacher changes
     */
    public function apiStudentTeacherChangeDetail(Request $request)
    {
        try {
            $studentId = (int) $request->input('student_id');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            if ($studentId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID không hợp lệ',
                ], 400);
            }

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn khoảng thời gian',
                ], 400);
            }

            $data = $this->dashboardService->getStudentTeacherChangeDetail($studentId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student teacher change detail failed", [
                'error' => $e->getMessage(),
                'student_id' => $request->input('student_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get chart data for student teacher changes
     * Phase 205: Returns distribution, top students, and monthly trend data for charts
     */
    public function apiStudentTeacherChangeChartData(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn khoảng thời gian (date_from và date_to)',
                ], 400);
            }

            $dfrom = \DateTime::createFromFormat('Y-m-d', $dateFrom);
            $dto = \DateTime::createFromFormat('Y-m-d', $dateTo);
            if (!$dfrom || !$dto || $dfrom->format('Y-m-d') !== $dateFrom || $dto->format('Y-m-d') !== $dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Định dạng ngày không hợp lệ (YYYY-MM-DD)',
                ], 400);
            }

            $data = $this->dashboardService->getStudentTeacherChangeChartData($dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student teacher change chart data failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
