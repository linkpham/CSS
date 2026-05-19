@props(['stats', 'periodKey', 'periodLabel'])

@php
    $total = $stats['total'] ?? 0;
    $scheduled = $stats['status_breakdown']['scheduled'] ?? 0;
    $completed = $stats['status_breakdown']['completed'] ?? 0;
    $cancelled = $stats['status_breakdown']['cancelled'] ?? 0;
    $urgentCancelled = $stats['status_breakdown']['urgent_cancelled'] ?? 0;
    $urgentByTeacher = $stats['status_breakdown']['urgent_by_teacher'] ?? 0;
    $urgentByStudent = $stats['status_breakdown']['urgent_by_student'] ?? 0;
    $urgentByAdmin = $stats['status_breakdown']['urgent_by_admin'] ?? 0;
    $chargeable = $stats['completed_breakdown']['chargeable'] ?? 0;
    $compensate = $stats['completed_breakdown']['compensate'] ?? 0;
    $awaitingData = $stats['completed_breakdown']['awaiting_classin_data'] ?? 0;
    $awaitingWithin30min = $stats['completed_breakdown']['awaiting_within_30min'] ?? 0;
    $noDataOver30min = $stats['completed_breakdown']['no_data_over_30min'] ?? 0;
    
    // Calculate percentages
    $completedPct = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    $scheduledPct = $total > 0 ? round(($scheduled / $total) * 100, 1) : 0;
    $cancelledPct = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
    $chargeablePct = $completed > 0 ? round(($chargeable / $completed) * 100, 1) : 0;
    $compensatePct = $completed > 0 ? round(($compensate / $completed) * 100, 1) : 0;
    
    // Phase 137: Dynamic Subject IDs for SQL reference based on active program
    $activeProgram = request('program', 'all');
    $subjectIds = match($activeProgram) {
        'speakwell' => '533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577',
        'easyspeak' => '403, 404, 471, 582, 583, 584, 585, 586',
        default => '533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471',
    };
@endphp

