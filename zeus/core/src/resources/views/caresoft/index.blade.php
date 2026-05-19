@extends('layouts.app')

@section('title', 'CareSoft CSKH Dashboard')

@section('content')
<div x-data="caresoftDashboard()" x-init="init()" class="space-y-6">
    {{-- Syncing Banner --}}
    <template x-if="isSyncing">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Đang đồng bộ dữ liệu CareSoft...</p>
                <p class="text-xs text-blue-600/80 dark:text-blue-400/80 mt-1">Dữ liệu sẽ tự động cập nhật sau khi hoàn tất. Quá trình này có thể mất vài phút.</p>
            </div>
        </div>
    </template>

    {{-- Empty Data Warning Banner --}}
    <template x-if="showEmptyBanner && !isSyncing">
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Chưa có dữ liệu CareSoft</p>
                <p class="text-xs text-yellow-600/80 dark:text-yellow-400/80 mt-1">Nhấn nút bên dưới để bắt đầu đồng bộ dữ liệu từ CareSoft API:</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button @click="triggerSync()" :disabled="syncButtonLoading" class="px-4 py-2 text-sm bg-yellow-500 hover:bg-yellow-600 disabled:bg-yellow-500/50 text-white rounded-lg transition flex items-center gap-2">
                        <svg x-show="syncButtonLoading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="syncButtonLoading ? 'Đang xử lý...' : 'Đồng bộ ngay'"></span>
                    </button>
                    <button @click="testConnection()" :disabled="testLoading" class="px-4 py-2 text-sm bg-blue-500 hover:bg-blue-600 disabled:bg-blue-500/50 text-white rounded-lg transition flex items-center gap-2">
                        <svg x-show="testLoading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="testLoading ? 'Đang kiểm tra...' : 'Kiểm tra kết nối API'"></span>
                    </button>
                </div>
                <template x-if="connectionResult">
                    <div class="mt-2 p-2 rounded text-xs" :class="connectionResult.success ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'" x-text="connectionResult.message"></div>
                </template>
            </div>
            <button @click="showEmptyBanner = false" class="text-yellow-500 hover:text-yellow-400 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-light-text dark:text-zeus-text">CareSoft CSKH Dashboard</h1>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-1">
                Quản trị vận hành & chất lượng chăm sóc khách hàng
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="range" @change="loadData()" class="text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                <option value="today">Hôm nay</option>
                <option value="yesterday">Hôm qua</option>
                <option value="week">Tuần này</option>
                <option value="month">Tháng này</option>
            </select>
            <button @click="refreshAgents()" class="px-3 py-2 text-sm bg-zeus-accent hover:bg-zeus-accent-light text-white rounded-lg transition flex items-center gap-1">
                <svg class="w-4 h-4" :class="{ 'animate-spin': agentLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Realtime</span>
            </button>
        </div>
    </div>

    {{-- Agent Status Cards (Realtime-lite) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Tổng Agent</p>
            <p class="text-2xl font-bold text-light-text dark:text-zeus-text mt-1" x-text="agents.total || '—'"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Thoại Online</p>
            <p class="text-2xl font-bold text-emerald-500 mt-1" x-text="agents.call_available || '0'"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Ticket Online</p>
            <p class="text-2xl font-bold text-blue-500 mt-1" x-text="agents.ticket_available || '0'"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Chat Online</p>
            <p class="text-2xl font-bold text-purple-500 mt-1" x-text="agents.chat_available || '0'"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Đã Logout</p>
            <p class="text-2xl font-bold text-red-400 mt-1" x-text="agents.all_logout || '0'"></p>
        </div>
    </div>

    {{-- Main KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Tickets --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">Phiếu ghi (Tickets)</h3>
                <span class="text-2xl font-bold text-light-text dark:text-zeus-text" x-text="tickets.total || '0'"></span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Mới</span>
                    <span class="font-medium text-blue-400" x-text="tickets.new || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Đang mở</span>
                    <span class="font-medium text-yellow-400" x-text="tickets.open || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Chờ xử lý</span>
                    <span class="font-medium text-orange-400" x-text="tickets.pending || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Đã giải quyết</span>
                    <span class="font-medium text-emerald-400" x-text="tickets.solved || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Đã đóng</span>
                    <span class="font-medium text-gray-400" x-text="tickets.closed || 0"></span>
                </div>
                <template x-if="tickets.avg_satisfaction !== null && tickets.avg_satisfaction !== undefined">
                    <div class="flex justify-between text-sm pt-2 border-t border-light-border dark:border-zeus-border">
                        <span class="text-light-text-muted dark:text-zeus-text-muted">CSAT trung bình</span>
                        <span class="font-medium text-yellow-300" x-text="tickets.avg_satisfaction + '/5'"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Calls --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">Cuộc gọi</h3>
                <span class="text-2xl font-bold text-light-text dark:text-zeus-text" x-text="calls.total || '0'"></span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Gọi vào</span>
                    <span class="font-medium text-blue-400" x-text="calls.inbound || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Gọi ra</span>
                    <span class="font-medium text-cyan-400" x-text="calls.outbound || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Gặp agent</span>
                    <span class="font-medium text-emerald-400" x-text="calls.met || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Nhỡ</span>
                    <span class="font-medium text-red-400" x-text="calls.missed || 0"></span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t border-light-border dark:border-zeus-border">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ gặp</span>
                    <span class="font-bold" :class="calls.meet_rate >= 80 ? 'text-emerald-400' : calls.meet_rate >= 60 ? 'text-yellow-400' : 'text-red-400'" x-text="calls.meet_rate + '%'"></span>
                </div>
            </div>
        </div>

        {{-- Chats --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">Chat</h3>
                <span class="text-2xl font-bold text-light-text dark:text-zeus-text" x-text="chats.total || '0'"></span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Live Chat</span>
                    <span class="font-medium text-blue-400" x-text="chats.by_type?.livechat || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Facebook/IG</span>
                    <span class="font-medium text-indigo-400" x-text="chats.by_type?.facebook || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Zalo</span>
                    <span class="font-medium text-blue-300" x-text="chats.by_type?.zalo || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Gặp</span>
                    <span class="font-medium text-emerald-400" x-text="chats.met || 0"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Nhỡ</span>
                    <span class="font-medium text-red-400" x-text="chats.missed || 0"></span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t border-light-border dark:border-zeus-border">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ gặp</span>
                    <span class="font-bold" :class="chats.meet_rate >= 80 ? 'text-emerald-400' : chats.meet_rate >= 60 ? 'text-yellow-400' : 'text-red-400'" x-text="chats.meet_rate + '%'"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Thời lượng TB</span>
                    <span class="font-medium text-light-text dark:text-zeus-text" x-text="formatDuration(chats.avg_duration)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Section: Phiếu ghi & Chat --}}
    <div>
        <h2 class="text-xs font-semibold text-light-text-muted dark:text-zeus-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
            <span class="w-1 h-4 rounded bg-purple-500"></span>
            Tổng quan Phiếu ghi & Chat
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Ticket by Source --}}
            <div class="lg:col-span-5 bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Phiếu ghi theo nguồn</h3>
                <canvas id="ticketSourceChart" height="260"></canvas>
            </div>

            {{-- Chat Trend --}}
            <div class="lg:col-span-7 bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Xu hướng chat</h3>
                <canvas id="chatTrendChart" height="260"></canvas>
            </div>
        </div>
    </div>

    {{-- Section: Phân tích Cuộc gọi --}}
    <div>
        <h2 class="text-xs font-semibold text-light-text-muted dark:text-zeus-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
            <span class="w-1 h-4 rounded bg-blue-500"></span>
            Phân tích Cuộc gọi
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Call Trend --}}
            <div class="lg:col-span-7 bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Xu hướng cuộc gọi</h3>
                <canvas id="callTrendChart" height="260"></canvas>
            </div>

            {{-- Call by Hour --}}
            <div class="lg:col-span-5 bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Cuộc gọi theo giờ</h3>
                <canvas id="callHourChart" height="260"></canvas>
            </div>
        </div>
    </div>

    {{-- Agent Status Table --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
        <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Trạng thái Agent theo nhóm</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Nhóm</th>
                        <th class="text-center py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Tổng</th>
                        <th class="text-center py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Thoại</th>
                        <th class="text-center py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Ticket</th>
                        <th class="text-center py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Chat</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="g in agents.by_group || []" :key="g.group_name">
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50">
                            <td class="py-2 px-3 text-light-text dark:text-zeus-text" x-text="g.group_name || '—'"></td>
                            <td class="py-2 px-3 text-center text-light-text dark:text-zeus-text" x-text="g.total"></td>
                            <td class="py-2 px-3 text-center">
                                <span class="inline-block min-w-[32px] px-1.5 py-0.5 rounded text-xs font-medium"
                                      :class="g.call_online > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-500/20 text-gray-400'"
                                      x-text="g.call_online"></span>
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="inline-block min-w-[32px] px-1.5 py-0.5 rounded text-xs font-medium"
                                      :class="g.ticket_online > 0 ? 'bg-blue-500/20 text-blue-400' : 'bg-gray-500/20 text-gray-400'"
                                      x-text="g.ticket_online"></span>
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="inline-block min-w-[32px] px-1.5 py-0.5 rounded text-xs font-medium"
                                      :class="g.chat_online > 0 ? 'bg-purple-500/20 text-purple-400' : 'bg-gray-500/20 text-gray-400'"
                                      x-text="g.chat_online"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Missed Reasons + Top Chat Agents --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Lý do nhỡ cuộc gọi</h3>
            <div class="space-y-2">
                <template x-for="(count, reason) in calls.missed_reasons || {}" :key="reason">
                    <div class="flex justify-between text-sm">
                        <span class="text-light-text-muted dark:text-zeus-text-muted" x-text="formatMissedReason(reason)"></span>
                        <span class="font-medium text-red-400" x-text="count"></span>
                    </div>
                </template>
                <template x-if="Object.keys(calls.missed_reasons || {}).length === 0">
                    <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</p>
                </template>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Top 10 Agent Chat</h3>
            <div class="space-y-2">
                <template x-for="(count, name) in chats.by_agent || {}" :key="name">
                    <div class="flex justify-between text-sm">
                        <span class="text-light-text dark:text-zeus-text" x-text="name"></span>
                        <span class="font-medium text-purple-400" x-text="count"></span>
                    </div>
                </template>
                <template x-if="Object.keys(chats.by_agent || {}).length === 0">
                    <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</p>
                </template>
            </div>
        </div>
    </div>

    {{-- Chat Message Classification --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">Phân loại tin nhắn Chat</h3>
            <button @click="loadChatMessages()" :disabled="chatMsgLoading" class="px-3 py-1.5 text-xs bg-purple-500 hover:bg-purple-600 disabled:bg-purple-500/50 text-white rounded-lg transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': chatMsgLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="chatMsgLoading ? 'Đang tải...' : 'Tải dữ liệu'"></span>
            </button>
        </div>
        <template x-if="chatMessages.total > 0">
            <div class="space-y-4">
                {{-- Summary Cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="text-center p-3 rounded-lg bg-blue-500/10 border border-blue-500/30">
                        <p class="text-xs text-blue-400 uppercase">Tổng tin nhắn</p>
                        <p class="text-lg font-bold text-blue-400" x-text="chatMessages.total"></p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30">
                        <p class="text-xs text-emerald-400 uppercase">Từ khách hàng</p>
                        <p class="text-lg font-bold text-emerald-400" x-text="chatMessages.customer_messages || 0"></p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-purple-500/10 border border-purple-500/30">
                        <p class="text-xs text-purple-400 uppercase">Từ Agent</p>
                        <p class="text-lg font-bold text-purple-400" x-text="chatMessages.agent_messages || 0"></p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-500/10 border border-gray-500/30">
                        <p class="text-xs text-gray-400 uppercase">Hệ thống</p>
                        <p class="text-lg font-bold text-gray-400" x-text="chatMessages.system_messages || 0"></p>
                    </div>
                </div>

                {{-- Customer Category Breakdown --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <h4 class="text-sm font-medium text-light-text dark:text-zeus-text">Phân loại tin nhắn khách hàng</h4>
                        <template x-for="(count, category) in chatMessages.customer_category_breakdown || {}" :key="category">
                            <div class="flex justify-between text-sm items-center">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="getCategoryColor(category)"></span>
                                    <span class="text-light-text-muted dark:text-zeus-text-muted" x-text="formatCategory(category)"></span>
                                </span>
                                <span class="font-medium text-light-text dark:text-zeus-text" x-text="count"></span>
                            </div>
                        </template>
                        <template x-if="Object.keys(chatMessages.customer_category_breakdown || {}).length === 0">
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</p>
                        </template>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-sm font-medium text-light-text dark:text-zeus-text">Phân loại theo kênh</h4>
                        <div class="flex justify-between text-sm">
                            <span class="text-light-text-muted dark:text-zeus-text-muted">Live Chat</span>
                            <span class="font-medium text-blue-400" x-text="chatMessages.by_conversation_type?.livechat || 0"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-light-text-muted dark:text-zeus-text-muted">Facebook/Instagram</span>
                            <span class="font-medium text-indigo-400" x-text="chatMessages.by_conversation_type?.facebook || 0"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-light-text-muted dark:text-zeus-text-muted">Zalo</span>
                            <span class="font-medium text-blue-300" x-text="chatMessages.by_conversation_type?.zalo || 0"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="chatMessages.total === 0 && !chatMsgLoading">
            <div class="text-center py-4">
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Nhấn "Tải dữ liệu" để xem phân loại tin nhắn chat.</p>
                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Dữ liệu tin nhắn được đồng bộ từ CareSoft API và tự động phân loại.</p>
            </div>
        </template>
    </div>

    {{-- Sync Status --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">Trạng thái đồng bộ dữ liệu</h3>
            <button @click="triggerSync()" :disabled="syncButtonLoading || isSyncing" class="px-3 py-1.5 text-xs bg-zeus-accent hover:bg-zeus-accent-light disabled:bg-zeus-accent/50 text-white rounded-lg transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': syncButtonLoading || isSyncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="isSyncing ? 'Đang đồng bộ...' : 'Đồng bộ ngay'"></span>
            </button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <template x-for="(info, type) in syncStatus" :key="type">
                <div class="text-center p-3 rounded-lg" :class="info.error ? 'bg-red-500/10 border border-red-500/30' : 'bg-emerald-500/10 border border-emerald-500/30'">
                    <p class="text-xs font-medium uppercase text-light-text-muted dark:text-zeus-text-muted" x-text="type"></p>
                    <p class="text-lg font-bold mt-1" :class="info.error ? 'text-red-400' : 'text-emerald-400'" x-text="info.record_count"></p>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1" x-text="formatSyncTime(info.synced_at)"></p>
                </div>
            </template>
        </div>
        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-3">
            <span x-show="isSyncing" class="text-blue-400">⏳ Đang đồng bộ dữ liệu, vui lòng đợi...</span>
            <span x-show="!isSyncing">Đồng bộ tự động mỗi 30 phút. Đồng bộ đầy đủ vào Chủ nhật lúc 2:00 AM.</span>
        </p>
    </div>
</div>

<script>
function caresoftDashboard() {
    return {
        range: '{{ $range }}',
        agents: @json($summary['agents'] ?? []),
        tickets: @json($summary['tickets'] ?? []),
        calls: @json($summary['calls'] ?? []),
        chats: @json($summary['chats'] ?? []),
        syncStatus: @json($summary['sync_status'] ?? []),
        chatMessages: { total: 0 },
        chatMsgLoading: false,
        agentLoading: false,
        charts: {},
        showEmptyBanner: false,
        isSyncing: {{ ($syncStatus['sync_running'] ?? false) ? 'true' : 'false' }},
        syncButtonLoading: false,
        testLoading: false,
        connectionResult: null,
        syncCheckInterval: null,

        init() {
            // Check if data is empty and show banner
            this.checkEmptyData();
            this.$nextTick(() => {
                this.renderCharts();
            });
            
            // If sync is running, poll for completion
            if (this.isSyncing) {
                this.startSyncPolling();
            }
        },

        checkEmptyData() {
            const hasAgents = this.agents && (this.agents.total > 0 || (this.agents.agents && this.agents.agents.length > 0));
            const hasTickets = this.tickets && this.tickets.total > 0;
            const hasCalls = this.calls && this.calls.total > 0;
            const hasChats = this.chats && this.chats.total > 0;
            const hasSyncData = this.syncStatus && Object.keys(this.syncStatus).length > 0;

            // Show banner if all data sources are empty
            if (!hasAgents && !hasTickets && !hasCalls && !hasChats && !hasSyncData) {
                this.showEmptyBanner = true;
            }
        },

        async triggerSync() {
            this.syncButtonLoading = true;
            this.connectionResult = null;
            try {
                const res = await fetch('/api/caresoft/trigger-sync', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                const data = await res.json();
                if (data.success) {
                    this.isSyncing = true;
                    this.showEmptyBanner = false;
                    this.startSyncPolling();
                } else {
                    this.connectionResult = { success: false, message: data.message || 'Không thể bắt đầu đồng bộ' };
                }
            } catch (e) {
                this.connectionResult = { success: false, message: 'Lỗi kết nối: ' + e.message };
            }
            this.syncButtonLoading = false;
        },

        async testConnection() {
            this.testLoading = true;
            this.connectionResult = null;
            try {
                const res = await fetch('/api/caresoft/test-connection');
                const data = await res.json();
                this.connectionResult = data;
            } catch (e) {
                this.connectionResult = { success: false, message: 'Lỗi kết nối: ' + e.message };
            }
            this.testLoading = false;
        },

        startSyncPolling() {
            // Poll every 10 seconds
            this.syncCheckInterval = setInterval(async () => {
                try {
                    const res = await fetch('/api/caresoft/sync-status');
                    const data = await res.json();
                    if (!data.is_syncing) {
                        // Sync completed - reload data
                        this.isSyncing = false;
                        clearInterval(this.syncCheckInterval);
                        this.syncCheckInterval = null;
                        await this.loadData();
                        await this.refreshAgents();
                        this.checkEmptyData();
                    }
                } catch (e) {
                    console.error('Sync status check error:', e);
                }
            }, 10000);
        },

        async loadData() {
            FilterProgress.show();
            try {
                const res = await fetch(`/api/caresoft/summary?range=${this.range}`);
                const data = await res.json();
                this.tickets = data.tickets || {};
                this.calls = data.calls || {};
                this.chats = data.chats || {};
                this.syncStatus = data.sync_status || {};
                this.renderCharts();
            } catch (e) { console.error('Load data error:', e); }
            FilterProgress.hide();
        },

        async refreshAgents() {
            this.agentLoading = true;
            try {
                const res = await fetch('/api/caresoft/agent-status');
                const data = await res.json();
                if (data.agents && Array.isArray(data.agents)) {
                    this.agents = {
                        ...data,
                        by_group: data.by_group || this.groupAgents(data.agents),
                    };
                    // Show source indicator
                    if (data.source === 'cache') {
                        console.info('Đang sử dụng dữ liệu từ cache SQLite');
                    } else if (data.source === 'empty') {
                        console.info(data.message || 'Chưa có dữ liệu agent');
                    }
                } else {
                    // Handle empty or invalid response
                    this.agents = {
                        total: 0,
                        call_available: 0,
                        ticket_available: 0,
                        chat_available: 0,
                        all_logout: 0,
                        by_group: [],
                        agents: [],
                    };
                }
            } catch (e) {
                console.error('Agent refresh error:', e);
                // Keep current data on error
            }
            this.agentLoading = false;
        },

        groupAgents(agents) {
            const groups = {};
            agents.forEach(a => {
                const name = a.group_name || 'Không xác định';
                if (!groups[name]) groups[name] = { group_name: name, total: 0, call_online: 0, ticket_online: 0, chat_online: 0 };
                groups[name].total++;
                if (a.call_status === 'AVAILABLE') groups[name].call_online++;
                if ((a.ticket_status || a.login_status) === 'AVAILABLE') groups[name].ticket_online++;
                if (a.chat_status === 'AVAILABLE') groups[name].chat_online++;
            });
            return Object.values(groups);
        },

        formatDuration(seconds) {
            if (!seconds) return '0s';
            if (seconds < 60) return seconds + 's';
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return m + 'p' + (s > 0 ? s + 's' : '');
        },

        formatMissedReason(reason) {
            const map = {
                'missed_customer': 'Khách dập máy',
                'missed_agent_device': 'Lỗi thiết bị agent',
                'missed_agent_reject': 'Agent từ chối',
                'missed_agent_timeout': 'Không tìm thấy agent',
            };
            return map[reason] || reason || 'Khác';
        },

        async loadChatMessages() {
            this.chatMsgLoading = true;
            try {
                const res = await fetch(`/api/caresoft/chat-messages?range=${this.range}`);
                const data = await res.json();
                this.chatMessages = data || { total: 0 };
            } catch (e) {
                console.error('Load chat messages error:', e);
            }
            this.chatMsgLoading = false;
        },

        formatCategory(category) {
            const map = {
                'inquiry': '❓ Hỏi/Thắc mắc',
                'complaint': '⚠️ Khiếu nại/Phản ánh',
                'order': '🛒 Đặt hàng/Mua',
                'feedback': '💬 Góp ý/Đánh giá',
                'support': '🆘 Yêu cầu hỗ trợ',
                'greeting': '👋 Chào hỏi',
                'customer_message': '💬 Tin nhắn khác',
                'agent_greeting': '👋 Agent chào',
                'agent_solution': '✅ Agent hướng dẫn',
                'agent_response': '💬 Agent trả lời',
                'system': '🔧 Hệ thống',
                'attachment': '📎 File đính kèm',
                'template': '📝 Template',
                'empty': '⬜ Trống',
                'other': '📝 Khác',
            };
            return map[category] || category || 'Khác';
        },

        getCategoryColor(category) {
            const colors = {
                'inquiry': 'bg-blue-500',
                'complaint': 'bg-red-500',
                'order': 'bg-emerald-500',
                'feedback': 'bg-yellow-500',
                'support': 'bg-orange-500',
                'greeting': 'bg-purple-500',
                'customer_message': 'bg-gray-500',
            };
            return colors[category] || 'bg-gray-400';
        },

        formatSyncTime(t) {
            if (!t) return '—';
            const d = new Date(t);
            const now = new Date();
            const diff = Math.floor((now - d) / 60000);
            if (diff < 1) return 'Vừa xong';
            if (diff < 60) return diff + ' phút trước';
            if (diff < 1440) return Math.floor(diff / 60) + ' giờ trước';
            return Math.floor(diff / 1440) + ' ngày trước';
        },

        renderCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9CA3AF' : '#64748B';
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

            // Ticket by Source
            this.renderDoughnut('ticketSourceChart', this.tickets.by_source || {}, textColor);

            // Call Trend
            this.renderLineTrend('callTrendChart', this.calls.by_day || {}, textColor, gridColor, 'calls');

            // Call by Hour
            this.renderBarChart('callHourChart', this.calls.by_hour || {}, textColor, gridColor);

            // Chat Trend
            this.renderLineTrend('chatTrendChart', this.chats.by_day || {}, textColor, gridColor, 'chats');
        },

        destroyChart(id) {
            if (this.charts[id] && typeof this.charts[id].destroy === 'function') {
                try {
                    this.charts[id].destroy();
                } catch (e) { /* ignore */ }
                this.charts[id] = null;
            }
        },

        renderDoughnut(id, data, textColor) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
            // Always destroy existing chart first
            this.destroyChart(id);

            const labels = Object.keys(data || {});
            const values = Object.values(data || {});
            
            // Clear canvas and show empty state if no data
            if (labels.length === 0) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = textColor;
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('Chưa có dữ liệu', canvas.width / 2, canvas.height / 2);
                return;
            }
            
            const colors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316','#14B8A6','#6366F1','#84CC16','#D946EF'];

            this.charts[id] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: colors.slice(0, labels.length), borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: textColor, font: { size: 11 }, padding: 8, usePointStyle: true } }
                    }
                }
            });
        },

        renderLineTrend(id, data, textColor, gridColor, type) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
            // Always destroy existing chart first
            this.destroyChart(id);

            const labels = Object.keys(data || {});
            
            // Clear canvas and show empty state if no data
            if (labels.length === 0) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = textColor;
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('Chưa có dữ liệu', canvas.width / 2, canvas.height / 2);
                return;
            }
            
            let datasets;

            if (type === 'calls' || type === 'chats') {
                const metData = labels.map(l => data[l]?.met || 0);
                const missedData = labels.map(l => data[l]?.missed || 0);
                datasets = [
                    { label: 'Gặp', data: metData, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
                    { label: 'Nhỡ', data: missedData, borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3 },
                ];
            } else {
                datasets = [{ label: 'Số lượng', data: Object.values(data || {}), borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3 }];
            }

            this.charts[id] = new Chart(ctx, {
                type: 'line',
                data: { labels: labels.map(l => l.substring(5)), datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } },
                        y: { beginAtZero: true, ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } }
                    },
                    plugins: { legend: { labels: { color: textColor, font: { size: 11 } } } }
                }
            });
        },

        renderBarChart(id, data, textColor, gridColor) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
            // Always destroy existing chart first
            this.destroyChart(id);

            const allHours = [];
            for (let i = 0; i < 24; i++) allHours.push(String(i).padStart(2, '0'));

            this.charts[id] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: allHours.map(h => h + 'h'),
                    datasets: [{ label: 'Cuộc gọi', data: allHours.map(h => (data || {})[h] || 0), backgroundColor: 'rgba(59,130,246,0.6)', borderRadius: 3 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: textColor, font: { size: 10 } }, grid: { color: gridColor } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    };
}
</script>
@endsection
