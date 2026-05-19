<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom Blade directives for authorization
        Blade::if('hasrole', function ($role) {
            return auth('admin')->check() && auth('admin')->user()->hasRole($role);
        });

        Blade::if('hasanyrole', function ($roles) {
            return auth('admin')->check() && auth('admin')->user()->hasAnyRole($roles);
        });

        Blade::if('haspermission', function ($permission) {
            return auth('admin')->check() && auth('admin')->user()->can($permission);
        });

        // Phase 47: Log queue job exceptions with full stack trace
        // This captures the ROOT CAUSE before it gets wrapped in MaxAttemptsExceededException
        $this->registerQueueEventListeners();
    }

    /**
     * Register event listeners for queue jobs to capture root cause of failures.
     * Phase 47: This is critical for debugging - the "attempted too many times" error
     * is just the wrapper; we need to see the ACTUAL exception that caused the failure.
     */
    protected function registerQueueEventListeners(): void
    {
        // This event fires WHEN the exception occurs, BEFORE the job is marked as failed
        // This is where we capture the ROOT CAUSE exception
        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            $jobName = $event->job->resolveName();
            $exception = $event->exception;

            // Log with CRITICAL level so it's not filtered out
            Log::channel('stack')->critical("🔴 QUEUE JOB EXCEPTION - ROOT CAUSE", [
                'job_class' => $jobName,
                'job_id' => $event->job->getJobId(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'exception_trace' => $exception->getTraceAsString(),
            ]);

            // Also log to stderr so it appears in docker logs
            error_log("🔴 QUEUE JOB EXCEPTION - ROOT CAUSE");
            error_log("Job: {$jobName}");
            error_log("Error: " . get_class($exception) . ": " . $exception->getMessage());
            error_log("File: " . $exception->getFile() . ":" . $exception->getLine());
            error_log("Trace:\n" . $exception->getTraceAsString());
        });

        // This event fires AFTER the job has permanently failed (all retries exhausted)
        Queue::failing(function (JobFailed $event) {
            $jobName = $event->job->resolveName();
            $exception = $event->exception;

            // Check if this is a MaxAttemptsExceededException and try to get the previous exception
            $rootCause = $exception;
            while ($rootCause->getPrevious() !== null) {
                $rootCause = $rootCause->getPrevious();
            }

            Log::channel('stack')->critical("🔴 QUEUE JOB FAILED PERMANENTLY", [
                'job_class' => $jobName,
                'job_id' => $event->job->getJobId(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'final_exception_class' => get_class($exception),
                'final_exception_message' => $exception->getMessage(),
                'root_cause_class' => get_class($rootCause),
                'root_cause_message' => $rootCause->getMessage(),
                'root_cause_file' => $rootCause->getFile(),
                'root_cause_line' => $rootCause->getLine(),
                'root_cause_trace' => $rootCause->getTraceAsString(),
            ]);

            // Also log to stderr for docker logs visibility
            error_log("🔴 QUEUE JOB FAILED PERMANENTLY");
            error_log("Job: {$jobName}");
            error_log("Final Error: " . get_class($exception) . ": " . $exception->getMessage());
            if ($rootCause !== $exception) {
                error_log("Root Cause: " . get_class($rootCause) . ": " . $rootCause->getMessage());
                error_log("File: " . $rootCause->getFile() . ":" . $rootCause->getLine());
            }
            error_log("Trace:\n" . $rootCause->getTraceAsString());
        });
    }
}
