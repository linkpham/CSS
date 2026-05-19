<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CareSoftController;
use App\Http\Controllers\CsiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth.admin');

/*
|--------------------------------------------------------------------------
| Dashboard Routes (Protected - Authentication required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth.admin')->group(function () {
    // Dashboard Overview
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Daily Operations
    Route::get('/daily-ops', [DashboardController::class, 'dailyOps'])->name('dashboard.daily-ops');

    // Teachers Module
    Route::get('/teachers', [DashboardController::class, 'teachers'])->name('dashboard.teachers');

    // Revenue Module - RESTRICTED to Accountant role (role_id=5) only
    Route::middleware('can.view.revenue')->group(function () {
        Route::get('/revenue', [DashboardController::class, 'revenue'])->name('dashboard.revenue');
    });

    // Quality Module
    Route::get('/quality', [DashboardController::class, 'quality'])->name('dashboard.quality');

    // Learning Path Module
    Route::get('/learning-path', [DashboardController::class, 'learningPath'])->name('dashboard.learning-path');

    // Teacher Management Module (Phase 4) — Phase 214: Restricted to role_id (3,4,6,7,9,11,14,29) + privileged users
    Route::middleware('can.view.teacher.mgmt')->group(function () {
        Route::get('/teacher-management', [DashboardController::class, 'teacherManagement'])->name('dashboard.teacher-management');
    });

    // LCMS Learning Progress Report (Phase 120)
    Route::get('/lcms', [DashboardController::class, 'lcms'])->name('dashboard.lcms');
});

/*
|--------------------------------------------------------------------------
| API Routes (Protected - Authentication required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth.admin')->prefix('api')->group(function () {
    // Chart data (non-revenue)
    Route::get('/overview', [DashboardController::class, 'apiOverview']);
    Route::get('/user-chart', [DashboardController::class, 'apiUserChart']);
    Route::get('/lesson-chart', [DashboardController::class, 'apiLessonChart']);

    // Revenue-related APIs - RESTRICTED to Accountant role (role_id=5) only
    Route::middleware('can.view.revenue')->group(function () {
        Route::get('/revenue-chart', [DashboardController::class, 'apiRevenueChart']);
        Route::get('/top-learners', [DashboardController::class, 'apiTopLearners']);

        // Usage Report (Phase 26)
        Route::get('/usage-report', [DashboardController::class, 'apiUsageReport']);
        Route::get('/export-usage-report', [DashboardController::class, 'apiExportUsageReport']);

        // Async Export (Phase 28)
        Route::post('/start-export-usage-report', [DashboardController::class, 'apiStartExportUsageReport']);
        Route::get('/export-status/{exportId}', [DashboardController::class, 'apiExportStatus']);
        Route::get('/download-export/{exportId}', [DashboardController::class, 'apiDownloadExport']);
        Route::delete('/cancel-export/{exportId}', [DashboardController::class, 'apiCancelExport']);
        
        // Phase 31: Export file list
        Route::get('/export-list', [DashboardController::class, 'apiExportList']);
        
        // Phase 41: Pending exports (for resuming progress tracking)
        Route::get('/pending-exports', [DashboardController::class, 'apiPendingExports']);

        // Phase 103: First orders with successful lessons
        Route::get('/first-orders-with-lessons', [DashboardController::class, 'apiFirstOrdersWithLessons']);

        // Phase 112: Trial lessons statistics with feedback
        Route::get('/trial-lessons-list', [DashboardController::class, 'apiTrialLessonsList']);
    });

    // Search & filter
    Route::get('/search-users', [DashboardController::class, 'apiSearchUsers']);
    Route::get('/stats-range', [DashboardController::class, 'apiStatsForRange']);

    // Top performers (non-revenue related)
    Route::get('/top-teachers', [DashboardController::class, 'apiTopTeachers']);

    // Learning path & feedback
    Route::get('/learning-path-stats', [DashboardController::class, 'apiLearningPathStats']);
    Route::get('/feedback-stats', [DashboardController::class, 'apiFeedbackStats']);
    Route::get('/feedback-trend', [DashboardController::class, 'apiFeedbackTrend']);
    Route::get('/feedback/{id}', [DashboardController::class, 'apiFeedbackDetail']);

    // Session quality
    Route::get('/session-outcome', [DashboardController::class, 'apiSessionOutcome']);
    Route::get('/session-quality', [DashboardController::class, 'apiSessionQuality']);
    Route::get('/attendance-issues', [DashboardController::class, 'apiAttendanceIssues']);

    // Teacher leave management (Phase 4)
    Route::get('/leave-stats', [DashboardController::class, 'apiLeaveStats']);
    Route::get('/leave-trend', [DashboardController::class, 'apiLeaveTrend']);

    // Quiz/Exam (Phase 4)
    Route::get('/quiz-stats', [DashboardController::class, 'apiQuizStats']);
    Route::get('/quiz-attempts', [DashboardController::class, 'apiQuizAttempts']);

    // Period-based stats
    Route::get('/period-stats', [DashboardController::class, 'apiPeriodStats']);

    // Login statistics
    Route::get('/login-stats', [DashboardController::class, 'apiLoginStats']);
    Route::get('/login-stats-by-type', [DashboardController::class, 'apiLoginStatsByType']);
    Route::get('/login-trend', [DashboardController::class, 'apiLoginTrend']);
    Route::get('/logins-by-hour', [DashboardController::class, 'apiLoginsByHour']);
    Route::get('/logins-by-day-of-week', [DashboardController::class, 'apiLoginsByDayOfWeek']);
    Route::get('/logins-by-source', [DashboardController::class, 'apiLoginsBySource']);

    // Program/Curriculum statistics
    Route::get('/program-stats', [DashboardController::class, 'apiProgramStats']);
    Route::get('/program-stats-summary', [DashboardController::class, 'apiProgramStatsSummary']);
    Route::get('/program-category-stats', [DashboardController::class, 'apiProgramCategoryStats']);
    Route::get('/program-enrollment-trend', [DashboardController::class, 'apiProgramEnrollmentTrend']);
    Route::get('/programs-list', [DashboardController::class, 'apiProgramsList']);
    Route::get('/program/{id}', [DashboardController::class, 'apiProgramDetail']);

    // Comparison statistics (today vs yesterday, week vs week, month vs month)
    Route::get('/comparison-stats', [DashboardController::class, 'apiComparisonStats']);
    Route::get('/session-stats', [DashboardController::class, 'apiSessionStats']);

    // Export session stats by month range
    Route::get('/export-session-stats', [DashboardController::class, 'apiExportSessionStats']);

    // Export session stats by date range (daily breakdown)
    Route::get('/export-daily-session-stats', [DashboardController::class, 'apiExportDailySessionStats']);

    // Acceptance codes statistics
    Route::get('/acceptance-codes-list', [DashboardController::class, 'apiAcceptanceCodesList']);
    Route::get('/acceptance-codes-stats', [DashboardController::class, 'apiAcceptanceCodeStats']);
    Route::get('/penalized-teachers-details', [DashboardController::class, 'apiPenalizedTeachersDetails']);

    // Never logged in students and multi-lesson students
    Route::get('/never-logged-in-students', [DashboardController::class, 'apiNeverLoggedInStudentsDetail']);
    Route::get('/students-multiple-lessons', [DashboardController::class, 'apiStudentsWithMultipleLessons']);

    // Cache management
    Route::post('/clear-cache', [DashboardController::class, 'apiClearCache']);
    Route::get('/cache-refreshed-at', [DashboardController::class, 'apiCacheRefreshedAt']);

    // Real-time lesson status (ongoing, upcoming, remaining, heatmap)
    Route::get('/real-time-status', [DashboardController::class, 'apiRealTimeStatus']);
    Route::get('/ongoing-lessons', [DashboardController::class, 'apiOngoingLessons']);
    Route::get('/upcoming-lessons', [DashboardController::class, 'apiUpcomingLessons']);
    Route::get('/remaining-lessons', [DashboardController::class, 'apiRemainingLessons']);
    Route::get('/time-slot-heatmap', [DashboardController::class, 'apiTimeSlotHeatmap']);
    Route::get('/yesterday-time-slot-heatmap', [DashboardController::class, 'apiYesterdayTimeSlotHeatmap']);

    // Teacher login status (no-show, late entry, early exit details)
    Route::get('/teacher-no-show-details', [DashboardController::class, 'apiTeacherNoShowDetails']);
    Route::get('/teacher-late-entry-details', [DashboardController::class, 'apiTeacherLateEntryDetails']);
    Route::get('/teacher-early-exit-details', [DashboardController::class, 'apiTeacherEarlyExitDetails']);

    // Custom date session stats (for "Tùy chọn" date picker)
    Route::get('/custom-date-session-stats', [DashboardController::class, 'apiCustomDateSessionStats']);

    // Phase 60: Weekly Plan Export
    Route::get('/export-weekly-plan', [DashboardController::class, 'apiExportWeeklyPlan']);
    Route::get('/download-plan/{filename}', [DashboardController::class, 'apiDownloadPlan']);
    
    // Phase 62: Teacher Country Weekly Summary
    Route::get('/teacher-country-weekly', [DashboardController::class, 'apiTeacherCountryWeekly']);
    
    // Phase 66: Teacher Country Unscheduled Summary
    Route::get('/teacher-country-unscheduled', [DashboardController::class, 'apiTeacherCountryUnscheduled']);
    
    // Phase 80: Weekly Unscheduled Breakdown
    Route::get('/weekly-unscheduled-breakdown', [DashboardController::class, 'apiWeeklyUnscheduledBreakdown']);

    // Phase 98: Cancellation Stats from tbl_session_logs
    Route::get('/cancellation-stats', [DashboardController::class, 'apiCancellationStats']);

    // Phase 101: Leave affected sessions
    Route::get('/leave-affected-sessions', [DashboardController::class, 'apiLeaveAffectedSessions']);

    // Phase 113: Teacher availability grid
    Route::get('/teacher-availability', [DashboardController::class, 'apiTeacherAvailability']);
    Route::get('/teacher-availability-slot-detail', [DashboardController::class, 'apiTeacherAvailabilitySlotDetail']);
    Route::get('/export-teacher-availability', [DashboardController::class, 'apiExportTeacherAvailability']);

    // Phase 114: Schedule search
    Route::get('/search-teacher-schedule', [DashboardController::class, 'apiSearchTeacherSchedule']);

    // Phase 120: LCMS Learning Progress
    Route::get('/lcms/overview', [DashboardController::class, 'apiLcmsOverview']);
    Route::get('/lcms/course-breakdown', [DashboardController::class, 'apiLcmsCourseBreakdown']);
    Route::get('/lcms/student-stats', [DashboardController::class, 'apiLcmsStudentStats']);
    Route::get('/lcms/top-students', [DashboardController::class, 'apiLcmsTopStudents']);
    Route::get('/lcms/at-risk-students', [DashboardController::class, 'apiLcmsAtRiskStudents']);

    // Phase 121: LCMS Enhanced Metrics
    Route::get('/lcms/section-distribution', [DashboardController::class, 'apiLcmsSectionDistribution']);
    Route::get('/lcms/score-distribution', [DashboardController::class, 'apiLcmsScoreDistribution']);
    Route::get('/lcms/completion-trend', [DashboardController::class, 'apiLcmsCompletionTrend']);
    Route::get('/lcms/enrollment-overview', [DashboardController::class, 'apiLcmsEnrollmentOverview']);
    Route::get('/lcms/student-demographics', [DashboardController::class, 'apiLcmsStudentDemographics']);

    // Phase 122: LCMS Cache Management & Advanced Search
    Route::post('/lcms/clear-cache', [DashboardController::class, 'apiLcmsClearCache']);
    Route::get('/lcms/cache-refreshed-at', [DashboardController::class, 'apiLcmsCacheRefreshedAt']);
    Route::get('/lcms/student-stats-advanced', [DashboardController::class, 'apiLcmsStudentStatsAdvanced']);
    Route::get('/lcms/student-detail', [DashboardController::class, 'apiLcmsStudentDetail']);

    // Phase 204: Student teacher changes — Phase 214: Restricted to teacher mgmt roles
    Route::middleware('can.view.teacher.mgmt')->group(function () {
        Route::get('/student-teacher-changes', [DashboardController::class, 'apiStudentTeacherChanges']);
        Route::get('/student-teacher-change-detail', [DashboardController::class, 'apiStudentTeacherChangeDetail']);
        // Phase 205: Student teacher change chart data
        Route::get('/student-teacher-change-chart-data', [DashboardController::class, 'apiStudentTeacherChangeChartData']);
    });
});

/*
|--------------------------------------------------------------------------
| CareSoft CSKH Dashboard Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth.admin')->group(function () {
    Route::get('/caresoft', [CareSoftController::class, 'index'])->name('caresoft.index');
});

/*
|--------------------------------------------------------------------------
| CSI (Chăm sóc CSI) Dashboard Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth.admin')->group(function () {
    Route::get('/csi', [CsiController::class, 'index'])->name('csi.index');
});

Route::middleware('auth.admin')->prefix('api/csi')->group(function () {
    Route::get('/summary', [CsiController::class, 'apiSummary']);
    Route::get('/students', [CsiController::class, 'apiStudents']);
    Route::get('/health-distribution', [CsiController::class, 'apiHealthDistribution']);
    Route::get('/css-performance', [CsiController::class, 'apiCssPerformance']);
    Route::get('/score-distribution', [CsiController::class, 'apiScoreDistribution']);
    Route::get('/teacher-warning', [CsiController::class, 'apiTeacherWarning']);
    Route::get('/ews', [CsiController::class, 'apiEws']);
    Route::get('/ews/{studentId}/detail', [CsiController::class, 'apiEwsDetail']);
    Route::get('/students/{studentId}/detail', [CsiController::class, 'apiStudentDetail']);
    Route::get('/trends', [CsiController::class, 'apiTrends']);
    Route::get('/health-trends', [CsiController::class, 'apiHealthTrends']);
    Route::get('/ontrack-trends', [CsiController::class, 'apiOntrackTrends']);
    Route::get('/search', [CsiController::class, 'apiSearch']);
    Route::get('/spw-inactive', [CsiController::class, 'apiSpwInactive']);
});

Route::middleware('auth.admin')->prefix('api/caresoft')->group(function () {
    Route::get('/agent-status', [CareSoftController::class, 'apiAgentStatus']);
    Route::get('/summary', [CareSoftController::class, 'apiSummary']);
    Route::get('/tickets', [CareSoftController::class, 'apiTickets']);
    Route::get('/calls', [CareSoftController::class, 'apiCalls']);
    Route::get('/chats', [CareSoftController::class, 'apiChats']);
    Route::get('/chat-messages', [CareSoftController::class, 'apiChatMessages']);
    Route::get('/sync-status', [CareSoftController::class, 'apiSyncStatus']);
    Route::post('/trigger-sync', [CareSoftController::class, 'apiTriggerSync']);
    Route::get('/test-connection', [CareSoftController::class, 'apiTestConnection']);
});
