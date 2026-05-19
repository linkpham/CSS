@props(['stats', 'periodLabel' => ''])

@php
    $total = $stats['total'] ?? 0;
    $scheduled = $stats['status_breakdown']['scheduled'] ?? 0;
    $completed = $stats['status_breakdown']['completed'] ?? 0;
    $cancelled = $stats['status_breakdown']['cancelled'] ?? 0;
    $chargeable = $stats['completed_breakdown']['chargeable'] ?? 0;
    $compensate = $stats['completed_breakdown']['compensate'] ?? 0;
    $awaitingData = $stats['completed_breakdown']['awaiting_classin_data'] ?? 0;
    $awaitingWithin30min = $stats['completed_breakdown']['awaiting_within_30min'] ?? 0;
    $noDataOver30min = $stats['completed_breakdown']['no_data_over_30min'] ?? 0;
    
    // Calculate percentages for progress bars
    $completedPct = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    $scheduledPct = $total > 0 ? round(($scheduled / $total) * 100, 1) : 0;
    $cancelledPct = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
    $chargeablePct = $completed > 0 ? round(($chargeable / $completed) * 100, 1) : 0;
    $compensatePct = $completed > 0 ? round(($compensate / $completed) * 100, 1) : 0;
@endphp

<div class="mt-4 p-4 md:p-5 bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-800/50 dark:to-blue-900/30 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
    <h4 class="text-sm md:text-base font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
        📊 Phân cấp KPI {{ $periodLabel }}
        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Cấu trúc phân cấp</span><br>
            Hiển thị chi tiết breakdown ca học theo trạng thái và kết quả thanh toán.<br>
            • Tính phí: HV phải thanh toán<br>
            • Bù buổi: HV được học bù miễn phí
        </span></span>
    </h4>
    
    <div class="space-y-3">
        <!-- Level 0: Tổng ca học -->
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200 dark:border-slate-600">
            <span class="text-base md:text-lg font-bold text-blue-700 dark:text-blue-400">📋 Tổng ca học:</span>
            <span class="text-xl md:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($total) }}</span>
        </div>
        
        <!-- Level 1: Đã hoàn thành -->
        <div class="ml-4 md:ml-6 border-l-3 border-green-400 pl-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm md:text-base font-semibold text-green-700 dark:text-green-400">✅ Đã hoàn thành:</span>
                <span class="text-base md:text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($completed) }}</span>
                <span class="text-sm text-green-600/80 dark:text-green-400/80 bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded-full">({{ $completedPct }}%)</span>
            </div>
            <div class="mt-2 flex-1">
                <div class="bg-green-200 dark:bg-green-800 rounded-full h-2.5 max-w-[280px]">
                    <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $completedPct }}%"></div>
                </div>
            </div>
            
            <!-- Level 2: Breakdown của hoàn thành -->
            @if($completed > 0)
            <div class="ml-4 md:ml-6 mt-3 space-y-2.5">
                <!-- Số ca đã tính phí -->
                <div class="flex items-center gap-2 flex-wrap border-l-2 border-emerald-400 pl-3 py-1">
                    <span class="text-sm md:text-base text-emerald-700 dark:text-emerald-400 font-medium">💰 Số ca đã tính phí:</span>
                    <span class="text-base md:text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($chargeable) }}</span>
                    <span class="text-sm text-emerald-600/80 dark:text-emerald-400/80 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-full">({{ $chargeablePct }}%)</span>
                </div>
                
                <!-- Số ca bù buổi -->
                <div class="flex items-center gap-2 flex-wrap border-l-2 border-amber-400 pl-3 py-1">
                    <span class="text-sm md:text-base text-amber-700 dark:text-amber-400 font-medium">🔄 Số ca bù buổi:</span>
                    <span class="text-base md:text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($compensate) }}</span>
                    <span class="text-sm text-amber-600/80 dark:text-amber-400/80 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">({{ $compensatePct }}%)</span>
                </div>
                
                <!-- Ca chưa có dữ liệu trả về -->
                @if($awaitingData > 0)
                <div class="border-l-2 border-yellow-400 pl-3 py-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm md:text-base text-yellow-700 dark:text-yellow-400 font-medium">⏳</span>
                        <span class="text-base md:text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($awaitingData) }}</span>
                        <span class="text-sm text-yellow-600/80 dark:text-yellow-400/80 italic">ca chưa có dữ liệu trả về (thông thường Classin sẽ gửi data về sau mỗi 20ph)</span>
                    </div>
                    
                    <!-- Level 3: Breakdown của ca chưa có dữ liệu -->
                    <div class="mt-2 ml-4 space-y-1.5">
                        <!-- Ca đang chờ data (<=30 phút) -->
                        @if($awaitingWithin30min > 0)
                        <div class="flex items-center gap-2 flex-wrap border-l-2 border-orange-300 pl-3 py-0.5">
                            <span class="text-xs text-orange-700 dark:text-orange-400 font-medium">🕐</span>
                            <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ number_format($awaitingWithin30min) }}</span>
                            <span class="text-xs text-orange-600/80 dark:text-orange-400/80 italic">ca đang chờ data của ClassIn về</span>
                        </div>
                        @endif
                        
                        <!-- Ca KHÔNG thấy có data trên ClassIn (>30 phút) -->
                        @if($noDataOver30min > 0)
                        <div class="flex items-center gap-2 flex-wrap border-l-2 border-red-300 pl-3 py-0.5">
                            <span class="text-xs text-red-700 dark:text-red-400 font-medium">⚠️</span>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400">{{ number_format($noDataOver30min) }}</span>
                            <span class="text-xs text-red-600/80 dark:text-red-400/80 italic">ca KHÔNG thấy có data trên ClassIn</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
        
        <!-- Level 1: Đã lên lịch -->
        <div class="ml-4 md:ml-6 border-l-3 border-blue-400 pl-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm md:text-base font-semibold text-blue-700 dark:text-blue-400">📅 Đã lên lịch:</span>
                <span class="text-base md:text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($scheduled) }}</span>
                <span class="text-sm text-blue-600/80 dark:text-blue-400/80 bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded-full">({{ $scheduledPct }}%)</span>
            </div>
            <div class="mt-2 flex-1">
                <div class="bg-blue-200 dark:bg-blue-800 rounded-full h-2.5 max-w-[280px]">
                    <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $scheduledPct }}%"></div>
                </div>
            </div>
        </div>
        
        <!-- Level 1: Đã hủy -->
        <div class="ml-4 md:ml-6 border-l-3 border-red-400 pl-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm md:text-base font-semibold text-red-700 dark:text-red-400">❌ Đã hủy:</span>
                <span class="text-base md:text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($cancelled) }}</span>
                <span class="text-sm text-red-600/80 dark:text-red-400/80 bg-red-100 dark:bg-red-900/40 px-2 py-0.5 rounded-full">({{ $cancelledPct }}%)</span>
            </div>
            <div class="mt-2 flex-1">
                <div class="bg-red-200 dark:bg-red-800 rounded-full h-2.5 max-w-[280px]">
                    <div class="bg-red-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $cancelledPct }}%"></div>
                </div>
            </div>
            
            {{-- Phase 102: Hủy gấp with Teacher/Student/Admin breakdown --}}
            @php
                $urgentCancelled = $stats['status_breakdown']['urgent_cancelled'] ?? 0;
                $urgentByTeacher = $stats['status_breakdown']['urgent_by_teacher'] ?? 0;
                $urgentByStudent = $stats['status_breakdown']['urgent_by_student'] ?? 0;
                $urgentByAdmin = $stats['status_breakdown']['urgent_by_admin'] ?? 0;
            @endphp
            @if($urgentCancelled > 0 || $cancelled > 0)
            <div class="mt-3 ml-4 md:ml-6">
                <div class="border-l-2 border-orange-400 pl-3 py-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm md:text-base text-orange-700 dark:text-orange-400 font-medium">⚡ Hủy gấp:</span>
                        <span class="text-base md:text-lg font-bold text-orange-600 dark:text-orange-400">{{ number_format($urgentCancelled) }}</span>
                        @if($cancelled > 0)
                        <span class="text-sm text-orange-600/80 dark:text-orange-400/80 bg-orange-100 dark:bg-orange-900/40 px-2 py-0.5 rounded-full">({{ round(($urgentCancelled / $cancelled) * 100, 1) }}% số hủy)</span>
                        @endif
                    </div>
                    
                    {{-- Breakdown by who cancelled --}}
                    @if($urgentCancelled > 0)
                    <div class="mt-2 ml-4 space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap border-l-2 border-amber-300 pl-3 py-0.5">
                            <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">👩‍🏫 Giáo viên hủy:</span>
                            <span class="text-sm font-bold text-amber-600 dark:text-amber-400">{{ number_format($urgentByTeacher) }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap border-l-2 border-blue-300 pl-3 py-0.5">
                            <span class="text-xs text-blue-700 dark:text-blue-400 font-medium">👨‍🎓 Học sinh hủy:</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ number_format($urgentByStudent) }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap border-l-2 border-purple-300 pl-3 py-0.5">
                            <span class="text-xs text-purple-700 dark:text-purple-400 font-medium">🖥️ Admin hủy:</span>
                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ number_format($urgentByAdmin) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
