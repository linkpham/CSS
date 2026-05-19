# Phase 47 - Capture Root Cause of Queue Job Failures

## Problem

The queue worker was showing error:
```
❌ Lỗi: App\Jobs\ProcessUsageReportExport has been attempted too many times.
```

This error message is **not helpful** because it's just the wrapper exception (`MaxAttemptsExceededException`). The **actual root cause** of the failure was being lost, making it impossible to debug what went wrong.

## Root Cause Analysis

Laravel's queue worker throws `MaxAttemptsExceededException` when a job exceeds its retry limit, but this exception **does not expose the original exception** that caused the job to fail. The previous logging only captured this wrapper exception, not the actual error.

## Solution

### 1. Queue Event Listeners in AppServiceProvider

Added event listeners to capture the **root cause exception** at the moment it occurs, before it gets wrapped:

```php
// Phase 47: Log queue job exceptions with full stack trace
$this->registerQueueEventListeners();
```

Two event listeners:

1. **`Queue::exceptionOccurred()`** - Fires **when the exception occurs**, BEFORE the job is marked as failed. This captures the ROOT CAUSE.

2. **`Queue::failing()`** - Fires **after the job has permanently failed**. Unwraps `MaxAttemptsExceededException` to find the original exception.

Both listeners:
- Log to Laravel's log channel with CRITICAL level
- Log to stderr (via `error_log()`) so it appears in `docker logs`
- Include full stack trace for debugging

### 2. Enhanced Job `failed()` Method

Updated `ProcessUsageReportExport::failed()` to:

- Unwrap `MaxAttemptsExceededException` to find root cause
- Log with CRITICAL level instead of ERROR
- Include full stack trace in logs
- Log to stderr for docker logs visibility
- Store detailed error info in Redis cache

## Files Changed

1. `src/app/Providers/AppServiceProvider.php`
   - Added `Queue::exceptionOccurred()` listener
   - Added `Queue::failing()` listener
   - Both log root cause with full stack trace

2. `src/app/Jobs/ProcessUsageReportExport.php`
   - Enhanced `failed()` method to unwrap and log root cause
   - Added stderr logging for docker visibility
   - Stores detailed error info in Redis cache

## How to View Root Cause Errors

After deployment, when a job fails, the root cause will appear in:

### 1. Docker Logs (recommended)
```bash
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "sudo docker logs zeus-dashboard-queue --tail 100"
```

Look for lines starting with:
```
🔴 QUEUE JOB EXCEPTION - ROOT CAUSE
🔴 ProcessUsageReportExport FAILED - ROOT CAUSE
```

### 2. Laravel Log File
```bash
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "sudo docker exec zeus-dashboard-app tail -100 /var/www/storage/logs/laravel.log"
```

## Example Output

When a job fails, you will now see:

```
🔴 QUEUE JOB EXCEPTION - ROOT CAUSE
Job: App\Jobs\ProcessUsageReportExport
Error: PDOException: SQLSTATE[HY000] [2002] Connection timed out
File: /var/www/vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php:70
Trace:
#0 /var/www/vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php(70): PDO->__construct(...)
#1 /var/www/vendor/laravel/framework/src/Illuminate/Database/Connectors/MySqlConnector.php(26): ...
... (full stack trace)
```

This provides the **actual error** (e.g., database connection timeout) instead of just "attempted too many times".

## Deployment

```bash
./DEPLOY-SERVER.sh upgrade
```

After deployment, trigger an export job and if it fails, check the logs to see the root cause.

## Notes

- The `error_log()` function writes to PHP's error output, which Docker captures and shows in `docker logs`
- CRITICAL log level ensures the messages are not filtered out by Laravel's log level settings
- The root cause is now also stored in Redis cache under `error_details` for API access
