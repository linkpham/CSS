{{-- Phase 141: Define getAcceptanceCodeColors when partial is rendered standalone (e.g., from controller) --}}
@php
if (!function_exists('getAcceptanceCodeColors')) {
    function getAcceptanceCodeColors($code) {
        return match((int)$code) {
            1, 2, 3 => ['bg' => 'bg-rose-200 dark:bg-rose-800', 'text' => 'text-rose-900 dark:text-rose-100', 'border' => 'border-rose-300 dark:border-rose-700', 'code_text' => 'text-rose-700 dark:text-rose-300'],
            4, 5, 6 => ['bg' => 'bg-orange-200 dark:bg-orange-800', 'text' => 'text-orange-900 dark:text-orange-100', 'border' => 'border-orange-300 dark:border-orange-700', 'code_text' => 'text-orange-700 dark:text-orange-300'],
            7, 8, 9 => ['bg' => 'bg-yellow-200 dark:bg-yellow-800', 'text' => 'text-yellow-900 dark:text-yellow-100', 'border' => 'border-yellow-300 dark:border-yellow-700', 'code_text' => 'text-yellow-700 dark:text-yellow-300'],
            10, 11 => ['bg' => 'bg-lime-200 dark:bg-lime-800', 'text' => 'text-lime-900 dark:text-lime-100', 'border' => 'border-lime-300 dark:border-lime-700', 'code_text' => 'text-lime-700 dark:text-lime-300'],
            12 => ['bg' => 'bg-emerald-200 dark:bg-emerald-800', 'text' => 'text-emerald-900 dark:text-emerald-100', 'border' => 'border-emerald-300 dark:border-emerald-700', 'code_text' => 'text-emerald-600 dark:text-emerald-300'],
            13 => ['bg' => 'bg-blue-200 dark:bg-blue-800', 'text' => 'text-blue-900 dark:text-blue-100', 'border' => 'border-blue-300 dark:border-blue-700', 'code_text' => 'text-blue-700 dark:text-blue-300'],
            14 => ['bg' => 'bg-purple-200 dark:bg-purple-800', 'text' => 'text-purple-900 dark:text-purple-100', 'border' => 'border-purple-300 dark:border-purple-700', 'code_text' => 'text-purple-700 dark:text-purple-300'],
            15 => ['bg' => 'bg-cyan-200 dark:bg-cyan-800', 'text' => 'text-cyan-900 dark:text-cyan-100', 'border' => 'border-cyan-300 dark:border-cyan-700', 'code_text' => 'text-cyan-700 dark:text-cyan-300'],
            16 => ['bg' => 'bg-amber-200 dark:bg-amber-800', 'text' => 'text-amber-900 dark:text-amber-100', 'border' => 'border-amber-300 dark:border-amber-700', 'code_text' => 'text-amber-700 dark:text-amber-300'],
            17 => ['bg' => 'bg-slate-200 dark:bg-slate-700', 'text' => 'text-slate-900 dark:text-slate-100', 'border' => 'border-slate-300 dark:border-slate-600', 'code_text' => 'text-slate-600 dark:text-slate-300'],
            default => ['bg' => 'bg-gray-200 dark:bg-gray-700', 'text' => 'text-gray-900 dark:text-gray-100', 'border' => 'border-gray-300 dark:border-gray-600', 'code_text' => 'text-gray-600 dark:text-gray-300'],
        };
    }
}
@endphp
    <!-- Session Stats Content (filtered by active program tab) -->
    
    <!-- ===== SESSION SUCCESS/FAILURE STATS (PRIORITIZED) ===== -->
    <div class="bg-gradient-to-r from-blue-500/5 via-green-500/5 to-red-500/5 dark:from-blue-500/10 dark:via-green-500/10 dark:to-red-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3 md:mb-4">
            <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2 flex-wrap">
                📊 Thống kê Ca học
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>• Đã hoàn thành (status=3)<br>• Đã lên lịch (status=2)<br>• Đã hủy (status=4)</span></span>
            </h3>
            <div class="flex items-center gap-2">
                <!-- Export Button -->
                <div class="relative" x-data="sessionExportModal()">
                    <button @click="showExport = !showExport" class="px-2 md:px-3 py-1 md:py-1.5 text-xs md:text-sm bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/20 transition flex items-center gap-1 whitespace-nowrap">
                        📥 Export
                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- Export Dropdown -->
                    <div x-show="showExport" @click.away="showExport = false" @click.stop x-cloak
                         class="absolute right-0 mt-2 w-80 bg-light-card dark:bg-zeus-card rounded-xl shadow-xl border border-light-border dark:border-zeus-border z-50 p-4">
                        <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3">📥 Export Thống kê Ca học</h4>
                        
                        <div class="space-y-3">
                            <!-- Export Type Tabs -->
                            <div class="flex gap-1 p-1 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <button @click="exportType = 'monthly'" 
                                        :class="exportType === 'monthly' ? 'bg-white dark:bg-zeus-card shadow text-light-text dark:text-zeus-text' : 'text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text'"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded transition">
                                    Theo tháng
                                </button>
                                <button @click="exportType = 'daily'" 
                                        :class="exportType === 'daily' ? 'bg-white dark:bg-zeus-card shadow text-light-text dark:text-zeus-text' : 'text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text'"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded transition">
                                    Theo ngày
                                </button>
                            </div>

                            <!-- Monthly Export Fields -->
                            <template x-if="exportType === 'monthly'">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Từ tháng:</label>
                                        <input type="month" x-model="exportFromMonth" 
                                               @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                               autocomplete="off" data-lpignore="true" data-form-type="other"
                                               class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Đến tháng:</label>
                                        <input type="month" x-model="exportToMonth"
                                               @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                               autocomplete="off" data-lpignore="true" data-form-type="other"
                                               class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent">
                                    </div>
                                </div>
                            </template>

                            <!-- Daily Export Fields -->
                            <template x-if="exportType === 'daily'">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Từ ngày:</label>
                                        <input type="date" x-model="exportFromDate" 
                                               @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                               autocomplete="off" data-lpignore="true" data-form-type="other"
                                               class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Đến ngày:</label>
                                        <input type="date" x-model="exportToDate"
                                               @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                               autocomplete="off" data-lpignore="true" data-form-type="other"
                                               class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent">
                                    </div>
                                </div>
                            </template>
                            
                            <p x-show="exportError" class="text-xs text-red-500" x-text="exportError"></p>
                            
                            <div class="pt-2">
                                <button @click="exportSessionStats()" 
                                        :disabled="exportLoading"
                                        class="w-full px-4 py-2 text-sm bg-green-500 text-white rounded-lg hover:bg-green-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                    <template x-if="exportLoading">
                                        <span class="spinner-inline"></span>
                                    </template>
                                    <span x-text="exportLoading ? 'Đang xuất...' : '📥 Tải xuống CSV'"></span>
                                </button>
                            </div>
                            
                            <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted">
                                Xuất dữ liệu: Tổng ca học, Buổi có tính phí, Thành công (code 12), GV no-show
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Compare Menu Button -->
                <div class="relative" x-data="{ showCompare: false }">
                    <button @click="showCompare = !showCompare" class="px-2 md:px-3 py-1 md:py-1.5 text-xs md:text-sm bg-indigo-500/10 text-indigo-500 rounded-lg hover:bg-indigo-500/20 transition flex items-center gap-1 whitespace-nowrap">
                        📊 So sánh
                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                <!-- Comparison Dropdown Menu -->
                <div x-show="showCompare" @click.away="showCompare = false" x-cloak
                     class="absolute right-0 mt-2 w-80 bg-light-card dark:bg-zeus-card rounded-xl shadow-xl border border-light-border dark:border-zeus-border z-50 p-4"
                     x-data="comparisonMenu()"
                     x-init="loadComparison()">
                    <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3">📊 So sánh các kỳ</h4>
                    
                    <template x-if="loading">
                        <div class="text-center py-4">
                            <span class="spinner-inline"></span>
                            <span class="text-sm text-light-text-muted dark:text-zeus-text-muted ml-2">Đang tải...</span>
                        </div>
                    </template>
                    
                    <template x-if="!loading && compData">
                        <div class="space-y-4">
                            <!-- Today vs Yesterday -->
                            <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Hôm nay vs Hôm qua</p>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div>
                                        <p class="font-bold text-green-500" x-text="compData.today_vs_yesterday?.sessions?.today?.completed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-red-500" x-text="compData.today_vs_yesterday?.sessions?.today?.failed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thất bại</p>
                                    </div>
                                    <div>
                                        <p class="font-bold" 
                                           :class="compData.today_vs_yesterday?.sessions?.change?.completed?.direction === 'up' ? 'text-green-500' : (compData.today_vs_yesterday?.sessions?.change?.completed?.direction === 'down' ? 'text-red-500' : 'text-gray-500')">
                                            <span x-text="compData.today_vs_yesterday?.sessions?.change?.completed?.direction === 'up' ? '↑' : (compData.today_vs_yesterday?.sessions?.change?.completed?.direction === 'down' ? '↓' : '−')"></span>
                                            <span x-text="compData.today_vs_yesterday?.sessions?.change?.completed?.value || 0"></span>%
                                        </p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thay đổi</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Yesterday vs Day Before Yesterday (Hôm qua vs Hôm kia) -->
                            <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Hôm qua vs Hôm kia</p>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div>
                                        <p class="font-bold text-green-500" x-text="compData.yesterday_vs_day_before?.sessions?.yesterday?.completed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-red-500" x-text="compData.yesterday_vs_day_before?.sessions?.yesterday?.failed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thất bại</p>
                                    </div>
                                    <div>
                                        <p class="font-bold" 
                                           :class="compData.yesterday_vs_day_before?.sessions?.change?.completed?.direction === 'up' ? 'text-green-500' : (compData.yesterday_vs_day_before?.sessions?.change?.completed?.direction === 'down' ? 'text-red-500' : 'text-gray-500')">
                                            <span x-text="compData.yesterday_vs_day_before?.sessions?.change?.completed?.direction === 'up' ? '↑' : (compData.yesterday_vs_day_before?.sessions?.change?.completed?.direction === 'down' ? '↓' : '−')"></span>
                                            <span x-text="compData.yesterday_vs_day_before?.sessions?.change?.completed?.value || 0"></span>%
                                        </p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thay đổi</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- This Week vs Last Week -->
                            <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Tuần này vs Tuần trước</p>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div>
                                        <p class="font-bold text-green-500" x-text="compData.this_week_vs_last_week?.sessions?.current?.completed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-red-500" x-text="compData.this_week_vs_last_week?.sessions?.current?.failed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thất bại</p>
                                    </div>
                                    <div>
                                        <p class="font-bold" 
                                           :class="compData.this_week_vs_last_week?.sessions?.change?.completed?.direction === 'up' ? 'text-green-500' : (compData.this_week_vs_last_week?.sessions?.change?.completed?.direction === 'down' ? 'text-red-500' : 'text-gray-500')">
                                            <span x-text="compData.this_week_vs_last_week?.sessions?.change?.completed?.direction === 'up' ? '↑' : (compData.this_week_vs_last_week?.sessions?.change?.completed?.direction === 'down' ? '↓' : '−')"></span>
                                            <span x-text="compData.this_week_vs_last_week?.sessions?.change?.completed?.value || 0"></span>%
                                        </p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thay đổi</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Last Week vs Week Before Last (Tuần trước vs Tuần trước nữa) -->
                            <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">📈 Tuần trước vs Tuần trước nữa</p>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div>
                                        <p class="font-bold text-green-500" x-text="compData.last_week_vs_week_before?.sessions?.current?.completed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-red-500" x-text="compData.last_week_vs_week_before?.sessions?.current?.failed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thất bại</p>
                                    </div>
                                    <div>
                                        <p class="font-bold" 
                                           :class="compData.last_week_vs_week_before?.sessions?.change?.completed?.direction === 'up' ? 'text-green-500' : (compData.last_week_vs_week_before?.sessions?.change?.completed?.direction === 'down' ? 'text-red-500' : 'text-gray-500')">
                                            <span x-text="compData.last_week_vs_week_before?.sessions?.change?.completed?.direction === 'up' ? '↑' : (compData.last_week_vs_week_before?.sessions?.change?.completed?.direction === 'down' ? '↓' : '−')"></span>
                                            <span x-text="compData.last_week_vs_week_before?.sessions?.change?.completed?.value || 0"></span>%
                                        </p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thay đổi</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- This Month vs Last Month -->
                            <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                                <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Tháng này vs Tháng trước</p>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div>
                                        <p class="font-bold text-green-500" x-text="compData.this_month_vs_last_month?.sessions?.current?.completed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thành công</p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-red-500" x-text="compData.this_month_vs_last_month?.sessions?.current?.failed || 0"></p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thất bại</p>
                                    </div>
                                    <div>
                                        <p class="font-bold" 
                                           :class="compData.this_month_vs_last_month?.sessions?.change?.completed?.direction === 'up' ? 'text-green-500' : (compData.this_month_vs_last_month?.sessions?.change?.completed?.direction === 'down' ? 'text-red-500' : 'text-gray-500')">
                                            <span x-text="compData.this_month_vs_last_month?.sessions?.change?.completed?.direction === 'up' ? '↑' : (compData.this_month_vs_last_month?.sessions?.change?.completed?.direction === 'down' ? '↓' : '−')"></span>
                                            <span x-text="compData.this_month_vs_last_month?.sessions?.change?.completed?.value || 0"></span>%
                                        </p>
                                        <p class="text-light-text-muted dark:text-zeus-text-muted">Thay đổi</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            </div>
        </div>
        
        <!-- Period Tabs for Session Stats -->
        <div x-data="sessionStatsFilter()">
            <!-- Tab Buttons and Date Picker -->
            <div class="relative mb-3 md:mb-4">
                <div class="tabs-container">
                    <button @click="activeTab = 'today'; customDate = ''" :class="activeTab === 'today' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm nay</button>
                    <button @click="activeTab = 'yesterday'; customDate = ''" :class="activeTab === 'yesterday' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm qua</button>
                    <button @click="activeTab = 'day_before'; customDate = ''" :class="activeTab === 'day_before' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm kia</button>
                    <button @click="activeTab = 'week'; customDate = ''" :class="activeTab === 'week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần này</button>
                    <button @click="activeTab = 'last_week'; customDate = ''" :class="activeTab === 'last_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần trước</button>
                    <button @click="activeTab = 'month'; customDate = ''" :class="activeTab === 'month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng này</button>
                    <!-- HIDDEN: Tháng trước button temporarily hidden per Phase 8 -->
                    <!-- <button @click="activeTab = 'last_month'; customDate = ''" :class="activeTab === 'last_month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng trước</button> -->
                    <!-- Phase 97: Chọn Tuần Button -->
                    <button @click="showWeekPicker = !showWeekPicker; showMonthPicker = false; showDatePicker = false" :class="activeTab === 'pick_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1">
                        Chọn Tuần
                        <span x-show="pickedWeekLabel" x-text="'(' + pickedWeekLabel + ')'" class="text-xs"></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <!-- Phase 97: Chọn Tháng Button -->
                    <button @click="showMonthPicker = !showMonthPicker; showWeekPicker = false; showDatePicker = false" :class="activeTab === 'pick_month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1">
                        Chọn Tháng
                        <span x-show="pickedMonthLabel" x-text="'(' + pickedMonthLabel + ')'" class="text-xs"></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <!-- Custom Date Range Picker Button -->
                    <button @click="showDatePicker = !showDatePicker; showWeekPicker = false; showMonthPicker = false" :class="activeTab === 'custom' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1" x-ref="datePickerBtn">
                        📅 Tùy chọn
                        <span x-show="customDateFormatted" x-text="'(' + customDateFormatted + ')'" class="text-xs"></span>
                    </button>
                </div>
                <!-- Phase 97: Week Picker Dropdown -->
                <div x-show="showWeekPicker" @click.away="showWeekPicker = false" @click.stop x-cloak
                     class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                     style="min-width: 280px; max-height: 350px; overflow-y: auto;">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Chọn tuần:</label>
                    <div class="space-y-1">
                        <template x-for="w in availableWeeks" :key="w.weekNum">
                            <button @click="selectWeek(w)" 
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg transition flex items-center justify-between"
                                    :class="pickedWeekNum === w.weekNum ? 'bg-zeus-accent text-white' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light text-light-text dark:text-zeus-text'">
                                <span x-text="'Tuần ' + w.weekNum"></span>
                                <span class="text-xs opacity-75" x-text="w.rangeLabel"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <!-- Phase 97: Month Picker Dropdown -->
                <div x-show="showMonthPicker" @click.away="showMonthPicker = false" @click.stop x-cloak
                     class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                     style="min-width: 220px; max-height: 350px; overflow-y: auto;">
                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Chọn tháng:</label>
                    <div class="space-y-1">
                        <template x-for="m in availableMonths" :key="m.month">
                            <button @click="selectMonth(m)" 
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg transition flex items-center justify-between"
                                    :class="pickedMonthNum === m.month ? 'bg-zeus-accent text-white' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light text-light-text dark:text-zeus-text'">
                                <span x-text="'Tháng ' + m.month"></span>
                                <span class="text-xs opacity-75" x-text="m.rangeLabel"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <!-- Date Range Picker Dropdown (updated Phase 97) - Outside tabs-container to avoid overflow clipping -->
                <div x-show="showDatePicker" @click.away="showDatePicker = false" @click.stop x-cloak
                     class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                     style="min-width: 260px;">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Từ ngày:</label>
                            <input type="date" x-model="customFromDate" 
                                   @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                   autocomplete="off" data-lpignore="true" data-form-type="other"
                                   class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent"
                                   :max="maxDate">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Đến ngày:</label>
                            <input type="date" x-model="customToDate" 
                                   @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                   autocomplete="off" data-lpignore="true" data-form-type="other"
                                   class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent"
                                   :max="maxDate">
                        </div>
                    </div>
                    <p x-show="customDateError" class="text-xs text-red-500 mt-1" x-text="customDateError"></p>
                    <div class="flex gap-2 mt-3">
                        <button @click="showDatePicker = false" 
                                class="flex-1 px-3 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Hủy
                        </button>
                        <button @click="loadCustomDateStats()" 
                                :disabled="!customFromDate || !customToDate"
                                class="flex-1 px-3 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent/90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Xác nhận
                        </button>
                    </div>
                </div>
            </div>
        
            <!-- Today Stats -->
            <div x-show="activeTab === 'today'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['today']" periodKey="today" periodLabel="Hôm nay" />
            </div>

            <!-- Yesterday Stats -->
            <div x-show="activeTab === 'yesterday'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['yesterday']" periodKey="yesterday" periodLabel="Hôm qua" />
            </div>

            <!-- Day Before Yesterday Stats (Hôm kia) -->
            <div x-show="activeTab === 'day_before'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['day_before_yesterday']" periodKey="day_before" periodLabel="Hôm kia" />
            </div>

            <!-- Week Stats -->
            <div x-show="activeTab === 'week'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['this_week']" periodKey="week" periodLabel="Tuần này" />
            </div>

            <!-- Last Week Stats -->
            <div x-show="activeTab === 'last_week'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['last_week']" periodKey="last_week" periodLabel="Tuần trước" />
            </div>

            <!-- Month Stats -->
            <div x-show="activeTab === 'month'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['this_month']" periodKey="month" periodLabel="Tháng này" />
            </div>

            <!-- HIDDEN: Last Month Stats temporarily hidden per Phase 8 -->
            <!--
            <div x-show="activeTab === 'last_month'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['last_month']" periodKey="last_month" periodLabel="Tháng trước" />
            </div>
            -->

            <!-- All Time Stats -->
            <div x-show="activeTab === 'all'" class="w-full mt-3 md:mt-4">
                <x-session-stats-display :stats="$sessionStats['all_time']" periodKey="all" periodLabel="Tất cả" />
            </div>

            <!-- Custom Date Stats (loaded via AJAX) -->
            <div x-show="activeTab === 'custom'" class="w-full mt-3 md:mt-4">
                <!-- Loading State -->
                <template x-if="customLoading">
                    <div class="flex items-center justify-center py-8">
                        <span class="spinner-inline"></span>
                        <span class="ml-2 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải dữ liệu...</span>
                    </div>
                </template>
                
                <!-- Error State -->
                <template x-if="!customLoading && customDateError">
                    <div class="text-center py-8">
                        <p class="text-red-500" x-text="customDateError"></p>
                    </div>
                </template>
                
                <!-- Stats Display - Hierarchical KPI Component -->
                <template x-if="!customLoading && !customDateError && customStats">
                    <div class="space-y-1">
                        <div class="p-4 md:p-6 bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-800/50 dark:to-blue-900/30 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="space-y-4">
                                <!-- Level 0: Tổng ca học (Root) -->
                                <div class="flex items-center gap-3 pb-3 border-b-2 border-blue-300 dark:border-blue-600">
                                    <span class="text-lg md:text-xl font-bold text-blue-700 dark:text-blue-400">📋 Tổng ca học:</span>
                                    <span class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.total || 0)"></span>
                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng ca học</span><br>
                                        Tổng số ca học có status = 2, 3, 4 (Đã lên lịch, Hoàn thành, Đã hủy).<br><br>
                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                        WHERE ordles_status IN (2, 3, 4)<br>
                                        AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                    </span></span>
                                </div>
                                
                                <div class="space-y-3 ml-0 md:ml-0">
                                    <!-- Level 1: Đã hoàn thành -->
                                    <div class="relative">
                                        <div class="ml-0 md:ml-0 space-y-4">
                                            <!-- Đã hoàn thành -->
                                            <div class="pl-4 border-l-4 border-green-400 dark:border-green-500 bg-green-50/50 dark:bg-green-900/20 rounded-r-lg py-3 pr-4">
                                                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                                    <span class="text-base md:text-lg font-semibold text-green-700 dark:text-green-400">✅ Đã hoàn thành:</span>
                                                    <span class="text-xl md:text-2xl font-bold text-green-600 dark:text-green-400" x-text="formatNumber(customStats.status_breakdown?.completed || 0)"></span>
                                                    <span class="text-sm md:text-base text-green-600/90 dark:text-green-400/90 bg-green-100 dark:bg-green-900/50 px-2.5 py-1 rounded-full font-medium" x-text="'(' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.completed || 0) / customStats.total * 1000) / 10 : 0) + '%)'"></span>
                                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hoàn thành</span><br>
                                                        Ca học có status = 3 (Completed).<br><br>
                                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                                        WHERE ordles_status = 3<br>
                                                        AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                                    </span></span>
                                                </div>
                                                
                                                <!-- Progress bar -->
                                                <div class="mt-2 max-w-md">
                                                    <div class="bg-green-200 dark:bg-green-800 rounded-full h-2.5">
                                                        <div class="bg-gradient-to-r from-green-400 to-green-500 h-2.5 rounded-full transition-all duration-500" :style="'width: ' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.completed || 0) / customStats.total * 100) : 0) + '%'"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Level 2: Breakdown của hoàn thành -->
                                                <template x-if="(customStats.status_breakdown?.completed || 0) > 0">
                                                    <div class="mt-4 ml-4 md:ml-6 space-y-2.5">
                                                        <!-- Số ca đã tính phí -->
                                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-2 border-l-3 border-emerald-400 dark:border-emerald-500 bg-emerald-50/70 dark:bg-emerald-900/30 rounded-r-lg">
                                                            <span class="text-sm md:text-base text-emerald-700 dark:text-emerald-400 font-medium">💰 Số ca đã tính phí:</span>
                                                            <span class="text-lg md:text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(customStats.completed_breakdown?.chargeable || 0)"></span>
                                                            <span class="text-sm text-emerald-600/80 dark:text-emerald-400/80 bg-emerald-100 dark:bg-emerald-900/50 px-2 py-0.5 rounded-full" x-text="'(' + ((customStats.status_breakdown?.completed || 0) > 0 ? Math.round((customStats.completed_breakdown?.chargeable || 0) / (customStats.status_breakdown?.completed || 1) * 1000) / 10 : 0) + '%)'"></span>
                                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số ca đã tính phí</span><br>
                                                                Ca hoàn thành có mã chấp nhận 4-12, 16, 17.
                                                            </span></span>
                                                        </div>
                                                        
                                                        <!-- Số ca bù buổi -->
                                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-2 border-l-3 border-amber-400 dark:border-amber-500 bg-amber-50/70 dark:bg-amber-900/30 rounded-r-lg">
                                                            <span class="text-sm md:text-base text-amber-700 dark:text-amber-400 font-medium">🔄 Số ca bù buổi:</span>
                                                            <span class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.completed_breakdown?.compensate || 0)"></span>
                                                            <span class="text-sm text-amber-600/80 dark:text-amber-400/80 bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full" x-text="'(' + ((customStats.status_breakdown?.completed || 0) > 0 ? Math.round((customStats.completed_breakdown?.compensate || 0) / (customStats.status_breakdown?.completed || 1) * 1000) / 10 : 0) + '%)'"></span>
                                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số ca bù buổi</span><br>
                                                                Ca hoàn thành có mã chấp nhận 1-3, 13-15. HV được học bù miễn phí.
                                                            </span></span>
                                                        </div>
                                                        
                                                        <!-- Ca chưa có dữ liệu trả về (chỉ hiển thị nếu có) -->
                                                        <template x-if="(customStats.completed_breakdown?.awaiting_classin_data || 0) > 0">
                                                            <div class="pl-3 py-2 border-l-3 border-yellow-400 dark:border-yellow-500 bg-yellow-50/70 dark:bg-yellow-900/30 rounded-r-lg">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <span class="text-sm md:text-base text-yellow-700 dark:text-yellow-400 font-medium">⏳</span>
                                                                    <span class="text-lg md:text-xl font-bold text-yellow-600 dark:text-yellow-400" x-text="formatNumber(customStats.completed_breakdown?.awaiting_classin_data || 0)"></span>
                                                                    <span class="text-sm text-yellow-600/80 dark:text-yellow-400/80 italic">ca chưa có dữ liệu trả về (thông thường Classin sẽ gửi data về sau mỗi 20ph)</span>
                                                                </div>
                                                                
                                                                <!-- Level 3: Breakdown của ca chưa có dữ liệu -->
                                                                <div class="mt-3 ml-4 md:ml-6 space-y-2">
                                                                    <!-- Ca đang chờ data (<=30 phút) -->
                                                                    <template x-if="(customStats.completed_breakdown?.awaiting_within_30min || 0) > 0">
                                                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-orange-300 dark:border-orange-500 bg-orange-50/50 dark:bg-orange-900/20 rounded-r-lg">
                                                                            <span class="text-sm text-orange-700 dark:text-orange-400 font-medium">🕐</span>
                                                                            <span class="text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.completed_breakdown?.awaiting_within_30min || 0)"></span>
                                                                            <span class="text-xs text-orange-600/80 dark:text-orange-400/80 italic">ca đang chờ data của ClassIn về</span>
                                                                        </div>
                                                                    </template>
                                                                    
                                                                    <!-- Ca KHÔNG thấy có data trên ClassIn (>30 phút) -->
                                                                    <template x-if="(customStats.completed_breakdown?.no_data_over_30min || 0) > 0">
                                                                        <div class="flex flex-wrap items-center gap-2 pl-3 py-1.5 border-l-2 border-red-300 dark:border-red-500 bg-red-50/50 dark:bg-red-900/20 rounded-r-lg">
                                                                            <span class="text-sm text-red-700 dark:text-red-400 font-medium">⚠️</span>
                                                                            <span class="text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.completed_breakdown?.no_data_over_30min || 0)"></span>
                                                                            <span class="text-xs text-red-600/80 dark:text-red-400/80 italic">ca KHÔNG thấy có data trên ClassIn</span>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <!-- Đã lên lịch -->
                                            <div class="pl-4 border-l-4 border-blue-400 dark:border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 rounded-r-lg py-3 pr-4">
                                                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                                    <span class="text-base md:text-lg font-semibold text-blue-700 dark:text-blue-400">📅 Đã lên lịch:</span>
                                                    <span class="text-xl md:text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.status_breakdown?.scheduled || 0)"></span>
                                                    <span class="text-sm md:text-base text-blue-600/90 dark:text-blue-400/90 bg-blue-100 dark:bg-blue-900/50 px-2.5 py-1 rounded-full font-medium" x-text="'(' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.scheduled || 0) / customStats.total * 1000) / 10 : 0) + '%)'"></span>
                                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã lên lịch</span><br>
                                                        Ca học có status = 2 (Scheduled).<br><br>
                                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                                        WHERE ordles_status = 2<br>
                                                        AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                                    </span></span>
                                                </div>
                                                
                                                <!-- Progress bar -->
                                                <div class="mt-2 max-w-md">
                                                    <div class="bg-blue-200 dark:bg-blue-800 rounded-full h-2.5">
                                                        <div class="bg-gradient-to-r from-blue-400 to-blue-500 h-2.5 rounded-full transition-all duration-500" :style="'width: ' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.scheduled || 0) / customStats.total * 100) : 0) + '%'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Đã hủy -->
                                            <div class="pl-4 border-l-4 border-red-400 dark:border-red-500 bg-red-50/50 dark:bg-red-900/20 rounded-r-lg py-3 pr-4">
                                                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                                    <span class="text-base md:text-lg font-semibold text-red-700 dark:text-red-400">❌ Đã hủy:</span>
                                                    <span class="text-xl md:text-2xl font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.status_breakdown?.cancelled || 0)"></span>
                                                    <span class="text-sm md:text-base text-red-600/90 dark:text-red-400/90 bg-red-100 dark:bg-red-900/50 px-2.5 py-1 rounded-full font-medium" x-text="'(' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.cancelled || 0) / customStats.total * 1000) / 10 : 0) + '%)'"></span>
                                                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy</span><br>
                                                        Ca học có status = 4 (Cancelled).<br><br>
                                                        <span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
                                                        WHERE ordles_status = 4<br>
                                                        AND ordles_lesson_starttime BETWEEN [start] AND [end]</span>
                                                    </span></span>
                                                </div>
                                                
                                                <!-- Progress bar -->
                                                <div class="mt-2 max-w-md">
                                                    <div class="bg-red-200 dark:bg-red-800 rounded-full h-2.5">
                                                        <div class="bg-gradient-to-r from-red-400 to-red-500 h-2.5 rounded-full transition-all duration-500" :style="'width: ' + (customStats.total > 0 ? Math.round((customStats.status_breakdown?.cancelled || 0) / customStats.total * 100) : 0) + '%'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Phase 143: SPEAKWELL & EASYSPEAK Program Breakdown (Custom Date) -->
                        <template x-if="customStats.program_breakdown">
                            <div class="mt-4 p-4 md:p-6 bg-gradient-to-br from-violet-50/80 via-white to-cyan-50/80 dark:from-violet-900/20 dark:via-slate-800/50 dark:to-cyan-900/20 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                                <h4 class="text-sm md:text-base font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                                    📊 Chi tiết theo Chương trình
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- SPEAKWELL -->
                                    <template x-if="customStats.program_breakdown.speakwell">
                                        <div class="rounded-xl border-2 border-violet-300 dark:border-violet-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                                            <div class="bg-gradient-to-r from-violet-500 to-purple-600 px-4 py-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">🟣 SPEAKWELL</span>
                                                    <span class="text-white/90 font-bold text-lg md:text-xl" x-text="formatNumber(customStats.program_breakdown.speakwell.total || 0)"></span>
                                                </div>
                                                <p class="text-violet-100 text-xs mt-0.5">28 bộ môn</p>
                                            </div>
                                            <div class="p-3 md:p-4 space-y-2">
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📋 Tổng ca học</span>
                                                    <span class="text-sm md:text-base font-bold text-violet-600 dark:text-violet-400" x-text="formatNumber(customStats.program_breakdown.speakwell.total || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">💰 Đã tính phí</span>
                                                    <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(customStats.program_breakdown.speakwell.chargeable || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">🕐 Chờ data ClassIn</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.program_breakdown.speakwell.awaiting_within_30min || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚠️ Không có data ClassIn</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.program_breakdown.speakwell.no_data_over_30min || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📅 Đã lên lịch</span>
                                                    <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.program_breakdown.speakwell.scheduled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">❌ Đã hủy</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.program_breakdown.speakwell.cancelled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚡ Hủy gấp</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.program_breakdown.speakwell.urgent_cancelled || 0)"></span>
                                                </div>
                                                <div class="ml-4 space-y-1.5 pb-1">
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👩‍🏫 Giáo viên hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.program_breakdown.speakwell.urgent_by_teacher || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👨‍🎓 Học sinh hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.program_breakdown.speakwell.urgent_by_student || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">🖥️ Admin hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="formatNumber(customStats.program_breakdown.speakwell.urgent_by_admin || 0)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <!-- EASYSPEAK -->
                                    <template x-if="customStats.program_breakdown.easyspeak">
                                        <div class="rounded-xl border-2 border-cyan-300 dark:border-cyan-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                                            <div class="bg-gradient-to-r from-cyan-500 to-teal-600 px-4 py-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">🔵 EASYSPEAK</span>
                                                    <span class="text-white/90 font-bold text-lg md:text-xl" x-text="formatNumber(customStats.program_breakdown.easyspeak.total || 0)"></span>
                                                </div>
                                                <p class="text-cyan-100 text-xs mt-0.5">8 bộ môn</p>
                                            </div>
                                            <div class="p-3 md:p-4 space-y-2">
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📋 Tổng ca học</span>
                                                    <span class="text-sm md:text-base font-bold text-cyan-600 dark:text-cyan-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.total || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">💰 Đã tính phí</span>
                                                    <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.chargeable || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">🕐 Chờ data ClassIn</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.awaiting_within_30min || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚠️ Không có data ClassIn</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.no_data_over_30min || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📅 Đã lên lịch</span>
                                                    <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.scheduled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">❌ Đã hủy</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.cancelled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚡ Hủy gấp</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.urgent_cancelled || 0)"></span>
                                                </div>
                                                <div class="ml-4 space-y-1.5 pb-1">
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👩‍🏫 Giáo viên hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.urgent_by_teacher || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👨‍🎓 Học sinh hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.urgent_by_student || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">🖥️ Admin hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="formatNumber(customStats.program_breakdown.easyspeak.urgent_by_admin || 0)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Phase 225: Class Type Breakdown (Custom Date) -->
                        <template x-if="customStats.class_type_breakdown">
                            <div class="mt-4 p-4 md:p-6 bg-gradient-to-br from-cyan-50/80 via-white to-teal-50/80 dark:from-cyan-900/20 dark:via-slate-800/50 dark:to-teal-900/20 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                                <h4 class="text-sm md:text-base font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                                    📊 Chi tiết theo Loại lớp
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Lớp 1:1 -->
                                    <template x-if="customStats.class_type_breakdown.one_on_one">
                                        <div class="rounded-xl border-2 border-cyan-300 dark:border-cyan-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                                            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">👤 Lớp 1:1</span>
                                                    <span class="text-white/90 font-bold text-lg md:text-xl" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.total || 0)"></span>
                                                </div>
                                                <p class="text-cyan-100 text-xs mt-0.5">1 Giáo viên - 1 Học viên</p>
                                            </div>
                                            <div class="p-3 md:p-4 space-y-2">
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📋 Tổng ca học</span>
                                                    <span class="text-sm md:text-base font-bold text-cyan-600 dark:text-cyan-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.total || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">✅ Đã hoàn thành</span>
                                                    <span class="text-sm md:text-base font-bold text-green-600 dark:text-green-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.completed || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">💰 Đã tính phí</span>
                                                    <span class="text-sm md:text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.chargeable || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">🔄 Bù buổi</span>
                                                    <span class="text-sm md:text-base font-bold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.compensate || 0)"></span>
                                                </div>
                                                <template x-if="(customStats.class_type_breakdown.one_on_one.awaiting_within_30min || 0) > 0">
                                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">🕐 Chờ data ClassIn</span>
                                                        <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.awaiting_within_30min || 0)"></span>
                                                    </div>
                                                </template>
                                                <template x-if="(customStats.class_type_breakdown.one_on_one.no_data_over_30min || 0) > 0">
                                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                        <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚠️ Không có data ClassIn</span>
                                                        <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.no_data_over_30min || 0)"></span>
                                                    </div>
                                                </template>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📅 Đã lên lịch</span>
                                                    <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.scheduled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">❌ Đã hủy</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.cancelled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚡ Hủy gấp</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.urgent_cancelled || 0)"></span>
                                                </div>
                                                <div class="ml-4 space-y-1.5 pb-1">
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👩‍🏫 Giáo viên hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.urgent_by_teacher || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👨‍🎓 Học sinh hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.urgent_by_student || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">🖥️ Admin hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_one.urgent_by_admin || 0)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <!-- Lớp 1:2 -->
                                    <template x-if="customStats.class_type_breakdown.one_on_two">
                                        <div class="rounded-xl border-2 border-teal-300 dark:border-teal-600 bg-white/80 dark:bg-slate-800/60 overflow-hidden">
                                            <div class="bg-gradient-to-r from-teal-500 to-green-600 px-4 py-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-white font-bold text-sm md:text-base flex items-center gap-2">👥 Lớp 1:2</span>
                                                    <span class="text-white/90 font-bold text-lg md:text-xl" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.total || 0)"></span>
                                                </div>
                                                <p class="text-teal-100 text-xs mt-0.5">1 Giáo viên - 2 Học viên</p>
                                            </div>
                                            <div class="p-3 md:p-4 space-y-2">
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📋 Tổng ca học</span>
                                                    <span class="text-sm md:text-base font-bold text-teal-600 dark:text-teal-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.total || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">✅ Đã hoàn thành</span>
                                                    <span class="text-sm md:text-base font-bold text-green-600 dark:text-green-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.completed || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">📅 Đã lên lịch</span>
                                                    <span class="text-sm md:text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.scheduled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">❌ Đã hủy</span>
                                                    <span class="text-sm md:text-base font-bold text-red-600 dark:text-red-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.cancelled || 0)"></span>
                                                </div>
                                                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700">
                                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-400">⚡ Hủy gấp</span>
                                                    <span class="text-sm md:text-base font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.urgent_cancelled || 0)"></span>
                                                </div>
                                                <div class="ml-4 space-y-1.5 pb-1">
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👩‍🏫 Giáo viên hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-amber-600 dark:text-amber-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.urgent_by_teacher || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">👨‍🎓 Học sinh hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.urgent_by_student || 0)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between py-1">
                                                        <span class="text-xs text-slate-500 dark:text-slate-500">🖥️ Admin hủy</span>
                                                        <span class="text-xs md:text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="formatNumber(customStats.class_type_breakdown.one_on_two.urgent_by_admin || 0)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>
                
                <!-- No Date Selected State -->
                <template x-if="!customLoading && !customDateError && !customStats && !customFromDate && !customToDate">
                    <div class="text-center py-8">
                        <p class="text-light-text-muted dark:text-zeus-text-muted">📅 Vui lòng chọn khoảng thời gian để xem số liệu</p>
                    </div>
                </template>
            </div>
        
        <!-- Comparison: Yesterday vs Day Before Yesterday -->
        @if(isset($sessionStats['yesterday']['comparison_with_day_before']))
        <div class="mt-4 p-4 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
            <h4 class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mb-3 flex items-center gap-2">
                📈 So sánh: Hôm qua vs Hôm kia
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Total Comparison -->
                <div class="text-center p-3 bg-light-card dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center justify-center gap-1">
                        @php
                            $totalChange = $sessionStats['yesterday']['comparison_with_day_before']['total'] ?? [];
                            $direction = $totalChange['direction'] ?? 'same';
                            $value = $totalChange['value'] ?? 0;
                            $diff = $totalChange['diff'] ?? 0;
                        @endphp
                        <span class="text-lg font-bold {{ $direction === 'up' ? 'text-green-600 dark:text-green-400' : ($direction === 'down' ? 'text-red-600 dark:text-red-400' : 'text-gray-500') }}">
                            {{ $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '−') }}
                            {{ $value }}%
                        </span>
                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">({{ $diff > 0 ? '+' : '' }}{{ $diff }})</span>
                    </div>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Tổng ca học</p>
                </div>
                
                <!-- Success Count Comparison -->
                <div class="text-center p-3 bg-light-card dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center justify-center gap-1">
                        @php
                            $successChange = $sessionStats['yesterday']['comparison_with_day_before']['success']['count'] ?? [];
                            $direction = $successChange['direction'] ?? 'same';
                            $value = $successChange['value'] ?? 0;
                            $diff = $successChange['diff'] ?? 0;
                        @endphp
                        <span class="text-lg font-bold {{ $direction === 'up' ? 'text-green-600 dark:text-green-400' : ($direction === 'down' ? 'text-red-600 dark:text-red-400' : 'text-gray-500') }}">
                            {{ $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '−') }}
                            {{ $value }}%
                        </span>
                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">({{ $diff > 0 ? '+' : '' }}{{ $diff }})</span>
                    </div>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">✅ Thành công</p>
                </div>
                
                <!-- Success Rate Comparison -->
                <div class="text-center p-3 bg-light-card dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center justify-center gap-1">
                        @php
                            $rateChange = $sessionStats['yesterday']['comparison_with_day_before']['success']['rate'] ?? [];
                            $direction = $rateChange['direction'] ?? 'same';
                            $value = $rateChange['value'] ?? 0;
                        @endphp
                        <span class="text-lg font-bold {{ $direction === 'up' ? 'text-green-600 dark:text-green-400' : ($direction === 'down' ? 'text-red-600 dark:text-red-400' : 'text-gray-500') }}">
                            {{ $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '−') }}
                            {{ $value }}%
                        </span>
                    </div>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">Tỷ lệ thành công</p>
                </div>
                
                <!-- Failure Comparison -->
                <div class="text-center p-3 bg-light-card dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center justify-center gap-1">
                        @php
                            $failureChange = $sessionStats['yesterday']['comparison_with_day_before']['failure']['count'] ?? [];
                            $direction = $failureChange['direction'] ?? 'same';
                            $value = $failureChange['value'] ?? 0;
                            $diff = $failureChange['diff'] ?? 0;
                            // For failure, down is good (green), up is bad (red)
                            $colorClass = $direction === 'down' ? 'text-green-600 dark:text-green-400' : ($direction === 'up' ? 'text-red-600 dark:text-red-400' : 'text-gray-500');
                        @endphp
                        <span class="text-lg font-bold {{ $colorClass }}">
                            {{ $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '−') }}
                            {{ $value }}%
                        </span>
                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted">({{ $diff > 0 ? '+' : '' }}{{ $diff }})</span>
                    </div>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">❌ Thất bại</p>
                </div>
            </div>
            
            <!-- Summary Text -->
            <div class="mt-3 text-center">
                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                    📊 Hôm qua: <span class="font-semibold text-light-text dark:text-zeus-text">{{ $sessionStats['yesterday']['success']['count'] ?? 0 }}</span> thành công / <span class="font-semibold text-light-text dark:text-zeus-text">{{ $sessionStats['yesterday']['total'] ?? 0 }}</span> tổng |
                    Hôm kia: <span class="font-semibold text-light-text dark:text-zeus-text">{{ $sessionStats['day_before_yesterday']['success']['count'] ?? 0 }}</span> thành công / <span class="font-semibold text-light-text dark:text-zeus-text">{{ $sessionStats['day_before_yesterday']['total'] ?? 0 }}</span> tổng
                </p>
            </div>
        </div>
        @endif
        <!-- Monthly Session Trend Charts -->
        @if(isset($monthlySessionTrendChart))
        <div class="mt-4 p-4 bg-gradient-to-r from-emerald-500/5 via-amber-500/5 to-orange-500/5 dark:from-emerald-500/10 dark:via-amber-500/10 dark:to-orange-500/10 rounded-lg border border-emerald-500/20">
            <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                📈 Xu hướng các tỷ lệ trong tháng {{ $monthlySessionTrendChart['month_label'] ?? '' }}
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Giải thích</span><br>
                    • <span class="text-green-500">Tỷ lệ TC</span>: Ca học thành công / Tổng ca học<br>
                    • <span class="text-amber-500">Tỷ lệ Hủy</span>: Ca học bị hủy / Tổng ca học<br>
                    • <span class="text-orange-500">GV No-show</span>: GV không vào / Tổng ca học<br>
                    • <span class="text-red-500">HV No-show</span>: HV không vào / Tổng ca học
                </span></span>
            </h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Success & Cancel Rate Chart -->
                <div class="bg-light-card dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                    <h5 class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Tỷ lệ Thành công & Hủy (%)</h5>
                    <div class="h-48">
                        <canvas id="successCancelRateChart"></canvas>
                    </div>
                </div>
                <!-- No-show Rate Chart -->
                <div class="bg-light-card dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                    <h5 class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Tỷ lệ No-show GV & HV (%)</h5>
                    <div class="h-48">
                        <canvas id="noShowRateChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Acceptance Codes Trend Chart (Full Width) -->
            @if(isset($monthlyAcceptanceCodesTrendChart))
            <div class="mt-4 bg-light-card dark:bg-zeus-card-light rounded-lg p-3 border border-light-border dark:border-zeus-border">
                <h5 class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2 flex items-center gap-2">
                    📊 Xu hướng số lượng theo mã chấp nhận
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Giải thích</span><br>
                        • <span class="text-green-500">Có tính phí HV</span>: Mã 4-12, 16, 17<br>
                        • <span class="text-red-500">Phải bù buổi</span>: Mã 1-3, 13-15
                    </span></span>
                </h5>
                <div class="h-48">
                    <canvas id="acceptanceCodesTrendChart"></canvas>
                </div>
            </div>
            @endif
        </div>
        @endif
        </div>
    </div>

    <!-- ===== REAL-TIME LESSON STATUS ===== -->
    <div class="bg-gradient-to-r from-cyan-500/5 via-blue-500/5 to-indigo-500/5 dark:from-cyan-500/10 dark:via-blue-500/10 dark:to-indigo-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="realTimeLessonStatus()"
         x-init="fetchRealTimeStatus()">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3 md:mb-4">
            <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2 flex-wrap">
                ⏰ Trạng thái Ca học Thời gian thực
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Thông tin</span><br>Dữ liệu được cập nhật mỗi 30 giây. Click "Làm mới" để cập nhật ngay.</span></span>
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'Cập nhật: ' + lastUpdated"></span>
                <button @click="fetchRealTimeStatus()" 
                        class="px-3 py-1.5 text-xs bg-cyan-500/20 text-cyan-600 dark:text-cyan-400 rounded-lg hover:bg-cyan-500/30 transition flex items-center gap-1"
                        :disabled="loading">
                    <svg x-show="loading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">🔄</span>
                    Làm mới
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4 mb-4">
            <!-- Ongoing Lessons -->
            <div class="text-center p-3 md:p-4 bg-green-500/10 rounded-lg border border-green-500/30 relative">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-xs font-medium text-green-600 dark:text-green-400">ĐANG DIỄN RA</span>
                </div>
                <p class="text-2xl md:text-3xl font-bold text-green-600 dark:text-green-400" x-text="ongoing.count || 0"></p>
                <p class="text-xs md:text-sm text-green-600/80 dark:text-green-400/80">🎓 Ca học đang diễn ra</p>
                <!-- Summary stats for current time slot -->
                <div class="mt-2 pt-2 border-t border-green-500/20 text-[10px] text-green-600/70 dark:text-green-400/70 flex flex-wrap justify-center gap-x-2 gap-y-1">
                    <span>📚 Tổng: <strong x-text="ongoing.slot_stats?.total || 0"></strong></span>
                    <span>👥 HV: <strong x-text="ongoing.slot_stats?.unique_students || 0"></strong></span>
                    <span class="text-emerald-600 dark:text-emerald-400">✅ TC: <strong x-text="ongoing.slot_stats?.success || 0"></strong></span>
                    <span class="text-red-600 dark:text-red-400">❌ Thất bại: <strong x-text="ongoing.slot_stats?.failed || 0"></strong></span>
                    <span class="text-amber-600 dark:text-amber-400">🚫 Hủy: <strong x-text="ongoing.slot_stats?.cancelled || 0"></strong></span>
                </div>
                <template x-if="ongoing.count > 0">
                    <button @click="openOngoingModal()" class="mt-2 px-3 py-1 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition">Chi tiết</button>
                </template>
            </div>
            
            <!-- Upcoming Lessons -->
            <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30 relative">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1">📅 KHUNG GIỜ TIẾP THEO (60')</p>
                <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="upcoming.count || 0"></p>
                <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">⏳ Sắp diễn ra</p>
                <!-- Summary stats for upcoming lessons -->
                <div class="mt-2 pt-2 border-t border-blue-500/20 text-[10px] text-blue-600/70 dark:text-blue-400/70 flex flex-wrap justify-center gap-x-2 gap-y-1">
                    <span>📚 Tổng: <strong x-text="upcoming.count || 0"></strong></span>
                    <span>👥 HV: <strong x-text="upcoming.unique_students || 0"></strong></span>
                    <span class="text-amber-600 dark:text-amber-400">🚫 Hủy: <strong x-text="upcoming.cancelled || 0"></strong></span>
                </div>
                <template x-if="upcoming.count > 0">
                    <button @click="openUpcomingModal()" class="mt-2 px-3 py-1 text-xs bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-500/30 transition">Chi tiết</button>
                </template>
            </div>
            
            <!-- Remaining Lessons -->
            <div class="text-center p-3 md:p-4 bg-indigo-500/10 rounded-lg border border-indigo-500/30 relative">
                <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-1">📋 CÒN LẠI HÔM NAY</p>
                <p class="text-2xl md:text-3xl font-bold text-indigo-600 dark:text-indigo-400" x-text="remaining.count || 0"></p>
                <p class="text-xs md:text-sm text-indigo-600/80 dark:text-indigo-400/80">📚 Chưa diễn ra</p>
                <template x-if="remaining.count > 0">
                    <button @click="openRemainingModal()" class="mt-2 px-3 py-1 text-xs bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-500/30 transition">Chi tiết</button>
                </template>
            </div>
        </div>
        
        <!-- Time Slot Heatmap -->
        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 md:p-4 border border-light-border dark:border-zeus-border">
            <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3 flex items-center gap-2">
                🗓️ Bản đồ nhiệt các ca học (Hôm nay)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Chú thích</span><br>
                    • Màu sắc thể hiện số lượng ca học<br>
                    • Hover để xem chi tiết từng khung giờ<br>
                    • 🟢 Hiện tại | ⬜ Tương lai | ⬛ Đã qua
                </span></span>
            </h4>
            <div class="grid grid-cols-6 sm:grid-cols-12 gap-1">
                <template x-for="slot in heatmap.slots || []" :key="slot.hour">
                    <div class="relative group cursor-pointer"
                         :class="{
                             'ring-2 ring-green-500': slot.is_current,
                             'opacity-60': slot.is_past && slot.total === 0
                         }">
                        <div class="text-center p-1.5 sm:p-2 rounded-lg transition-all duration-200 hover:scale-105"
                             :class="{
                                 'bg-gray-200 dark:bg-gray-700': slot.total === 0,
                                 'bg-blue-200 dark:bg-blue-800': slot.intensity === 1,
                                 'bg-blue-300 dark:bg-blue-700': slot.intensity === 2,
                                 'bg-blue-400 dark:bg-blue-600': slot.intensity === 3,
                                 'bg-blue-500 dark:bg-blue-500': slot.intensity === 4,
                                 'bg-blue-600 dark:bg-blue-400': slot.intensity >= 5
                             }">
                            <p class="text-[9px] sm:text-[10px] font-medium whitespace-nowrap leading-none" 
                               :class="slot.intensity >= 4 ? 'text-white' : 'text-gray-700 dark:text-gray-300'"
                               x-text="slot.label"></p>
                            <p class="text-sm font-bold"
                               :class="slot.intensity >= 4 ? 'text-white' : 'text-gray-900 dark:text-gray-100'"
                               x-text="slot.total"></p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-20">
                            <div class="bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap shadow-lg min-w-[140px]">
                                <p class="font-bold mb-1" x-text="slot.label + ' - ' + (slot.hour + 1) + ':00'"></p>
                                <p>📚 Tổng: <span x-text="slot.total" class="font-bold"></span></p>
                                <p>👥 HV: <span x-text="slot.unique_students" class="font-bold"></span></p>
                                <p class="text-green-400">✅ TC: <span x-text="slot.success" class="font-bold"></span></p>
                                <p class="text-red-400">❌ Thất bại: <span x-text="slot.failed" class="font-bold"></span></p>
                                <p class="text-yellow-400">🚫 Hủy: <span x-text="slot.cancelled" class="font-bold"></span></p>
                                <p class="text-blue-400">📅 Chờ: <span x-text="slot.scheduled" class="font-bold"></span></p>
                                <template x-if="slot.completed > 0">
                                    <p class="mt-1 text-emerald-400">Tỷ lệ TC: <span x-text="slot.success_rate + '%'" class="font-bold"></span></p>
                                </template>
                                <div class="absolute left-1/2 -translate-x-1/2 top-full w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-light-text-muted dark:text-zeus-text-muted">
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded"></span> 0 ca</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-blue-300 dark:bg-blue-700 rounded"></span> Ít</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-blue-500 rounded"></span> Trung bình</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-blue-600 dark:bg-blue-400 rounded"></span> Nhiều</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 ring-2 ring-green-500 rounded"></span> Đang diễn ra</span>
            </div>
        </div>
        
        <!-- Yesterday Time Slot Heatmap -->
        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 md:p-4 border border-light-border dark:border-zeus-border mt-4"
             x-data="yesterdayHeatmap()"
             x-init="fetchYesterdayHeatmap()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                    📅 Bản đồ nhiệt các ca học (Hôm qua)
                    <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + yesterdayDate + ')'"></span>
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Chú thích</span><br>
                        • Dữ liệu các ca học ngày hôm qua<br>
                        • Màu sắc thể hiện số lượng ca học<br>
                        • Hover để xem chi tiết từng khung giờ
                    </span></span>
                </h4>
                <button @click="fetchYesterdayHeatmap()" 
                        class="self-start sm:self-auto px-2 py-1 text-xs bg-gray-500/20 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-500/30 transition inline-flex items-center gap-1"
                        :disabled="loading">
                    <svg x-show="loading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">🔄</span>
                    Làm mới
                </button>
            </div>
            <div class="grid grid-cols-6 sm:grid-cols-12 gap-1">
                <template x-for="slot in yesterdayHeatmapData.slots || []" :key="slot.hour">
                    <div class="relative group cursor-pointer"
                         :class="{
                             'opacity-60': slot.total === 0
                         }">
                        <div class="text-center p-1.5 sm:p-2 rounded-lg transition-all duration-200 hover:scale-105"
                             :class="{
                                 'bg-gray-200 dark:bg-gray-700': slot.total === 0,
                                 'bg-purple-200 dark:bg-purple-800': slot.intensity === 1,
                                 'bg-purple-300 dark:bg-purple-700': slot.intensity === 2,
                                 'bg-purple-400 dark:bg-purple-600': slot.intensity === 3,
                                 'bg-purple-500 dark:bg-purple-500': slot.intensity === 4,
                                 'bg-purple-600 dark:bg-purple-400': slot.intensity >= 5
                             }">
                            <p class="text-[9px] sm:text-[10px] font-medium whitespace-nowrap leading-none" 
                               :class="slot.intensity >= 4 ? 'text-white' : 'text-gray-700 dark:text-gray-300'"
                               x-text="slot.label"></p>
                            <p class="text-sm font-bold"
                               :class="slot.intensity >= 4 ? 'text-white' : 'text-gray-900 dark:text-gray-100'"
                               x-text="slot.total"></p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-20">
                            <div class="bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap shadow-lg min-w-[140px]">
                                <p class="font-bold mb-1" x-text="slot.label + ' - ' + (slot.hour + 1) + ':00'"></p>
                                <p>📚 Tổng: <span x-text="slot.total" class="font-bold"></span></p>
                                <p>👥 HV: <span x-text="slot.unique_students" class="font-bold"></span></p>
                                <p class="text-green-400">✅ TC: <span x-text="slot.success" class="font-bold"></span></p>
                                <p class="text-red-400">❌ Thất bại: <span x-text="slot.failed" class="font-bold"></span></p>
                                <p class="text-yellow-400">🚫 Hủy: <span x-text="slot.cancelled" class="font-bold"></span></p>
                                <template x-if="slot.completed > 0">
                                    <p class="mt-1 text-emerald-400">Tỷ lệ TC: <span x-text="slot.success_rate + '%'" class="font-bold"></span></p>
                                </template>
                                <div class="absolute left-1/2 -translate-x-1/2 top-full w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-light-text-muted dark:text-zeus-text-muted">
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded"></span> 0 ca</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-purple-300 dark:bg-purple-700 rounded"></span> Ít</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-purple-500 rounded"></span> Trung bình</span>
                <span class="flex items-center gap-1"><span class="w-4 h-4 bg-purple-600 dark:bg-purple-400 rounded"></span> Nhiều</span>
            </div>
        </div>
        
        <!-- Ongoing Lessons Modal -->
        <div x-show="showOngoingModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showOngoingModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        🎓 Ca học đang diễn ra
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                    </h3>
                    <button @click="showOngoingModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="ongoing.lessons && ongoing.lessons.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Khung giờ</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Thời lượng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(lesson, index) in ongoing.lessons" :key="lesson.id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2">
                                                <p class="text-light-text dark:text-zeus-text font-medium" x-text="lesson.student_name"></p>
                                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="lesson.student_email"></p>
                                            </td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="lesson.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono" x-text="lesson.time_slot"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-full" x-text="lesson.duration + ' phút'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!ongoing.lessons || ongoing.lessons.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có ca học nào đang diễn ra</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="ongoing.count || 0"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportOngoingToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showOngoingModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Lessons Modal -->
        <div x-show="showUpcomingModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showUpcomingModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        ⏳ Ca học sắp diễn ra (60 phút tới)
                    </h3>
                    <button @click="showUpcomingModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="upcoming.lessons && upcoming.lessons.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Khung giờ</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Bắt đầu sau</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(lesson, index) in upcoming.lessons" :key="lesson.id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2">
                                                <p class="text-light-text dark:text-zeus-text font-medium" x-text="lesson.student_name"></p>
                                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="lesson.student_email"></p>
                                            </td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="lesson.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono" x-text="lesson.time_slot"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-full" x-text="lesson.minutes_until_start + ' phút'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!upcoming.lessons || upcoming.lessons.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có ca học nào trong 60 phút tới</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="upcoming.count || 0"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportUpcomingToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showUpcomingModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Remaining Lessons Modal -->
        <div x-show="showRemainingModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showRemainingModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        📚 Ca học còn lại hôm nay
                    </h3>
                    <button @click="showRemainingModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <!-- By Hour Summary -->
                    <template x-if="remaining.by_hour && Object.keys(remaining.by_hour).length > 0">
                        <div class="mb-4 p-3 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg">
                            <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-2">Phân bổ theo giờ:</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(count, hour) in remaining.by_hour" :key="hour">
                                    <span class="px-2 py-1 text-xs bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-full">
                                        <span x-text="hour"></span>: <strong x-text="count"></strong>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="remaining.lessons && remaining.lessons.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Khung giờ</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Thời lượng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(lesson, index) in remaining.lessons" :key="lesson.id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2">
                                                <p class="text-light-text dark:text-zeus-text font-medium" x-text="lesson.student_name"></p>
                                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="lesson.student_email"></p>
                                            </td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="lesson.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono" x-text="lesson.time_slot"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-full" x-text="lesson.duration + ' phút'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!remaining.lessons || remaining.lessons.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không còn ca học nào hôm nay</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="remaining.count || 0"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportRemainingToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showRemainingModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== NEVER LOGGED IN STUDENTS STATS ===== -->
    <div class="bg-gradient-to-r from-purple-500/5 via-pink-500/5 to-rose-500/5 dark:from-purple-500/10 dark:via-pink-500/10 dark:to-rose-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="neverLoggedInSection()">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-3 md:mb-4 flex items-center gap-2 flex-wrap">
            👤 Tình trạng đăng nhập của Học viên
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT u.* FROM tbl_users u WHERE u.user_id IN (SELECT DISTINCT o.order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE ol.ordles_tlang_id IN (533,558,560,...)) AND u.user_lastseen IS NULL</code></span></span>
        </h3>
        
        <!-- Period Tabs for Never Logged In Stats -->
        <div x-data="{ activeNeverLoggedTab: 'today' }">
            <div class="tabs-container mb-3 md:mb-4">
                <button @click="activeNeverLoggedTab = 'today'" :class="activeNeverLoggedTab === 'today' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm nay</button>
                <button @click="activeNeverLoggedTab = 'yesterday'" :class="activeNeverLoggedTab === 'yesterday' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm qua</button>
                <button @click="activeNeverLoggedTab = 'day_before'" :class="activeNeverLoggedTab === 'day_before' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm kia</button>
                <button @click="activeNeverLoggedTab = 'week'" :class="activeNeverLoggedTab === 'week' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần này</button>
                <button @click="activeNeverLoggedTab = 'last_week'" :class="activeNeverLoggedTab === 'last_week' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần trước</button>
                <button @click="activeNeverLoggedTab = 'month'" :class="activeNeverLoggedTab === 'month' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng này</button>
                <!-- HIDDEN: Tháng trước button temporarily hidden per Phase 8 -->
                <!-- <button @click="activeNeverLoggedTab = 'last_month'" :class="activeNeverLoggedTab === 'last_month' ? 'bg-purple-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng trước</button> -->
            </div>
            
            <!-- Today Stats -->
            <div x-show="activeNeverLoggedTab === 'today'" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['today']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['today']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('today')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['today']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(DISTINCT order_user_id) FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_tlang_id IN (533,558,560,...)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['today']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa login
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (HV chưa đăng nhập / Tổng HV có lịch) × 100</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['today']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT order_user_id, COUNT(*) as cnt FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status IN (2) AND ol.ordles_tlang_id IN (533,558,560,...) GROUP BY order_user_id HAVING cnt >= 2</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['today']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('today')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Yesterday Stats -->
            <div x-show="activeNeverLoggedTab === 'yesterday'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['yesterday']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['yesterday']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('yesterday')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['yesterday']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(DISTINCT order_user_id) FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ol.ordles_tlang_id IN (533,558,560,...)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['yesterday']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa login
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (HV chưa đăng nhập / Tổng HV có lịch) × 100</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['yesterday']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['yesterday']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('yesterday')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Day Before Yesterday Stats -->
            <div x-show="activeNeverLoggedTab === 'day_before'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['day_before_yesterday']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['day_before_yesterday']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('day_before_yesterday')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['day_before_yesterday']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(DISTINCT order_user_id) FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AND ol.ordles_tlang_id IN (533,558,560,...)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['day_before_yesterday']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa login
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (HV chưa đăng nhập / Tổng HV có lịch) × 100</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['day_before_yesterday']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['day_before_yesterday']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('day_before_yesterday')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- This Week Stats -->
            <div x-show="activeNeverLoggedTab === 'week'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['this_week']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE ol.ordles_lesson_starttime BETWEEN [week_start] AND [week_end] AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['this_week']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('this_week')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['this_week']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(DISTINCT order_user_id) FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE ol.ordles_lesson_starttime BETWEEN [week_start] AND [week_end] AND ol.ordles_tlang_id IN (533,558,560,...)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['this_week']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa login
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (HV chưa đăng nhập / Tổng HV có lịch) × 100</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['this_week']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['this_week']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('this_week')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Last Week Stats -->
            <div x-show="activeNeverLoggedTab === 'last_week'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['last_week']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập</p>
                        @if(($neverLoggedInStats['last_week']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('last_week')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['last_week']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['last_week']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa ĐN</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['last_week']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['last_week']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('last_week')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- This Month Stats -->
            <div x-show="activeNeverLoggedTab === 'month'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['this_month']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE ol.ordles_lesson_starttime BETWEEN [month_start] AND [month_end] AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                        @if(($neverLoggedInStats['this_month']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('this_month')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['this_month']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(DISTINCT order_user_id) FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE ol.ordles_lesson_starttime BETWEEN [month_start] AND [month_end] AND ol.ordles_tlang_id IN (533,558,560,...)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['this_month']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa login
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (HV chưa đăng nhập / Tổng HV có lịch) × 100</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['this_month']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['this_month']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('this_month')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Last Month Stats -->
            <div x-show="activeNeverLoggedTab === 'last_month'" style="display: none;" class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['last_month']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập</p>
                        @if(($neverLoggedInStats['last_month']['count'] ?? 0) > 0)
                        <button @click="openNeverLoggedInModal('last_month')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['last_month']['total_students_with_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-rose-500/10 rounded-lg border border-rose-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $neverLoggedInStats['last_month']['rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-rose-600/80 dark:text-rose-400/80">📊 Tỷ lệ chưa ĐN</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-teal-500/10 rounded-lg border border-teal-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $neverLoggedInStats['last_month']['students_with_multiple_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-teal-600/80 dark:text-teal-400/80">👥 HV có ≥2 ca/ngày</p>
                        @if(($neverLoggedInStats['last_month']['students_with_multiple_lessons'] ?? 0) > 0)
                        <button @click="openMultiLessonModal('last_month')" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Never Logged In Trend Chart for Month -->
        @if(isset($neverLoggedInTrendChart) && !empty($neverLoggedInTrendChart['labels']))
        <div class="mt-4 pt-4 border-t border-light-border dark:border-zeus-border">
            <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3 flex items-center gap-2">
                📈 Xu thế HV chưa đăng nhập trong tháng {{ $neverLoggedInTrendChart['month_label'] ?? '' }}
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Mô tả</span><br>Biểu đồ thể hiện số lượng và tỷ lệ học viên chưa từng đăng nhập theo từng ngày trong tháng.</span></span>
            </h4>
            <div class="bg-light-card dark:bg-zeus-card-light rounded-lg p-4">
                <canvas id="neverLoggedInTrendChart" height="200"></canvas>
            </div>
        </div>
        @endif
        
        <!-- Never Logged In Students Modal -->
        <div x-show="showNeverLoggedInModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showNeverLoggedInModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        🚫 Danh sách HV chưa từng đăng nhập (<span x-text="periodLabels[currentPeriod] || currentPeriod"></span>)
                    </h3>
                    <button @click="showNeverLoggedInModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="neverLoggedInLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </template>
                    <template x-if="!neverLoggedInLoading && neverLoggedInStudents.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Username</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Họ tên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">SĐT</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(student, index) in neverLoggedInStudents" :key="student.user_id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="student.username"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="student.name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="student.email"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="student.phone"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!neverLoggedInLoading && neverLoggedInStudents.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="neverLoggedInStudents.length"></strong> học viên
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportNeverLoggedInToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showNeverLoggedInModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Multi-Lesson Students Modal -->
        <div x-show="showMultiLessonModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showMultiLessonModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        👥 Danh sách HV có ≥2 ca học/ngày (<span x-text="periodLabels[currentPeriod] || currentPeriod"></span>)
                    </h3>
                    <button @click="showMultiLessonModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="multiLessonLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </template>
                    <template x-if="!multiLessonLoading && multiLessonStudents.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Username</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Họ tên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">SĐT</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Số ca</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Khung giờ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(student, index) in multiLessonStudents" :key="index">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text font-mono text-xs" x-text="student.username"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="student.name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="student.email"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="student.phone"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="student.lesson_date"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-full font-bold" x-text="student.lesson_count"></span>
                                            </td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="(slot, slotIndex) in (student.time_slots || [])" :key="slotIndex">
                                                        <span class="px-1.5 py-0.5 text-[10px] bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded font-mono" x-text="slot"></span>
                                                    </template>
                                                    <template x-if="!student.time_slots || student.time_slots.length === 0">
                                                        <span class="text-light-text-muted dark:text-zeus-text-muted" x-text="student.time_slots_display || '-'"></span>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!multiLessonLoading && multiLessonStudents.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="multiLessonCount"></strong> học viên (<strong x-text="multiLessonStudents.length"></strong> bản ghi)
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportMultiLessonToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showMultiLessonModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TEACHER LOGIN STATUS (No-show & Entry/Exit Violations) ===== -->
    <div class="bg-gradient-to-r from-amber-500/5 via-orange-500/5 to-red-500/5 dark:from-amber-500/10 dark:via-orange-500/10 dark:to-red-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="teacherLoginStatusSection()">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-3 md:mb-4 flex items-center gap-2 flex-wrap">
            👨‍🏫 Tình trạng đăng nhập của Giáo viên
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons_extras</span><br>No-show: ole_teacher_first_join IS NULL<br>Vào trễ: ole_teacher_first_join > lesson_start + 5 phút<br>Ra sớm: ole_teacher_last_leave < lesson_end - 5 phút</span></span>
        </h3>
        
        <!-- Period Tabs for Teacher Login Status -->
        <div x-data="{ activeTeacherTab: 'today' }">
            <div class="tabs-container mb-3 md:mb-4">
                <button @click="activeTeacherTab = 'today'" :class="activeTeacherTab === 'today' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm nay</button>
                <button @click="activeTeacherTab = 'yesterday'" :class="activeTeacherTab === 'yesterday' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm qua</button>
                <button @click="activeTeacherTab = 'day_before'" :class="activeTeacherTab === 'day_before' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm kia</button>
                <button @click="activeTeacherTab = 'week'" :class="activeTeacherTab === 'week' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần này</button>
                <button @click="activeTeacherTab = 'last_week'" :class="activeTeacherTab === 'last_week' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần trước</button>
                <button @click="activeTeacherTab = 'month'" :class="activeTeacherTab === 'month' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng này</button>
                <!-- HIDDEN: Tháng trước button temporarily hidden per Phase 8 -->
                <!-- <button @click="activeTeacherTab = 'last_month'" :class="activeTeacherTab === 'last_month' ? 'bg-amber-500 text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng trước</button> -->
            </div>
            
            <!-- Today Stats -->
            <div x-show="activeTeacherTab === 'today'" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['today']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = CURDATE() AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                        </p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['today']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND ole.ole_teacher_first_join IS NULL</code></span></span>
                        </p>
                        @if(($teacherLoginStatus['today']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('today')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['today']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_starttime, ole.ole_teacher_first_join) > 5</code></span></span>
                        </p>
                        @if(($teacherLoginStatus['today']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('today')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['today']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_starttime, ole.ole_teacher_last_leave) < (ol.ordles_duration - 5)</code></span></span>
                        </p>
                        @if(($teacherLoginStatus['today']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('today')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['today']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ vi phạm = (No-show + Vào trễ + Ra sớm) / Tổng ca hoàn thành × 100</code></span></span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Yesterday Stats -->
            <div x-show="activeTeacherTab === 'yesterday'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['yesterday']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['yesterday']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['yesterday']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('yesterday')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['yesterday']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['yesterday']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('yesterday')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['yesterday']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['yesterday']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('yesterday')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['yesterday']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
            
            <!-- Day Before Yesterday Stats -->
            <div x-show="activeTeacherTab === 'day_before'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['day_before_yesterday']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['day_before_yesterday']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['day_before_yesterday']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('day_before_yesterday')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['day_before_yesterday']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['day_before_yesterday']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('day_before_yesterday')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['day_before_yesterday']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['day_before_yesterday']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('day_before_yesterday')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['day_before_yesterday']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
            
            <!-- This Week Stats -->
            <div x-show="activeTeacherTab === 'week'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['this_week']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['this_week']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['this_week']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('this_week')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['this_week']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['this_week']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('this_week')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['this_week']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['this_week']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('this_week')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['this_week']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
            
            <!-- Last Week Stats -->
            <div x-show="activeTeacherTab === 'last_week'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['last_week']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['last_week']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['last_week']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('last_week')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['last_week']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['last_week']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('last_week')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['last_week']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['last_week']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('last_week')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['last_week']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
            
            <!-- This Month Stats -->
            <div x-show="activeTeacherTab === 'month'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['this_month']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['this_month']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['this_month']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('this_month')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['this_month']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['this_month']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('this_month')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['this_month']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['this_month']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('this_month')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['this_month']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
            
            <!-- Last Month Stats -->
            <div x-show="activeTeacherTab === 'last_month'" style="display: none;" class="w-full">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4">
                    <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherLoginStatus['last_month']['total_completed_lessons'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Ca học hoàn thành</p>
                    </div>
                    <div class="text-center p-3 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $teacherLoginStatus['last_month']['no_show']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">🚫 GV No-show</p>
                        @if(($teacherLoginStatus['last_month']['no_show']['count'] ?? 0) > 0)
                        <button @click="openNoShowModal('last_month')" class="mt-2 px-3 py-1 text-xs bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30 relative">
                        <p class="text-2xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teacherLoginStatus['last_month']['late_entry']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-yellow-600/80 dark:text-yellow-400/80">⏰ Vào trễ (>5 phút)</p>
                        @if(($teacherLoginStatus['last_month']['late_entry']['count'] ?? 0) > 0)
                        <button @click="openLateEntryModal('last_month')" class="mt-2 px-3 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teacherLoginStatus['last_month']['early_exit']['count'] ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚪 Ra sớm (>5 phút)</p>
                        @if(($teacherLoginStatus['last_month']['early_exit']['count'] ?? 0) > 0)
                        <button @click="openEarlyExitModal('last_month')" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                        @endif
                    </div>
                    <div class="text-center p-3 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ $teacherLoginStatus['last_month']['violation_rate'] ?? 0 }}%</p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">📊 Tỷ lệ vi phạm</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Teacher No-Show Modal -->
        <div x-show="showNoShowModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showNoShowModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-5xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        🚫 Danh sách GV No-show (<span x-text="periodLabels[currentPeriod] || currentPeriod"></span>)
                    </h3>
                    <button @click="showNoShowModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="noShowLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </template>
                    <template x-if="!noShowLoading && noShowTeachers.length > 0">
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
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(item, index) in noShowTeachers" :key="item.lesson_id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="item.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.teacher_email"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="item.student_name"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_date"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_time"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.duration + ' phút'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!noShowLoading && noShowTeachers.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="noShowTeachers.length"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportNoShowToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showNoShowModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Teacher Late Entry Modal -->
        <div x-show="showLateEntryModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showLateEntryModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-5xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        ⏰ Danh sách GV vào trễ (<span x-text="periodLabels[currentPeriod] || currentPeriod"></span>)
                    </h3>
                    <button @click="showLateEntryModal = false" class="p-2 rounded-lg hover:bg-light-border dark:hover:bg-zeus-border transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <template x-if="lateEntryLoading">
                        <div class="text-center py-8">
                            <span class="spinner-inline"></span>
                            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </template>
                    <template x-if="!lateEntryLoading && lateEntryTeachers.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Email GV</th>
                                        <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Giờ lịch</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Giờ vào</th>
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Trễ (phút)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(item, index) in lateEntryTeachers" :key="item.lesson_id">
                                        <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                            <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="index + 1"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="item.teacher_name"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text text-xs" x-text="item.teacher_email"></td>
                                            <td class="px-3 py-2 text-light-text dark:text-zeus-text" x-text="item.student_name"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.lesson_date"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.scheduled_time"></td>
                                            <td class="px-3 py-2 text-center text-light-text dark:text-zeus-text text-xs" x-text="item.actual_join_time"></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-full font-bold" x-text="item.late_minutes"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!lateEntryLoading && lateEntryTeachers.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="lateEntryTeachers.length"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportLateEntryToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showLateEntryModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Teacher Early Exit Modal -->
        <div x-show="showEarlyExitModal" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEarlyExitModal = false"></div>
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-5xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between p-4 border-b border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                        🚪 Danh sách GV ra sớm (<span x-text="periodLabels[currentPeriod] || currentPeriod"></span>)
                    </h3>
                    <button @click="showEarlyExitModal = false" class="p-2 hover:bg-light-hover dark:hover:bg-zeus-hover rounded-lg transition">
                        <svg class="w-5 h-5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-auto max-h-[60vh]">
                    <template x-if="earlyExitLoading">
                        <div class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                        </div>
                    </template>
                    <template x-if="!earlyExitLoading && earlyExitTeachers.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-light-card-alt dark:bg-zeus-card-light">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">STT</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Giáo viên</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Học viên</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Ngày</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Bắt đầu</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Kết thúc dự kiến</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Ra thực tế</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wider">Ra sớm (phút)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                    <template x-for="(item, index) in earlyExitTeachers" :key="item.lesson_id">
                                        <tr class="hover:bg-light-hover dark:hover:bg-zeus-hover transition">
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text" x-text="index + 1"></td>
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text font-medium" x-text="item.teacher_name"></td>
                                            <td class="px-4 py-3 text-light-text-muted dark:text-zeus-text-muted text-xs" x-text="item.teacher_email"></td>
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text" x-text="item.student_name"></td>
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text" x-text="item.lesson_date"></td>
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text" x-text="item.scheduled_time"></td>
                                            <td class="px-4 py-3 text-light-text dark:text-zeus-text" x-text="item.expected_end_time"></td>
                                            <td class="px-4 py-3 text-purple-600 dark:text-purple-400 font-medium" x-text="item.actual_leave_time"></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs font-bold bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full" x-text="item.early_minutes + ' phút'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!earlyExitLoading && earlyExitTeachers.length === 0">
                        <p class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">Không có dữ liệu</p>
                    </template>
                </div>
                <div class="p-4 border-t border-light-border dark:border-zeus-border bg-light-card-alt dark:bg-zeus-card-light flex justify-between items-center">
                    <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                        Tổng: <strong x-text="earlyExitTeachers.length"></strong> ca học
                    </span>
                    <div class="flex gap-2">
                        <button @click="exportEarlyExitToExcel()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Xuất Excel
                        </button>
                        <button @click="showEarlyExitModal = false" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition font-medium text-sm">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ACCEPTANCE CODES REFERENCE & STATISTICS ===== -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm" x-data="penalizedTeachersSection()">
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
                <!-- HIDDEN: Tháng trước button temporarily hidden per Phase 8 -->
                <!-- <button @click="activeCodeTab = 'last_month'" :class="activeCodeTab === 'last_month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tháng trước</button> -->
            </div>
            
            <!-- Today Stats -->
            <div x-show="activeCodeTab === 'today'">
                @if(isset($acceptanceCodeStats['today']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['today']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = CURDATE() AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['today']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['today']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['today']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('today')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['today']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['today']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['today']['codes'] ?? [] as $codeData)
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
            
            <!-- Yesterday Stats -->
            <div x-show="activeCodeTab === 'yesterday'" style="display: none;">
                @if(isset($acceptanceCodeStats['yesterday']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['yesterday']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['yesterday']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['yesterday']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['yesterday']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('yesterday')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['yesterday']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['yesterday']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['yesterday']['codes'] ?? [] as $codeData)
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
            
            <!-- Day Before Yesterday Stats (Hôm kia) -->
            <div x-show="activeCodeTab === 'day_before'" style="display: none;">
                @if(isset($acceptanceCodeStats['day_before_yesterday']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['day_before_yesterday']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['day_before_yesterday']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['day_before_yesterday']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['day_before_yesterday']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('day_before_yesterday')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['day_before_yesterday']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['day_before_yesterday']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['day_before_yesterday']['codes'] ?? [] as $codeData)
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
            
            <!-- Week Stats -->
            <div x-show="activeCodeTab === 'week'" style="display: none;">
                @if(isset($acceptanceCodeStats['this_week']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['this_week']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_lesson_starttime BETWEEN [week_start] AND [week_end] AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['this_week']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [week_start] AND [week_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['this_week']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [week_start] AND [week_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['this_week']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('this_week')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['this_week']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['this_week']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['this_week']['codes'] ?? [] as $codeData)
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
            
            <!-- Last Week Stats (Tuần trước) -->
            <div x-show="activeCodeTab === 'last_week'" style="display: none;">
                @if(isset($acceptanceCodeStats['last_week']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['last_week']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_lesson_starttime BETWEEN [last_week_start] AND [last_week_end] AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['last_week']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [last_week_start] AND [last_week_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['last_week']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [last_week_start] AND [last_week_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['last_week']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('last_week')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['last_week']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['last_week']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['last_week']['codes'] ?? [] as $codeData)
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
            
            <!-- Month Stats -->
            <div x-show="activeCodeTab === 'month'" style="display: none;">
                @if(isset($acceptanceCodeStats['this_month']))
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-500/10 rounded-lg border border-blue-500/30">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $acceptanceCodeStats['this_month']['total_completed'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Tổng hoàn thành
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_lesson_starttime BETWEEN [month_start] AND [month_end] AND ordles_status = 3 AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $acceptanceCodeStats['this_month']['total_success'] ?? 0 }}</p>
                            <p class="text-xs text-green-600/80 dark:text-green-400/80">Thành công (Code 12)
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [month_start] AND [month_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code = 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-500/10 rounded-lg border border-red-500/30">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $acceptanceCodeStats['this_month']['total_failure'] ?? 0 }}</p>
                            <p class="text-xs text-red-600/80 dark:text-red-400/80">Không thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE ol.ordles_lesson_starttime BETWEEN [month_start] AND [month_end] AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (533,558,560,...) AND ole.ole_acceptance_code != 12</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $acceptanceCodeStats['this_month']['success_rate'] ?? 0 }}%</p>
                            <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ thành công = (Code 12 / Tổng hoàn thành) × 100</code></span></span>
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/30 cursor-pointer hover:bg-orange-500/20 transition" @click="openPenalizedModal('this_month')">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $acceptanceCodeStats['this_month']['penalized_teachers'] ?? 0 }}</p>
                            <p class="text-xs text-orange-600/80 dark:text-orange-400/80">GV bị phạt
                                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">GV bị phạt</span><br>Số GV có mã lỗi: 1, 2, 3, 6, 14, 17<br>Tổng ca: {{ $acceptanceCodeStats['this_month']['penalized_sessions'] ?? 0 }}<br><em>Nhấn để xem chi tiết</em></span></span>
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
                                @foreach($acceptanceCodeStats['this_month']['codes'] ?? [] as $codeData)
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
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCodeModal = false"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl border border-light-border dark:border-zeus-border max-w-4xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="showCodeModal = false">
                
                <!-- Modal Header -->
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
                
                <!-- Modal Body -->
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
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Code</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Ca học</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                    <th class="px-3 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                    <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Kết quả</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                @foreach($acceptanceCodesList as $code => $info)
                                <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light {{ $info['is_success'] ? 'bg-green-500/5' : '' }}">
                                    <td class="px-3 py-2 font-mono font-bold {{ $info['is_success'] ? 'text-green-600 dark:text-green-400' : 'text-light-text dark:text-zeus-text' }}">{{ $code }}</td>
                                    <td class="px-3 py-2 text-light-text dark:text-zeus-text">{{ $info['session'] }}</td>
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs">{{ $info['teacher'] }}</td>
                                    <td class="px-3 py-2 text-light-text-muted dark:text-zeus-text-muted text-xs">{{ $info['student'] }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($info['is_success'])
                                            <span class="px-2 py-1 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-full">✓ Thành công</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-500/10 text-red-600 dark:text-red-400 rounded-full">✗ Không TC</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Modal Footer -->
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

    <!-- ===== VOUCHER (COUPON) STATISTICS ===== -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
            🎟️ Thống kê Voucher (Mã giảm giá)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_coupons</span>, <span class="tooltip-table">tbl_coupons_history</span>, <span class="tooltip-table">tbl_coupon_logs</span><br>Thống kê việc sử dụng voucher/coupon trong hệ thống</span></span>
        </h3>
        
        <!-- Voucher Statistics by Period -->
        <div x-data="{ activeVoucherTab: 'today' }">
            <h4 class="text-md font-medium text-light-text dark:text-zeus-text mb-3">📊 Thống kê theo kỳ</h4>
            
            <!-- Period Tabs -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button @click="activeVoucherTab = 'today'" :class="activeVoucherTab === 'today' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm nay</button>
                <button @click="activeVoucherTab = 'yesterday'" :class="activeVoucherTab === 'yesterday' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm qua</button>
                <button @click="activeVoucherTab = 'day_before'" :class="activeVoucherTab === 'day_before' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Hôm kia</button>
                <button @click="activeVoucherTab = 'week'" :class="activeVoucherTab === 'week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tuần này</button>
                <button @click="activeVoucherTab = 'last_week'" :class="activeVoucherTab === 'last_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tuần trước</button>
                <button @click="activeVoucherTab = 'month'" :class="activeVoucherTab === 'month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition">Tháng này</button>
            </div>
            
            <!-- Today Stats -->
            <div x-show="activeVoucherTab === 'today'">
                @if(isset($voucherStats['today']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['today'], 'period' => 'today', 'periodLabel' => 'Hôm nay'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            
            <!-- Yesterday Stats -->
            <div x-show="activeVoucherTab === 'yesterday'" style="display: none;">
                @if(isset($voucherStats['yesterday']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['yesterday'], 'period' => 'yesterday', 'periodLabel' => 'Hôm qua'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            
            <!-- Day Before Yesterday Stats (Hôm kia) -->
            <div x-show="activeVoucherTab === 'day_before'" style="display: none;">
                @if(isset($voucherStats['day_before_yesterday']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['day_before_yesterday'], 'period' => 'day_before', 'periodLabel' => 'Hôm kia'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            
            <!-- Week Stats -->
            <div x-show="activeVoucherTab === 'week'" style="display: none;">
                @if(isset($voucherStats['this_week']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['this_week'], 'period' => 'this_week', 'periodLabel' => 'Tuần này'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            
            <!-- Last Week Stats -->
            <div x-show="activeVoucherTab === 'last_week'" style="display: none;">
                @if(isset($voucherStats['last_week']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['last_week'], 'period' => 'last_week', 'periodLabel' => 'Tuần trước'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
            
            <!-- Month Stats -->
            <div x-show="activeVoucherTab === 'month'" style="display: none;">
                @if(isset($voucherStats['this_month']))
                    @include('dashboard.partials.voucher-stats-content', ['stats' => $voucherStats['this_month'], 'period' => 'this_month', 'periodLabel' => 'Tháng này'])
                @else
                    <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Không có dữ liệu</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Period-filtered Stats Cards (Dynamic) -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border shadow-sm relative" x-show="periodStats || loading" :class="{ 'content-loading': loading }">
        <h3 class="text-md font-semibold text-light-text dark:text-zeus-text mb-3">
            📊 Thống kê theo kỳ: <span class="text-zeus-accent" x-text="periodLabel"></span>
            <span x-show="loading" class="spinner-inline spinner-sm ml-2"></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="text-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="periodStats?.users?.new_users ?? 0"></p>
                <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Người dùng mới
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE DATE(user_registration) BETWEEN [start_date] AND [end_date]</code></span></span>
                </p>
            </div>
            <div class="text-center p-3 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-xl font-bold text-purple-600 dark:text-purple-400" x-text="periodStats?.users?.new_teachers ?? 0"></p>
                <p class="text-xs text-purple-600/80 dark:text-purple-400/80">GV mới
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_type = 1 AND DATE(user_registration) BETWEEN [start_date] AND [end_date]</code></span></span>
                </p>
            </div>
            <div class="text-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-xl font-bold text-green-600 dark:text-green-400" x-text="periodStats?.users?.new_learners ?? 0"></p>
                <p class="text-xs text-green-600/80 dark:text-green-400/80">HV mới
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_type = 2 AND DATE(user_registration) BETWEEN [start_date] AND [end_date]</code></span></span>
                </p>
            </div>
            @if(session('can_view_revenue'))
            <div class="text-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400" x-text="formatNumber(periodStats?.revenue?.total ?? 0)"></p>
                <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Doanh thu
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT SUM(order_final_amount) FROM tbl_orders WHERE order_status IN (1,2) AND DATE(order_date) BETWEEN [start_date] AND [end_date]</code></span></span>
                </p>
            </div>
            @endif
            <div class="text-center p-3 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-xl font-bold text-teal-600 dark:text-teal-400" x-text="periodStats?.lessons?.total ?? 0"></p>
                <p class="text-xs text-teal-600/80 dark:text-teal-400/80">Tổng bài học
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) BETWEEN [start_date] AND [end_date]</code></span></span>
                </p>
            </div>
            <div class="text-center p-3 bg-rose-500/5 dark:bg-rose-500/10 rounded-lg border border-rose-500/20">
                <p class="text-xl font-bold text-rose-600 dark:text-rose-400" x-text="(periodStats?.lessons?.completion_rate ?? 0) + '%'"></p>
                <p class="text-xs text-rose-600/80 dark:text-rose-400/80">Tỷ lệ hoàn thành
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ hoàn thành = (Bài học status=3 / Tổng bài học) × 100</code></span></span>
                </p>
            </div>
        </div>
        
        <!-- Period Login Stats -->
        <div class="mt-4 pt-4 border-t border-light-border dark:border-zeus-border">
            <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">🔐 Đăng nhập trong kỳ</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                    <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="periodStats?.logins?.total ?? 0"></p>
                    <p class="text-xs text-indigo-600/80 dark:text-indigo-400/80">Tổng đăng nhập
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_user_logins WHERE DATE(login_time) BETWEEN [start_date] AND [end_date]</code></span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                    <p class="text-xl font-bold text-cyan-600 dark:text-cyan-400" x-text="periodStats?.logins?.unique_users ?? 0"></p>
                    <p class="text-xs text-cyan-600/80 dark:text-cyan-400/80">Users duy nhất
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_user_logins WHERE DATE(login_time) BETWEEN [start_date] AND [end_date]</code></span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-sky-500/5 dark:bg-sky-500/10 rounded-lg border border-sky-500/20">
                    <p class="text-xl font-bold text-sky-600 dark:text-sky-400" x-text="periodStats?.logins?.teachers ?? 0"></p>
                    <p class="text-xs text-sky-600/80 dark:text-sky-400/80">Đăng nhập GV
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_user_logins ul JOIN tbl_users u ON ul.user_id = u.user_id WHERE u.user_type = 1 AND DATE(ul.login_time) BETWEEN [start_date] AND [end_date]</code></span></span>
                    </p>
                </div>
                <div class="text-center p-3 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="periodStats?.logins?.learners ?? 0"></p>
                    <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Đăng nhập HV
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_user_logins ul JOIN tbl_users u ON ul.user_id = u.user_id WHERE u.user_type = 2 AND DATE(ul.login_time) BETWEEN [start_date] AND [end_date]</code></span></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Teachers -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-blue-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                        Tổng Giáo viên
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_is_teacher = 1 AND user_deleted IS NULL</code></span></span>
                    </p>
                    <p class="text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($overview['users']['total_teachers'] ?? 0) }}</p>
                </div>
                <div class="bg-blue-500/10 dark:bg-blue-500/20 p-3 rounded-xl">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Đánh giá TB: <span class="text-amber-500">{{ $overview['teachers']['average_rating'] ?? 0 }}⭐</span></p>
        </div>

        <!-- Total Learners -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-green-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                        Tổng Học sinh
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE (user_is_teacher = 0 OR user_is_teacher IS NULL) AND user_deleted IS NULL</code></span></span>
                    </p>
                    <p class="text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($overview['users']['total_learners'] ?? 0) }}</p>
                </div>
                <div class="bg-green-500/10 dark:bg-green-500/20 p-3 rounded-xl">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Mới hôm nay: <span class="text-green-500">+{{ $overview['users']['new_today'] ?? 0 }}</span></p>
        </div>

        @if(session('can_view_revenue'))
        <!-- Revenue Today -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-amber-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                        Doanh thu Hôm nay
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT SUM(order_final_amount) FROM tbl_orders WHERE order_payment_status = 1 AND DATE(order_addedon) = CURDATE()</code></span></span>
                    </p>
                    <p class="text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($overview['revenue']['today'] ?? 0) }}</p>
                </div>
                <div class="bg-amber-500/10 dark:bg-amber-500/20 p-3 rounded-xl">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Tháng này: <span class="text-amber-500">{{ number_format($overview['revenue']['this_month'] ?? 0) }}</span></p>
        </div>
        @endif

        <!-- HIDDEN: Lessons Today block temporarily hidden per Phase 8 -->
        <!--
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-purple-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                        Ca học Hôm nay
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = CURDATE() AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                    </p>
                    <p class="text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ $overview['lessons_today']['total_sessions'] ?? 0 }}</p>
                </div>
                <div class="bg-purple-500/10 dark:bg-purple-500/20 p-3 rounded-xl">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">Hoàn thành: <span class="text-purple-500">{{ $overview['lessons_today']['completion_rate'] ?? 0 }}%</span></p>
        </div>
        -->
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🎯 Chuyển đổi Trial → Paid
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Trial: SELECT * FROM tbl_order_lessons WHERE ordles_type = 'trial'<br>Chuyển đổi: user hoàn thành trial AND có order khác</code></span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($conversionFunnel['total_trials'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Trial
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 'trial'</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($conversionFunnel['completed_trials'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Trial hoàn thành
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 'trial' AND ordles_status = 3</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $conversionFunnel['trial_completion_rate'] ?? 0 }}%</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Tỷ lệ hoàn thành
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ hoàn thành = (Trial hoàn thành / Tổng Trial) × 100</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($conversionFunnel['converted_users'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đã mua hàng
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE user_id IN (SELECT user_id FROM tbl_order_lessons WHERE ordles_type = 'trial') AND order_type != 'trial'</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $conversionFunnel['conversion_rate'] ?? 0 }}%</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Tỷ lệ chuyển đổi
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ chuyển đổi = (Đã mua hàng / Trial hoàn thành) × 100</code></span></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Trial Lessons Statistics Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🎓 Thống kê Trial Lessons
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_type = 1 (trial)<br>Thống kê chi tiết các buổi học thử</span></span>
        </h3>
        
        <!-- Trial Overview Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($trialStats['total'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Trial
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($trialStats['completed'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Hoàn thành
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_status = 3</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($trialStats['scheduled'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đã lên lịch
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_status = 2</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($trialStats['cancelled'] ?? 0) }}</p>
                <p class="text-sm text-red-600/80 dark:text-red-400/80">Đã hủy
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_status = 4</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-gray-500/5 dark:bg-gray-500/10 rounded-lg border border-gray-500/20">
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($trialStats['unscheduled'] ?? 0) }}</p>
                <p class="text-sm text-gray-600/80 dark:text-gray-400/80">Chưa lên lịch
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_status = 1</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $trialStats['completion_rate'] ?? 0 }}%</p>
                <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ HT
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ HT = (Hoàn thành / Tổng Trial) × 100</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-rose-500/5 dark:bg-rose-500/10 rounded-lg border border-rose-500/20">
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $trialStats['cancellation_rate'] ?? 0 }}%</p>
                <p class="text-sm text-rose-600/80 dark:text-rose-400/80">Tỷ lệ hủy
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ hủy = (Đã hủy / Tổng Trial) × 100</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $trialStats['avg_duration'] ?? 0 }}'</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Thời lượng TB
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT AVG(ordles_duration) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_status = 3</code></span></span>
                </p>
            </div>
        </div>

        <!-- Trial Time-based Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">📅 Hôm nay
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND DATE(ordles_start_datetime) = CURDATE()</code></span></span>
                </h4>
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-light-text dark:text-zeus-text">{{ $trialStats['today']['total'] ?? 0 }}</span>
                    <span class="text-sm text-green-500">{{ $trialStats['today']['completed'] ?? 0 }} HT ({{ $trialStats['today']['rate'] ?? 0 }}%)</span>
                </div>
            </div>
            <div class="p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">📆 Tuần này
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_start_datetime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)</code></span></span>
                </h4>
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-light-text dark:text-zeus-text">{{ $trialStats['this_week']['total'] ?? 0 }}</span>
                    <span class="text-sm text-green-500">{{ $trialStats['this_week']['completed'] ?? 0 }} HT ({{ $trialStats['this_week']['rate'] ?? 0 }}%)</span>
                </div>
            </div>
            <div class="p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">📅 Tháng này
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND MONTH(ordles_start_datetime) = MONTH(CURDATE())</code></span></span>
                </h4>
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-light-text dark:text-zeus-text">{{ $trialStats['this_month']['total'] ?? 0 }}</span>
                    <span class="text-sm text-green-500">{{ $trialStats['this_month']['completed'] ?? 0 }} HT ({{ $trialStats['this_month']['rate'] ?? 0 }}%)</span>
                </div>
            </div>
            <div class="p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">📊 30 ngày qua
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)</code></span></span>
                </h4>
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-light-text dark:text-zeus-text">{{ $trialStats['last_30_days']['total'] ?? 0 }}</span>
                    <span class="text-sm text-green-500">{{ $trialStats['last_30_days']['completed'] ?? 0 }} HT ({{ $trialStats['last_30_days']['rate'] ?? 0 }}%)</span>
                </div>
            </div>
        </div>

        <!-- Trial Conversion Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- All-time Conversion -->
            <div class="p-4 bg-gradient-to-r from-blue-500/5 to-teal-500/5 dark:from-blue-500/10 dark:to-teal-500/10 rounded-lg border border-blue-500/20">
                <h4 class="text-md font-semibold text-light-text dark:text-zeus-text mb-3">🎯 Chuyển đổi Trial → Paid (Tất cả)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE user_id IN (trial_users) AND order_type != 'trial'</code></span></span>
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center">
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($trialConversion['all_time']['trial_users'] ?? 0) }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Trial Users
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_order_lessons WHERE ordles_type = 1</code></span></span>
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($trialConversion['all_time']['converted_users'] ?? 0) }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đã mua hàng
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE user_id IN (trial_users) AND order_type != 'trial'</code></span></span>
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-teal-600 dark:text-teal-400">{{ $trialConversion['all_time']['conversion_rate'] ?? 0 }}%</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ CV
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ CV = (Đã mua hàng / Trial Users) × 100</code></span></span>
                        </p>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span class="text-sm text-purple-500">→ {{ number_format($trialConversion['all_time']['converted_to_regular'] ?? 0) }} mua Regular Lessons ({{ $trialConversion['all_time']['regular_conversion_rate'] ?? 0 }}%)</span>
                </div>
            </div>

            <!-- 30 Days Conversion -->
            <div class="p-4 bg-gradient-to-r from-amber-500/5 to-orange-500/5 dark:from-amber-500/10 dark:to-orange-500/10 rounded-lg border border-amber-500/20">
                <h4 class="text-md font-semibold text-light-text dark:text-zeus-text mb-3">📅 Chuyển đổi 30 ngày qua
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE order_created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)</code></span></span>
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center">
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($trialConversion['last_30_days']['trial_users'] ?? 0) }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Trial Users
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_order_lessons WHERE ordles_type = 1 AND ordles_created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)</code></span></span>
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($trialConversion['last_30_days']['converted_users'] ?? 0) }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đã mua hàng
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE user_id IN (trial_users_30d) AND order_type != 'trial'</code></span></span>
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $trialConversion['last_30_days']['conversion_rate'] ?? 0 }}%</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ CV
                            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ CV = (Đã mua hàng / Trial Users) × 100</code></span></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trial Lessons Charts & Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Trial Trend Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-2">
                📈 Xu hướng Trial Lessons (14 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>
                    Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>
                    Điều kiện: ordles_type = 1 (trial)<br><br>
                    <span class="tooltip-sql">SELECT DATE(ordles_lesson_starttime) as date,<br>
                    SUM(CASE WHEN ordles_status=2 THEN 1 ELSE 0 END) as scheduled,<br>
                    SUM(CASE WHEN ordles_status=3 THEN 1 ELSE 0 END) as completed,<br>
                    SUM(CASE WHEN ordles_status=4 THEN 1 ELSE 0 END) as cancelled<br>
                    FROM tbl_order_lessons WHERE ordles_type = 1<br>
                    GROUP BY DATE(ordles_lesson_starttime)</span>
                </span></span>
            </h3>
            <!-- Summary stats row -->
            <div class="flex flex-wrap gap-3 mb-4 text-xs">
                <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full">📅 Đã lên lịch: {{ array_sum($trialTrendChart['datasets']['scheduled']) }}</span>
                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">✅ Hoàn thành: {{ array_sum($trialTrendChart['datasets']['completed']) }}</span>
                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">❌ Đã hủy: {{ array_sum($trialTrendChart['datasets']['cancelled']) }}</span>
            </div>
            <div class="h-56">
                <canvas id="trialTrendChart"></canvas>
            </div>
        </div>

        <!-- Trial Status Pie Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-2">
                📊 Phân bố Trạng thái Trial
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>
                    Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>
                    Điều kiện: ordles_type = 1 (trial)<br><br>
                    <span class="tooltip-sql">SELECT ordles_status, COUNT(*) as count<br>
                    FROM tbl_order_lessons<br>
                    WHERE ordles_type = 1<br>
                    GROUP BY ordles_status</span>
                </span></span>
            </h3>
            @php
                $totalTrial = ($trialByStatus['unscheduled'] ?? 0) + ($trialByStatus['scheduled'] ?? 0) + ($trialByStatus['completed'] ?? 0) + ($trialByStatus['cancelled'] ?? 0);
                $completionRate = $totalTrial > 0 ? round(($trialByStatus['completed'] ?? 0) / $totalTrial * 100, 1) : 0;
            @endphp
            <!-- Summary stats with percentages -->
            <div class="grid grid-cols-2 gap-2 mb-4 text-xs">
                <div class="flex items-center gap-2 p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <span class="w-3 h-3 bg-gray-500 rounded-full"></span>
                    <span>Chưa lên lịch: <strong>{{ number_format($trialByStatus['unscheduled'] ?? 0) }}</strong></span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <span class="w-3 h-3 bg-amber-500 rounded-full"></span>
                    <span>Đã lên lịch: <strong>{{ number_format($trialByStatus['scheduled'] ?? 0) }}</strong></span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                    <span>Hoàn thành: <strong>{{ number_format($trialByStatus['completed'] ?? 0) }}</strong> ({{ $completionRate }}%)</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    <span>Đã hủy: <strong>{{ number_format($trialByStatus['cancelled'] ?? 0) }}</strong></span>
                </div>
            </div>
            <div class="h-44 flex items-center justify-center">
                <canvas id="trialStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Trial & Top Teachers by Trial -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Trial Lessons -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🕐 Trial Lessons Gần đây
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>ordles_type = 1 (trial)<br>Sắp xếp theo thời gian DESC</span></span>
            </h3>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @forelse($recentTrialLessons as $lesson)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $lesson['student_name'] }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">GV: {{ $lesson['teacher_name'] }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($lesson['status'] == 1) bg-gray-500/10 text-gray-600 dark:text-gray-400
                            @elseif($lesson['status'] == 2) bg-amber-500/10 text-amber-600 dark:text-amber-400
                            @elseif($lesson['status'] == 3) bg-green-500/10 text-green-600 dark:text-green-400
                            @else bg-red-500/10 text-red-600 dark:text-red-400
                            @endif">{{ $lesson['status_label'] }}</span>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted mt-1">{{ $lesson['start_time'] }}</p>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>

        <!-- Top Teachers by Trial -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🏆 Top GV dạy Trial nhiều nhất
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>GROUP BY ordles_teacher_id<br>COUNT(*) WHERE ordles_type = 1</span></span>
            </h3>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @forelse($topTeachersByTrial as $index => $teacher)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $teacher['name'] }}</p>
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['email'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-blue-500">{{ $teacher['total_trials'] }} trials</p>
                        <p class="text-xs text-green-500">{{ $teacher['completed_trials'] }} HT ({{ $teacher['completion_rate'] }}%)</p>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if(session('can_view_revenue'))
        <!-- Revenue Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📈 Doanh thu 30 ngày qua
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Điều kiện: order_payment_status = 1<br>SUM(order_total_amount) theo ngày</span></span>
            </h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        @endif

        <!-- User Registration Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm {{ !session('can_view_revenue') ? 'lg:col-span-2' : '' }}">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                👥 Đăng ký mới 30 ngày qua
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_users</span><br>Điều kiện: user_deleted IS NULL<br>COUNT theo user_created</span></span>
            </h3>
            <canvas id="userChart" height="200"></canvas>
        </div>
    </div>

    <!-- Top Performers & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Teachers -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🏆 Top Giáo viên
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_stats</span><br>Sắp xếp theo testat_lessons DESC<br>Kết hợp tbl_users để lấy tên</span></span>
            </h3>
            <div class="space-y-3">
                @forelse($topTeachers as $index => $teacher)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $teacher['name'] }}</p>
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['lessons'] }} bài • {{ $teacher['rating'] }}⭐</p>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>

        <!-- Top Learners -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                💎 Top Học viên
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Điều kiện: order_payment_status = 1<br>GROUP BY order_user_id, SUM total_spent</span></span>
            </h3>
            <div class="space-y-3">
                @forelse($topLearners as $index => $learner)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $learner['name'] }}</p>
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $learner['orders'] }} đơn • {{ number_format($learner['total_spent']) }}đ</p>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🕐 Đơn hàng Gần đây
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>Sắp xếp theo order_addedon DESC<br>LIMIT 10 đơn hàng mới nhất</span></span>
            </h3>
            <div class="space-y-3">
                @forelse($recentOrders as $order)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $order['user_name'] }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $order['type'] }} • {{ $order['date'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold {{ $order['payment_status'] == 1 ? 'text-green-500' : 'text-amber-500' }}">
                            {{ number_format($order['amount']) }}đ
                        </p>
                        <span class="text-xs {{ $order['payment_status'] == 1 ? 'text-green-500' : 'text-amber-500' }}">
                            {{ $order['payment_status'] == 1 ? '✓ Paid' : '⏳ Pending' }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order by Type -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📦 Đơn hàng theo Loại
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>GROUP BY order_type<br>Loại: Lesson(1), Subscription(2), Group(3), Package(4), v.v.</span></span>
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @foreach($ordersByType as $type => $count)
                <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">{{ $type }}</span>
                    <span class="font-semibold text-light-text dark:text-zeus-text">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Order Status -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📊 Trạng thái Đơn hàng
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>order_status: 1=Đang xử lý, 2=Hoàn thành, 3=Đã hủy<br>order_payment_status: 0=Chưa TT, 1=Đã TT</span></span>
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <span class="text-amber-600 dark:text-amber-400">🔄 Đang xử lý</span>
                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ number_format($ordersByStatus['in_process'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">✅ Hoàn thành</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($ordersByStatus['completed'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <span class="text-red-600 dark:text-red-400">❌ Đã hủy</span>
                    <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($ordersByStatus['cancelled'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-blue-600 dark:text-blue-400">💳 Đã thanh toán</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($ordersByStatus['paid'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                    <span class="text-orange-600 dark:text-orange-400">⏳ Chưa thanh toán</span>
                    <span class="font-semibold text-orange-600 dark:text-orange-400">{{ number_format($ordersByStatus['unpaid'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Issues & Ratings -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🎯 Vấn đề & Đánh giá
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Vấn đề: <span class="tooltip-table">tbl_reported_issues</span><br>Đánh giá: <span class="tooltip-table">tbl_rating_reviews</span></span></span>
            </h3>
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Báo cáo vấn đề</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="bg-amber-500/5 dark:bg-amber-500/10 p-2 rounded border border-amber-500/20">
                            <span class="text-amber-600 dark:text-amber-400">Đang xử lý: {{ $issues['in_progress'] ?? 0 }}</span>
                        </div>
                        <div class="bg-green-500/5 dark:bg-green-500/10 p-2 rounded border border-green-500/20">
                            <span class="text-green-600 dark:text-green-400">Đã giải quyết: {{ $issues['resolved'] ?? 0 }}</span>
                        </div>
                        <div class="bg-red-500/5 dark:bg-red-500/10 p-2 rounded border border-red-500/20">
                            <span class="text-red-600 dark:text-red-400">Escalated: {{ $issues['escalated'] ?? 0 }}</span>
                        </div>
                        <div class="bg-blue-500/5 dark:bg-blue-500/10 p-2 rounded border border-blue-500/20">
                            <span class="text-blue-600 dark:text-blue-400">Mới hôm nay: {{ $issues['new_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Đánh giá</h4>
                    <div class="flex items-center space-x-4">
                        <div class="text-2xl font-bold text-amber-500">{{ $ratings['average_rating'] ?? 0 }} ⭐</div>
                        <div class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                            {{ $ratings['approved_reviews'] ?? 0 }} đánh giá
                        </div>
                    </div>
                    <div class="text-sm text-orange-500 mt-1">
                        {{ $ratings['pending_reviews'] ?? 0 }} đang chờ duyệt
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Stats Detail -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            👤 Chi tiết Người dùng
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*), user_is_teacher, user_is_parent, user_is_affiliate FROM tbl_users GROUP BY user_is_teacher, user_is_parent, user_is_affiliate</code></span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-blue-500">{{ number_format($overview['users']['total_teachers'] ?? 0) }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Giáo viên
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_is_teacher = 1 AND user_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-green-500">{{ number_format($overview['users']['total_learners'] ?? 0) }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Học sinh
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE (user_is_teacher = 0 OR user_is_teacher IS NULL) AND user_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-purple-500">{{ number_format($overview['users']['total_parents'] ?? 0) }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Phụ huynh
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_is_parent = 1 AND user_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-orange-500">{{ number_format($overview['users']['total_affiliates'] ?? 0) }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Affiliate
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_is_affiliate = 1 AND user_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-blue-400">+{{ $overview['users']['new_today'] ?? 0 }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Mới hôm nay
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE DATE(user_registration) = CURDATE()</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-teal-500">+{{ $overview['users']['new_this_week'] ?? 0 }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tuần này
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_users WHERE user_registration >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-2xl font-bold text-pink-500">{{ $overview['users']['verified_rate'] ?? 0 }}%</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ xác thực
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ = (COUNT(user_verified=1) / COUNT(*)) × 100</code></span></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Phase 38: Students by Class Size Statistics -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            👥 Học viên theo Size lớp (SpeakWell)
            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Thống kê Size lớp (SpeakWell)</span><br>
Số lượng học viên SpeakWell phân theo size lớp học dựa trên dữ liệu từ tbl_group_classes.grpcls_total_seats.<br>
Chỉ tính các môn học SpeakWell (ordles_tlang_id thuộc danh sách 36 môn).<br><br>
<span class="tooltip-sql">SELECT class_size, COUNT(DISTINCT user_id)<br>
FROM tbl_order_lessons ol<br>
LEFT JOIN tbl_group_classes gc...<br>
WHERE ol.ordles_tlang_id IN (533, 558, ...)<br>
GROUP BY class_size</span>
</span></span>
        </h3>
        <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
            @php
                $classSizeData = $studentsByClassSize['by_size'] ?? [];
                $classSizeColors = [
                    '1:1' => ['bg' => 'bg-blue-500/10', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-500/30'],
                    '1:2' => ['bg' => 'bg-green-500/10', 'text' => 'text-green-600 dark:text-green-400', 'border' => 'border-green-500/30'],
                    '1:3' => ['bg' => 'bg-purple-500/10', 'text' => 'text-purple-600 dark:text-purple-400', 'border' => 'border-purple-500/30'],
                    '1:6' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-500/30'],
                    '1:8' => ['bg' => 'bg-pink-500/10', 'text' => 'text-pink-600 dark:text-pink-400', 'border' => 'border-pink-500/30'],
                    'Group' => ['bg' => 'bg-teal-500/10', 'text' => 'text-teal-600 dark:text-teal-400', 'border' => 'border-teal-500/30'],
                ];
                $classSizeLabels = [
                    '1:1' => 'Cá nhân (1:1)',
                    '1:2' => 'Đôi (1:2)',
                    '1:3' => 'Nhóm 3 (1:3)',
                    '1:6' => 'Nhóm vừa (1:6)',
                    '1:8' => 'Nhóm lớn (1:8)',
                    'Group' => 'Lớp học (11+)',
                ];
            @endphp
            @foreach($classSizeColors as $size => $colors)
                <div class="text-center p-4 {{ $colors['bg'] }} rounded-lg border {{ $colors['border'] }}">
                    <p class="text-2xl font-bold {{ $colors['text'] }}">{{ number_format($classSizeData[$size] ?? 0) }}</p>
                    <p class="text-sm {{ $colors['text'] }}/80">{{ $classSizeLabels[$size] }}
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">{{ $classSizeLabels[$size] }}</span><br>
Số học viên có ít nhất 1 buổi học với size lớp này.<br>
@php
    $seatDescription = match($size) {
        '1:1' => '1 chỗ ngồi (cá nhân)',
        '1:2' => '2 chỗ ngồi (đôi)',
        '1:3' => '3 chỗ ngồi (nhóm nhỏ)',
        '1:6' => '4-6 chỗ ngồi (nhóm vừa)',
        '1:8' => '7-10 chỗ ngồi (nhóm lớn)',
        'Group' => '11+ chỗ ngồi (lớp học)',
        default => $size,
    };
@endphp
<br><span class="tooltip-sql">grpcls_total_seats: {{ $seatDescription }}</span>
</span></span>
                    </p>
                </div>
            @endforeach
        </div>
        <div class="mt-4 p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg text-center">
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">
                Tổng học viên SpeakWell có buổi học: <span class="font-bold text-light-text dark:text-zeus-text">{{ number_format($studentsByClassSize['total_with_lessons'] ?? 0) }}</span>
                <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng học viên SpeakWell có buổi học</span><br>
Số học viên duy nhất đã có ít nhất 1 buổi học SpeakWell (scheduled hoặc completed).<br>
Chỉ tính các môn học SpeakWell (ordles_tlang_id thuộc danh sách 36 môn).<br>
Lưu ý: 1 học viên có thể tham gia nhiều size lớp khác nhau.
</span></span>
            </p>
        </div>
    </div>

    <!-- User Login Statistics Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🔐 Thống kê Đăng nhập
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_sales_lead_logs WHERE sllg_action = 'USER_LOGIN_SUCCESS'</code></span></span>
        </h3>
        
        <!-- Login Overview Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($loginStats['today'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Hôm nay
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_sales_lead_logs WHERE sllg_action = 'USER_LOGIN_SUCCESS' AND DATE(sllg_datetime) = CURDATE()</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($loginStats['this_week'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Tuần này
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_sales_lead_logs WHERE sllg_action = 'USER_LOGIN_SUCCESS' AND sllg_datetime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($loginStats['this_month'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Tháng này
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_sales_lead_logs WHERE sllg_action = 'USER_LOGIN_SUCCESS' AND MONTH(sllg_datetime) = MONTH(CURDATE())</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($loginStats['unique_users_today'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">User đăng nhập hôm nay
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT sllg_user_id) FROM tbl_sales_lead_logs WHERE sllg_action = 'USER_LOGIN_SUCCESS' AND DATE(sllg_datetime) = CURDATE()</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $loginStats['avg_logins_per_day'] ?? 0 }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">TB/ngày (30 ngày)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">TB/ngày = COUNT(logins_30_days) / 30</code></span></span>
                </p>
            </div>
        </div>

        <!-- Login by User Type -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Login by User Type Stats -->
            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                <h4 class="text-md font-semibold text-light-text dark:text-zeus-text mb-3">
                    👥 Đăng nhập theo Loại người dùng (30 ngày)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT u.user_is_teacher, COUNT(*) FROM tbl_sales_lead_logs sl JOIN tbl_users u ON sl.sllg_user_id = u.user_id WHERE sllg_action = 'USER_LOGIN_SUCCESS' GROUP BY u.user_is_teacher</code></span></span>
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600 dark:text-blue-400">👨‍🏫 Giáo viên</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($loginStatsByUserType['teachers']['logins'] ?? 0) }}</span>
                            <span class="text-xs text-blue-500/70 ml-1">({{ $loginStatsByUserType['teachers']['unique_users'] ?? 0 }} users)</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 dark:text-green-400">📚 Học viên</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($loginStatsByUserType['learners']['logins'] ?? 0) }}</span>
                            <span class="text-xs text-green-500/70 ml-1">({{ $loginStatsByUserType['learners']['unique_users'] ?? 0 }} users)</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-600 dark:text-purple-400">👪 Phụ huynh</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-purple-600 dark:text-purple-400">{{ number_format($loginStatsByUserType['parents']['logins'] ?? 0) }}</span>
                            <span class="text-xs text-purple-500/70 ml-1">({{ $loginStatsByUserType['parents']['unique_users'] ?? 0 }} users)</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                        <div class="flex items-center gap-2">
                            <span class="text-orange-600 dark:text-orange-400">🤝 Affiliate</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-orange-600 dark:text-orange-400">{{ number_format($loginStatsByUserType['affiliates']['logins'] ?? 0) }}</span>
                            <span class="text-xs text-orange-500/70 ml-1">({{ $loginStatsByUserType['affiliates']['unique_users'] ?? 0 }} users)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Trend Chart -->
            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                <h4 class="text-md font-semibold text-light-text dark:text-zeus-text mb-3">
                    📈 Xu hướng Đăng nhập (14 ngày)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>Tổng số lần đăng nhập và số user duy nhất theo ngày</span></span>
                </h4>
                <canvas id="loginTrendChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Logins & Top Active Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Logins -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🕐 Đăng nhập Gần đây
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>Sắp xếp theo sllg_occurred_at DESC<br>LIMIT 5 đăng nhập mới nhất</span></span>
            </h3>
            <div class="space-y-3">
                @forelse($recentLogins as $login)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full {{ $login['user_type'] == 'Giáo viên' ? 'bg-blue-500/20 text-blue-500' : 'bg-green-500/20 text-green-500' }}">
                            {{ $login['user_type'] == 'Giáo viên' ? '👨‍🏫' : '📚' }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $login['user_name'] }}</p>
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $login['user_email'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $login['login_time'] }}</p>
                        <span class="text-xs px-2 py-0.5 rounded {{ $login['user_type'] == 'Giáo viên' ? 'bg-blue-500/10 text-blue-500' : 'bg-green-500/10 text-green-500' }}">
                            {{ $login['user_type'] }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>

        <!-- Top Active Users -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🏆 User Hoạt động Nhiều nhất (30 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>GROUP BY sllg_user_id, COUNT(*) as login_count<br>ORDER BY login_count DESC</span></span>
            </h3>
            <div class="space-y-3">
                @forelse($topActiveUsers as $index => $user)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $user['name'] }}</p>
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $user['email'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-teal-500">{{ number_format($user['login_count']) }} lần</p>
                        <span class="text-xs px-2 py-0.5 rounded {{ $user['user_type'] == 'Giáo viên' ? 'bg-blue-500/10 text-blue-500' : 'bg-green-500/10 text-green-500' }}">
                            {{ $user['user_type'] }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Login Analytics: Hour, Day of Week, Source -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Login by Hour -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                ⏰ Đăng nhập theo Giờ (GMT+7)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>GROUP BY HOUR (GMT+7)<br>30 ngày gần nhất</span></span>
            </h3>
            <canvas id="loginByHourChart" height="200"></canvas>
        </div>

        <!-- Login by Day of Week -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📅 Đăng nhập theo Ngày trong tuần
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>GROUP BY DAYOFWEEK(sllg_occurred_at)<br>30 ngày gần nhất</span></span>
            </h3>
            <canvas id="loginByDayOfWeekChart" height="200"></canvas>
        </div>

        <!-- Login by Source -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📱 Đăng nhập theo Nguồn
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_sales_lead_logs</span><br>GROUP BY sllg_source<br>30 ngày gần nhất</span></span>
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($loginsBySource as $source => $count)
                <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <span class="text-light-text dark:text-zeus-text">{{ $source ?: 'Không xác định' }}</span>
                    <span class="font-semibold text-teal-600 dark:text-teal-400">{{ number_format($count) }}</span>
                </div>
                @empty
                <p class="text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Program/Curriculum Statistics Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📚 Thống kê theo Chương trình (Program/Curriculum)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_program</span>, <span class="tooltip-table">tbl_program_user</span>, <span class="tooltip-table">tbl_curriculum_session</span><br>Thống kê học viên đăng ký và tiến độ theo từng chương trình</span></span>
        </h3>
        
        <!-- Program Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $programStatsSummary['total_programs'] ?? 0 }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Program
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_program WHERE program_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $programStatsSummary['published_programs'] ?? 0 }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Published
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_program WHERE program_status = 'published' AND program_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $programStatsSummary['parent_programs'] ?? 0 }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Nhóm chính
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_program WHERE program_parent_id IS NULL AND program_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $programStatsSummary['child_programs'] ?? 0 }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Chương trình con
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_program WHERE program_parent_id IS NOT NULL AND program_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ number_format($programStatsSummary['total_enrollments'] ?? 0) }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Tổng đăng ký
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_program_user WHERE pu_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-rose-500/5 dark:bg-rose-500/10 rounded-lg border border-rose-500/20">
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($programStatsSummary['unique_learners'] ?? 0) }}</p>
                <p class="text-sm text-rose-600/80 dark:text-rose-400/80">Học viên
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT pu_user_id) FROM tbl_program_user WHERE pu_role = 'learner' AND pu_deleted IS NULL</code></span></span>
                </p>
            </div>
            <div class="text-center p-4 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($programStatsSummary['unique_teachers'] ?? 0) }}</p>
                <p class="text-sm text-indigo-600/80 dark:text-indigo-400/80">Giáo viên
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(DISTINCT pu_user_id) FROM tbl_program_user WHERE pu_role = 'teacher' AND pu_deleted IS NULL</code></span></span>
                </p>
            </div>
        </div>

        <!-- Program Category Stats Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-4 font-semibold text-light-text dark:text-zeus-text">Chương trình</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text hidden sm:table-cell">Trạng thái</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text">CT con</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text">Đăng ký</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text hidden md:table-cell">Học viên</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text hidden md:table-cell">Giáo viên</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text hidden lg:table-cell">Buổi học</th>
                        <th class="text-center py-3 px-2 font-semibold text-light-text dark:text-zeus-text">Hoàn thành</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programCategoryStats as $program)
                    <tr class="border-b border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">📘</span>
                                <div>
                                    <p class="font-medium text-light-text dark:text-zeus-text">{{ $program['title'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-center py-3 px-2 hidden sm:table-cell">
                            <span class="px-2 py-1 text-xs rounded-full {{ $program['status'] == 'published' ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-gray-500/10 text-gray-600 dark:text-gray-400' }}">
                                {{ $program['status'] == 'published' ? 'Published' : ucfirst($program['status']) }}
                            </span>
                        </td>
                        <td class="text-center py-3 px-2">
                            <span class="text-purple-600 dark:text-purple-400 font-medium">{{ $program['child_count'] }}</span>
                        </td>
                        <td class="text-center py-3 px-2">
                            <span class="text-teal-600 dark:text-teal-400 font-bold">{{ number_format($program['enrollments']) }}</span>
                        </td>
                        <td class="text-center py-3 px-2 hidden md:table-cell">
                            <span class="text-light-text dark:text-zeus-text">{{ number_format($program['unique_learners']) }}</span>
                        </td>
                        <td class="text-center py-3 px-2 hidden md:table-cell">
                            <span class="text-light-text dark:text-zeus-text">{{ number_format($program['unique_teachers']) }}</span>
                        </td>
                        <td class="text-center py-3 px-2 hidden lg:table-cell">
                            <span class="text-light-text-muted dark:text-zeus-text-muted">
                                {{ number_format($program['sessions_completed']) }}/{{ number_format($program['sessions_total']) }}
                            </span>
                        </td>
                        <td class="text-center py-3 px-2">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $program['completion_rate'] >= 80 ? 'bg-green-500' : ($program['completion_rate'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" 
                                         style="width: {{ min($program['completion_rate'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $program['completion_rate'] >= 80 ? 'text-green-500' : ($program['completion_rate'] >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                                    {{ $program['completion_rate'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-light-text-muted dark:text-zeus-text-muted">
                            Chưa có dữ liệu chương trình
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Top Programs by Enrollment -->
        @if(!empty($programStatsSummary['top_programs']))
        <div class="mt-6 pt-6 border-t border-light-border dark:border-zeus-border">
            <h4 class="text-md font-semibold text-light-text dark:text-zeus-text mb-4">🏆 Top 5 Chương trình có nhiều đăng ký nhất
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT p.program_title, COUNT(*) as enrollments FROM tbl_program_user pu JOIN tbl_program p ON pu.pu_program_id = p.program_id GROUP BY pu_program_id ORDER BY enrollments DESC LIMIT 5</code></span></span>
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($programStatsSummary['top_programs'] as $index => $program)
                <div class="flex items-center gap-3 p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <span class="w-8 h-8 flex items-center justify-center text-lg font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text truncate">{{ $program['title'] }}</p>
                        <p class="text-xs text-teal-600 dark:text-teal-400 font-bold">{{ number_format($program['enrollments']) }} đăng ký</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    {{-- End of session stats content --}}
