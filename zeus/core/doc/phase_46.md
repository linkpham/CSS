# Phase 46 - Fix ProcessUsageReportExport "Attempted Too Many Times" Error

## Problem

The queue worker was showing error:
```
❌ Lỗi: App\Jobs\ProcessUsageReportExport has been attempted too many times.
```

This error occurs when a job fails and gets stuck in the queue with an incremented attempt counter. When the queue worker tries to process it again, Laravel sees the attempt count exceeds the allowed `$tries` limit and throws `MaxAttemptsExceededException`.

## Root Cause Analysis

1. **Memory issues** - Large exports may exceed PHP memory limits
2. **Stuck jobs** - Failed jobs remain in Redis queue with high attempt counters
3. **Missing job properties** - Job class lacked explicit retry prevention settings

## Solution

### 1. Enhanced Job Class Properties

Updated `src/app/Jobs/ProcessUsageReportExport.php` with additional safeguards:

```php
/**
 * The maximum number of unhandled exceptions to allow before failing.
 * Phase 46: Set to 1 - prevents job from being retried after exception
 */
public int $maxExceptions = 1;

/**
 * Indicate if the job should be marked as failed on timeout.
 * Phase 46: Ensures job fails cleanly if it times out
 */
public bool $failOnTimeout = true;

/**
 * Delete the job if its models no longer exist.
 * Phase 46: Prevents serialization issues
 */
public bool $deleteWhenMissingModels = true;
```

### 2. Queue Cleanup in Deploy Script

Updated `DEPLOY-SERVER.sh` upgrade mode to clear stuck jobs:

```bash
# Phase 46: Clear stuck/failed queue jobs from Redis before restarting
echo "🗑️  Clearing stuck queue jobs..."
$DOCKER_CMD exec zeus-dashboard-app php artisan queue:clear redis --force
echo "✅ Queue jobs cleared"
```

### 3. Increased PHP Memory Limit (from previous changes)

The `docker/php/local.ini` was updated:
- `memory_limit=512M` (increased from 256M)

## Files Changed

1. `src/app/Jobs/ProcessUsageReportExport.php` - Added `$maxExceptions`, `$failOnTimeout`, `$deleteWhenMissingModels`
2. `DEPLOY-SERVER.sh` - Added `queue:clear redis --force` in upgrade mode

## Deployment

Run upgrade to apply changes and clear stuck jobs:

```bash
./DEPLOY-SERVER.sh upgrade
```

The upgrade script will:
1. Sync updated source files to server
2. Clear all Laravel caches
3. **Clear stuck queue jobs from Redis** (new in Phase 46)
4. Restart queue worker container
5. Refresh dashboard cache

## Verification

After deployment, the queue worker should process new export jobs without the "attempted too many times" error. Check logs with:

```bash
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "sudo docker logs zeus-dashboard-queue --tail 50"
```

## Notes

- The `queue:clear` command removes ALL pending jobs from the queue
- This is safe because export jobs are user-initiated and can be restarted
- Dashboard cache is NOT affected (uses different Redis keys)
