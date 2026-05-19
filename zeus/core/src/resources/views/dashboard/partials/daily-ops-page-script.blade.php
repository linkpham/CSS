// Phase 139: Changed from const to window property for PJAX tab switching
window.activeProgram = '{{ $activeProgram ?? "all" }}';

{
    // Get theme-aware colors
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#9CA3AF' : '#64748B';
    const gridColor = isDarkMode ? '#2A2E35' : '#E2E8F0';

    // Dark theme chart defaults
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // Lesson Chart
    const lessonEl = document.getElementById('lessonChart');
    if (lessonEl) {
        const lessonCtx = lessonEl.getContext('2d');
        new Chart(lessonCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($lessonChart['labels']) !!},
                datasets: [
                    {
                        label: 'Hoàn thành',
                        data: {!! json_encode($lessonChart['datasets']['completed']) !!},
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: '#22c55e',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Đã lên lịch',
                        data: {!! json_encode($lessonChart['datasets']['scheduled']) !!},
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: '#3B82F6',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Đã hủy',
                        data: {!! json_encode($lessonChart['datasets']['cancelled']) !!},
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#EF4444',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    x: {
                        stacked: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                }
            }
        });
    }
} // End of chart initialization block

// Never Logged In Students Section for Daily Ops (Today only)
function neverLoggedInSectionDailyOps() {
    return {
        // Modal states
        showNeverLoggedInModal: false,
        showMultiLessonModal: false,
        
        // Loading states
        neverLoggedInLoading: false,
        multiLessonLoading: false,
        
        // Data
        neverLoggedInStudents: [],
        multiLessonStudents: [],
        
        // Open Never Logged In Modal and fetch data
        async openNeverLoggedInModal() {
            this.showNeverLoggedInModal = true;
            this.neverLoggedInLoading = true;
            this.neverLoggedInStudents = [];
            
            try {
                const response = await fetch(`/api/never-logged-in-students?period=today&program=${activeProgram}`);
                const result = await response.json();
                if (result.success) {
                    this.neverLoggedInStudents = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching never logged in students:', error);
            } finally {
                this.neverLoggedInLoading = false;
            }
        },
        
        // Open Multi-Lesson Modal and fetch data
        async openMultiLessonModal() {
            this.showMultiLessonModal = true;
            this.multiLessonLoading = true;
            this.multiLessonStudents = [];
            
            try {
                const response = await fetch(`/api/students-multiple-lessons?period=today&program=${activeProgram}`);
                const result = await response.json();
                if (result.success) {
                    this.multiLessonStudents = result.data?.students || [];
                }
            } catch (error) {
                console.error('Error fetching multi-lesson students:', error);
            } finally {
                this.multiLessonLoading = false;
            }
        },
        
        // Copy text to clipboard
        copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
                    toast.textContent = '✓ Đã copy SQL query!';
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    alert('Đã copy SQL query!');
                });
            }
        },
        
        // Export Never Logged In students to Excel (CSV format)
        exportNeverLoggedInToExcel() {
            if (this.neverLoggedInStudents.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const headers = ['STT', 'Username', 'Họ tên', 'Email', 'SĐT'];
            const rows = this.neverLoggedInStudents.map((student, index) => [
                index + 1,
                student.username || '',
                student.name || '',
                student.email || '',
                student.phone || ''
            ]);
            
            this.downloadCSV(headers, rows, 'HV_chua_dang_nhap_Hom_nay');
        },
        
        // Export Multi-Lesson students to Excel (CSV format)
        exportMultiLessonToExcel() {
            if (this.multiLessonStudents.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const headers = ['STT', 'Username', 'Họ tên', 'Email', 'SĐT', 'Số ca'];
            const rows = this.multiLessonStudents.map((student, index) => [
                index + 1,
                student.username || '',
                student.name || '',
                student.email || '',
                student.phone || '',
                student.lesson_count || ''
            ]);
            
            this.downloadCSV(headers, rows, 'HV_co_nhieu_ca_hoc_Hom_nay');
        },
        
        // Helper function to download CSV
        downloadCSV(headers, rows, filename) {
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
            link.setAttribute('download', `${filename}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
}

// Real-Time Lesson Status Section
function realTimeLessonStatus() {
    return {
        // Loading state
        loading: false,
        
        // Data
        ongoing: { count: 0, lessons: [], unique_students: 0, slot_stats: { total: 0, unique_students: 0, success: 0, failed: 0, cancelled: 0 } },
        upcoming: { count: 0, lessons: [], unique_students: 0, cancelled: 0 },
        remaining: { count: 0, lessons: [], by_hour: {} },
        heatmap: { slots: [] },
        lastUpdated: '--:--',
        
        // Modal states
        showOngoingModal: false,
        showUpcomingModal: false,
        showRemainingModal: false,
        
        // Auto-refresh interval (30 seconds)
        refreshInterval: null,
        
        // Initialize auto-refresh
        init() {
            this.fetchRealTimeStatus();
            // Auto-refresh every 30 seconds
            this.refreshInterval = setInterval(() => {
                this.fetchRealTimeStatus();
            }, 30000);
        },
        
        // Cleanup on destroy
        destroy() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },
        
        // Fetch real-time status data
        async fetchRealTimeStatus() {
            this.loading = true;
            try {
                const response = await fetch(`/api/real-time-status?program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    this.ongoing = result.data.ongoing || { count: 0, lessons: [], unique_students: 0, slot_stats: { total: 0, unique_students: 0, success: 0, failed: 0, cancelled: 0 } };
                    this.upcoming = result.data.upcoming || { count: 0, lessons: [], unique_students: 0, cancelled: 0 };
                    this.remaining = result.data.remaining || { count: 0, lessons: [], by_hour: {} };
                    this.heatmap = result.data.heatmap || { slots: [] };
                    this.lastUpdated = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
            } catch (error) {
                console.error('Error fetching real-time status:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Open Ongoing Modal
        async openOngoingModal() {
            this.showOngoingModal = true;
            // Data already loaded, no need to fetch again
        },
        
        // Open Upcoming Modal
        async openUpcomingModal() {
            this.showUpcomingModal = true;
            // Data already loaded, no need to fetch again
        },
        
        // Open Remaining Modal
        async openRemainingModal() {
            this.showRemainingModal = true;
            // Data already loaded, no need to fetch again
        },
        
        // Export Ongoing to Excel
        exportOngoingToExcel() {
            if (!this.ongoing.lessons || this.ongoing.lessons.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const headers = ['STT', 'Học viên', 'Email', 'Giáo viên', 'Khung giờ', 'Thời lượng (phút)'];
            const rows = this.ongoing.lessons.map((lesson, index) => [
                index + 1,
                lesson.student_name || '',
                lesson.student_email || '',
                lesson.teacher_name || '',
                lesson.time_slot || '',
                lesson.duration || ''
            ]);
            
            this.downloadCSV(headers, rows, 'Ca_hoc_dang_dien_ra');
        },
        
        // Export Upcoming to Excel
        exportUpcomingToExcel() {
            if (!this.upcoming.lessons || this.upcoming.lessons.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const headers = ['STT', 'Học viên', 'Email', 'Giáo viên', 'Khung giờ', 'Bắt đầu sau (phút)'];
            const rows = this.upcoming.lessons.map((lesson, index) => [
                index + 1,
                lesson.student_name || '',
                lesson.student_email || '',
                lesson.teacher_name || '',
                lesson.time_slot || '',
                lesson.minutes_until_start || ''
            ]);
            
            this.downloadCSV(headers, rows, 'Ca_hoc_sap_dien_ra');
        },
        
        // Export Remaining to Excel
        exportRemainingToExcel() {
            if (!this.remaining.lessons || this.remaining.lessons.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const headers = ['STT', 'Học viên', 'Email', 'Giáo viên', 'Khung giờ', 'Thời lượng (phút)'];
            const rows = this.remaining.lessons.map((lesson, index) => [
                index + 1,
                lesson.student_name || '',
                lesson.student_email || '',
                lesson.teacher_name || '',
                lesson.time_slot || '',
                lesson.duration || ''
            ]);
            
            this.downloadCSV(headers, rows, 'Ca_hoc_con_lai_hom_nay');
        },
        
        // Helper function to download CSV
        downloadCSV(headers, rows, filename) {
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
            link.setAttribute('download', `${filename}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
}

// Yesterday Heatmap Section
function yesterdayHeatmap() {
    return {
        // Loading state
        loading: false,
        
        // Data
        yesterdayHeatmapData: { slots: [] },
        yesterdayDate: '',
        
        // Fetch yesterday heatmap data
        async fetchYesterdayHeatmap() {
            this.loading = true;
            try {
                const response = await fetch(`/api/yesterday-time-slot-heatmap?program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    this.yesterdayHeatmapData = result.data || { slots: [] };
                    this.yesterdayDate = result.data?.date || '';
                }
            } catch (error) {
                console.error('Error fetching yesterday heatmap:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}

// Phase 62/74/77/224: Teacher Country Weekly Summary with Class Size breakdown, auto-refresh, export, and date range filter
function teacherCountryWeekly() {
    const now = new Date();
    const currentMonth = now.toISOString().slice(0, 7); // YYYY-MM format
    
    return {
        loading: false,
        weeks: [],
        classSizes: ['1v1', '1v2', '1v3', '1v8'],
        nationalities: [],
        grandTotal: 0,
        
        // Auto-refresh (30 minutes = 1800 seconds)
        autoRefreshInterval: null,
        autoRefreshCountdown: 0,
        countdownInterval: null,
        
        // Export modal
        showExportModal: false,
        exportLoading: false,
        exportError: '',
        fromMonth: currentMonth,
        toMonth: currentMonth,
        
        // Phase 224: Date range filter
        showFilterDatePicker: false,
        filterFromDate: '',
        filterToDate: now.toISOString().split('T')[0],
        filterMaxDate: now.toISOString().split('T')[0],
        filterDateError: '',
        filterApplied: false,
        filterLabel: '',
        _activeFromDate: null,
        _activeToDate: null,
        
        init() {
            this.fetchData();
            this.startAutoRefresh();
        },
        
        destroy() {
            if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
            if (this.countdownInterval) clearInterval(this.countdownInterval);
        },
        
        startAutoRefresh() {
            // Auto-refresh every 30 minutes
            this.autoRefreshCountdown = 1800;
            this.autoRefreshInterval = setInterval(() => {
                this.fetchData();
                this.autoRefreshCountdown = 1800;
            }, 1800000);
            
            // Update countdown every second
            this.countdownInterval = setInterval(() => {
                if (this.autoRefreshCountdown > 0) {
                    this.autoRefreshCountdown--;
                }
            }, 1000);
        },
        
        // Phase 224: Apply date range filter
        applyDateFilter() {
            if (!this.filterFromDate || !this.filterToDate) return;
            if (this.filterFromDate > this.filterToDate) {
                this.filterDateError = 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc';
                return;
            }
            this.filterDateError = '';
            this.showFilterDatePicker = false;
            this.filterApplied = true;
            this._activeFromDate = this.filterFromDate;
            this._activeToDate = this.filterToDate;
            
            // Build human-readable label
            const fromObj = new Date(this.filterFromDate);
            const toObj = new Date(this.filterToDate);
            this.filterLabel = fromObj.toLocaleDateString('vi-VN') + ' → ' + toObj.toLocaleDateString('vi-VN');
            
            // Update export modal defaults to match filter
            this.fromMonth = this.filterFromDate.slice(0, 7);
            this.toMonth = this.filterToDate.slice(0, 7);
            
            this.fetchData();
        },
        
        // Phase 224: Clear date filter
        clearDateFilter() {
            this.filterApplied = false;
            this.filterLabel = '';
            this._activeFromDate = null;
            this._activeToDate = null;
            this.filterDateError = '';
            this.showFilterDatePicker = false;
            
            // Reset export modal to current month
            this.fromMonth = currentMonth;
            this.toMonth = currentMonth;
            
            this.fetchData();
        },
        
        async fetchData() {
            this.loading = true;
            this.autoRefreshCountdown = 1800; // Reset countdown on manual refresh
            try {
                // Phase 224: Include date filter params if active
                let url = '/api/teacher-country-weekly';
                if (this._activeFromDate && this._activeToDate) {
                    url += `?from_date=${this._activeFromDate}&to_date=${this._activeToDate}`;
                }
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.weeks = result.data.weeks || [];
                    this.classSizes = result.data.class_sizes || ['1v1', '1v2', '1v3', '1v8'];
                    this.nationalities = result.data.nationalities || [];
                    
                    // Calculate grand total
                    this.grandTotal = this.weeks.reduce((sum, week) => sum + week.total, 0);
                }
            } catch (error) {
                console.error('Error fetching teacher country weekly:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async exportPlan() {
            this.exportLoading = true;
            this.exportError = '';
            
            try {
                // Phase 224: Use day-level date params if filter is active, otherwise use month params
                let url;
                if (this._activeFromDate && this._activeToDate) {
                    url = `/api/export-weekly-plan?from_date=${this._activeFromDate}&to_date=${this._activeToDate}`;
                } else {
                    url = `/api/export-weekly-plan?from=${this.fromMonth}&to=${this.toMonth}`;
                }
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    // Phase 79: Use hidden iframe/link to force download even on second click
                    const link = document.createElement('a');
                    link.href = result.download_url;
                    link.download = result.filename || 'export.xlsx';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    this.showExportModal = false;
                } else {
                    this.exportError = result.message || 'Có lỗi xảy ra khi xuất file';
                }
            } catch (error) {
                console.error('Error exporting plan:', error);
                this.exportError = 'Có lỗi xảy ra khi xuất file';
            } finally {
                this.exportLoading = false;
            }
        },
        
        getClassSizeTotal(classSize) {
            return this.weeks.reduce((sum, week) => {
                return sum + (week.by_class_size?.[classSize]?.total || 0);
            }, 0);
        },
        
        getNationalityTotal(classSize, nationality) {
            return this.weeks.reduce((sum, week) => {
                return sum + (week.by_class_size?.[classSize]?.by_nationality?.[nationality] || 0);
            }, 0);
        }
    }
}

// Phase 66/68/77/80/226: Teacher Country Unscheduled Summary with auto-refresh and weekly breakdown
function teacherCountryUnscheduled() {
    return {
        loading: false,
        countries: [],
        byCountry: {},
        total: 0,
        
        // Phase 226: Class type breakdown (1:1 vs 1:2)
        oneOnOneTotal: 0,
        oneOnTwoTotal: 0,
        byCountryOneOnOne: {},
        byCountryOneOnTwo: {},
        
        // Phase 80: Weekly breakdown
        lessonsPerWeek: '2',
        weeklyLoading: false,
        weeklyData: { weeks: [] },
        
        // Auto-refresh (30 minutes = 1800 seconds)
        autoRefreshInterval: null,
        autoRefreshCountdown: 0,
        countdownInterval: null,
        
        init() {
            this.fetchData();
            this.startAutoRefresh();
        },
        
        destroy() {
            if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
            if (this.countdownInterval) clearInterval(this.countdownInterval);
        },
        
        startAutoRefresh() {
            // Auto-refresh every 30 minutes
            this.autoRefreshCountdown = 1800;
            this.autoRefreshInterval = setInterval(() => {
                this.fetchData();
                this.autoRefreshCountdown = 1800;
            }, 1800000);
            
            // Update countdown every second
            this.countdownInterval = setInterval(() => {
                if (this.autoRefreshCountdown > 0) {
                    this.autoRefreshCountdown--;
                }
            }, 1000);
        },
        
        async fetchData() {
            this.loading = true;
            this.autoRefreshCountdown = 1800; // Reset countdown on manual refresh
            try {
                const response = await fetch('/api/teacher-country-unscheduled');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data.data || [];
                    this.total = result.data.total || 0;
                    this.oneOnOneTotal = result.data.one_on_one_total || 0;
                    this.oneOnTwoTotal = result.data.one_on_two_total || 0;
                    
                    // Transform data to match weekly table structure
                    this.countries = data.map(row => row.country_name);
                    this.byCountry = {};
                    this.byCountryOneOnOne = {};
                    this.byCountryOneOnTwo = {};
                    data.forEach(row => {
                        this.byCountry[row.country_name] = row.unscheduled_count;
                        this.byCountryOneOnOne[row.country_name] = row.one_on_one || 0;
                        this.byCountryOneOnTwo[row.country_name] = row.one_on_two || 0;
                    });
                }
            } catch (error) {
                console.error('Error fetching teacher country unscheduled:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Phase 80: Fetch weekly unscheduled breakdown
        async fetchWeeklyBreakdown() {
            this.weeklyLoading = true;
            try {
                const response = await fetch(`/api/weekly-unscheduled-breakdown?lessons_per_week=${this.lessonsPerWeek}`);
                const result = await response.json();
                
                if (result.success) {
                    this.weeklyData = result.data || { weeks: [] };
                }
            } catch (error) {
                console.error('Error fetching weekly unscheduled breakdown:', error);
            } finally {
                this.weeklyLoading = false;
            }
        }
    }
}

// Phase 98: Cancellation Stats Block with time period picker
function cancellationStatsBlock() {
    // Helper: get ISO week number for a date
    function getISOWeekNumber(d) {
        const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7));
        const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
        return Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
    }
    // Helper: get Monday of ISO week
    function getISOWeekStart(year, week) {
        const jan4 = new Date(year, 0, 4);
        const dayOfWeek = jan4.getDay() || 7;
        const monday = new Date(jan4);
        monday.setDate(jan4.getDate() - dayOfWeek + 1 + (week - 1) * 7);
        return monday;
    }
    // Helper: format date as dd/mm
    function fmtDM(d) {
        return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
    }
    // Helper: format date as yyyy-mm-dd
    function fmtYMD(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    const today = new Date();
    const currentYear = today.getFullYear();
    const currentWeek = getISOWeekNumber(today);

    // Build available weeks (current week down to week 1)
    const weeks = [];
    for (let w = currentWeek; w >= 1; w--) {
        const start = getISOWeekStart(currentYear, w);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        weeks.push({
            weekNum: w,
            start: fmtYMD(start),
            end: fmtYMD(end),
            rangeLabel: fmtDM(start) + ' - ' + fmtDM(end)
        });
    }

    // Build available months (current month down to month 1)
    const months = [];
    const currentMonth = today.getMonth() + 1;
    for (let m = currentMonth; m >= 1; m--) {
        const mStart = new Date(currentYear, m - 1, 1);
        const mEnd = new Date(currentYear, m, 0);
        const cappedEnd = (m === currentMonth && mEnd > today) ? today : mEnd;
        months.push({
            month: m,
            start: fmtYMD(mStart),
            end: fmtYMD(cappedEnd),
            rangeLabel: fmtDM(mStart) + ' - ' + fmtDM(cappedEnd)
        });
    }

    return {
        activePeriod: 'today',
        loading: false,
        errorMsg: '',
        stats: null,

        // Week picker
        showWeekPicker: false,
        availableWeeks: weeks,
        pickedWeekNum: null,
        pickedWeekLabel: '',

        // Month picker
        showMonthPicker: false,
        availableMonths: months,
        pickedMonthNum: null,
        pickedMonthLabel: '',

        // Custom date range
        showDatePicker: false,
        customFromDate: '',
        customToDate: new Date().toISOString().split('T')[0],
        customDateFormatted: '',
        dateError: '',
        maxDate: new Date().toISOString().split('T')[0],

        // Phase 99: Pagination, filter, search
        pagination: null,
        detailsLoading: false,
        detailFilter: null,
        searchQuery: '',
        _currentFromDate: null,
        _currentToDate: null,
        _currentPeriod: null,

        init() {
            this.fetchStats('today');
        },

        selectPeriod(period) {
            this.activePeriod = period;
            this.showWeekPicker = false;
            this.showMonthPicker = false;
            this.showDatePicker = false;
            this.customDateFormatted = '';
            this.pickedWeekLabel = '';
            this.pickedMonthLabel = '';
            this.pickedWeekNum = null;
            this.pickedMonthNum = null;
            this.fetchStats(period);
        },

        selectWeek(w) {
            this.pickedWeekNum = w.weekNum;
            this.pickedWeekLabel = 'T' + w.weekNum + ': ' + w.rangeLabel;
            this.showWeekPicker = false;
            this.activePeriod = 'pick_week';
            this.customDateFormatted = '';
            this.pickedMonthLabel = '';
            this.pickedMonthNum = null;
            this._loadDateRange(w.start, w.end, this.pickedWeekLabel);
        },

        selectMonth(m) {
            this.pickedMonthNum = m.month;
            this.pickedMonthLabel = 'Tháng ' + m.month + ': ' + m.rangeLabel;
            this.showMonthPicker = false;
            this.activePeriod = 'pick_month';
            this.customDateFormatted = '';
            this.pickedWeekLabel = '';
            this.pickedWeekNum = null;
            this._loadDateRange(m.start, m.end, this.pickedMonthLabel);
        },

        loadCustomDateStats() {
            if (!this.customFromDate || !this.customToDate) return;
            if (this.customFromDate > this.customToDate) {
                this.dateError = 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc';
                return;
            }
            this.showDatePicker = false;
            this.dateError = '';
            const fromObj = new Date(this.customFromDate);
            const toObj = new Date(this.customToDate);
            const label = fromObj.toLocaleDateString('vi-VN') + ' - ' + toObj.toLocaleDateString('vi-VN');
            this.activePeriod = 'custom';
            this.customDateFormatted = label;
            this.pickedWeekLabel = '';
            this.pickedMonthLabel = '';
            this.pickedWeekNum = null;
            this.pickedMonthNum = null;
            this._loadDateRange(this.customFromDate, this.customToDate, label);
        },

        async _loadDateRange(fromDate, toDate, label) {
            this.loading = true;
            FilterProgress.show();
            this.errorMsg = '';
            this.stats = null;
            this.pagination = null;
            this.detailFilter = null;
            this.searchQuery = '';
            this._currentFromDate = fromDate;
            this._currentToDate = toDate;
            this._currentPeriod = null;
            try {
                const response = await fetch(`/api/cancellation-stats?from_date=${fromDate}&to_date=${toDate}&program=${activeProgram}`);
                const result = await response.json();
                if (result.success) {
                    this.stats = result.data;
                    this.pagination = result.data.pagination || null;
                } else {
                    this.errorMsg = result.message || 'Có lỗi xảy ra';
                }
            } catch (error) {
                console.error('Error fetching cancellation stats:', error);
                this.errorMsg = 'Có lỗi xảy ra khi tải dữ liệu';
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        async fetchStats(period) {
            this.loading = true;
            FilterProgress.show();
            this.errorMsg = '';
            this.stats = null;
            this.pagination = null;
            this.detailFilter = null;
            this.searchQuery = '';
            this._currentFromDate = null;
            this._currentToDate = null;
            this._currentPeriod = period;
            try {
                const response = await fetch(`/api/cancellation-stats?period=${period}&program=${activeProgram}`);
                const result = await response.json();
                if (result.success) {
                    this.stats = result.data;
                    this.pagination = result.data.pagination || null;
                } else {
                    this.errorMsg = result.message || 'Có lỗi xảy ra';
                }
            } catch (error) {
                console.error('Error fetching cancellation stats:', error);
                this.errorMsg = 'Có lỗi xảy ra khi tải dữ liệu';
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },

        // Phase 99: Fetch details only (for filter/search/pagination without reloading summary)
        async _fetchDetails(page = 1) {
            this.detailsLoading = true;
            try {
                let url = '/api/cancellation-stats?';
                if (this._currentFromDate && this._currentToDate) {
                    url += `from_date=${this._currentFromDate}&to_date=${this._currentToDate}`;
                } else {
                    url += `period=${this._currentPeriod || 'today'}`;
                }
                url += `&page=${page}&per_page=50&program=${activeProgram}`;
                if (this.detailFilter !== null) {
                    url += `&user_type_filter=${this.detailFilter}`;
                }
                if (this.searchQuery) {
                    url += `&search=${encodeURIComponent(this.searchQuery)}`;
                }
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    this.stats.details = result.data.details;
                    this.pagination = result.data.pagination || null;
                }
            } catch (error) {
                console.error('Error fetching cancellation details:', error);
            } finally {
                this.detailsLoading = false;
            }
        },

        setUserTypeFilter(type) {
            this.detailFilter = type;
            this._fetchDetails(1);
        },

        searchDetails() {
            this._fetchDetails(1);
        },

        goToPage(page) {
            if (!this.pagination) return;
            if (page < 1 || page > this.pagination.last_page) return;
            this._fetchDetails(page);
        },

        paginationPages() {
            if (!this.pagination) return [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const pages = [];
            if (last <= 7) {
                for (let i = 1; i <= last; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                    pages.push(i);
                }
                if (current < last - 2) pages.push('...');
                pages.push(last);
            }
            return pages;
        },

        async exportToExcel() {
            if (!this.stats || this.stats.total === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }

            // Fetch ALL records (no pagination) for export
            let allDetails = [];
            try {
                let url = '/api/cancellation-stats?';
                if (this._currentFromDate && this._currentToDate) {
                    url += `from_date=${this._currentFromDate}&to_date=${this._currentToDate}`;
                } else {
                    url += `period=${this._currentPeriod || 'today'}`;
                }
                // Fetch all records by using a very large per_page
                const totalRecords = this.pagination ? this.pagination.total : (this.stats.total || 0);
                url += `&page=1&per_page=${Math.max(totalRecords, 100)}&program=${activeProgram}`;
                if (this.detailFilter !== null) {
                    url += `&user_type_filter=${this.detailFilter}`;
                }
                if (this.searchQuery) {
                    url += `&search=${encodeURIComponent(this.searchQuery)}`;
                }
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    allDetails = result.data.details || [];
                }
            } catch (error) {
                console.error('Error fetching all data for export:', error);
                // Fallback: export current page
                allDetails = this.stats.details || [];
            }

            if (allDetails.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }

            const BOM = '\uFEFF';
            const headers = ['STT', 'Loại lớp', 'Thời gian hủy', 'Ca học', 'Giáo viên', 'Email GV', 'Học viên', 'Email HV', 'Người hủy', 'Lý do'];
            let csvContent = headers.join(',') + '\n';

            allDetails.forEach((item, index) => {
                const row = [
                    index + 1,
                    item.class_type || '1:1',
                    item.cancelled_at || '',
                    item.lesson_time || '',
                    item.teacher_name || '',
                    item.teacher_email || '',
                    item.student_name || '',
                    item.student_email || '',
                    item.user_type_label || '',
                    item.comment || ''
                ].map(cell => {
                    const cellStr = String(cell);
                    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                        return '"' + cellStr.replace(/"/g, '""') + '"';
                    }
                    return cellStr;
                });
                csvContent += row.join(',') + '\n';
            });

            const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            const periodLabel = this.activePeriod.replace(/\s/g, '_');
            link.setAttribute('href', url);
            link.setAttribute('download', `Huy_ca_hoc_${periodLabel}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
}
