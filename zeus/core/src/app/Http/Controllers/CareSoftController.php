<?php

namespace App\Http\Controllers;

use App\Jobs\CareSoftInitialSync;
use App\Services\CareSoftService;
use App\Services\CareSoftApiClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CareSoftController extends Controller
{
    private CareSoftService $service;

    public function __construct(CareSoftService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        // Check if initial sync is needed
        $syncStatus = $this->checkAndTriggerInitialSync();

        $summary = $this->service->getDashboardSummary($from, $to);

        return view('caresoft.index', [
            'summary' => $summary,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'syncStatus' => $syncStatus,
        ]);
    }

    /**
     * Check if data exists and trigger initial sync if needed
     */
    private function checkAndTriggerInitialSync(): array
    {
        $status = [
            'needs_sync' => false,
            'sync_running' => false,
            'has_data' => false,
        ];

        // Check if sync is already running
        if (Cache::has('caresoft:initial-sync-running')) {
            $status['sync_running'] = true;
            return $status;
        }

        // Check if we have any data
        try {
            if (Schema::connection('caresoft')->hasTable('cs_agents')) {
                $agentCount = DB::connection('caresoft')->table('cs_agents')->count();
                if ($agentCount > 0) {
                    $status['has_data'] = true;
                    return $status;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        // No data - trigger initial sync via queue
        $status['needs_sync'] = true;
        
        // Dispatch sync job if not already done recently
        if (!Cache::has('caresoft:initial-sync-dispatched')) {
            CareSoftInitialSync::dispatch();
            Cache::put('caresoft:initial-sync-dispatched', true, now()->addMinutes(5));
            $status['sync_running'] = true;
        }

        return $status;
    }

    public function apiAgentStatus()
    {
        // First try live API
        $api = new CareSoftApiClient();
        $response = $api->getAgents();

        if ($response && isset($response['agents'])) {
            $agents = collect($response['agents']);
            return response()->json([
                'total' => $agents->count(),
                'call_available' => $agents->where('call_status', 'AVAILABLE')->count(),
                'ticket_available' => $agents->filter(fn($a) => ($a['ticket_status'] ?? $a['login_status'] ?? '') === 'AVAILABLE')->count(),
                'chat_available' => $agents->where('chat_status', 'AVAILABLE')->count(),
                'agents' => $agents->toArray(),
                'source' => 'live',
            ]);
        }

        // Fallback to cached SQLite data
        $cachedData = $this->service->getAgentStatuses();
        if (!empty($cachedData) && !empty($cachedData['agents'])) {
            return response()->json([
                ...$cachedData,
                'source' => 'cache',
            ]);
        }

        // Return empty response with flag indicating no data
        return response()->json([
            'total' => 0,
            'call_available' => 0,
            'ticket_available' => 0,
            'chat_available' => 0,
            'all_logout' => 0,
            'by_group' => [],
            'agents' => [],
            'source' => 'empty',
            'message' => 'Chưa có dữ liệu. Vui lòng chạy `php artisan caresoft:sync` để đồng bộ.',
        ]);
    }

    public function apiSummary(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json($this->service->getDashboardSummary($from, $to));
    }

    public function apiTickets(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json($this->service->getTicketStats($from, $to));
    }

    public function apiCalls(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json($this->service->getCallStats($from, $to));
    }

    public function apiChats(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json($this->service->getChatStats($from, $to));
    }

    /**
     * Get chat message statistics with category breakdown
     */
    public function apiChatMessages(Request $request)
    {
        $range = $request->get('range', 'today');
        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json($this->service->getChatMessageStats($from, $to));
    }

    public function apiSyncStatus()
    {
        $syncLogs = $this->service->getSyncStatus();
        $isRunning = Cache::has('caresoft:initial-sync-running');
        
        return response()->json([
            'logs' => $syncLogs,
            'is_syncing' => $isRunning,
            'last_sync' => $this->getLastSyncTime($syncLogs),
        ]);
    }

    /**
     * Trigger a manual sync via API
     */
    public function apiTriggerSync(Request $request)
    {
        // Check if sync is already running
        if (Cache::has('caresoft:initial-sync-running')) {
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ đang chạy, vui lòng đợi...',
            ], 409);
        }

        // Check API credentials
        if (empty(config('caresoft.api_token')) || empty(config('caresoft.domain'))) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình API CareSoft. Vui lòng kiểm tra CARESOFT_DOMAIN và CARESOFT_API_TOKEN trong .env',
            ], 400);
        }

        $days = $request->get('days', 7);

        // Dispatch sync job
        CareSoftInitialSync::dispatch();
        Cache::put('caresoft:initial-sync-dispatched', true, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'message' => 'Đã bắt đầu đồng bộ dữ liệu CareSoft (background job)',
        ]);
    }

    /**
     * Test API connection
     */
    public function apiTestConnection()
    {
        $api = new CareSoftApiClient();
        
        // Try to fetch agents as a connection test
        $response = $api->getAgents();

        if ($response && isset($response['code']) && $response['code'] === 'ok') {
            $agentCount = count($response['agents'] ?? []);
            return response()->json([
                'success' => true,
                'message' => "Kết nối thành công! Tìm thấy {$agentCount} chuyên viên.",
                'domain' => config('caresoft.domain'),
                'agent_count' => $agentCount,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể kết nối tới CareSoft API. Kiểm tra lại domain và token.',
            'domain' => config('caresoft.domain'),
            'response' => $response,
        ], 400);
    }

    private function getLastSyncTime(array $syncLogs): ?string
    {
        $latestTime = null;
        foreach ($syncLogs as $log) {
            if (isset($log->synced_at)) {
                if (!$latestTime || $log->synced_at > $latestTime) {
                    $latestTime = $log->synced_at;
                }
            }
        }
        return $latestTime;
    }

    private function parseDateRange(string $range, Request $request): array
    {
        return match ($range) {
            'today' => [Carbon::today()->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'yesterday' => [Carbon::yesterday()->startOfDay()->toDateTimeString(), Carbon::yesterday()->endOfDay()->toDateTimeString()],
            'week' => [Carbon::now()->startOfWeek()->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'month' => [Carbon::now()->startOfMonth()->toDateTimeString(), Carbon::now()->toDateTimeString()],
            'custom' => [
                $request->get('from', Carbon::today()->toDateTimeString()),
                $request->get('to', Carbon::now()->toDateTimeString()),
            ],
            default => [Carbon::today()->toDateTimeString(), Carbon::now()->toDateTimeString()],
        };
    }
}
