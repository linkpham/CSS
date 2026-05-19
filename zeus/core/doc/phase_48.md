# Phase 48 - Fix pm.max_children Warning and Enhance Job Diagnostics

## Problem Analysis

The user reported two issues:
1. Queue job error: `❌ Lỗi: App\Jobs\ProcessUsageReportExport has been attempted too many times.`
2. PHP-FPM warning: `[pool www] server reached pm.max_children setting (5), consider raising it`

## Investigation Findings

### Are these two issues related?

**No, they are separate issues:**

1. **pm.max_children warning** - This comes from PHP-FPM in the **web container** (`zeus-dashboard-app`). It means the web server is receiving more concurrent HTTP requests than PHP-FPM can handle (default is 5 child processes).

2. **Queue job failure** - The queue worker runs as a separate container (`zeus-dashboard-queue`) using `php artisan queue:work`, which does NOT use PHP-FPM. It's a CLI process.

### Why the queue job is failing

The root cause is NOT `pm.max_children`. Possible causes include:
- Memory exhaustion (512MB limit in queue worker)
- Database connection timeout
- Large dataset processing taking too long

However, the Phase 47 logging improvements should now capture the actual root cause in the docker logs.

## Solution

### 1. Fix pm.max_children Warning

Created a custom PHP-FPM pool configuration at `docker/php/www.conf`:

```ini
; Process manager settings
pm = dynamic
pm.max_children = 20      ; Increased from default 5
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
request_slowlog_timeout = 10s
request_terminate_timeout = 600s
```

Updated `docker/php/Dockerfile` to copy this configuration:

```dockerfile
# Phase 48: Copy PHP-FPM pool configuration with increased pm.max_children
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
```

### 2. Enhanced Job Diagnostics

Added system metrics logging to `ProcessUsageReportExport` job:

- **JOB_START**: Logs memory usage at job start
- **JOB_FAILED**: Logs memory usage when job fails

This will help identify if memory exhaustion is causing the failures.

Example output in docker logs:
```
📊 System Metrics [JOB_START]
  Memory: 45.25 MB / 512M (8.8%)
  Memory Peak: 48.00 MB
  Export ID: abc123
  Date Range: 2026-01-01 to 2026-01-31
```

## Files Changed

1. **docker/php/www.conf** (NEW)
   - PHP-FPM pool configuration with increased pm.max_children

2. **docker/php/Dockerfile**
   - Added COPY instruction for www.conf

3. **src/app/Jobs/ProcessUsageReportExport.php**
   - Added `logSystemMetrics()` method
   - Added `formatBytes()` helper
   - Added `calculateMemoryPercent()` helper
   - Log metrics at job start and on failure

## How to Debug Queue Failures

After deployment, run an export job and check the logs:

```bash
# View queue worker logs (contains system metrics + root cause)
ssh -i ~/Downloads/zeus/quenn quenn@13.215.57.82 "sudo docker logs zeus-dashboard-queue --tail 200"
```

Look for:
1. `📊 System Metrics [JOB_START]` - Shows memory at start
2. `🔴 QUEUE JOB EXCEPTION - ROOT CAUSE` - The actual error
3. `📊 System Metrics [JOB_FAILED]` - Shows memory at failure

If memory is near 100%, consider:
- Increasing `--memory=512` in queue worker command
- Optimizing the report generation to use less memory
- Processing data in smaller chunks

## Deployment

```bash
./DEPLOY-SERVER.sh upgrade
```

This will:
- Rebuild containers with new PHP-FPM config
- Apply increased pm.max_children (20 vs 5)
- Deploy enhanced job diagnostics

## Next Steps

1. Deploy the changes
2. Trigger an export job
3. Check docker logs for:
   - System metrics showing memory usage
   - The actual root cause exception
4. Based on findings, apply targeted fix (memory increase, query optimization, etc.)