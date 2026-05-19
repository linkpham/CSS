<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CareSoftService
{
    private function db()
    {
        return DB::connection('caresoft');
    }

    private function hasTable(string $table): bool
    {
        return Schema::connection('caresoft')->hasTable($table);
    }

    public function getAgentStatuses(): array
    {
        if (!$this->hasTable('cs_agents')) return [];

        $agents = $this->db()->table('cs_agents')->get();

        $total = $agents->count();
        $callAvailable = $agents->where('call_status', 'AVAILABLE')->count();
        $ticketAvailable = $agents->where('ticket_status', 'AVAILABLE')->count();
        $chatAvailable = $agents->where('chat_status', 'AVAILABLE')->count();
        $allLogout = $agents->filter(fn($a) =>
            $a->call_status === 'LOGOUT' && $a->ticket_status === 'LOGOUT' && $a->chat_status === 'LOGOUT'
        )->count();

        $byGroup = $agents->groupBy('group_name')->map(function ($group, $name) {
            return [
                'group_name' => $name,
                'total' => $group->count(),
                'call_online' => $group->where('call_status', 'AVAILABLE')->count(),
                'ticket_online' => $group->where('ticket_status', 'AVAILABLE')->count(),
                'chat_online' => $group->where('chat_status', 'AVAILABLE')->count(),
            ];
        })->values()->toArray();

        return [
            'total' => $total,
            'call_available' => $callAvailable,
            'ticket_available' => $ticketAvailable,
            'chat_available' => $chatAvailable,
            'all_logout' => $allLogout,
            'by_group' => $byGroup,
            'agents' => $agents->map(fn($a) => (array) $a)->toArray(),
        ];
    }

    public function getTicketStats(?string $from = null, ?string $to = null): array
    {
        if (!$this->hasTable('cs_tickets')) return $this->emptyTicketStats();

        $query = $this->db()->table('cs_tickets');
        if ($from) $query->where('created_at', '>=', $from);
        if ($to) $query->where('created_at', '<=', $to);

        $tickets = $query->get();
        $total = $tickets->count();

        $byStatus = $tickets->groupBy('ticket_status')->map->count()->toArray();
        $bySource = $tickets->groupBy('ticket_source')->map->count()->toArray();
        $byPriority = $tickets->groupBy('ticket_priority')->map->count()->toArray();

        $satisfied = $tickets->whereNotNull('satisfaction');
        $avgSatisfaction = $satisfied->count() > 0 ? round($satisfied->avg('satisfaction'), 2) : null;

        $byDay = $tickets->groupBy(fn($t) => substr($t->created_at ?? '', 0, 10))
            ->map->count()->sortKeys()->toArray();

        $byGroup = [];
        if ($this->hasTable('cs_agents')) {
            $agentGroups = $this->db()->table('cs_agents')->pluck('group_name', 'id')->toArray();
            $byAssigneeGroup = $tickets->groupBy(function ($t) use ($agentGroups) {
                return $agentGroups[$t->assignee_id] ?? 'Không xác định';
            })->map->count()->sortByDesc(fn($v) => $v)->toArray();
            $byGroup = $byAssigneeGroup;
        }

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'by_priority' => $byPriority,
            'by_day' => $byDay,
            'by_group' => $byGroup,
            'avg_satisfaction' => $avgSatisfaction,
            'open' => $byStatus['open'] ?? 0,
            'pending' => $byStatus['pending'] ?? 0,
            'solved' => $byStatus['solved'] ?? 0,
            'closed' => $byStatus['closed'] ?? 0,
            'new' => $byStatus['new'] ?? 0,
        ];
    }

    public function getCallStats(?string $from = null, ?string $to = null): array
    {
        if (!$this->hasTable('cs_calls')) return $this->emptyCallStats();

        $query = $this->db()->table('cs_calls');
        if ($from) $query->where('start_time', '>=', $from);
        if ($to) $query->where('start_time', '<=', $to);

        $calls = $query->get();
        $total = $calls->count();

        $inbound = $calls->where('call_type', 0)->count();
        $outbound = $calls->where('call_type', 1)->count();
        $met = $calls->where('call_status', 'meetAgent')->count();
        $missed = $calls->where('call_status', 'miss')->count();
        $meetRate = $total > 0 ? round($met / $total * 100, 1) : 0;

        $missedReasons = $calls->where('call_status', 'miss')
            ->groupBy('missed_reason')->map->count()->toArray();

        $byDay = $calls->groupBy(fn($c) => substr($c->start_time ?? '', 0, 10))
            ->map(function ($dayItems) {
                return [
                    'total' => $dayItems->count(),
                    'met' => $dayItems->where('call_status', 'meetAgent')->count(),
                    'missed' => $dayItems->where('call_status', 'miss')->count(),
                ];
            })->sortKeys()->toArray();

        $byHour = $calls->groupBy(fn($c) => substr($c->start_time ?? '', 11, 2))
            ->map->count()->sortKeys()->toArray();

        return [
            'total' => $total,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'met' => $met,
            'missed' => $missed,
            'meet_rate' => $meetRate,
            'missed_reasons' => $missedReasons,
            'by_day' => $byDay,
            'by_hour' => $byHour,
        ];
    }

    public function getChatStats(?string $from = null, ?string $to = null): array
    {
        if (!$this->hasTable('cs_chats')) return $this->emptyChatStats();

        $query = $this->db()->table('cs_chats');
        if ($from) $query->where('start_time', '>=', $from);
        if ($to) $query->where('start_time', '<=', $to);

        $chats = $query->get();
        $total = $chats->count();

        $met = $chats->where('chat_status', 'LBL_CHAT_STATUS_MEET')->count();
        $missed = $chats->where('chat_status', 'LBL_CHAT_STATUS_MISS')->count();
        $meetRate = $total > 0 ? round($met / $total * 100, 1) : 0;

        $avgDuration = $chats->whereNotNull('chat_duration')->avg('chat_duration');
        $avgDuration = $avgDuration ? round($avgDuration) : 0;

        $byType = [
            'livechat' => $chats->where('conversation_type', 0)->count(),
            'facebook' => $chats->where('conversation_type', 1)->count(),
            'zalo' => $chats->where('conversation_type', 3)->count(),
        ];

        $byDay = $chats->groupBy(fn($c) => substr($c->start_time ?? '', 0, 10))
            ->map(function ($dayItems) {
                return [
                    'total' => $dayItems->count(),
                    'met' => $dayItems->where('chat_status', 'LBL_CHAT_STATUS_MEET')->count(),
                    'missed' => $dayItems->where('chat_status', 'LBL_CHAT_STATUS_MISS')->count(),
                ];
            })->sortKeys()->toArray();

        $byAgent = $chats->whereNotNull('agent_name')
            ->groupBy('agent_name')->map->count()
            ->sortByDesc(fn($v) => $v)->take(10)->toArray();

        return [
            'total' => $total,
            'met' => $met,
            'missed' => $missed,
            'meet_rate' => $meetRate,
            'avg_duration' => $avgDuration,
            'by_type' => $byType,
            'by_day' => $byDay,
            'by_agent' => $byAgent,
        ];
    }

    public function getSyncStatus(): array
    {
        if (!$this->hasTable('cs_sync_logs')) return [];

        return $this->db()->table('cs_sync_logs')
            ->select('sync_type', 'record_count', 'error', 'elapsed_seconds', 'synced_at')
            ->orderByDesc('synced_at')
            ->limit(20)
            ->get()
            ->groupBy('sync_type')
            ->map(fn($items) => $items->first())
            ->toArray();
    }

    public function getDashboardSummary(?string $from = null, ?string $to = null): array
    {
        $from = $from ?? Carbon::today()->toDateTimeString();
        $to = $to ?? Carbon::now()->toDateTimeString();

        return [
            'agents' => $this->getAgentStatuses(),
            'tickets' => $this->getTicketStats($from, $to),
            'calls' => $this->getCallStats($from, $to),
            'chats' => $this->getChatStats($from, $to),
            'sync_status' => $this->getSyncStatus(),
            'period' => ['from' => $from, 'to' => $to],
        ];
    }

    private function emptyTicketStats(): array
    {
        return ['total' => 0, 'by_status' => [], 'by_source' => [], 'by_priority' => [], 'by_day' => [], 'by_group' => [], 'avg_satisfaction' => null, 'open' => 0, 'pending' => 0, 'solved' => 0, 'closed' => 0, 'new' => 0];
    }

    private function emptyCallStats(): array
    {
        return ['total' => 0, 'inbound' => 0, 'outbound' => 0, 'met' => 0, 'missed' => 0, 'meet_rate' => 0, 'missed_reasons' => [], 'by_day' => [], 'by_hour' => []];
    }

    private function emptyChatStats(): array
    {
        return ['total' => 0, 'met' => 0, 'missed' => 0, 'meet_rate' => 0, 'avg_duration' => 0, 'by_type' => ['livechat' => 0, 'facebook' => 0, 'zalo' => 0], 'by_day' => [], 'by_agent' => []];
    }

    /**
     * Get chat message statistics with category breakdown
     */
    public function getChatMessageStats(?string $from = null, ?string $to = null): array
    {
        if (!$this->hasTable('cs_chat_messages')) return $this->emptyChatMessageStats();

        $query = $this->db()->table('cs_chat_messages');
        if ($from) $query->where('time', '>=', $from);
        if ($to) $query->where('time', '<=', $to);

        $messages = $query->get();
        $total = $messages->count();

        // By category
        $byCategory = $messages->groupBy('category')->map->count()->sortByDesc(fn($v) => $v)->toArray();

        // By conversation type
        $byConversationType = [
            'livechat' => $messages->where('conversation_type', 0)->count(),
            'facebook' => $messages->where('conversation_type', 1)->count(),
            'zalo' => $messages->where('conversation_type', 3)->count(),
        ];

        // Customer vs Agent messages
        $customerMessages = $messages->whereNotNull('sender_visitor_id')->where('sender_visitor_id', '>', 0)->count();
        $agentMessages = $messages->whereNotNull('sender_agent_id')->where('sender_agent_id', '>', 0)->count();
        $systemMessages = $messages->where('type', 3)->count();

        // By day
        $byDay = $messages->groupBy(fn($m) => substr($m->time ?? '', 0, 10))
            ->map->count()->sortKeys()->toArray();

        // Top categories for customer messages
        $customerCategoryBreakdown = $messages
            ->whereIn('category', ['inquiry', 'complaint', 'order', 'feedback', 'support', 'greeting', 'customer_message'])
            ->groupBy('category')->map->count()->sortByDesc(fn($v) => $v)->toArray();

        // Top conversations by message count
        $topConversations = $messages->groupBy('conversation_id')
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'customer_name' => $items->first()->sender_visitor_name ?? 'N/A',
                    'type' => $items->first()->conversation_type,
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->toArray();

        return [
            'total' => $total,
            'by_category' => $byCategory,
            'by_conversation_type' => $byConversationType,
            'customer_messages' => $customerMessages,
            'agent_messages' => $agentMessages,
            'system_messages' => $systemMessages,
            'by_day' => $byDay,
            'customer_category_breakdown' => $customerCategoryBreakdown,
            'top_conversations' => $topConversations,
        ];
    }

    private function emptyChatMessageStats(): array
    {
        return [
            'total' => 0,
            'by_category' => [],
            'by_conversation_type' => ['livechat' => 0, 'facebook' => 0, 'zalo' => 0],
            'customer_messages' => 0,
            'agent_messages' => 0,
            'system_messages' => 0,
            'by_day' => [],
            'customer_category_breakdown' => [],
            'top_conversations' => [],
        ];
    }
}
