<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CareSoftInitialSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes max

    public function handle(): void
    {
        // Lock to prevent multiple concurrent syncs
        $lockKey = 'caresoft:initial-sync-running';
        
        if (Cache::has($lockKey)) {
            Log::info('CareSoft initial sync already running, skipping');
            return;
        }

        Cache::put($lockKey, true, now()->addMinutes(15));

        try {
            Log::info('Starting CareSoft initial sync via background job');

            // Run sync with 7 days lookback for initial data
            Artisan::call('caresoft:sync', [
                '--days' => 7,
            ]);

            Log::info('CareSoft initial sync completed', [
                'output' => Artisan::output()
            ]);

            // Mark sync as done
            Cache::put('caresoft:initial-sync-done', true, now()->addDays(30));

        } catch (\Exception $e) {
            Log::error('CareSoft initial sync failed', [
                'error' => $e->getMessage()
            ]);
        } finally {
            Cache::forget($lockKey);
        }
    }
}
