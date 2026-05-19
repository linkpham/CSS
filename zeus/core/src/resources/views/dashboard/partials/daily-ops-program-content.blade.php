    <!-- ===== SESSION SUCCESS/FAILURE STATS (PRIORITIZED) ===== -->
    <div class="bg-gradient-to-r from-blue-500/5 via-green-500/5 to-red-500/5 dark:from-blue-500/10 dark:via-green-500/10 dark:to-red-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-3 md:mb-4 flex items-center gap-2 flex-wrap">
            📊 Thống kê Ca học (Hôm nay)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*), ordles_status FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = CURDATE() AND ordles_tlang_id IN (533,558,560,...) GROUP BY ordles_status</code></span></span>
        </h3>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-4">
            <div class="text-center p-2 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-lg md:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $sessionStats['today']['total'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">Tổng ca học
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE DATE(ordles_lesson_starttime) = CURDATE() AND ordles_tlang_id IN (533,558,560,...)</code></span></span>
                </p>
            </div>
            <div class="text-center p-2 md:p-4 bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-lg md:text-2xl font-bold text-green-600 dark:text-green-400">{{ $sessionStats['today']['success']['count'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-green-600/80 dark:text-green-400/80">✅ Thành công
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND ole.ole_acceptance_code = 12</code></span></span>
                </p>
            </div>
            <div class="text-center p-2 md:p-4 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                <p class="text-lg md:text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $sessionStats['today']['success']['rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ TC
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Tỷ lệ TC = (Thành công / Tổng ca học) × 100</code></span></span>
                </p>
            </div>
            <div class="text-center p-2 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-lg md:text-2xl font-bold text-red-600 dark:text-red-400">{{ $sessionStats['today']['failure']['count'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">❌ Không TC
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_status = 3 AND ole.ole_acceptance_code != 12</code></span></span>
                </p>
            </div>
            <div class="text-center p-2 md:p-4 bg-amber-500/10 rounded-lg border border-amber-500/30">
                <p class="text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $sessionStats['today']['failure']['breakdown']['cancelled'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-amber-600/80 dark:text-amber-400/80">Đã hủy
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_tlang_id IN (...) AND ordles_status = 4 AND DATE(ordles_lesson_starttime) = CURDATE()</code></span></span>
                </p>
            </div>
            <div class="text-center p-2 md:p-4 bg-orange-500/10 rounded-lg border border-orange-500/30">
                <p class="text-lg md:text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $sessionStats['today']['failure']['breakdown']['no_show']['total'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-orange-600/80 dark:text-orange-400/80">No-show
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(*) FROM tbl_order_lessons ol JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND (ole.ole_teacher_first_join IS NULL OR ole.ole_student_first_join IS NULL)</code></span></span>
                </p>
            </div>
        </div>
        
        <!-- No-show breakdown -->
        @if(($sessionStats['today']['failure']['breakdown']['no_show']['total'] ?? 0) > 0)
        <div class="mt-3 p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
            <p class="text-xs font-medium text-orange-600 dark:text-orange-400 mb-2">Chi tiết No-show:</p>
            <div class="flex flex-wrap gap-4 text-xs">
                <span class="text-orange-600/80 dark:text-orange-400/80">GV không tham gia: <strong>{{ $sessionStats['today']['failure']['breakdown']['no_show']['teacher_only'] ?? 0 }}</strong></span>
                <span class="text-orange-600/80 dark:text-orange-400/80">HV không tham gia: <strong>{{ $sessionStats['today']['failure']['breakdown']['no_show']['student_only'] ?? 0 }}</strong></span>
                <span class="text-orange-600/80 dark:text-orange-400/80">Cả hai không tham gia: <strong>{{ $sessionStats['today']['failure']['breakdown']['no_show']['both'] ?? 0 }}</strong></span>
            </div>
        </div>
        @endif
    </div>

    <!-- ===== PHASE 98: CANCELLATION STATS FROM SESSION LOGS ===== -->
    <div class="bg-gradient-to-r from-red-500/5 via-amber-500/5 to-orange-500/5 dark:from-red-500/10 dark:via-amber-500/10 dark:to-orange-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="cancellationStatsBlock()"
         x-init="init()">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-3 md:mb-4 flex items-center gap-2 flex-wrap">
            🚫 Tình trạng hủy Ca học
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query — Lớp 1:1</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) FROM tbl_order_lessons ol INNER JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id AND sl.sesslog_record_type=1 WHERE ol.ordles_status = 4 AND sl.sesslog_changed_status = 4 AND ol.ordles_tlang_id IN (533,389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY) AND ol.ordles_lesson_starttime BETWEEN [start] AND [end] GROUP BY sl.sesslog_user_type</code><br><br><span class="tooltip-label">SQL Query — Lớp 1:2</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id) FROM tbl_group_classes gc INNER JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id AND sl.sesslog_record_type=2 WHERE gc.grpcls_status = 3 AND sl.sesslog_changed_status = 3 AND gc.grpcls_tlang_id IN (533,389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586) AND sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY) AND gc.grpcls_start_datetime BETWEEN [start] AND [end] GROUP BY sl.sesslog_user_type</code></span></span>
        </h3>

        <!-- Period Tabs -->
        <div class="relative mb-3 md:mb-4">
            <div class="tabs-container">
                <button @click="selectPeriod('today')" :class="activePeriod === 'today' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm nay</button>
                <button @click="selectPeriod('yesterday')" :class="activePeriod === 'yesterday' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm qua</button>
                <button @click="selectPeriod('day_before')" :class="activePeriod === 'day_before' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Hôm kia</button>
                <button @click="selectPeriod('this_week')" :class="activePeriod === 'this_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần này</button>
                <button @click="selectPeriod('last_week')" :class="activePeriod === 'last_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tuần trước</button>
                <button @click="selectPeriod('this_month')" :class="activePeriod === 'this_month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition">Tháng này</button>
                <!-- Chọn Tuần -->
                <button @click="showWeekPicker = !showWeekPicker; showMonthPicker = false; showDatePicker = false" :class="activePeriod === 'pick_week' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1">
                    Chọn Tuần
                    <span x-show="pickedWeekLabel" x-text="'(' + pickedWeekLabel + ')'" class="text-xs"></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <!-- Chọn Tháng -->
                <button @click="showMonthPicker = !showMonthPicker; showWeekPicker = false; showDatePicker = false" :class="activePeriod === 'pick_month' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1">
                    Chọn Tháng
                    <span x-show="pickedMonthLabel" x-text="'(' + pickedMonthLabel + ')'" class="text-xs"></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <!-- Tùy chọn -->
                <button @click="showDatePicker = !showDatePicker; showWeekPicker = false; showMonthPicker = false" :class="activePeriod === 'custom' ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted'" class="tab-button font-medium transition flex items-center gap-1">
                    📅 Tùy chọn
                    <span x-show="customDateFormatted" x-text="'(' + customDateFormatted + ')'" class="text-xs"></span>
                </button>
            </div>

            <!-- Week Picker Dropdown -->
            <div x-show="showWeekPicker" @click.away="showWeekPicker = false" @click.stop x-cloak
                 class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                 style="min-width: 280px; max-height: 350px; overflow-y: auto;">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Chọn tuần:</label>
                <div class="space-y-1">
                    <template x-for="w in availableWeeks" :key="'cancel-week-' + w.weekNum">
                        <button @click="selectWeek(w)" 
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition flex items-center justify-between"
                                :class="pickedWeekNum === w.weekNum ? 'bg-zeus-accent text-white' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light text-light-text dark:text-zeus-text'">
                            <span x-text="'Tuần ' + w.weekNum"></span>
                            <span class="text-xs opacity-75" x-text="w.rangeLabel"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Month Picker Dropdown -->
            <div x-show="showMonthPicker" @click.away="showMonthPicker = false" @click.stop x-cloak
                 class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                 style="min-width: 220px; max-height: 350px; overflow-y: auto;">
                <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Chọn tháng:</label>
                <div class="space-y-1">
                    <template x-for="m in availableMonths" :key="'cancel-month-' + m.month">
                        <button @click="selectMonth(m)" 
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition flex items-center justify-between"
                                :class="pickedMonthNum === m.month ? 'bg-zeus-accent text-white' : 'hover:bg-light-card-alt dark:hover:bg-zeus-card-light text-light-text dark:text-zeus-text'">
                            <span x-text="'Tháng ' + m.month"></span>
                            <span class="text-xs opacity-75" x-text="m.rangeLabel"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Date Range Picker Dropdown -->
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
                <p x-show="dateError" class="text-xs text-red-500 mt-1" x-text="dateError"></p>
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

        <!-- Loading State -->
        <template x-if="loading">
            <div class="flex items-center justify-center py-8">
                <span class="spinner-inline"></span>
                <span class="ml-2 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải dữ liệu hủy ca học...</span>
            </div>
        </template>

        <!-- Error State -->
        <template x-if="!loading && errorMsg">
            <div class="text-center py-8">
                <p class="text-red-500" x-text="errorMsg"></p>
            </div>
        </template>

        <!-- Stats Display -->
        <template x-if="!loading && !errorMsg && stats">
            <div>
                <!-- Combined Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 md:gap-4 mb-4">
                    <div class="text-center p-2 md:p-4 bg-red-500/10 rounded-lg border border-red-500/30">
                        <p class="text-lg md:text-2xl font-bold text-red-600 dark:text-red-400" x-text="stats.total || 0"></p>
                        <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">🚫 Tổng hủy</p>
                    </div>
                    <div class="text-center p-2 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <p class="text-lg md:text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="stats.by_student || 0"></p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">👨‍🎓 Hủy từ HS</p>
                    </div>
                    <div class="text-center p-2 md:p-4 bg-amber-500/10 rounded-lg border border-amber-500/30">
                        <p class="text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="stats.by_teacher || 0"></p>
                        <p class="text-xs md:text-sm text-amber-600/80 dark:text-amber-400/80">👩‍🏫 Hủy từ GV</p>
                    </div>
                    <div class="text-center p-2 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <p class="text-lg md:text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="stats.by_admin || 0"></p>
                        <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🖥️ Hủy từ Admin</p>
                    </div>
                </div>

                <!-- Phase 223: Breakdown by class type: 1:1 and 1:2 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <!-- 1:1 Breakdown -->
                    <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                        <p class="text-xs font-semibold text-light-text dark:text-zeus-text mb-2">👤 Lớp 1:1</p>
                        <div class="grid grid-cols-4 gap-1.5">
                            <div class="text-center p-1.5 bg-red-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-red-600 dark:text-red-400" x-text="stats.one_on_one?.total || 0"></p>
                                <p class="text-[10px] text-red-600/70 dark:text-red-400/70">Tổng</p>
                            </div>
                            <div class="text-center p-1.5 bg-blue-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-blue-600 dark:text-blue-400" x-text="stats.one_on_one?.by_student || 0"></p>
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-400/70">HS</p>
                            </div>
                            <div class="text-center p-1.5 bg-amber-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-amber-600 dark:text-amber-400" x-text="stats.one_on_one?.by_teacher || 0"></p>
                                <p class="text-[10px] text-amber-600/70 dark:text-amber-400/70">GV</p>
                            </div>
                            <div class="text-center p-1.5 bg-purple-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-purple-600 dark:text-purple-400" x-text="stats.one_on_one?.by_admin || 0"></p>
                                <p class="text-[10px] text-purple-600/70 dark:text-purple-400/70">Admin</p>
                            </div>
                        </div>
                    </div>
                    <!-- 1:2 Breakdown -->
                    <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                        <p class="text-xs font-semibold text-light-text dark:text-zeus-text mb-2">👥 Lớp 1:2</p>
                        <div class="grid grid-cols-4 gap-1.5">
                            <div class="text-center p-1.5 bg-red-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-red-600 dark:text-red-400" x-text="stats.one_on_two?.total || 0"></p>
                                <p class="text-[10px] text-red-600/70 dark:text-red-400/70">Tổng</p>
                            </div>
                            <div class="text-center p-1.5 bg-blue-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-blue-600 dark:text-blue-400" x-text="stats.one_on_two?.by_student || 0"></p>
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-400/70">HS</p>
                            </div>
                            <div class="text-center p-1.5 bg-amber-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-amber-600 dark:text-amber-400" x-text="stats.one_on_two?.by_teacher || 0"></p>
                                <p class="text-[10px] text-amber-600/70 dark:text-amber-400/70">GV</p>
                            </div>
                            <div class="text-center p-1.5 bg-purple-500/10 rounded-lg">
                                <p class="text-sm md:text-lg font-bold text-purple-600 dark:text-purple-400" x-text="stats.one_on_two?.by_admin || 0"></p>
                                <p class="text-[10px] text-purple-600/70 dark:text-purple-400/70">Admin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phase 99: Detail Table with Filters, Search & Pagination -->
                <div>
                    <div class="flex flex-col gap-3 mb-3">
                        <!-- Header row: title + export -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                                📋 Chi tiết hủy ca học
                                <span class="text-xs font-normal text-light-text-muted dark:text-zeus-text-muted" x-text="'(' + (pagination?.total ?? 0) + ' bản ghi)'"></span>
                            </h4>
                            <button @click="exportToExcel()" class="self-start sm:self-auto px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1">
                                📥 Xuất Excel
                            </button>
                        </div>

                        <!-- Filter buttons + Search -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <!-- User type filter tabs -->
                            <div class="flex flex-wrap gap-1">
                                <button @click="setUserTypeFilter(null)" 
                                        :class="detailFilter === null ? 'bg-zeus-accent text-white' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        class="px-3 py-1.5 text-xs rounded-lg font-medium transition">
                                    Tất cả <span class="opacity-75" x-text="'(' + (stats.total || 0) + ')'"></span>
                                </button>
                                <button @click="setUserTypeFilter(2)" 
                                        :class="detailFilter === 2 ? 'bg-amber-500 text-white' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20'"
                                        class="px-3 py-1.5 text-xs rounded-lg font-medium transition">
                                    👩‍🏫 Giáo viên <span class="opacity-75" x-text="'(' + (stats.by_teacher || 0) + ')'"></span>
                                </button>
                                <button @click="setUserTypeFilter(1)" 
                                        :class="detailFilter === 1 ? 'bg-blue-500 text-white' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-500/20'"
                                        class="px-3 py-1.5 text-xs rounded-lg font-medium transition">
                                    👨‍🎓 Học viên <span class="opacity-75" x-text="'(' + (stats.by_student || 0) + ')'"></span>
                                </button>
                                <button @click="setUserTypeFilter(3)" 
                                        :class="detailFilter === 3 ? 'bg-purple-500 text-white' : 'bg-purple-500/10 text-purple-600 dark:text-purple-400 hover:bg-purple-500/20'"
                                        class="px-3 py-1.5 text-xs rounded-lg font-medium transition">
                                    🖥️ Admin <span class="opacity-75" x-text="'(' + (stats.by_admin || 0) + ')'"></span>
                                </button>
                            </div>

                            <!-- Search box -->
                            <div class="relative flex-1 max-w-xs">
                                <input type="text" 
                                       x-model="searchQuery" 
                                       @input.debounce.400ms="searchDetails()"
                                       @keydown.escape="searchQuery = ''; searchDetails()"
                                       placeholder="Tìm tên GV, HV..." 
                                       class="w-full pl-8 pr-8 py-1.5 text-xs bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent placeholder:text-light-text-muted dark:placeholder:text-zeus-text-muted">
                                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <button x-show="searchQuery" @click="searchQuery = ''; searchDetails()" 
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Details Loading Spinner -->
                    <template x-if="detailsLoading">
                        <div class="flex items-center justify-center py-6">
                            <span class="spinner-inline"></span>
                            <span class="ml-2 text-xs text-light-text-muted dark:text-zeus-text-muted">Đang tải danh sách...</span>
                        </div>
                    </template>

                    <!-- Details Table -->
                    <template x-if="!detailsLoading && stats.details && stats.details.length > 0">
                        <div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="bg-light-card-alt dark:bg-zeus-card-light">
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">#</th>
                                            <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Loại</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Thời gian hủy</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Ca học</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Giáo viên</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Học viên</th>
                                            <th class="px-2 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Người hủy</th>
                                            <th class="px-2 py-2 text-left text-light-text-muted dark:text-zeus-text-muted font-medium">Lý do</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-light-border dark:divide-zeus-border">
                                        <template x-for="(item, index) in stats.details" :key="item.sesslog_id">
                                            <tr class="hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                                                <td class="px-2 py-2 text-light-text-muted dark:text-zeus-text-muted" x-text="((pagination?.current_page || 1) - 1) * (pagination?.per_page || 50) + index + 1"></td>
                                                <td class="px-2 py-2 text-center">
                                                    <span class="px-1.5 py-0.5 text-[10px] rounded-full font-medium whitespace-nowrap"
                                                          :class="item.class_type === '1:1' ? 'bg-cyan-500/20 text-cyan-600 dark:text-cyan-400' : 'bg-teal-500/20 text-teal-600 dark:text-teal-400'"
                                                          x-text="item.class_type || '1:1'"></span>
                                                </td>
                                                <td class="px-2 py-2 text-light-text dark:text-zeus-text font-mono whitespace-nowrap" x-text="item.cancelled_at"></td>
                                                <td class="px-2 py-2 text-light-text dark:text-zeus-text whitespace-nowrap" x-text="item.lesson_time"></td>
                                                <td class="px-2 py-2">
                                                    <p class="text-light-text dark:text-zeus-text font-medium" x-text="item.teacher_name"></p>
                                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="item.teacher_email"></p>
                                                </td>
                                                <td class="px-2 py-2">
                                                    <p class="text-light-text dark:text-zeus-text font-medium" x-text="item.student_name"></p>
                                                    <p class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="item.student_email"></p>
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <span class="px-2 py-0.5 text-[10px] rounded-full font-medium whitespace-nowrap"
                                                          :class="{
                                                              'bg-blue-500/20 text-blue-600 dark:text-blue-400': item.user_type === 1,
                                                              'bg-amber-500/20 text-amber-600 dark:text-amber-400': item.user_type === 2,
                                                              'bg-purple-500/20 text-purple-600 dark:text-purple-400': item.user_type === 3,
                                                          }"
                                                          x-text="item.user_type_label"></span>
                                                </td>
                                                <td class="px-2 py-2 text-light-text dark:text-zeus-text max-w-[200px]">
                                                    <p class="truncate" x-text="item.comment || '—'" :title="item.comment"></p>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Controls -->
                            <template x-if="pagination && pagination.last_page > 1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-3 pt-3 border-t border-light-border dark:border-zeus-border">
                                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                                        Hiển thị <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> / <span x-text="pagination.total"></span> bản ghi
                                    </p>
                                    <div class="flex items-center gap-1">
                                        <!-- First page -->
                                        <button @click="goToPage(1)" :disabled="pagination.current_page === 1"
                                                class="px-2 py-1 text-xs rounded-lg transition disabled:opacity-30 disabled:cursor-not-allowed bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700">
                                            «
                                        </button>
                                        <!-- Previous page -->
                                        <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                                                class="px-2 py-1 text-xs rounded-lg transition disabled:opacity-30 disabled:cursor-not-allowed bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700">
                                            ‹
                                        </button>
                                        <!-- Page numbers -->
                                        <template x-for="p in paginationPages()" :key="'page-' + p">
                                            <button @click="p !== '...' && goToPage(p)" 
                                                    :disabled="p === '...'"
                                                    :class="p === pagination.current_page ? 'bg-zeus-accent text-white' : (p === '...' ? 'cursor-default bg-transparent text-light-text-muted dark:text-zeus-text-muted' : 'bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700')"
                                                    class="px-2.5 py-1 text-xs rounded-lg transition min-w-[28px] text-center">
                                                <span x-text="p"></span>
                                            </button>
                                        </template>
                                        <!-- Next page -->
                                        <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                                                class="px-2 py-1 text-xs rounded-lg transition disabled:opacity-30 disabled:cursor-not-allowed bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700">
                                            ›
                                        </button>
                                        <!-- Last page -->
                                        <button @click="goToPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page"
                                                class="px-2 py-1 text-xs rounded-lg transition disabled:opacity-30 disabled:cursor-not-allowed bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700">
                                            »
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!detailsLoading && stats.details && stats.details.length === 0">
                        <div class="text-center py-6 text-light-text-muted dark:text-zeus-text-muted">
                            <template x-if="searchQuery || detailFilter !== null">
                                <span>🔍 Không tìm thấy kết quả phù hợp</span>
                            </template>
                            <template x-if="!searchQuery && detailFilter === null">
                                <span>✅ Không có ca học nào bị hủy trong khoảng thời gian này</span>
                            </template>
                        </div>
                    </template>

                    <!-- No data at all -->
                    <template x-if="!detailsLoading && (!stats.details || stats.total === 0) && !searchQuery && detailFilter === null">
                        <div class="text-center py-6 text-light-text-muted dark:text-zeus-text-muted">
                            ✅ Không có ca học nào bị hủy trong khoảng thời gian này
                        </div>
                    </template>
                </div>
            </div>
        </template>
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
        
        <!-- Phase 62/77/224: Teacher Country Weekly Summary (Scheduled) with date range filter -->
        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 md:p-4 border border-light-border dark:border-zeus-border mt-4"
             x-data="teacherCountryWeekly()"
             x-init="init()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text flex items-center gap-2 flex-wrap">
                    🌍 Tổng số ca Scheduled
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT teacher_country, YEARWEEK(starttime_utc7, 3), COUNT(*) FROM tbl_order_lessons ol JOIN tbl_orders o ON o.order_id = ol.ordles_order_id WHERE ordles_status IN (2,3,4) AND starttime >= YEAR-01-01 AND o.order_status=2 AND o.order_payment_status=1 GROUP BY country, week</code></span></span>
                    <!-- Phase 224: Active filter badge -->
                    <template x-if="filterApplied">
                        <span class="px-2 py-0.5 text-[10px] bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-full font-normal" x-text="filterLabel"></span>
                    </template>
                </h4>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Phase 224: Date range filter button -->
                    <div class="relative">
                        <button @click="showFilterDatePicker = !showFilterDatePicker" 
                                :class="filterApplied ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-light-card dark:bg-zeus-card text-light-text-muted dark:text-zeus-text-muted hover:bg-gray-200 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 text-xs rounded-lg transition inline-flex items-center gap-1 border border-light-border dark:border-zeus-border">
                            📅 Lọc ngày
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <!-- Date Range Picker Dropdown -->
                        <div x-show="showFilterDatePicker" @click.away="showFilterDatePicker = false" @click.stop x-cloak
                             class="absolute left-auto right-0 sm:left-0 sm:right-auto mt-2 p-3 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border z-50"
                             style="min-width: 260px;">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Từ ngày:</label>
                                    <input type="date" x-model="filterFromDate" 
                                           @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                           autocomplete="off" data-lpignore="true" data-form-type="other"
                                           class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent"
                                           :max="filterMaxDate">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Đến ngày:</label>
                                    <input type="date" x-model="filterToDate" 
                                           @keydown.stop @input.stop @focus.stop @click.stop @change.stop
                                           autocomplete="off" data-lpignore="true" data-form-type="other"
                                           class="w-full px-3 py-2 text-sm bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent focus:border-transparent"
                                           :max="filterMaxDate">
                                </div>
                            </div>
                            <p x-show="filterDateError" class="text-xs text-red-500 mt-1" x-text="filterDateError"></p>
                            <div class="flex gap-2 mt-3">
                                <button @click="showFilterDatePicker = false" 
                                        class="flex-1 px-3 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                    Hủy
                                </button>
                                <button @click="applyDateFilter()" 
                                        :disabled="!filterFromDate || !filterToDate"
                                        class="flex-1 px-3 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent/90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    Xác nhận
                                </button>
                            </div>
                            <!-- Clear filter button -->
                            <template x-if="filterApplied">
                                <button @click="clearDateFilter()" 
                                        class="w-full mt-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 bg-red-500/10 rounded-lg hover:bg-red-500/20 transition">
                                    ✕ Bỏ lọc (xem toàn bộ năm)
                                </button>
                            </template>
                        </div>
                    </div>
                    <!-- Phase 77: Export Button moved here -->
                    <button @click="showExportModal = true" 
                            class="px-3 py-1.5 text-xs bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500/30 transition inline-flex items-center gap-1">
                        📥 Xuất dữ liệu
                    </button>
                    <button @click="fetchData()" 
                            class="self-start sm:self-auto px-2 py-1 text-xs bg-gray-500/20 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-500/30 transition inline-flex items-center gap-1"
                            :disabled="loading">
                        <svg x-show="loading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!loading">🔄</span>
                        <span x-text="autoRefreshCountdown > 0 ? 'Làm mới (' + Math.floor(autoRefreshCountdown/60) + ':' + String(autoRefreshCountdown%60).padStart(2,'0') + ')' : 'Làm mới'"></span>
                    </button>
                </div>
            </div>
            
            <!-- Phase 77/224: Export Modal -->
            <div x-show="showExportModal" 
                 x-transition
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                 @click.self="showExportModal = false">
                <div class="bg-white dark:bg-zeus-card rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
                    <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">📥 Xuất Kế hoạch Ca học</h3>
                    
                    <div class="space-y-4">
                        <!-- Phase 224: Show active filter notice -->
                        <template x-if="filterApplied">
                            <div class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">📅 Đang lọc theo ngày: <span x-text="filterLabel"></span></p>
                                <p class="text-[10px] text-blue-600/70 dark:text-blue-400/70 mt-1">File Excel sẽ chỉ chứa dữ liệu trong khoảng thời gian đã lọc.</p>
                            </div>
                        </template>
                        
                        <!-- Phase 224: Hide month pickers when date filter is active -->
                        <template x-if="!filterApplied">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Từ tháng</label>
                                    <input type="month" x-model="fromMonth" 
                                           class="w-full px-3 py-2 rounded-lg bg-light-secondary dark:bg-zeus-secondary border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-1">Đến tháng</label>
                                    <input type="month" x-model="toMonth"
                                           class="w-full px-3 py-2 rounded-lg bg-light-secondary dark:bg-zeus-secondary border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                        </template>
                        
                        <div class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                            ℹ️ File Excel sẽ chứa số ca dự kiến theo tuần, phân loại theo quy mô lớp (1v1, 1v2, 1v3, 1v8) và quốc tịch giáo viên.
                        </div>
                        
                        <div class="flex gap-3 justify-end">
                            <button @click="showExportModal = false"
                                    class="px-4 py-2 text-sm bg-gray-500/20 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-500/30 transition">
                                Hủy
                            </button>
                            <button @click="exportPlan()"
                                    :disabled="exportLoading"
                                    class="px-4 py-2 text-sm bg-green-500 text-white rounded-lg hover:bg-green-600 transition flex items-center gap-2 disabled:opacity-50">
                                <svg x-show="exportLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="exportLoading ? 'Đang xuất...' : '📥 Xuất Excel'"></span>
                            </button>
                        </div>
                        
                        <!-- Error message -->
                        <div x-show="exportError" class="text-sm text-red-500" x-text="exportError"></div>
                    </div>
                </div>
            </div>
            
            <!-- Loading state -->
            <template x-if="loading && weeks.length === 0">
                <div class="text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline"></span> Đang tải dữ liệu...
                </div>
            </template>
            
            <!-- Data Table - Colors matching Plan.xlsx format with Class Size breakdown -->
            <template x-if="weeks.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <!-- Row 1: Tuần -->
                            <tr>
                                <th class="px-2 py-1 text-left text-gray-600 border border-gray-300 sticky left-0 bg-white dark:bg-slate-800 dark:text-gray-300 dark:border-slate-600" colspan="2">Tuần</th>
                                <template x-for="(week, weekIdx) in weeks" :key="'week-' + weekIdx">
                                    <th class="px-2 py-1 text-center font-bold border border-gray-300 dark:border-slate-600 min-w-[70px]"
                                        :class="week.is_current ? 'bg-emerald-500 text-white' : 'text-gray-800 dark:text-gray-200 bg-slate-100 dark:bg-slate-700'"
                                        x-text="'Tuần ' + week.week_of_year"></th>
                                </template>
                                <th class="px-2 py-1 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-orange-500">SUM</th>
                            </tr>
                            <!-- Row 2: Từ ngày -->
                            <tr>
                                <th class="px-2 py-1 text-left text-gray-600 border border-gray-300 sticky left-0 bg-white dark:bg-slate-800 dark:text-gray-400 dark:border-slate-600" colspan="2">Từ ngày</th>
                                <template x-for="(week, weekIdx) in weeks" :key="'start-' + weekIdx">
                                    <th class="px-2 py-1 text-center text-[10px] border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' : 'text-gray-600 dark:text-gray-400 bg-slate-50 dark:bg-slate-700'"
                                        x-text="week.week_start"></th>
                                </template>
                                <th class="px-2 py-1 border border-gray-300 dark:border-slate-600 bg-orange-400"></th>
                            </tr>
                            <!-- Row 3: Đến ngày -->
                            <tr>
                                <th class="px-2 py-1 text-left text-gray-600 border border-gray-300 sticky left-0 bg-white dark:bg-slate-800 dark:text-gray-400 dark:border-slate-600" colspan="2">Đến ngày</th>
                                <template x-for="(week, weekIdx) in weeks" :key="'end-' + weekIdx">
                                    <th class="px-2 py-1 text-center text-[10px] border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' : 'text-gray-600 dark:text-gray-400 bg-slate-50 dark:bg-slate-700'"
                                        x-text="week.week_end"></th>
                                </template>
                                <th class="px-2 py-1 border border-gray-300 dark:border-slate-600 bg-orange-400"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- SPW Header Row - Orange -->
                            <tr class="bg-orange-500">
                                <td class="px-2 py-2 text-white font-bold border border-gray-300 dark:border-slate-600 sticky left-0 bg-orange-500">SPW</td>
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 bg-orange-500"></td>
                                <template x-for="(week, weekIdx) in weeks" :key="'spw-' + weekIdx">
                                    <td class="px-2 py-2 border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-500' : 'bg-orange-500'"></td>
                                </template>
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 bg-orange-500"></td>
                            </tr>
                            <!-- Tổng ca dự kiến đang chạy - Light orange -->
                            <tr class="bg-orange-300 dark:bg-orange-400 font-bold">
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-orange-300 dark:bg-orange-400"></td>
                                <td class="px-2 py-2 text-gray-800 border border-gray-300 dark:border-slate-600 bg-orange-300 dark:bg-orange-400">Tổng ca dự kiến đang chạy</td>
                                <template x-for="(week, weekIdx) in weeks" :key="'total-' + weekIdx">
                                    <td class="px-2 py-2 text-center text-gray-800 border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-200 dark:bg-emerald-700 font-bold' : 'bg-orange-200 dark:bg-orange-500'"
                                        x-text="week.total.toLocaleString()"></td>
                                </template>
                                <td class="px-2 py-2 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-orange-500" x-text="grandTotal.toLocaleString()"></td>
                            </tr>
                            <!-- Class Size: 1v1 -->
                            <tr class="bg-amber-100 dark:bg-amber-900">
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-amber-100 dark:bg-amber-900"></td>
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900">1v1</td>
                                <template x-for="(week, weekIdx) in weeks" :key="'1v1-sum-' + weekIdx">
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-800' : 'bg-amber-50 dark:bg-amber-800'"
                                        x-text="(week.by_class_size?.['1v1']?.total || 0).toLocaleString()"></td>
                                </template>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900" x-text="getClassSizeTotal('1v1').toLocaleString()"></td>
                            </tr>
                            <template x-for="nationality in nationalities" :key="'1v1-' + nationality">
                                <tr class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-white dark:bg-slate-800"></td>
                                    <td class="px-2 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 pl-4" x-text="nationality"></td>
                                    <template x-for="(week, weekIdx) in weeks" :key="'1v1-' + nationality + '-' + weekIdx">
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="[
                                                week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/50' : '',
                                                (week.by_class_size?.['1v1']?.by_nationality?.[nationality]) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                            x-text="(week.by_class_size?.['1v1']?.by_nationality?.[nationality] || '-')"></td>
                                    </template>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-amber-50 dark:bg-amber-900/50" x-text="getNationalityTotal('1v1', nationality)"></td>
                                </tr>
                            </template>
                            <!-- Class Size: 1v2 -->
                            <tr class="bg-amber-100 dark:bg-amber-900">
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-amber-100 dark:bg-amber-900"></td>
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900">1v2</td>
                                <template x-for="(week, weekIdx) in weeks" :key="'1v2-sum-' + weekIdx">
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-800' : 'bg-amber-50 dark:bg-amber-800'"
                                        x-text="(week.by_class_size?.['1v2']?.total || 0).toLocaleString()"></td>
                                </template>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900" x-text="getClassSizeTotal('1v2').toLocaleString()"></td>
                            </tr>
                            <template x-for="nationality in nationalities" :key="'1v2-' + nationality">
                                <tr class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-white dark:bg-slate-800"></td>
                                    <td class="px-2 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 pl-4" x-text="nationality"></td>
                                    <template x-for="(week, weekIdx) in weeks" :key="'1v2-' + nationality + '-' + weekIdx">
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="[
                                                week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/50' : '',
                                                (week.by_class_size?.['1v2']?.by_nationality?.[nationality]) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                            x-text="(week.by_class_size?.['1v2']?.by_nationality?.[nationality] || '-')"></td>
                                    </template>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-amber-50 dark:bg-amber-900/50" x-text="getNationalityTotal('1v2', nationality)"></td>
                                </tr>
                            </template>
                            <!-- Class Size: 1v3 -->
                            <tr class="bg-amber-100 dark:bg-amber-900">
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-amber-100 dark:bg-amber-900"></td>
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900">1v3</td>
                                <template x-for="(week, weekIdx) in weeks" :key="'1v3-sum-' + weekIdx">
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-800' : 'bg-amber-50 dark:bg-amber-800'"
                                        x-text="(week.by_class_size?.['1v3']?.total || 0).toLocaleString()"></td>
                                </template>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900" x-text="getClassSizeTotal('1v3').toLocaleString()"></td>
                            </tr>
                            <template x-for="nationality in nationalities" :key="'1v3-' + nationality">
                                <tr class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-white dark:bg-slate-800"></td>
                                    <td class="px-2 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 pl-4" x-text="nationality"></td>
                                    <template x-for="(week, weekIdx) in weeks" :key="'1v3-' + nationality + '-' + weekIdx">
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="[
                                                week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/50' : '',
                                                (week.by_class_size?.['1v3']?.by_nationality?.[nationality]) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                            x-text="(week.by_class_size?.['1v3']?.by_nationality?.[nationality] || '-')"></td>
                                    </template>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-amber-50 dark:bg-amber-900/50" x-text="getNationalityTotal('1v3', nationality)"></td>
                                </tr>
                            </template>
                            <!-- Class Size: 1v8 -->
                            <tr class="bg-amber-100 dark:bg-amber-900">
                                <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-amber-100 dark:bg-amber-900"></td>
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900">1v8</td>
                                <template x-for="(week, weekIdx) in weeks" :key="'1v8-sum-' + weekIdx">
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600"
                                        :class="week.is_current ? 'bg-emerald-100 dark:bg-emerald-800' : 'bg-amber-50 dark:bg-amber-800'"
                                        x-text="(week.by_class_size?.['1v8']?.total || 0).toLocaleString()"></td>
                                </template>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 bg-amber-100 dark:bg-amber-900" x-text="getClassSizeTotal('1v8').toLocaleString()"></td>
                            </tr>
                            <template x-for="nationality in nationalities" :key="'1v8-' + nationality">
                                <tr class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <td class="px-2 py-2 border border-gray-300 dark:border-slate-600 sticky left-0 bg-white dark:bg-slate-800"></td>
                                    <td class="px-2 py-2 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 pl-4" x-text="nationality"></td>
                                    <template x-for="(week, weekIdx) in weeks" :key="'1v8-' + nationality + '-' + weekIdx">
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="[
                                                week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/50' : '',
                                                (week.by_class_size?.['1v8']?.by_nationality?.[nationality]) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                            x-text="(week.by_class_size?.['1v8']?.by_nationality?.[nationality] || '-')"></td>
                                    </template>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-amber-50 dark:bg-amber-900/50" x-text="getNationalityTotal('1v8', nationality)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
            
            <!-- Empty state -->
            <template x-if="!loading && weeks.length === 0">
                <div class="text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
                    Không có dữ liệu ca học dự kiến
                </div>
            </template>
        </div>
        
        <!-- Phase 66/68/77/80: Teacher Country Unscheduled Summary -->
        <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-3 md:p-4 border border-light-border dark:border-zeus-border mt-4"
             x-data="teacherCountryUnscheduled()"
             x-init="init()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                    📅 Tổng số ca Unscheduled
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query — Lớp 1:1 &amp; 1:2</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT teacher.user_country_id, IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name, CASE WHEN gc.grpcls_total_seats = 2 THEN '1:2' ELSE '1:1' END AS class_type, COUNT(ol.ordles_id) AS unscheduled_count FROM tbl_order_lessons ol INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id INNER JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id LEFT JOIN tbl_countries_lang cl ON cl.countrylang_country_id = c.country_id AND cl.countrylang_lang_id = 1 LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id WHERE ol.ordles_status = 1 AND ol.ordles_tlang_id IN (...) AND o.order_status = 2 AND o.order_payment_status = 1 GROUP BY teacher.user_country_id, teacher_country_name, class_type</code></span></span>
                </h4>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Phase 80: Average lessons per week filter -->
                    <div class="flex items-center gap-1">
                        <label class="text-xs text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">Số buổi/tuần:</label>
                        <select x-model="lessonsPerWeek" class="text-xs px-2 py-1 border border-light-border dark:border-zeus-border rounded-lg bg-white dark:bg-zeus-card focus:outline-none focus:ring-1 focus:ring-blue-500 text-light-text dark:text-zeus-text">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                        </select>
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Giải thích</span><br>Số buổi học trung bình mỗi học sinh dự kiến học trong tuần.<br><br><span class="tooltip-label">Công thức:</span><br>Ca Unscheduled/tuần = (Số HV active × Số buổi/tuần) - Số ca Scheduled/tuần</span></span>
                    </div>
                    <button @click="fetchWeeklyBreakdown()" 
                            class="px-2 py-1 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition inline-flex items-center gap-1"
                            :disabled="weeklyLoading">
                        <svg x-show="weeklyLoading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Lọc số ca Unscheduled</span>
                    </button>
                    <button @click="fetchData()" 
                            class="px-2 py-1 text-xs bg-gray-500/20 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-500/30 transition inline-flex items-center gap-1"
                            :disabled="loading">
                        <svg x-show="loading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!loading">🔄</span>
                        <span x-text="autoRefreshCountdown > 0 ? 'Làm mới (' + Math.floor(autoRefreshCountdown/60) + ':' + String(autoRefreshCountdown%60).padStart(2,'0') + ')' : 'Làm mới'"></span>
                    </button>
                </div>
            </div>
            
            <!-- Phase 226: Class type breakdown cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <!-- 1:1 Breakdown -->
                <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-xs font-semibold text-light-text dark:text-zeus-text mb-2">👤 Lớp 1:1
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query — Lớp 1:1</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(ol.ordles_id) FROM tbl_order_lessons ol INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id WHERE ol.ordles_status = 1 AND ol.ordles_tlang_id IN (...) AND o.order_status = 2 AND o.order_payment_status = 1 AND (gc.grpcls_total_seats IS NULL OR gc.grpcls_total_seats < 2)</code></span></span>
                    </p>
                    <div class="text-center p-2 bg-amber-500/10 rounded-lg">
                        <p class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400" x-text="oneOnOneTotal.toLocaleString()"></p>
                        <p class="text-[10px] text-amber-600/70 dark:text-amber-400/70">Unscheduled</p>
                    </div>
                </div>
                <!-- 1:2 Breakdown -->
                <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-xs font-semibold text-light-text dark:text-zeus-text mb-2">👥 Lớp 1:2
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query — Lớp 1:2</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT COUNT(ol.ordles_id) FROM tbl_order_lessons ol INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id WHERE ol.ordles_status = 1 AND ol.ordles_tlang_id IN (...) AND o.order_status = 2 AND o.order_payment_status = 1 AND gc.grpcls_total_seats = 2</code></span></span>
                    </p>
                    <div class="text-center p-2 bg-amber-500/10 rounded-lg">
                        <p class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400" x-text="oneOnTwoTotal.toLocaleString()"></p>
                        <p class="text-[10px] text-amber-600/70 dark:text-amber-400/70">Unscheduled</p>
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <template x-if="loading && countries.length === 0">
                <div class="text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline"></span> Đang tải dữ liệu...
                </div>
            </template>
            
            <!-- Data Table - Same structure as Weekly table -->
            <template x-if="countries.length > 0">
                <!-- Phase 226: Data Table with 1:1 and 1:2 columns -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <!-- Header row -->
                            <tr class="bg-sky-100 dark:bg-sky-900">
                                <th class="px-2 py-2 text-left text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 sticky left-0 bg-sky-100 dark:bg-sky-900">Quốc gia</th>
                                <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 min-w-[80px]">
                                    <div>👤 1:1</div>
                                </th>
                                <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 min-w-[80px]">
                                    <div>👥 1:2</div>
                                </th>
                                <th class="px-2 py-2 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-sky-500">TỔNG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Total row first -->
                            <tr class="bg-sky-200 dark:bg-sky-800 font-bold">
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 sticky left-0 bg-sky-200 dark:bg-sky-800">📊 TỔNG CỘNG</td>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-sky-100 dark:bg-sky-700" x-text="oneOnOneTotal.toLocaleString()"></td>
                                <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-sky-100 dark:bg-sky-700" x-text="oneOnTwoTotal.toLocaleString()"></td>
                                <td class="px-2 py-2 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-sky-500" x-text="total.toLocaleString()"></td>
                            </tr>
                            <!-- Country rows -->
                            <template x-for="country in countries" :key="country">
                                <tr class="bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <td class="px-2 py-2 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 sticky left-0 bg-white dark:bg-slate-800" x-text="country"></td>
                                    <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                        :class="(byCountryOneOnOne[country] && byCountryOneOnOne[country] !== 0) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'"
                                        x-text="byCountryOneOnOne[country] ? byCountryOneOnOne[country].toLocaleString() : '-'"></td>
                                    <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                        :class="(byCountryOneOnTwo[country] && byCountryOneOnTwo[country] !== 0) ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'"
                                        x-text="byCountryOneOnTwo[country] ? byCountryOneOnTwo[country].toLocaleString() : '-'"></td>
                                    <td class="px-2 py-2 text-center font-medium text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-sky-50 dark:bg-sky-900/50" 
                                        x-text="byCountry[country] ? byCountry[country].toLocaleString() : '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
            
            <!-- Empty state -->
            <template x-if="!loading && countries.length === 0">
                <div class="text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
                    Không có dữ liệu ca học unscheduled
                </div>
            </template>
            
            <!-- Phase 80/227: Weekly Unscheduled Breakdown Table -->
            <template x-if="weeklyData.weeks && weeklyData.weeks.length > 0">
                <div class="mt-4 pt-4 border-t border-light-border dark:border-zeus-border">
                    <h5 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3 flex items-center gap-2">
                        📊 Ca Unscheduled theo tuần (dự kiến <span x-text="lessonsPerWeek"></span> buổi/tuần)
                        <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Công thức tính</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">Unscheduled/tuần = (Số HV active có order paid × Số buổi/tuần) - Số ca Scheduled trong tuần đó

HV active: Học viên có đơn hàng đã thanh toán VÀ còn ca học chưa học (status IN (1,2))
- Status 1: Pending (chưa lên lịch)
- Status 2: Scheduled (đã lên lịch)
- KHÔNG bao gồm status 3 (Completed)
- Chỉ tính học sinh sản phẩm SPW (ordles_tlang_id IN danh sách)

Scheduled: Ca học đã lên lịch (status IN (2,3,4)) trong tuần, chỉ SPW

Phân loại lớp 1:1 / 1:2:
- LEFT JOIN tbl_group_classes (grpcls_total_seats)
- grpcls_total_seats = 2 → Lớp 1:2
- Còn lại (NULL hoặc < 2) → Lớp 1:1

SQL (HV active):
SELECT COUNT(DISTINCT order_user_id) FROM tbl_orders o
JOIN tbl_order_lessons ol ON o.order_id = ol.ordles_order_id
WHERE o.order_status = 2 AND o.order_payment_status = 1
AND ol.ordles_status IN (1,2)
AND ol.ordles_tlang_id IN (558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)

SQL (Scheduled/tuần + class_type):
SELECT YEARWEEK(CONVERT_TZ(ordles_lesson_starttime, '+00:00', '+07:00'), 3),
  CASE WHEN gc.grpcls_total_seats = 2 THEN '1:2' ELSE '1:1' END AS class_type,
  COUNT(*)
FROM tbl_order_lessons ol
INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status IN (2,3,4)
AND o.order_status = 2 AND o.order_payment_status = 1
AND ol.ordles_tlang_id IN (558,560,562,580,581,564,567,568,569,416,415,414,413,571,572,574,575,576,389,390,392,405,406,407,411,412,577,586,585,584,582,404,403,583,471)
GROUP BY YEARWEEK(...), class_type</code></span></span>
                    </h5>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-purple-100 dark:bg-purple-900">
                                    <th class="px-2 py-2 text-left text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 sticky left-0 bg-purple-100 dark:bg-purple-900">Tuần</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600">Từ ngày</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600">Đến ngày</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600">HV Active</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600">Dự kiến</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600">Scheduled</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 min-w-[80px]">👤 1:1</th>
                                    <th class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 font-bold border border-gray-300 dark:border-slate-600 min-w-[80px]">👥 1:2</th>
                                    <th class="px-2 py-2 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-purple-500">Unscheduled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Total row first -->
                                <tr class="bg-purple-200 dark:bg-purple-800 font-bold">
                                    <td class="px-2 py-2 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 sticky left-0 bg-purple-200 dark:bg-purple-800" colspan="3">📊 TỔNG CỘNG</td>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="weeklyData.total_active_students?.toLocaleString() || '-'"></td>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="weeklyData.total_expected?.toLocaleString() || '-'"></td>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="weeklyData.total_scheduled?.toLocaleString() || '-'"></td>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-purple-100 dark:bg-purple-700" x-text="weeklyData.one_on_one_total_unscheduled?.toLocaleString() || '-'"></td>
                                    <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 bg-purple-100 dark:bg-purple-700" x-text="weeklyData.one_on_two_total_unscheduled?.toLocaleString() || '-'"></td>
                                    <td class="px-2 py-2 text-center text-white font-bold border border-gray-300 dark:border-slate-600 bg-purple-500" x-text="weeklyData.total_unscheduled?.toLocaleString() || '-'"></td>
                                </tr>
                                <!-- Week rows -->
                                <template x-for="week in weeklyData.weeks" :key="week.week_key">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700"
                                        :class="week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-white dark:bg-slate-800'">
                                        <td class="px-2 py-2 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600 sticky left-0"
                                            :class="week.is_current ? 'bg-emerald-50 dark:bg-emerald-900/30 font-bold' : 'bg-white dark:bg-slate-800'"
                                            x-text="'Tuần ' + week.week_of_year"></td>
                                        <td class="px-2 py-2 text-center text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-slate-600 text-[10px]" x-text="week.week_start"></td>
                                        <td class="px-2 py-2 text-center text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-slate-600 text-[10px]" x-text="week.week_end"></td>
                                        <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="week.active_students?.toLocaleString() || '-'"></td>
                                        <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="week.expected?.toLocaleString() || '-'"></td>
                                        <td class="px-2 py-2 text-center text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600" x-text="week.scheduled?.toLocaleString() || '-'"></td>
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="week.one_on_one_unscheduled > 0 ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30' : 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30'"
                                            x-text="week.one_on_one_unscheduled?.toLocaleString() || '0'"></td>
                                        <td class="px-2 py-2 text-center border border-gray-300 dark:border-slate-600"
                                            :class="week.one_on_two_unscheduled > 0 ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30' : 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30'"
                                            x-text="week.one_on_two_unscheduled?.toLocaleString() || '0'"></td>
                                        <td class="px-2 py-2 text-center font-bold border border-gray-300 dark:border-slate-600"
                                            :class="week.unscheduled > 0 ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30' : 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30'"
                                            x-text="week.unscheduled?.toLocaleString() || '0'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
            
            <!-- Weekly loading state -->
            <template x-if="weeklyLoading">
                <div class="mt-4 text-center py-4 text-light-text-muted dark:text-zeus-text-muted">
                    <span class="spinner-inline"></span> Đang tính toán ca Unscheduled theo tuần...
                </div>
            </template>
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

    <!-- ===== NEVER LOGGED IN STUDENTS STATS (TODAY ONLY) ===== -->
    <div class="bg-gradient-to-r from-purple-500/5 via-pink-500/5 to-rose-500/5 dark:from-purple-500/10 dark:via-pink-500/10 dark:to-rose-500/10 rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm"
         x-data="neverLoggedInSectionDailyOps()">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-3 md:mb-4 flex items-center gap-2 flex-wrap">
            👤 Tình trạng đăng nhập của Học viên
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap">SELECT u.* FROM tbl_users u WHERE u.user_id IN (SELECT DISTINCT o.order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE()) AND u.user_lastseen IS NULL</code></span></span>
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 md:gap-4">
            <div class="text-center p-3 md:p-4 bg-purple-500/10 rounded-lg border border-purple-500/30 relative">
                <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $neverLoggedInStats['today']['count'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">🚫 HV chưa đăng nhập
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">SQL Query</span><br><code class="text-[10px] bg-gray-100 dark:bg-gray-800 p-1 rounded block mt-1 whitespace-pre-wrap cursor-pointer" @click="copyToClipboard($event.target.textContent)">SELECT COUNT(*) FROM tbl_users WHERE user_id IN (SELECT DISTINCT order_user_id FROM tbl_order_lessons ol JOIN tbl_orders o ON ol.ordles_order_id = o.order_id WHERE DATE(ol.ordles_lesson_starttime) = CURDATE() AND ol.ordles_tlang_id IN (533,558,560,...)) AND user_lastseen IS NULL AND user_deleted IS NULL AND user_id NOT IN (SELECT usrtok_user_id FROM tbl_user_auth_token)</code><br><small class="text-gray-500">Click để copy</small></span></span>
                </p>
                @if(($neverLoggedInStats['today']['count'] ?? 0) > 0)
                <button @click="openNeverLoggedInModal()" class="mt-2 px-3 py-1 text-xs bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-500/30 transition">Chi tiết</button>
                @endif
            </div>
            <div class="text-center p-3 md:p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $neverLoggedInStats['today']['total_students_with_lessons'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">📚 Tổng HV có lịch hôm nay
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
                <button @click="openMultiLessonModal()" class="mt-2 px-3 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-500/30 transition">Chi tiết</button>
                @endif
            </div>
        </div>
        
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
                        🚫 Danh sách HV chưa từng đăng nhập (Hôm nay)
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
                        👥 Danh sách HV có ≥2 ca học/ngày (Hôm nay)
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
                                        <th class="px-3 py-2 text-center text-light-text-muted dark:text-zeus-text-muted font-medium">Số ca</th>
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
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs bg-teal-500/20 text-teal-600 dark:text-teal-400 rounded-full font-bold" x-text="student.lesson_count"></span>
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
                        Tổng: <strong x-text="multiLessonStudents.length"></strong> học viên
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

    <!-- Today's Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-3 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <div class="text-center">
                <p class="text-xl md:text-3xl font-bold text-blue-500">{{ $todayStats['total_sessions'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted">
                    Tổng ca học
                    <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span> + <span class="tooltip-table">tbl_group_classes</span><br>Điều kiện: ngày hôm nay</span></span>
                </p>
            </div>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-3 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <div class="text-center">
                <p class="text-xl md:text-3xl font-bold text-green-500">{{ $todayStats['lessons']['completed'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted">
                    Đã hoàn thành
                    <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 3 (COMPLETED)</span></span>
                </p>
            </div>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-3 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <div class="text-center">
                <p class="text-xl md:text-3xl font-bold text-blue-400">{{ $todayStats['lessons']['scheduled'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted">
                    Đang chờ
                    <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 2 (SCHEDULED)</span></span>
                </p>
            </div>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-3 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <div class="text-center">
                <p class="text-xl md:text-3xl font-bold text-red-500">{{ $todayStats['lessons']['cancelled'] ?? 0 }}</p>
                <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted">
                    Đã hủy
                    <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 4 (CANCELLED)</span></span>
                </p>
            </div>
        </div>
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-3 md:p-6 border border-light-border dark:border-zeus-border shadow-sm col-span-2 sm:col-span-1">
            <div class="text-center">
                <p class="text-xl md:text-3xl font-bold text-purple-500">{{ $todayStats['completion_rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted">
                    Tỷ lệ hoàn thành
                    <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Công thức: COMPLETED / (SCHEDULED + COMPLETED) × 100<br>Từ bảng: <span class="tooltip-table">tbl_order_lessons</span></span></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Lesson Chart (14 days) -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📊 Bài học 14 ngày qua
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>GROUP BY ngày, theo ordles_status<br>Hoàn thành (3), Đã lên lịch (2), Đã hủy (4)</span></span>
        </h3>
        <canvas id="lessonChart" height="100"></canvas>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Lessons Detail -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📚 Bài học 1-1
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Trạng thái: 1=Chưa lên lịch, 2=Đã lên lịch, 3=Hoàn thành, 4=Đã hủy</span></span>
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Tổng số</span>
                    <span class="text-xl font-bold text-light-text dark:text-zeus-text">{{ $todayStats['lessons']['total'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">✅ Hoàn thành</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ $todayStats['lessons']['completed'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-blue-600 dark:text-blue-400">📅 Đã lên lịch</span>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $todayStats['lessons']['scheduled'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <span class="text-amber-600 dark:text-amber-400">⏳ Chưa lên lịch</span>
                    <span class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $todayStats['lessons']['unscheduled'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <span class="text-red-600 dark:text-red-400">❌ Đã hủy</span>
                    <span class="text-xl font-bold text-red-600 dark:text-red-400">{{ $todayStats['lessons']['cancelled'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Group Classes -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                👥 Lớp nhóm
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_group_classes</span><br>grpcls_status: 1=Đã lên lịch, 2=Hoàn thành, 3=Đã hủy</span></span>
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Tổng số</span>
                    <span class="text-xl font-bold text-light-text dark:text-zeus-text">{{ $todayStats['group_classes']['total'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-blue-600 dark:text-blue-400">📅 Đã lên lịch</span>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $todayStats['group_classes']['scheduled'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">✅ Hoàn thành</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ $todayStats['group_classes']['completed'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Lessons Table -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🕐 Bài học Gần đây
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span> JOIN <span class="tooltip-table">tbl_orders</span><br>Sắp xếp theo ordles_lesson_starttime DESC</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">ID</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Giáo viên</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Học sinh</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời gian</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời lượng</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLessons as $lesson)
                    <tr class="border-b border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                        <td class="py-3 px-4 text-sm text-light-text dark:text-zeus-text">#{{ $lesson['id'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text dark:text-zeus-text">{{ $lesson['teacher_name'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text dark:text-zeus-text">{{ $lesson['student_name'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $lesson['start_time'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $lesson['duration'] }} phút</td>
                        <td class="py-3 px-4">
                            @php
                                $statusColors = [
                                    1 => 'bg-amber-500/20 text-amber-500',
                                    2 => 'bg-blue-500/20 text-blue-500',
                                    3 => 'bg-green-500/20 text-green-500',
                                    4 => 'bg-red-500/20 text-red-500',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$lesson['status']] ?? 'bg-gray-500/20 text-gray-500' }}">
                                {{ $lesson['status_label'] }}
                            </span>
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

    <!-- Issues Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Issues Stats -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🚨 Báo cáo vấn đề
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_reported_issues</span><br>repiss_status: 1=Đang xử lý, 2=Đã giải quyết, 3=Escalated, 4=Đã đóng</span></span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/30">
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $issues['in_progress'] ?? 0 }}</p>
                    <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đang xử lý</p>
                </div>
                <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/30">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $issues['resolved'] ?? 0 }}</p>
                    <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã giải quyết</p>
                </div>
                <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/30">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $issues['escalated'] ?? 0 }}</p>
                    <p class="text-sm text-red-600/80 dark:text-red-400/80">Đã escalate</p>
                </div>
                <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-3xl font-bold text-light-text-muted dark:text-zeus-text-muted">{{ $issues['closed'] ?? 0 }}</p>
                    <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Đã đóng</p>
                </div>
                <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/30">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $issues['new_today'] ?? 0 }}</p>
                    <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Mới hôm nay</p>
                </div>
            </div>
        </div>

        <!-- Pending Issues List -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">⚠️ Vấn đề Đang xử lý</h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($pendingIssues as $issue)
                <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text">#{{ $issue['id'] }} - {{ $issue['user_name'] }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $issue['reported_on'] }}</p>
                    </div>
                    @php
                        $issueStatusColors = [
                            1 => 'bg-amber-500/20 text-amber-500',
                            3 => 'bg-red-500/20 text-red-500',
                        ];
                        $issueStatusLabels = [
                            1 => 'Đang xử lý',
                            3 => 'Escalated',
                        ];
                    @endphp
                    <span class="px-2 py-1 text-xs rounded-full {{ $issueStatusColors[$issue['status']] ?? 'bg-gray-500/20 text-gray-500' }}">
                        {{ $issueStatusLabels[$issue['status']] ?? 'Unknown' }}
                    </span>
                </div>
                @empty
                <p class="text-center text-light-text-muted dark:text-zeus-text-muted py-4">Không có vấn đề đang xử lý</p>
                @endforelse
            </div>
        </div>
    </div>
    
