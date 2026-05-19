@extends('layouts.app')

@section('title', 'Chăm sóc CSI Dashboard')
@section('page-title', 'Chăm sóc CSI')

@section('content')
<div x-data="csiDashboard()" x-init="init()" class="space-y-6">

    {{-- No Data Warning --}}
    @if(!$isAvailable)
    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-yellow-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">Chưa có dữ liệu CSI</p>
        <p class="text-sm text-yellow-600/80 dark:text-yellow-400/80 mt-2">
            Không thể kết nối đến Zeus Core MySQL hoặc chưa có dữ liệu buổi học SPEAKWELL hoàn thành sau ngày 2025-11-04.
        </p>
    </div>
    @else

    {{-- SPEAKWELL Program Indicator --}}
    <div class="mb-0">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 border border-blue-500/20 text-blue-600 dark:text-blue-400">
            <span>🗣️</span>
            <span>SPEAKWELL</span>
            <span class="info-tooltip hidden md:inline-flex">ⓘ
                <span class="tooltip-content tooltip-wide">
                    <span class="tooltip-label">chương trình SPEAKWELL</span><br>
                    Tất cả chỉ số trên trang Chăm sóc CSI được lấy từ dữ liệu khóa học SPEAKWELL.<br><br>
                    <span class="tooltip-label">bộ lọc khóa học</span><br>
                    Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>
                    Điều kiện: ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)<br>
                    Tổng cộng 36 mã khóa học SPEAKWELL (bao gồm trial id 533).
                </span>
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Filter: Date From --}}
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    📅 Từ ngày
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">bộ lọc thời gian</span><br>Lọc các chỉ số CSI theo khoảng thời gian. Mặc định: từ 2025-11-04 đến hiện tại.</span></span>
                </label>
                <input type="date" x-model="filters.date_from"
                    class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
            </div>

            {{-- Filter: Date To --}}
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    📅 Đến ngày
                </label>
                <input type="date" x-model="filters.date_to"
                    class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
            </div>

            {{-- Filter: Risk Group --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🔍 Nhóm rủi ro
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">bộ lọc</span><br>Lọc tất cả KPI, biểu đồ và bảng HV theo nhóm rủi ro, CSS, cảnh báo GV hoặc tìm kiếm HV.</span></span>
                </label>
                <select x-model="filters.health_category" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="red_yellow">🔴🟡 Đỏ và Vàng</option>
                    <option value="red">🔴 Đỏ (Báo động)</option>
                    <option value="yellow">🟡 Vàng (Cảnh báo)</option>
                    <option value="green">🟢 Xanh (Khỏe mạnh)</option>
                </select>
            </div>

            {{-- Filter: CSS Staff --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    👤 Chuyên viên CSS
                </label>
                <select x-model="filters.css_staff" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    @foreach($cssStaffList as $staff)
                    <option value="{{ $staff }}">{{ $staff }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter: Teacher Warning --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    ⚠️ Cảnh báo GV
                </label>
                <select x-model="filters.teacher_warning" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="has_warning">Có cảnh báo</option>
                    <option value="Bình thường">Bình thường</option>
                    <option value="Có ảnh hưởng (GV nghỉ 1b)">Có ảnh hưởng (GV nghỉ 1b)</option>
                    <option value="Nghiêm trọng (GV nghỉ >=2b)">Nghiêm trọng (GV nghỉ >=2b)</option>
                    <option value="Khẩn cấp (GV nghỉ >= 4 buổi)">Khẩn cấp (GV nghỉ >= 4 buổi)</option>
                </select>
            </div>

            {{-- Filter: Search --}}
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🔎 Tra cứu HV (ID / Email / Tên / SĐT)
                </label>
                <div class="relative">
                    <input type="text" x-model="filters.search" @keydown.enter="loadData()" placeholder="Nhập ID, email, tên hoặc SĐT..."
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 pr-8 focus:ring-2 focus:ring-zeus-accent">
                    <button x-show="filters.search" @click="filters.search = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted hover:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Apply Filter Button --}}
            <div>
                <button @click="loadData()" class="px-4 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent/90 transition font-medium">
                    🔍 Lọc
                </button>
            </div>

            {{-- Reset Filters --}}
            <div>
                <button @click="resetFilters()" class="px-4 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text border border-light-border dark:border-zeus-border rounded-lg transition">
                    Xóa bộ lọc
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Summary Cards - Loading --}}
    <div x-show="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4">
        <template x-for="i in 7" :key="i">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border animate-pulse">
                <div class="h-3 bg-light-border dark:bg-zeus-border rounded w-2/3 mb-3"></div>
                <div class="h-7 bg-light-border dark:bg-zeus-border rounded w-1/2"></div>
            </div>
        </template>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4" x-show="!loading">
        {{-- Total Students --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Tổng HV
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tổng học viên</span><br>Tổng số học viên SPEAKWELL có buổi học hoàn thành (ordles_status=3) sau 2025-11-04.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_users</span><br><br><span class="tooltip-sql">SELECT COUNT(DISTINCT ordles_beneficiary_id) AS total_hv
FROM tbl_order_lessons
WHERE ordles_beneficiary_id IS NOT NULL
  AND ordles_beneficiary_id > 0
  AND ordles_status = 3
  AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
  AND ordles_lesson_starttime > '2025-11-04'
  AND ordles_lesson_starttime <= NOW()</span></span></span>
            </p>
            <p class="text-2xl font-bold text-light-text dark:text-zeus-text mt-1" x-text="fmt(summary.total_students)"></p>
        </div>

        {{-- Green --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-emerald-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">🟢 Khỏe mạnh
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">xanh (khỏe mạnh)</span><br>HV có tỉ lệ ca học thành công từ 85-100%.<br>Điểm = Số buổi thành công / Tổng buổi × 100.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT COUNT(*) AS so_hv_xanh FROM scores
WHERE health_score >= 85</span></span></span>
            </p>
            <p class="text-2xl font-bold text-emerald-500 mt-1" x-text="fmt(summary.green)"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="summary.green_pct + '%'"></p>
        </div>

        {{-- Yellow --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-yellow-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">🟡 Cảnh báo
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">vàng (cảnh báo)</span><br>HV có tỉ lệ ca học thành công từ 60-84%.<br>CSS cần gọi điện tìm hiểu nguyên nhân.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT COUNT(*) AS so_hv_vang FROM scores
WHERE health_score >= 60 AND health_score < 85</span></span></span>
            </p>
            <p class="text-2xl font-bold text-yellow-500 mt-1" x-text="fmt(summary.yellow)"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="summary.yellow_pct + '%'"></p>
        </div>

        {{-- Red --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-red-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">🔴 Báo động
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">đỏ (báo động)</span><br>HV có tỉ lệ ca học thành công dưới 60%.<br>CSS cần kích hoạt cứu vãn khẩn cấp.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT COUNT(*) AS so_hv_do FROM scores
WHERE health_score < 60</span></span></span>
            </p>
            <p class="text-2xl font-bold text-red-500 mt-1" x-text="fmt(summary.red)"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="summary.red_pct + '%'"></p>
        </div>

        {{-- Avg Health Score --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Điểm SK TB
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">điểm sức khỏe trung bình</span><br>Trung bình cộng tỉ lệ ca học thành công (%) của tất cả HV.<br>Điểm = Số buổi thành công / Tổng buổi × 100.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT ROUND(AVG(health_score), 1) AS avg_health_score
FROM scores</span></span></span>
            </p>
            <p class="text-2xl font-bold mt-1" :class="summary.avg_score >= 85 ? 'text-emerald-500' : summary.avg_score >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="summary.avg_score + '%'"></p>
        </div>

        {{-- Success Rate --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Tỉ lệ học TC
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tỉ lệ học thành công</span><br>= Tổng buổi thành công / Tổng buổi hoàn thành × 100 (của các HV thỏa mãn bộ lọc).<br>Buổi thành công: ole_acceptance_code IN (3,6,9,12).<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT ROUND(
  SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END)
  * 100.0 / COUNT(*), 1
) AS success_rate_pct
FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-2xl font-bold mt-1" :class="summary.success_rate >= 80 ? 'text-emerald-500' : summary.success_rate >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="summary.success_rate + '%'"></p>
        </div>

        {{-- Ontrack Rate (Phase 183) --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-cyan-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">🎯 Ontrack
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">ontrack</span><br>= Trung bình cột 🎯 Ontrack trong bảng số liệu chi tiết.<br><br><span class="tooltip-label">Công thức mỗi kỳ</span>: HV ontrack / Tổng HV active × 100.<br><span class="tooltip-label">HV ontrack</span>: Số HV có tỉ lệ ca học thành công ≥ 90% trong kỳ (acceptance_code IN 3,6,9,12).<br><span class="tooltip-label">Tổng HV active</span>: Tổng HV (distinct) có buổi học trong kỳ.<br><br><span class="tooltip-label">KPI</span>: Trung bình cộng của tất cả giá trị Ontrack theo tuần.<br><br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">-- HV ontrack: HV có success_rate >= 90%
-- success_rate = buổi thành công (code 3,6,9,12) / tổng buổi × 100
-- Tổng HV active: COUNT(DISTINCT student_id) trong kỳ
-- Mỗi kỳ: ontrack = HV ontrack / Tổng HV active × 100
-- KPI = AVG(ontrack) của tất cả các kỳ (tuần)</span></span></span>
            </p>
            <p class="text-2xl font-bold mt-1" :class="summary.ontrack_rate >= 80 ? 'text-cyan-500' : summary.ontrack_rate >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="summary.ontrack_rate + '%'"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">TB bảng chi tiết</p>
        </div>
    </div>

    {{-- SpeakWell Student Stats (Phase 189) - Loading --}}
    <div x-show="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4">
        <template x-for="i in 3" :key="i">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border animate-pulse">
                <div class="h-3 bg-light-border dark:bg-zeus-border rounded w-2/3 mb-3"></div>
                <div class="h-7 bg-light-border dark:bg-zeus-border rounded w-1/2"></div>
            </div>
        </template>
    </div>

    {{-- SpeakWell Student Stats (Phase 189, updated Phase 221) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4" x-show="!loading">
        {{-- Total SpeakWell Students --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-indigo-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">👥 Tổng HV SpeakWell
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tổng học viên SpeakWell</span><br>Tổng số học viên (distinct) của sản phẩm SpeakWell (bao gồm cả Easy Speak), kết hợp từ buổi học 1-1 và lớp nhóm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_classes, tbl_group_classes</span><br><br><span class="tooltip-sql">SELECT COUNT(DISTINCT t.student_id) FROM (
  SELECT DISTINCT ol.ordles_beneficiary_id AS student_id
  FROM tbl_order_lessons AS ol
  WHERE ol.ordles_tlang_id IN (389,390,392,...,586)
  UNION ALL
  SELECT DISTINCT oc.ordcls_beneficiary_id AS student_id
  FROM tbl_order_classes oc
  INNER JOIN tbl_group_classes gce
    ON oc.ordcls_grpcls_id = gce.grpcls_id
  WHERE gce.grpcls_tlang_id IN (389,390,392,...,586)
) t</span></span></span>
            </p>
            <p class="text-2xl font-bold text-indigo-500 mt-1" x-text="fmt(summary.speakwell_total)"></p>
        </div>

        {{-- Active SpeakWell Students --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-emerald-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">✅ HV Active SpeakWell
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">HV active SpeakWell</span><br>Tổng số học viên Active của sản phẩm SpeakWell (gồm cả Easy Speak), kết hợp từ buổi học 1-1 và lớp nhóm.<br>Điều kiện Active (1-1):<br>• user_lastseen trong 30 ngày gần nhất<br>• ordles_status = 3 (hoàn thành)<br>• ordles_lesson_endtime trong 30 ngày gần nhất<br>Điều kiện Active (lớp nhóm):<br>• grpcls_status = 2<br>• user_lastseen trong 30 ngày gần nhất<br>Bảng: <span class="tooltip-table">tbl_users, tbl_orders, tbl_order_lessons, tbl_order_classes, tbl_group_classes</span><br><br><span class="tooltip-sql">SELECT COUNT(DISTINCT t.student_id) FROM (
  SELECT DISTINCT u.user_id AS student_id
  FROM tbl_users u
  JOIN tbl_orders o ON o.order_user_id = u.user_id
  JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
  WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
    AND ol.ordles_status = 3
    AND ol.ordles_tlang_id IN (389,...,586)
    AND ol.ordles_lesson_endtime BETWEEN
        NOW() - INTERVAL 30 DAY AND NOW()
  UNION ALL
  SELECT DISTINCT oc.ordcls_beneficiary_id
  FROM tbl_order_classes oc
  JOIN tbl_group_classes gce
    ON oc.ordcls_grpcls_id = gce.grpcls_id
  JOIN tbl_users u
    ON oc.ordcls_beneficiary_id = u.user_id
  WHERE gce.grpcls_tlang_id IN (389,...,586)
    AND gce.grpcls_status = 2
    AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
) t</span></span></span>
            </p>
            <p class="text-2xl font-bold text-emerald-500 mt-1" x-text="fmt(summary.speakwell_active)"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="summary.speakwell_total > 0 ? (summary.speakwell_active * 100 / summary.speakwell_total).toFixed(1) + '%' : '0%'"></p>
        </div>

        {{-- Inactive SpeakWell Students --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border-l-4 border-gray-500 border border-light-border dark:border-zeus-border">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">💤 HV Inactive SpeakWell
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">HV inactive SpeakWell</span><br>Số học viên Inactive = Tổng HV SpeakWell − HV Active SpeakWell.<br>Bao gồm cả buổi 1-1 và lớp nhóm.<br>Đây là những học viên không hoạt động trong 30 ngày gần nhất.</span></span>
            </p>
            <p class="text-2xl font-bold text-gray-500 mt-1" x-text="fmt(summary.speakwell_inactive)"></p>
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="summary.speakwell_total > 0 ? (summary.speakwell_inactive * 100 / summary.speakwell_total).toFixed(1) + '%' : '0%'"></p>
            <button @click="openInactiveDialog()"
                class="mt-2 px-3 py-1.5 text-xs bg-gray-500/20 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-500/30 transition inline-flex items-center gap-1">
                📋 Xem chi tiết
            </button>
        </div>
    </div>

    {{-- Inactive Students Dialog (Phase 221) --}}
    <div x-show="showInactiveDialog" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="showInactiveDialog = false"></div>

        {{-- Dialog --}}
        <div class="relative bg-light-card dark:bg-zeus-card rounded-2xl shadow-2xl border border-light-border dark:border-zeus-border w-full max-w-5xl max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border">
                <div>
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text">💤 Danh sách HV Inactive SpeakWell</h3>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">
                        Tổng: <span class="font-bold" x-text="fmt(inactiveData.total)"></span> học viên
                        &nbsp;·&nbsp;
                        <span class="text-red-500 font-bold" x-text="fmt(inactiveData.zero_lessons_count)"></span> HV có 0 buổi còn lại (unschedule + schedule = 0)
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="exportInactiveToExcel()" class="px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1"
                        :disabled="inactiveExporting">
                        <template x-if="!inactiveExporting">
                            <span>📥 Xuất Excel</span>
                        </template>
                        <template x-if="inactiveExporting">
                            <span><span class="spinner-inline"></span> Đang xuất...</span>
                        </template>
                    </button>
                    <button @click="showInactiveDialog = false" class="p-1.5 text-light-text-muted dark:text-zeus-text-muted hover:text-red-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Search --}}
            <div class="p-4 border-b border-light-border dark:border-zeus-border">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <input type="text" x-model="inactiveSearch" @keydown.enter="loadInactiveStudents(1)" placeholder="Tìm theo ID, tên, email, username..."
                            class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    </div>
                    <button @click="loadInactiveStudents(1)" class="px-3 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent/90 transition">🔍</button>
                    <button x-show="inactiveSearch" @click="inactiveSearch = ''; loadInactiveStudents(1)" class="px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted border border-light-border dark:border-zeus-border rounded-lg transition hover:text-light-text dark:hover:text-zeus-text">Xóa</button>
                </div>
            </div>

            {{-- Table --}}
            <div class="flex-1 overflow-auto p-4">
                {{-- Loading --}}
                <div x-show="inactiveLoading" class="flex items-center justify-center py-8 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline"></span> Đang tải dữ liệu...
                </div>

                {{-- Data Table (Phase 222: sortable columns) --}}
                <div x-show="!inactiveLoading" class="table-container">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-light-border dark:border-zeus-border">
                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('student_id')">
                                    ID <span x-text="inactiveSortIndicator('student_id')"></span>
                                </th>
                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('user_username')">
                                    Username <span x-text="inactiveSortIndicator('user_username')"></span>
                                </th>
                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('student_name')">
                                    Tên HV <span x-text="inactiveSortIndicator('student_name')"></span>
                                </th>
                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('user_email')">
                                    Email <span x-text="inactiveSortIndicator('user_email')"></span>
                                </th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('unscheduled_count')">
                                    Chưa lên lịch
                                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">unschedule</span><br>Số buổi SPW có ordles_status = 1 (UNSCHEDULED)</span></span>
                                    <span x-text="inactiveSortIndicator('unscheduled_count')"></span>
                                </th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('scheduled_count')">
                                    Đã lên lịch
                                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">schedule</span><br>Số buổi SPW có ordles_status = 2 (SCHEDULED)</span></span>
                                    <span x-text="inactiveSortIndicator('scheduled_count')"></span>
                                </th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleInactiveSort('remaining_total')">
                                    Tổng còn lại <span x-text="inactiveSortIndicator('remaining_total')"></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in inactiveData.data" :key="i">
                                <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt/50 dark:hover:bg-zeus-card-light/50">
                                    <td class="py-2 px-3 text-light-text dark:text-zeus-text font-mono text-xs" x-text="row.student_id"></td>
                                    <td class="py-2 px-3 text-light-text dark:text-zeus-text" x-text="row.user_username || '—'"></td>
                                    <td class="py-2 px-3 text-light-text dark:text-zeus-text font-medium" x-text="row.student_name"></td>
                                    <td class="py-2 px-3 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="row.user_email"></td>
                                    <td class="py-2 px-3 text-right" :class="(parseInt(row.unscheduled_count) || 0) > 0 ? 'text-blue-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="parseInt(row.unscheduled_count) || 0"></td>
                                    <td class="py-2 px-3 text-right" :class="(parseInt(row.scheduled_count) || 0) > 0 ? 'text-emerald-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="parseInt(row.scheduled_count) || 0"></td>
                                    <td class="py-2 px-3 text-right font-bold" :class="(parseInt(row.remaining_total) || 0) === 0 ? 'text-red-500' : 'text-light-text dark:text-zeus-text'" x-text="parseInt(row.remaining_total) || 0"></td>
                                </tr>
                            </template>
                            <template x-if="!inactiveLoading && inactiveData.data.length === 0">
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="flex items-center justify-between p-4 border-t border-light-border dark:border-zeus-border" x-show="inactiveData.total_pages > 1">
                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                    Trang <span x-text="inactiveData.page"></span> / <span x-text="inactiveData.total_pages"></span>
                    &nbsp;(<span x-text="fmt(inactiveData.total)"></span> HV)
                </p>
                <div class="flex items-center gap-2">
                    <button @click="loadInactiveStudents(inactiveData.page - 1)" :disabled="inactiveData.page <= 1"
                        class="px-3 py-1.5 text-xs rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text disabled:opacity-50 hover:bg-light-card dark:hover:bg-zeus-card transition">
                        ← Trước
                    </button>
                    <button @click="loadInactiveStudents(inactiveData.page + 1)" :disabled="inactiveData.page >= inactiveData.total_pages"
                        class="px-3 py-1.5 text-xs rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text disabled:opacity-50 hover:bg-light-card dark:hover:bg-zeus-card transition">
                        Sau →
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Session Stats Row - Loading --}}
    <div x-show="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <template x-for="i in 6" :key="i">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center animate-pulse">
                <div class="h-3 bg-light-border dark:bg-zeus-border rounded w-3/4 mx-auto mb-3"></div>
                <div class="h-5 bg-light-border dark:bg-zeus-border rounded w-1/2 mx-auto"></div>
            </div>
        </template>
    </div>

    {{-- Session Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4" x-show="!loading">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tổng buổi hoàn thành
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tổng buổi hoàn thành</span><br>Tổng cộng tất cả buổi học SPEAKWELL hoàn thành (ordles_status=3) của các HV thỏa mãn bộ lọc.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br><br><span class="tooltip-sql">SELECT COUNT(*) AS total_completed
FROM tbl_order_lessons
WHERE ordles_beneficiary_id IS NOT NULL
  AND ordles_beneficiary_id > 0
  AND ordles_status = 3
  AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
  AND ordles_lesson_starttime > '2025-11-04'
  AND ordles_lesson_starttime <= NOW()
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-lg font-bold text-light-text dark:text-zeus-text mt-1" x-text="fmt(summary.total_scheduled)"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Buổi thành công
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">buổi thành công</span><br>Tổng số buổi HV vào học bình thường (acceptance_code IN 3,6,9,12) của các HV thỏa mãn bộ lọc.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT COUNT(*) AS total_success
FROM les l JOIN ext e ON e.ole_ordles_id = l.ordles_id
WHERE e.ole_acceptance_code IN (3, 6, 9, 12)
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-lg font-bold text-emerald-500 mt-1" x-text="fmt(summary.total_success)"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">HV Noshow
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">HV noshow</span><br>Tổng số buổi HV không vào lớp (noshow) của các HV thỏa mãn bộ lọc.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT COUNT(*) AS total_student_noshow
FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
WHERE e.ole_acceptance_code IN (0, 4, 7, 10)
   OR e.ole_acceptance_code IS NULL
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-lg font-bold text-red-400 mt-1" x-text="fmt(summary.total_noshow)"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">HV < 1/2 giờ
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">HV học dưới 1/2 giờ</span><br>Tổng số buổi HV vào lớp nhưng học dưới 1/2 thời gian của các HV thỏa mãn bộ lọc.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT COUNT(*) AS total_student_half
FROM les l JOIN ext e ON e.ole_ordles_id = l.ordles_id
WHERE e.ole_acceptance_code IN (2, 5, 8, 11)
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-lg font-bold text-orange-400 mt-1" x-text="fmt(summary.total_half)"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">GV Noshow
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">GV noshow</span><br>Tổng số buổi GV không vào lớp của các HV thỏa mãn bộ lọc.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT COUNT(*) AS total_teacher_noshow
FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
WHERE e.ole_acceptance_code IN (0, 2, 3)
   OR e.ole_acceptance_code IS NULL
-- Lọc theo khoảng thời gian, nhóm rủi ro, CSS, cảnh báo GV</span></span></span>
            </p>
            <p class="text-lg font-bold text-purple-400 mt-1" x-text="fmt(summary.total_teacher_noshow)"></p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border text-center">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Buổi TB / tuần
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">buổi học trung bình / tuần</span><br>Trung bình cộng của chỉ số buổi/tuần của từng học viên thỏa mãn bộ lọc.<br>Công thức: AVG(buổi thành công của HV / tổng số tuần).<br>Mỗi HV có avg_per_week = số buổi thành công / tổng tuần, sau đó lấy trung bình tất cả HV.<br>Chỉ số này thay đổi theo bộ lọc: thời gian, nhóm rủi ro, CSS, cảnh báo GV, tìm kiếm.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span></span></span>
            </p>
            <p class="text-lg font-bold text-blue-500 mt-1" x-text="summary.avg_lessons_per_week"></p>
        </div>
    </div>

    {{-- Charts Section - Loading --}}
    <div x-show="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <template x-for="i in 4" :key="i">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
                <div class="h-4 bg-light-border dark:bg-zeus-border rounded w-1/3 mb-4 animate-pulse"></div>
                <div class="flex items-center justify-center" style="height: 280px;">
                    <span class="spinner-inline"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="!loading">
        {{-- Health Distribution Pie Chart --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Phân bố Sức khỏe HV
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">biểu đồ phân bố sức khỏe</span><br>Biểu đồ tròn thể hiện tỉ lệ HV theo 3 nhóm sức khỏe: Xanh (85-100%), Vàng (60-84%), Đỏ (dưới 60%).<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras, tbl_users</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT
  CASE
    WHEN health_score >= 85 THEN 'Xanh (Khỏe mạnh)'
    WHEN health_score >= 60 THEN 'Vàng (Cảnh báo)'
    ELSE 'Đỏ (Báo động)'
  END AS health_category,
  COUNT(*) AS count
FROM scores
GROUP BY health_category ORDER BY count DESC</span></span></span>
            </h3>
            <div class="chart-container" style="height: 280px;">
                <canvas id="healthChart"></canvas>
            </div>
        </div>

        {{-- Score Distribution Bar Chart --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Phân bố Điểm Sức khỏe
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">biểu đồ phân bố điểm</span><br>Biểu đồ cột thể hiện số HV theo từng khoảng tỉ lệ thành công (%): 0-20, 21-40, 41-60, 61-80, 81-100.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT
  CASE
    WHEN health_score >= 0 AND health_score <= 20 THEN '0-20'
    WHEN health_score > 20 AND health_score <= 40 THEN '21-40'
    WHEN health_score > 40 AND health_score <= 60 THEN '41-60'
    WHEN health_score > 60 AND health_score <= 80 THEN '61-80'
    WHEN health_score > 80 AND health_score <= 100 THEN '81-100'
    ELSE '< 0'
  END AS label, COUNT(*) AS count
FROM scores GROUP BY label ORDER BY MIN(health_score)</span></span></span>
            </h3>
            <div class="chart-container" style="height: 280px;">
                <canvas id="scoreChart"></canvas>
            </div>
        </div>

        {{-- CSS Staff Performance --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Hiệu suất theo CSS
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">biểu đồ hiệu suất CSS</span><br>Biểu đồ cột xếp chồng (stacked bar) thể hiện số lượng HV Xanh/Vàng/Đỏ mà mỗi chuyên viên CSS phụ trách.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras, tbl_user_extras, tbl_admin</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    MAX(a.admin_username) AS css_staff,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  INNER JOIN tbl_users u ON l.ordles_beneficiary_id = u.user_id
  LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
  LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT css_staff, COUNT(*) AS total,
  SUM(CASE WHEN health_score >= 85 THEN 1 ELSE 0 END) AS green,
  SUM(CASE WHEN health_score >= 60 AND health_score < 85 THEN 1 ELSE 0 END) AS yellow,
  SUM(CASE WHEN health_score < 60 THEN 1 ELSE 0 END) AS red
FROM scores WHERE css_staff IS NOT NULL
GROUP BY css_staff ORDER BY css_staff</span></span></span>
            </h3>
            <div class="chart-container" style="height: 280px;">
                <canvas id="cssChart"></canvas>
            </div>
        </div>

        {{-- Teacher Warning Distribution --}}
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-4">Cảnh báo trải nghiệm do GV
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">biểu đồ cảnh báo GV</span><br>Biểu đồ tròn thể hiện tỉ lệ HV theo mức cảnh báo trải nghiệm do GV nghỉ:<br>• Bình thường (GV không nghỉ)<br>• Có ảnh hưởng (GV nghỉ 1 buổi)<br>• Nghiêm trọng (GV nghỉ ≥2 buổi)<br>• Khẩn cấp (GV nghỉ ≥4 buổi)<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), teacher_ns AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    SUM(CASE WHEN e.ole_acceptance_code IN (0,2,3)
      OR e.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS teacher_noshow
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT
  CASE
    WHEN teacher_noshow >= 4 THEN 'Khẩn cấp (GV nghỉ >= 4 buổi)'
    WHEN teacher_noshow >= 2 THEN 'Nghiêm trọng (GV nghỉ >=2b)'
    WHEN teacher_noshow = 1  THEN 'Có ảnh hưởng (GV nghỉ 1b)'
    ELSE 'Bình thường'
  END AS teacher_warning, COUNT(*) AS count
FROM teacher_ns
GROUP BY teacher_warning ORDER BY count DESC</span></span></span>
            </h3>
            <div class="chart-container" style="height: 280px;">
                <canvas id="teacherWarningChart"></canvas>
            </div>

            {{-- Leave-affected Sessions Stats (Phase 187) --}}
            <div class="mt-4 pt-4 border-t border-light-border dark:border-zeus-border" x-show="summary.leave_affected && summary.leave_affected.total_affected_sessions > 0">
                <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-2">
                    📋 Buổi bị ảnh hưởng do GV xin nghỉ phép
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">buổi bị ảnh hưởng</span><br>Số buổi học bị ảnh hưởng khi giáo viên xin nghỉ phép (đã duyệt / tự động duyệt) trong giai đoạn lọc.<br>Bảng: <span class="tooltip-table">tbl_teacher_leave_requests, tbl_teacher_leave_request_sessions</span><br><br><span class="tooltip-sql">SELECT COUNT(*) as total_affected_sessions,
  SUM(CASE WHEN tlrs_need_replacement = 1 THEN 1 ELSE 0 END) as need_replacement,
  SUM(CASE WHEN tlrs_need_replacement = 0 OR tlrs_need_replacement IS NULL THEN 1 ELSE 0 END) as no_replacement
FROM tbl_teacher_leave_requests lr
INNER JOIN tbl_teacher_leave_request_sessions lrs
  ON lr.tlr_id = lrs.tlrs_leave_request_id
WHERE lr.tlr_status IN (2, 3)
  AND lrs.tlrs_session_date BETWEEN '...' AND '...'</span></span></span>
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center p-2 rounded-lg bg-light-card-alt dark:bg-zeus-card-light">
                        <p class="text-lg font-bold text-red-500" x-text="fmt(summary.leave_affected.total_affected_sessions)"></p>
                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Tổng buổi ảnh hưởng</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-light-card-alt dark:bg-zeus-card-light">
                        <p class="text-lg font-bold text-yellow-500" x-text="fmt(summary.leave_affected.need_replacement)"></p>
                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Cần thay GV</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-light-card-alt dark:bg-zeus-card-light">
                        <p class="text-lg font-bold text-orange-500" x-text="fmt(summary.leave_affected.no_replacement)"></p>
                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Không thay GV</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CSS Staff Detail Table - Loading --}}
    <div x-show="loading" class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
        <div class="h-4 bg-light-border dark:bg-zeus-border rounded w-1/4 mb-4 animate-pulse"></div>
        <div class="flex items-center justify-center py-8 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
            <span class="spinner-inline"></span> Đang tải dữ liệu...
        </div>
    </div>

    {{-- CSS Staff Detail Table --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border" x-show="!loading && cssPerformance.length > 0">
        <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">📊 Chi tiết theo Chuyên viên CSS
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">bảng chi tiết CSS</span><br>Thống kê tổng hợp theo từng chuyên viên CSS: số HV phụ trách, phân nhóm sức khỏe (Xanh/Vàng/Đỏ), điểm TB và tỉ lệ thành công.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras, tbl_user_extras, tbl_admin</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), scores AS (
  SELECT l.ordles_beneficiary_id AS student_id,
    MAX(a.admin_username) AS css_staff,
    ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
      THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score,
    SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END)
      * 1.0 / COUNT(*) AS success_rate
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
  INNER JOIN tbl_users u ON l.ordles_beneficiary_id = u.user_id
  LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
  LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
  GROUP BY l.ordles_beneficiary_id
)
SELECT css_staff, COUNT(*) AS total,
  SUM(CASE WHEN health_score >= 85 THEN 1 ELSE 0 END) AS green,
  SUM(CASE WHEN health_score >= 60 AND health_score < 85 THEN 1 ELSE 0 END) AS yellow,
  SUM(CASE WHEN health_score < 60 THEN 1 ELSE 0 END) AS red,
  ROUND(AVG(health_score), 1) AS avg_score,
  ROUND(AVG(success_rate) * 100, 1) AS avg_success_rate
FROM scores WHERE css_staff IS NOT NULL AND css_staff != ''
GROUP BY css_staff ORDER BY css_staff</span></span></span>
        </h3>
            <button @click="exportCssToExcel()" class="px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1 whitespace-nowrap">
                📥 Xuất Excel
            </button>
        </div>
        <div class="table-container">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">CSS</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Tổng HV</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🟢 Xanh</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🟡 Vàng</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🔴 Đỏ</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Điểm TB</th>
                        <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Tỉ lệ TC</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, i) in cssPerformance" :key="i">
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt/50 dark:hover:bg-zeus-card-light/50">
                            <td class="py-2 px-3 font-medium text-light-text dark:text-zeus-text" x-text="row.css_staff"></td>
                            <td class="py-2 px-3 text-right text-light-text dark:text-zeus-text" x-text="row.total"></td>
                            <td class="py-2 px-3 text-right text-emerald-500 font-medium" x-text="row.green"></td>
                            <td class="py-2 px-3 text-right text-yellow-500 font-medium" x-text="row.yellow"></td>
                            <td class="py-2 px-3 text-right text-red-500 font-medium" x-text="row.red"></td>
                            <td class="py-2 px-3 text-right" :class="row.avg_score >= 85 ? 'text-emerald-500' : row.avg_score >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="row.avg_score"></td>
                            <td class="py-2 px-3 text-right" :class="row.avg_success_rate >= 80 ? 'text-emerald-500' : row.avg_success_rate >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="row.avg_success_rate + '%'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Trend Comparison Charts Section --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border" x-data="{ showTrends: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="showTrends = !showTrends; if(showTrends && trendData.length === 0 && !trendLoading) loadTrends()">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                📈 Biểu đồ xu hướng theo thời gian
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">biểu đồ xu hướng</span><br>So sánh các chỉ số quan trọng theo tuần hoặc tháng để thấy sự tăng giảm:<br>• Tổng buổi hoàn thành<br>• Buổi thành công / Noshow / Dưới 1/2 giờ<br>• Tỉ lệ thành công (%)<br>• Số HV hoạt động<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span></span></span>
            </h3>
            <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted transition-transform" :class="{ 'rotate-180': showTrends }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="showTrends" x-collapse class="mt-4">
            {{-- Trend Period Toggle --}}
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Nhóm theo:</span>
                <button @click="trendGroupBy = 'week'; loadTrends()"
                    class="px-3 py-1.5 text-xs rounded-lg border transition"
                    :class="trendGroupBy === 'week' ? 'bg-zeus-accent text-white border-zeus-accent' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted border-light-border dark:border-zeus-border hover:text-light-text dark:hover:text-zeus-text'">
                    📅 Theo tuần
                </button>
                <button @click="trendGroupBy = 'month'; loadTrends()"
                    class="px-3 py-1.5 text-xs rounded-lg border transition"
                    :class="trendGroupBy === 'month' ? 'bg-zeus-accent text-white border-zeus-accent' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted border-light-border dark:border-zeus-border hover:text-light-text dark:hover:text-zeus-text'">
                    📆 Theo tháng
                </button>
            </div>

            {{-- Loading --}}
            <template x-if="trendLoading">
                <div class="flex items-center justify-center py-8 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline"></span> Đang tải dữ liệu xu hướng...
                </div>
            </template>

            {{-- No Data --}}
            <template x-if="!trendLoading && trendData.length === 0">
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu xu hướng.</p>
            </template>

            {{-- Trend Charts Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="!trendLoading && trendData.length > 0">
                {{-- Chart 1: Lesson Volume Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        📊 Số lượng buổi học
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">xu hướng buổi học</span><br>So sánh tổng buổi hoàn thành, buổi thành công, noshow và học dưới 1/2 giờ qua các kỳ.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendVolumeChart"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Success Rate Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        📈 Tỉ lệ thành công (%)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">xu hướng tỉ lệ thành công</span><br>Tỉ lệ buổi thành công so với tổng buổi qua các kỳ. Đường xu hướng giúp nhận diện cải thiện hoặc suy giảm.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendSuccessRateChart"></canvas>
                    </div>
                </div>

                {{-- Chart 3: Active Students Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        👨‍🎓 Số HV hoạt động
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">xu hướng HV hoạt động</span><br>Số HV có ít nhất 1 buổi học hoàn thành trong kỳ. Giúp theo dõi sự tăng giảm số HV tham gia học.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendStudentsChart"></canvas>
                    </div>
                </div>

                {{-- Chart 4: Noshow & Half Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        ⚠️ HV Noshow & Dưới 1/2 giờ
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">xu hướng vắng mặt</span><br>Số buổi HV noshow và học dưới 1/2 giờ qua các kỳ. Giúp phát hiện xu hướng tăng vắng mặt sớm.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendAbsenceChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Health Category Trend Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6" x-show="!trendLoading && healthTrendData.length > 0">
                {{-- Chart 5: Health Category Count Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        🏥 Khỏe mạnh / Cảnh báo / Báo động (số HV)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">xu hướng phân loại sức khỏe</span><br>Số HV theo nhóm sức khỏe trong từng kỳ. Mỗi kỳ tính riêng: tỉ lệ thành công từ buổi học trong kỳ đó.<br>🟢 Khỏe mạnh: ≥ 85%<br>🟡 Cảnh báo: 60-84%<br>🔴 Báo động: &lt; 60%</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendHealthCountChart"></canvas>
                    </div>
                </div>

                {{-- Chart 6: Health Category Percentage Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        📊 Khỏe mạnh / Cảnh báo / Báo động (%)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">xu hướng tỉ lệ phân loại sức khỏe</span><br>Tỉ lệ phần trăm HV theo nhóm sức khỏe trong từng kỳ. Giúp so sánh sự thay đổi cơ cấu qua các kỳ.<br>🟢 Khỏe mạnh: ≥ 85%<br>🟡 Cảnh báo: 60-84%<br>🔴 Báo động: &lt; 60%</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendHealthPctChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- OnTrack Trend Chart --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6" x-show="!trendLoading && ontrackTrendData.length > 0">
                {{-- Chart 7: OnTrack Rate Trend --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        🎯 Ontrack (%)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">xu hướng ontrack</span><br>Tỉ lệ ontrack theo từng kỳ (tuần/tháng).<br>Công thức: HV ontrack / Tổng HV active × 100.<br><br><span class="tooltip-label">HV ontrack</span>: HV có tỉ lệ ca học thành công ≥ 90% trong kỳ (code 3,6,9,12).<br><span class="tooltip-label">Tổng HV active</span>: tổng HV (distinct) có buổi học trong kỳ.<br><br><span class="tooltip-label">Lưu ý</span>: Cùng công thức với cột 🎯 Ontrack trên bảng số liệu chi tiết.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendOntrackChart"></canvas>
                    </div>
                </div>

                {{-- Chart 8: OnTrack Student Count --}}
                <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3">
                        📊 HV Ontrack / HV Active
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">số HV ontrack vs HV active</span><br>So sánh số HV ontrack (tỉ lệ code 12 ≥ 90%) với tổng HV active (có ít nhất 1 buổi code 12) theo từng kỳ.<br>Giúp theo dõi xu hướng duy trì và cải thiện chất lượng học tập.<br><br><span class="tooltip-label">Lưu ý</span>: Biểu đồ này dùng đơn vị HV, khác với tỉ lệ Ontrack (%) dùng đơn vị buổi.</span></span>
                    </h4>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="trendOntrackCountChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Trend Data Table --}}
            <div class="mt-6" x-show="!trendLoading && trendData.length > 0">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider">📋 Bảng số liệu chi tiết</h4>
                    <button @click="exportTrendsToExcel()" class="px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1">
                        📥 Xuất Excel
                    </button>
                </div>
                <div class="table-container">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-light-border dark:border-zeus-border">
                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Kỳ</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Tổng buổi</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thành công</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Noshow</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">&lt; 1/2 giờ</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Tỉ lệ TC</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">HV hoạt động</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🟢</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🟡</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🔴</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">🎯 Ontrack</th>
                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thay đổi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in trendData" :key="i">
                                <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt/50 dark:hover:bg-zeus-card-light/50">
                                    <td class="py-2 px-3 font-medium text-light-text dark:text-zeus-text whitespace-nowrap" x-text="row.period"></td>
                                    <td class="py-2 px-3 text-right text-light-text dark:text-zeus-text" x-text="fmt(row.total_scheduled)"></td>
                                    <td class="py-2 px-3 text-right text-emerald-500 font-medium" x-text="fmt(row.total_success)"></td>
                                    <td class="py-2 px-3 text-right text-red-400" x-text="fmt(row.total_noshow)"></td>
                                    <td class="py-2 px-3 text-right text-orange-400" x-text="fmt(row.total_half)"></td>
                                    <td class="py-2 px-3 text-right" :class="row.success_rate >= 80 ? 'text-emerald-500' : row.success_rate >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="row.success_rate + '%'"></td>
                                    <td class="py-2 px-3 text-right text-blue-500" x-text="fmt(row.unique_students)"></td>
                                    <td class="py-2 px-3 text-right text-emerald-500 font-medium" x-text="fmt(getHealthTrendByPeriod(row.period, 'green'))"></td>
                                    <td class="py-2 px-3 text-right text-yellow-500 font-medium" x-text="fmt(getHealthTrendByPeriod(row.period, 'yellow'))"></td>
                                    <td class="py-2 px-3 text-right text-red-500 font-medium" x-text="fmt(getHealthTrendByPeriod(row.period, 'red'))"></td>
                                    <td class="py-2 px-3 text-right text-cyan-500 font-medium">
                                        <span x-text="getTableOntrackRate(row.period) + '%'"></span>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + fmt(getTableOntrackBySuccess(row.period)) + '/' + fmt(row.unique_students) + ')'"></span>
                                    </td>
                                    <td class="py-2 px-3 text-right text-xs whitespace-nowrap">
                                        <template x-if="i > 0">
                                            <span :class="trendChange(i, 'success_rate') >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                                <span x-text="trendChange(i, 'success_rate') >= 0 ? '▲' : '▼'"></span>
                                                <span x-text="Math.abs(trendChange(i, 'success_rate')).toFixed(1) + '%'"></span>
                                            </span>
                                        </template>
                                        <template x-if="i === 0">
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
    </div>

    {{-- EWS (Early Warning) Section --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border" x-data="{ showEws: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="showEws = !showEws; if(showEws && ewsData.data.length === 0 && !ewsLoading) loadEws()">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                🚨 Cảnh báo sớm (EWS) - HV nghỉ liên tiếp
                <span x-show="ewsData.total > 0" class="text-xs font-normal px-2 py-0.5 rounded-full bg-red-500/20 text-red-600 dark:text-red-400" x-text="fmt(ewsData.total) + ' HV'"></span>
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">cảnh báo sớm (Early Warning System)</span><br>Danh sách HV có buổi nghỉ liên tiếp (noshow/học dưới nửa giờ), sắp xếp theo số buổi nghỉ giảm dần.<br>Dữ liệu được tính từ chuỗi nghỉ liên tiếp gần nhất của từng HV.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras, tbl_users</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id, ordles_lesson_starttime
  FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
), ranked AS (
  SELECT l.ordles_beneficiary_id AS uid,
    CASE WHEN e.ole_acceptance_code IN (3,6,9,12) THEN 0 ELSE 1 END AS is_missed,
    ROW_NUMBER() OVER (PARTITION BY l.ordles_beneficiary_id
      ORDER BY l.ordles_lesson_starttime DESC) AS rn
  FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
), first_ok AS (
  SELECT uid, MIN(rn) AS first_ok_rn FROM ranked
  WHERE is_missed = 0 GROUP BY uid
), totals AS (
  SELECT uid, COUNT(*) AS total FROM ranked GROUP BY uid
)
SELECT t.uid AS student_id,
  COALESCE(f.first_ok_rn, t.total + 1) - 1 AS total_missed
FROM totals t LEFT JOIN first_ok f ON t.uid = f.uid
HAVING total_missed > 0
ORDER BY total_missed DESC</span></span></span>
            </h3>
            <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted transition-transform" :class="{ 'rotate-180': showEws }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="showEws" x-collapse class="mt-4">
            {{-- EWS Filters --}}
            <div class="flex flex-wrap items-end gap-3 mb-4">
                {{-- EWS Search --}}
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        🔎 Tìm HV (ID / Tên / SĐT / Email)
                    </label>
                    <div class="relative">
                        <input type="text" x-model="ewsFilters.search" @input.debounce.400ms="ewsCurrentPage = 1; loadEws()" placeholder="Nhập ID, tên, SĐT hoặc email..."
                            class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 pr-8 focus:ring-2 focus:ring-zeus-accent">
                        <button x-show="ewsFilters.search" @click="ewsFilters.search = ''; ewsCurrentPage = 1; loadEws()" class="absolute right-2 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted hover:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- EWS CSS Staff Filter --}}
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        👤 Chuyên viên CSS
                    </label>
                    <select x-model="ewsFilters.css_staff" @change="ewsCurrentPage = 1; loadEws()" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                        <option value="">Tất cả</option>
                        @foreach($cssStaffList as $staff)
                        <option value="{{ $staff }}">{{ $staff }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- EWS Min Missed Filter --}}
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        🔢 Số buổi nghỉ LT ≥
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">lọc số buổi nghỉ liên tiếp</span><br>Chỉ hiển thị HV có số buổi nghỉ liên tiếp lớn hơn hoặc bằng giá trị được chọn.</span></span>
                    </label>
                    <select x-model="ewsFilters.min_missed" @change="ewsCurrentPage = 1; loadEws()" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                        <option value="0">Tất cả</option>
                        <option value="2">≥ 2 buổi</option>
                        <option value="3">≥ 3 buổi</option>
                        <option value="5">≥ 5 buổi</option>
                        <option value="7">≥ 7 buổi</option>
                        <option value="10">≥ 10 buổi</option>
                    </select>
                </div>

                {{-- Reset EWS Filters --}}
                <div>
                    <button @click="ewsFilters = { search: '', css_staff: '', min_missed: '0' }; ewsCurrentPage = 1; loadEws()" class="px-4 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text border border-light-border dark:border-zeus-border rounded-lg transition">
                        Xóa bộ lọc
                    </button>
                </div>

                {{-- Export EWS --}}
                <div>
                    <button @click="exportEwsToExcel()" :disabled="ewsExporting" class="px-4 py-2 text-sm bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1">
                        <span x-show="!ewsExporting">📥 Xuất Excel</span>
                        <span x-show="ewsExporting" class="inline-flex items-center gap-1"><span class="spinner-inline spinner-sm"></span> Đang xuất...</span>
                    </button>
                </div>
            </div>

            {{-- EWS Explanation Box --}}
            <div class="mb-4 p-3 rounded-lg bg-red-500/5 dark:bg-red-500/10 border border-red-500/20 text-xs text-light-text-muted dark:text-zeus-text-muted leading-relaxed">
                <p class="font-semibold text-red-600 dark:text-red-400 mb-1">📖 Cách tính số buổi nghỉ liên tiếp</p>
                <p>Hệ thống xét <strong class="text-light-text dark:text-zeus-text">từ buổi học gần nhất</strong> trở về trước. Mỗi buổi được đánh dấu là <strong class="text-light-text dark:text-zeus-text">nghỉ</strong> nếu HV không đến (noshow) hoặc học dưới nửa giờ (halftime) hoặc chưa có dữ liệu ClassIn. Hệ thống đếm số buổi nghỉ liên tiếp cho đến khi gặp buổi học thành công đầu tiên thì dừng.</p>
                <p class="mt-1"><strong class="text-light-text dark:text-zeus-text">Ví dụ:</strong> HV có 5 buổi gần nhất theo thứ tự mới→cũ: <span class="text-red-500 font-medium">nghỉ</span>, <span class="text-red-500 font-medium">nghỉ</span>, <span class="text-red-500 font-medium">nghỉ</span>, <span class="text-emerald-500 font-medium">đi học</span>, <span class="text-red-500 font-medium">nghỉ</span> → Số buổi nghỉ LT = <strong class="text-red-500">3</strong> (chỉ đếm chuỗi nghỉ liên tiếp gần nhất, dừng khi gặp buổi đi học).</p>
            </div>

            <template x-if="ewsData.data.length === 0 && !ewsLoading">
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Không tìm thấy dữ liệu EWS phù hợp.</p>
            </template>
            <template x-if="ewsLoading">
                <div class="flex items-center gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline spinner-sm"></span> Đang tải...
                </div>
            </template>
            <div class="table-container" x-show="ewsData.data.length > 0 && !ewsLoading">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-light-border dark:border-zeus-border">
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleEwsSort('student_id')">
                                ID HV <span x-text="ewsSortIndicator('student_id')"></span>
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleEwsSort('student_name')">
                                Tên <span x-text="ewsSortIndicator('student_name')"></span>
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">SĐT</th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Email</th>
                            <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleEwsSort('total_missed')">
                                Buổi nghỉ LT
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">buổi nghỉ liên tiếp</span><br>Số buổi nghỉ liên tiếp tính từ buổi gần nhất trở về trước.<br>Nghỉ = noshow / halftime / chưa có data ClassIn.<br>Đếm dừng khi gặp buổi học thành công.</span></span>
                                <span x-text="ewsSortIndicator('total_missed')"></span>
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleEwsSort('last_success_time')">
                                Buổi TC gần nhất
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">buổi thành công gần nhất</span><br>Thời gian buổi học thành công gần nhất của HV.<br>Buổi thành công: ole_acceptance_code IN (3, 6, 9, 12).</span></span>
                                <span x-text="ewsSortIndicator('last_success_time')"></span>
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleEwsSort('css_staff')">
                                CSS <span x-text="ewsSortIndicator('css_staff')"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(s, i) in ewsData.data" :key="i">
                            <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-red-500/5 cursor-pointer"
                                @click="openEwsDetail(s.student_id, s.student_name, s.total_missed)"
                                title="Click để xem chi tiết buổi nghỉ">
                                <td class="py-2 px-3 font-mono text-xs text-light-text dark:text-zeus-text" x-text="s.student_id"></td>
                                <td class="py-2 px-3 text-light-text dark:text-zeus-text" x-text="s.student_name"></td>
                                <td class="py-2 px-3 text-light-text-muted dark:text-zeus-text-muted" x-text="s.phone"></td>
                                <td class="py-2 px-3 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="s.email"></td>
                                <td class="py-2 px-3 text-right font-bold text-red-500" x-text="s.total_missed"></td>
                                <td class="py-2 px-3 text-xs whitespace-nowrap" :class="s.last_success_time ? 'text-emerald-500' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.last_success_time ? formatDateTime(s.last_success_time) : 'Chưa có'"></td>
                                <td class="py-2 px-3 text-light-text-muted dark:text-zeus-text-muted" x-text="s.css_staff"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- EWS Pagination --}}
            <div class="flex items-center justify-between mt-4 text-xs text-light-text-muted dark:text-zeus-text-muted" x-show="ewsData.total_pages > 1 && !ewsLoading">
                <span x-text="'Hiển thị ' + ((ewsData.page - 1) * ewsPerPage + 1) + '-' + Math.min(ewsData.page * ewsPerPage, ewsData.total) + ' / ' + ewsData.total + ' HV'"></span>
                <div class="flex items-center gap-1">
                    <button @click="ewsCurrentPage = 1; loadEws()" :disabled="ewsData.page <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏮</button>
                    <button @click="ewsCurrentPage--; loadEws()" :disabled="ewsData.page <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">← Trước</button>
                    <span class="px-2" x-text="'Trang ' + ewsData.page + '/' + ewsData.total_pages"></span>
                    <button @click="ewsCurrentPage++; loadEws()" :disabled="ewsData.page >= ewsData.total_pages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">Sau →</button>
                    <button @click="ewsCurrentPage = ewsData.total_pages; loadEws()" :disabled="ewsData.page >= ewsData.total_pages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏭</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Data Table --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                👨‍🎓 Danh sách Học viên
                <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + fmt(studentData.total) + ' HV)'"></span>
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">danh sách học viên</span><br>Bảng chi tiết từng HV với thông tin: buổi hoàn thành, thành công, noshow, dưới 1/2 giờ, điểm sức khỏe, phân loại, CSS phụ trách, cảnh báo GV.<br>Sử dụng bộ lọc bên dưới hoặc bộ lọc chung phía trên để tìm kiếm/lọc theo nhóm rủi ro, CSS, cảnh báo GV, hoặc tra cứu theo ID/tên/SĐT/email.<br>Click vào từng HV để xem chi tiết buổi học.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras, tbl_users, tbl_user_settings, tbl_user_extras, tbl_admin</span><br><br><span class="tooltip-sql">WITH les AS (
  SELECT ordles_id, ordles_beneficiary_id, ordles_lesson_starttime
  FROM tbl_order_lessons
  WHERE ordles_beneficiary_id IS NOT NULL AND ordles_beneficiary_id > 0
    AND ordles_status = 3
    AND ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
    AND ordles_lesson_starttime > '2025-11-04'
    AND ordles_lesson_starttime <= NOW()
), ext AS (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
)
SELECT l.ordles_beneficiary_id AS student_id,
  CONCAT(COALESCE(u.user_last_name,''),' ',COALESCE(u.user_first_name,'')) AS student_name,
  u.user_email, MAX(us.user_phone_number) AS phone,
  MAX(a.admin_username) AS css_staff,
  COUNT(*) AS total_scheduled,
  SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) AS total_success,
  SUM(CASE WHEN e.ole_acceptance_code IN (0,4,7,10)
    OR e.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS student_noshow,
  SUM(CASE WHEN e.ole_acceptance_code IN (2,5,8,11) THEN 1 ELSE 0 END) AS student_half,
  ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
    THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score
FROM les l LEFT JOIN ext e ON e.ole_ordles_id = l.ordles_id
INNER JOIN tbl_users u ON l.ordles_beneficiary_id = u.user_id
LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
LEFT JOIN tbl_admin a ON ue.usrextra_css_id = a.admin_id
GROUP BY l.ordles_beneficiary_id, u.user_last_name, u.user_first_name, u.user_email
ORDER BY health_score ASC</span></span></span>
            </h3>
            <div class="flex items-center gap-2 text-xs text-light-text-muted dark:text-zeus-text-muted">
                <button @click="exportStudentsToExcel()" :disabled="studentsExporting" class="px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1">
                    <span x-show="!studentsExporting">📥 Xuất Excel</span>
                    <span x-show="studentsExporting" class="inline-flex items-center gap-1"><span class="spinner-inline spinner-sm"></span> Đang xuất...</span>
                </button>
                <span class="text-light-border dark:text-zeus-border">|</span>
                <span>Trang</span>
                <button @click="prevPage()" :disabled="studentData.page <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">←</button>
                <span x-text="studentData.page + '/' + studentData.total_pages"></span>
                <button @click="nextPage()" :disabled="studentData.page >= studentData.total_pages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">→</button>
                <select x-model="perPage" @change="currentPage = 1; loadStudents()" class="text-xs rounded border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light px-2 py-1">
                    <option value="25">25/trang</option>
                    <option value="50">50/trang</option>
                    <option value="100">100/trang</option>
                </select>
            </div>
        </div>

        {{-- Student Table Filters --}}
        <div class="flex flex-wrap items-end gap-3 mb-4 p-3 rounded-lg bg-light-card-alt/50 dark:bg-zeus-card-light/50 border border-light-border/50 dark:border-zeus-border/50">
            {{-- Search --}}
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🔎 Tìm HV (ID / Tên / SĐT / Email)
                </label>
                <div class="relative">
                    <input type="text" x-model="studentFilters.search" @keydown.enter="currentPage = 1; loadStudents()" placeholder="Nhập ID, tên, SĐT hoặc email..."
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 pr-8 focus:ring-2 focus:ring-zeus-accent">
                    <button x-show="studentFilters.search" @click="studentFilters.search = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted hover:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Health Category --}}
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🔍 Nhóm rủi ro
                </label>
                <select x-model="studentFilters.health_category" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="red_yellow">🔴🟡 Đỏ và Vàng</option>
                    <option value="red">🔴 Đỏ (Báo động)</option>
                    <option value="yellow">🟡 Vàng (Cảnh báo)</option>
                    <option value="green">🟢 Xanh (Khỏe mạnh)</option>
                </select>
            </div>

            {{-- CSS Staff --}}
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    👤 Chuyên viên CSS
                </label>
                <select x-model="studentFilters.css_staff" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    @foreach($cssStaffList as $staff)
                    <option value="{{ $staff }}">{{ $staff }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Teacher Warning --}}
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    ⚠️ Cảnh báo GV
                </label>
                <select x-model="studentFilters.teacher_warning" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="has_warning">Có cảnh báo</option>
                    <option value="Bình thường">Bình thường</option>
                    <option value="Có ảnh hưởng (GV nghỉ 1b)">Có ảnh hưởng (GV nghỉ 1b)</option>
                    <option value="Nghiêm trọng (GV nghỉ >=2b)">Nghiêm trọng (GV nghỉ >=2b)</option>
                    <option value="Khẩn cấp (GV nghỉ >= 4 buổi)">Khẩn cấp (GV nghỉ >= 4 buổi)</option>
                </select>
            </div>

            {{-- Ontrack Status (Phase 202) --}}
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🎯 Ontrack
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">bộ lọc ontrack</span><br>Lọc HV theo trạng thái ontrack (tỉ lệ thành công ≥ 90%).</span></span>
                </label>
                <select x-model="studentFilters.ontrack_status" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="ontrack">🎯 Ontrack (≥ 90%)</option>
                    <option value="not_ontrack">⚠️ Chưa ontrack (< 90%)</option>
                </select>
            </div>

            {{-- Phase 220: Program Filter --}}
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                    🗣️ Chương trình
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">bộ lọc chương trình</span><br>Lọc HV theo chương trình đang theo học: SPEAKWELL hoặc EASYSPEAK.</span></span>
                </label>
                <select x-model="studentFilters.program" class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                    <option value="">Tất cả</option>
                    <option value="SPEAKWELL">SPEAKWELL</option>
                    <option value="EASYSPEAK">EASYSPEAK</option>
                </select>
            </div>

            {{-- Lesson 1 Date From & To (same row) --}}
            <div class="flex items-end gap-2 min-w-[320px]">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        📅 Buổi 1 từ ngày
                    </label>
                    <input type="date" x-model="studentFilters.lesson_1_from"
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        📅 Buổi 1 đến ngày
                    </label>
                    <input type="date" x-model="studentFilters.lesson_1_to"
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                </div>
            </div>

            {{-- First 3 Sessions Date Range Filter --}}
            <div class="flex items-end gap-2 min-w-[320px]">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        📅 3BĐ từ ngày
                    </label>
                    <input type="date" x-model="studentFilters.first_3_from"
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider mb-1">
                        📅 3BĐ đến ngày
                    </label>
                    <input type="date" x-model="studentFilters.first_3_to"
                        class="w-full text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text px-3 py-2 focus:ring-2 focus:ring-zeus-accent">
                </div>
            </div>

            {{-- Apply Student Filter Button --}}
            <div>
                <button @click="currentPage = 1; loadStudents()" class="px-4 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent/90 transition font-medium">
                    🔍 Lọc
                </button>
            </div>

            {{-- Reset Student Filters --}}
            <div>
                <button @click="studentFilters = { search: '', health_category: '', css_staff: '', teacher_warning: '', ontrack_status: '', program: '', lesson_1_from: '', lesson_1_to: '', first_3_from: '', first_3_to: '' }; currentPage = 1; loadStudents()" class="px-4 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text border border-light-border dark:border-zeus-border rounded-lg transition">
                    Xóa bộ lọc
                </button>
            </div>
        </div>

        {{-- Loading indicator --}}
        <template x-if="tableLoading">
            <div class="flex items-center justify-center py-8 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                <span class="spinner-inline"></span> Đang tải dữ liệu...
            </div>
        </template>

        {{-- Table --}}
        <div class="table-container" x-show="!tableLoading">
            <table class="w-full text-sm min-w-[1920px]">
                <thead>
                    <tr class="border-b-2 border-light-border dark:border-zeus-border">
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_id')">
                            ID HV <span x-text="sortIndicator('student_id')"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_name')">
                            Tên <span x-text="sortIndicator('student_name')"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">SĐT</th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('total_scheduled')">
                            XL <span x-text="sortIndicator('total_scheduled')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('total_success')">
                            TC <span x-text="sortIndicator('total_success')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_noshow')">
                            NS <span x-text="sortIndicator('student_noshow')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('student_half')">
                            ½ <span x-text="sortIndicator('student_half')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('health_score')">
                            Điểm <span x-text="sortIndicator('health_score')"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Phân loại</th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('css_staff')">
                            CSS <span x-text="sortIndicator('css_staff')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('teacher_noshow')">
                            GV NS <span x-text="sortIndicator('teacher_noshow')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('leave_sessions')">
                            GVNP
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">GV nghỉ phép (GVNP)</span><br>Số buổi học bị ảnh hưởng do Giáo viên xin nghỉ phép (đã duyệt) trong giai đoạn lọc.<br>Bảng: <span class="tooltip-table">tbl_teacher_leave_requests, tbl_teacher_leave_request_sessions</span><br><br><span class="tooltip-sql">SELECT COUNT(*) AS leave_sessions
FROM tbl_teacher_leave_requests lr
INNER JOIN tbl_teacher_leave_request_sessions lrs
  ON lr.tlr_id = lrs.tlrs_leave_request_id
WHERE lr.tlr_status IN (2, 3)
  AND JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id') = &lt;student_id&gt;
  AND lrs.tlrs_session_date BETWEEN '...' AND '...'</span></span></span>
                            <span x-text="sortIndicator('leave_sessions')"></span>
                        </th>
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">CB GV</th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('success_rate')">
                            %TC <span x-text="sortIndicator('success_rate')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('avg_per_week')">
                            TB/tuần
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">trung bình buổi / tuần</span><br>Số buổi thành công trung bình mỗi tuần của HV trong giai đoạn lọc.<br>Công thức: Buổi thành công / Tổng số tuần.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span></span></span>
                            <span x-text="sortIndicator('avg_per_week')"></span>
                        </th>

                        <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('lesson_1_date')">
                            Buổi 1
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">buổi học đầu tiên</span><br>Ngày và trạng thái buổi học đầu tiên <strong>sau Trial</strong> (sau khi đóng tiền) của HV.<br>Buổi Trial (ordles_tlang_id = CONF_TRIAL_SUBJECT_ID) không được tính.</span></span>
                            <span x-text="sortIndicator('lesson_1_date')"></span>
                        </th>
                        <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('lesson_2_date')">
                            Buổi 2
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">buổi học thứ 2</span><br>Ngày và trạng thái buổi học thứ 2 của HV.</span></span>
                            <span x-text="sortIndicator('lesson_2_date')"></span>
                        </th>
                        <th class="text-center py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('lesson_3_date')">
                            Buổi 3
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">buổi học thứ 3</span><br>Ngày và trạng thái buổi học thứ 3 của HV.</span></span>
                            <span x-text="sortIndicator('lesson_3_date')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('first_3_success_rate')">
                            %3BĐ
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tỉ lệ thành công 3 buổi đầu</span><br>Tỉ lệ buổi học thành công trong 3 buổi đầu tiên <strong>sau Trial</strong> của HV.<br>Buổi thành công: ole_acceptance_code IN (9, 12).<br>Công thức: Số buổi TC / Tổng buổi (tối đa 3) × 100.<br>Buổi Trial không được tính vào 3 buổi đầu.</span></span>
                            <span x-text="sortIndicator('first_3_success_rate')"></span>
                        </th>
                        {{-- Phase 202: LCMS Metrics --}}
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('hw_completion_rate')">
                            BTVN%
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">tỉ lệ làm BTVN</span><br>Tỉ lệ hoàn thành Bài tập về nhà (BTVN) trên LCMS của HV.<br>Công thức: Số BTVN hoàn thành / Tổng BTVN × 100.<br>Dữ liệu từ: <span class="tooltip-table">lcms_user_assignments, lcms_courses</span></span></span>
                            <span x-text="sortIndicator('hw_completion_rate')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('hw_avg_score')">
                            TB BTVN
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">điểm trung bình BTVN</span><br>Điểm trung bình các Bài tập về nhà trên LCMS (thang 10).<br>Dữ liệu từ: <span class="tooltip-table">lcms_student_scores, lcms_courses</span></span></span>
                            <span x-text="sortIndicator('hw_avg_score')"></span>
                        </th>
                        <th class="text-right py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase cursor-pointer hover:text-light-text dark:hover:text-zeus-text" @click="toggleSort('test_avg_score')">
                            TB BKT
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">điểm trung bình bài kiểm tra</span><br>Điểm trung bình các Bài kiểm tra trên LCMS (thang 10).<br>Dữ liệu từ: <span class="tooltip-table">lcms_student_scores, lcms_courses</span></span></span>
                            <span x-text="sortIndicator('test_avg_score')"></span>
                        </th>
                        {{-- Phase 219: Program Names --}}
                        <th class="text-left py-2 px-2 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">
                            Chương trình
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">chương trình đang theo học</span><br>Tên các chương trình mà học viên đang theo học.<br>• <strong>SPEAKWELL</strong>: ordles_tlang_id IN (533, 558, 560, 562, ...)<br>• <strong>EASYSPEAK</strong>: ordles_tlang_id IN (403, 404, 471, 582, 583, 584, 585, 586)<br>Bảng: <span class="tooltip-table">tbl_order_lessons</span></span></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(s, i) in studentData.data" :key="i">
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt/50 dark:hover:bg-zeus-card-light/50 transition cursor-pointer"
                            :class="{ 'bg-red-500/5 dark:bg-red-500/10': s.health_category === 'Đỏ (Báo động)', 'bg-yellow-500/5 dark:bg-yellow-500/10': s.health_category === 'Vàng (Cảnh báo)', 'bg-emerald-500/5 dark:bg-emerald-500/10': s.health_category === 'Xanh (Khỏe mạnh)' }"
                            @click="openStudentDetail(s.student_id, s.student_name)"
                            title="Click để xem chi tiết học viên">
                            <td class="py-2 px-2 font-mono text-xs text-light-text dark:text-zeus-text" x-text="s.student_id"></td>
                            <td class="py-2 px-2 text-light-text dark:text-zeus-text font-medium whitespace-nowrap" x-text="s.student_name"></td>
                            <td class="py-2 px-2 text-light-text-muted dark:text-zeus-text-muted text-xs whitespace-nowrap" x-text="s.phone"></td>
                            <td class="py-2 px-2 text-right text-light-text dark:text-zeus-text" x-text="s.total_scheduled"></td>
                            <td class="py-2 px-2 text-right text-emerald-500 font-medium" x-text="s.total_success"></td>
                            <td class="py-2 px-2 text-right" :class="s.student_noshow > 0 ? 'text-red-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.student_noshow"></td>
                            <td class="py-2 px-2 text-right" :class="s.student_half > 0 ? 'text-orange-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.student_half"></td>
                            <td class="py-2 px-2 text-right font-bold" :class="s.health_score >= 85 ? 'text-emerald-500' : s.health_score >= 60 ? 'text-yellow-500' : 'text-red-500'" x-text="s.health_score + '%'"></td>
                            <td class="py-2 px-2 whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': s.health_category === 'Xanh (Khỏe mạnh)',
                                        'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400': s.health_category === 'Vàng (Cảnh báo)',
                                        'bg-red-500/20 text-red-600 dark:text-red-400': s.health_category === 'Đỏ (Báo động)',
                                        'bg-gray-500/20 text-gray-600 dark:text-gray-400': s.health_category === 'Chưa có lớp'
                                    }"
                                    x-text="s.health_category"></span>
                            </td>
                            <td class="py-2 px-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="s.css_staff || '—'"></td>
                            <td class="py-2 px-2 text-right" :class="s.teacher_noshow > 0 ? 'text-purple-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.teacher_noshow"></td>
                            <td class="py-2 px-2 text-right" :class="s.leave_sessions > 0 ? 'text-pink-500 font-medium' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.leave_sessions"></td>
                            <td class="py-2 px-2 text-xs whitespace-nowrap">
                                <span :class="{
                                    'text-light-text-muted dark:text-zeus-text-muted': s.teacher_warning === 'Bình thường',
                                    'text-yellow-500': s.teacher_warning && s.teacher_warning.includes('1b'),
                                    'text-orange-500': s.teacher_warning && s.teacher_warning.includes('>=2b'),
                                    'text-red-500 font-medium': s.teacher_warning && s.teacher_warning.includes('>= 4')
                                }" x-text="s.teacher_warning === 'Bình thường' ? '—' : s.teacher_warning"></span>
                            </td>
                            <td class="py-2 px-2 text-right" :class="parseFloat(s.success_rate) >= 0.8 ? 'text-emerald-500' : parseFloat(s.success_rate) >= 0.6 ? 'text-yellow-500' : 'text-red-400'" x-text="(parseFloat(s.success_rate) * 100).toFixed(0) + '%'"></td>
                            <td class="py-2 px-2 text-right font-medium text-blue-500" x-text="s.avg_per_week !== null && s.avg_per_week !== undefined ? s.avg_per_week : '—'"></td>

                            {{-- First 3 Lessons --}}
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <template x-if="s.lesson_1_date">
                                    <div>
                                        <div class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="formatShortDate(s.lesson_1_date)"></div>
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium"
                                            :class="acceptanceCodeClass(s.lesson_1_code)"
                                            x-text="acceptanceCodeLabel(s.lesson_1_code)"></span>
                                    </div>
                                </template>
                                <template x-if="!s.lesson_1_date">
                                    <span class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <template x-if="s.lesson_2_date">
                                    <div>
                                        <div class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="formatShortDate(s.lesson_2_date)"></div>
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium"
                                            :class="acceptanceCodeClass(s.lesson_2_code)"
                                            x-text="acceptanceCodeLabel(s.lesson_2_code)"></span>
                                    </div>
                                </template>
                                <template x-if="!s.lesson_2_date">
                                    <span class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <template x-if="s.lesson_3_date">
                                    <div>
                                        <div class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="formatShortDate(s.lesson_3_date)"></div>
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium"
                                            :class="acceptanceCodeClass(s.lesson_3_code)"
                                            x-text="acceptanceCodeLabel(s.lesson_3_code)"></span>
                                    </div>
                                </template>
                                <template x-if="!s.lesson_3_date">
                                    <span class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-2 text-right font-medium" :class="s.first_3_success_rate !== null ? (s.first_3_success_rate >= 67 ? 'text-emerald-500' : s.first_3_success_rate >= 33 ? 'text-yellow-500' : 'text-red-500') : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.first_3_success_rate !== null ? s.first_3_success_rate + '%' : '—'"></td>
                            {{-- Phase 202: LCMS Metrics --}}
                            <td class="py-2 px-2 text-right text-xs" :class="s.hw_completion_rate !== null ? (s.hw_completion_rate >= 80 ? 'text-emerald-500 font-medium' : s.hw_completion_rate >= 50 ? 'text-yellow-500' : 'text-red-400') : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.hw_completion_rate !== null ? s.hw_completion_rate + '%' : '—'"></td>
                            <td class="py-2 px-2 text-right text-xs" :class="s.hw_avg_score !== null ? (s.hw_avg_score >= 8 ? 'text-emerald-500 font-medium' : s.hw_avg_score >= 5 ? 'text-yellow-500' : 'text-red-400') : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.hw_avg_score !== null ? s.hw_avg_score : '—'"></td>
                            <td class="py-2 px-2 text-right text-xs" :class="s.test_avg_score !== null ? (s.test_avg_score >= 8 ? 'text-emerald-500 font-medium' : s.test_avg_score >= 5 ? 'text-yellow-500' : 'text-red-400') : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="s.test_avg_score !== null ? s.test_avg_score : '—'"></td>
                            {{-- Phase 219: Program Names --}}
                            <td class="py-2 px-2 text-left text-xs text-light-text dark:text-zeus-text whitespace-nowrap" x-text="s.course_names || '—'" :title="s.course_names || ''"></td>
                        </tr>
                    </template>
                    <template x-if="studentData.data && studentData.data.length === 0">
                        <tr>
                            <td colspan="23" class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">
                                Không tìm thấy học viên nào phù hợp bộ lọc.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Column Legend --}}
        <div class="mt-3 flex flex-wrap gap-3 text-[10px] text-light-text-muted dark:text-zeus-text-muted">
            <span><strong>XL</strong> = Hoàn thành</span>
            <span><strong>TC</strong> = Thành công</span>
            <span><strong>NS</strong> = Noshow</span>
            <span><strong>½</strong> = Học &lt; 1/2 giờ</span>
            <span><strong>GV NS</strong> = GV Noshow</span>
            <span><strong>CB GV</strong> = Cảnh báo GV</span>
            <span><strong>%TC</strong> = Tỉ lệ thành công</span>
            <span><strong>TB/tuần</strong> = TB buổi TC / tuần</span>
            <span><strong>Buổi 1/2/3</strong> = 3 buổi đầu tiên sau Trial</span>
            <span><strong>%3BĐ</strong> = %TC 3 buổi đầu</span>
            <span><strong>3BĐ từ/đến</strong> = Lọc HV đã hoàn thành 3 buổi đầu trong khoảng thời gian</span>
            <span><strong>BTVN%</strong> = Tỉ lệ làm BTVN (LCMS)</span>
            <span><strong>TB BTVN</strong> = Điểm TB BTVN (LCMS)</span>
            <span><strong>TB BKT</strong> = Điểm TB Bài kiểm tra (LCMS)</span>
            <span><strong>Chương trình</strong> = Chương trình đang theo học (SPEAKWELL / EASYSPEAK)</span>
        </div>

        {{-- Bottom Pagination --}}
        <div class="flex items-center justify-between mt-4 text-xs text-light-text-muted dark:text-zeus-text-muted" x-show="studentData.total_pages > 1">
            <span x-text="'Hiển thị ' + ((studentData.page - 1) * perPage + 1) + '-' + Math.min(studentData.page * perPage, studentData.total) + ' / ' + studentData.total + ' HV'"></span>
            <div class="flex items-center gap-1">
                <button @click="currentPage = 1; loadStudents()" :disabled="studentData.page <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏮</button>
                <button @click="prevPage()" :disabled="studentData.page <= 1" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">← Trước</button>
                <span class="px-2" x-text="'Trang ' + studentData.page + '/' + studentData.total_pages"></span>
                <button @click="nextPage()" :disabled="studentData.page >= studentData.total_pages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">Sau →</button>
                <button @click="currentPage = studentData.total_pages; loadStudents()" :disabled="studentData.page >= studentData.total_pages" class="px-2 py-1 rounded border border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light disabled:opacity-30 transition">⏭</button>
            </div>
        </div>
    </div>

    {{-- Rules Reference --}}
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border" x-data="{ showRules: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="showRules = !showRules">
            <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                📋 Quy tắc tính điểm Sức khỏe HV
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">quy tắc CSI</span><br>Công thức tính điểm sức khỏe HV dựa trên dữ liệu nghiệm thu buổi học từ Zeus Core.<br>Bảng: <span class="tooltip-table">tbl_order_lessons, tbl_order_lessons_extras</span><br><br><span class="tooltip-sql">SELECT l.ordles_beneficiary_id AS student_id,
  -- student noshow: ole_acceptance_code IN (0,4,7,10) or NULL
  SUM(CASE WHEN e.ole_acceptance_code IN (0,4,7,10)
    OR e.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS student_noshow,
  -- student half: ole_acceptance_code IN (2,5,8,11)
  SUM(CASE WHEN e.ole_acceptance_code IN (2,5,8,11) THEN 1 ELSE 0 END) AS student_half,
  -- health_score = buổi thành công / tổng buổi * 100
  ROUND(SUM(CASE WHEN e.ole_acceptance_code IN (3,6,9,12)
    THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score,
  -- teacher noshow: ole_acceptance_code IN (0,2,3) or NULL
  SUM(CASE WHEN e.ole_acceptance_code IN (0,2,3)
    OR e.ole_acceptance_code IS NULL THEN 1 ELSE 0 END) AS teacher_noshow
FROM tbl_order_lessons l
LEFT JOIN (
  SELECT ole_ordles_id, ole_acceptance_code FROM (
    SELECT ole_ordles_id, ole_acceptance_code,
      ROW_NUMBER() OVER (PARTITION BY ole_ordles_id ORDER BY ole_id) rn
    FROM tbl_order_lessons_extras) t WHERE rn = 1
) e ON e.ole_ordles_id = l.ordles_id
WHERE l.ordles_beneficiary_id IS NOT NULL AND l.ordles_beneficiary_id > 0
  AND l.ordles_status = 3
  AND l.ordles_tlang_id IN (533,558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
  AND l.ordles_lesson_starttime > '2025-11-04'
  AND l.ordles_lesson_starttime <= NOW()
GROUP BY l.ordles_beneficiary_id
-- 🟢 xanh: health_score >= 85 (tỉ lệ thành công ≥ 85%)
-- 🟡 vàng: health_score >= 60 AND < 85 (60-84%)
-- 🔴 đỏ: health_score < 60 (dưới 60%)
-- cảnh báo GV: teacher_noshow 0=bình thường, 1=ảnh hưởng, >=2=nghiêm trọng, >=4=khẩn cấp</span></span></span>
            </h3>
            <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted transition-transform" :class="{ 'rotate-180': showRules }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="showRules" x-collapse class="mt-4 space-y-4 text-sm text-light-text dark:text-zeus-text">
            <div>
                <h4 class="font-medium mb-2">Công thức tính điểm sức khỏe:</h4>
                <ul class="space-y-1 text-light-text-muted dark:text-zeus-text-muted list-disc list-inside">
                    <li>Điểm sức khỏe = <span class="text-emerald-400 font-medium">Số buổi thành công / Tổng buổi hoàn thành × 100</span></li>
                    <li>Buổi thành công: HV vào học bình thường (ole_acceptance_code IN 3, 6, 9, 12)</li>
                    <li>VD: HV có 4 buổi, thành công 3 buổi → Điểm = <span class="text-emerald-400 font-medium">75%</span></li>
                    <li>GV không vào: <span class="text-purple-400 font-medium">Không ảnh hưởng điểm</span> nhưng đếm trong cảnh báo trải nghiệm GV</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Phân loại rủi ro:</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                        <p class="font-medium text-emerald-500">🟢 Xanh (85-100%)</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Khỏe mạnh. HV đi học đều. CSS: Gửi email/Zalo khích lệ.</p>
                    </div>
                    <div class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                        <p class="font-medium text-yellow-500">🟡 Vàng (60-84%)</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Cảnh báo rủi ro. CSS: Gọi điện tìm hiểu nguyên nhân.</p>
                    </div>
                    <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                        <p class="font-medium text-red-500">🔴 Đỏ (Dưới 60%)</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Báo động rớt lớp. CSS: Kích hoạt cứu vãn khẩn cấp.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- EWS Student Detail Modal --}}
    <div x-show="showEwsDetail" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEwsDetail = false"></div>
        <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[85vh] overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-red-500/5">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                    🔍 Chi tiết nghỉ liên tiếp
                    <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'— ' + ewsDetailStudentName + ' (ID: ' + ewsDetailStudentId + ')'"></span>
                </h3>
                <button @click="showEwsDetail = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                    <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 overflow-y-auto" style="max-height: calc(85vh - 120px);">
                {{-- Loading --}}
                <template x-if="ewsDetailLoading">
                    <div class="flex items-center justify-center py-12 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                        <span class="spinner-inline"></span> Đang tải dữ liệu...
                    </div>
                </template>

                <template x-if="!ewsDetailLoading && ewsDetailData.student">
                    <div class="space-y-5">
                        {{-- Student Info Cards --}}
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tổng buổi</p>
                                <p class="text-lg font-bold text-light-text dark:text-zeus-text" x-text="ewsDetailData.student.total_lessons"></p>
                            </div>
                            <div class="bg-emerald-500/5 rounded-lg p-3 border border-emerald-500/20 text-center">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                <p class="text-lg font-bold text-emerald-500" x-text="ewsDetailData.student.total_success"></p>
                            </div>
                            <div class="bg-red-500/5 rounded-lg p-3 border border-red-500/20 text-center">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Noshow</p>
                                <p class="text-lg font-bold text-red-500" x-text="ewsDetailData.student.total_noshow"></p>
                            </div>
                            <div class="bg-orange-500/5 rounded-lg p-3 border border-orange-500/20 text-center">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">&lt; 1/2 giờ</p>
                                <p class="text-lg font-bold text-orange-500" x-text="ewsDetailData.student.total_half"></p>
                            </div>
                            <div class="bg-emerald-500/5 rounded-lg p-3 border border-emerald-500/20 text-center">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Buổi TC gần nhất</p>
                                <p class="text-sm font-bold mt-1" :class="ewsDetailData.student.last_success_time ? 'text-emerald-500' : 'text-light-text-muted dark:text-zeus-text-muted'" x-text="ewsDetailData.student.last_success_time ? formatDateTime(ewsDetailData.student.last_success_time) : 'Chưa có'"></p>
                            </div>
                        </div>

                        {{-- Consecutive Streak Alert --}}
                        <div class="p-3 rounded-lg border text-sm flex items-center gap-3"
                             :class="ewsDetailData.consecutive_streak >= 5 ? 'bg-red-500/10 border-red-500/30' : ewsDetailData.consecutive_streak >= 3 ? 'bg-orange-500/10 border-orange-500/30' : 'bg-yellow-500/10 border-yellow-500/30'">
                            <span class="text-2xl" x-text="ewsDetailData.consecutive_streak >= 5 ? '🚨' : ewsDetailData.consecutive_streak >= 3 ? '⚠️' : '⚡'"></span>
                            <div>
                                <p class="font-semibold" :class="ewsDetailData.consecutive_streak >= 5 ? 'text-red-600 dark:text-red-400' : ewsDetailData.consecutive_streak >= 3 ? 'text-orange-600 dark:text-orange-400' : 'text-yellow-600 dark:text-yellow-400'">
                                    <span x-text="ewsDetailData.consecutive_streak"></span> buổi nghỉ liên tiếp gần nhất
                                </p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-0.5" x-text="ewsDetailData.consecutive_streak >= 5 ? 'Mức độ nghiêm trọng: RẤT CAO — Cần liên hệ HV khẩn cấp!' : ewsDetailData.consecutive_streak >= 3 ? 'Mức độ nghiêm trọng: CAO — Cần liên hệ HV sớm.' : 'Mức độ: TRUNG BÌNH — Theo dõi tiếp.'"></p>
                            </div>
                        </div>

                        {{-- Timeline Visualization --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📊 Bản đồ buổi học
                                <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted">(Trái = cũ nhất → Phải = mới nhất, viền đỏ = chuỗi nghỉ liên tiếp hiện tại)</span>
                            </h4>
                            {{-- Legend --}}
                            <div class="flex flex-wrap gap-3 mb-3 text-[10px] text-light-text-muted dark:text-zeus-text-muted">
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Thành công</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-500 inline-block"></span> Noshow</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-orange-400 inline-block"></span> &lt; 1/2 giờ</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-gray-400 inline-block"></span> Chưa có dữ liệu</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-purple-500 inline-block"></span> GV nghỉ phép</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm border-2 border-red-500 bg-transparent inline-block"></span> Chuỗi nghỉ LT</span>
                            </div>
                            {{-- Lesson blocks - chronological order (oldest first) --}}
                            <div class="flex flex-wrap gap-1.5" id="ewsTimelineBlocks">
                                <template x-for="(lesson, idx) in ewsDetailLessonsChronological" :key="idx">
                                    <div class="relative group">
                                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-md flex items-center justify-center text-[9px] font-bold transition-transform hover:scale-125 cursor-default"
                                             :class="{
                                                 'bg-purple-500 text-white': lesson.status === 'teacher_leave',
                                                 'bg-emerald-500 text-white': lesson.status === 'success' && !lesson._is_teacher_leave,
                                                 'bg-red-500 text-white': lesson.status === 'noshow' && !lesson._is_teacher_leave,
                                                 'bg-orange-400 text-white': lesson.status === 'half' && !lesson._is_teacher_leave,
                                                 'bg-gray-400 text-white': lesson.status === 'unknown' && !lesson._is_teacher_leave,
                                                 'ring-2 ring-purple-500 ring-offset-1 ring-offset-light-card-alt dark:ring-offset-zeus-card-light': lesson._is_teacher_leave && lesson.status !== 'teacher_leave',
                                                 'ring-2 ring-red-500 ring-offset-1 ring-offset-light-card-alt dark:ring-offset-zeus-card-light': lesson._in_streak && !lesson._is_teacher_leave
                                             }"
                                             :title="lesson.lesson_time + ' — ' + lesson.status_label + (lesson._is_teacher_leave ? ' (GV nghỉ phép)' : '')">
                                            <span x-text="lesson._short_date"></span>
                                        </div>
                                        {{-- Tooltip on hover --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
                                            <span x-text="lesson.lesson_time"></span><br>
                                            <span x-text="lesson.status_label"></span>
                                            <template x-if="lesson._is_teacher_leave">
                                                <span><br>🟣 GV nghỉ phép<span x-show="lesson._teacher_name"> — <span x-text="lesson._teacher_name"></span></span></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Monthly Heatmap Calendar --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📅 Lịch học theo tháng
                                <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted">(Ngày có buổi học được tô màu theo kết quả)</span>
                            </h4>
                            <div class="space-y-4" id="ewsCalendarContainer">
                                <template x-for="(month, mIdx) in ewsDetailMonths" :key="mIdx">
                                    <div>
                                        <p class="text-xs font-medium text-light-text dark:text-zeus-text mb-2" x-text="month.label"></p>
                                        {{-- Day-of-week headers --}}
                                        <div class="grid grid-cols-7 gap-1 mb-1">
                                            <template x-for="d in ['T2','T3','T4','T5','T6','T7','CN']" :key="d">
                                                <div class="text-[9px] text-center text-light-text-muted dark:text-zeus-text-muted font-medium" x-text="d"></div>
                                            </template>
                                        </div>
                                        {{-- Calendar days --}}
                                        <div class="grid grid-cols-7 gap-1">
                                            <template x-for="(cell, cIdx) in month.cells" :key="cIdx">
                                                <div class="relative group">
                                                    <div class="w-full aspect-square rounded-md flex items-center justify-center text-[10px] transition"
                                                         :class="{
                                                             'bg-transparent': cell.type === 'empty',
                                                             'bg-light-border/30 dark:bg-zeus-border/30 text-light-text-muted/50 dark:text-zeus-text-muted/50': cell.type === 'day' && !cell.lesson,
                                                             'bg-purple-500 text-white font-bold': cell.type === 'day' && cell.lesson && cell.lesson.status === 'teacher_leave',
                                                             'bg-emerald-500 text-white font-bold': cell.type === 'day' && cell.lesson && cell.lesson.status === 'success' && !cell.lesson._is_teacher_leave,
                                                             'bg-red-500 text-white font-bold': cell.type === 'day' && cell.lesson && cell.lesson.status === 'noshow' && !cell.lesson._is_teacher_leave,
                                                             'bg-orange-400 text-white font-bold': cell.type === 'day' && cell.lesson && cell.lesson.status === 'half' && !cell.lesson._is_teacher_leave,
                                                             'bg-gray-400 text-white font-bold': cell.type === 'day' && cell.lesson && cell.lesson.status === 'unknown' && !cell.lesson._is_teacher_leave,
                                                             'ring-2 ring-purple-500': cell.type === 'day' && cell.lesson && cell.lesson._is_teacher_leave && cell.lesson.status !== 'teacher_leave',
                                                             'ring-2 ring-red-500': cell.type === 'day' && cell.lesson && cell.lesson._in_streak && !cell.lesson._is_teacher_leave
                                                         }">
                                                        <span x-text="cell.day || ''" x-show="cell.type === 'day'"></span>
                                                    </div>
                                                    {{-- Day tooltip --}}
                                                    <div x-show="cell.type === 'day' && cell.lesson" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
                                                        <span x-text="cell.lesson ? (cell.lesson.lesson_time + ' — ' + cell.lesson.status_label + (cell.lesson._is_teacher_leave ? ' (GV nghỉ phép)' : '')) : ''"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Lesson Detail Table --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📋 Danh sách buổi học
                                <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + ewsDetailLessonsChronological.length + ' buổi)'"></span>
                            </h4>
                            <div class="table-container max-h-[300px] overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-light-card-alt dark:bg-zeus-card-light">
                                        <tr class="border-b border-light-border dark:border-zeus-border">
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">#</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thời gian</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Trạng thái</th>
                                            <th class="text-center py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">GV nghỉ phép</th>
                                            <th class="text-center py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thuộc chuỗi nghỉ LT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(lesson, idx) in ewsDetailLessonsChronological" :key="idx">
                                            <tr class="border-b border-light-border/30 dark:border-zeus-border/30"
                                                :class="{
                                                    'bg-purple-500/10': lesson._is_teacher_leave,
                                                    'bg-red-500/10': !lesson._is_teacher_leave && lesson._in_streak,
                                                }">
                                                <td class="py-1.5 px-3 text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="idx + 1"></td>
                                                <td class="py-1.5 px-3 text-xs text-light-text dark:text-zeus-text font-mono" x-text="lesson.lesson_time"></td>
                                                <td class="py-1.5 px-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                                          :class="{
                                                              'bg-purple-500/20 text-purple-600 dark:text-purple-400': lesson.status === 'teacher_leave',
                                                              'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': lesson.status === 'success',
                                                              'bg-red-500/20 text-red-600 dark:text-red-400': lesson.status === 'noshow',
                                                              'bg-orange-500/20 text-orange-600 dark:text-orange-400': lesson.status === 'half',
                                                              'bg-gray-500/20 text-gray-600 dark:text-gray-400': lesson.status === 'unknown',
                                                          }" x-text="lesson.status_label">
                                                    </span>
                                                </td>
                                                <td class="py-1.5 px-3 text-center">
                                                    <span x-show="lesson._is_teacher_leave" class="text-purple-500 font-bold text-xs">🟣 <span x-text="lesson._teacher_name || 'Có'"></span></span>
                                                    <span x-show="!lesson._is_teacher_leave" class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                                </td>
                                                <td class="py-1.5 px-3 text-center">
                                                    <span x-show="lesson._in_streak" class="text-red-500 font-bold text-xs">🔴 Có</span>
                                                    <span x-show="!lesson._in_streak" class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!ewsDetailLoading && !ewsDetailData.student">
                    <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không tìm thấy dữ liệu cho học viên này.</p>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                <button @click="showEwsDetail = false" class="w-full py-2 px-4 bg-zeus-accent hover:bg-zeus-accent-light text-white rounded-lg transition font-medium">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    {{-- Student Detail Modal --}}
    <div x-show="showStudentDetail" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showStudentDetail = false"></div>
        <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-5xl w-full max-h-[90vh] overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border"
                 :class="{
                     'bg-emerald-500/5': studentDetailData.student && studentDetailData.student.health_category === 'Xanh (Khỏe mạnh)',
                     'bg-yellow-500/5': studentDetailData.student && studentDetailData.student.health_category === 'Vàng (Cảnh báo)',
                     'bg-red-500/5': studentDetailData.student && studentDetailData.student.health_category === 'Đỏ (Báo động)'
                 }">
                <h3 class="text-sm font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider flex items-center gap-2">
                    👨‍🎓 Chi tiết Học viên
                    <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'— ' + studentDetailName + ' (ID: ' + studentDetailId + ')'"></span>
                </h3>
                <button @click="showStudentDetail = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                    <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 overflow-y-auto" style="max-height: calc(90vh - 120px);">
                {{-- Loading --}}
                <template x-if="studentDetailLoading">
                    <div class="flex items-center justify-center py-12 gap-2 text-sm text-light-text-muted dark:text-zeus-text-muted">
                        <span class="spinner-inline"></span> Đang tải dữ liệu...
                    </div>
                </template>

                <template x-if="!studentDetailLoading && studentDetailData.student">
                    <div class="space-y-5">
                        {{-- Student Info Row --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Email</p>
                                <p class="text-xs font-medium text-light-text dark:text-zeus-text mt-0.5 break-all" x-text="studentDetailData.student.email || '—'"></p>
                            </div>
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Điện thoại</p>
                                <p class="text-xs font-medium text-light-text dark:text-zeus-text mt-0.5" x-text="studentDetailData.student.phone || '—'"></p>
                            </div>
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Chuyên viên CSS</p>
                                <p class="text-xs font-medium text-light-text dark:text-zeus-text mt-0.5" x-text="studentDetailData.student.css_staff || '—'"></p>
                            </div>
                            <div class="rounded-lg p-3 border"
                                 :class="{
                                     'bg-emerald-500/10 border-emerald-500/20': studentDetailData.student.health_category === 'Xanh (Khỏe mạnh)',
                                     'bg-yellow-500/10 border-yellow-500/20': studentDetailData.student.health_category === 'Vàng (Cảnh báo)',
                                     'bg-red-500/10 border-red-500/20': studentDetailData.student.health_category === 'Đỏ (Báo động)'
                                 }">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Phân loại</p>
                                <p class="text-xs font-bold mt-0.5"
                                   :class="{
                                       'text-emerald-500': studentDetailData.student.health_category === 'Xanh (Khỏe mạnh)',
                                       'text-yellow-500': studentDetailData.student.health_category === 'Vàng (Cảnh báo)',
                                       'text-red-500': studentDetailData.student.health_category === 'Đỏ (Báo động)'
                                   }"
                                   x-text="studentDetailData.student.health_category"></p>
                            </div>
                        </div>

                        {{-- Metrics Cards --}}
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Tổng buổi</p>
                                <p class="text-lg font-bold text-light-text dark:text-zeus-text" x-text="studentDetailData.student.total_scheduled"></p>
                            </div>
                            <div class="bg-emerald-500/5 rounded-lg p-3 border border-emerald-500/20 text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                <p class="text-lg font-bold text-emerald-500" x-text="studentDetailData.student.total_success"></p>
                            </div>
                            <div class="bg-red-500/5 rounded-lg p-3 border border-red-500/20 text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">HV Noshow</p>
                                <p class="text-lg font-bold text-red-500" x-text="studentDetailData.student.student_noshow"></p>
                            </div>
                            <div class="bg-orange-500/5 rounded-lg p-3 border border-orange-500/20 text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">&lt; 1/2 giờ</p>
                                <p class="text-lg font-bold text-orange-500" x-text="studentDetailData.student.student_half"></p>
                            </div>
                            <div class="bg-purple-500/5 rounded-lg p-3 border border-purple-500/20 text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">GV Noshow</p>
                                <p class="text-lg font-bold text-purple-500" x-text="studentDetailData.student.teacher_noshow"></p>
                            </div>
                            <div class="rounded-lg p-3 border text-center"
                                 :class="studentDetailData.student.health_score >= 85 ? 'bg-emerald-500/5 border-emerald-500/20' : studentDetailData.student.health_score >= 60 ? 'bg-yellow-500/5 border-yellow-500/20' : 'bg-red-500/5 border-red-500/20'">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Điểm SK</p>
                                <p class="text-lg font-bold"
                                   :class="studentDetailData.student.health_score >= 85 ? 'text-emerald-500' : studentDetailData.student.health_score >= 60 ? 'text-yellow-500' : 'text-red-500'"
                                   x-text="studentDetailData.student.health_score + '%'"></p>
                            </div>
                        </div>

                        {{-- Additional Stats --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Tỉ lệ TC</p>
                                <p class="text-sm font-bold"
                                   :class="parseFloat(studentDetailData.student.success_rate) >= 0.8 ? 'text-emerald-500' : parseFloat(studentDetailData.student.success_rate) >= 0.6 ? 'text-yellow-500' : 'text-red-500'"
                                   x-text="(parseFloat(studentDetailData.student.success_rate) * 100).toFixed(1) + '%'"></p>
                            </div>

                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">%TC 3 buổi đầu</p>
                                <p class="text-sm font-bold"
                                   :class="studentDetailData.student.first_3_success_rate !== null ? (studentDetailData.student.first_3_success_rate >= 67 ? 'text-emerald-500' : studentDetailData.student.first_3_success_rate >= 33 ? 'text-yellow-500' : 'text-red-500') : 'text-light-text-muted dark:text-zeus-text-muted'"
                                   x-text="studentDetailData.student.first_3_success_rate !== null ? studentDetailData.student.first_3_success_rate + '%' : '—'"></p>
                            </div>
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Cảnh báo GV</p>
                                <p class="text-xs font-bold mt-0.5"
                                   :class="{
                                       'text-light-text-muted dark:text-zeus-text-muted': studentDetailData.student.teacher_warning === 'Bình thường',
                                       'text-yellow-500': studentDetailData.student.teacher_warning && studentDetailData.student.teacher_warning.includes('1b'),
                                       'text-orange-500': studentDetailData.student.teacher_warning && studentDetailData.student.teacher_warning.includes('>=2b'),
                                       'text-red-500': studentDetailData.student.teacher_warning && studentDetailData.student.teacher_warning.includes('>= 4')
                                   }"
                                   x-text="studentDetailData.student.teacher_warning"></p>
                            </div>
                        </div>

                        {{-- Phase 202: LCMS Metrics --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border"
                             x-show="studentDetailData.lcms && (studentDetailData.lcms.hw_completion_rate !== null || studentDetailData.lcms.hw_avg_score !== null || studentDetailData.lcms.test_avg_score !== null)">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📚 Chỉ số LCMS (BTVN & Bài kiểm tra)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">dữ liệu LCMS</span><br>Thống kê từ hệ thống LCMS (Learning Content Management System) bao gồm tỉ lệ làm Bài tập về nhà, điểm trung bình BTVN và điểm trung bình Bài kiểm tra.<br>Dữ liệu từ: <span class="tooltip-table">lcms_user_assignments, lcms_student_scores, lcms_courses</span></span></span>
                            </h4>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-light-card dark:bg-zeus-card rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Tỉ lệ làm BTVN</p>
                                    <p class="text-lg font-bold"
                                       :class="studentDetailData.lcms && studentDetailData.lcms.hw_completion_rate !== null ? (studentDetailData.lcms.hw_completion_rate >= 80 ? 'text-emerald-500' : studentDetailData.lcms.hw_completion_rate >= 50 ? 'text-yellow-500' : 'text-red-500') : 'text-light-text-muted dark:text-zeus-text-muted'"
                                       x-text="studentDetailData.lcms && studentDetailData.lcms.hw_completion_rate !== null ? studentDetailData.lcms.hw_completion_rate + '%' : '—'"></p>
                                </div>
                                <div class="bg-light-card dark:bg-zeus-card rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Điểm TB BTVN</p>
                                    <p class="text-lg font-bold"
                                       :class="studentDetailData.lcms && studentDetailData.lcms.hw_avg_score !== null ? (studentDetailData.lcms.hw_avg_score >= 8 ? 'text-emerald-500' : studentDetailData.lcms.hw_avg_score >= 5 ? 'text-yellow-500' : 'text-red-500') : 'text-light-text-muted dark:text-zeus-text-muted'"
                                       x-text="studentDetailData.lcms && studentDetailData.lcms.hw_avg_score !== null ? studentDetailData.lcms.hw_avg_score : '—'"></p>
                                </div>
                                <div class="bg-light-card dark:bg-zeus-card rounded-lg p-3 border border-light-border dark:border-zeus-border text-center">
                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">Điểm TB Bài KT</p>
                                    <p class="text-lg font-bold"
                                       :class="studentDetailData.lcms && studentDetailData.lcms.test_avg_score !== null ? (studentDetailData.lcms.test_avg_score >= 8 ? 'text-emerald-500' : studentDetailData.lcms.test_avg_score >= 5 ? 'text-yellow-500' : 'text-red-500') : 'text-light-text-muted dark:text-zeus-text-muted'"
                                       x-text="studentDetailData.lcms && studentDetailData.lcms.test_avg_score !== null ? studentDetailData.lcms.test_avg_score : '—'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Orders Section --}}
                        <template x-if="studentDetailData.orders && studentDetailData.orders.length > 0">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                                <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                    💳 Thông tin thanh toán
                                    <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + studentDetailData.orders.length + ' đơn hàng)'"></span>
                                </h4>
                                <div class="table-container max-h-[200px] overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 bg-light-card-alt dark:bg-zeus-card-light">
                                            <tr class="border-b border-light-border dark:border-zeus-border">
                                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Mã ĐH</th>
                                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Loại</th>
                                                <th class="text-right py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Tổng tiền</th>
                                                <th class="text-center py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thanh toán</th>
                                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Phương thức</th>
                                                <th class="text-center py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Trạng thái</th>
                                                <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Ngày tạo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(ord, idx) in studentDetailData.orders" :key="idx">
                                                <tr class="border-b border-light-border/30 dark:border-zeus-border/30">
                                                    <td class="py-1.5 px-3 text-xs font-mono text-light-text dark:text-zeus-text" x-text="'#' + ord.order_id"></td>
                                                    <td class="py-1.5 px-3 text-xs text-light-text dark:text-zeus-text" x-text="orderTypeLabel(ord.order_type)"></td>
                                                    <td class="py-1.5 px-3 text-xs text-right font-medium text-light-text dark:text-zeus-text" x-text="formatCurrency(ord.order_total_amount, ord.order_currency_code)"></td>
                                                    <td class="py-1.5 px-3 text-center">
                                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                              :class="ord.order_payment_status == 1 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/20 text-red-600 dark:text-red-400'"
                                                              x-text="ord.order_payment_status == 1 ? 'Đã TT' : 'Chưa TT'"></span>
                                                    </td>
                                                    <td class="py-1.5 px-3 text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="paymentMethodLabel(ord.payment_method)"></td>
                                                    <td class="py-1.5 px-3 text-center">
                                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                              :class="{
                                                                  'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': ord.order_status == 2,
                                                                  'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400': ord.order_status == 1,
                                                                  'bg-red-500/20 text-red-600 dark:text-red-400': ord.order_status == 3,
                                                              }"
                                                              x-text="orderStatusLabel(ord.order_status)"></span>
                                                    </td>
                                                    <td class="py-1.5 px-3 text-xs text-light-text-muted dark:text-zeus-text-muted font-mono" x-text="formatDateTime(ord.order_addedon)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                {{-- Order Summary --}}
                                <div class="mt-3 flex flex-wrap gap-4 text-xs text-light-text-muted dark:text-zeus-text-muted border-t border-light-border/50 dark:border-zeus-border/50 pt-3">
                                    <span>Tổng đơn: <strong class="text-light-text dark:text-zeus-text" x-text="studentDetailData.orders.length"></strong></span>
                                    <span>Tổng giá trị: <strong class="text-emerald-500" x-text="formatCurrency(studentDetailData.orders.reduce((s, o) => s + parseFloat(o.order_total_amount || 0), 0), studentDetailData.orders[0]?.order_currency_code)"></strong></span>
                                    <span>Đã thanh toán: <strong class="text-emerald-500" x-text="studentDetailData.orders.filter(o => o.order_payment_status == 1).length + '/' + studentDetailData.orders.length"></strong></span>
                                </div>
                            </div>
                        </template>

                        {{-- Subscription Plans / Packages Section --}}
                        <template x-if="studentDetailData.packages && studentDetailData.packages.length > 0">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                                <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                    📦 Gói mua (Subscription Plans)
                                    <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + studentDetailData.packages.length + ' gói)'"></span>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <template x-for="(pkg, idx) in studentDetailData.packages" :key="idx">
                                        <div class="rounded-lg p-3 border"
                                             :class="{
                                                 'bg-emerald-500/5 border-emerald-500/20': pkg.ordsplan_status == 1,
                                                 'bg-blue-500/5 border-blue-500/20': pkg.ordsplan_status == 2,
                                                 'bg-red-500/5 border-red-500/20': pkg.ordsplan_status == 3,
                                                 'bg-gray-500/5 border-gray-500/20': pkg.ordsplan_status == 4 || (pkg.ordsplan_status != 1 && pkg.ordsplan_status != 2 && pkg.ordsplan_status != 3),
                                             }">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs font-bold text-light-text dark:text-zeus-text" x-text="pkg.plan_title || ('Gói #' + pkg.ordsplan_plan_id)"></span>
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                      :class="{
                                                          'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': pkg.ordsplan_status == 1,
                                                          'bg-blue-500/20 text-blue-600 dark:text-blue-400': pkg.ordsplan_status == 2,
                                                          'bg-red-500/20 text-red-600 dark:text-red-400': pkg.ordsplan_status == 3,
                                                          'bg-gray-500/20 text-gray-600 dark:text-gray-400': pkg.ordsplan_status == 4,
                                                      }"
                                                      x-text="packageStatusLabel(pkg.ordsplan_status)"></span>
                                            </div>
                                            <div class="space-y-1 text-[11px]">
                                                <div class="flex justify-between">
                                                    <span class="text-light-text-muted dark:text-zeus-text-muted">Giá gói:</span>
                                                    <span class="text-light-text dark:text-zeus-text font-medium" x-text="formatCurrency(pkg.ordsplan_amount)"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-light-text-muted dark:text-zeus-text-muted">Số buổi:</span>
                                                    <span class="text-light-text dark:text-zeus-text font-medium" x-text="(pkg.ordsplan_used_lesson_count || 0) + ' / ' + (pkg.ordsplan_lessons || '—')"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-light-text-muted dark:text-zeus-text-muted">Thời hạn:</span>
                                                    <span class="text-light-text dark:text-zeus-text font-medium" x-text="(pkg.ordsplan_validity || '—') + ' tuần'"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-light-text-muted dark:text-zeus-text-muted">Bắt đầu:</span>
                                                    <span class="text-light-text dark:text-zeus-text font-mono" x-text="pkg.ordsplan_start_date ? formatDateTime(pkg.ordsplan_start_date) : '—'"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-light-text-muted dark:text-zeus-text-muted">Kết thúc:</span>
                                                    <span class="text-light-text dark:text-zeus-text font-mono" x-text="pkg.ordsplan_end_date ? formatDateTime(pkg.ordsplan_end_date) : '—'"></span>
                                                </div>
                                                <template x-if="pkg.ordsplan_refund > 0">
                                                    <div class="flex justify-between">
                                                        <span class="text-red-400">Hoàn tiền:</span>
                                                        <span class="text-red-500 font-medium" x-text="formatCurrency(pkg.ordsplan_refund)"></span>
                                                    </div>
                                                </template>
                                                {{-- Progress bar for used lessons --}}
                                                <div class="mt-1.5" x-show="pkg.ordsplan_lessons > 0">
                                                    <div class="w-full h-1.5 bg-light-border dark:bg-zeus-border rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full transition-all"
                                                             :class="(pkg.ordsplan_used_lesson_count / pkg.ordsplan_lessons) >= 0.8 ? 'bg-red-500' : (pkg.ordsplan_used_lesson_count / pkg.ordsplan_lessons) >= 0.5 ? 'bg-yellow-500' : 'bg-emerald-500'"
                                                             :style="'width: ' + Math.min((pkg.ordsplan_used_lesson_count / pkg.ordsplan_lessons) * 100, 100) + '%'"></div>
                                                    </div>
                                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted mt-0.5 text-right" x-text="'Đã dùng ' + Math.round((pkg.ordsplan_used_lesson_count / pkg.ordsplan_lessons) * 100) + '%'"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Consecutive Streak Alert (if any) --}}
                        <template x-if="studentDetailData.consecutive_streak > 0">
                            <div class="p-3 rounded-lg border text-sm flex items-center gap-3"
                                 :class="studentDetailData.consecutive_streak >= 5 ? 'bg-red-500/10 border-red-500/30' : studentDetailData.consecutive_streak >= 3 ? 'bg-orange-500/10 border-orange-500/30' : 'bg-yellow-500/10 border-yellow-500/30'">
                                <span class="text-2xl" x-text="studentDetailData.consecutive_streak >= 5 ? '🚨' : studentDetailData.consecutive_streak >= 3 ? '⚠️' : '⚡'"></span>
                                <div>
                                    <p class="font-semibold" :class="studentDetailData.consecutive_streak >= 5 ? 'text-red-600 dark:text-red-400' : studentDetailData.consecutive_streak >= 3 ? 'text-orange-600 dark:text-orange-400' : 'text-yellow-600 dark:text-yellow-400'">
                                        <span x-text="studentDetailData.consecutive_streak"></span> buổi nghỉ liên tiếp gần nhất
                                    </p>
                                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-0.5" x-text="studentDetailData.consecutive_streak >= 5 ? 'Mức độ: RẤT CAO — Cần liên hệ HV khẩn cấp!' : studentDetailData.consecutive_streak >= 3 ? 'Mức độ: CAO — Cần liên hệ HV sớm.' : 'Mức độ: TRUNG BÌNH — Theo dõi tiếp.'"></p>
                                </div>
                            </div>
                        </template>

                        {{-- First 3 Lessons Detail --}}
                        <template x-if="studentDetailData.student.lesson_1_date">
                            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                                <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                    🎯 3 buổi học đầu tiên (sau Trial)
                                </h4>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="text-center p-2 rounded-lg border border-light-border dark:border-zeus-border">
                                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted mb-1">Buổi 1</p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="studentDetailData.student.lesson_1_date ? formatShortDate(studentDetailData.student.lesson_1_date) : '—'"></p>
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1"
                                              :class="acceptanceCodeClass(studentDetailData.student.lesson_1_code)"
                                              x-text="acceptanceCodeLabel(studentDetailData.student.lesson_1_code)"></span>
                                    </div>
                                    <div class="text-center p-2 rounded-lg border border-light-border dark:border-zeus-border">
                                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted mb-1">Buổi 2</p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="studentDetailData.student.lesson_2_date ? formatShortDate(studentDetailData.student.lesson_2_date) : '—'"></p>
                                        <template x-if="studentDetailData.student.lesson_2_date">
                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1"
                                                  :class="acceptanceCodeClass(studentDetailData.student.lesson_2_code)"
                                                  x-text="acceptanceCodeLabel(studentDetailData.student.lesson_2_code)"></span>
                                        </template>
                                        <template x-if="!studentDetailData.student.lesson_2_date">
                                            <span class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                        </template>
                                    </div>
                                    <div class="text-center p-2 rounded-lg border border-light-border dark:border-zeus-border">
                                        <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted mb-1">Buổi 3</p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="studentDetailData.student.lesson_3_date ? formatShortDate(studentDetailData.student.lesson_3_date) : '—'"></p>
                                        <template x-if="studentDetailData.student.lesson_3_date">
                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1"
                                                  :class="acceptanceCodeClass(studentDetailData.student.lesson_3_code)"
                                                  x-text="acceptanceCodeLabel(studentDetailData.student.lesson_3_code)"></span>
                                        </template>
                                        <template x-if="!studentDetailData.student.lesson_3_date">
                                            <span class="text-light-text-muted dark:text-zeus-text-muted text-xs">—</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Timeline Visualization --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border" x-show="studentDetailLessonsChronological.length > 0">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📊 Bản đồ buổi học
                                <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted">(Trái = cũ nhất → Phải = mới nhất)</span>
                            </h4>
                            {{-- Legend --}}
                            <div class="flex flex-wrap gap-3 mb-3 text-[10px] text-light-text-muted dark:text-zeus-text-muted">
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Thành công</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-500 inline-block"></span> Noshow</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-orange-400 inline-block"></span> &lt; 1/2 giờ</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-gray-400 inline-block"></span> Chưa có dữ liệu</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-purple-500 inline-block"></span> GV nghỉ phép</span>
                                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm border-2 border-red-500 bg-transparent inline-block"></span> Chuỗi nghỉ LT</span>
                            </div>
                            {{-- Lesson blocks - chronological order (oldest first) --}}
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(lesson, idx) in studentDetailLessonsChronological" :key="idx">
                                    <div class="relative group">
                                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-md flex items-center justify-center text-[9px] font-bold transition-transform hover:scale-125 cursor-default"
                                             :class="{
                                                 'bg-purple-500 text-white': lesson.status === 'teacher_leave',
                                                 'bg-emerald-500 text-white': lesson.status === 'success' && !lesson._is_teacher_leave,
                                                 'bg-red-500 text-white': lesson.status === 'noshow' && !lesson._is_teacher_leave,
                                                 'bg-orange-400 text-white': lesson.status === 'half' && !lesson._is_teacher_leave,
                                                 'bg-gray-400 text-white': lesson.status === 'unknown' && !lesson._is_teacher_leave,
                                                 'ring-2 ring-purple-500 ring-offset-1 ring-offset-light-card-alt dark:ring-offset-zeus-card-light': lesson._is_teacher_leave && lesson.status !== 'teacher_leave',
                                                 'ring-2 ring-red-500 ring-offset-1 ring-offset-light-card-alt dark:ring-offset-zeus-card-light': lesson._in_streak && !lesson._is_teacher_leave
                                             }"
                                             :title="lesson.lesson_time + ' — ' + lesson.status_label + (lesson._is_teacher_leave ? ' (GV nghỉ phép)' : '')">
                                            <span x-text="lesson._short_date"></span>
                                        </div>
                                        {{-- Tooltip on hover --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
                                            <span x-text="lesson.lesson_time"></span><br>
                                            <span x-text="lesson.status_label"></span>
                                            <template x-if="lesson._is_teacher_leave">
                                                <span><br>🟣 GV nghỉ phép<span x-show="lesson._teacher_name"> — <span x-text="lesson._teacher_name"></span></span></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Lesson Detail Table --}}
                        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border" x-show="studentDetailLessonsChronological.length > 0">
                            <h4 class="text-xs font-semibold text-light-text dark:text-zeus-text uppercase tracking-wider mb-3 flex items-center gap-2">
                                📋 Lịch sử buổi học
                                <span class="text-[10px] font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + studentDetailLessonsChronological.length + ' buổi)'"></span>
                            </h4>
                            <div class="table-container max-h-[300px] overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-light-card-alt dark:bg-zeus-card-light">
                                        <tr class="border-b border-light-border dark:border-zeus-border">
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">#</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Thời gian</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Trạng thái</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">GV</th>
                                            <th class="text-center py-2 px-3 text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase">Mã nghiệm thu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(lesson, idx) in studentDetailLessonsChronological" :key="idx">
                                            <tr class="border-b border-light-border/30 dark:border-zeus-border/30"
                                                :class="{
                                                    'bg-purple-500/10': lesson._is_teacher_leave,
                                                    'bg-red-500/10': !lesson._is_teacher_leave && lesson._in_streak,
                                                }">
                                                <td class="py-1.5 px-3 text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="idx + 1"></td>
                                                <td class="py-1.5 px-3 text-xs text-light-text dark:text-zeus-text font-mono" x-text="lesson.lesson_time"></td>
                                                <td class="py-1.5 px-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                                          :class="{
                                                              'bg-purple-500/20 text-purple-600 dark:text-purple-400': lesson.status === 'teacher_leave',
                                                              'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': lesson.status === 'success',
                                                              'bg-red-500/20 text-red-600 dark:text-red-400': lesson.status === 'noshow',
                                                              'bg-orange-500/20 text-orange-600 dark:text-orange-400': lesson.status === 'half',
                                                              'bg-gray-500/20 text-gray-600 dark:text-gray-400': lesson.status === 'unknown',
                                                          }" x-text="lesson.status_label">
                                                    </span>
                                                </td>
                                                <td class="py-1.5 px-3">
                                                    <template x-if="lesson._is_teacher_leave">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-600 dark:text-purple-400" title="Giáo viên nghỉ phép">
                                                            🟣 NP<span x-show="lesson._teacher_name">: <span x-text="lesson._teacher_name"></span></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!lesson._is_teacher_leave && lesson.is_teacher_noshow == 1">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-600 dark:text-purple-400" title="Giáo viên không có mặt">
                                                            🚫 GV Noshow
                                                        </span>
                                                    </template>
                                                </td>
                                                <td class="py-1.5 px-3 text-center text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="lesson.acceptance_code !== null && lesson.acceptance_code !== undefined ? lesson.acceptance_code : '—'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!studentDetailLoading && !studentDetailData.student">
                    <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không tìm thấy dữ liệu cho học viên này.</p>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                <button @click="showStudentDetail = false" class="w-full py-2 px-4 bg-zeus-accent hover:bg-zeus-accent-light text-white rounded-lg transition font-medium">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    @endif
</div>

@push('styles')
<style>
    /* Highlight row on hover */
    .table-container table tbody tr {
        transition: background-color 0.15s ease;
    }
</style>
@endpush

<script>
function csiDashboard() {
    return {
        loading: true,
        tableLoading: false,
        ewsLoading: false,
        filters: {
            health_category: '',
            css_staff: '',
            teacher_warning: '',
            search: '',
            date_from: '',
            date_to: '',
        },
        summary: {},
        studentData: { data: [], total: 0, page: 1, per_page: 50, total_pages: 1 },
        healthDistribution: [],
        scoreDistribution: [],
        cssPerformance: [],
        teacherWarning: [],
        ewsData: { data: [], total: 0, page: 1, per_page: 50, total_pages: 1 },
        ewsFilters: { search: '', css_staff: '', min_missed: '0' },
        ewsCurrentPage: 1,
        ewsPerPage: 50,
        ewsSortBy: 'total_missed',
        ewsSortDir: 'desc',
        currentPage: 1,
        perPage: 50,
        sortBy: 'health_score',
        sortDir: 'asc',

        // Student table local filters
        studentFilters: { search: '', health_category: '', css_staff: '', teacher_warning: '', ontrack_status: '', program: '', lesson_1_from: '', lesson_1_to: '', first_3_from: '', first_3_to: '' },

        // Export loading states
        ewsExporting: false,
        studentsExporting: false,

        // Student Detail modal state
        showStudentDetail: false,
        studentDetailLoading: false,
        studentDetailId: null,
        studentDetailName: '',
        studentDetailData: { student: null, lessons: [], consecutive_streak: 0, orders: [], packages: [], leave_sessions: [], lcms: null },
        studentDetailLessonsChronological: [],

        // EWS Detail modal state
        showEwsDetail: false,
        ewsDetailLoading: false,
        ewsDetailStudentId: null,
        ewsDetailStudentName: '',
        ewsDetailData: { student: null, lessons: [], consecutive_streak: 0, leave_sessions: [] },
        ewsDetailLessonsChronological: [],
        ewsDetailMonths: [],

        // Phase 221: Inactive students dialog state
        showInactiveDialog: false,
        inactiveLoading: false,
        inactiveExporting: false,
        inactiveSearch: '',
        inactiveSortBy: 'remaining_total',
        inactiveSortDir: 'asc',
        inactiveData: { data: [], total: 0, page: 1, per_page: 50, total_pages: 1, zero_lessons_count: 0 },

        // Trend state
        trendData: [],
        healthTrendData: [],
        ontrackTrendData: [],
        trendLoading: false,
        trendGroupBy: 'week',

        // Chart instances
        healthChart: null,
        scoreChart: null,
        cssChart: null,
        teacherWarningChart: null,
        trendVolumeChart: null,
        trendSuccessRateChart: null,
        trendStudentsChart: null,
        trendAbsenceChart: null,
        trendHealthCountChart: null,
        trendHealthPctChart: null,
        trendOntrackChart: null,
        trendOntrackCountChart: null,

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            this.tableLoading = true;
            this.currentPage = 1;
            FilterProgress.show();

            const params = this.buildParams();

            try {
                const [summaryRes, healthRes, scoreRes, cssRes, twRes] = await Promise.all([
                    fetch('/api/csi/summary?' + params),
                    fetch('/api/csi/health-distribution?' + params),
                    fetch('/api/csi/score-distribution?' + params),
                    fetch('/api/csi/css-performance?' + params),
                    fetch('/api/csi/teacher-warning?' + params),
                ]);

                this.summary = await summaryRes.json();
                this.healthDistribution = await healthRes.json();
                this.scoreDistribution = await scoreRes.json();
                this.cssPerformance = await cssRes.json();
                this.teacherWarning = await twRes.json();
            } catch (e) {
                console.error('CSI load error:', e);
            }

            this.loading = false;
            FilterProgress.hide();

            // Render charts after DOM has been updated (loading=false makes containers visible)
            this.$nextTick(() => {
                this.renderCharts();
            });

            await this.loadStudents();

            // Reload EWS if it was previously loaded (date filters affect EWS data)
            if (this.ewsData.data.length > 0 || this.ewsData.total > 0) {
                this.ewsCurrentPage = 1;
                this.loadEws();
            }

            // Reload trends if previously loaded (filters affect trend data)
            if (this.trendData.length > 0) {
                this.loadTrends();
            }
        },

        async loadStudents() {
            this.tableLoading = true;
            const params = this.buildStudentParams();
            // For client-side sort columns (LCMS), send default server sort; re-sort after load
            const serverSortBy = this._clientSortCols.includes(this.sortBy) ? 'health_score' : this.sortBy;
            const serverSortDir = this._clientSortCols.includes(this.sortBy) ? 'asc' : this.sortDir;
            const extra = `&page=${this.currentPage}&per_page=${this.perPage}&sort_by=${serverSortBy}&sort_dir=${serverSortDir}`;

            try {
                const res = await fetch('/api/csi/students?' + params + extra);
                this.studentData = await res.json();
                // Re-apply client-side sort if active sort is a LCMS column
                if (this._clientSortCols.includes(this.sortBy)) {
                    this._applyClientSort();
                }
            } catch (e) {
                console.error('CSI students error:', e);
            }
            this.tableLoading = false;
        },

        async loadEws() {
            this.ewsLoading = true;
            const p = new URLSearchParams();
            if (this.ewsFilters.search) p.set('search', this.ewsFilters.search);
            if (this.ewsFilters.css_staff) p.set('css_staff', this.ewsFilters.css_staff);
            if (this.ewsFilters.min_missed && parseInt(this.ewsFilters.min_missed) > 0) p.set('min_missed', this.ewsFilters.min_missed);
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);
            p.set('page', this.ewsCurrentPage);
            p.set('per_page', this.ewsPerPage);
            p.set('sort_by', this.ewsSortBy);
            p.set('sort_dir', this.ewsSortDir);

            try {
                const res = await fetch('/api/csi/ews?' + p.toString());
                this.ewsData = await res.json();
            } catch (e) {
                console.error('CSI EWS error:', e);
            }
            this.ewsLoading = false;
        },

        toggleEwsSort(col) {
            if (this.ewsSortBy === col) {
                this.ewsSortDir = this.ewsSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.ewsSortBy = col;
                this.ewsSortDir = col === 'total_missed' ? 'desc' : 'asc';
            }
            this.loadEws();
        },

        ewsSortIndicator(col) {
            if (this.ewsSortBy !== col) return '';
            return this.ewsSortDir === 'asc' ? '↑' : '↓';
        },

        buildParams() {
            const p = new URLSearchParams();
            if (this.filters.health_category) p.set('health_category', this.filters.health_category);
            if (this.filters.css_staff) p.set('css_staff', this.filters.css_staff);
            if (this.filters.teacher_warning) p.set('teacher_warning', this.filters.teacher_warning);
            if (this.filters.search) p.set('search', this.filters.search);
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);
            return p.toString();
        },

        /**
         * Build query params for student table using local studentFilters + global date filters
         */
        buildStudentParams() {
            const p = new URLSearchParams();
            if (this.studentFilters.health_category) p.set('health_category', this.studentFilters.health_category);
            if (this.studentFilters.css_staff) p.set('css_staff', this.studentFilters.css_staff);
            if (this.studentFilters.teacher_warning) p.set('teacher_warning', this.studentFilters.teacher_warning);
            if (this.studentFilters.ontrack_status) p.set('ontrack_status', this.studentFilters.ontrack_status);
            if (this.studentFilters.program) p.set('program', this.studentFilters.program);
            if (this.studentFilters.search) p.set('search', this.studentFilters.search);
            if (this.studentFilters.lesson_1_from) p.set('lesson_1_from', this.studentFilters.lesson_1_from);
            if (this.studentFilters.lesson_1_to) p.set('lesson_1_to', this.studentFilters.lesson_1_to);
            if (this.studentFilters.first_3_from) p.set('first_3_from', this.studentFilters.first_3_from);
            if (this.studentFilters.first_3_to) p.set('first_3_to', this.studentFilters.first_3_to);
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);
            return p.toString();
        },

        resetFilters() {
            this.filters = { health_category: '', css_staff: '', teacher_warning: '', search: '', date_from: '', date_to: '' };
            this.loadData();
        },

        // LCMS columns are enriched post-query; sort client-side for the current page
        _clientSortCols: ['hw_completion_rate', 'hw_avg_score', 'test_avg_score'],

        toggleSort(col) {
            if (this.sortBy === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = col;
                this.sortDir = 'asc';
            }
            if (this._clientSortCols.includes(col)) {
                this._applyClientSort();
            } else {
                this.loadStudents();
            }
        },

        _applyClientSort() {
            if (!this.studentData || !this.studentData.data) return;
            const col = this.sortBy;
            const dir = this.sortDir === 'asc' ? 1 : -1;
            this.studentData.data.sort((a, b) => {
                const va = a[col] !== null && a[col] !== undefined ? parseFloat(a[col]) : -Infinity;
                const vb = b[col] !== null && b[col] !== undefined ? parseFloat(b[col]) : -Infinity;
                return (va - vb) * dir;
            });
        },

        sortIndicator(col) {
            if (this.sortBy !== col) return '';
            return this.sortDir === 'asc' ? '↑' : '↓';
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadStudents();
            }
        },

        nextPage() {
            if (this.currentPage < this.studentData.total_pages) {
                this.currentPage++;
                this.loadStudents();
            }
        },

        fmt(n) {
            if (n === undefined || n === null) return '—';
            return Number(n).toLocaleString('vi-VN');
        },

        formatDateTime(dt) {
            if (!dt) return '';
            // Input: "YYYY-MM-DD HH:MM:SS" → Output: "DD/MM/YYYY HH:MM"
            const parts = dt.split(' ');
            if (parts.length < 2) return dt;
            const dateParts = parts[0].split('-');
            if (dateParts.length < 3) return dt;
            const timeParts = parts[1].split(':');
            return dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0] + ' ' + timeParts[0] + ':' + timeParts[1];
        },

        /**
         * Format datetime to short date: "DD/MM/YY HH:MM"
         */
        formatShortDate(dt) {
            if (!dt) return '';
            const parts = dt.split(' ');
            if (parts.length < 2) return dt;
            const dateParts = parts[0].split('-');
            if (dateParts.length < 3) return dt;
            const timeParts = parts[1].split(':');
            return dateParts[2] + '/' + dateParts[1] + ' ' + timeParts[0] + ':' + timeParts[1];
        },

        /**
         * Map acceptance code to Vietnamese status label (for first-3 lessons display)
         */
        acceptanceCodeLabel(code) {
            if (code === null || code === undefined) return 'N/A';
            code = parseInt(code);
            if ([3, 6, 9, 12].includes(code)) return 'TC';
            if ([0, 4, 7, 10].includes(code)) return 'NS';
            if ([2, 5, 8, 11].includes(code)) return '½';
            if (code === 1) return 'Khác';
            return 'N/A';
        },

        /**
         * Map acceptance code to Tailwind CSS class (for first-3 lessons display)
         */
        acceptanceCodeClass(code) {
            if (code === null || code === undefined) return 'bg-gray-500/20 text-gray-500';
            code = parseInt(code);
            if ([3, 6, 9, 12].includes(code)) return 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400';
            if ([0, 4, 7, 10].includes(code)) return 'bg-red-500/20 text-red-600 dark:text-red-400';
            if ([2, 5, 8, 11].includes(code)) return 'bg-orange-500/20 text-orange-600 dark:text-orange-400';
            return 'bg-gray-500/20 text-gray-500';
        },

        /**
         * Order type code → Vietnamese label
         */
        orderTypeLabel(type) {
            const map = { 1: 'Buổi học', 2: 'Gói ĐK', 3: 'Lớp nhóm', 4: 'Package', 5: 'Khóa học', 6: 'Nạp ví', 7: 'Giftcard', 18: 'Sub Plan', 20: 'ZCoupon', 21: 'Reservation' };
            return map[type] || ('Loại ' + type);
        },

        /**
         * Payment method code → friendly label
         */
        paymentMethodLabel(code) {
            if (!code) return '—';
            const map = { 'WalletPay': 'Ví', 'BankTransferPay': 'Chuyển khoản', 'StripePay': 'Stripe', 'PaypalStandardPay': 'PayPal', 'BillingPay': 'Billing', 'ZCoupon': 'ZCoupon' };
            return map[code] || code;
        },

        /**
         * Order status code → Vietnamese label
         */
        orderStatusLabel(status) {
            const map = { 1: 'Đang xử lý', 2: 'Hoàn thành', 3: 'Đã hủy' };
            return map[status] || ('Trạng thái ' + status);
        },

        /**
         * Package/subscription plan status → Vietnamese label
         */
        packageStatusLabel(status) {
            const map = { 1: 'Hoạt động', 2: 'Đã hết hạn', 3: 'Đã hoàn tiền', 4: 'Đã hủy' };
            return map[status] || ('Trạng thái ' + status);
        },

        /**
         * Format currency amount with locale and optional currency code
         */
        formatCurrency(amount, currencyCode) {
            if (amount === null || amount === undefined) return '—';
            const num = parseFloat(amount);
            if (isNaN(num)) return '—';
            const formatted = num.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            if (currencyCode) return formatted + ' ' + currencyCode;
            return formatted + ' USD';
        },

        // ── Export helpers ───────────────────────────────────────
        downloadCSV(headers, rows, filename) {
            const BOM = '\uFEFF';
            const escape = (v) => {
                if (v === null || v === undefined) return '';
                const s = String(v);
                if (s.includes(',') || s.includes('"') || s.includes('\n')) {
                    return '"' + s.replace(/"/g, '""') + '"';
                }
                return s;
            };
            const csv = BOM + headers.map(escape).join(',') + '\n' +
                rows.map(r => r.map(escape).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', `${filename}_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        },

        exportCssToExcel() {
            const data = this.cssPerformance || [];
            if (data.length === 0) return;
            const headers = ['Chuyên viên CSS', 'Tổng HV', '🟢 Xanh', '🟡 Vàng', '🔴 Đỏ', 'Điểm TB', 'Tỉ lệ TC (%)'];
            const rows = data.map(r => [
                r.css_staff,
                r.total,
                r.green,
                r.yellow,
                r.red,
                r.avg_score,
                r.avg_success_rate,
            ]);
            this.downloadCSV(headers, rows, 'CSI_Chi_tiet_CSS');
        },

        async exportEwsToExcel() {
            this.ewsExporting = true;
            try {
                const p = new URLSearchParams();
                if (this.ewsFilters.search) p.set('search', this.ewsFilters.search);
                if (this.ewsFilters.css_staff) p.set('css_staff', this.ewsFilters.css_staff);
                if (this.ewsFilters.min_missed && parseInt(this.ewsFilters.min_missed) > 0) p.set('min_missed', this.ewsFilters.min_missed);
                if (this.filters.date_from) p.set('date_from', this.filters.date_from);
                if (this.filters.date_to) p.set('date_to', this.filters.date_to);
                p.set('page', 1);
                p.set('per_page', 99999);
                p.set('sort_by', this.ewsSortBy);
                p.set('sort_dir', this.ewsSortDir);

                const res = await fetch('/api/csi/ews?' + p.toString());
                const result = await res.json();
                const data = result.data || [];

                if (data.length === 0) {
                    this.ewsExporting = false;
                    return;
                }

                const headers = ['ID HV', 'Tên HV', 'SĐT', 'Email', 'Số buổi nghỉ liên tiếp', 'Buổi TC gần nhất', 'Chuyên viên CSS'];
                const rows = data.map(s => [
                    s.student_id,
                    s.student_name,
                    s.phone,
                    s.email,
                    s.total_missed,
                    s.last_success_time || 'Chưa có',
                    s.css_staff,
                ]);
                this.downloadCSV(headers, rows, 'CSI_Canh_bao_som_EWS');
            } catch (e) {
                console.error('EWS export error:', e);
            }
            this.ewsExporting = false;
        },

        async exportStudentsToExcel() {
            this.studentsExporting = true;
            try {
                const params = this.buildStudentParams();
                const exportSortBy = this._clientSortCols.includes(this.sortBy) ? 'health_score' : this.sortBy;
                const exportSortDir = this._clientSortCols.includes(this.sortBy) ? 'asc' : this.sortDir;
                const extra = `&page=1&per_page=99999&sort_by=${exportSortBy}&sort_dir=${exportSortDir}`;

                const res = await fetch('/api/csi/students?' + params + extra);
                const result = await res.json();
                const data = result.data || [];

                if (data.length === 0) {
                    this.studentsExporting = false;
                    return;
                }

                const acLabel = (code) => {
                    if (code === null || code === undefined) return '';
                    code = parseInt(code);
                    if ([3, 6, 9, 12].includes(code)) return 'Thành công';
                    if ([0, 4, 7, 10].includes(code)) return 'Noshow';
                    if ([2, 5, 8, 11].includes(code)) return '< 1/2 giờ';
                    return 'Khác';
                };
                const headers = [
                    'ID HV', 'Tên HV', 'Email', 'SĐT', 'Chuyên viên CSS',
                    'Tổng buổi hoàn thành', 'Buổi thành công', 'HV Noshow', 'HV < 1/2 giờ',
                    'Điểm sức khỏe', 'Phân loại', 'GV Noshow', 'GV Nghỉ phép', 'Cảnh báo GV',
                    'Tỉ lệ thành công (%)', 'TB buổi/tuần',
                    'Buổi 1 - Ngày', 'Buổi 1 - TT', 'Buổi 2 - Ngày', 'Buổi 2 - TT',
                    'Buổi 3 - Ngày', 'Buổi 3 - TT', '%3BĐ',
                    'Tỉ lệ làm BTVN (%)', 'Điểm TB BTVN', 'Điểm TB Bài KT',
                    'Chương trình'
                ];
                const rows = data.map(s => [
                    s.student_id,
                    s.student_name,
                    s.email,
                    s.phone,
                    s.css_staff || '',
                    s.total_scheduled,
                    s.total_success,
                    s.student_noshow,
                    s.student_half,
                    s.health_score,
                    s.health_category,
                    s.teacher_noshow,
                    s.leave_sessions,
                    s.teacher_warning,
                    (parseFloat(s.success_rate) * 100).toFixed(1),
                    s.avg_per_week !== null && s.avg_per_week !== undefined ? s.avg_per_week : '',
                    s.lesson_1_date || '',
                    acLabel(s.lesson_1_code),
                    s.lesson_2_date || '',
                    acLabel(s.lesson_2_code),
                    s.lesson_3_date || '',
                    acLabel(s.lesson_3_code),
                    s.first_3_success_rate !== null ? s.first_3_success_rate : '',
                    s.hw_completion_rate !== null && s.hw_completion_rate !== undefined ? s.hw_completion_rate : '',
                    s.hw_avg_score !== null && s.hw_avg_score !== undefined ? s.hw_avg_score : '',
                    s.test_avg_score !== null && s.test_avg_score !== undefined ? s.test_avg_score : '',
                    s.course_names || '',
                ]);
                this.downloadCSV(headers, rows, 'CSI_Danh_sach_hoc_vien');
            } catch (e) {
                console.error('Students export error:', e);
            }
            this.studentsExporting = false;
        },

        // ── EWS Detail Modal ────────────────────────────────────
        async openEwsDetail(studentId, studentName, totalMissed) {
            this.ewsDetailStudentId = studentId;
            this.ewsDetailStudentName = studentName;
            this.showEwsDetail = true;
            this.ewsDetailLoading = true;
            this.ewsDetailData = { student: null, lessons: [], consecutive_streak: 0, leave_sessions: [] };
            this.ewsDetailLessonsChronological = [];
            this.ewsDetailMonths = [];

            const p = new URLSearchParams();
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);

            try {
                const res = await fetch(`/api/csi/ews/${studentId}/detail?` + p.toString());
                this.ewsDetailData = await res.json();

                // Phase 190: Build timeline even if only leave sessions exist
                if ((this.ewsDetailData.lessons && this.ewsDetailData.lessons.length > 0) ||
                    (this.ewsDetailData.leave_sessions && this.ewsDetailData.leave_sessions.length > 0)) {
                    this.buildTimelineData();
                    this.buildCalendarData();
                }
            } catch (e) {
                console.error('EWS detail error:', e);
            }
            this.ewsDetailLoading = false;
        },

        /**
         * Phase 190: Merge leave sessions into the lesson list.
         * - If a leave session matches an existing lesson by lesson_id, mark the lesson.
         * - If a leave session doesn't match any existing lesson, add as a new entry.
         * Returns an array sorted chronologically (oldest first).
         */
        _mergeLeaveSessions(lessons, leaveSessions) {
            // Build a map of lesson_id -> leave session info
            const leaveByLessonId = {};
            const unmatchedLeaves = [];
            (leaveSessions || []).forEach(ls => {
                if (ls.lesson_id) {
                    leaveByLessonId[ls.lesson_id] = ls;
                } else {
                    unmatchedLeaves.push(ls);
                }
            });

            // Mark existing lessons that have a corresponding leave session
            const matchedLeaveIds = new Set();
            const merged = lessons.map(l => {
                const leaveInfo = leaveByLessonId[l.lesson_id];
                if (leaveInfo) {
                    matchedLeaveIds.add(leaveInfo.lesson_id);
                    return {
                        ...l,
                        _is_teacher_leave: true,
                        _teacher_name: leaveInfo.teacher_name || '',
                    };
                }
                return { ...l, _is_teacher_leave: false, _teacher_name: '' };
            });

            // Add unmatched leave sessions (those not linked to any existing lesson)
            const allLeaves = leaveSessions || [];
            allLeaves.forEach(ls => {
                if (!matchedLeaveIds.has(ls.lesson_id)) {
                    // Convert session_date (YYYY-MM-DD) to a datetime-like string for sorting
                    const dateStr = ls.session_date || '';
                    merged.push({
                        lesson_id: ls.lesson_id || null,
                        lesson_time: dateStr,
                        acceptance_code: null,
                        status: 'teacher_leave',
                        status_label: 'GV nghỉ phép',
                        _is_teacher_leave: true,
                        _teacher_name: ls.teacher_name || '',
                    });
                }
            });

            // Sort by lesson_time chronologically (oldest first)
            merged.sort((a, b) => {
                const ta = a.lesson_time || '';
                const tb = b.lesson_time || '';
                return ta.localeCompare(tb);
            });

            return merged;
        },

        /**
         * Build chronological lesson list for the timeline blocks visualization.
         * Marks which lessons belong to the current consecutive streak.
         * Phase 190: Merges teacher leave sessions into the timeline.
         */
        buildTimelineData() {
            const lessons = this.ewsDetailData.lessons || [];
            const leaveSessions = this.ewsDetailData.leave_sessions || [];
            const streak = this.ewsDetailData.consecutive_streak || 0;

            // Lessons come DESC from API, reverse for chronological order (oldest first)
            const chrono = [...lessons].reverse();

            // Phase 190: Merge leave sessions
            const merged = this._mergeLeaveSessions(chrono, leaveSessions);

            // The streak is counted from the end (most recent), so mark the last N items
            // Only count streak from non-leave lessons at the tail
            const total = merged.length;
            this.ewsDetailLessonsChronological = merged.map((l, idx) => {
                const isInStreak = idx >= (total - streak) && l.status !== 'success' && l.status !== 'teacher_leave';
                // Short date: dd/MM
                let shortDate = '';
                if (l.lesson_time) {
                    const parts = l.lesson_time.split(/[\s-]/);
                    if (parts.length >= 3) {
                        shortDate = parseInt(parts[2]) + '/' + parseInt(parts[1]);
                    }
                }
                return {
                    ...l,
                    _in_streak: l._in_streak || isInStreak,
                    _short_date: shortDate,
                };
            });
        },

        /**
         * Build calendar month data for heatmap visualization.
         * Groups lessons by month and creates a grid of day cells.
         * Phase 190: Also includes teacher leave sessions in the calendar.
         */
        buildCalendarData() {
            const lessons = this.ewsDetailData.lessons || [];
            const leaveSessions = this.ewsDetailData.leave_sessions || [];
            const streak = this.ewsDetailData.consecutive_streak || 0;

            // Build leave session map by lesson_id and by date
            const leaveByLessonId = {};
            const leaveByDate = {};
            leaveSessions.forEach(ls => {
                if (ls.lesson_id) leaveByLessonId[ls.lesson_id] = ls;
                if (ls.session_date) {
                    const d = ls.session_date.split(' ')[0]; // Ensure YYYY-MM-DD
                    leaveByDate[d] = ls;
                }
            });

            // Create a map of date -> lesson info (with streak flag)
            const lessonMap = {};
            const matchedLeaveDates = new Set();
            lessons.forEach((l, idx) => {
                if (!l.lesson_time) return;
                const dateStr = l.lesson_time.split(' ')[0]; // YYYY-MM-DD
                const isInStreak = idx < streak && l.status !== 'success';
                const leaveInfo = leaveByLessonId[l.lesson_id];
                // If multiple lessons on same day, keep the most recent one (lower idx = more recent)
                if (!lessonMap[dateStr]) {
                    lessonMap[dateStr] = {
                        ...l,
                        _in_streak: isInStreak,
                        _is_teacher_leave: !!leaveInfo,
                        _teacher_name: leaveInfo ? (leaveInfo.teacher_name || '') : '',
                    };
                    if (leaveInfo && leaveInfo.session_date) {
                        matchedLeaveDates.add(leaveInfo.session_date.split(' ')[0]);
                    }
                }
            });

            // Phase 190: Add unmatched leave sessions to the calendar map
            leaveSessions.forEach(ls => {
                const d = (ls.session_date || '').split(' ')[0];
                if (d && !matchedLeaveDates.has(d) && !lessonMap[d]) {
                    lessonMap[d] = {
                        lesson_id: ls.lesson_id || null,
                        lesson_time: d,
                        status: 'teacher_leave',
                        status_label: 'GV nghỉ phép',
                        _in_streak: false,
                        _is_teacher_leave: true,
                        _teacher_name: ls.teacher_name || '',
                    };
                }
            });

            // Find date range
            const dates = Object.keys(lessonMap).sort();
            if (dates.length === 0) { this.ewsDetailMonths = []; return; }

            const firstDate = new Date(dates[0] + 'T00:00:00');
            const lastDate = new Date(dates[dates.length - 1] + 'T00:00:00');

            // Generate months from first to last
            const months = [];
            let current = new Date(firstDate.getFullYear(), firstDate.getMonth(), 1);
            const end = new Date(lastDate.getFullYear(), lastDate.getMonth() + 1, 0);

            const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];

            while (current <= end) {
                const year = current.getFullYear();
                const month = current.getMonth();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                // Monday=0, Sunday=6 for our grid (JS: getDay() returns 0=Sun, 1=Mon, ...)
                const firstDayOfWeek = new Date(year, month, 1).getDay();
                // Convert to Mon=0: (day + 6) % 7
                const startOffset = (firstDayOfWeek + 6) % 7;

                const cells = [];
                // Empty cells before first day
                for (let i = 0; i < startOffset; i++) {
                    cells.push({ type: 'empty', day: null, lesson: null });
                }
                // Day cells
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    cells.push({
                        type: 'day',
                        day: d,
                        lesson: lessonMap[dateStr] || null,
                    });
                }

                months.push({
                    label: `${monthNames[month]} ${year}`,
                    cells: cells,
                });

                current = new Date(year, month + 1, 1);
            }

            this.ewsDetailMonths = months;
        },

        // ── Student Detail Modal ─────────────────────────────────
        async openStudentDetail(studentId, studentName) {
            this.studentDetailId = studentId;
            this.studentDetailName = studentName;
            this.showStudentDetail = true;
            this.studentDetailLoading = true;
            this.studentDetailData = { student: null, lessons: [], consecutive_streak: 0, orders: [], packages: [], leave_sessions: [], lcms: null };
            this.studentDetailLessonsChronological = [];

            const p = new URLSearchParams();
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);

            try {
                const res = await fetch(`/api/csi/students/${studentId}/detail?` + p.toString());
                this.studentDetailData = await res.json();

                // Phase 190: Build timeline even if only leave sessions exist
                if ((this.studentDetailData.lessons && this.studentDetailData.lessons.length > 0) ||
                    (this.studentDetailData.leave_sessions && this.studentDetailData.leave_sessions.length > 0)) {
                    this.buildStudentTimeline();
                }
            } catch (e) {
                console.error('Student detail error:', e);
            }
            this.studentDetailLoading = false;
        },

        /**
         * Build chronological lesson list for the student detail timeline.
         * Marks which lessons belong to the current consecutive streak.
         * Phase 190: Merges teacher leave sessions into the timeline.
         */
        buildStudentTimeline() {
            const lessons = this.studentDetailData.lessons || [];
            const leaveSessions = this.studentDetailData.leave_sessions || [];
            const streak = this.studentDetailData.consecutive_streak || 0;

            // Lessons come DESC from API, reverse for chronological order (oldest first)
            const chrono = [...lessons].reverse();

            // Phase 190: Merge leave sessions
            const merged = this._mergeLeaveSessions(chrono, leaveSessions);

            const total = merged.length;
            this.studentDetailLessonsChronological = merged.map((l, idx) => {
                const isInStreak = idx >= (total - streak) && l.status !== 'success' && l.status !== 'teacher_leave';
                let shortDate = '';
                if (l.lesson_time) {
                    const parts = l.lesson_time.split(/[\s-]/);
                    if (parts.length >= 3) {
                        shortDate = parseInt(parts[2]) + '/' + parseInt(parts[1]);
                    }
                }
                return {
                    ...l,
                    _in_streak: l._in_streak || isInStreak,
                    _short_date: shortDate,
                };
            });
        },

        // ── Phase 221: Inactive Students Dialog ─────────────────
        async openInactiveDialog() {
            this.showInactiveDialog = true;
            this.inactiveSearch = '';
            this.inactiveSortBy = 'remaining_total';
            this.inactiveSortDir = 'asc';
            await this.loadInactiveStudents(1);
        },

        async loadInactiveStudents(page) {
            if (page < 1) return;
            this.inactiveLoading = true;

            const p = new URLSearchParams();
            p.set('page', page);
            p.set('per_page', 50);
            if (this.inactiveSearch) p.set('search', this.inactiveSearch);
            p.set('sort_by', this.inactiveSortBy);
            p.set('sort_dir', this.inactiveSortDir);

            try {
                const res = await fetch('/api/csi/spw-inactive?' + p.toString());
                this.inactiveData = await res.json();
            } catch (e) {
                console.error('Inactive students load error:', e);
            }
            this.inactiveLoading = false;
        },

        toggleInactiveSort(col) {
            if (this.inactiveSortBy === col) {
                this.inactiveSortDir = this.inactiveSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.inactiveSortBy = col;
                this.inactiveSortDir = col === 'remaining_total' ? 'asc' : 'asc';
            }
            this.loadInactiveStudents(1);
        },

        inactiveSortIndicator(col) {
            if (this.inactiveSortBy !== col) return '';
            return this.inactiveSortDir === 'asc' ? '↑' : '↓';
        },

        async exportInactiveToExcel() {
            this.inactiveExporting = true;
            try {
                const p = new URLSearchParams();
                p.set('page', 1);
                p.set('per_page', 99999);
                if (this.inactiveSearch) p.set('search', this.inactiveSearch);
                p.set('sort_by', this.inactiveSortBy);
                p.set('sort_dir', this.inactiveSortDir);

                const res = await fetch('/api/csi/spw-inactive?' + p.toString());
                const result = await res.json();
                const data = result.data || [];

                if (data.length === 0) {
                    this.inactiveExporting = false;
                    return;
                }

                const headers = ['ID HV', 'Username', 'Tên HV', 'Email', 'Buổi chưa lên lịch (Unschedule)', 'Buổi đã lên lịch (Schedule)', 'Tổng còn lại'];
                const rows = data.map(s => [
                    s.student_id,
                    s.user_username || '',
                    s.student_name,
                    s.user_email,
                    parseInt(s.unscheduled_count) || 0,
                    parseInt(s.scheduled_count) || 0,
                    (parseInt(s.unscheduled_count) || 0) + (parseInt(s.scheduled_count) || 0),
                ]);

                // Add summary row
                rows.push([]);
                rows.push(['Tổng HV Inactive:', result.total]);
                rows.push(['HV có 0 buổi còn lại (unschedule + schedule = 0):', result.zero_lessons_count]);

                this.downloadCSV(headers, rows, 'CSI_HV_Inactive_SpeakWell');
            } catch (e) {
                console.error('Inactive export error:', e);
            }
            this.inactiveExporting = false;
        },

        // ── Trend Methods ────────────────────────────────────────
        async loadTrends() {
            this.trendLoading = true;
            const p = new URLSearchParams();
            p.set('group_by', this.trendGroupBy);
            if (this.filters.date_from) p.set('date_from', this.filters.date_from);
            if (this.filters.date_to) p.set('date_to', this.filters.date_to);
            if (this.filters.css_staff) p.set('css_staff', this.filters.css_staff);

            try {
                const [trendRes, healthTrendRes, ontrackTrendRes] = await Promise.all([
                    fetch('/api/csi/trends?' + p.toString()),
                    fetch('/api/csi/health-trends?' + p.toString()),
                    fetch('/api/csi/ontrack-trends?' + p.toString()),
                ]);
                this.trendData = await trendRes.json();
                this.healthTrendData = await healthTrendRes.json();
                this.ontrackTrendData = await ontrackTrendRes.json();
            } catch (e) {
                console.error('CSI trends error:', e);
                this.trendData = [];
                this.healthTrendData = [];
                this.ontrackTrendData = [];
            }
            this.trendLoading = false;

            this.$nextTick(() => {
                this.renderTrendCharts();
            });
        },

        trendChange(idx, field) {
            if (idx <= 0 || !this.trendData[idx] || !this.trendData[idx - 1]) return 0;
            return this.trendData[idx][field] - this.trendData[idx - 1][field];
        },

        getHealthTrendByPeriod(period, field) {
            if (!this.healthTrendData || this.healthTrendData.length === 0) return 0;
            const match = this.healthTrendData.find(d => d.period === period);
            return match ? (match[field] || 0) : 0;
        },

        getOntrackTrendByPeriod(period, field) {
            if (!this.ontrackTrendData || this.ontrackTrendData.length === 0) return 0;
            const match = this.ontrackTrendData.find(d => d.period === period);
            return match ? (match[field] || 0) : 0;
        },

        // Phase 200: Ontrack rate for detailed table = HV ontrack (≥90% success) / Tổng HV active × 100
        getTableOntrackRate(period) {
            if (!this.ontrackTrendData || this.ontrackTrendData.length === 0) return 0;
            const match = this.ontrackTrendData.find(d => d.period === period);
            return match ? Number(match.ontrack_rate).toFixed(1) : '0.0';
        },

        getTableOntrackBySuccess(period) {
            if (!this.ontrackTrendData || this.ontrackTrendData.length === 0) return 0;
            const match = this.ontrackTrendData.find(d => d.period === period);
            return match ? (match.ontrack_by_success || 0) : 0;
        },

        exportTrendsToExcel() {
            const data = this.trendData || [];
            if (data.length === 0) return;
            const headers = ['Kỳ', 'Tổng buổi hoàn thành', 'Buổi thành công', 'HV Noshow', 'HV < 1/2 giờ', 'Tỉ lệ TC (%)', 'HV hoạt động', '🟢 Khỏe mạnh', '🟡 Cảnh báo', '🔴 Báo động', '🎯 HV ontrack (≥90% TC)', '🎯 Tổng HV active', '🎯 Ontrack (%)', '🎯 HV Ontrack code12 (≥90%)', '🎯 HV Active (code 12)', '🎯 Ontrack trend (%)'];
            const rows = data.map(r => [
                r.period,
                r.total_scheduled,
                r.total_success,
                r.total_noshow,
                r.total_half,
                r.success_rate,
                r.unique_students,
                this.getHealthTrendByPeriod(r.period, 'green'),
                this.getHealthTrendByPeriod(r.period, 'yellow'),
                this.getHealthTrendByPeriod(r.period, 'red'),
                this.getTableOntrackBySuccess(r.period),
                r.unique_students,
                this.getTableOntrackRate(r.period),
                this.getOntrackTrendByPeriod(r.period, 'ontrack_count'),
                this.getOntrackTrendByPeriod(r.period, 'total_active'),
                this.getOntrackTrendByPeriod(r.period, 'ontrack_rate'),
            ]);
            this.downloadCSV(headers, rows, 'CSI_Xu_huong_' + this.trendGroupBy);
        },

        renderTrendCharts() {
            if (!this.trendData || this.trendData.length === 0) return;

            const labels = this.trendData.map(d => d.period);
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9CA3AF' : '#64748B';
            const gridColor = isDark ? '#2A2E35' : '#E2E8F0';

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor, font: { size: 11 }, boxWidth: 12, padding: 8 }
                    },
                },
                scales: {
                    x: {
                        ticks: { color: textColor, font: { size: 10 }, maxRotation: 45, minRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor },
                        grid: { color: gridColor },
                    },
                },
            };

            // Chart 1: Lesson Volume
            this.trendVolumeChart = null;
            const vol = this.resetCanvas('trendVolumeChart');
            if (vol) {
                try {
                    this.trendVolumeChart = new Chart(vol, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Thành công',
                                    data: this.trendData.map(d => d.total_success),
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                    borderColor: '#10B981',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Noshow',
                                    data: this.trendData.map(d => d.total_noshow),
                                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                    borderColor: '#EF4444',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                },
                                {
                                    label: '< 1/2 giờ',
                                    data: this.trendData.map(d => d.total_half),
                                    backgroundColor: 'rgba(251, 146, 60, 0.7)',
                                    borderColor: '#FB923C',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                }
                            ]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                x: { ...commonOptions.scales.x, stacked: true },
                                y: { ...commonOptions.scales.y, stacked: true },
                            },
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render trend volume chart:', e.message); }
            }

            // Chart 2: Success Rate Line
            this.trendSuccessRateChart = null;
            const sr = this.resetCanvas('trendSuccessRateChart');
            if (sr) {
                try {
                    this.trendSuccessRateChart = new Chart(sr, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Tỉ lệ TC (%)',
                                data: this.trendData.map(d => d.success_rate),
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#10B981',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                y: {
                                    ...commonOptions.scales.y,
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        ...commonOptions.scales.y.ticks,
                                        callback: (v) => v + '%'
                                    }
                                },
                            },
                            plugins: {
                                ...commonOptions.plugins,
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y + '%'
                                    }
                                }
                            }
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render trend success rate chart:', e.message); }
            }

            // Chart 3: Active Students
            this.trendStudentsChart = null;
            const st = this.resetCanvas('trendStudentsChart');
            if (st) {
                try {
                    this.trendStudentsChart = new Chart(st, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'HV hoạt động',
                                data: this.trendData.map(d => d.unique_students),
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#3B82F6',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: commonOptions,
                    });
                } catch (e) { console.warn('CSI: Failed to render trend students chart:', e.message); }
            }

            // Chart 4: Noshow & Half Trend
            this.trendAbsenceChart = null;
            const ab = this.resetCanvas('trendAbsenceChart');
            if (ab) {
                try {
                    this.trendAbsenceChart = new Chart(ab, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Noshow',
                                    data: this.trendData.map(d => d.total_noshow),
                                    borderColor: '#EF4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: '#EF4444',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: '< 1/2 giờ',
                                    data: this.trendData.map(d => d.total_half),
                                    borderColor: '#FB923C',
                                    backgroundColor: 'rgba(251, 146, 60, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: '#FB923C',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                }
                            ]
                        },
                        options: commonOptions,
                    });
                } catch (e) { console.warn('CSI: Failed to render trend absence chart:', e.message); }
            }

            // Health Category Trend Charts
            this.renderHealthTrendCharts(commonOptions);

            // OnTrack Trend Charts
            this.renderOntrackTrendCharts(commonOptions);
        },

        renderHealthTrendCharts(commonOptions) {
            if (!this.healthTrendData || this.healthTrendData.length === 0) return;

            const labels = this.healthTrendData.map(d => d.period);

            // Chart 5: Health Category Count (Stacked Bar)
            this.trendHealthCountChart = null;
            const hc = this.resetCanvas('trendHealthCountChart');
            if (hc) {
                try {
                    this.trendHealthCountChart = new Chart(hc, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Khỏe mạnh',
                                    data: this.healthTrendData.map(d => d.green),
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                    borderColor: '#10B981',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Cảnh báo',
                                    data: this.healthTrendData.map(d => d.yellow),
                                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                                    borderColor: '#F59E0B',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Báo động',
                                    data: this.healthTrendData.map(d => d.red),
                                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                    borderColor: '#EF4444',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                }
                            ]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                x: { ...commonOptions.scales.x, stacked: true },
                                y: { ...commonOptions.scales.y, stacked: true },
                            },
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render health count trend chart:', e.message); }
            }

            // Chart 6: Health Category Percentage (Line)
            this.trendHealthPctChart = null;
            const hp = this.resetCanvas('trendHealthPctChart');
            if (hp) {
                try {
                    const greenPct = this.healthTrendData.map(d => d.total_students > 0 ? parseFloat((d.green / d.total_students * 100).toFixed(1)) : 0);
                    const yellowPct = this.healthTrendData.map(d => d.total_students > 0 ? parseFloat((d.yellow / d.total_students * 100).toFixed(1)) : 0);
                    const redPct = this.healthTrendData.map(d => d.total_students > 0 ? parseFloat((d.red / d.total_students * 100).toFixed(1)) : 0);

                    this.trendHealthPctChart = new Chart(hp, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Khỏe mạnh (%)',
                                    data: greenPct,
                                    borderColor: '#10B981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: '#10B981',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Cảnh báo (%)',
                                    data: yellowPct,
                                    borderColor: '#F59E0B',
                                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: '#F59E0B',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Báo động (%)',
                                    data: redPct,
                                    borderColor: '#EF4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: '#EF4444',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                }
                            ]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                y: {
                                    ...commonOptions.scales.y,
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        ...commonOptions.scales.y.ticks,
                                        callback: (v) => v + '%'
                                    }
                                },
                            },
                            plugins: {
                                ...commonOptions.plugins,
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y + '%'
                                    }
                                }
                            }
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render health pct trend chart:', e.message); }
            }
        },

        renderOntrackTrendCharts(commonOptions) {
            if (!this.ontrackTrendData || this.ontrackTrendData.length === 0) return;

            const labels = this.ontrackTrendData.map(d => d.period);

            // Chart 7: OnTrack Rate Line
            this.trendOntrackChart = null;
            const ot = this.resetCanvas('trendOntrackChart');
            if (ot) {
                try {
                    this.trendOntrackChart = new Chart(ot, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Ontrack (%)',
                                data: this.ontrackTrendData.map(d => d.ontrack_rate),
                                borderColor: '#06B6D4',
                                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#06B6D4',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                y: {
                                    ...commonOptions.scales.y,
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        ...commonOptions.scales.y.ticks,
                                        callback: (v) => v + '%'
                                    }
                                },
                            },
                            plugins: {
                                ...commonOptions.plugins,
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const d = this.ontrackTrendData[ctx.dataIndex];
                                            return [
                                                'Ontrack: ' + ctx.parsed.y + '%',
                                                'HV ontrack (≥90% TC): ' + (d ? d.ontrack_by_success : 0) + ' / ' + (d ? d.total_students : 0) + ' HV',
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render ontrack trend chart:', e.message); }
            }

            // Chart 8: OnTrack Student Count (Stacked Bar) - Phase 191: student-level
            this.trendOntrackCountChart = null;
            const otc = this.resetCanvas('trendOntrackCountChart');
            if (otc) {
                try {
                    const activeNotOntrack = this.ontrackTrendData.map(d => d.total_active - d.ontrack_count);
                    this.trendOntrackCountChart = new Chart(otc, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'HV Ontrack (≥90%)',
                                    data: this.ontrackTrendData.map(d => d.ontrack_count),
                                    backgroundColor: 'rgba(6, 182, 212, 0.7)',
                                    borderColor: '#06B6D4',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                },
                                {
                                    label: 'HV Active chưa Ontrack',
                                    data: activeNotOntrack,
                                    backgroundColor: 'rgba(251, 146, 60, 0.7)',
                                    borderColor: '#FB923C',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                }
                            ]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                x: { ...commonOptions.scales.x, stacked: true },
                                y: { ...commonOptions.scales.y, stacked: true },
                            },
                        }
                    });
                } catch (e) { console.warn('CSI: Failed to render ontrack count trend chart:', e.message); }
            }
        },

        renderCharts() {
            this.renderHealthChart();
            this.renderScoreChart();
            this.renderCssChart();
            this.renderTeacherWarningChart();
        },

        /**
         * Safely reset a canvas element for Chart.js reuse.
         * 1. Destroys any existing Chart.js instance on the canvas (via Chart.getChart)
         * 2. Replaces the canvas DOM element with a fresh one
         * 3. Validates the new canvas has a working 2D context
         * Returns the new canvas element, or null if unusable.
         */
        resetCanvas(id) {
            const old = document.getElementById(id);
            if (!old) return null;

            // Destroy any lingering Chart.js instance on this canvas
            // (handles cases where the stored reference was lost or another render is in progress)
            try {
                const existing = Chart.getChart(old);
                if (existing) existing.destroy();
            } catch (e) { /* ignore */ }

            const parent = old.parentNode;
            if (!parent) return null;

            const canvas = document.createElement('canvas');
            canvas.id = id;
            parent.replaceChild(canvas, old);

            // Validate the new canvas has a working 2D rendering context
            const ctx2d = canvas.getContext('2d');
            if (!ctx2d) return null;

            return canvas;
        },

        renderHealthChart() {
            this.healthChart = null;
            const canvas = this.resetCanvas('healthChart');
            if (!canvas) return;

            const colorMap = {
                'Xanh (Khỏe mạnh)': { bg: 'rgba(16, 185, 129, 0.8)', border: '#10B981' },
                'Vàng (Cảnh báo)': { bg: 'rgba(245, 158, 11, 0.8)', border: '#F59E0B' },
                'Đỏ (Báo động)': { bg: 'rgba(239, 68, 68, 0.8)', border: '#EF4444' },
            };

            const data = this.healthDistribution || [];
            if (data.length === 0 || data.every(d => !d.count)) return;
            const labels = data.map(d => d.health_category);
            const values = data.map(d => d.count);
            const bgColors = labels.map(l => colorMap[l]?.bg || 'rgba(107,114,128,0.5)');
            const borderColors = labels.map(l => colorMap[l]?.border || '#6B7280');

            try {
                this.healthChart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: bgColors,
                            borderColor: borderColors,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B',
                                    font: { size: 12 },
                                    padding: 15,
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (tipCtx) => {
                                        const total = tipCtx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((tipCtx.raw / total) * 100).toFixed(1) : 0;
                                        return `${tipCtx.label}: ${tipCtx.raw.toLocaleString()} (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn('CSI: Failed to render health chart:', e.message);
            }
        },

        renderScoreChart() {
            this.scoreChart = null;
            const canvas = this.resetCanvas('scoreChart');
            if (!canvas) return;

            const data = this.scoreDistribution || [];
            if (data.length === 0 || data.every(d => !d.count)) return;
            const labels = data.map(d => d.label);
            const values = data.map(d => d.count);
            const colorMap = {
                '< 0': '#6B7280',
                '0-20': '#EF4444',
                '21-40': '#F97316',
                '41-60': '#F59E0B',
                '61-80': '#60A5FA',
                '81-100': '#10B981',
            };
            const colors = labels.map(l => colorMap[l] || '#6B7280');

            try {
                this.scoreChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Số HV',
                            data: values,
                            backgroundColor: colors.map(c => c + 'CC'),
                            borderColor: colors,
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B' },
                                grid: { color: document.documentElement.classList.contains('dark') ? '#2A2E35' : '#E2E8F0' },
                            },
                            x: {
                                ticks: { color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B' },
                                grid: { display: false },
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn('CSI: Failed to render score chart:', e.message);
            }
        },

        renderCssChart() {
            this.cssChart = null;
            const canvas = this.resetCanvas('cssChart');
            if (!canvas) return;

            const data = this.cssPerformance || [];
            if (data.length === 0) return;
            const labels = data.map(d => d.css_staff);

            try {
                this.cssChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Xanh',
                                data: data.map(d => d.green),
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                borderColor: '#10B981',
                                borderWidth: 1,
                                borderRadius: 4,
                            },
                            {
                                label: 'Vàng',
                                data: data.map(d => d.yellow),
                                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                                borderColor: '#F59E0B',
                                borderWidth: 1,
                                borderRadius: 4,
                            },
                            {
                                label: 'Đỏ',
                                data: data.map(d => d.red),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: '#EF4444',
                                borderWidth: 1,
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B',
                                    font: { size: 11 },
                                    boxWidth: 12,
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: { color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B', font: { size: 10 } },
                                grid: { display: false },
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: { color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B' },
                                grid: { color: document.documentElement.classList.contains('dark') ? '#2A2E35' : '#E2E8F0' },
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn('CSI: Failed to render CSS chart:', e.message);
            }
        },

        renderTeacherWarningChart() {
            this.teacherWarningChart = null;
            const canvas = this.resetCanvas('teacherWarningChart');
            if (!canvas) return;

            const data = this.teacherWarning || [];
            if (data.length === 0 || data.every(d => !d.count)) return;
            const colorMap = {
                'Bình thường': { bg: 'rgba(107, 114, 128, 0.6)', border: '#6B7280' },
                'Có ảnh hưởng (GV nghỉ 1b)': { bg: 'rgba(245, 158, 11, 0.7)', border: '#F59E0B' },
                'Nghiêm trọng (GV nghỉ >=2b)': { bg: 'rgba(249, 115, 22, 0.7)', border: '#F97316' },
                'Khẩn cấp (GV nghỉ >= 4 buổi)': { bg: 'rgba(239, 68, 68, 0.7)', border: '#EF4444' },
            };

            const labels = data.map(d => d.teacher_warning);
            const values = data.map(d => d.count);
            const bgColors = labels.map(l => colorMap[l]?.bg || 'rgba(107,114,128,0.5)');
            const borderColors = labels.map(l => colorMap[l]?.border || '#6B7280');

            try {
                this.teacherWarningChart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: bgColors,
                            borderColor: borderColors,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#64748B',
                                    font: { size: 11 },
                                    padding: 12,
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (tipCtx) => {
                                        const total = tipCtx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((tipCtx.raw / total) * 100).toFixed(1) : 0;
                                        return `${tipCtx.label}: ${tipCtx.raw.toLocaleString()} (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn('CSI: Failed to render teacher warning chart:', e.message);
            }
        },
    };
}
</script>
@endsection
