@extends('layouts.app')

@section('title', 'ICan Dashboard - Doanh thu')
@section('page-title', 'Báo cáo Doanh thu')

@section('content')
@php
    $activeProgram = request('program', 'all');
@endphp

<div class="space-y-4 md:space-y-6">
    <!-- Phase 138: Program indicator (📊 All) -->
    <x-program-tabs :activeProgram="$activeProgram" />

    <!-- Usage Report Export (Phase 26) -->
    <div class="bg-gradient-to-r from-teal-500/5 via-cyan-500/5 to-blue-500/5 dark:from-teal-500/10 dark:via-cyan-500/10 dark:to-blue-500/10 rounded-xl p-4 md:p-6 border border-teal-500/30 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                    📋 Báo cáo Sử dụng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Báo cáo Sử dụng</span><br>
Xuất báo cáo chi tiết sử dụng gói học theo khoảng thời gian.<br><br>
<b>Các cột:</b> Phân loại gói, Mã HV, Tên HV, Email, Trình độ, Size lớp, Quốc tịch GV, Mã gói Zeus, ID Billing, Tên gói, Số buổi, Item_ID, Ngày thanh toán, Ngày bắt đầu, Ngày kết thúc, Ngày hủy, Trạng thái, Giá/buổi, Dư đầu kỳ (Số buổi &amp; Số tiền), Mua trong kỳ (Số buổi &amp; Số tiền), Sử dụng (Số buổi &amp; Số tiền), Hủy - Số buổi, Cuối kỳ (Số buổi &amp; Số tiền), Zeus Order ID.<br><br>
<b>Chỉ xuất sản phẩm SPW</b> (35 subject IDs).<br>
<b>Chỉ đơn hoàn tất</b> (order_payment_status=1 AND order_status=2).<br>
<b>Chỉ buổi hoàn thành</b> (ordles_status = 3).<br>
<b>Thời gian bắt đầu buổi học nằm trong khoảng đã chọn</b> (ordles_lesson_starttime &gt;= từ ngày AND &lt; đến ngày).<br><br>
<b>Dư đầu kỳ:</b> Nếu đơn được thanh toán trong kỳ thì dư đầu kỳ = 0; nếu đơn có trước kỳ thì = tổng buổi − buổi đã dùng trước kỳ.<br>
<b>Mua trong kỳ:</b> Nếu ngày thanh toán nằm trong kỳ thì = tổng buổi/tiền; ngược lại = 0.<br>
<b>Sử dụng:</b> Số buổi hoàn thành (ordles_status=3) có ordles_lesson_endtime trong kỳ.<br>
<b>Cuối kỳ:</b> Dư đầu kỳ + Mua trong kỳ − Sử dụng − Giảm khác.<br><br>
<span class="tooltip-sql">SELECT o.order_id, ol.ordles_tlang_id,<br>
&nbsp;&nbsp;u.user_username, u.user_email,<br>
&nbsp;&nbsp;o.order_addedon AS payment_date,<br>
&nbsp;&nbsp;COUNT(ol.ordles_id) AS item_count,<br>
&nbsp;&nbsp;SUM(ol.ordles_amount) AS total_amount<br>
FROM tbl_orders o<br>
JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id<br>
JOIN tbl_users u ON o.order_user_id = u.user_id<br>
LEFT JOIN tbl_teach_languages tl ON ol.ordles_tlang_id = tl.tlang_id<br>
LEFT JOIN tbl_order_payments op ON op.ordpay_order_id = o.order_id<br>
WHERE o.order_payment_status = 1<br>
&nbsp;&nbsp;AND o.order_status = 2<br>
&nbsp;&nbsp;AND u.user_deleted IS NULL<br>
&nbsp;&nbsp;AND ol.ordles_tlang_id IN (558,560,...,471) -- 35 SPW IDs<br>
&nbsp;&nbsp;AND ol.ordles_status = 3 -- chỉ buổi hoàn thành<br>
&nbsp;&nbsp;AND ol.ordles_lesson_starttime &gt;= :start_date<br>
&nbsp;&nbsp;AND ol.ordles_lesson_starttime &lt; :end_date<br>
GROUP BY o.order_id, ol.ordles_tlang_id, ...</span>
</span></span>
                </h3>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-1">Xuất báo cáo chi tiết sử dụng gói học theo khoảng thời gian</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <div class="flex gap-2 items-center">
                    <input type="date" id="usage-start-date" class="px-3 py-2 rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text text-sm" />
                    <span class="text-light-text-muted dark:text-zeus-text-muted">→</span>
                    <input type="date" id="usage-end-date" class="px-3 py-2 rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text text-sm" />
                </div>
                <button id="btn-export-usage-report" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Xuất CSV
                </button>
            </div>
        </div>
        <!-- Export Progress Bar (Phase 28) -->
        <div id="export-progress-container" class="hidden mt-4">
            <div class="flex items-center justify-between mb-2">
                <span id="export-status-message" class="text-sm text-light-text dark:text-zeus-text">Đang chờ xử lý...</span>
                <span id="export-progress-percent" class="text-sm font-medium text-teal-600 dark:text-teal-400">0%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-zeus-card-light rounded-full h-3 overflow-hidden">
                <div id="export-progress-bar" class="bg-gradient-to-r from-teal-500 to-cyan-500 h-full transition-all duration-300 ease-out" style="width: 0%"></div>
            </div>
            <div class="mt-3 flex gap-2">
                <button id="btn-download-export" class="hidden px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Tải file
                </button>
                <button id="btn-cancel-export" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- Revenue Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-green-500/30 shadow-sm">
            <p class="text-xs md:text-sm font-medium text-green-600 dark:text-green-400">
                Doanh thu Hôm nay
                <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Điều kiện: order_payment_status=1 (PAID), order_addedon=hôm nay</span></span>
            </p>
            <p class="text-2xl md:text-4xl font-bold text-green-500 mt-2">{{ number_format($revenue['today'] ?? 0) }}</p>
            <p class="text-green-600/70 dark:text-green-400/70 text-xs md:text-sm mt-1">VNĐ</p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-blue-500/30 shadow-sm">
            <p class="text-xs md:text-sm font-medium text-blue-600 dark:text-blue-400">
                Doanh thu Tuần này
                <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Điều kiện: order_payment_status=1 (PAID), 7 ngày qua</span></span>
            </p>
            <p class="text-2xl md:text-4xl font-bold text-blue-500 mt-2">{{ number_format($revenue['this_week'] ?? 0) }}</p>
            <p class="text-blue-600/70 dark:text-blue-400/70 text-xs md:text-sm mt-1">VNĐ</p>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-purple-500/30 shadow-sm">
            <p class="text-xs md:text-sm font-medium text-purple-600 dark:text-purple-400">
                Doanh thu Tháng này
                <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Điều kiện: order_payment_status=1 (PAID), tháng hiện tại</span></span>
            </p>
            <p class="text-2xl md:text-4xl font-bold text-purple-500 mt-2">{{ number_format($revenue['this_month'] ?? 0) }}</p>
            <p class="text-purple-600/70 dark:text-purple-400/70 text-xs md:text-sm mt-1">VNĐ</p>
        </div>
    </div>

    <!-- Order Analytics (NEW) -->
    <div class="bg-gradient-to-r from-indigo-500/5 via-purple-500/5 to-pink-500/5 dark:from-indigo-500/10 dark:via-purple-500/10 dark:to-pink-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            📊 Phân tích Đơn hàng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Thống kê đơn hàng tổng quan, đơn 0đ, trạng thái thanh toán</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
            <!-- Total Orders -->
            <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-lg md:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($revenue['total_orders'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">Tổng đơn hàng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng đơn hàng</span><br>
Tổng số đơn hàng trong hệ thống.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_orders</span>
</span></span>
                </p>
            </div>
            <!-- Average Order Value -->
            <div class="text-center p-3 md:p-4 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                <p class="text-lg md:text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($revenue['average_order_value'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-emerald-600/80 dark:text-emerald-400/80">TB/Đơn (VNĐ)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Giá trị trung bình mỗi đơn</span><br>
Giá trị trung bình của các đơn hàng đã thanh toán.<br><br>
<span class="tooltip-sql">SELECT AVG(order_total_amount)<br>
FROM tbl_orders<br>
WHERE order_payment_status=1</span>
</span></span>
                </p>
            </div>
            <!-- Unique Customers -->
            <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                <p class="text-lg md:text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($revenue['unique_customers'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">Khách hàng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số khách hàng</span><br>
Số lượng khách hàng duy nhất đã đặt hàng.<br><br>
<span class="tooltip-sql">SELECT COUNT(DISTINCT order_user_id)<br>
FROM tbl_orders</span>
</span></span>
                </p>
            </div>
            <!-- Average Orders Per Customer -->
            <div class="text-center p-3 md:p-4 bg-indigo-500/10 rounded-lg border border-indigo-500/30">
                <p class="text-lg md:text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $revenue['avg_orders_per_customer'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-indigo-600/80 dark:text-indigo-400/80">Đơn/Khách
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số đơn trung bình mỗi khách</span><br>
Trung bình số đơn hàng mà mỗi khách hàng đặt.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) / COUNT(DISTINCT order_user_id)<br>
FROM tbl_orders</span>
</span></span>
                </p>
            </div>
            <!-- Payment Completion Rate -->
            <div class="text-center p-3 md:p-4 bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-lg md:text-2xl font-bold text-green-600 dark:text-green-400">{{ $revenue['payment_completion_rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-green-600/80 dark:text-green-400/80">Tỷ lệ TT
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ thanh toán</span><br>
Phần trăm đơn hàng đã thanh toán trên tổng số đơn.<br><br>
<span class="tooltip-sql">SELECT (COUNT(*) WHERE order_payment_status=1)<br>
/ COUNT(*) * 100<br>
FROM tbl_orders</span>
</span></span>
                </p>
            </div>
            <!-- Zero-value Rate -->
            <div class="text-center p-3 md:p-4 bg-amber-500/10 rounded-lg border border-amber-500/30">
                <p class="text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $revenue['zero_value_rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-amber-600/80 dark:text-amber-400/80">Tỷ lệ đơn 0đ
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ đơn 0đ</span><br>
Phần trăm đơn hàng có giá trị bằng 0.<br><br>
<span class="tooltip-sql">SELECT (COUNT(*) WHERE order_total_amount=0)<br>
/ COUNT(*) * 100<br>
FROM tbl_orders</span>
</span></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Payment Status Analysis (NEW) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        <!-- Payment Status Summary -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                💰 Trạng thái Thanh toán
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Số lượng đơn Đã TT / Chờ TT, Doanh thu đã thu / đang chờ thu</span></span>
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                    <p class="text-lg md:text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($revenue['paid_orders_count'] ?? 0) }}</p>
                    <p class="text-xs text-green-600/80 dark:text-green-400/80">✅ Đã thanh toán
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn đã thanh toán</span><br>
Số lượng đơn hàng đã được thanh toán.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_orders<br>
WHERE order_payment_status=1</span>
</span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30">
                    <p class="text-lg md:text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($revenue['unpaid_orders_count'] ?? 0) }}</p>
                    <p class="text-xs text-orange-600/80 dark:text-orange-400/80">⏳ Chờ thanh toán
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn chờ thanh toán</span><br>
Số lượng đơn hàng chưa thanh toán.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_orders<br>
WHERE order_payment_status=0</span>
</span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                    <p class="text-lg md:text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($revenue['total_paid_revenue'] ?? 0) }}</p>
                    <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">💵 Doanh thu đã thu (VNĐ)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Doanh thu đã thu</span><br>
Tổng doanh thu từ các đơn đã thanh toán.<br><br>
<span class="tooltip-sql">SELECT SUM(order_total_amount)<br>
FROM tbl_orders<br>
WHERE order_payment_status=1</span>
</span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-amber-500/10 rounded-lg border border-amber-500/30">
                    <p class="text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($revenue['pending_revenue'] ?? 0) }}</p>
                    <p class="text-xs text-amber-600/80 dark:text-amber-400/80">⏳ Doanh thu chờ thu (VNĐ)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Doanh thu chờ thu</span><br>
Tổng doanh thu từ các đơn chưa thanh toán.<br><br>
<span class="tooltip-sql">SELECT SUM(order_total_amount)<br>
FROM tbl_orders<br>
WHERE order_payment_status=0</span>
</span></span>
                    </p>
                </div>
            </div>
            <div class="mt-3 p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted text-center">
                    Giá trị TB đơn Chờ TT: <span class="font-bold text-light-text dark:text-zeus-text">{{ number_format($revenue['average_pending_order_value'] ?? 0) }} VNĐ</span>
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Giá trị TB đơn Chờ TT</span><br>
Giá trị trung bình của các đơn chưa thanh toán.<br><br>
<span class="tooltip-sql">SELECT AVG(order_total_amount)<br>
FROM tbl_orders<br>
WHERE order_payment_status=0</span>
</span></span>
                </p>
            </div>
        </div>

        <!-- Zero-value vs Non-zero Orders -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📉 Phân tích Đơn 0đ
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Tỷ lệ đơn 0đ so với tổng số đơn, Tỷ lệ đơn có giá trị > 0đ</span></span>
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-amber-500/10 rounded-lg border border-amber-500/30">
                    <p class="text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($revenue['zero_value_orders'] ?? 0) }}</p>
                    <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Đơn 0đ
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn 0đ</span><br>
Số lượng đơn hàng có giá trị bằng 0.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_orders<br>
WHERE order_total_amount=0</span>
</span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                    <p class="text-lg md:text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($revenue['non_zero_value_orders'] ?? 0) }}</p>
                    <p class="text-xs text-green-600/80 dark:text-green-400/80">Đơn > 0đ
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn > 0đ</span><br>
Số lượng đơn hàng có giá trị lớn hơn 0.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_orders<br>
WHERE order_total_amount>0</span>
</span></span>
                    </p>
                </div>
            </div>
            <!-- Progress bar for zero-value ratio -->
            <div class="mt-4">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-amber-600 dark:text-amber-400">Đơn 0đ: {{ $revenue['zero_value_rate'] ?? 0 }}%</span>
                    <span class="text-green-600 dark:text-green-400">Đơn > 0đ: {{ $revenue['non_zero_value_rate'] ?? 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-zeus-card-light rounded-full h-3 overflow-hidden">
                    <div class="h-full flex">
                        <div class="bg-amber-500 h-full" style="width: {{ $revenue['zero_value_rate'] ?? 0 }}%"></div>
                        <div class="bg-green-500 h-full" style="width: {{ $revenue['non_zero_value_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase 103/104: First Orders with Successful Lessons (moved above chart, with sorting & null filter) -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="firstOrdersSection()" x-init="loadData(1)">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            📋 Đơn hàng đầu tiên & Buổi học thành công sau thanh toán
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn hàng đầu tiên & Buổi học thành công</span><br>
Danh sách học viên có buổi học thành công (acceptance_code IN (9,12)) sau khi thanh toán đơn hàng đầu tiên qua phương thức thanh toán 13.<br><br>
<b>Lưu ý:</b> Nếu record có trường <code>first_lesson_start_time</code> (<code>ordles_lesson_starttime</code>) = NULL có nghĩa là đơn hàng đầu tiên đó bị hủy, chưa học 1 buổi nào.<br><br>
Case này sẽ gặp khi: Sale lên đơn nhưng chọn nhầm giáo viên hoặc chọn nhầm giáo trình. Khi đó vận hành sẽ phải sửa cho sale bằng cách, hủy (cancel) các buổi học vừa mua đi, lấy credit + cấp thêm voucher bù vào để mua 1 đơn mới đúng giáo viên và giáo trình.<br><br>
<span class="tooltip-sql">WITH first_orders AS (<br>
&nbsp;&nbsp;SELECT o.order_id, o.order_user_id, o.order_item_count,<br>
&nbsp;&nbsp;&nbsp;&nbsp;o.order_net_amount, o.order_addedon, op.ordpay_datetime,<br>
&nbsp;&nbsp;&nbsp;&nbsp;ROW_NUMBER() OVER (PARTITION BY o.order_user_id<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ORDER BY op.ordpay_datetime ASC, o.order_id ASC) AS rn<br>
&nbsp;&nbsp;FROM tbl_orders o<br>
&nbsp;&nbsp;INNER JOIN tbl_order_payments op ON o.order_id = op.ordpay_order_id<br>
&nbsp;&nbsp;WHERE op.ordpay_pmethod_id = 13 AND op.ordpay_amount > 0<br>
&nbsp;&nbsp;&nbsp;&nbsp;AND o.order_net_amount > 0<br>
&nbsp;&nbsp;&nbsp;&nbsp;AND op.ordpay_datetime >= '2026-01-04 17:00:00'<br>
&nbsp;&nbsp;&nbsp;&nbsp;AND o.order_payment_status = 1 AND o.order_status = 2<br>
),<br>
first_lessons AS (<br>
&nbsp;&nbsp;SELECT fo.order_id, ol.ordles_id, ol.ordles_lesson_starttime,<br>
&nbsp;&nbsp;&nbsp;&nbsp;ROW_NUMBER() OVER (PARTITION BY fo.order_id<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ORDER BY ol.ordles_lesson_starttime ASC, ol.ordles_id ASC) AS rn_lesson<br>
&nbsp;&nbsp;FROM first_orders fo<br>
&nbsp;&nbsp;INNER JOIN tbl_order_lessons ol ON ol.ordles_order_id = fo.order_id<br>
&nbsp;&nbsp;INNER JOIN tbl_order_lessons_extras ole ON ole.ole_ordles_id = ol.ordles_id<br>
&nbsp;&nbsp;WHERE fo.rn = 1 AND ole.ole_acceptance_code IN (9,12)<br>
)<br>
SELECT fo.order_id, fo.order_user_id, fo.order_item_count,<br>
&nbsp;&nbsp;fo.order_net_amount, fo.order_addedon,<br>
&nbsp;&nbsp;fl.ordles_lesson_starttime AS first_lesson_start_time<br>
FROM first_orders fo<br>
LEFT JOIN first_lessons fl ON fo.order_id = fl.order_id AND fl.rn_lesson = 1<br>
WHERE fo.rn = 1<br>
ORDER BY fo.order_addedon</span>
</span></span>
        </h3>

        <!-- Search, Filter & Export -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                    x-model="searchQuery"
                    @input.debounce.400ms="loadData(1)"
                    placeholder="Tìm theo tên, email, mã đơn hàng, user ID..."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:ring-2 focus:ring-zeus-accent/50 focus:border-zeus-accent outline-none">
            </div>
            <label class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border cursor-pointer transition"
                :class="filterNullLesson ? 'border-amber-500/50 bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'border-light-border dark:border-zeus-border text-light-text-muted dark:text-zeus-text-muted hover:bg-light-card-alt dark:hover:bg-zeus-card-light'">
                <input type="checkbox" x-model="filterNullLesson" @change="loadData(1)" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/50">
                <span>Chỉ NULL ⚠️</span>
            </label>
            <button @click="exportToExcel()" class="px-3 py-2 text-sm bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/20 transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xuất Excel
            </button>
        </div>

        <!-- Phase 105: Advanced Filters - Days Difference & Order Date Range -->
        <div class="flex flex-wrap items-end gap-3 mb-4 p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
            <!-- Days difference filter -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">
                    Chưa xếp lớp ≥
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Lọc theo số ngày chưa xếp lớp</span><br>
Lọc học viên có khoảng cách giữa buổi học đầu tiên và ngày đặt hàng ≥ số ngày nhập vào.<br><br>
<span class="tooltip-sql">DATEDIFF(fl.ordles_lesson_starttime, vfo.order_addedon) >= N</span><br>
<br>Kết quả sẽ hiển thị thêm cột "Ngày chờ" (theo ngày) và "Giờ chờ" (theo giờ).
</span></span>
                </label>
                <div class="flex items-center">
                    <button @click="if(daysDifference > 0) { daysDifference--; loadData(1); }"
                        class="px-2 py-1 text-sm rounded-l-lg border border-r-0 border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition select-none">−</button>
                    <input type="number" x-model.number="daysDifference" @change="loadData(1)" min="0" step="1"
                        class="w-16 px-2 py-1 text-sm text-center border-y border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <button @click="daysDifference++; loadData(1);"
                        class="px-2 py-1 text-sm rounded-r-lg border border-l-0 border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition select-none">+</button>
                </div>
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">ngày</span>
                <label class="flex items-center gap-1 ml-2 px-2 py-1 text-sm rounded-lg border cursor-pointer transition"
                    :class="filterDaysDifference ? 'border-blue-500/50 bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'border-light-border dark:border-zeus-border text-light-text-muted dark:text-zeus-text-muted hover:bg-light-card-alt dark:hover:bg-zeus-card-light'">
                    <input type="checkbox" x-model="filterDaysDifference" @change="loadData(1)" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500/50">
                    <span>Bật lọc</span>
                </label>
            </div>

            <!-- Separator -->
            <div class="hidden md:block w-px h-8 bg-light-border dark:bg-zeus-border"></div>

            <!-- Date range filter -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">
                    Ngày mua hàng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Lọc theo thời gian mua hàng</span><br>
Lọc danh sách theo ngày đặt hàng (order_addedon).<br><br>
<span class="tooltip-sql">WHERE fo.order_addedon >= 'from_date'<br>AND fo.order_addedon <= 'to_date 23:59:59'</span>
</span></span>
                </label>
                <input type="date" x-model="orderDateFrom" @change="loadData(1)"
                    class="px-2 py-1 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
                <span class="text-light-text-muted dark:text-zeus-text-muted text-sm">→</span>
                <input type="date" x-model="orderDateTo" @change="loadData(1)"
                    class="px-2 py-1 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
            </div>
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
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Order ID</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">User ID</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Họ tên</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email</th>
                                <th @click="toggleSort('order_item_count')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition">
                                    Số buổi
                                    <span x-show="sortBy === 'order_item_count'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('order_net_amount')" class="px-3 py-2 text-right text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition">
                                    Giá trị đơn (VNĐ)
                                    <span x-show="sortBy === 'order_net_amount'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('order_addedon')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition">
                                    Ngày đặt hàng
                                    <span x-show="sortBy === 'order_addedon'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('first_lesson_start_time')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition">
                                    Buổi học đầu tiên
                                    <span x-show="sortBy === 'first_lesson_start_time'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('days_difference')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition">
                                    Ngày chờ
                                    <span x-show="sortBy === 'days_difference'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">
                                    Giờ chờ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                                        Chưa có dữ liệu
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in items" :key="item.order_id">
                                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="'#' + item.order_id"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="item.order_user_id"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-medium" x-text="item.student_name"></td>
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.student_email"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text" x-text="item.order_item_count"></td>
                                    <td class="px-3 py-2 text-right font-semibold text-green-500" x-text="Number(item.order_net_amount).toLocaleString('vi-VN') + 'đ'"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.order_addedon"></td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="item.first_lesson_start_time">
                                            <span class="text-xs text-light-text dark:text-zeus-text" x-text="item.first_lesson_start_time"></span>
                                        </template>
                                        <template x-if="!item.first_lesson_start_time">
                                            <span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-500" title="Đơn hàng đầu tiên bị hủy, chưa học buổi nào">NULL ⚠️</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="item.days_difference !== null && item.days_difference !== undefined">
                                            <span class="text-xs font-medium" :class="item.days_difference >= 2 ? 'text-red-500' : 'text-green-500'" x-text="item.days_difference + ' ngày'"></span>
                                        </template>
                                        <template x-if="item.days_difference === null || item.days_difference === undefined">
                                            <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="item.time_difference !== null && item.time_difference !== undefined">
                                            <span class="text-xs text-light-text dark:text-zeus-text" x-text="item.time_difference"></span>
                                        </template>
                                        <template x-if="item.time_difference === null || item.time_difference === undefined">
                                            <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">—</span>
                                        </template>
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
                        (Tổng: <strong x-text="pagination.total"></strong> bản ghi)
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

                <!-- Total count (when only 1 page) -->
                <div class="mt-4" x-show="pagination.last_page <= 1 && items.length > 0">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="pagination.total"></strong> bản ghi
                    </span>
                </div>
            </div>
        </template>
    </div>

    <!-- Phase 112: Trial Lessons Statistics Table -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="trialLessonsSection()" x-init="loadData(1)">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            🎓 Thống kê Buổi học Trial & Kết quả đánh giá GV
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Buổi học Trial & Kết quả đánh giá</span><br>
Danh sách các buổi học trial bao gồm kết quả đánh giá của giáo viên (assessment detail).<br><br>
<b>Dữ liệu bao gồm:</b> Thông tin học viên, ngày đặt lịch, ngày học trial, ghi chú, trạng thái, kết quả đánh giá (level, score, suggested subject).<br><br>
<span class="tooltip-sql">SELECT ol.ordles_id, ol.ordles_beneficiary_id,<br>
&nbsp;&nbsp;u.user_username, u.user_email,<br>
&nbsp;&nbsp;CONCAT(u.user_first_name, ' ', u.user_last_name),<br>
&nbsp;&nbsp;DATE(CONVERT_TZ(o.order_addedon, '+00:00', '+07:00')),<br>
&nbsp;&nbsp;DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')),<br>
&nbsp;&nbsp;ln.lesnote_content, tf.teafeed_assessment_detail<br>
FROM tbl_order_lessons ol<br>
INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id<br>
LEFT JOIN tbl_users u ON u.user_id = ol.ordles_beneficiary_id<br>
LEFT JOIN tbl_lesson_notes ln ON ln.lesnote_ordles_id = ol.ordles_id<br>
LEFT JOIN tbl_teacher_feedbacks tf ON tf.teafeed_learner_id = ol.ordles_beneficiary_id<br>
&nbsp;&nbsp;AND tf.teafeed_record_id = ol.ordles_id AND tf.teafeed_record_type = 1 AND tf.teafeed_type = 1<br>
WHERE ol.ordles_tlang_id = (SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID')<br>
&nbsp;&nbsp;AND ol.ordles_status IN (1,2,3,4)<br>
&nbsp;&nbsp;AND ol.ordles_lesson_starttime >= :start_date AND < :end_date</span>
</span></span>
        </h3>

        <!-- Date Range & Search Filters -->
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div class="flex items-center gap-2">
                <label class="text-sm text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">Từ ngày</label>
                <input type="date" x-model="startDate" @change="loadData(1)"
                    class="px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">Đến ngày</label>
                <input type="date" x-model="endDate" @change="loadData(1)"
                    class="px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent/50 outline-none">
            </div>
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                    x-model="searchQuery"
                    @input.debounce.400ms="loadData(1)"
                    placeholder="Tìm theo tên, email, username, ID..."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:ring-2 focus:ring-zeus-accent/50 outline-none">
            </div>
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
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Trial ID</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">User ID</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Họ tên</th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email</th>
                                <th @click="toggleSort('trial_request_date')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition whitespace-nowrap">
                                    Ngày đặt
                                    <span x-show="sortBy === 'trial_request_date'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('trial_date')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition whitespace-nowrap">
                                    Ngày học
                                    <span x-show="sortBy === 'trial_date'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th @click="toggleSort('trial_status')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition whitespace-nowrap">
                                    Trạng thái
                                    <span x-show="sortBy === 'trial_status'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Chương trình</th>
                                <th @click="toggleSort('trial_feedback_level')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition whitespace-nowrap">
                                    Level
                                    <span x-show="sortBy === 'trial_feedback_level'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Đề xuất</th>
                                <th @click="toggleSort('trial_feedback_date')" class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer hover:text-light-text dark:hover:text-zeus-text select-none transition whitespace-nowrap">
                                    Ngày đánh giá
                                    <span x-show="sortBy === 'trial_feedback_date'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-zeus-accent ml-1"></span>
                                </th>
                                <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="13" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">
                                        Chưa có dữ liệu
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in items" :key="item.trial_id">
                                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="'#' + item.trial_id"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="item.trial_user_id"></td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text font-medium text-xs" x-text="item.trial_user_fullname"></td>
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.trial_user_email"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs whitespace-nowrap" x-text="item.trial_request_date"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs whitespace-nowrap" x-text="item.trial_date"></td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap"
                                            :class="{
                                                'bg-green-500/20 text-green-500': item.trial_status === 'COMPLETED',
                                                'bg-blue-500/20 text-blue-500': item.trial_status === 'SCHEDULED',
                                                'bg-red-500/20 text-red-500': item.trial_status === 'CANCELLED',
                                                'bg-amber-500/20 text-amber-500': item.trial_status === 'UNSCHEDULED',
                                            }"
                                            x-text="item.trial_status"></span>
                                    </td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.trial_program_name || '—'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="item.trial_feedback_level">
                                            <span class="px-2 py-1 text-xs rounded-full bg-purple-500/20 text-purple-500 font-medium whitespace-nowrap" x-text="item.trial_feedback_level"></span>
                                        </template>
                                        <template x-if="!item.trial_feedback_level">
                                            <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.trial_suggested_subject_name || '—'"></td>
                                    <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs whitespace-nowrap" x-text="item.trial_feedback_date || '—'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button @click="showDetail(item)"
                                            class="px-2 py-1 text-xs bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-500/20 transition whitespace-nowrap">
                                            Xem
                                        </button>
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
                        (Tổng: <strong x-text="pagination.total"></strong> bản ghi)
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

                <!-- Total count (when only 1 page) -->
                <div class="mt-4" x-show="pagination.last_page <= 1 && items.length > 0">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="pagination.total"></strong> bản ghi
                    </span>
                </div>
            </div>
        </template>

        <!-- Detail Modal -->
        <div x-show="detailItem !== null" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="detailItem = null"
            @keydown.escape.window="detailItem = null">
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto border border-light-border dark:border-zeus-border shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-light-text dark:text-zeus-text">
                        Chi tiết Trial #<span x-text="detailItem?.trial_id"></span>
                    </h4>
                    <button @click="detailItem = null" class="text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="detailItem">
                    <div class="space-y-4">
                        <!-- Student Info -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Học viên</p>
                                <p class="text-sm font-medium text-light-text dark:text-zeus-text" x-text="detailItem.trial_user_fullname"></p>
                            </div>
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Email</p>
                                <p class="text-sm text-light-text dark:text-zeus-text" x-text="detailItem.trial_user_email"></p>
                            </div>
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Username</p>
                                <p class="text-sm text-light-text dark:text-zeus-text" x-text="detailItem.trial_user_username"></p>
                            </div>
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Trạng thái</p>
                                <span class="px-2 py-1 text-xs rounded-full"
                                    :class="{
                                        'bg-green-500/20 text-green-500': detailItem.trial_status === 'COMPLETED',
                                        'bg-blue-500/20 text-blue-500': detailItem.trial_status === 'SCHEDULED',
                                        'bg-red-500/20 text-red-500': detailItem.trial_status === 'CANCELLED',
                                        'bg-amber-500/20 text-amber-500': detailItem.trial_status === 'UNSCHEDULED',
                                    }"
                                    x-text="detailItem.trial_status"></span>
                            </div>
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Ngày đặt lịch</p>
                                <p class="text-sm text-light-text dark:text-zeus-text" x-text="detailItem.trial_request_date || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Ngày học trial</p>
                                <p class="text-sm text-light-text dark:text-zeus-text" x-text="detailItem.trial_date || '—'"></p>
                            </div>
                        </div>

                        <!-- Trial Note -->
                        <template x-if="detailItem.trial_note">
                            <div class="p-3 bg-amber-500/5 rounded-lg border border-amber-500/20">
                                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1">📝 Ghi chú Trial</p>
                                <pre class="text-sm text-light-text dark:text-zeus-text whitespace-pre-wrap font-sans" x-text="detailItem.trial_note"></pre>
                            </div>
                        </template>

                        <!-- Feedback Assessment -->
                        <template x-if="detailItem.trial_feedback_content">
                            <div class="p-3 bg-blue-500/5 rounded-lg border border-blue-500/20">
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-2">📋 Kết quả đánh giá GV</p>
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Chương trình:</span>
                                        <span class="text-xs font-medium text-light-text dark:text-zeus-text ml-1" x-text="detailItem.trial_program_name || '—'"></span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Level:</span>
                                        <span class="text-xs font-medium text-purple-500 ml-1" x-text="detailItem.trial_feedback_level || '—'"></span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đề xuất:</span>
                                        <span class="text-xs font-medium text-light-text dark:text-zeus-text ml-1" x-text="detailItem.trial_suggested_subject_name || '—'"></span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">Ngày đánh giá:</span>
                                        <span class="text-xs text-light-text dark:text-zeus-text ml-1" x-text="detailItem.trial_feedback_date || '—'"></span>
                                    </div>
                                </div>
                                <pre class="text-xs text-light-text dark:text-zeus-text whitespace-pre-wrap font-sans leading-relaxed bg-light-card-alt dark:bg-zeus-card-light rounded p-2" x-text="detailItem.trial_feedback_content"></pre>
                            </div>
                        </template>
                        <template x-if="!detailItem.trial_feedback_content">
                            <div class="p-3 bg-gray-500/5 rounded-lg border border-gray-500/20">
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted text-center">Chưa có kết quả đánh giá từ giáo viên</p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📈 Biểu đồ Doanh thu 30 ngày
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>SUM(order_total_amount) GROUP BY ngày, payment_status=1</span></span>
        </h3>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <!-- Wallet Stats -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            💳 Giao dịch Ví
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_wallet_transactions</span><br>Phân loại theo waltrn_type (nạp/rút/thanh toán GV/hoàn tiền)</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($walletStats['total_deposits'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Tổng nạp tiền
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng nạp tiền</span><br>
Tổng số tiền nạp vào ví.<br><br>
<span class="tooltip-sql">SELECT SUM(usrtxn_amount)<br>
FROM tbl_wallet_transactions<br>
WHERE usrtxn_type=1</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($walletStats['total_withdrawals'] ?? 0) }}</p>
                <p class="text-sm text-red-600/80 dark:text-red-400/80">Tổng rút tiền
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng rút tiền</span><br>
Tổng số tiền rút từ ví.<br><br>
<span class="tooltip-sql">SELECT SUM(usrtxn_amount)<br>
FROM tbl_wallet_transactions<br>
WHERE usrtxn_type=2</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($walletStats['teacher_payments'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Thanh toán GV
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Thanh toán GV</span><br>
Tổng số tiền thanh toán cho giáo viên.<br><br>
<span class="tooltip-sql">SELECT SUM(usrtxn_amount)<br>
FROM tbl_wallet_transactions<br>
WHERE usrtxn_type=5</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/30">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($walletStats['refunds'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Hoàn tiền
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hoàn tiền</span><br>
Tổng số tiền hoàn trả cho khách hàng.<br><br>
<span class="tooltip-sql">SELECT SUM(usrtxn_amount)<br>
FROM tbl_wallet_transactions<br>
WHERE usrtxn_type=7</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/30">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($walletStats['net_balance'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Số dư ròng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số dư ròng</span><br>
Chênh lệch giữa tổng nạp và tổng rút.<br><br>
<span class="tooltip-sql">Tổng nạp tiền - Tổng rút tiền</span>
</span></span>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order by Type -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📦 Đơn hàng theo Loại
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>GROUP BY order_type</span></span>
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @foreach($ordersByType as $type => $count)
                <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border hover:border-blue-500/50 transition">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">{{ $type }}</span>
                    <span class="font-semibold text-light-text dark:text-zeus-text bg-blue-500/10 dark:bg-blue-500/20 px-3 py-1 rounded-full">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Order Status -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📊 Trạng thái Đơn hàng
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>GROUP BY order_status (1=Đang xử lý, 2=Hoàn thành, 3=Đã hủy)</span></span>
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <span class="text-amber-600 dark:text-amber-400">🔄 Đang xử lý</span>
                    <span class="font-semibold text-amber-600 dark:text-amber-400 bg-amber-500/10 dark:bg-amber-500/20 px-3 py-1 rounded-full">{{ number_format($ordersByStatus['in_process'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">✅ Hoàn thành</span>
                    <span class="font-semibold text-green-600 dark:text-green-400 bg-green-500/10 dark:bg-green-500/20 px-3 py-1 rounded-full">{{ number_format($ordersByStatus['completed'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <span class="text-red-600 dark:text-red-400">❌ Đã hủy</span>
                    <span class="font-semibold text-red-600 dark:text-red-400 bg-red-500/10 dark:bg-red-500/20 px-3 py-1 rounded-full">{{ number_format($ordersByStatus['cancelled'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-blue-600 dark:text-blue-400">💳 Đã thanh toán</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400 bg-blue-500/10 dark:bg-blue-500/20 px-3 py-1 rounded-full">{{ number_format($ordersByStatus['paid'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                    <span class="text-orange-600 dark:text-orange-400">⏳ Chưa thanh toán</span>
                    <span class="font-semibold text-orange-600 dark:text-orange-400 bg-orange-500/10 dark:bg-orange-500/20 px-3 py-1 rounded-full">{{ number_format($ordersByStatus['unpaid'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Learners Table -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">💎 Top Học viên (theo chi tiêu)
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Top Học viên</span><br>
Top 10 học viên có tổng chi tiêu cao nhất.<br><br>
<span class="tooltip-sql">SELECT order_user_id, COUNT(*), SUM(order_total_amount)<br>
FROM tbl_orders<br>
WHERE order_payment_status=1<br>
GROUP BY order_user_id<br>
ORDER BY SUM(order_total_amount) DESC<br>
LIMIT 10</span>
</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">#</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Học viên</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Email</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số đơn</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Tổng chi tiêu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topLearners as $index => $learner)
                    <tr class="border-b border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                        <td class="py-3 px-4">
                            <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm font-medium text-light-text dark:text-zeus-text">{{ $learner['name'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $learner['email'] }}</td>
                        <td class="py-3 px-4 text-sm text-right text-light-text dark:text-zeus-text">{{ number_format($learner['orders']) }}</td>
                        <td class="py-3 px-4 text-sm text-right font-semibold text-green-500">{{ number_format($learner['total_spent']) }}đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">🕐 Đơn hàng Gần đây
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đơn hàng Gần đây</span><br>
10 đơn hàng mới nhất trong hệ thống.<br><br>
<span class="tooltip-sql">SELECT * FROM tbl_orders<br>
ORDER BY order_addedon DESC<br>
LIMIT 10</span>
</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">ID</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Khách hàng</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Loại</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời gian</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số tiền</th>
                        <th class="text-center py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr class="border-b border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                        <td class="py-3 px-4 text-sm text-light-text dark:text-zeus-text">#{{ $order['id'] }}</td>
                        <td class="py-3 px-4 text-sm font-medium text-light-text dark:text-zeus-text">{{ $order['user_name'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $order['type'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $order['date'] }}</td>
                        <td class="py-3 px-4 text-sm text-right font-semibold text-light-text dark:text-zeus-text">{{ number_format($order['amount']) }}đ</td>
                        <td class="py-3 px-4 text-center">
                            @if($order['payment_status'] == 1)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-500">Đã TT</span>
                            @else
                            <span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-500">Chờ TT</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Distribution -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">💰 Phân bố Thanh toán
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Phân bố Thanh toán</span><br>
Phân bố chi tiết các khoản thanh toán trong hệ thống.<br><br>
<span class="tooltip-sql">Dữ liệu từ tbl_order_lessons và tbl_orders</span>
</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($paymentStats['total_paid'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã trả GV
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã trả GV</span><br>
Tổng số tiền đã thanh toán cho giáo viên.<br><br>
<span class="tooltip-sql">SELECT SUM(ordles_teacher_paid)<br>
FROM tbl_order_lessons</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($paymentStats['system_commission'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Hoa hồng hệ thống
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hoa hồng hệ thống</span><br>
Tổng hoa hồng hệ thống thu được từ các giao dịch.<br><br>
<span class="tooltip-sql">SELECT SUM(ordles_commission_amount)<br>
FROM tbl_order_lessons</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/30">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($paymentStats['affiliate_commission'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Hoa hồng Affiliate
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hoa hồng Affiliate</span><br>
Tổng hoa hồng trả cho các affiliate giới thiệu.<br><br>
<span class="tooltip-sql">SELECT SUM(ordles_affiliate_commission)<br>
FROM tbl_order_lessons</span>
</span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/30">
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($revenue['total_discount'] ?? 0) }}</p>
                <p class="text-sm text-orange-600/80 dark:text-orange-400/80">Tổng giảm giá
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng giảm giá</span><br>
Tổng giá trị giảm giá đã áp dụng cho các đơn hàng.<br><br>
<span class="tooltip-sql">SELECT SUM(order_discount_value)<br>
FROM tbl_orders</span>
</span></span>
                </p>
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
    const pointBorderColor = isDarkMode ? '#12151A' : '#FFFFFF';

    // Dark theme chart defaults
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    const revenueEl = document.getElementById('revenueChart');
    if (revenueEl) {
        const ctx = revenueEl.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($revenueChart['labels']) !!},
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: {!! json_encode($revenueChart['values']) !!},
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: pointBorderColor,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    // Usage Report Export Handler (Phase 28 - Async with Progress)
    const btnExport = document.getElementById('btn-export-usage-report');
    const startDateInput = document.getElementById('usage-start-date');
    const endDateInput = document.getElementById('usage-end-date');
    const progressContainer = document.getElementById('export-progress-container');
    const progressBar = document.getElementById('export-progress-bar');
    const progressPercent = document.getElementById('export-progress-percent');
    const statusMessage = document.getElementById('export-status-message');
    const btnDownload = document.getElementById('btn-download-export');
    const btnCancel = document.getElementById('btn-cancel-export');

    let currentExportId = null;
    let pollInterval = null;

    if (btnExport && startDateInput && endDateInput) {
        // Set default dates (current month)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        startDateInput.value = firstDay.toISOString().split('T')[0];
        endDateInput.value = today.toISOString().split('T')[0];

        // Phase 41: Check for pending exports on page load
        async function checkPendingExports() {
            try {
                const response = await fetch('/api/pending-exports');
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    // Resume tracking the first pending export
                    const pendingExport = result.data[0];
                    currentExportId = pendingExport.export_id;
                    
                    // Show progress container with current state
                    progressContainer.classList.remove('hidden');
                    progressBar.style.width = `${pendingExport.progress || 0}%`;
                    progressPercent.textContent = `${pendingExport.progress || 0}%`;
                    statusMessage.textContent = pendingExport.message || 'Đang xử lý...';
                    
                    if (pendingExport.status === 'completed') {
                        btnDownload.classList.remove('hidden');
                        btnDownload.onclick = () => {
                            window.location.href = `/api/download-export/${pendingExport.export_id}`;
                        };
                        progressBar.classList.remove('from-teal-500', 'to-cyan-500');
                        progressBar.classList.add('bg-green-500');
                    } else {
                        // Resume polling
                        startPolling(pendingExport.export_id);
                    }
                }
            } catch (e) {
                console.log('No pending exports found');
            }
        }
        
        // Check on page load
        checkPendingExports();

        // Start export button
        btnExport.addEventListener('click', async function() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (!startDate || !endDate) {
                alert('Vui lòng chọn khoảng thời gian');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Ngày bắt đầu phải nhỏ hơn ngày kết thúc');
                return;
            }

            // Show loading state
            const originalText = btnExport.innerHTML;
            btnExport.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Đang khởi tạo...';
            btnExport.disabled = true;

            try {
                // Start async export
                const response = await fetch('/api/start-export-usage-report', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate
                    })
                });

                // Phase 32: Better error handling for non-JSON responses
                let result;
                try {
                    const text = await response.text();
                    result = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    console.error('Failed to parse response:', parseError);
                    throw new Error(`Lỗi server (${response.status}): Không thể xử lý phản hồi`);
                }

                // Check for HTTP errors
                if (!response.ok) {
                    throw new Error(result.message || `Lỗi server (${response.status})`);
                }

                if (result.success && result.export_id) {
                    currentExportId = result.export_id;
                    
                    // Show progress container
                    progressContainer.classList.remove('hidden');
                    btnDownload.classList.add('hidden');
                    progressBar.style.width = '0%';
                    progressPercent.textContent = '0%';
                    statusMessage.textContent = 'Đang khởi tạo...';
                    
                    // Start polling for status
                    startPolling(result.export_id);
                } else {
                    throw new Error(result.message || 'Không thể bắt đầu xuất báo cáo');
                }
            } catch (error) {
                console.error('Export error:', error);
                alert('Có lỗi xảy ra: ' + error.message);
            } finally {
                btnExport.innerHTML = originalText;
                btnExport.disabled = false;
            }
        });

        // Poll for export status
        function startPolling(exportId) {
            // Clear any existing interval
            if (pollInterval) clearInterval(pollInterval);
            
            pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/export-status/${exportId}`);
                    
                    // Phase 32: Better error handling for non-JSON responses
                    let result;
                    try {
                        const text = await response.text();
                        result = text ? JSON.parse(text) : {};
                    } catch (parseError) {
                        console.error('Failed to parse status response:', parseError);
                        return; // Don't stop polling, just skip this iteration
                    }
                    
                    if (!result.success) {
                        stopPolling();
                        statusMessage.textContent = 'Lỗi: Không tìm thấy thông tin xuất báo cáo';
                        return;
                    }
                    
                    const data = result.data;
                    const progress = data.progress || 0;
                    
                    // Update progress UI
                    progressBar.style.width = `${progress}%`;
                    progressPercent.textContent = `${progress}%`;
                    statusMessage.textContent = data.message || 'Đang xử lý...';
                    
                    if (data.status === 'completed') {
                        stopPolling();
                        // Show download button
                        btnDownload.classList.remove('hidden');
                        btnDownload.onclick = () => {
                            window.location.href = `/api/download-export/${exportId}`;
                        };
                        statusMessage.textContent = `✅ ${data.message || 'Hoàn tất!'} (${data.record_count || 0} bản ghi)`;
                        progressBar.classList.remove('from-teal-500', 'to-cyan-500');
                        progressBar.classList.add('bg-green-500');
                    } else if (data.status === 'failed') {
                        stopPolling();
                        statusMessage.textContent = `❌ ${data.message || 'Có lỗi xảy ra'}`;
                        progressBar.classList.remove('from-teal-500', 'to-cyan-500');
                        progressBar.classList.add('bg-red-500');
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 1500); // Poll every 1.5 seconds
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        // Cancel/Close button
        btnCancel.addEventListener('click', async function() {
            stopPolling();
            progressContainer.classList.add('hidden');
            
            // Reset progress bar colors
            progressBar.classList.remove('bg-green-500', 'bg-red-500');
            progressBar.classList.add('from-teal-500', 'to-cyan-500');
            
            // Cleanup on server if export was in progress
            if (currentExportId) {
                try {
                    await fetch(`/api/cancel-export/${currentExportId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
                } catch (e) {
                    // Ignore cleanup errors
                }
                currentExportId = null;
            }
        });
    }
} // End of chart initialization block

// Phase 103/104: First Orders with Successful Lessons Section (with sorting & null filter)
function firstOrdersSection() {
    return {
        loading: false,
        items: [],
        searchQuery: '',
        sortBy: '',
        sortDir: 'desc',
        filterNullLesson: false,
        daysDifference: 2,
        filterDaysDifference: false,
        orderDateFrom: '',
        orderDateTo: '',
        pagination: {
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1,
        },

        toggleSort(column) {
            if (this.sortBy === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDir = 'desc';
            }
            this.loadData(1);
        },

        buildParams(page, perPage) {
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', perPage || this.pagination.per_page);
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.sortBy) {
                params.set('sort_by', this.sortBy);
                params.set('sort_dir', this.sortDir);
            }
            if (this.filterNullLesson) params.set('filter_null_lesson', '1');
            if (this.filterDaysDifference) params.set('days_difference', this.daysDifference);
            if (this.orderDateFrom) params.set('order_date_from', this.orderDateFrom);
            if (this.orderDateTo) params.set('order_date_to', this.orderDateTo);
            return params;
        },

        async loadData(page = 1) {
            this.loading = true;
            FilterProgress.show();
            const params = this.buildParams(page);

            try {
                const response = await fetch(`/api/first-orders-with-lessons?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.items = result.data || [];
                    this.pagination = result.pagination || this.pagination;
                }
            } catch (error) {
                console.error('Error fetching first orders with lessons:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        async exportToExcel() {
            // Fetch ALL data for export (no pagination)
            const params = this.buildParams(1, 10000);

            try {
                const response = await fetch(`/api/first-orders-with-lessons?${params.toString()}`);
                const result = await response.json();

                if (!result.success || !result.data || result.data.length === 0) {
                    alert('Không có dữ liệu để xuất!');
                    return;
                }

                const allData = result.data;
                const headers = ['STT', 'Order ID', 'User ID', 'Họ tên', 'Email', 'Số buổi', 'Giá trị đơn (VNĐ)', 'Ngày đặt hàng', 'Buổi học đầu tiên', 'Ngày chờ', 'Giờ chờ'];
                const rows = allData.map((item, index) => [
                    index + 1,
                    item.order_id || '',
                    item.order_user_id || '',
                    item.student_name || '',
                    item.student_email || '',
                    item.order_item_count || '',
                    item.order_net_amount || '',
                    item.order_addedon || '',
                    item.first_lesson_start_time || 'NULL',
                    item.days_difference !== null && item.days_difference !== undefined ? item.days_difference : '',
                    item.time_difference || ''
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
                link.setAttribute('download', `Don_hang_dau_tien_buoi_hoc_thanh_cong_${new Date().toISOString().slice(0, 10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting first orders with lessons:', error);
                alert('Có lỗi khi xuất file!');
            }
        }
    }
}

// Phase 112: Trial Lessons Statistics Section
function trialLessonsSection() {
    return {
        loading: false,
        items: [],
        searchQuery: '',
        sortBy: '',
        sortDir: 'desc',
        startDate: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        detailItem: null,
        pagination: {
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1,
        },

        toggleSort(column) {
            if (this.sortBy === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDir = 'desc';
            }
            this.loadData(1);
        },

        buildParams(page, perPage) {
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', perPage || this.pagination.per_page);
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.sortBy) {
                params.set('sort_by', this.sortBy);
                params.set('sort_dir', this.sortDir);
            }
            if (this.startDate) params.set('start_date', this.startDate);
            if (this.endDate) params.set('end_date', this.endDate);
            return params;
        },

        async loadData(page = 1) {
            this.loading = true;
            FilterProgress.show();
            const params = this.buildParams(page);

            try {
                const response = await fetch(`/api/trial-lessons-list?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.items = result.data || [];
                    this.pagination = result.pagination || this.pagination;
                }
            } catch (error) {
                console.error('Error fetching trial lessons list:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        showDetail(item) {
            this.detailItem = item;
        },

        async exportToExcel() {
            const params = this.buildParams(1, 10000);

            try {
                const response = await fetch(`/api/trial-lessons-list?${params.toString()}`);
                const result = await response.json();

                if (!result.success || !result.data || result.data.length === 0) {
                    alert('Không có dữ liệu để xuất!');
                    return;
                }

                const allData = result.data;
                const headers = [
                    'STT', 'Trial ID', 'User ID', 'Username', 'Họ tên', 'Email',
                    'Ngày đặt lịch', 'Ngày học Trial', 'Trạng thái', 'Chương trình',
                    'Level', 'Đề xuất', 'Ngày đánh giá', 'Ghi chú', 'Nội dung đánh giá'
                ];
                const rows = allData.map((item, index) => [
                    index + 1,
                    item.trial_id || '',
                    item.trial_user_id || '',
                    item.trial_user_username || '',
                    item.trial_user_fullname || '',
                    item.trial_user_email || '',
                    item.trial_request_date || '',
                    item.trial_date || '',
                    item.trial_status || '',
                    item.trial_program_name || '',
                    item.trial_feedback_level || '',
                    item.trial_suggested_subject_name || '',
                    item.trial_feedback_date || '',
                    item.trial_note || '',
                    item.trial_feedback_content || ''
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
                link.setAttribute('download', `Thong_ke_trial_${this.startDate}_${this.endDate}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting trial lessons:', error);
                alert('Có lỗi khi xuất file!');
            }
        }
    }
}
</script>
@endpush
