<?php

namespace App\Console\Commands;

use App\Services\DashboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:refresh-cache {--force : Force refresh even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-cache all dashboard data for faster page loads (runs every 15 minutes)';

    /**
     * Cache TTL in seconds (15 minutes = 900 seconds)
     * We set slightly longer TTL to ensure data is available between refreshes
     */
    protected const CACHE_TTL = 1000;

    /**
     * Human-readable labels for cache operations
     */
    protected const CACHE_LABELS = [
        'dashboard.index_data' => 'Dashboard Index',
        'dashboard.overview' => 'Overview Stats',
        'dashboard.orders_by_type' => 'Orders by Type',
        'dashboard.orders_by_status' => 'Orders by Status',
        'dashboard.issues' => 'Issues',
        'dashboard.ratings' => 'Ratings',
        'dashboard.revenue_chart' => 'Revenue Chart',
        'dashboard.user_chart' => 'User Chart',
        'dashboard.lesson_chart' => 'Lesson Chart',
        'dashboard.top_teachers' => 'Top Teachers',
        'dashboard.top_learners' => 'Top Learners',
        'dashboard.recent_orders' => 'Recent Orders',
        'dashboard.recent_lessons' => 'Recent Lessons',
        'dashboard.pending_issues' => 'Pending Issues',
        'dashboard.conversion_funnel' => 'Conversion Funnel',
        'dashboard.wallet_stats' => 'Wallet Stats',
        'dashboard.login_stats' => 'Login Stats',
        'dashboard.login_stats_by_type' => 'Login by Type',
        'dashboard.login_trend_chart' => 'Login Trend',
        'dashboard.recent_logins' => 'Recent Logins',
        'dashboard.top_active_users' => 'Top Active Users',
        'dashboard.logins_by_hour' => 'Logins by Hour',
        'dashboard.logins_by_day_of_week' => 'Logins by Day',
        'dashboard.logins_by_source' => 'Logins by Source',
        'dashboard.trial_stats' => 'Trial Stats',
        'dashboard.trial_by_status' => 'Trial by Status',
        'dashboard.trial_trend_chart' => 'Trial Trend',
        'dashboard.trial_conversion' => 'Trial Conversion',
        'dashboard.recent_trial_lessons' => 'Recent Trials',
        'dashboard.top_teachers_by_trial' => 'Top Trial Teachers',
        'dashboard.program_stats_summary' => 'Program Summary',
        'dashboard.program_category_stats' => 'Program Categories',
        'dashboard.session_stats' => 'Session Stats',
        'dashboard.acceptance_codes_list' => 'Acceptance Codes',
        'dashboard.acceptance_code_stats' => 'Code Stats',
        'dashboard.acceptance_code_map_stats' => 'Code Map Stats',
        'dashboard.learning_path_stats' => 'Learning Path',
        'dashboard.curriculum_session_distribution' => 'Curriculum Dist',
        'dashboard.program_enrollment_stats' => 'Enrollments',
        'dashboard.curriculum_session_chart' => 'Curriculum Chart',
        'dashboard.feedback_stats' => 'Feedback Stats',
        'dashboard.feedback_status' => 'Feedback Status',
        'dashboard.feedback_trend' => 'Feedback Trend',
        'dashboard.recent_feedback' => 'Recent Feedback',
        'dashboard.top_teachers_by_feedback' => 'Top Feedback Teachers',
        'dashboard.session_outcome' => 'Session Outcome',
        'dashboard.attendance_issues' => 'Attendance Issues',
        'dashboard.leave_stats' => 'Leave Stats',
        'dashboard.leave_by_status' => 'Leave by Status',
        'dashboard.recent_leave_requests' => 'Recent Leave',
        'dashboard.violation_stats' => 'Violations',
        'dashboard.teachers_with_most_leave' => 'Most Leave',
        'dashboard.leave_trend' => 'Leave Trend',
        'dashboard.leave_quota_summary' => 'Leave Quota',
        'dashboard.quiz_stats' => 'Quiz Stats',
        'dashboard.quiz_attempt_stats' => 'Quiz Attempts',
        'dashboard.recent_quiz_attempts' => 'Recent Quizzes',
        'dashboard.quiz_pass_fail_chart' => 'Quiz Pass/Fail',
        'dashboard.lesson_quality' => 'Lesson Quality',
        'dashboard.session_quality' => 'Session Quality',
        'dashboard.cancellation_breakdown' => 'Cancellations',
        'dashboard.teacher_payment_stats' => 'Teacher Payments',
        'dashboard.comparison_stats' => 'Comparison Stats',
    ];

    /**
     * Get cache operations definition
     * Returns an array of [cacheKey => callable] for all dashboard data
     */
    protected function getCacheOperations(DashboardService $dashboardService): array
    {
        return [
            // Main dashboard index data (cache-first approach for login)
            'dashboard.index_data' => fn() => $dashboardService->getDashboardIndexData(),
            
            // Main dashboard overview
            'dashboard.overview' => fn() => [
                'users' => $dashboardService->getUserStats(),
                'teachers' => $dashboardService->getTeacherStats(),
                'lessons_today' => $dashboardService->getTodayLessonStats(),
                'revenue' => $dashboardService->getRevenueStats(),
            ],
            
            // Order statistics
            'dashboard.orders_by_type' => fn() => $dashboardService->getOrderStatsByType(),
            'dashboard.orders_by_status' => fn() => $dashboardService->getOrderStatsByStatus(),
            
            // Issue and rating stats
            'dashboard.issues' => fn() => $dashboardService->getIssueStats(),
            'dashboard.ratings' => fn() => $dashboardService->getRatingStats(),
            
            // Chart data
            'dashboard.revenue_chart' => fn() => $dashboardService->getRevenueChartData(30),
            'dashboard.user_chart' => fn() => $dashboardService->getUserRegistrationChartData(30),
            'dashboard.lesson_chart' => fn() => $dashboardService->getLessonChartData(30),
            
            // Top performers
            'dashboard.top_teachers' => fn() => $dashboardService->getTopTeachers(10),
            'dashboard.top_learners' => fn() => $dashboardService->getTopLearners(10),
            
            // Recent items
            'dashboard.recent_orders' => fn() => $dashboardService->getRecentOrders(10),
            'dashboard.recent_lessons' => fn() => $dashboardService->getRecentLessons(10),
            'dashboard.pending_issues' => fn() => $dashboardService->getPendingIssues(10),
            
            // Conversion and wallet
            'dashboard.conversion_funnel' => fn() => $dashboardService->getConversionFunnelStats(),
            'dashboard.wallet_stats' => fn() => $dashboardService->getWalletStats(),
            
            // Login statistics
            'dashboard.login_stats' => fn() => $dashboardService->getLoginStats(),
            'dashboard.login_stats_by_type' => fn() => $dashboardService->getLoginStatsByUserType(),
            'dashboard.login_trend_chart' => fn() => $dashboardService->getLoginTrendChartData(14),
            'dashboard.recent_logins' => fn() => $dashboardService->getRecentLogins(10),
            'dashboard.top_active_users' => fn() => $dashboardService->getTopActiveUsers(10),
            'dashboard.logins_by_hour' => fn() => $dashboardService->getLoginsByHour(),
            'dashboard.logins_by_day_of_week' => fn() => $dashboardService->getLoginsByDayOfWeek(),
            'dashboard.logins_by_source' => fn() => $dashboardService->getLoginsBySource(),
            
            // Trial lesson stats
            'dashboard.trial_stats' => fn() => $dashboardService->getTrialLessonStats(),
            'dashboard.trial_by_status' => fn() => $dashboardService->getTrialLessonsByStatus(),
            'dashboard.trial_trend_chart' => fn() => $dashboardService->getTrialLessonTrendChart(14),
            'dashboard.trial_conversion' => fn() => $dashboardService->getTrialConversionStats(),
            'dashboard.recent_trial_lessons' => fn() => $dashboardService->getRecentTrialLessons(10),
            'dashboard.top_teachers_by_trial' => fn() => $dashboardService->getTopTeachersByTrialLessons(10),
            
            // Program/Curriculum stats
            'dashboard.program_stats_summary' => fn() => $dashboardService->getProgramStatsSummary(),
            'dashboard.program_category_stats' => fn() => $dashboardService->getProgramCategoryStats(),
            
            // Session success/failure stats (prioritized)
            'dashboard.session_stats' => fn() => $dashboardService->getMultiPeriodSessionStats(),
            
            // Acceptance codes
            'dashboard.acceptance_codes_list' => fn() => $dashboardService->getAcceptanceCodesList(),
            'dashboard.acceptance_code_stats' => fn() => $dashboardService->getMultiPeriodAcceptanceCodeStats(),
            'dashboard.acceptance_code_map_stats' => fn() => $dashboardService->getMultiPeriodAcceptanceCodeMapStats(),
            
            // Learning path and feedback
            'dashboard.learning_path_stats' => fn() => $dashboardService->getLearningPathStats(),
            'dashboard.curriculum_session_distribution' => fn() => $dashboardService->getCurriculumSessionDistribution(),
            'dashboard.program_enrollment_stats' => fn() => $dashboardService->getProgramEnrollmentStats(),
            'dashboard.curriculum_session_chart' => fn() => $dashboardService->getCurriculumSessionChartData(30),
            'dashboard.feedback_stats' => fn() => $dashboardService->getTeacherFeedbackStats(),
            'dashboard.feedback_status' => fn() => $dashboardService->getTeachersFeedbackStatus(),
            'dashboard.feedback_trend' => fn() => $dashboardService->getFeedbackSubmissionTrend(14),
            'dashboard.recent_feedback' => fn() => $dashboardService->getRecentFeedbackWithContent(10),
            'dashboard.top_teachers_by_feedback' => fn() => $dashboardService->getTopTeachersByFeedback(10),
            'dashboard.session_outcome' => fn() => $dashboardService->getSessionOutcomeStats(),
            'dashboard.attendance_issues' => fn() => $dashboardService->getAttendanceIssues(10),
            
            // Teacher management and quiz
            'dashboard.leave_stats' => fn() => $dashboardService->getLeaveRequestStats(),
            'dashboard.leave_by_status' => fn() => $dashboardService->getLeaveRequestsByStatus(),
            'dashboard.recent_leave_requests' => fn() => $dashboardService->getRecentLeaveRequests(10),
            'dashboard.violation_stats' => fn() => $dashboardService->getLeaveViolationStats(),
            'dashboard.teachers_with_most_leave' => fn() => $dashboardService->getTeachersWithMostLeave(10),
            'dashboard.leave_trend' => fn() => $dashboardService->getLeaveRequestTrend(14),
            'dashboard.leave_quota_summary' => fn() => $dashboardService->getLeaveQuotaSummary(),
            'dashboard.quiz_stats' => fn() => $dashboardService->getQuizStats(),
            'dashboard.quiz_attempt_stats' => fn() => $dashboardService->getQuizAttemptStats(),
            'dashboard.recent_quiz_attempts' => fn() => $dashboardService->getRecentQuizAttempts(10),
            'dashboard.quiz_pass_fail_chart' => fn() => $dashboardService->getQuizPassFailChartData(14),
            
            // Quality dashboard
            'dashboard.lesson_quality' => fn() => $dashboardService->getLessonQualityStats(),
            'dashboard.session_quality' => fn() => $dashboardService->getSessionQualitySummary(),
            'dashboard.cancellation_breakdown' => fn() => $dashboardService->getCancellationBreakdown(30),
            
            // Teacher payment
            'dashboard.teacher_payment_stats' => fn() => $dashboardService->getTeacherPaymentStats(),
            
            // Comparison stats
            'dashboard.comparison_stats' => fn() => $dashboardService->getComparisonStats(),
        ];
    }

    /**
     * Execute the console command.
     * Phase 140: Pre-caches all data for program tabs
     * Phase 142: Only caches 'all' program (removed speakwell/easyspeak for performance)
     * Phase 158: Pre-check database connection to fail fast with clear error
     */
    public function handle(DashboardService $dashboardService): int
    {
        $startTime = microtime(true);
        
        // Phase 158: Pre-check database connection before attempting to cache
        // This prevents 60+ identical "Access denied" errors and gives a single clear message
        $this->newLine();
        $this->info("📊 Dashboard Cache Refresh");
        $this->line("   Checking database connection...");
        
        try {
            \Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
            $this->line("   ✅ MySQL connection OK");
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("❌ Database connection failed! Cannot refresh cache.");
            $this->error("   Error: " . $e->getMessage());
            $this->newLine();
            $this->warn("💡 Possible causes:");
            $this->line("   - ZEUS_DB_PASSWORD environment variable not set before running DEPLOY-SERVER.sh");
            $this->line("   - MySQL user does not have permission to connect from this server's IP");
            $this->line("   - Database host/port is incorrect in .env");
            $this->newLine();
            $this->line("   Skipping cache refresh. Dashboard will query the database directly on each request.");
            $this->newLine();
            Log::error("Dashboard cache refresh aborted: database connection failed - " . $e->getMessage());
            return self::FAILURE;
        }
        
        $cachedItems = 0;
        $errors = [];

        // Phase 142: Only cache 'all' program (removed speakwell/easyspeak tabs)
        $programs = DashboardService::VALID_PROGRAMS; // ['all']
        $programLabels = ['all' => 'ALL'];
        $cacheOperations = $this->getCacheOperations($dashboardService);
        $totalItemsPerProgram = count($cacheOperations);
        $totalItems = $totalItemsPerProgram * count($programs);
        $currentItem = 0;
        
        // Display header with total count
        $this->line("   Programs: " . implode(', ', array_map(fn($p) => $programLabels[$p] ?? $p, $programs)));
        $this->line("   Items: {$totalItems} total");
        $this->newLine();

        foreach ($programs as $program) {
            $programLabel = $programLabels[$program] ?? $program;
            $dashboardService->setProgram($program);
            
            $this->info("   🔄 Caching program: {$programLabel}");

            foreach ($cacheOperations as $key => $callback) {
                $currentItem++;
                $label = self::CACHE_LABELS[$key] ?? $key;
                $percentage = round(($currentItem / $totalItems) * 100);
                
                // Create visual progress bar
                $barWidth = 30;
                $filled = (int) round(($currentItem / $totalItems) * $barWidth);
                $empty = $barWidth - $filled;
                $progressBar = str_repeat('█', $filled) . str_repeat('░', $empty);
                
                // Display current item being processed
                $status = sprintf(
                    "   [%s] %3d%% (%2d/%d) ⏳ [%s] %s...",
                    $progressBar,
                    $percentage,
                    $currentItem,
                    $totalItems,
                    $programLabel,
                    str_pad($label, 20)
                );
                
                if (stream_isatty(STDOUT)) {
                    $this->output->write("\r{$status}");
                } else {
                    $this->line($status);
                }
                
                $itemStart = microtime(true);
                
                try {
                    // Call the callback which internally uses getCached() with program suffix
                    $data = $callback();
                    // Also store at the base key (for backward compatibility)
                    $programKey = $key . '.prog_' . $program;
                    Cache::put($programKey, $data, self::CACHE_TTL);
                    $cachedItems++;
                    $itemDuration = round(microtime(true) - $itemStart, 1);
                    
                    if (stream_isatty(STDOUT)) {
                        $this->output->write("\r" . sprintf(
                            "   [%s] %3d%% (%2d/%d) ✅ [%s] %s (%.1fs)",
                            $progressBar,
                            $percentage,
                            $currentItem,
                            $totalItems,
                            $programLabel,
                            str_pad($label, 20),
                            $itemDuration
                        ) . str_repeat(' ', 10) . "\n");
                    }
                } catch (\Throwable $e) {
                    $errorKey = "{$key}[{$program}]";
                    $errors[$errorKey] = $e->getMessage();
                    Log::error("Dashboard cache refresh failed for {$errorKey}: " . $e->getMessage());
                    
                    if (stream_isatty(STDOUT)) {
                        $this->output->write("\r" . sprintf(
                            "   [%s] %3d%% (%2d/%d) ❌ [%s] %s (error)",
                            $progressBar,
                            $percentage,
                            $currentItem,
                            $totalItems,
                            $programLabel,
                            str_pad($label, 20)
                        ) . str_repeat(' ', 10) . "\n");
                    }
                }
            }
            
            $this->newLine();
        }
        
        // Clear line and add newline for final summary
        if (stream_isatty(STDOUT)) {
            $this->output->write("\r" . str_repeat(' ', 80) . "\r");
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        
        // Store the cache refresh timestamp (Vietnam time)
        $refreshedAt = now()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s');
        Cache::put('dashboard.cache_refreshed_at', $refreshedAt, self::CACHE_TTL);
        
        if (empty($errors)) {
            $this->info("✓ Successfully cached {$cachedItems}/{$totalItems} items in {$duration}s");
            $this->info("  Cache refreshed at: {$refreshedAt}");
            Log::info("Dashboard cache refreshed: {$cachedItems} items in {$duration}s at {$refreshedAt}");
        } else {
            $this->warn("Cached {$cachedItems}/{$totalItems} items in {$duration}s with " . count($errors) . " errors");
            foreach ($errors as $key => $error) {
                $this->error("  - {$key}: {$error}");
            }
            Log::warning("Dashboard cache refresh completed with errors", $errors);
        }

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }
}
