@extends('layouts.app')

@section('title', 'ICan Dashboard - Quản trị Giáo viên')
@section('page-title', 'Quản trị Giáo viên & Bài kiểm tra')

@section('content')
@php
    $activeProgram = request('program', 'all');
@endphp

<div class="space-y-6">
    <!-- Phase 138: Program indicator (📊 All) -->
    <x-program-tabs :activeProgram="$activeProgram" />
    
    <!-- Leave Request Overview -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📋 Tổng quan Nghỉ phép
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span><br>Thống kê đơn nghỉ phép của GV<br>tealv_status: 1=Chờ, 2=Tự động duyệt, 3=Duyệt, 4=Từ chối</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($leaveStats['total'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng đơn</p>
            </div>
            <div class="text-center p-4 {{ ($leaveStats['pending'] ?? 0) > 0 ? 'bg-amber-500/10 border-amber-500/30' : 'bg-gray-500/5 border-gray-500/20' }} rounded-lg border">
                <p class="text-3xl font-bold {{ ($leaveStats['pending'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">{{ number_format($leaveStats['pending'] ?? 0) }}</p>
                <p class="text-sm {{ ($leaveStats['pending'] ?? 0) > 0 ? 'text-amber-600/80 dark:text-amber-400/80' : 'text-gray-600/80 dark:text-gray-400/80' }}">Chờ duyệt</p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($leaveStats['total_approved'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã duyệt</p>
            </div>
            <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($leaveStats['rejected'] ?? 0) }}</p>
                <p class="text-sm text-red-600/80 dark:text-red-400/80">Từ chối</p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($leaveStats['total_approved_days'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Ngày nghỉ đã duyệt</p>
            </div>
            <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $leaveStats['approval_rate'] ?? 0 }}%</p>
                <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ duyệt</p>
            </div>
        </div>
        <!-- Leave type breakdown -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Nghỉ ngắn hạn (≤6 ngày)</span>
                <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ $leaveStats['short_term'] ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Nghỉ dài hạn (≥7 ngày)</span>
                <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ $leaveStats['long_term'] ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tháng này</span>
                <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ $leaveStats['this_month'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <!-- ===== TEACHER AVAILABILITY GRID (Phase 113) ===== -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm" x-data="teacherAvailabilityGrid()">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            📅 Danh sách GV khả dụng theo khung giờ
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_availability</span>, <span class="tooltip-table">tbl_order_lessons</span>, <span class="tooltip-table">tbl_group_classes</span>, <span class="tooltip-table">tbl_teacher_stats</span><br>Chỉ GV Speakwell (<span class="tooltip-table">tbl_user_teach_languages</span>) đủ qualified<br><span class="tooltip-label">SQL Query</span><span class="tooltip-sql">-- Bước 1: Lấy danh sách GV Speakwell
SELECT DISTINCT utl_user_id
FROM tbl_user_teach_languages
WHERE utl_tlang_id IN (533, 558, 560, ...)

-- Bước 1b: Lọc GV đủ qualified
SELECT testat_user_id FROM tbl_teacher_stats
WHERE testat_user_id IN ([teacher_ids])
AND testat_preference &gt; 0
AND testat_qualification &gt; 0
AND testat_teachlang &gt; 0
AND testat_speaklang &gt; 0
AND testat_availability &gt; 0

-- Bước 2: Lấy ca khả dụng trong tuần
SELECT avail_user_id, avail_starttime, avail_endtime
FROM tbl_availability
WHERE avail_user_id IN ([qualified_ids])
AND avail_starttime &lt; [week_end_utc]
AND avail_endtime &gt; [week_start_utc]

-- Bước 3: Trừ buổi học 1-1 đã book
SELECT ol.ordles_teacher_id,
  ol.ordles_lesson_starttime,
  ol.ordles_lesson_endtime,
  ol.ordles_status
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
WHERE ol.ordles_teacher_id IN ([qualified_ids])
AND ol.ordles_status IN (2, 3)
AND o.order_payment_status = 1
AND o.order_status = 2
-- Ngày quá khứ: trừ cả status=2 (Scheduled)
--   VÀ status=3 (Completed)
-- Ngày hiện tại/tương lai: chỉ trừ status=2

-- Bước 4: Trừ lớp nhóm đã book
SELECT grpcls_teacher_id,
  grpcls_start_datetime, grpcls_end_datetime
FROM tbl_group_classes
WHERE grpcls_teacher_id IN ([qualified_ids])
AND grpcls_status = 1

-- Kết quả: Khả dụng = GV qualified
--   AND Có ca trống
--   AND Không trùng buổi 1-1
--   AND Không trùng lớp nhóm
-- Lưu ý: Ngày quá khứ trừ thêm
--   buổi hoàn thành (ordles_status=3)</span></span></span>
        </h3>

        <!-- Slot Mode Tabs (Even / Odd) -->
        <div class="flex items-center gap-2 mb-4">
            <button @click="slotMode = 'odd'; loadData()" :class="slotMode === 'odd' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-4 py-1.5 text-sm font-medium rounded-lg transition">
                📊 Khung giờ lẻ
            </button>
            <button @click="slotMode = 'even'; loadData()" :class="slotMode === 'even' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-4 py-1.5 text-sm font-medium rounded-lg transition">
                📐 Khung giờ chẵn
            </button>
            <span class="text-xs text-light-text-muted dark:text-zeus-text-muted ml-2" x-text="slotMode === 'odd' ? '(Khung giờ thực tế)' : '(30 phút: :00, :30)'"></span>
        </div>

        <!-- Filters Row 1 -->
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <!-- Week navigation -->
            <div class="flex items-center gap-1">
                <button @click="changeWeek(-1)" class="px-2 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text hover:bg-light-border dark:hover:bg-zeus-border transition" title="Tuần trước">
                    «
                </button>
                <span class="px-3 py-1.5 text-sm font-medium text-light-text dark:text-zeus-text bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border min-w-[160px] text-center" x-text="weekLabel"></span>
                <button @click="changeWeek(1)" class="px-2 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text hover:bg-light-border dark:hover:bg-zeus-border transition" title="Tuần sau">
                    »
                </button>
                <button @click="goToCurrentWeek()" class="px-3 py-1.5 text-sm rounded-lg bg-zeus-accent/10 text-zeus-accent hover:bg-zeus-accent/20 transition" title="Tuần hiện tại">
                    Hôm nay
                </button>
            </div>

            <!-- Teacher search -->
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                    x-model="teacherSearch"
                    @input.debounce.500ms="loadData()"
                    placeholder="Tìm GV theo tên/email..."
                    class="w-full px-3 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:ring-2 focus:ring-zeus-accent/50 focus:border-zeus-accent outline-none">
            </div>

            <!-- Time range filter (only for even mode) -->
            <div class="flex items-center gap-1" x-show="slotMode === 'even'">
                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Từ</span>
                <select x-model="timeFrom" @change="loadData()"
                    class="px-2 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                    <template x-for="h in allHours" :key="'from-' + h">
                        <option :value="h" x-text="h + ':00'"></option>
                    </template>
                </select>
                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">đến</span>
                <select x-model="timeTo" @change="loadData()"
                    class="px-2 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                    <template x-for="h in allHours" :key="'to-' + h">
                        <option :value="h" x-text="h + ':00'"></option>
                    </template>
                </select>
            </div>

            <!-- Teacher type filter -->
            <select x-model="teacherType" @change="loadData()"
                class="px-3 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                <option value="">Tất cả loại GV</option>
                <option value="VN">🇻🇳 VN (Vietnam)</option>
                <option value="PHIL">🇵🇭 PHIL (Philippines)</option>
                <option value="NN">🌍 NN (Native English)</option>
            </select>

            <!-- Trial filter dropdown -->
            <select x-model="trialFilter" @change="loadData()" class="px-3 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-zeus-accent focus:border-zeus-accent">
                <option value="all">📋 Đầy đủ danh sách</option>
                <option value="exclude">🚫 Không có GV dạy Trial</option>
                <option value="only">🎯 Chỉ có GV dạy Trial</option>
            </select>

            <!-- Export button -->
            <button @click="exportToExcel()" class="px-3 py-1.5 text-sm bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/20 transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xuất Excel
            </button>
        </div>

        <!-- Schedule Search Section -->
        <div class="mb-4 p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
            <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3 flex items-center gap-2">
                🔍 Tìm lịch GV khả dụng
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_availability</span>, <span class="tooltip-table">tbl_order_lessons</span>, <span class="tooltip-table">tbl_group_classes</span>, <span class="tooltip-table">tbl_teacher_stats</span><br><span class="tooltip-label">SQL Query</span><span class="tooltip-sql">-- Với mỗi khung (time, day), kiểm tra
-- 4 tuần từ ngày bắt đầu:

-- Bước 1: Lấy GV Speakwell
SELECT DISTINCT utl_user_id
FROM tbl_user_teach_languages
WHERE utl_tlang_id IN (533, 558, 560, ...)

-- Bước 1b: Lọc GV đủ qualified
SELECT testat_user_id FROM tbl_teacher_stats
WHERE testat_preference &gt; 0
AND testat_qualification &gt; 0
AND testat_teachlang &gt; 0
AND testat_speaklang &gt; 0
AND testat_availability &gt; 0

-- Bước 2: GV có ca trống tại mỗi (date, time)
SELECT DISTINCT avail_user_id
FROM tbl_availability
WHERE avail_user_id IN ([qualified_ids])
AND avail_starttime &lt;= [slot_start_utc]
AND avail_endtime &gt;= [slot_end_utc]

-- Bước 3: Trừ GV bận dạy 1-1
SELECT DISTINCT ol.ordles_teacher_id
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
WHERE ol.ordles_status IN (2, 3)
-- Ngày quá khứ: trừ status 2+3
-- Ngày hiện tại/tương lai: chỉ status 2
AND o.order_payment_status = 1
AND o.order_status = 2
AND ol.ordles_lesson_starttime &lt; [slot_end_utc]
AND ol.ordles_lesson_endtime &gt; [slot_start_utc]

-- Bước 4: Trừ GV bận dạy nhóm
SELECT DISTINCT grpcls_teacher_id
FROM tbl_group_classes
WHERE grpcls_status = 1
AND grpcls_start_datetime &lt; [slot_end_utc]
AND grpcls_end_datetime &gt; [slot_start_utc]

-- Kết quả: Đếm số ngày khả dụng/tổng
-- GV nhiều ngày khả dụng nhất xếp trước</span></span></span>
                <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted">(Tìm GV khả dụng theo nhiều khung giờ + thứ, tối đa 7 khung)</span>
            </h4>

            <!-- Slot entries -->
            <div class="space-y-2 mb-3">
                <template x-for="(slot, index) in scheduleSlots" :key="index">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted min-w-[55px]" x-text="'Khung ' + (index + 1) + ':'"></span>
                        <div class="flex items-center gap-1">
                            <label class="text-xs text-light-text-muted dark:text-zeus-text-muted">Giờ</label>
                            <input type="time" x-model="slot.time"
                                class="px-2 py-1 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-white dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                        </div>
                        <div class="flex items-center gap-1">
                            <label class="text-xs text-light-text-muted dark:text-zeus-text-muted">Thứ</label>
                            <select x-model="slot.day"
                                class="px-2 py-1 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-white dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                                <option value="">-- Chọn --</option>
                                <template x-for="dayOpt in scheduleDayOptions" :key="'slot-' + index + '-' + dayOpt.value">
                                    <option :value="dayOpt.value" x-text="dayOpt.label"></option>
                                </template>
                            </select>
                        </div>
                        <button x-show="scheduleSlots.length > 1" @click="removeScheduleSlot(index)"
                            class="p-1 text-red-500 hover:bg-red-500/10 rounded-lg transition" title="Xóa khung">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Add slot button + Start date + Actions -->
            <div class="flex flex-wrap items-end gap-3">
                <button x-show="scheduleSlots.length < 7" @click="addScheduleSlot()"
                    class="px-3 py-1.5 text-sm rounded-lg border border-dashed border-zeus-accent/50 text-zeus-accent hover:bg-zeus-accent/10 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Thêm khung
                </button>
                <div>
                    <label class="block text-xs text-light-text-muted dark:text-zeus-text-muted mb-1">Ngày bắt đầu</label>
                    <input type="date" x-model="scheduleSearchDate"
                        class="px-3 py-1.5 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-white dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                </div>
                <button @click="searchSchedule()" :disabled="!canSearchSchedule()"
                    :class="!canSearchSchedule() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zeus-accent-light'"
                    class="px-4 py-1.5 text-sm bg-zeus-accent text-white rounded-lg transition font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Tìm kiếm
                </button>
                <button @click="exportScheduleSearch()" x-show="scheduleSearchResults !== null && scheduleSearchResults.length > 0"
                    class="px-3 py-1.5 text-sm bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/20 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export
                </button>
            </div>

            <!-- Schedule Search Results -->
            <template x-if="scheduleSearchResults !== null">
                <div class="mt-4">
                    <template x-if="scheduleSearchLoading">
                        <div class="text-center py-4">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-1">Đang tìm kiếm...</p>
                        </div>
                    </template>
                    <template x-if="!scheduleSearchLoading">
                        <div>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mb-2">
                                Tìm thấy <strong class="text-light-text dark:text-zeus-text" x-text="scheduleSearchResults.length"></strong> GV khả dụng cho
                                <strong x-text="scheduleSlots.filter(s => s.time && s.day).length"></strong> khung giờ,
                                từ <strong x-text="scheduleSearchDate"></strong>
                                <span class="text-xs">(kiểm tra 4 tuần)</span>
                            </p>
                            <!-- Show searched slot summary -->
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-for="(slot, idx) in scheduleSlots.filter(s => s.time && s.day)" :key="'summary-' + idx">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zeus-accent/10 text-zeus-accent border border-zeus-accent/20"
                                        x-text="slot.time + ' ' + scheduleDayOptions.find(o => o.value == slot.day)?.label"></span>
                                </template>
                            </div>
                            <template x-if="scheduleSearchResults.length > 0">
                                <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 bg-light-card-alt dark:bg-zeus-card-light">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email</th>
                                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Loại GV</th>
                                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Trial</th>
                                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày khả dụng</th>
                                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Tỷ lệ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                            <template x-for="(teacher, index) in scheduleSearchResults" :key="teacher.id">
                                                <tr class="hover:bg-white dark:hover:bg-zeus-card">
                                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-medium" x-text="teacher.name"></td>
                                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="teacher.email"></td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                            :class="{
                                                                'bg-red-500/20 text-red-600 dark:text-red-400': teacher.teacher_type === 'VN',
                                                                'bg-blue-500/20 text-blue-600 dark:text-blue-400': teacher.teacher_type === 'PHIL',
                                                                'bg-purple-500/20 text-purple-600 dark:text-purple-400': teacher.teacher_type === 'NN',
                                                                'bg-gray-500/20 text-gray-600 dark:text-gray-400': !['VN','PHIL','NN'].includes(teacher.teacher_type)
                                                            }"
                                                            x-text="teacher.teacher_type || 'N/A'"></span>
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span x-show="teacher.can_teach_trial" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-600 dark:text-green-400">✓</span>
                                                        <span x-show="!teacher.can_teach_trial" class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text">
                                                        <span x-text="teacher.available_dates + '/' + teacher.total_dates"></span>
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                            :class="teacher.availability_rate >= 100 ? 'bg-green-500/20 text-green-600 dark:text-green-400' : (teacher.availability_rate >= 50 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-red-500/20 text-red-600 dark:text-red-400')"
                                                            x-text="teacher.availability_rate + '%'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <template x-if="scheduleSearchResults.length === 0">
                                <p class="text-center py-4 text-light-text-muted dark:text-zeus-text-muted text-sm">Không tìm thấy GV khả dụng cho lịch này</p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Summary -->
        <div class="flex items-center gap-4 mb-3 text-sm text-light-text-muted dark:text-zeus-text-muted">
            <span>Tổng GV: <strong class="text-light-text dark:text-zeus-text" x-text="teacherCount"></strong></span>
            <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-green-500/30 border border-green-500/50"></span> Nhiều GV khả dụng
            </span>
            <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-amber-500/30 border border-amber-500/50"></span> Ít GV khả dụng
            </span>
            <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-gray-500/10 border border-gray-500/20"></span> Không có ca trống
            </span>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="text-center py-12">
                <span class="spinner-inline"></span>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu khả dụng...</p>
            </div>
        </template>

        <!-- Grid Table -->
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr>
                            <th class="sticky left-0 z-10 bg-light-card dark:bg-zeus-card px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium border border-light-border dark:border-zeus-border min-w-[80px]">
                                Giờ
                            </th>
                            <template x-for="day in days" :key="day.date">
                                <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium border border-light-border dark:border-zeus-border min-w-[100px]" x-text="day.label"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="slot in timeSlots" :key="slot">
                            <tr>
                                <td class="sticky left-0 z-10 bg-light-card dark:bg-zeus-card px-2 py-1.5 text-light-text dark:text-zeus-text font-mono font-medium border border-light-border dark:border-zeus-border" x-text="slot"></td>
                                <template x-for="day in days" :key="day.date + '-' + slot">
                                    <td class="px-1 py-1 text-center border border-light-border dark:border-zeus-border cursor-pointer transition-colors"
                                        :class="getCellClass(day.date, slot)"
                                        @click="showSlotDetail(day.date, slot, day.label)"
                                        :title="getCellTitle(day.date, slot)">
                                        <span class="font-bold" x-text="getCellValue(day.date, slot)"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Slot Detail Modal -->
        <div x-show="showDetailModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDetailModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-5xl w-full max-h-[80vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        👩‍🏫 GV khả dụng — <span x-text="detailDayLabel"></span> <span x-text="detailSlot"></span>
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_availability</span>, <span class="tooltip-table">tbl_order_lessons</span>, <span class="tooltip-table">tbl_group_classes</span>, <span class="tooltip-table">tbl_teacher_stats</span>, <span class="tooltip-table">tbl_user_settings</span><br><span class="tooltip-label">SQL Query</span><span class="tooltip-sql">-- Chỉ GV đủ qualified (tbl_teacher_stats):
-- testat_preference, testat_qualification,
-- testat_teachlang, testat_speaklang,
-- testat_availability đều &gt; 0

-- Chi tiết từng GV tại khung giờ:
SELECT a.avail_user_id as teacher_id,
  teacher.user_first_name as teacher_name,
  teacher_settings.user_trial_enabled,
  c.country_identifier,
  ol.ordles_id as lesson_id,
  ol.ordles_beneficiary_id as student_id,
  student.user_first_name, student.user_email,
  tl.tlang_identifier, ol.ordles_status
FROM tbl_availability a
INNER JOIN tbl_teacher_stats ts
  ON a.avail_user_id = ts.testat_user_id
INNER JOIN tbl_users teacher
  ON teacher.user_id = a.avail_user_id
INNER JOIN tbl_user_settings teacher_settings
  ON a.avail_user_id = teacher_settings.user_id
INNER JOIN tbl_countries c
  ON teacher.user_country_id = c.country_id
LEFT JOIN tbl_order_lessons ol
  ON a.avail_user_id = ol.ordles_teacher_id
  AND a.avail_starttime = ol.ordles_lesson_starttime
  AND ol.ordles_status IN (2,3)
LEFT JOIN tbl_users student
  ON student.user_id = ol.ordles_beneficiary_id
LEFT JOIN tbl_teach_languages tl
  ON ol.ordles_tlang_id = tl.tlang_id
WHERE ts.testat_preference &gt; 0
AND ts.testat_qualification &gt; 0
AND ts.testat_teachlang &gt; 0
AND ts.testat_speaklang &gt; 0
AND ts.testat_availability &gt; 0
AND a.avail_starttime = [slot_start_utc]
ORDER BY lesson_id</span></span></span>
                    </h3>
                    <button @click="showDetailModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(80vh-130px)]">
                    <template x-if="detailLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải...</p>
                        </div>
                    </template>
                    <template x-if="!detailLoading && detailTeachers.length > 0">
                        <div>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mb-3">
                                Tìm thấy <strong class="text-light-text dark:text-zeus-text" x-text="detailTeachers.filter(t => !t.is_busy).length"></strong> GV khả dụng /
                                <strong class="text-light-text dark:text-zeus-text" x-text="detailTeachers.length"></strong> GV có ca
                            </p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">ID</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                            <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Trial</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Quốc gia</th>
                                            <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Lesson</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Ngôn ngữ</th>
                                            <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                        <template x-for="(teacher, index) in detailTeachers" :key="teacher.id">
                                            <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light"
                                                :class="teacher.is_busy ? 'opacity-60' : ''">
                                                <td class="px-2 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                                <td class="px-2 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="teacher.id"></td>
                                                <td class="px-2 py-2">
                                                    <div class="text-light-text dark:text-zeus-text font-medium text-xs" x-text="teacher.name"></div>
                                                    <div class="text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="teacher.email"></div>
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <span x-show="teacher.teacher_trial == 1" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-600 dark:text-green-400">Có</span>
                                                    <span x-show="teacher.teacher_trial != 1" class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                                </td>
                                                <td class="px-2 py-2 text-xs text-light-text dark:text-zeus-text" x-text="teacher.country_identifier || teacher.country_code || '—'"></td>
                                                <td class="px-2 py-2 text-center">
                                                    <template x-if="teacher.lesson_id">
                                                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium" x-text="teacher.lesson_id"></span>
                                                    </template>
                                                    <template x-if="!teacher.lesson_id && teacher.is_busy">
                                                        <span class="text-xs text-orange-500">Lớp nhóm</span>
                                                    </template>
                                                    <template x-if="!teacher.lesson_id && !teacher.is_busy">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-600 dark:text-green-400">Trống</span>
                                                    </template>
                                                </td>
                                                <td class="px-2 py-2">
                                                    <template x-if="teacher.student_id">
                                                        <div>
                                                            <div class="text-xs text-light-text dark:text-zeus-text" x-text="teacher.student_full_name || '—'"></div>
                                                            <div class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="teacher.student_username || teacher.student_email || ''"></div>
                                                        </div>
                                                    </template>
                                                    <template x-if="!teacher.student_id">
                                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">—</span>
                                                    </template>
                                                </td>
                                                <td class="px-2 py-2 text-xs text-light-text dark:text-zeus-text" x-text="teacher.tlang_identifier || '—'"></td>
                                                <td class="px-2 py-2 text-center">
                                                    <template x-if="teacher.ordles_status == 2">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-600 dark:text-blue-400">Scheduled</span>
                                                    </template>
                                                    <template x-if="teacher.ordles_status == 3">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-600 dark:text-green-400">Completed</span>
                                                    </template>
                                                    <template x-if="!teacher.ordles_status && teacher.is_busy">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-500/20 text-orange-600 dark:text-orange-400">Bận</span>
                                                    </template>
                                                    <template x-if="!teacher.ordles_status && !teacher.is_busy">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">Rảnh</span>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                    <template x-if="!detailLoading && detailTeachers.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có GV khả dụng cho khung giờ này</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <button @click="showDetailModal = false" class="w-full py-2 px-4 bg-zeus-accent hover:bg-zeus-accent-light text-white rounded-lg transition font-medium">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Status Distribution & Trend -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leave Status Pie Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📊 Phân bố Trạng thái Đơn nghỉ
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span><br>GROUP BY tealv_status<br>Chờ duyệt, Tự động duyệt, Đã duyệt, Từ chối, Đã hủy</span></span>
            </h3>
            <canvas id="leaveStatusChart" height="200"></canvas>
        </div>

        <!-- Leave Request Trend Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📈 Xu hướng Đơn nghỉ (14 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span><br>GROUP BY ngày (tealv_addedon)<br>Số đơn nộp, duyệt, từ chối theo ngày</span></span>
            </h3>
            <canvas id="leaveTrendChart" height="200"></canvas>
        </div>
    </div>

    <!-- Leave Violations & Top Teachers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leave Violations -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                ⚠️ Vi phạm Nghỉ phép
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span> + <span class="tooltip-table">tbl_order_lessons</span><br>No-show: GV không tham gia buổi học đã lên lịch<br>Nộp trễ: đơn nghỉ nộp sau deadline<br>Vượt quota: vượt số ngày nghỉ cho phép</span></span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 {{ ($violationStats['total'] ?? 0) > 0 ? 'bg-red-500/10 border-red-500/30' : 'bg-green-500/10 border-green-500/30' }} rounded-lg border">
                    <p class="text-3xl font-bold {{ ($violationStats['total'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format($violationStats['total'] ?? 0) }}</p>
                    <p class="text-sm {{ ($violationStats['total'] ?? 0) > 0 ? 'text-red-600/80 dark:text-red-400/80' : 'text-green-600/80 dark:text-green-400/80' }}">Tổng vi phạm</p>
                </div>
                <div class="text-center p-4 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($violationStats['no_show'] ?? 0) }}</p>
                    <p class="text-sm text-orange-600/80 dark:text-orange-400/80">No-show</p>
                </div>
                <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($violationStats['late_submission'] ?? 0) }}</p>
                    <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Nộp đơn trễ</p>
                </div>
                <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($violationStats['exceeded_quota'] ?? 0) }}</p>
                    <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Vượt quota</p>
                </div>
            </div>
            @if($violationStats['this_month'] ?? 0 > 0)
            <div class="mt-4 p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-sm text-red-600 dark:text-red-400">
                    ⚠️ Có <strong>{{ $violationStats['this_month'] }}</strong> vi phạm trong tháng này
                </p>
            </div>
            @endif
        </div>

        <!-- Top Teachers by Leave -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🏠 Top GV Nghỉ nhiều nhất
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span><br>GROUP BY tealv_teacher_id<br>SUM(số ngày nghỉ) đã được duyệt</span></span>
            </h3>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-light-card dark:bg-zeus-card">
                        <tr class="border-b border-light-border dark:border-zeus-border">
                            <th class="text-left py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">#</th>
                            <th class="text-left py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Giáo viên</th>
                            <th class="text-right py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số ngày</th>
                            <th class="text-right py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số đơn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachersWithMostLeave as $index => $teacher)
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                            <td class="py-2 px-2 text-light-text dark:text-zeus-text">{{ $index + 1 }}</td>
                            <td class="py-2 px-2">
                                <div class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $teacher['name'] }}</div>
                                <div class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['email'] }}</div>
                            </td>
                            <td class="py-2 px-2 text-right">
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400">{{ $teacher['total_days'] }} ngày</span>
                            </td>
                            <td class="py-2 px-2 text-right text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['request_count'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Leave Requests -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📝 Đơn nghỉ Gần đây
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leaves</span><br>Sắp xếp theo tealv_addedon DESC<br>LIMIT 10 đơn mới nhất</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Giáo viên</th>
                        <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời gian nghỉ</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số ngày</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Loại nghỉ</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Lý do</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Trạng thái</th>
                        <th class="text-right py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Nộp lúc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLeaveRequests as $request)
                    <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                        <td class="py-3 px-2 text-sm font-medium text-light-text dark:text-zeus-text">{{ $request['teacher_name'] }}</td>
                        <td class="py-3 px-2 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $request['start_date'] }} - {{ $request['end_date'] }}</td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400">{{ $request['total_days'] }} ngày</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400">{{ $request['leave_type'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $request['reason_type'] === 'Bất khả kháng' ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-gray-500/10 text-gray-600 dark:text-gray-400' }}">{{ $request['reason_type'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($request['status_raw'] == 1) bg-amber-500/10 text-amber-600 dark:text-amber-400
                                @elseif($request['status_raw'] == 2 || $request['status_raw'] == 3) bg-green-500/10 text-green-600 dark:text-green-400
                                @elseif($request['status_raw'] == 4) bg-red-500/10 text-red-600 dark:text-red-400
                                @else bg-gray-500/10 text-gray-600 dark:text-gray-400
                                @endif">{{ $request['status'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-right text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $request['submitted_at'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-4 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có đơn nghỉ nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== LEAVE AFFECTED SESSIONS (Phase 101) ===== -->
    @if(isset($leaveAffectedStats))
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm" x-data="leaveAffectedSessionsSection()">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            📅 Buổi học bị ảnh hưởng do GV xin nghỉ
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_leave_request_sessions</span><br>JOIN <span class="tooltip-table">tbl_teacher_leave_requests</span><br>JOIN <span class="tooltip-table">tbl_order_lessons</span><br>Danh sách buổi học bị ảnh hưởng khi GV xin nghỉ phép<br>Loại thay thế: Dạy thay / Thay GV / Không thay</span></span>
        </h3>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-4">
            <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($leaveAffectedStats['total'] ?? 0) }}</p>
                <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng buổi</p>
            </div>
            <div class="text-center p-3 bg-indigo-500/10 rounded-lg border border-indigo-500/30">
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($leaveAffectedStats['lesson_sessions'] ?? 0) }}</p>
                <p class="text-xs text-indigo-600/80 dark:text-indigo-400/80">Buổi 1-1</p>
            </div>
            <div class="text-center p-3 bg-purple-500/10 rounded-lg border border-purple-500/30">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($leaveAffectedStats['class_sessions'] ?? 0) }}</p>
                <p class="text-xs text-purple-600/80 dark:text-purple-400/80">Lớp nhóm</p>
            </div>
            <div class="text-center p-3 bg-amber-500/10 rounded-lg border border-amber-500/30">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($leaveAffectedStats['need_replacement'] ?? 0) }}</p>
                <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Cần thay thế</p>
            </div>
            <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($leaveAffectedStats['substitute'] ?? 0) }}</p>
                <p class="text-xs text-green-600/80 dark:text-green-400/80">Dạy thay</p>
            </div>
            <div class="text-center p-3 bg-teal-500/10 rounded-lg border border-teal-500/30">
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ number_format($leaveAffectedStats['replace'] ?? 0) }}</p>
                <p class="text-xs text-teal-600/80 dark:text-teal-400/80">Thay GV mới</p>
            </div>
            <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($leaveAffectedStats['no_replacement'] ?? 0) }}</p>
                <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thay</p>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                    x-model="searchQuery"
                    @input.debounce.400ms="loadData(1)"
                    placeholder="Tìm theo tên/email GV hoặc HV..."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:ring-2 focus:ring-zeus-accent/50 focus:border-zeus-accent outline-none">
            </div>
            <select x-model="replacementFilter" @change="loadData(1)"
                class="px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 focus:border-zeus-accent outline-none">
                <option value="">Tất cả loại thay thế</option>
                <option value="1">Dạy thay (tạm thời)</option>
                <option value="2">Thay GV mới</option>
                <option value="null">Không thay</option>
            </select>
            <button @click="exportToExcel()" class="px-3 py-2 text-sm bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/20 transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xuất Excel
            </button>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="text-center py-8">
                <span class="spinner-inline"></span>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
            </div>
        </template>

        <!-- Data Table -->
        <template x-if="!loading">
            <div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày buổi học</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Giờ</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Thời lượng</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Loại buổi</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Loại thay thế</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">TT Đơn nghỉ</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Thời gian nghỉ</th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">TT Buổi học</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                            <template x-if="sessions.length === 0">
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                                        Chưa có dữ liệu buổi học bị ảnh hưởng
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in sessions" :key="item.id">
                                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                    <td class="px-3 py-2">
                                        <div class="text-light-text dark:text-zeus-text font-medium text-xs" x-text="item.teacher_name"></div>
                                        <div class="text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.teacher_email"></div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-light-text dark:text-zeus-text text-xs" x-text="item.learner_name"></div>
                                        <div class="text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.learner_email" x-show="item.learner_email"></div>
                                        <div class="text-light-text-muted dark:text-zeus-text-muted text-xs italic truncate max-w-[180px]" x-text="item.subject_name" x-show="item.subject_name" :title="item.subject_name"></div>
                                    </td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.session_date"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_time || '—'"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_duration || '—'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="item.session_type === 1 ? 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-400' : 'bg-purple-500/20 text-purple-600 dark:text-purple-400'"
                                            x-text="item.session_type_label"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="item.replacement_type === 1 ? 'bg-green-500/20 text-green-600 dark:text-green-400' : (item.replacement_type === 2 ? 'bg-teal-500/20 text-teal-600 dark:text-teal-400' : 'bg-red-500/20 text-red-600 dark:text-red-400')"
                                            x-text="item.replacement_type_label"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-amber-500/20 text-amber-600 dark:text-amber-400': item.leave_status_raw == 1,
                                                'bg-green-500/20 text-green-600 dark:text-green-400': item.leave_status_raw == 2 || item.leave_status_raw == 3,
                                                'bg-red-500/20 text-red-600 dark:text-red-400': item.leave_status_raw == 4,
                                                'bg-gray-500/20 text-gray-600 dark:text-gray-400': ![1,2,3,4].includes(item.leave_status_raw)
                                            }"
                                            x-text="item.leave_status"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.leave_period"></td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="item.lesson_status === 'Hoàn thành' ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400' : (item.lesson_status === 'Đã hủy' ? 'bg-red-500/20 text-red-600 dark:text-red-400' : 'bg-gray-500/20 text-gray-600 dark:text-gray-400')"
                                            x-text="item.lesson_status || '—'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4" x-show="pagination.last_page > 1">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Trang <strong x-text="pagination.current_page"></strong> / <strong x-text="pagination.last_page"></strong>
                        (Tổng: <strong x-text="pagination.total"></strong> buổi)
                    </span>
                    <div class="flex gap-1">
                        <button @click="loadData(1)" :disabled="pagination.current_page <= 1"
                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light'"
                            class="px-2 py-1 text-xs rounded border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text transition">
                            ««
                        </button>
                        <button @click="loadData(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light'"
                            class="px-2 py-1 text-xs rounded border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text transition">
                            «
                        </button>
                        <button @click="loadData(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light'"
                            class="px-2 py-1 text-xs rounded border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text transition">
                            »
                        </button>
                        <button @click="loadData(pagination.last_page)" :disabled="pagination.current_page >= pagination.last_page"
                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light'"
                            class="px-2 py-1 text-xs rounded border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text transition">
                            »»
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
    @endif

    <!-- Quiz Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📝 Bài kiểm tra / Quiz
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_quizzes</span><br>Thống kê các bài kiểm tra/quiz<br>quiz_status: draft, active, upcoming</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($quizStats['total'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Quiz</p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($quizStats['active'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đang hoạt động</p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($quizStats['draft'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Nháp</p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($quizStats['upcoming'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Sắp diễn ra</p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $quizStats['avg_duration'] ?? 0 }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Thời gian TB (giây)</p>
            </div>
            <div class="text-center p-4 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $quizStats['avg_questions'] ?? 0 }}</p>
                <p class="text-sm text-indigo-600/80 dark:text-indigo-400/80">Câu hỏi TB</p>
            </div>
        </div>
    </div>

    <!-- Quiz Attempts & Pass/Fail -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quiz Attempt Stats -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📊 Thống kê Lượt thi
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_quiz_attempts</span><br>Tổng lượt thi, tỷ lệ đạt/không đạt<br>qzatt_is_passed, qzatt_score</span></span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($quizAttemptStats['total'] ?? 0) }}</p>
                    <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng lượt thi</p>
                </div>
                <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($quizAttemptStats['passed'] ?? 0) }}</p>
                    <p class="text-sm text-green-600/80 dark:text-green-400/80">Đạt</p>
                </div>
                <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($quizAttemptStats['failed'] ?? 0) }}</p>
                    <p class="text-sm text-red-600/80 dark:text-red-400/80">Không đạt</p>
                </div>
                <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $quizAttemptStats['pass_rate'] ?? 0 }}%</p>
                    <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ đạt</p>
                </div>
                <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $quizAttemptStats['avg_score'] ?? 0 }}</p>
                    <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Điểm TB</p>
                </div>
                <div class="text-center p-4 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                    <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($quizAttemptStats['today'] ?? 0) }}</p>
                    <p class="text-sm text-cyan-600/80 dark:text-cyan-400/80">Hôm nay</p>
                </div>
            </div>
        </div>

        <!-- Quiz Pass/Fail Trend Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📈 Xu hướng Đạt/Không đạt (14 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_quiz_attempts</span><br>GROUP BY ngày (qzatt_addedon)<br>COUNT qzatt_is_passed = 1/0 theo ngày</span></span>
            </h3>
            <canvas id="quizPassFailChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Quiz Attempts -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📋 Lượt thi Gần đây
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_quiz_attempts</span><br>Sắp xếp theo qzatt_addedon DESC<br>LIMIT 10 lượt thi mới nhất</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Học viên</th>
                        <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Bài thi</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Điểm</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Kết quả</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời gian làm</th>
                        <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Trạng thái</th>
                        <th class="text-right py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Bắt đầu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentQuizAttempts as $attempt)
                    <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                        <td class="py-3 px-2 text-sm font-medium text-light-text dark:text-zeus-text">{{ $attempt['user_name'] }}</td>
                        <td class="py-3 px-2 text-sm text-light-text-muted dark:text-zeus-text-muted max-w-xs truncate">{{ $attempt['quiz_title'] }}</td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400">{{ $attempt['score'] }}/{{ $attempt['total_marks'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            @if($attempt['is_passed'])
                                <span class="px-2 py-1 text-xs rounded-full bg-green-500/10 text-green-600 dark:text-green-400">✓ Đạt</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-red-500/10 text-red-600 dark:text-red-400">✗ Không đạt</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $attempt['time_taken'] }}</td>
                        <td class="py-3 px-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400">{{ $attempt['status'] }}</span>
                        </td>
                        <td class="py-3 px-2 text-right text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $attempt['start_time'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-4 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có lượt thi nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ===== ACCEPTANCE CODES REFERENCE & STATISTICS (Phase 100) ===== -->
    @if(isset($acceptanceCodeStats))
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm" x-data="penalizedTeachersMgmtSection()">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            📋 Mã Acceptance Code (Mã kết quả ca học)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons_extras</span><br>Cột: ole_acceptance_code<br>Thành công = Code 12</span></span>
        </h3>
        
        <!-- Acceptance Code Statistics by Period -->
        <div x-data="{ activeCodeTab: 'today' }">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-md font-medium text-light-text dark:text-zeus-text">📊 Thống kê theo mã Acceptance Code</h4>
                <button @click="showCodeModal = true" class="px-3 py-1.5 text-sm bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-500/20 transition flex items-center gap-1">
                    📖 Tra cứu mã
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
            </div>
            
            <!-- Period Tabs -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button @click="activeCodeTab = 'today'" :class="activeCodeTab === 'today' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm nay</button>
                <button @click="activeCodeTab = 'yesterday'" :class="activeCodeTab === 'yesterday' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm qua</button>
                <button @click="activeCodeTab = 'day_before'" :class="activeCodeTab === 'day_before' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm kia</button>
                <button @click="activeCodeTab = 'week'" :class="activeCodeTab === 'week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tuần này</button>
                <button @click="activeCodeTab = 'last_week'" :class="activeCodeTab === 'last_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tuần trước</button>
                <button @click="activeCodeTab = 'month'" :class="activeCodeTab === 'month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tháng này</button>
            </div>
            
            @foreach(['today' => 'today', 'yesterday' => 'yesterday', 'day_before_yesterday' => 'day_before', 'this_week' => 'week', 'last_week' => 'last_week', 'this_month' => 'month'] as $dataKey => $tabKey)
            <div x-show="activeCodeTab === '{{ $tabKey }}'" {{ $tabKey !== 'today' ? 'style=display:none' : '' }}>
                @if(isset($acceptanceCodeStats[$dataKey]))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats[$dataKey]['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành</p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats[$dataKey]['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)</p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats[$dataKey]['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công</p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats[$dataKey]['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công</p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('{{ $dataKey }}')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats[$dataKey]['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats[$dataKey]['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
                            </p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Code</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Mô tả</th>
                                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Số lượng</th>
                                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                @foreach($acceptanceCodeStats[$dataKey]['codes'] ?? [] as $codeData)
                                    @if($codeData['count'] > 0)
                                    <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light {{ $codeData['is_success'] ? 'bg-green-500/5' : '' }}">
                                        <td class="px-3 py-2 font-mono font-bold {{ $codeData['is_success'] ? 'text-green-600 dark:text-green-400' : 'text-light-text dark:text-zeus-text' }}">{{ $codeData['code'] }}</td>
                                        <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">{{ $codeData['label'] }}</td>
                                        <td class="px-3 py-2 text-center font-bold {{ $codeData['is_success'] ? 'text-green-600 dark:text-green-400' : 'text-light-text dark:text-zeus-text' }}">{{ $codeData['count'] }}</td>
                                        <td class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted">{{ $codeData['rate'] }}%</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            @endforeach
        </div>
        
        <!-- Acceptance Code Lookup Modal -->
        <div x-show="showCodeModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCodeModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="showCodeModal = false">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        📖 Bảng tra cứu mã Acceptance Code
                    </h3>
                    <button @click="showCodeModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="mb-4 p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-sm text-blue-600 dark:text-blue-400">
                            <strong>💡 Lưu ý:</strong> Code 12 là mã duy nhất biểu thị ca học thành công. Tất cả các mã khác (1-11, 13-17) đều là không thành công.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium w-16">Code</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Ca học</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                @foreach($acceptanceCodesList as $code => $info)
                                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light {{ $info['is_success'] ? 'bg-green-500/5' : '' }}">
                                    <td class="px-3 py-2 text-center font-mono font-bold {{ $info['is_success'] ? 'text-green-600 dark:text-green-400' : 'text-light-text dark:text-zeus-text' }}">{{ $code }}</td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">{{ $info['session'] }}</td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">{{ $info['teacher'] }}</td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">{{ $info['student'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <button @click="showCodeModal = false" class="w-full py-2 px-4 bg-zeus-accent hover:bg-zeus-accent-light text-white rounded-lg transition font-medium">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Penalized Teachers Modal -->
        <div x-show="showPenalizedModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPenalizedModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-6xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        ⚠️ Danh sách GV bị phạt (<span x-text="penalizedPeriodLabels[penalizedPeriod] || penalizedPeriod"></span>)
                    </h3>
                    <button @click="showPenalizedModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="penalizedLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </template>
                    <template x-if="!penalizedLoading && penalizedTeachers.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email GV</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Giờ</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Thời lượng</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Mã lỗi</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Mô tả lỗi</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(item, index) in penalizedTeachers" :key="item.lesson_id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-medium" x-text="item.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.teacher_email"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="item.student_name"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_date"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_time"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.duration + ' phút'"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-orange-500/20 text-orange-600 dark:text-orange-400" x-text="item.acceptance_code"></span>
                                            </td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.acceptance_label"></td>
                                            <td class="px-3 py-2 text-center text-xs">
                                                <span :class="item.session_status === 'Hoàn thành' ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400' : 'bg-gray-500/20 text-gray-600 dark:text-gray-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" x-text="item.session_status"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!penalizedLoading && penalizedTeachers.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="penalizedTeachers.length"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportPenalizedToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showPenalizedModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ===== STUDENT TEACHER CHANGES (Phase 204) ===== -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm" x-data="studentTeacherChangesSection()">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            🔄 Học viên SPW bị thay đổi Giáo viên
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span>, <span class="tooltip-table">tbl_users</span>, <span class="tooltip-table">tbl_countries</span>, <span class="tooltip-table">tbl_teacher_leave_requests</span>, <span class="tooltip-table">tbl_teacher_leave_request_sessions</span><br>Thống kê HV SPW có GV thay đổi trong khoảng thời gian.<br>Chỉ xét học sinh SPW (SPEAKWELL_NO_TRIAL_SUBJECT_IDS, không bao gồm môn trial 533) và buổi học đã hoàn thành (ordles_status = 3).<br><strong>Phase 213:</strong> Phân tách theo từng đơn hàng (khóa học). Đổi GV chỉ tính trong cùng 1 đơn hàng, tránh nhầm lẫn khi HV học nhiều khóa cùng lúc.<br><strong>Phase 215/216:</strong> Mỗi lần đổi GV chỉ tính là 1 sự kiện duy nhất, gộp lại theo (HV, GV mới, tháng). Nếu cùng HV đổi sang cùng GV mới trong cùng tháng (dù từ GV cũ khác nhau ở các đơn khác nhau) thì chỉ tính 1 sự kiện. Lý do &quot;GV nghỉ&quot; chỉ được gắn nếu thời gian nghỉ trùng với buổi học.<br>Phân loại GV theo quốc tịch: Vietnamese, Philippines, Native 1 (US/GB/CA/AU), Native 2 (NZ/IE/ZA).<br>Loại buổi trial (533). Lọc theo quốc tịch GV.<br><span class="tooltip-sql">WITH lesson_sequence AS (
  SELECT ordles_id, ordles_beneficiary_id AS student_id,
    ordles_teacher_id, ordles_lesson_starttime, ordles_order_id,
    LAG(ordles_teacher_id) OVER (
      PARTITION BY ordles_beneficiary_id, ordles_order_id
      ORDER BY ordles_lesson_starttime, ordles_id
    ) AS prev_teacher_id
  FROM tbl_order_lessons WHERE ordles_status = 3
    AND ordles_tlang_id IN (SPW IDs, trừ 533) AND ... BETWEEN ? AND ?
), change_events AS (
  SELECT * FROM lesson_sequence
  WHERE prev_teacher_id IS NOT NULL AND ordles_teacher_id != prev_teacher_id
), change_with_leave AS (
  -- Xác định lý do thay đổi: GV nghỉ phép hay PH yêu cầu đổi
  -- Phase 215: kiểm tra ngày nghỉ phải trùng với ngày buổi học
  ...
), change_events_dedup AS (
  -- Phase 216: Gộp sự kiện trùng lặp theo (student, GV mới, tháng)
  -- Bỏ prev_teacher_id ra khỏi GROUP BY để tránh đếm trùng khi
  -- cùng HV đổi sang cùng GV mới từ GV cũ khác nhau ở các đơn khác nhau
  SELECT student_id, MAX(is_leave_related) AS is_leave_related
  FROM change_with_leave
  GROUP BY student_id, ordles_teacher_id, DATE_FORMAT(...)
)
SELECT student_id, COUNT(*) AS change_count,
  SUM(is_leave_related) AS leave_change_count ...
FROM change_events_dedup GROUP BY student_id</span></span></span>
        </h3>

        <!-- Date Range Filter -->
        <div class="flex flex-wrap items-end gap-3 mb-4 p-3 rounded-lg bg-light-card-alt/50 dark:bg-zeus-card-light/50 border border-light-border/50 dark:border-zeus-border/50">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">📅 Từ ngày</label>
                <input type="date" x-model="dateFrom" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">📅 Đến ngày</label>
                <input type="date" x-model="dateTo" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">🔎 Tìm HV (ID / Tên / SĐT / Email)</label>
                <input type="text" x-model="search" @keydown.enter="currentPage = 1; loadData()" placeholder="Nhập ID, tên, SĐT hoặc email..." class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
            </div>
            <!-- Phase 208: Teacher nationality filter -->
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">🌍 Quốc tịch GV</label>
                <select x-model="teacherNationality" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="Vietnamese">🇻🇳 Vietnamese</option>
                    <option value="Philippines">🇵🇭 Philippines</option>
                    <option value="Native 1">🌍 Native 1 (US/GB/CA/AU)</option>
                    <option value="Native 2">🌍 Native 2 (NZ/IE/ZA)</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button @click="currentPage = 1; loadData()" :disabled="loading || !dateFrom || !dateTo" class="px-4 py-2 text-sm bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-500/30 transition disabled:opacity-40 inline-flex items-center gap-1">
                    <span x-show="!loading">🔍 Xem</span>
                    <span x-show="loading" class="inline-flex items-center gap-1"><span class="spinner-inline spinner-sm"></span> Đang tải...</span>
                </button>
                <button @click="exportToCSV()" :disabled="exporting || students.length === 0" class="px-4 py-2 text-sm bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition disabled:opacity-40 inline-flex items-center gap-1">
                    <span x-show="!exporting">📥 Xuất Excel</span>
                    <span x-show="exporting" class="inline-flex items-center gap-1"><span class="spinner-inline spinner-sm"></span> Đang xuất...</span>
                </button>
            </div>
        </div>

        <!-- Summary -->
        <div x-show="loaded && !loading" class="mb-4">
            <div class="flex items-center gap-4 text-sm text-light-text-muted dark:text-zeus-text-muted">
                <span>📊 Tổng: <strong class="text-light-text dark:text-zeus-text" x-text="total"></strong> HV bị thay đổi GV</span>
                <span x-show="dateFrom && dateTo" x-text="'Giai đoạn: ' + formatDate(dateFrom) + ' → ' + formatDate(dateTo)"></span>
            </div>
        </div>

        <!-- Student Table -->
        <div x-show="loaded && !loading" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">STT</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_id')">
                            ID HV
                            <span x-show="sortBy === 'student_id'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_name')">
                            Họ tên
                            <span x-show="sortBy === 'student_name'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Email</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">SĐT</th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('change_count')">
                            Số lần đổi GV
                            <span class="info-tooltip" style="font-size:10px">ⓘ<span class="tooltip-content">Số <strong>sự kiện</strong> thay đổi giáo viên thực tế.<br>Gộp theo (HV, GV mới, tháng): nếu cùng HV đổi sang cùng GV mới trong cùng tháng (dù từ GV cũ khác nhau ở các đơn khác nhau) thì chỉ tính 1 sự kiện.<br>Đổi GV = do GV nghỉ phép hoặc PH yêu cầu đổi.</span></span>
                            <span x-show="sortBy === 'change_count'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('leave_change_count')">
                            Do GV nghỉ
                            <span class="info-tooltip" style="font-size:10px">ⓘ<span class="tooltip-content">Số lần đổi GV do giáo viên trước đó <strong>xin nghỉ phép</strong> (có đơn nghỉ phép được duyệt).<br>Hệ thống kiểm tra qua bảng tbl_teacher_leave_request_sessions hoặc tbl_teacher_leave_requests trong thời gian buổi học.</span></span>
                            <span x-show="sortBy === 'leave_change_count'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('distinct_teachers')">
                            Số GV
                            <span x-show="sortBy === 'distinct_teachers'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('total_lessons')">
                            Tổng buổi
                            <span x-show="sortBy === 'total_lessons'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">
                            Khóa học
                            <span class="info-tooltip" style="font-size:10px">ⓘ<span class="tooltip-content">Tên các khóa học SPW mà HV đang theo học.<br>Nguồn: tbl_teach_languages.tlang_identifier</span></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('order_count')">
                            Số đơn hàng
                            <span class="info-tooltip" style="font-size:10px">ⓘ<span class="tooltip-content">Số đơn hàng SPW riêng biệt mà HV đã mua.<br>Một HV có thể mua nhiều đơn hàng với lịch học khác nhau.</span></span>
                            <span x-show="sortBy === 'order_count'" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                        </th>
                        <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(student, idx) in students" :key="student.student_id">
                        <tr class="border-b border-light-border/30 dark:border-zeus-border/30 hover:bg-light-card-alt/50 dark:hover:bg-zeus-card-light/50 transition">
                            <td class="py-2 px-2 text-light-text-muted dark:text-zeus-text-muted" x-text="(currentPage - 1) * perPage + idx + 1"></td>
                            <td class="py-2 px-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="student.student_id"></td>
                            <td class="py-2 px-2 text-light-text dark:text-zeus-text font-medium" x-text="student.student_name"></td>
                            <td class="py-2 px-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="student.email"></td>
                            <td class="py-2 px-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="student.phone || '—'"></td>
                            <td class="py-2 px-2 text-right">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                    :class="student.change_count >= 5 ? 'bg-red-500/20 text-red-600 dark:text-red-400' : (student.change_count >= 3 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-blue-500/20 text-blue-600 dark:text-blue-400')"
                                    x-text="student.change_count"></span>
                            </td>
                            <td class="py-2 px-2 text-right">
                                <template x-if="student.leave_change_count > 0">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-500/20 text-purple-600 dark:text-purple-400" x-text="student.leave_change_count"></span>
                                </template>
                                <template x-if="!student.leave_change_count || student.leave_change_count === 0">
                                    <span class="text-light-text-muted dark:text-zeus-text-muted">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-2 text-right text-light-text dark:text-zeus-text" x-text="student.distinct_teachers"></td>
                            <td class="py-2 px-2 text-right text-light-text dark:text-zeus-text" x-text="student.total_lessons"></td>
                            <td class="py-2 px-2 text-xs text-light-text dark:text-zeus-text max-w-[200px]">
                                <span x-text="student.course_names || '—'" :title="student.course_names || ''" class="block truncate"></span>
                            </td>
                            <td class="py-2 px-2 text-right">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                    :class="student.order_count >= 3 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : (student.order_count >= 2 ? 'bg-teal-500/20 text-teal-600 dark:text-teal-400' : 'bg-gray-500/20 text-gray-600 dark:text-gray-400')"
                                    x-text="student.order_count || 0"></span>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <button @click="showDetail(student)" class="px-2 py-1 text-xs bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded hover:bg-indigo-500/20 transition">
                                    👁️ Xem
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="students.length === 0 && loaded">
                        <tr>
                            <td colspan="12" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                                <span x-show="!dateFrom || !dateTo">Vui lòng chọn khoảng thời gian và nhấn "Xem"</span>
                                <span x-show="dateFrom && dateTo">Không tìm thấy HV nào bị thay đổi GV trong khoảng thời gian này</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
            <span class="spinner-inline spinner-sm"></span> Đang tải dữ liệu...
        </div>

        <!-- Pagination -->
        <div x-show="totalPages > 1 && !loading" class="flex items-center justify-between mt-4 text-xs text-light-text-muted dark:text-zeus-text-muted">
            <span x-text="'Hiển thị ' + ((currentPage - 1) * perPage + 1) + '-' + Math.min(currentPage * perPage, total) + ' / ' + total + ' HV'"></span>
            <div class="flex items-center gap-1">
                <button @click="currentPage = 1; loadData()" :disabled="currentPage <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏮</button>
                <button @click="currentPage--; loadData()" :disabled="currentPage <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">← Trước</button>
                <span class="px-2" x-text="'Trang ' + currentPage + '/' + totalPages"></span>
                <button @click="currentPage++; loadData()" :disabled="currentPage >= totalPages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">Sau →</button>
                <button @click="currentPage = totalPages; loadData()" :disabled="currentPage >= totalPages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏭</button>
                <select x-model="perPage" @change="currentPage = 1; loadData()" class="text-xs rounded border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light px-2 py-1">
                    <option value="25">25/trang</option>
                    <option value="50">50/trang</option>
                    <option value="100">100/trang</option>
                </select>
            </div>
        </div>

        <!-- Phase 205: Charts Section -->
        <div x-show="loaded && !loading && students.length > 0" class="mt-6">
            <h4 class="text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                📊 Biểu đồ thay đổi GV
                <span x-show="chartLoading" class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted"><span class="spinner-inline spinner-sm"></span> Đang tải...</span>
            </h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                <!-- Distribution Chart -->
                <div class="bg-light-card-alt/30 dark:bg-zeus-card-light/30 rounded-lg p-4 border border-light-border/50 dark:border-zeus-border/50">
                    <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-3">📊 Phân bố số lần đổi GV</h5>
                    <div class="relative" style="height: 280px;">
                        <canvas x-ref="distributionChart"></canvas>
                    </div>
                </div>
                <!-- Top Students Chart -->
                <div class="bg-light-card-alt/30 dark:bg-zeus-card-light/30 rounded-lg p-4 border border-light-border/50 dark:border-zeus-border/50">
                    <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-3">🏆 Top HV bị đổi GV nhiều nhất</h5>
                    <div class="relative" style="height: 280px;">
                        <canvas x-ref="topStudentsChart"></canvas>
                    </div>
                </div>
                <!-- Phase 206: Reason Breakdown Pie Chart -->
                <div class="bg-light-card-alt/30 dark:bg-zeus-card-light/30 rounded-lg p-4 border border-light-border/50 dark:border-zeus-border/50">
                    <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-3">📋 Lý do đổi GV (GV nghỉ / PH đổi)</h5>
                    <div class="relative" style="height: 280px;">
                        <canvas x-ref="reasonBreakdownChart"></canvas>
                    </div>
                </div>
                <!-- Phase 207: Teacher Nationality Chart -->
                <div class="bg-light-card-alt/30 dark:bg-zeus-card-light/30 rounded-lg p-4 border border-light-border/50 dark:border-zeus-border/50">
                    <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-3">🌍 Tỷ lệ đổi GV: Việt Nam / Nước ngoài</h5>
                    <div class="relative" style="height: 280px;">
                        <canvas x-ref="nationalityChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Trend Chart -->
            <div class="bg-light-card-alt/30 dark:bg-zeus-card-light/30 rounded-lg p-4 border border-light-border/50 dark:border-zeus-border/50">
                <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-3">📈 Xu hướng thay đổi GV theo tháng (GV nghỉ / PH yêu cầu đổi)</h5>
                <div class="relative" style="height: 280px;">
                    <canvas x-ref="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @keydown.escape.window="showDetailModal = false">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl border border-light-border dark:border-zeus-border shadow-2xl w-full max-w-4xl max-h-[80vh] overflow-hidden flex flex-col mx-4" @click.outside="showDetailModal = false">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border">
                    <h4 class="text-base font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        🔄 Chi tiết thay đổi GV -
                        <span x-text="detailStudent?.student_name || ''"></span>
                        <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(ID: ' + (detailStudent?.student_id || '') + ')'"></span>
                    </h4>
                    <button @click="showDetailModal = false" class="p-1 rounded hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div x-show="detailLoading" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                        <span class="spinner-inline spinner-sm"></span> Đang tải chi tiết...
                    </div>
                    <div x-show="!detailLoading && detailError" class="py-8 text-center text-red-500">
                        <p class="text-sm" x-text="detailError"></p>
                    </div>
                    <div x-show="!detailLoading && !detailError && detailData && (detailData.lessons || []).length === 0" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                        <p class="text-sm">Không tìm thấy buổi học SPW nào cho HV này trong khoảng thời gian đã chọn.</p>
                        <p class="text-xs mt-1">Lưu ý: Chỉ xét các môn SPW (không bao gồm trial). Buổi học với trạng thái Scheduled, Completed hoặc Cancelled đều được hiển thị.</p>
                    </div>
                    <div x-show="!detailLoading && !detailError">
                        <!-- Phase 206/207: Enhanced summary with leave breakdown + nationality -->
                        <div class="mb-3 text-sm text-light-text-muted dark:text-zeus-text-muted flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span>📊 Tổng buổi: <strong class="text-light-text dark:text-zeus-text" x-text="detailData?.total_lessons || 0"></strong></span>
                            <span>⚠️ Số lần đổi GV: <strong class="text-red-600 dark:text-red-400" x-text="detailData?.change_count || 0"></strong></span>
                            <span>📋 Do GV nghỉ: <strong class="text-purple-600 dark:text-purple-400" x-text="detailData?.leave_change_count || 0"></strong></span>
                            <span>👨‍👩‍👧 Do PH yêu cầu đổi: <strong class="text-amber-600 dark:text-amber-400" x-text="detailData?.other_change_count || 0"></strong></span>
                        </div>
                        <!-- Phase 207: Teacher nationality breakdown -->
                        <template x-if="detailData?.nationality_breakdown && Object.keys(detailData.nationality_breakdown).length > 0">
                            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                                <span class="text-light-text-muted dark:text-zeus-text-muted font-medium">🌍 Quốc tịch GV:</span>
                                <template x-for="(count, type) in (detailData?.nationality_breakdown || {})" :key="type">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium"
                                        :class="{
                                            'bg-red-500/20 text-red-600 dark:text-red-400': type === 'Vietnamese',
                                            'bg-blue-500/20 text-blue-600 dark:text-blue-400': type === 'Philippines',
                                            'bg-purple-500/20 text-purple-600 dark:text-purple-400': type === 'Native 1',
                                            'bg-teal-500/20 text-teal-600 dark:text-teal-400': type === 'Native 2',
                                            'bg-gray-500/20 text-gray-600 dark:text-gray-400': type === 'Khác'
                                        }"
                                        x-text="type + ': ' + count + ' GV'"></span>
                                </template>
                            </div>
                        </template>
                        <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-light-border dark:border-zeus-border">
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">STT</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Khóa học</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Lịch học</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thời gian</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Giáo viên</th>
                                    <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Quốc tịch</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Trạng thái</th>
                                    <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Đổi GV</th>
                                    <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Lý do thay đổi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(lesson, idx) in (detailData?.lessons || [])" :key="lesson.lesson_id">
                                    <tr class="border-b border-light-border/30 dark:border-zeus-border/30"
                                        :class="{
                                            'bg-purple-500/5 dark:bg-purple-500/10': lesson.is_change && lesson.leave_info,
                                            'bg-red-500/5 dark:bg-red-500/10': lesson.is_change && !lesson.leave_info,
                                            'bg-orange-500/5 dark:bg-orange-500/8': lesson.is_change_continuation
                                        }">
                                        <td class="py-1.5 px-2 text-light-text-muted dark:text-zeus-text-muted" x-text="idx + 1"></td>
                                        <td class="py-1.5 px-2 text-xs">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 max-w-[150px] truncate" x-text="lesson.course_name || '—'" :title="lesson.course_name || ''"></span>
                                        </td>
                                        <td class="py-1.5 px-2 text-xs whitespace-nowrap">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-500/10 text-indigo-600 dark:text-indigo-400" x-text="lesson.schedule_slot || '—'"></span>
                                        </td>
                                        <td class="py-1.5 px-2 text-light-text dark:text-zeus-text text-xs whitespace-nowrap" x-text="lesson.lesson_time"></td>
                                        <td class="py-1.5 px-2">
                                            <div class="text-light-text dark:text-zeus-text font-medium" x-text="lesson.teacher_name"></div>
                                            <div x-show="(lesson.is_change || lesson.is_change_continuation) && lesson.prev_teacher_name" class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-0.5">
                                                ← GV trước: <span class="font-medium text-amber-600 dark:text-amber-400" x-text="lesson.prev_teacher_name"></span>
                                                <span x-show="lesson.prev_teacher_type" class="ml-1 text-xs opacity-70" x-text="'(' + lesson.prev_teacher_type + ')'"></span>
                                            </div>
                                        </td>
                                        <td class="py-1.5 px-2 text-center">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-red-500/20 text-red-600 dark:text-red-400': lesson.teacher_type === 'Vietnamese',
                                                    'bg-blue-500/20 text-blue-600 dark:text-blue-400': lesson.teacher_type === 'Philippines',
                                                    'bg-purple-500/20 text-purple-600 dark:text-purple-400': lesson.teacher_type === 'Native 1',
                                                    'bg-teal-500/20 text-teal-600 dark:text-teal-400': lesson.teacher_type === 'Native 2',
                                                    'bg-gray-500/20 text-gray-600 dark:text-gray-400': !['Vietnamese','Philippines','Native 1','Native 2'].includes(lesson.teacher_type)
                                                }"
                                                x-text="lesson.teacher_type || 'N/A'"></span>
                                        </td>
                                        <td class="py-1.5 px-2">
                                            <span class="px-1.5 py-0.5 rounded text-xs"
                                                :class="{
                                                    'bg-green-500/20 text-green-600 dark:text-green-400': lesson.status === 3,
                                                    'bg-blue-500/20 text-blue-600 dark:text-blue-400': lesson.status === 2,
                                                    'bg-red-500/20 text-red-600 dark:text-red-400': lesson.status === 4,
                                                    'bg-gray-500/20 text-gray-600 dark:text-gray-400': ![2,3,4].includes(lesson.status)
                                                }"
                                                x-text="lesson.status_label"></span>
                                        </td>
                                        <td class="py-1.5 px-2 text-center">
                                            <span x-show="lesson.is_change && lesson.leave_info" class="text-purple-500 font-bold" title="Đổi do GV nghỉ phép">📋 GV nghỉ</span>
                                            <span x-show="lesson.is_change && !lesson.leave_info" class="text-amber-500 font-bold" title="Do PH yêu cầu đổi">👨‍👩‍👧 PH đổi</span>
                                            <span x-show="lesson.is_change_continuation" class="text-orange-500 text-xs" title="Thuộc cùng sự kiện đổi GV (không tính thêm)">🔄 Cùng sự kiện</span>
                                            <span x-show="!lesson.is_change && !lesson.is_change_continuation" class="text-light-text-muted dark:text-zeus-text-muted">—</span>
                                        </td>
                                        <td class="py-1.5 px-2 text-xs">
                                            <template x-if="lesson.is_change && lesson.leave_info">
                                                <div class="space-y-0.5">
                                                    <div class="text-purple-600 dark:text-purple-400 font-medium">
                                                        📋 GV nghỉ phép
                                                        <span x-show="lesson.leave_info.replacement_type" class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + lesson.leave_info.replacement_type + ')'"></span>
                                                    </div>
                                                    <div x-show="lesson.leave_info.leave_reason_type" class="text-light-text-muted dark:text-zeus-text-muted">
                                                        Loại: <span x-text="lesson.leave_info.leave_reason_type"></span>
                                                    </div>
                                                    <div x-show="lesson.leave_info.leave_period" class="text-light-text-muted dark:text-zeus-text-muted">
                                                        Thời gian nghỉ: <span x-text="lesson.leave_info.leave_period"></span>
                                                    </div>
                                                    <div x-show="lesson.leave_info.leave_reason" class="text-light-text-muted dark:text-zeus-text-muted italic truncate max-w-[200px]" :title="lesson.leave_info.leave_reason" x-text="'\"' + lesson.leave_info.leave_reason + '\"'"></div>
                                                </div>
                                            </template>
                                            <template x-if="lesson.is_change && !lesson.leave_info">
                                                <span class="text-amber-600 dark:text-amber-400">👨‍👩‍👧 Do PH yêu cầu đổi</span>
                                            </template>
                                            <template x-if="lesson.is_change_continuation">
                                                <span class="text-orange-500 dark:text-orange-400 text-xs">🔄 Thuộc cùng sự kiện đổi GV<br>(không tính là lần đổi mới)</span>
                                            </template>
                                            <template x-if="!lesson.is_change && !lesson.is_change_continuation">
                                                <span class="text-light-text-muted dark:text-zeus-text-muted">—</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between p-4 border-t border-light-border dark:border-zeus-border">
                    <button @click="exportDetailToCSV()" :disabled="detailExporting || !detailData || !(detailData.lessons || []).length" class="px-4 py-2 text-sm bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition disabled:opacity-40 inline-flex items-center gap-1 font-medium">
                        <template x-if="detailExporting"><span class="spinner-inline spinner-sm"></span></template>
                        <template x-if="!detailExporting"><span>📥</span></template>
                        Export CSV
                    </button>
                    <button @click="showDetailModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Phase 138: Always show charts (no more OMO conditional)