<div class="space-y-1">
    {{-- Hierarchical KPI Display --}}
    <div class="p-4 md:p-6 bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-800/50 dark:to-blue-900/30 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        
        <div class="space-y-4">
            {{-- Level 0: Tổng ca học (Root) --}}
            <div class="flex items-center gap-3 pb-3 border-b-2 border-blue-300 dark:border-blue-600">
                <span class="text-lg md:text-xl font-bold text-blue-700 dark:text-blue-400">📋 Tổng ca học:</span>
                <span class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($total) }}</span>
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng ca học</span><br>
                    Tổng số ca học có status = 2, 3, 4 (Đã lên lịch, Hoàn thành, Đã hủy).<br><br>
                    <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                    WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                    AND ordles_status IN (2, 3, 4)<br>
                    AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                </span></span>
            </div>
            
            <div class="space-y-3 ml-0 md:ml-0">
                {{-- Level 1: Đã hoàn thành --}}
                <div class="relative">
                    <div class="ml-0 md:ml-0 space-y-4">
                        {{-- Đã hoàn thành --}}
                        <div class="pl-4 border-l-4 border-green-400 dark:border-green-500 bg-green-50/50 dark:bg-green-900/20 rounded-r-lg py-3 pr-4">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                <span class="text-base md:text-lg font-semibold text-green-700 dark:text-green-400">✅ Đã hoàn thành:</span>
                                <span class="text-xl md:text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($completed) }}</span>
                                <span class="text-sm md:text-base text-green-600/90 dark:text-green-400/90 bg-green-100 dark:bg-green-900/50 px-2.5 py-1 rounded-full font-medium">({{ $completedPct }}%)</span>
                                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hoàn thành</span><br>
                                    Ca học có status = 3 (Completed). Bao gồm cả ca thành công và thất bại.<br><br>
                                    <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                    WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                    AND ordles_status = 3<br>
                                    AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                </span></span>
                            </div>
                            
                            {{-- Progress bar --}}
                            <div class="mt-2 max-w-md">
                                <div class="bg-green-200 dark:bg-green-800 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-green-400 to-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $completedPct }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Level 2: Breakdown của hoàn thành --}}
                            @if($completed > 0)
                            <div class="mt-4 ml-4 md:ml-6 space-y-2.5">
                                {{-- Số ca đã tính phí --}}
                                <div class="flex flex-wrap items-center gap-2 pl-3 py-2 border-l-3 border-emerald-400 dark:border-emerald-500 bg-emerald-50/70 dark:bg-emerald-900/30 rounded-r-lg">
                                    <span class="text-sm md:text-base text-emerald-700 dark:text-emerald-400 font-medium">💰 Số ca đã tính phí:</span>
                                    <span class="text-lg md:text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($chargeable) }}</span>
                                    <span class="text-sm text-emerald-600/80 dark:text-emerald-400/80 bg-emerald-100 dark:bg-emerald-900/50 px-2 py-0.5 rounded-full">({{ $chargeablePct }}%)</span>
                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số ca đã tính phí</span><br>
                                        Ca học hoàn thành có mã chấp nhận tính phí HV (4-12, 16, 17).<br><br>
                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                        JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                        WHERE ol.ordles_status = 3<br>
                                        AND ole.ole_acceptance_code IN (4,5,6,7,8,9,10,11,12,16,17)<br>
                                        AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                    </span></span>
                                </div>
                                
                                {{-- Số ca bù buổi --}}
                                <div class="flex flex-wrap items-center gap-2 pl-3 py-2 border-l-3 border-amber-400 dark:border-amber-500 bg-amber-50/70 dark:bg-amber-900/30 rounded-r-lg">
                                    <span class="text-sm md:text-base text-amber-700 dark:text-amber-400 font-medium">🔄 Số ca bù buổi:</span>
                                    <span class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($compensate) }}</span>
                                    <span class="text-sm text-amber-600/80 dark:text-amber-400/80 bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full">({{ $compensatePct }}%)</span>
                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số ca bù buổi</span><br>
                                        Ca học hoàn thành có mã chấp nhận bù buổi (1-3, 13-15). HV được học bù miễn phí.<br><br>
                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                        JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                        WHERE ol.ordles_status = 3<br>
                                        AND ole.ole_acceptance_code IN (1,2,3,13,14,15)<br>
                                        AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                    </span></span>
                                </div>
                                
                                {{-- Ca chưa có dữ liệu trả về (chỉ hiển thị nếu có) --}}
                                @if($awaitingData > 0)
                                <div class="pl-3 py-2 border-l-3 border-yellow-400 dark:border-yellow-500 bg-yellow-50/70 dark:bg-yellow-900/30 rounded-r-lg">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm md:text-base text-yellow-700 dark:text-yellow-400 font-medium">⏳</span>
                                        <span class="text-lg md:text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($awaitingData) }}</span>
                                        <span class="text-sm text-yellow-600/80 dark:text-yellow-400/80 italic">ca chưa có dữ liệu trả về (thông thường Classin sẽ gửi data về sau mỗi 20ph)</span>
                                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Ca chưa có dữ liệu</span><br>
                                            Ca học hoàn thành (status=3) nhưng chưa có dữ liệu trong bảng tbl_order_lessons_extras.<br><br>
                                            <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                            LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                            WHERE ol.ordles_tlang_id IN ({{ $subjectIds }})<br>
                                            AND ol.ordles_status = 3<br>
                                            AND ole.ole_ordles_id IS NULL<br>
                                            AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                        </span></span>
                                    </div>
                                    
                                    {{-- Level 3: Breakdown của ca chưa có dữ liệu --}}
                                    <div class="mt-3 ml-4 md:ml-6 space-y-2">
                                        {{-- Ca đang chờ data (<=30 phút) --}}
                                        @if($awaitingWithin30min > 0)
                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-orange-300 dark:border-orange-500 bg-orange-50/50 dark:bg-orange-900/20 rounded-r-lg">
                                            <span class="text-sm text-orange-700 dark:text-orange-400 font-medium">🕐</span>
                                            <span class="text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($awaitingWithin30min) }}</span>
                                            <span class="text-xs text-orange-600/80 dark:text-orange-400/80 italic">ca đang chờ data của ClassIn về</span>
                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đang chờ data</span><br>
                                                Không có dữ liệu trong bảng order_lessons_extras VÀ NOW() - ordles_lesson_endtime &lt;= 30 phút.<br><br>
                                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                                LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                                WHERE ol.ordles_status = 3<br>
                                                AND ole.ole_ordles_id IS NULL<br>
                                                AND TIMESTAMPDIFF(MINUTE, ordles_lesson_endtime, NOW()) &lt;= 30</span>
                                            </span></span>
                                        </div>
                                        @endif
                                        
                                        {{-- Ca KHÔNG thấy có data trên ClassIn (>30 phút) --}}
                                        @if($noDataOver30min > 0)
                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-red-300 dark:border-red-500 bg-red-50/50 dark:bg-red-900/20 rounded-r-lg">
                                            <span class="text-sm text-red-700 dark:text-red-400 font-medium">⚠️</span>
                                            <span class="text-base font-bold text-red-600 dark:text-red-400">{{ number_format($noDataOver30min) }}</span>
                                            <span class="text-xs text-red-600/80 dark:text-red-400/80 italic">ca KHÔNG thấy có data trên ClassIn</span>
                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Không có data trên ClassIn</span><br>
                                                Không có dữ liệu trong bảng order_lessons_extras VÀ NOW() - ordles_lesson_endtime &gt; 30 phút.<br><br>
                                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                                LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                                WHERE ol.ordles_status = 3<br>
                                                AND ole.ole_ordles_id IS NULL<br>
                                                AND TIMESTAMPDIFF(MINUTE, ordles_lesson_endtime, NOW()) &gt; 30</span>
                                            </span></span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        
                        {{-- Đã lên lịch --}}
                        <div class="pl-4 border-l-4 border-blue-400 dark:border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 rounded-r-lg py-3 pr-4">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                <span class="text-base md:text-lg font-semibold text-blue-700 dark:text-blue-400">📅 Đã lên lịch:</span>
                                <span class="text-xl md:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($scheduled) }}</span>
                                <span class="text-sm md:text-base text-blue-600/90 dark:text-blue-400/90 bg-blue-100 dark:bg-blue-900/50 px-2.5 py-1 rounded-full font-medium">({{ $scheduledPct }}%)</span>
                                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã lên lịch</span><br>
                                    Ca học có status = 2 (Scheduled). Đã đặt lịch nhưng chưa diễn ra hoặc chưa hoàn thành.<br><br>
                                    <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                    WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                    AND ordles_status = 2<br>
                                    AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                </span></span>
                            </div>
                            
                            {{-- Progress bar --}}
                            <div class="mt-2 max-w-md">
                                <div class="bg-blue-200 dark:bg-blue-800 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $scheduledPct }}%"></div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Đã hủy --}}
                        <div class="pl-4 border-l-4 border-red-400 dark:border-red-500 bg-red-50/50 dark:bg-red-900/20 rounded-r-lg py-3 pr-4">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                <span class="text-base md:text-lg font-semibold text-red-700 dark:text-red-400">❌ Đã hủy:</span>
                                <span class="text-xl md:text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($cancelled) }}</span>
                                <span class="text-sm md:text-base text-red-600/90 dark:text-red-400/90 bg-red-100 dark:bg-red-900/50 px-2.5 py-1 rounded-full font-medium">({{ $cancelledPct }}%)</span>
                                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy</span><br>
                                    Tất cả ca học có status = 4 (Cancelled).<br><br>
                                    <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                    WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                    AND ordles_status = 4<br>
                                    AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                </span></span>
                            </div>
                            
                            {{-- Progress bar --}}
                            <div class="mt-2 max-w-md">
                                <div class="bg-red-200 dark:bg-red-800 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-red-400 to-red-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $cancelledPct }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Hủy gấp (Urgent cancellation) with Teacher/Student/Admin breakdown --}}
                            @if($urgentCancelled > 0 || $cancelled > 0)
                            <div class="mt-4 ml-4 md:ml-6">
                                <div class="pl-3 py-2 border-l-3 border-orange-500 dark:border-orange-400 bg-orange-50/70 dark:bg-orange-900/30 rounded-r-lg">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm md:text-base text-orange-700 dark:text-orange-400 font-medium">⚡ Hủy gấp:</span>
                                        <span class="text-lg md:text-xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($urgentCancelled) }}</span>
                                        @if($cancelled > 0)
                                        <span class="text-sm text-orange-600/80 dark:text-orange-400/80 bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 rounded-full">({{ round(($urgentCancelled / $cancelled) * 100, 1) }}% số hủy)</span>
                                        @endif
                                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hủy gấp</span><br>
                                            Ca học bị hủy trong vòng 24 giờ trước khi buổi học diễn ra, phân loại theo người hủy.<br><br>
                                            <span class="tooltip-sql">SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id)<br>
                                            FROM tbl_order_lessons ol<br>
                                            INNER JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id<br>
                                            WHERE ol.ordles_status = 4 AND sl.sesslog_changed_status = 4<br>
                                            AND ol.ordles_updated &gt; DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)<br>
                                            GROUP BY sl.sesslog_user_type</span>
                                        </span></span>
                                    </div>
                                    
                                    {{-- Phase 102: Breakdown by Teacher/Student/Admin --}}
                                    @if($urgentCancelled > 0)
                                    <div class="mt-3 ml-4 md:ml-6 space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-amber-300 dark:border-amber-500 bg-amber-50/50 dark:bg-amber-900/20 rounded-r-lg">
                                            <span class="text-sm text-amber-700 dark:text-amber-400 font-medium">👩‍🏫 Giáo viên hủy:</span>
                                            <span class="text-base font-bold text-amber-600 dark:text-amber-400">{{ number_format($urgentByTeacher) }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-blue-300 dark:border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 rounded-r-lg">
                                            <span class="text-sm text-blue-700 dark:text-blue-400 font-medium">👨‍🎓 Học sinh hủy:</span>
                                            <span class="text-base font-bold text-blue-600 dark:text-blue-400">{{ number_format($urgentByStudent) }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-purple-300 dark:border-purple-500 bg-purple-50/50 dark:bg-purple-900/20 rounded-r-lg">
                                            <span class="text-sm text-purple-700 dark:text-purple-400 font-medium">🖥️ Admin hủy:</span>
                                            <span class="text-base font-bold text-purple-600 dark:text-purple-400">{{ number_format($urgentByAdmin) }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Phase 143: SPEAKWELL & EASYSPEAK Program Breakdown --}}
    @php
        $programBreakdown = $stats['program_breakdown'] ?? null;
        $swData = $programBreakdown['speakwell'] ?? null;
        $esData = $programBreakdown['easyspeak'] ?? null;
    @endphp
    @if($programBreakdown && ($swData || $esData))
    <div class="mt-4 p-4 md:p-6 bg-gradient-to-br from-violet-50/80 via-white to-cyan-50/80 dark:from-violet-900/20 dark:via-slate-800/50 dark:to-cyan-900/20 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <h4 class="text-sm md:text-base font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
            📊 Chi tiết theo Chương trình
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Chi tiết SPEAKWELL & EASYSPEAK</span><br>
                Phân tích riêng cho từng chương trình học.<br>
                • <strong>SPEAKWELL</strong>: 28 bộ môn (ordles_tlang_id: 533, 558, ...)<br>
                • <strong>EASYSPEAK</strong>: 8 bộ môn (ordles_tlang_id: 403, 404, ...)
            </span></span>
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- SPEAKWELL Column --}}
            @if($swData)
            @php
                $swTotal = $swData['total'] ?? 0;
                $swChargeable = $swData['chargeable'] ?? 0;
                $swAwaiting30 = $swData['awaiting_within_30min'] ?? 0;
                $swNoData30 = $swData['no_data_over_30min'] ?? 0;
                $swScheduled = $swData['scheduled'] ?? 0;
                $swCancelled = $swData['cancelled'] ?? 0;
                $swUrgent = $swData['urgent_cancelled'] ?? 0;
                $swByTeacher = $swData['urgent_by_teacher'] ?? 0;
                $swByStudent = $swData['urgent_by_student'] ?? 0;
                $swByAdmin = $swData['urgent_by_admin'] ?? 0;
            @endphp
            <div class="rounded-xl border-2 border-violet-300 dark:border-violet-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 px-4 py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">🟣 SPEAKWELL</span>
                        <span class="text-white/90 font-bold text-lg md:text-xl">{{ number_format($swTotal) }}</span>
                    </div>
                    <p class="text-violet-100 text-xs mt-0.5">28 bộ môn</p>
                </div>
                {{-- Body --}}
                <div class="p-3 md:p-4 space-y-2">
                    {{-- Tổng ca học --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📋 Tổng ca học</span>
                        <span class="text-sm md:text-base font-bold text-violet-600 dark:text-violet-400">{{ number_format($swTotal) }}</span>
                    </div>
                    {{-- Số ca đã tính phí --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">💰 Đã tính phí</span>
                        <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($swChargeable) }}</span>
                    </div>
                    {{-- Đang chờ data ClassIn --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">🕐 Chờ data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($swAwaiting30) }}</span>
                    </div>
                    {{-- KHÔNG có data ClassIn --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚠️ Không có data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($swNoData30) }}</span>
                    </div>
                    {{-- Đã lên lịch --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📅 Đã lên lịch</span>
                        <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400">{{ number_format($swScheduled) }}</span>
                    </div>
                    {{-- Đã hủy --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">❌ Đã hủy</span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($swCancelled) }}</span>
                    </div>
                    {{-- Hủy gấp --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚡ Hủy gấp</span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($swUrgent) }}</span>
                    </div>
                    {{-- Cancellation breakdown --}}
                    <div class="ml-4 space-y-1.5 pb-1">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👩‍🏫 Giáo viên hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($swByTeacher) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👨‍🎓 Học sinh hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($swByStudent) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">🖥️ Admin hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($swByAdmin) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- EASYSPEAK Column --}}
            @if($esData)
            @php
                $esTotal = $esData['total'] ?? 0;
                $esChargeable = $esData['chargeable'] ?? 0;
                $esAwaiting30 = $esData['awaiting_within_30min'] ?? 0;
                $esNoData30 = $esData['no_data_over_30min'] ?? 0;
                $esScheduled = $esData['scheduled'] ?? 0;
                $esCancelled = $esData['cancelled'] ?? 0;
                $esUrgent = $esData['urgent_cancelled'] ?? 0;
                $esByTeacher = $esData['urgent_by_teacher'] ?? 0;
                $esByStudent = $esData['urgent_by_student'] ?? 0;
                $esByAdmin = $esData['urgent_by_admin'] ?? 0;
            @endphp
            <div class="rounded-xl border-2 border-cyan-300 dark:border-cyan-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-cyan-500 to-teal-600 px-4 py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">🔵 EASYSPEAK</span>
                        <span class="text-white/90 font-bold text-lg md:text-xl">{{ number_format($esTotal) }}</span>
                    </div>
                    <p class="text-cyan-100 text-xs mt-0.5">8 bộ môn</p>
                </div>
                {{-- Body --}}
                <div class="p-3 md:p-4 space-y-2">
                    {{-- Tổng ca học --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📋 Tổng ca học</span>
                        <span class="text-sm md:text-base font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($esTotal) }}</span>
                    </div>
                    {{-- Số ca đã tính phí --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">💰 Đã tính phí</span>
                        <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($esChargeable) }}</span>
                    </div>
                    {{-- Đang chờ data ClassIn --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">🕐 Chờ data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($esAwaiting30) }}</span>
                    </div>
                    {{-- KHÔNG có data ClassIn --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚠️ Không có data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($esNoData30) }}</span>
                    </div>
                    {{-- Đã lên lịch --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📅 Đã lên lịch</span>
                        <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400">{{ number_format($esScheduled) }}</span>
                    </div>
                    {{-- Đã hủy --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">❌ Đã hủy</span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($esCancelled) }}</span>
                    </div>
                    {{-- Hủy gấp --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚡ Hủy gấp</span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($esUrgent) }}</span>
                    </div>
                    {{-- Cancellation breakdown --}}
                    <div class="ml-4 space-y-1.5 pb-1">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👩‍🏫 Giáo viên hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($esByTeacher) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👨‍🎓 Học sinh hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($esByStudent) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">🖥️ Admin hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($esByAdmin) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Phase 225: Class Type Breakdown (1:1 and 1:2) --}}
    @php
        $classTypeBreakdown = $stats['class_type_breakdown'] ?? null;
        $oneOnOne = $classTypeBreakdown['one_on_one'] ?? null;
        $oneOnTwo = $classTypeBreakdown['one_on_two'] ?? null;
    @endphp
    @if($classTypeBreakdown && ($oneOnOne || $oneOnTwo))
    <div class="mt-4 p-4 md:p-6 bg-gradient-to-br from-cyan-50/80 via-white to-teal-50/80 dark:from-cyan-900/20 dark:via-slate-800/50 dark:to-teal-900/20 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <h4 class="text-sm md:text-base font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
            📊 Chi tiết theo Loại lớp
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Chi tiết Lớp 1:1 & Lớp 1:2</span><br>
                Phân tích riêng cho từng loại lớp học.<br>
                • <strong>Lớp 1:1</strong>: Bảng <span class="tooltip-table">tbl_order_lessons</span> (1 GV - 1 HV)<br>
                • <strong>Lớp 1:2</strong>: Bảng <span class="tooltip-table">tbl_group_classes</span> (1 GV - 2 HV)
            </span></span>
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Lớp 1:1 Column --}}
            @if($oneOnOne)
            @php
                $ooTotal = $oneOnOne['total'] ?? 0;
                $ooScheduled = $oneOnOne['scheduled'] ?? 0;
                $ooCompleted = $oneOnOne['completed'] ?? 0;
                $ooCancelled = $oneOnOne['cancelled'] ?? 0;
                $ooChargeable = $oneOnOne['chargeable'] ?? 0;
                $ooCompensate = $oneOnOne['compensate'] ?? 0;
                $ooAwaiting30 = $oneOnOne['awaiting_within_30min'] ?? 0;
                $ooNoData30 = $oneOnOne['no_data_over_30min'] ?? 0;
                $ooUrgent = $oneOnOne['urgent_cancelled'] ?? 0;
                $ooByTeacher = $oneOnOne['urgent_by_teacher'] ?? 0;
                $ooByStudent = $oneOnOne['urgent_by_student'] ?? 0;
                $ooByAdmin = $oneOnOne['urgent_by_admin'] ?? 0;
            @endphp
            <div class="rounded-xl border-2 border-cyan-300 dark:border-cyan-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">👤 Lớp 1:1</span>
                        <span class="text-white/90 font-bold text-lg md:text-xl">{{ number_format($ooTotal) }}</span>
                    </div>
                    <p class="text-cyan-100 text-xs mt-0.5">1 Giáo viên - 1 Học viên</p>
                </div>
                {{-- Body --}}
                <div class="p-3 md:p-4 space-y-2">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📋 Tổng ca học
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng ca học - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ordles_status IN (2, 3, 4)<br>
                                AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($ooTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">✅ Đã hoàn thành
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hoàn thành - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ordles_status = 3<br>
                                AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-green-600 dark:text-green-400">{{ number_format($ooCompleted) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">💰 Đã tính phí
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã tính phí - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                WHERE ol.ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ol.ordles_status = 3<br>
                                AND ole.ole_acceptance_code IN (4,5,6,7,8,9,10,11,12,16,17)<br>
                                AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($ooChargeable) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">🔄 Bù buổi
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bù buổi - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
                                JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id<br>
                                WHERE ol.ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ol.ordles_status = 3<br>
                                AND ole.ole_acceptance_code IN (1,2,3,13,14,15)<br>
                                AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-amber-600 dark:text-amber-400">{{ number_format($ooCompensate) }}</span>
                    </div>
                    @if($ooAwaiting30 > 0)
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">🕐 Chờ data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($ooAwaiting30) }}</span>
                    </div>
                    @endif
                    @if($ooNoData30 > 0)
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚠️ Không có data ClassIn</span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($ooNoData30) }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📅 Đã lên lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã lên lịch - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ordles_status = 2<br>
                                AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400">{{ number_format($ooScheduled) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">❌ Đã hủy
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                WHERE ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ordles_status = 4<br>
                                AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($ooCancelled) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚡ Hủy gấp
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hủy gấp - Lớp 1:1</span><br>
                                <span class="tooltip-sql">SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id)<br>
                                FROM tbl_order_lessons ol<br>
                                INNER JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id<br>
                                    AND sl.sesslog_record_type = 1<br>
                                WHERE ol.ordles_status = 4 AND sl.sesslog_changed_status = 4<br>
                                AND ol.ordles_tlang_id IN ({{ $subjectIds }})<br>
                                AND ol.ordles_updated &gt; DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)<br>
                                AND ol.ordles_lesson_starttime BETWEEN [start] AND [end]<br>
                                GROUP BY sl.sesslog_user_type</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($ooUrgent) }}</span>
                    </div>
                    <div class="ml-4 space-y-1.5 pb-1">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👩‍🏫 Giáo viên hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($ooByTeacher) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👨‍🎓 Học sinh hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($ooByStudent) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">🖥️ Admin hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($ooByAdmin) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Lớp 1:2 Column --}}
            @if($oneOnTwo)
            @php
                $otTotal = $oneOnTwo['total'] ?? 0;
                $otScheduled = $oneOnTwo['scheduled'] ?? 0;
                $otCompleted = $oneOnTwo['completed'] ?? 0;
                $otCancelled = $oneOnTwo['cancelled'] ?? 0;
                $otUrgent = $oneOnTwo['urgent_cancelled'] ?? 0;
                $otByTeacher = $oneOnTwo['urgent_by_teacher'] ?? 0;
                $otByStudent = $oneOnTwo['urgent_by_student'] ?? 0;
                $otByAdmin = $oneOnTwo['urgent_by_admin'] ?? 0;
            @endphp
            <div class="rounded-xl border-2 border-teal-300 dark:border-teal-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-teal-500 to-green-600 px-4 py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">👥 Lớp 1:2</span>
                        <span class="text-white/90 font-bold text-lg md:text-xl">{{ number_format($otTotal) }}</span>
                    </div>
                    <p class="text-teal-100 text-xs mt-0.5">1 Giáo viên - 2 Học viên</p>
                </div>
                {{-- Body --}}
                <div class="p-3 md:p-4 space-y-2">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📋 Tổng ca học
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng ca học - Lớp 1:2</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_group_classes<br>
                                WHERE grpcls_tlang_id IN ({{ $subjectIds }})<br>
                                AND grpcls_status IN (1, 2, 3)<br>
                                AND grpcls_start_datetime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-teal-600 dark:text-teal-400">{{ number_format($otTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">✅ Đã hoàn thành
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hoàn thành - Lớp 1:2</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_group_classes<br>
                                WHERE grpcls_tlang_id IN ({{ $subjectIds }})<br>
                                AND grpcls_status = 2<br>
                                AND grpcls_start_datetime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-green-600 dark:text-green-400">{{ number_format($otCompleted) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">📅 Đã lên lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã lên lịch - Lớp 1:2</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_group_classes<br>
                                WHERE grpcls_tlang_id IN ({{ $subjectIds }})<br>
                                AND grpcls_status = 1<br>
                                AND grpcls_start_datetime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400">{{ number_format($otScheduled) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">❌ Đã hủy
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy - Lớp 1:2</span><br>
                                <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_group_classes<br>
                                WHERE grpcls_tlang_id IN ({{ $subjectIds }})<br>
                                AND grpcls_status = 3<br>
                                AND grpcls_start_datetime BETWEEN [start] AND [end]</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400">{{ number_format($otCancelled) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-1.5">⚡ Hủy gấp
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hủy gấp - Lớp 1:2</span><br>
                                <span class="tooltip-sql">SELECT sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id)<br>
                                FROM tbl_group_classes gc<br>
                                INNER JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id<br>
                                    AND sl.sesslog_record_type = 2<br>
                                WHERE gc.grpcls_status = 3 AND sl.sesslog_changed_status = 3<br>
                                AND gc.grpcls_tlang_id IN ({{ $subjectIds }})<br>
                                AND sl.sesslog_created &gt; DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)<br>
                                AND gc.grpcls_start_datetime BETWEEN [start] AND [end]<br>
                                GROUP BY sl.sesslog_user_type</span>
                            </span></span>
                        </span>
                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400">{{ number_format($otUrgent) }}</span>
                    </div>
                    <div class="ml-4 space-y-1.5 pb-1">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👩‍🏫 Giáo viên hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($otByTeacher) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">👨‍🎓 Học sinh hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($otByStudent) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-xs text-slate-500 dark:text-slate-500 flex items-center gap-1.5">🖥️ Admin hủy</span>
                            <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($otByAdmin) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
