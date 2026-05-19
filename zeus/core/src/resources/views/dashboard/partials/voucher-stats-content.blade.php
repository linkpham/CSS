{{-- Voucher Statistics Content Partial --}}
{{-- Variables: $stats (voucher stats array), $period (period key), $periodLabel (display label) --}}

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <!-- Số lần sử dụng voucher trong kỳ -->
    <div class="text-center p-3 bg-purple-500/10 rounded-lg border border-purple-500/30">
        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['usage_count'] ?? 0 }}</p>
        <p class="text-xs text-purple-600/80 dark:text-purple-400/80">Số lần áp dụng voucher
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]</code></span></span>
        </p>
    </div>
    
    <!-- Số đơn hàng sử dụng voucher -->
    <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['orders_with_coupon'] ?? 0 }}</p>
        <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Đơn hàng có voucher
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT couhis_order_id) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]</code></span></span>
        </p>
    </div>
    
    <!-- Số người dùng sử dụng voucher -->
    <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['unique_users_with_coupon'] ?? 0 }}</p>
        <p class="text-xs text-green-600/80 dark:text-green-400/80">Người dùng sử dụng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT clog_beneficiary_id) FROM tbl_coupon_logs WHERE clog_action = 4 AND clog_created_at BETWEEN [start] AND [end]</code></span></span>
        </p>
    </div>
    
    <!-- Tổng giá trị giảm giá -->
    <div class="text-center p-3 bg-amber-500/10 rounded-lg border border-amber-500/30">
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['total_discount_amount'] ?? 0) }}</p>
        <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Tổng giảm giá (VND)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tổng cộng coupon_discount từ JSON trong couhis_coupon của các bản ghi trong kỳ</code></span></span>
        </p>
    </div>
</div>

<!-- Second row of stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <!-- Số voucher khác nhau được sử dụng -->
    <div class="text-center p-3 bg-indigo-500/10 rounded-lg border border-indigo-500/30">
        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['unique_coupons_used'] ?? 0 }}</p>
        <p class="text-xs text-indigo-600/80 dark:text-indigo-400/80">Mã voucher được dùng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT couhis_coupon_id) FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end]</code></span></span>
        </p>
    </div>
    
    <!-- Số lần áp dụng -->
    <div class="text-center p-3 bg-teal-500/10 rounded-lg border border-teal-500/30">
        <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $stats['applied_count'] ?? 0 }}</p>
        <p class="text-xs text-teal-600/80 dark:text-teal-400/80">Lượt áp dụng mã
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupon_logs WHERE clog_action = 4 AND clog_created_at BETWEEN [start] AND [end]</code><br>Action 4 = Coupon applied</span></span>
        </p>
    </div>
    
    <!-- Số lần hủy áp dụng -->
    <div class="text-center p-3 bg-rose-500/10 rounded-lg border border-rose-500/30">
        <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $stats['released_count'] ?? 0 }}</p>
        <p class="text-xs text-rose-600/80 dark:text-rose-400/80">Lượt hủy áp dụng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupon_logs WHERE clog_action = 5 AND clog_created_at BETWEEN [start] AND [end]</code><br>Action 5 = Coupon released</span></span>
        </p>
    </div>
    
    <!-- Net applied -->
    <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['net_applied'] ?? 0 }}</p>
        <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Lượt áp dụng ròng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Lượt áp dụng ròng = (Lượt áp dụng) - (Lượt hủy áp dụng)</code></span></span>
        </p>
    </div>
</div>

<!-- Overview Stats (all time) -->
<div class="mt-4 p-4 bg-slate-500/5 dark:bg-slate-500/10 rounded-lg border border-slate-500/20">
    <h5 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">📋 Tổng quan hệ thống</h5>
    <div class="grid grid-cols-3 gap-4">
        <!-- Tổng số voucher -->
        <div class="text-center">
            <p class="text-xl font-bold text-slate-600 dark:text-slate-400">{{ $stats['total_coupons'] ?? 0 }}</p>
            <p class="text-xs text-slate-600/80 dark:text-slate-400/80">Tổng voucher
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupons</code></span></span>
            </p>
        </div>
        
        <!-- Voucher đang hoạt động -->
        <div class="text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['active_coupons'] ?? 0 }}</p>
            <p class="text-xs text-green-600/80 dark:text-green-400/80">Đang hoạt động
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupons WHERE coupon_active = 1 AND coupon_start_date <= NOW() AND coupon_end_date >= NOW()</code></span></span>
            </p>
        </div>
        
        <!-- Voucher hết hạn -->
        <div class="text-center">
            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $stats['expired_coupons'] ?? 0 }}</p>
            <p class="text-xs text-red-600/80 dark:text-red-400/80">Đã hết hạn
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_coupons WHERE coupon_end_date < NOW()</code></span></span>
            </p>
        </div>
    </div>
    
    <!-- Usage rate -->
    <div class="mt-3 text-center">
        <p class="text-sm text-slate-600 dark:text-slate-400">
            Tỷ lệ voucher đã được sử dụng: 
            <span class="font-bold text-zeus-accent">{{ $stats['overall_usage_rate'] ?? 0 }}%</span>
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (Số voucher có ít nhất 1 lượt dùng / Tổng voucher) × 100</code></span></span>
        </p>
    </div>
</div>

<!-- Top Vouchers in Period -->
@if(!empty($stats['top_coupons']))
<div class="mt-4">
    <h5 class="text-sm font-medium text-light-text dark:text-zeus-text mb-2">
        🏆 Top voucher được sử dụng nhiều nhất ({{ $periodLabel }})
        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT couhis_coupon_id, COUNT(*) as usage_count FROM tbl_coupons_history WHERE couhis_created BETWEEN [start] AND [end] GROUP BY couhis_coupon_id ORDER BY usage_count DESC LIMIT 5</code></span></span>
    </h5>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Mã</th>
                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Tên</th>
                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Loại</th>
                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Giá trị</th>
                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Lượt dùng</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                @foreach($stats['top_coupons'] as $coupon)
                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                    <td class="px-3 py-2 font-mono font-bold text-purple-600 dark:text-purple-400">{{ $coupon['coupon_code'] }}</td>
                    <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">{{ $coupon['coupon_identifier'] }}</td>
                    <td class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted text-xs">
                        @if($coupon['discount_type'] == 1)
                            <span class="px-2 py-0.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full">%</span>
                        @else
                            <span class="px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-full">VND</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center font-medium text-light-text dark:text-zeus-text">
                        @if($coupon['discount_type'] == 1)
                            {{ number_format($coupon['discount_value'] ?? 0) }}%
                        @else
                            {{ number_format($coupon['discount_value'] ?? 0) }}₫
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center font-bold text-green-600 dark:text-green-400">{{ $coupon['usage_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="mt-4 text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
    <p class="text-sm">Không có voucher nào được sử dụng trong kỳ này</p>
</div>
@endif