const activeProgram = '{{ $activeProgram ?? "all" }}';

{
    // Get theme-aware colors
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#9CA3AF' : '#64748B';
    const gridColor = isDarkMode ? '#2A2E35' : '#E2E8F0';

    // Dark theme chart defaults
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // Leave Status Pie Chart
    const leaveStatusEl = document.getElementById('leaveStatusChart');
    if (leaveStatusEl) {
        const leaveStatusCtx = leaveStatusEl.getContext('2d');
        new Chart(leaveStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chờ duyệt', 'Tự động duyệt', 'Đã duyệt', 'Từ chối', 'Đã hủy'],
                datasets: [{
                    data: [
                        {{ $leaveByStatus['pending'] ?? 0 }},
                        {{ $leaveByStatus['auto_approved'] ?? 0 }},
                        {{ $leaveByStatus['approved'] ?? 0 }},
                        {{ $leaveByStatus['rejected'] ?? 0 }},
                        {{ $leaveByStatus['canceled'] ?? 0 }}
                    ],
                    backgroundColor: [
                        '#f59e0b',
                        '#10b981',
                        '#22c55e',
                        '#ef4444',
                        '#6b7280'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Leave Trend Chart
    const leaveTrendEl = document.getElementById('leaveTrendChart');
    if (leaveTrendEl) {
        const leaveTrendCtx = leaveTrendEl.getContext('2d');
        new Chart(leaveTrendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($leaveTrend['labels']) !!},
                datasets: [
                    {
                        label: 'Nộp đơn',
                        data: {!! json_encode($leaveTrend['datasets']['submitted']) !!},
                        backgroundColor: '#3B82F6',
                        borderRadius: 4
                    },
                    {
                        label: 'Đã duyệt',
                        data: {!! json_encode($leaveTrend['datasets']['approved']) !!},
                        backgroundColor: '#22c55e',
                        borderRadius: 4
                    },
                    {
                        label: 'Từ chối',
                        data: {!! json_encode($leaveTrend['datasets']['rejected']) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // Quiz Pass/Fail Trend Chart
    const quizPassFailEl = document.getElementById('quizPassFailChart');
    if (quizPassFailEl) {
        const quizPassFailCtx = quizPassFailEl.getContext('2d');
        new Chart(quizPassFailCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($quizPassFailChart['labels']) !!},
                datasets: [
                    {
                        label: 'Đạt',
                        data: {!! json_encode($quizPassFailChart['datasets']['passed']) !!},
                        backgroundColor: '#22c55e',
                        borderRadius: 4
                    },
                    {
                        label: 'Không đạt',
                        data: {!! json_encode($quizPassFailChart['datasets']['failed']) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }
} // End of chart initialization block

// Leave Affected Sessions Section (Phase 101)
function leaveAffectedSessionsSection() {
    return {
        loading: false,
        sessions: [],
        searchQuery: '',
        replacementFilter: '',
        pagination: {
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1
        },

        init() {
            this.loadData(1);
        },

        async loadData(page) {
            this.loading = true;
            FilterProgress.show();
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', this.pagination.per_page);
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.replacementFilter) params.set('replacement_type', this.replacementFilter);

            try {
                const response = await fetch(`/api/leave-affected-sessions?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.sessions = result.data || [];
                    this.pagination = result.pagination || this.pagination;
                }
            } catch (error) {
                console.error('Error fetching leave affected sessions:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        async exportToExcel() {
            // Fetch ALL data for export (no pagination)
            const params = new URLSearchParams();
            params.set('page', 1);
            params.set('per_page', 10000);
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.replacementFilter) params.set('replacement_type', this.replacementFilter);

            try {
                const response = await fetch(`/api/leave-affected-sessions?${params.toString()}`);
                const result = await response.json();

                if (!result.success || !result.data || result.data.length === 0) {
                    alert('Không có dữ liệu để xuất!');
                    return;
                }

                const allData = result.data;
                const headers = ['STT', 'Giáo viên', 'Email GV', 'Học viên', 'Email HV', 'Môn học', 'Ngày buổi học', 'Giờ', 'Thời lượng', 'Loại buổi', 'Loại thay thế', 'TT Đơn nghỉ', 'Thời gian nghỉ', 'TT Buổi học'];
                const rows = allData.map((item, index) => [
                    index + 1,
                    item.teacher_name || '',
                    item.teacher_email || '',
                    item.learner_name || '',
                    item.learner_email || '',
                    item.subject_name || '',
                    item.session_date || '',
                    item.lesson_time || '',
                    item.lesson_duration || '',
                    item.session_type_label || '',
                    item.replacement_type_label || '',
                    item.leave_status || '',
                    item.leave_period || '',
                    item.lesson_status || ''
                ]);

                const BOM = '\uFEFF';
                let csvContent = headers.join(',') + '\n';
                rows.forEach(row => {
                    const escapedRow = row.map(cell => {
                        const cellStr = String(cell);
                        if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                            return '"' + cellStr.replace(/"/g, '""') + '"';
                        }
                        return cellStr;
                    });
                    csvContent += escapedRow.join(',') + '\n';
                });

                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);

                link.setAttribute('href', url);
                link.setAttribute('download', `Buoi_hoc_bi_anh_huong_${new Date().toISOString().slice(0, 10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting leave affected sessions:', error);
                alert('Có lỗi khi xuất file!');
            }
        }
    }
}

// Penalized Teachers Section for Teacher Management page
function penalizedTeachersMgmtSection() {
    return {
        showCodeModal: false,
        showPenalizedModal: false,
        penalizedLoading: false,
        penalizedTeachers: [],
        penalizedPeriod: 'today',
        
        penalizedPeriodLabels: {
            'today': 'Hôm nay',
            'yesterday': 'Hôm qua',
            'day_before_yesterday': 'Hôm kia',
            'this_week': 'Tuần này',
            'last_week': 'Tuần trước',
            'this_month': 'Tháng này',
            'last_month': 'Tháng trước'
        },
        
        async openPenalizedModal(period) {
            this.penalizedPeriod = period;
            this.showPenalizedModal = true;
            this.penalizedLoading = true;
            this.penalizedTeachers = [];
            
            try {
                const response = await fetch(`/api/penalized-teachers-details?period=${period}`);
                const result = await response.json();
                
                if (result.success) {
                    this.penalizedTeachers = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching penalized teachers details:', error);
            } finally {
                this.penalizedLoading = false;
            }
        },
        
        exportPenalizedToExcel() {
            if (this.penalizedTeachers.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.penalizedPeriodLabels[this.penalizedPeriod] || this.penalizedPeriod;
            const headers = ['STT', 'Giáo viên', 'Email GV', 'Học viên', 'Ngày', 'Giờ', 'Thời lượng (phút)', 'Mã lỗi', 'Mô tả lỗi', 'Trạng thái'];
            const rows = this.penalizedTeachers.map((item, index) => [
                index + 1,
                item.teacher_name || '',
                item.teacher_email || '',
                item.student_name || '',
                item.lesson_date || '',
                item.lesson_time || '',
                item.duration || '',
                item.acceptance_code || '',
                item.acceptance_label || '',
                item.session_status || ''
            ]);
            
            const BOM = '\uFEFF';
            let csvContent = headers.join(',') + '\n';
            rows.forEach(row => {
                const escapedRow = row.map(cell => {
                    const cellStr = String(cell);
                    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                        return '"' + cellStr.replace(/"/g, '""') + '"';
                    }
                    return cellStr;
                });
                csvContent += escapedRow.join(',') + '\n';
            });
            
            const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `GV_bi_phat_${periodLabel.replace(/\s/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
}

// Phase 113: Teacher Availability Grid
function teacherAvailabilityGrid() {
    return {
        loading: false,
        weekStart: '',
        weekLabel: '',
        teacherSearch: '',
        trialFilter: 'all',
        teacherType: '',
        slotMode: 'odd', // 'odd' (default) or 'even'
        teacherCount: 0,
        days: [],
        timeSlots: [],
        grid: {},
        teacherDetail: {},
        teachers: [],

        // Time range filter (even mode only)
        timeFrom: '06',
        timeTo: '23',
        allHours: ['06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'],

        // Detail modal
        showDetailModal: false,
        detailLoading: false,
        detailTeachers: [],
        detailDayLabel: '',
        detailSlot: '',

        // Schedule search (multi-slot)
        scheduleSlots: [{ time: '', day: '' }],
        scheduleSearchDate: '',
        scheduleSearchResults: null,
        scheduleSearchLoading: false,
        scheduleDayOptions: [
            { value: '1', label: 'T2' },
            { value: '2', label: 'T3' },
            { value: '3', label: 'T4' },
            { value: '4', label: 'T5' },
            { value: '5', label: 'T6' },
            { value: '6', label: 'T7' },
            { value: '7', label: 'CN' },
        ],

        init() {
            // Start with current week (Monday)
            const today = new Date();
            const dayOfWeek = today.getDay();
            const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Monday
            const monday = new Date(today);
            monday.setDate(today.getDate() + diff);
            this.weekStart = this.formatDate(monday);
            this.loadData();
        },

        formatDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        changeWeek(offset) {
            const current = new Date(this.weekStart);
            current.setDate(current.getDate() + (offset * 7));
            this.weekStart = this.formatDate(current);
            this.loadData();
        },

        goToCurrentWeek() {
            const today = new Date();
            const dayOfWeek = today.getDay();
            const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
            const monday = new Date(today);
            monday.setDate(today.getDate() + diff);
            this.weekStart = this.formatDate(monday);
            this.loadData();
        },

        buildTimeSlots() {
            // Build time slots from timeFrom to timeTo (30-min intervals) for even mode
            const slots = [];
            const fromH = parseInt(this.timeFrom);
            const toH = parseInt(this.timeTo);
            for (let h = fromH; h <= toH; h++) {
                slots.push(String(h).padStart(2, '0') + ':00');
                slots.push(String(h).padStart(2, '0') + ':30');
            }
            return slots;
        },

        async loadData() {
            this.loading = true;
            FilterProgress.show();
            const params = new URLSearchParams();
            params.set('week_start', this.weekStart);
            params.set('slot_mode', this.slotMode);
            if (this.teacherSearch) params.set('teacher_search', this.teacherSearch);
            if (this.trialFilter !== 'all') params.set('trial_filter', this.trialFilter);
            if (this.teacherType) params.set('teacher_type', this.teacherType);

            // Time slot filter (only for even mode)
            if (this.slotMode === 'even') {
                const filteredSlots = this.buildTimeSlots();
                if (filteredSlots.length > 0 && (parseInt(this.timeFrom) > 6 || parseInt(this.timeTo) < 23)) {
                    params.set('time_slots', filteredSlots.join(','));
                }
            }

            try {
                const response = await fetch(`/api/teacher-availability?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    this.days = data.days || [];
                    this.timeSlots = data.time_slots || [];
                    this.grid = data.grid || {};
                    this.teacherDetail = data.teacher_detail || {};
                    this.teachers = data.teachers || [];
                    this.teacherCount = data.teacher_count || 0;
                    this.weekLabel = data.week_label || '';
                }
            } catch (error) {
                console.error('Error fetching teacher availability:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        getCellValue(date, slot) {
            const cell = this.grid[date]?.[slot];
            if (!cell || cell.total_slots === 0) return '—';
            return `${cell.available}/${cell.total_slots}`;
        },

        getCellClass(date, slot) {
            const cell = this.grid[date]?.[slot];
            if (!cell || cell.total_slots === 0) {
                return 'bg-gray-500/5 dark:bg-gray-500/5 text-gray-400 dark:text-gray-600';
            }
            const ratio = cell.available / cell.total_slots;
            if (cell.available === 0) {
                return 'bg-red-500/10 text-red-500 dark:text-red-400 hover:bg-red-500/20';
            }
            if (ratio <= 0.3) {
                return 'bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25';
            }
            if (ratio <= 0.6) {
                return 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-500/20';
            }
            return 'bg-green-500/15 text-green-600 dark:text-green-400 hover:bg-green-500/25';
        },

        getCellTitle(date, slot) {
            const cell = this.grid[date]?.[slot];
            if (!cell || cell.total_slots === 0) return 'Không có ca trống';
            return `${cell.available} GV khả dụng / ${cell.total_slots} tổng ca trống\nNhấn để xem chi tiết`;
        },

        async showSlotDetail(date, slot, dayLabel) {
            const cell = this.grid[date]?.[slot];
            if (!cell || cell.total_slots === 0) return;

            this.detailDayLabel = dayLabel;
            this.detailSlot = slot;
            this.showDetailModal = true;
            this.detailLoading = true;
            this.detailTeachers = [];

            const params = new URLSearchParams();
            params.set('date', date);
            params.set('time_slot', slot);
            if (this.trialFilter !== 'all') params.set('trial_filter', this.trialFilter);
            if (this.teacherSearch) params.set('teacher_search', this.teacherSearch);
            if (this.teacherType) params.set('teacher_type', this.teacherType);

            try {
                const response = await fetch(`/api/teacher-availability-slot-detail?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.detailTeachers = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching slot detail:', error);
            } finally {
                this.detailLoading = false;
            }
        },

        addScheduleSlot() {
            if (this.scheduleSlots.length < 7) {
                this.scheduleSlots.push({ time: '', day: '' });
            }
        },

        removeScheduleSlot(index) {
            if (this.scheduleSlots.length > 1) {
                this.scheduleSlots.splice(index, 1);
            }
        },

        canSearchSchedule() {
            const validSlots = this.scheduleSlots.filter(s => s.time && s.day);
            return validSlots.length > 0 && this.scheduleSearchDate;
        },

        getValidSlots() {
            return this.scheduleSlots.filter(s => s.time && s.day);
        },

        async searchSchedule() {
            const validSlots = this.getValidSlots();
            if (validSlots.length === 0 || !this.scheduleSearchDate) return;

            this.scheduleSearchLoading = true;
            this.scheduleSearchResults = [];

            const params = new URLSearchParams();
            params.set('slots', JSON.stringify(validSlots));
            params.set('start_date', this.scheduleSearchDate);
            if (this.trialFilter !== 'all') params.set('trial_filter', this.trialFilter);
            if (this.teacherType) params.set('teacher_type', this.teacherType);
            if (this.teacherSearch) params.set('teacher_search', this.teacherSearch);

            try {
                const response = await fetch(`/api/search-teacher-schedule?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.scheduleSearchResults = result.data || [];
                }
            } catch (error) {
                console.error('Error searching teacher schedule:', error);
            } finally {
                this.scheduleSearchLoading = false;
            }
        },

        exportScheduleSearch() {
            if (!this.scheduleSearchResults || this.scheduleSearchResults.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }

            const validSlots = this.getValidSlots();
            const slotSummary = validSlots.map(s => s.time + ' ' + (this.scheduleDayOptions.find(o => o.value == s.day)?.label || '')).join(', ');

            const headers = ['STT', 'Giáo viên', 'Email', 'Loại GV', 'Trial', 'Ngày khả dụng', 'Tổng ngày', 'Tỷ lệ (%)'];
            const rows = this.scheduleSearchResults.map((teacher, index) => [
                index + 1,
                teacher.name || '',
                teacher.email || '',
                teacher.teacher_type || 'N/A',
                teacher.can_teach_trial ? 'Có' : 'Không',
                teacher.available_dates || 0,
                teacher.total_dates || 0,
                teacher.availability_rate || 0
            ]);

            const BOM = '\uFEFF';
            let csvContent = `Tìm lịch GV khả dụng: ${slotSummary} - Từ ${this.scheduleSearchDate}\n`;
            csvContent += headers.join(',') + '\n';
            rows.forEach(row => {
                const escapedRow = row.map(cell => {
                    const cellStr = String(cell);
                    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                        return '"' + cellStr.replace(/"/g, '""') + '"';
                    }
                    return cellStr;
                });
                csvContent += escapedRow.join(',') + '\n';
            });

            const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `GV_kha_dung_${this.scheduleSearchDate}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        exportToExcel() {
            const params = new URLSearchParams();
            params.set('week_start', this.weekStart);
            params.set('slot_mode', this.slotMode);
            if (this.teacherSearch) params.set('teacher_search', this.teacherSearch);
            if (this.trialFilter !== 'all') params.set('trial_filter', this.trialFilter);
            if (this.teacherType) params.set('teacher_type', this.teacherType);

            // Time slot filter (only for even mode)
            if (this.slotMode === 'even') {
                const filteredSlots = this.buildTimeSlots();
                if (filteredSlots.length > 0 && (parseInt(this.timeFrom) > 6 || parseInt(this.timeTo) < 23)) {
                    params.set('time_slots', filteredSlots.join(','));
                }
            }

            window.open(`/api/export-teacher-availability?${params.toString()}`, '_blank');
        }
    }
}

// Student Teacher Changes Section (Phase 204 + Phase 205 charts)
function studentTeacherChangesSection() {
    return {
        dateFrom: '',
        dateTo: new Date().toISOString().split('T')[0], // Phase 208: Default to today
        search: '',
        teacherNationality: '', // Phase 208: Teacher nationality filter
        loading: false,
        loaded: false,
        exporting: false,
        students: [],
        total: 0,
        currentPage: 1,
        perPage: 50,
        totalPages: 1,
        sortBy: 'change_count',
        sortDir: 'desc',
        showDetailModal: false,
        detailLoading: false,
        detailStudent: null,
        detailData: null,
        detailError: null,
        detailExporting: false,
        // Phase 205: Chart state
        chartLoading: false,
        distributionChartInstance: null,
        topStudentsChartInstance: null,
        trendChartInstance: null,
        // Phase 206: Reason breakdown chart
        reasonBreakdownChartInstance: null,
        // Phase 207: Teacher nationality chart
        nationalityChartInstance: null,

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('vi-VN');
        },

        toggleSort(col) {
            if (this.sortBy === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = col;
                this.sortDir = col === 'student_name' ? 'asc' : 'desc';
            }
            this.currentPage = 1;
            this.loadData();
        },

        async loadData() {
            if (!this.dateFrom || !this.dateTo) return;
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('date_from', this.dateFrom);
                params.set('date_to', this.dateTo);
                params.set('page', this.currentPage);
                params.set('per_page', this.perPage);
                params.set('sort_by', this.sortBy);
                params.set('sort_dir', this.sortDir);
                if (this.search) params.set('search', this.search);
                // Phase 208: Teacher nationality filter
                if (this.teacherNationality) params.set('teacher_nationality', this.teacherNationality);

                const res = await fetch('/api/student-teacher-changes?' + params.toString());
                const result = await res.json();

                if (result.success) {
                    this.students = result.data || [];
                    this.total = result.total || 0;
                    this.totalPages = result.total_pages || 1;
                    this.currentPage = result.page || 1;
                } else {
                    console.error('Load teacher changes failed:', result.message);
                    this.students = [];
                    this.total = 0;
                }
            } catch (e) {
                console.error('Error loading teacher changes:', e);
                this.students = [];
                this.total = 0;
            }
            this.loading = false;
            this.loaded = true;

            // Phase 205: Load chart data after table data is loaded
            if (this.students.length > 0) {
                this.loadChartData();
            }
        },

        // Phase 205: Load and render charts
        async loadChartData() {
            this.chartLoading = true;
            try {
                const params = new URLSearchParams();
                params.set('date_from', this.dateFrom);
                params.set('date_to', this.dateTo);

                const res = await fetch('/api/student-teacher-change-chart-data?' + params.toString());
                const result = await res.json();

                if (result.success && result.data) {
                    this.renderDistributionChart(result.data.distribution || []);
                    this.renderTopStudentsChart(result.data.top_students || []);
                    this.renderTrendChart(result.data.trend || { labels: [], students_affected: [], change_events: [], leave_changes: [], other_changes: [] });
                    this.renderReasonBreakdownChart(result.data.reason_breakdown || { leave_related: 0, other: 0 });
                    // Phase 207: Teacher nationality chart
                    this.renderNationalityChart(result.data.teacher_nationality || {});
                }
            } catch (e) {
                console.error('Error loading chart data:', e);
            }
            this.chartLoading = false;
        },

        renderDistributionChart(distribution) {
            if (this.distributionChartInstance) {
                this.distributionChartInstance.destroy();
                this.distributionChartInstance = null;
            }
            const canvas = this.$refs.distributionChart;
            if (!canvas || distribution.length === 0) return;

            const labels = distribution.map(d => d.change_count + ' lần');
            const data = distribution.map(d => d.student_count);

            this.distributionChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số học viên',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => 'Đổi GV ' + items[0].label,
                                label: (item) => item.raw + ' học viên',
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Số lần đổi GV' },
                            grid: { display: false },
                        },
                        y: {
                            title: { display: true, text: 'Số học viên' },
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                        }
                    }
                }
            });
        },

        renderTopStudentsChart(topStudents) {
            if (this.topStudentsChartInstance) {
                this.topStudentsChartInstance.destroy();
                this.topStudentsChartInstance = null;
            }
            const canvas = this.$refs.topStudentsChart;
            if (!canvas || topStudents.length === 0) return;

            const labels = topStudents.map(s => s.student_name || ('ID: ' + s.student_id));
            const changeCounts = topStudents.map(s => s.change_count);
            const distinctTeachers = topStudents.map(s => s.distinct_teachers);

            this.topStudentsChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Số lần đổi GV',
                            data: changeCounts,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Số GV khác nhau',
                            data: distinctTeachers,
                            backgroundColor: 'rgba(245, 158, 11, 0.6)',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: { display: true },
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 20 ? label.substring(0, 18) + '…' : label;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderTrendChart(trend) {
            if (this.trendChartInstance) {
                this.trendChartInstance.destroy();
                this.trendChartInstance = null;
            }
            const canvas = this.$refs.trendChart;
            if (!canvas || !trend.labels || trend.labels.length === 0) return;

            // Format month labels (YYYY-MM → MM/YYYY)
            const labels = trend.labels.map(l => {
                const parts = l.split('-');
                return parts[1] + '/' + parts[0];
            });

            // Phase 206: Show leave vs other breakdown in stacked bar + line for students affected
            const hasLeaveData = (trend.leave_changes || []).some(v => v > 0);

            this.trendChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Do GV nghỉ',
                            data: trend.leave_changes || [],
                            backgroundColor: 'rgba(168, 85, 247, 0.6)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 1,
                            borderRadius: 2,
                            stack: 'changes',
                            order: 2,
                        },
                        {
                            label: 'Do PH yêu cầu đổi',
                            data: trend.other_changes || [],
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1,
                            borderRadius: 2,
                            stack: 'changes',
                            order: 2,
                        },
                        {
                            label: 'Số HV bị ảnh hưởng',
                            data: trend.students_affected,
                            type: 'line',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                            order: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                afterBody: function(tooltipItems) {
                                    const idx = tooltipItems[0].dataIndex;
                                    const total = (trend.leave_changes?.[idx] || 0) + (trend.other_changes?.[idx] || 0);
                                    return 'Tổng đổi GV: ' + total;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Tháng' },
                            grid: { display: false },
                            stacked: true,
                        },
                        y: {
                            title: { display: true, text: 'Số lượng' },
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            stacked: true,
                        }
                    }
                }
            });
        },

        // Phase 206: Render reason breakdown pie chart
        renderReasonBreakdownChart(breakdown) {
            if (this.reasonBreakdownChartInstance) {
                this.reasonBreakdownChartInstance.destroy();
                this.reasonBreakdownChartInstance = null;
            }
            const canvas = this.$refs.reasonBreakdownChart;
            if (!canvas) return;
            const total = (breakdown.leave_related || 0) + (breakdown.other || 0);
            if (total === 0) return;

            this.reasonBreakdownChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Do GV nghỉ', 'Do PH yêu cầu đổi'],
                    datasets: [{
                        data: [breakdown.leave_related || 0, breakdown.other || 0],
                        backgroundColor: [
                            'rgba(168, 85, 247, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                        ],
                        borderColor: [
                            'rgba(168, 85, 247, 1)',
                            'rgba(239, 68, 68, 1)',
                        ],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw;
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + val + ' lần (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        },

        // Phase 212: Render teacher nationality bar chart — change events by VN vs Foreign
        renderNationalityChart(nationalityData) {
            if (this.nationalityChartInstance) {
                this.nationalityChartInstance.destroy();
                this.nationalityChartInstance = null;
            }
            const canvas = this.$refs.nationalityChart;
            if (!canvas) return;

            const totalChanges = nationalityData.total_changes || 0;
            if (totalChanges === 0) return;

            const changeFrom = nationalityData.change_from || {};
            const changeTo = nationalityData.change_to || {};
            const detailedFrom = nationalityData.detailed_from || {};
            const detailedTo = nationalityData.detailed_to || {};

            const labels = ['GV Việt Nam', 'GV Nước ngoài'];
            const fromData = labels.map(l => changeFrom[l] || 0);
            const toData = labels.map(l => changeTo[l] || 0);

            // Build detailed tooltip text
            const detailedLabels = {
                from: detailedFrom,
                to: detailedTo,
            };

            this.nationalityChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Đổi TỪ',
                            data: fromData,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Đổi SANG',
                            data: toData,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    // Show detailed nationality breakdown in tooltip
                                    const direction = context.datasetIndex === 0 ? 'from' : 'to';
                                    const isVN = context.dataIndex === 0;
                                    const details = detailedLabels[direction];
                                    if (isVN) {
                                        return details['Vietnamese'] ? '  Vietnamese: ' + details['Vietnamese'] + ' lần' : '';
                                    }
                                    // Foreign: show breakdown
                                    const parts = [];
                                    ['Philippines', 'Native 1', 'Native 2', 'Khác'].forEach(k => {
                                        if (details[k]) parts.push('  ' + k + ': ' + details[k] + ' lần');
                                    });
                                    return parts.join('\n');
                                },
                                label: function(context) {
                                    const val = context.raw;
                                    const pct = totalChanges > 0 ? ((val / totalChanges) * 100).toFixed(1) : 0;
                                    return context.dataset.label + ': ' + val + ' lần (' + pct + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Số lần đổi GV', font: { size: 10 } }
                        },
                        y: {
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        },

        async showDetail(student) {
            this.detailStudent = student;
            this.showDetailModal = true;
            this.detailLoading = true;
            this.detailData = null;
            this.detailError = null;

            try {
                const params = new URLSearchParams();
                params.set('student_id', student.student_id);
                params.set('date_from', this.dateFrom);
                params.set('date_to', this.dateTo);

                const res = await fetch('/api/student-teacher-change-detail?' + params.toString());
                const result = await res.json();

                if (result.success) {
                    this.detailData = result.data;
                } else {
                    this.detailError = result.message || 'Có lỗi xảy ra khi tải chi tiết';
                }
            } catch (e) {
                console.error('Error loading detail:', e);
                this.detailError = 'Lỗi kết nối: ' + (e.message || 'Không thể tải dữ liệu');
            }
            this.detailLoading = false;
        },

        // Phase 212: Export detail modal data to CSV
        async exportDetailToCSV() {
            if (!this.detailData || !(this.detailData.lessons || []).length) return;
            this.detailExporting = true;

            try {
                const student = this.detailStudent;
                const data = this.detailData;
                const lessons = data.lessons || [];

                const headers = ['STT', 'Khóa học', 'Lịch học', 'Thời gian', 'Giáo viên', 'Quốc tịch', 'Trạng thái', 'Đổi GV', 'GV trước', 'Quốc tịch GV trước', 'Lý do thay đổi'];
                const rows = lessons.map((lesson, idx) => {
                    let changeLabel = '—';
                    if (lesson.is_change && lesson.leave_info) {
                        changeLabel = 'GV nghỉ';
                    } else if (lesson.is_change) {
                        changeLabel = 'PH đổi';
                    } else if (lesson.is_change_continuation) {
                        changeLabel = 'Cùng sự kiện';
                    }

                    let reason = '—';
                    if (lesson.is_change && lesson.leave_info) {
                        reason = 'GV nghỉ phép';
                        if (lesson.leave_info.leave_reason_type) reason += ' - ' + lesson.leave_info.leave_reason_type;
                        if (lesson.leave_info.leave_period) reason += ' (' + lesson.leave_info.leave_period + ')';
                        if (lesson.leave_info.leave_reason) reason += ' - ' + lesson.leave_info.leave_reason;
                    } else if (lesson.is_change) {
                        reason = 'Do PH yêu cầu đổi';
                    } else if (lesson.is_change_continuation) {
                        reason = 'Thuộc cùng sự kiện đổi GV (không tính là lần đổi mới)';
                    }

                    return [
                        idx + 1,
                        lesson.course_name || '—',
                        lesson.schedule_slot || '—',
                        lesson.lesson_time || '',
                        lesson.teacher_name || '',
                        lesson.teacher_type || 'N/A',
                        lesson.status_label || '',
                        changeLabel,
                        (lesson.is_change || lesson.is_change_continuation) && lesson.prev_teacher_name ? lesson.prev_teacher_name : '—',
                        (lesson.is_change || lesson.is_change_continuation) && lesson.prev_teacher_type ? lesson.prev_teacher_type : '—',
                        reason,
                    ];
                });

                const BOM = '\uFEFF';
                let csvContent = `Chi tiết thay đổi GV: ${student.student_name} (ID: ${student.student_id})\n`;
                csvContent += `Thời gian: ${this.formatDate(this.dateFrom)} - ${this.formatDate(this.dateTo)}\n`;
                csvContent += `Tổng buổi: ${data.total_lessons || 0} | Số lần đổi GV: ${data.change_count || 0} | Do GV nghỉ: ${data.leave_change_count || 0} | Do PH yêu cầu đổi: ${data.other_change_count || 0}\n`;
                csvContent += '\n';
                csvContent += headers.join(',') + '\n';
                rows.forEach(row => {
                    const escapedRow = row.map(cell => {
                        const cellStr = String(cell);
                        if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                            return '"' + cellStr.replace(/"/g, '""') + '"';
                        }
                        return cellStr;
                    });
                    csvContent += escapedRow.join(',') + '\n';
                });

                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `Chi_tiet_doi_GV_${student.student_id}_${this.dateFrom}_${this.dateTo}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error('Export detail error:', e);
            }
            this.detailExporting = false;
        },

        async exportToCSV() {
            if (this.students.length === 0 || !this.dateFrom || !this.dateTo) return;
            this.exporting = true;

            try {
                // Fetch all data for export (no pagination limit)
                const params = new URLSearchParams();
                params.set('date_from', this.dateFrom);
                params.set('date_to', this.dateTo);
                params.set('page', 1);
                params.set('per_page', 99999);
                params.set('sort_by', this.sortBy);
                params.set('sort_dir', this.sortDir);
                if (this.search) params.set('search', this.search);
                // Phase 208: Teacher nationality filter
                if (this.teacherNationality) params.set('teacher_nationality', this.teacherNationality);

                const res = await fetch('/api/student-teacher-changes?' + params.toString());
                const result = await res.json();
                const data = result.data || [];

                if (data.length === 0) {
                    this.exporting = false;
                    return;
                }

                const headers = ['STT', 'ID HV', 'Họ tên', 'Email', 'SĐT', 'Số lần đổi GV', 'Do GV nghỉ', 'Do PH yêu cầu đổi', 'Số GV khác nhau', 'Tổng buổi học', 'Khóa học', 'Số đơn hàng'];
                const rows = data.map((s, i) => [
                    i + 1,
                    s.student_id,
                    s.student_name,
                    s.email,
                    s.phone || '',
                    s.change_count,
                    s.leave_change_count || 0,
                    (s.change_count || 0) - (s.leave_change_count || 0),
                    s.distinct_teachers,
                    s.total_lessons,
                    s.course_names || '',
                    s.order_count || 0,
                ]);

                const BOM = '\uFEFF';
                let csvContent = `HV thay đổi GV: ${this.formatDate(this.dateFrom)} - ${this.formatDate(this.dateTo)}\n`;
                csvContent += headers.join(',') + '\n';
                rows.forEach(row => {
                    const escapedRow = row.map(cell => {
                        const cellStr = String(cell);
                        if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                            return '"' + cellStr.replace(/"/g, '""') + '"';
                        }
                        return cellStr;
                    });
                    csvContent += escapedRow.join(',') + '\n';
                });

                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `HV_doi_GV_${this.dateFrom}_${this.dateTo}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error('Export error:', e);
            }
            this.exporting = false;
        }
    };
}
</script>
@endpush
