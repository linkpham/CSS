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

    // Linear regression helper function for trend lines
    function calculateLinearRegression(data) {
        const n = data.length;
        if (n === 0) return [];
        
        let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
        
        for (let i = 0; i < n; i++) {
            const y = data[i] || 0;
            sumX += i;
            sumY += y;
            sumXY += i * y;
            sumX2 += i * i;
        }
        
        const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
        const intercept = (sumY - slope * sumX) / n;
        
        // Generate trend line data points
        const trendLine = [];
        for (let i = 0; i < n; i++) {
            trendLine.push(parseFloat((slope * i + intercept).toFixed(2)));
        }
        
        return trendLine;
    }

    @if(session('can_view_revenue'))
    // Revenue Chart
    const revenueEl = document.getElementById('revenueChart');
    if (revenueEl) {
        const revenueCtx = revenueEl.getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($revenueChart['labels']) !!},
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: {!! json_encode($revenueChart['values']) !!},
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
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
                                return value.toLocaleString('vi-VN');
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
                }
            }
        });
    }
    @endif

    // User Registration Chart
    const userEl = document.getElementById('userChart');
    if (userEl) {
        const userCtx = userEl.getContext('2d');
        new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($userChart['labels']) !!},
                datasets: [{
                    label: 'Đăng ký mới',
                    data: {!! json_encode($userChart['values']) !!},
                    backgroundColor: 'rgba(34, 197, 94, 0.6)',
                    borderColor: '#22c55e',
                    borderWidth: 1,
                    borderRadius: 4
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
                            color: textColor
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
                }
            }
        });
    }

    // Login Trend Chart
    const loginEl = document.getElementById('loginTrendChart');
    if (loginEl) {
        const loginCtx = loginEl.getContext('2d');
        new Chart(loginCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($loginTrendChart['labels']) !!},
                datasets: [{
                    label: 'Tổng đăng nhập',
                    data: {!! json_encode($loginTrendChart['datasets']['total_logins']) !!},
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }, {
                    label: 'User duy nhất',
                    data: {!! json_encode($loginTrendChart['datasets']['unique_users']) !!},
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
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
                }
            }
        });
    }

    // Trial Lessons Trend Chart
    const trialTrendEl = document.getElementById('trialTrendChart');
    if (trialTrendEl) {
        const trialTrendCtx = trialTrendEl.getContext('2d');
        new Chart(trialTrendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($trialTrendChart['labels']) !!},
                datasets: [
                    {
                        label: 'Đã lên lịch',
                        data: {!! json_encode($trialTrendChart['datasets']['scheduled']) !!},
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        hoverBackgroundColor: 'rgba(245, 158, 11, 1)',
                        borderRadius: 4,
                        borderSkipped: false
                    },
                    {
                        label: 'Hoàn thành',
                        data: {!! json_encode($trialTrendChart['datasets']['completed']) !!},
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        hoverBackgroundColor: 'rgba(34, 197, 94, 1)',
                        borderRadius: 4,
                        borderSkipped: false
                    },
                    {
                        label: 'Đã hủy',
                        data: {!! json_encode($trialTrendChart['datasets']['cancelled']) !!},
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        hoverBackgroundColor: 'rgba(239, 68, 68, 1)',
                        borderRadius: 4,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#e5e7eb',
                        borderColor: 'rgba(75, 85, 99, 0.5)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Ngày ' + tooltipItems[0].label;
                            },
                            footer: function(tooltipItems) {
                                let total = 0;
                                tooltipItems.forEach(item => total += item.raw);
                                return 'Tổng: ' + total + ' trials';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: gridColor, display: false },
                        ticks: { color: textColor, font: { size: 10 } }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, stepSize: 1 }
                    }
                }
            }
        });
    }

    // Trial Status Pie Chart
    const trialStatusEl = document.getElementById('trialStatusChart');
    if (trialStatusEl) {
        const trialStatusCtx = trialStatusEl.getContext('2d');
        const trialTotal = {{ ($trialByStatus['unscheduled'] ?? 0) + ($trialByStatus['scheduled'] ?? 0) + ($trialByStatus['completed'] ?? 0) + ($trialByStatus['cancelled'] ?? 0) }};
        new Chart(trialStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chưa lên lịch', 'Đã lên lịch', 'Hoàn thành', 'Đã hủy'],
                datasets: [{
                    data: [
                        {{ $trialByStatus['unscheduled'] ?? 0 }},
                        {{ $trialByStatus['scheduled'] ?? 0 }},
                        {{ $trialByStatus['completed'] ?? 0 }},
                        {{ $trialByStatus['cancelled'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(107, 114, 128, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(34, 197, 94, 0.85)',
                        'rgba(239, 68, 68, 0.85)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(107, 114, 128, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    borderColor: isDarkMode ? '#1e293b' : '#ffffff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        display: false  // We have custom legend above
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#e5e7eb',
                        borderColor: 'rgba(75, 85, 99, 0.5)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = trialTotal > 0 ? Math.round(value / trialTotal * 100) : 0;
                                return context.label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Login by Hour Chart
    const loginByHourEl = document.getElementById('loginByHourChart');
    if (loginByHourEl) {
        const loginByHourCtx = loginByHourEl.getContext('2d');
        new Chart(loginByHourCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($loginsByHour['labels']) !!},
                datasets: [{
                    label: 'Số lần đăng nhập',
                    data: {!! json_encode($loginsByHour['data']) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: '#3B82F6',
                    borderWidth: 1,
                    borderRadius: 2
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
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: textColor,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Login by Day of Week Chart
    const loginByDayEl = document.getElementById('loginByDayOfWeekChart');
    if (loginByDayEl) {
        const loginByDayCtx = loginByDayEl.getContext('2d');
        new Chart(loginByDayCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($loginsByDayOfWeek['labels']) !!},
                datasets: [{
                    label: 'Số lần đăng nhập',
                    data: {!! json_encode($loginsByDayOfWeek['data']) !!},
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.6)',  // Sunday
                        'rgba(59, 130, 246, 0.6)', // Monday
                        'rgba(16, 185, 129, 0.6)', // Tuesday
                        'rgba(245, 158, 11, 0.6)', // Wednesday
                        'rgba(139, 92, 246, 0.6)', // Thursday
                        'rgba(236, 72, 153, 0.6)', // Friday
                        'rgba(20, 184, 166, 0.6)'  // Saturday
                    ],
                    borderWidth: 0,
                    borderRadius: 4
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
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // Monthly Session Trend Charts - Success & Cancel Rate
    @if(isset($monthlySessionTrendChart))
    const successCancelRateEl = document.getElementById('successCancelRateChart');
    if (successCancelRateEl) {
        const successCancelRateCtx = successCancelRateEl.getContext('2d');
        
        // Get data for trend line calculations
        const successRateData = {!! json_encode($monthlySessionTrendChart['datasets']['success_rate']) !!};
        const cancelRateData = {!! json_encode($monthlySessionTrendChart['datasets']['cancel_rate']) !!};
        
        // Calculate trend lines
        const successRateTrend = calculateLinearRegression(successRateData);
        const cancelRateTrend = calculateLinearRegression(cancelRateData);
        
        new Chart(successCancelRateCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlySessionTrendChart['labels']) !!},
                datasets: [
                    {
                        label: 'Tỷ lệ TC (%)',
                        data: successRateData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#22c55e'
                    },
                    {
                        label: 'Xu hướng TC',
                        data: successRateTrend,
                        borderColor: '#22c55e',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    },
                    {
                        label: 'Tỷ lệ Hủy (%)',
                        data: cancelRateData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#f59e0b'
                    },
                    {
                        label: 'Xu hướng Hủy',
                        data: cancelRateTrend,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: gridColor },
                        ticks: { 
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: textColor,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    // Monthly Session Trend Charts - No-show Rate
    const noShowRateEl = document.getElementById('noShowRateChart');
    if (noShowRateEl) {
        const noShowRateCtx = noShowRateEl.getContext('2d');
        
        // Get data for trend line calculations
        const teacherNoShowData = {!! json_encode($monthlySessionTrendChart['datasets']['teacher_no_show_rate']) !!};
        const studentNoShowData = {!! json_encode($monthlySessionTrendChart['datasets']['student_no_show_rate']) !!};
        
        // Calculate trend lines
        const teacherNoShowTrend = calculateLinearRegression(teacherNoShowData);
        const studentNoShowTrend = calculateLinearRegression(studentNoShowData);
        
        new Chart(noShowRateCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlySessionTrendChart['labels']) !!},
                datasets: [
                    {
                        label: 'GV No-show (%)',
                        data: teacherNoShowData,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#f97316'
                    },
                    {
                        label: 'Xu hướng GV',
                        data: teacherNoShowTrend,
                        borderColor: '#f97316',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    },
                    {
                        label: 'HV No-show (%)',
                        data: studentNoShowData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#ef4444'
                    },
                    {
                        label: 'Xu hướng HV',
                        data: studentNoShowTrend,
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { 
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: textColor,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    @endif

    // Monthly Acceptance Codes Trend Chart (Chargeable vs Compensate)
    @if(isset($monthlyAcceptanceCodesTrendChart) && !empty($monthlyAcceptanceCodesTrendChart['labels']))
    const acceptanceCodesTrendEl = document.getElementById('acceptanceCodesTrendChart');
    if (acceptanceCodesTrendEl) {
        const acceptanceCodesTrendCtx = acceptanceCodesTrendEl.getContext('2d');
        
        // Get data
        const chargeableData = {!! json_encode($monthlyAcceptanceCodesTrendChart['datasets']['chargeable']) !!};
        const compensateData = {!! json_encode($monthlyAcceptanceCodesTrendChart['datasets']['compensate']) !!};
        
        // Calculate trend lines
        const chargeableTrend = calculateLinearRegression(chargeableData);
        const compensateTrend = calculateLinearRegression(compensateData);
        
        new Chart(acceptanceCodesTrendCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyAcceptanceCodesTrendChart['labels']) !!},
                datasets: [
                    {
                        label: 'Có tính phí HV (Mã 4-12, 16, 17)',
                        data: chargeableData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#22c55e'
                    },
                    {
                        label: 'Xu hướng tính phí',
                        data: chargeableTrend,
                        borderColor: '#22c55e',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    },
                    {
                        label: 'Phải bù buổi (Mã 1-3, 13-15)',
                        data: compensateData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#ef4444'
                    },
                    {
                        label: 'Xu hướng bù buổi',
                        data: compensateTrend,
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        pointHoverRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' ca';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { 
                            color: textColor,
                            callback: function(value) {
                                return value + ' ca';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: textColor,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    @endif

    // Never Logged In Students Trend Chart
    @if(isset($neverLoggedInTrendChart) && !empty($neverLoggedInTrendChart['labels']))
    const neverLoggedInTrendEl = document.getElementById('neverLoggedInTrendChart');
    if (neverLoggedInTrendEl) {
        const neverLoggedInTrendCtx = neverLoggedInTrendEl.getContext('2d');
        new Chart(neverLoggedInTrendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($neverLoggedInTrendChart['labels']) !!},
                datasets: [
                    {
                        label: 'Số HV chưa đăng nhập',
                        data: {!! json_encode($neverLoggedInTrendChart['datasets']['counts']) !!},
                        backgroundColor: 'rgba(147, 51, 234, 0.6)',
                        borderColor: '#9333ea',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Tỷ lệ (%)',
                        data: {!! json_encode($neverLoggedInTrendChart['datasets']['rates']) !!},
                        type: 'line',
                        borderColor: '#ec4899',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#ec4899',
                        order: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.yAxisID === 'y1') {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { 
                            color: textColor,
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Số lượng',
                            color: textColor
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: { 
                            drawOnChartArea: false 
                        },
                        ticks: { 
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Tỷ lệ (%)',
                            color: textColor
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: textColor,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    @endif
} // End of chart initialization block

// Dashboard Period Filter
function dashboardFilter() {
    return {
        period: 'all',
        periodStats: null,
        loading: false,
        
        get periodLabel() {
            const labels = {
                'today': 'Hôm nay',
                'yesterday': 'Hôm qua',
                'week': 'Tuần này',
                'month': 'Tháng này',
                'all': 'Tất cả'
            };
            return labels[this.period] || 'Tất cả';
        },
        
        setPeriod(period) {
            this.period = period;
            this.fetchPeriodStats();
        },
        
        async fetchPeriodStats() {
            this.loading = true;
            FilterProgress.show();
            try {
                const response = await fetch(`/api/period-stats?period=${this.period}&program=${activeProgram}`);
                const data = await response.json();
                if (data.success) {
                    this.periodStats = data.data;
                }
            } catch (error) {
                console.error('Error fetching period stats:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        },
        
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toLocaleString('vi-VN');
        },
        
        init() {
            // Load initial stats when page loads
            this.fetchPeriodStats();
        }
    }
}

// Comparison Menu for comparing periods (today vs yesterday, week vs week, month vs month)
function comparisonMenu() {
    return {
        loading: true,
        compData: null,
        
        async loadComparison() {
            this.loading = true;
            FilterProgress.show();
            try {
                const response = await fetch(`/api/comparison-stats?program=${activeProgram}`);
                const result = await response.json();
                if (result.success) {
                    this.compData = result.data;
                }
            } catch (error) {
                console.error('Error loading comparison stats:', error);
            } finally {
                this.loading = false;
                FilterProgress.hide();
            }
        }
    }
}

// Session Export Modal - handles exporting session stats by month range or date range
function sessionExportModal() {
    // Default to last month for "from" and current month for "to" (monthly)
    const now = new Date();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
    const lastWeek = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 7);
    
    const formatMonth = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        return `${year}-${month}`;
    };
    
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    return {
        showExport: false,
        exportType: 'daily', // 'monthly' or 'daily'
        exportFromMonth: formatMonth(lastMonth),
        exportToMonth: formatMonth(now),
        exportFromDate: formatDate(lastWeek),
        exportToDate: formatDate(yesterday),
        exportLoading: false,
        exportError: '',
        
        async exportSessionStats() {
            if (this.exportType === 'monthly') {
                await this.exportMonthlyStats();
            } else {
                await this.exportDailyStats();
            }
        },
        
        async exportMonthlyStats() {
            // Validate
            if (!this.exportFromMonth || !this.exportToMonth) {
                this.exportError = 'Vui lòng chọn khoảng thời gian';
                return;
            }
            
            if (this.exportFromMonth > this.exportToMonth) {
                this.exportError = 'Tháng bắt đầu phải trước hoặc bằng tháng kết thúc';
                return;
            }
            
            this.exportLoading = true;
            this.exportError = '';
            
            try {
                const response = await fetch(`/api/export-session-stats?from=${this.exportFromMonth}&to=${this.exportToMonth}&program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    // Generate CSV content
                    const data = result.data;
                    const BOM = '\uFEFF';
                    let csvContent = BOM;
                    
                    // Header
                    csvContent += 'Tháng,Tổng ca học,Buổi có tính phí,Thành công (code 12),GV No-show\n';
                    
                    // Data rows
                    data.forEach(row => {
                        csvContent += `${row.month_label},${row.total_sessions},${row.chargeable_sessions},${row.successful_sessions},${row.teacher_no_show}\n`;
                    });
                    
                    // Download
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', `session_stats_monthly_${this.exportFromMonth}_to_${this.exportToMonth}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    
                    this.showExport = false;
                } else {
                    this.exportError = result.message || 'Có lỗi xảy ra khi xuất dữ liệu';
                }
            } catch (error) {
                console.error('Error exporting session stats:', error);
                this.exportError = 'Có lỗi xảy ra khi xuất dữ liệu';
            } finally {
                this.exportLoading = false;
            }
        },
        
        async exportDailyStats() {
            // Validate
            if (!this.exportFromDate || !this.exportToDate) {
                this.exportError = 'Vui lòng chọn khoảng thời gian';
                return;
            }
            
            if (this.exportFromDate > this.exportToDate) {
                this.exportError = 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc';
                return;
            }
            
            this.exportLoading = true;
            this.exportError = '';
            
            try {
                const response = await fetch(`/api/export-daily-session-stats?from=${this.exportFromDate}&to=${this.exportToDate}&program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    // Generate CSV content
                    const data = result.data;
                    const BOM = '\uFEFF';
                    let csvContent = BOM;
                    
                    // Header
                    csvContent += 'Ngày,Tổng ca học,Buổi có tính phí,Thành công (code 12),GV No-show\n';
                    
                    // Data rows
                    data.forEach(row => {
                        csvContent += `${row.date_label},${row.total_sessions},${row.chargeable_sessions},${row.successful_sessions},${row.teacher_no_show}\n`;
                    });
                    
                    // Download
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', `session_stats_daily_${this.exportFromDate}_to_${this.exportToDate}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    
                    this.showExport = false;
                } else {
                    this.exportError = result.message || 'Có lỗi xảy ra khi xuất dữ liệu';
                }
            } catch (error) {
                console.error('Error exporting daily session stats:', error);
                this.exportError = 'Có lỗi xảy ra khi xuất dữ liệu';
            } finally {
                this.exportLoading = false;
            }
        }
    }
}

// Never Logged In Students Section - handles modals, data fetching, and exports
function neverLoggedInSection() {
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
        multiLessonCount: 0,
        currentPeriod: 'today',
        
        // Period labels for display
        periodLabels: {
            'today': 'Hôm nay',
            'yesterday': 'Hôm qua',
            'day_before_yesterday': 'Hôm kia',
            'this_week': 'Tuần này',
            'last_week': 'Tuần trước',
            'this_month': 'Tháng này',
            'last_month': 'Tháng trước'
        },
        
        // Open Never Logged In Modal and fetch data
        async openNeverLoggedInModal(period) {
            this.currentPeriod = period;
            this.showNeverLoggedInModal = true;
            this.neverLoggedInLoading = true;
            this.neverLoggedInStudents = [];
            
            try {
                const response = await fetch(`/api/never-logged-in-students?period=${period}&program=${activeProgram}`);
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
        async openMultiLessonModal(period) {
            this.currentPeriod = period;
            this.showMultiLessonModal = true;
            this.multiLessonLoading = true;
            this.multiLessonStudents = [];
            this.multiLessonCount = 0;
            
            try {
                const response = await fetch(`/api/students-multiple-lessons?period=${period}&program=${activeProgram}`);
                const result = await response.json();
                if (result.success && result.data) {
                    this.multiLessonStudents = result.data.students || [];
                    this.multiLessonCount = result.data.count || 0;
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
                    // Show brief success notification
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
                    this.fallbackCopyToClipboard(text);
                });
            } else {
                this.fallbackCopyToClipboard(text);
            }
        },
        
        // Fallback copy method for older browsers
        fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Đã copy SQL query!');
            } catch (err) {
                console.error('Fallback copy failed:', err);
            }
            document.body.removeChild(textArea);
        },
        
        // Export Never Logged In students to Excel (CSV format)
        exportNeverLoggedInToExcel() {
            if (this.neverLoggedInStudents.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.periodLabels[this.currentPeriod] || this.currentPeriod;
            const headers = ['STT', 'Username', 'Họ tên', 'Email', 'SĐT'];
            const rows = this.neverLoggedInStudents.map((student, index) => [
                index + 1,
                student.username || '',
                student.name || '',
                student.email || '',
                student.phone || ''
            ]);
            
            this.downloadCSV(headers, rows, `HV_chua_dang_nhap_${periodLabel.replace(/\s/g, '_')}`);
        },
        
        // Export Multi-Lesson students to Excel (CSV format)
        exportMultiLessonToExcel() {
            if (this.multiLessonStudents.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.periodLabels[this.currentPeriod] || this.currentPeriod;
            const headers = ['STT', 'Username', 'Họ tên', 'Email', 'SĐT', 'Ngày học', 'Số ca', 'Khung giờ'];
            const rows = this.multiLessonStudents.map((student, index) => [
                index + 1,
                student.username || '',
                student.name || '',
                student.email || '',
                student.phone || '',
                student.lesson_date || '',
                student.lesson_count || '',
                student.time_slots_display || (student.time_slots ? student.time_slots.join(', ') : '')
            ]);
            
            this.downloadCSV(headers, rows, `HV_co_nhieu_ca_hoc_${periodLabel.replace(/\s/g, '_')}`);
        },
        
        // Helper function to download CSV
        downloadCSV(headers, rows, filename) {
            // Add BOM for Excel to recognize UTF-8
            const BOM = '\uFEFF';
            
            // Build CSV content
            let csvContent = headers.join(',') + '\n';
            rows.forEach(row => {
                const escapedRow = row.map(cell => {
                    // Escape quotes and wrap in quotes if contains comma or quotes
                    const cellStr = String(cell);
                    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                        return '"' + cellStr.replace(/"/g, '""') + '"';
                    }
                    return cellStr;
                });
                csvContent += escapedRow.join(',') + '\n';
            });
            
            // Create and trigger download
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

// Penalized Teachers Section - handles penalized teachers modal and export
function penalizedTeachersSection() {
    return {
        // Existing code lookup modal state
        showCodeModal: false,
        
        // Penalized modal states
        showPenalizedModal: false,
        penalizedLoading: false,
        penalizedTeachers: [],
        penalizedPeriod: 'today',
        
        // Period labels
        penalizedPeriodLabels: {
            'today': 'Hôm nay',
            'yesterday': 'Hôm qua',
            'day_before_yesterday': 'Hôm kia',
            'this_week': 'Tuần này',
            'last_week': 'Tuần trước',
            'this_month': 'Tháng này',
            'last_month': 'Tháng trước'
        },
        
        // Open Penalized Teachers Modal
        async openPenalizedModal(period) {
            this.penalizedPeriod = period;
            this.showPenalizedModal = true;
            this.penalizedLoading = true;
            this.penalizedTeachers = [];
            
            try {
                const response = await fetch(`/api/penalized-teachers-details?period=${period}&program=${activeProgram}`);
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
        
        // Export Penalized Teachers to Excel (CSV format)
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
            
            // CSV download with BOM for Excel UTF-8 support
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

// Teacher Login Status Section - handles no-show, late entry, and early exit modals
function teacherLoginStatusSection() {
    return {
        // Modal states
        showNoShowModal: false,
        showLateEntryModal: false,
        showEarlyExitModal: false,
        
        // Loading states
        noShowLoading: false,
        lateEntryLoading: false,
        earlyExitLoading: false,
        
        // Data
        noShowTeachers: [],
        lateEntryTeachers: [],
        earlyExitTeachers: [],
        currentPeriod: 'today',
        
        // Period labels
        periodLabels: {
            'today': 'Hôm nay',
            'yesterday': 'Hôm qua',
            'day_before_yesterday': 'Hôm kia',
            'this_week': 'Tuần này',
            'last_week': 'Tuần trước',
            'this_month': 'Tháng này',
            'last_month': 'Tháng trước'
        },
        
        // Open No-Show Modal
        async openNoShowModal(period) {
            this.currentPeriod = period;
            this.showNoShowModal = true;
            this.noShowLoading = true;
            this.noShowTeachers = [];
            
            try {
                const response = await fetch(`/api/teacher-no-show-details?period=${period}&program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    this.noShowTeachers = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching teacher no-show details:', error);
            } finally {
                this.noShowLoading = false;
            }
        },
        
        // Open Late Entry Modal
        async openLateEntryModal(period) {
            this.currentPeriod = period;
            this.showLateEntryModal = true;
            this.lateEntryLoading = true;
            this.lateEntryTeachers = [];
            
            try {
                const response = await fetch(`/api/teacher-late-entry-details?period=${period}&program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    this.lateEntryTeachers = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching teacher late entry details:', error);
            } finally {
                this.lateEntryLoading = false;
            }
        },
        
        // Open Early Exit Modal
        async openEarlyExitModal(period) {
            this.currentPeriod = period;
            this.showEarlyExitModal = true;
            this.earlyExitLoading = true;
            this.earlyExitTeachers = [];
            
            try {
                const response = await fetch(`/api/teacher-early-exit-details?period=${period}&program=${activeProgram}`);
                const result = await response.json();
                
                if (result.success) {
                    this.earlyExitTeachers = result.data || [];
                }
            } catch (error) {
                console.error('Error fetching teacher early exit details:', error);
            } finally {
                this.earlyExitLoading = false;
            }
        },
        
        // Export No-Show to Excel
        exportNoShowToExcel() {
            if (this.noShowTeachers.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.periodLabels[this.currentPeriod] || this.currentPeriod;
            const headers = ['STT', 'Giáo viên', 'Email GV', 'Học viên', 'Ngày', 'Giờ', 'Thời lượng (phút)'];
            const rows = this.noShowTeachers.map((item, index) => [
                index + 1,
                item.teacher_name || '',
                item.teacher_email || '',
                item.student_name || '',
                item.lesson_date || '',
                item.lesson_time || '',
                item.duration || ''
            ]);
            
            this.downloadCSV(headers, rows, `GV_No-show_${periodLabel.replace(/\s/g, '_')}`);
        },
        
        // Export Late Entry to Excel
        exportLateEntryToExcel() {
            if (this.lateEntryTeachers.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.periodLabels[this.currentPeriod] || this.currentPeriod;
            const headers = ['STT', 'Giáo viên', 'Email GV', 'Học viên', 'Ngày', 'Giờ lịch', 'Giờ vào', 'Trễ (phút)'];
            const rows = this.lateEntryTeachers.map((item, index) => [
                index + 1,
                item.teacher_name || '',
                item.teacher_email || '',
                item.student_name || '',
                item.lesson_date || '',
                item.scheduled_time || '',
                item.actual_join_time || '',
                item.late_minutes || ''
            ]);
            
            this.downloadCSV(headers, rows, `GV_Vao_tre_${periodLabel.replace(/\s/g, '_')}`);
        },
        
        // Export Early Exit to Excel
        exportEarlyExitToExcel() {
            if (this.earlyExitTeachers.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            const periodLabel = this.periodLabels[this.currentPeriod] || this.currentPeriod;
            const headers = ['STT', 'Giáo viên', 'Email GV', 'Học viên', 'Ngày', 'Giờ bắt đầu', 'Giờ kết thúc dự kiến', 'Giờ ra thực tế', 'Ra sớm (phút)'];
            const rows = this.earlyExitTeachers.map((item, index) => [
                index + 1,
                item.teacher_name || '',
                item.teacher_email || '',
                item.student_name || '',
                item.lesson_date || '',
                item.scheduled_time || '',
                item.expected_end_time || '',
                item.actual_leave_time || '',
                item.early_minutes || ''
            ]);
            
            this.downloadCSV(headers, rows, `GV_Ra_som_${periodLabel.replace(/\s/g, '_')}`);
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

// Session Stats Filter with Custom Date Picker (Phase 97: added week/month pickers and date range)
function sessionStatsFilter() {
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

    // Build available weeks (week 1 to current week)
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

    // Build available months (month 1 to current month)
    const months = [];
    const currentMonth = today.getMonth() + 1; // 1-indexed
    for (let m = currentMonth; m >= 1; m--) {
        const mStart = new Date(currentYear, m - 1, 1);
        const mEnd = new Date(currentYear, m, 0); // last day of month
        // If it's the current month, cap end to today
        const cappedEnd = (m === currentMonth && mEnd > today) ? today : mEnd;
        months.push({
            month: m,
            start: fmtYMD(mStart),
            end: fmtYMD(cappedEnd),
            rangeLabel: fmtDM(mStart) + ' - ' + fmtDM(cappedEnd)
        });
    }

    return {
        activeTab: 'today',
        showDatePicker: false,
        showWeekPicker: false,
        showMonthPicker: false,
        // Phase 97: date range instead of single date
        customFromDate: '',
        customToDate: new Date().toISOString().split('T')[0],
        customDate: '', // kept for backward compat in template conditions
        customDateFormatted: '',
        customStats: null,
        customLoading: false,
        customDateError: '',
        maxDate: new Date().toISOString().split('T')[0],
        // Phase 97: Week picker state
        availableWeeks: weeks,
        pickedWeekNum: null,
        pickedWeekLabel: '',
        // Phase 97: Month picker state
        availableMonths: months,
        pickedMonthNum: null,
        pickedMonthLabel: '',

        // Phase 97: Select a week
        selectWeek(w) {
            this.pickedWeekNum = w.weekNum;
            this.pickedWeekLabel = 'T' + w.weekNum + ': ' + w.rangeLabel;
            this.showWeekPicker = false;
            this.activeTab = 'custom';
            this._loadDateRangeStats(w.start, w.end, this.pickedWeekLabel);
        },

        // Phase 97: Select a month
        selectMonth(m) {
            this.pickedMonthNum = m.month;
            this.pickedMonthLabel = 'Tháng ' + m.month + ': ' + m.rangeLabel;
            this.showMonthPicker = false;
            this.activeTab = 'custom';
            this._loadDateRangeStats(m.start, m.end, this.pickedMonthLabel);
        },

        // Phase 97: Load stats for custom date range (Tùy chọn)
        async loadCustomDateStats() {
            if (!this.customFromDate || !this.customToDate) {
                return;
            }
            if (this.customFromDate > this.customToDate) {
                this.customDateError = 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc';
                return;
            }
            this.showDatePicker = false;
            const fromObj = new Date(this.customFromDate);
            const toObj = new Date(this.customToDate);
            const label = fromObj.toLocaleDateString('vi-VN') + ' - ' + toObj.toLocaleDateString('vi-VN');
            this.activeTab = 'custom';
            this._loadDateRangeStats(this.customFromDate, this.customToDate, label);
        },

        // Internal: fetch stats for a date range
        async _loadDateRangeStats(fromDate, toDate, label) {
            this.customLoading = true;
            this.customDateError = '';
            this.customStats = null;
            this.customDateFormatted = label;
            this.customDate = fromDate; // set truthy for template condition

            try {
                const response = await fetch(`/api/custom-date-session-stats?from_date=${fromDate}&to_date=${toDate}&program=${activeProgram}`);
                const result = await response.json();

                if (result.success) {
                    this.customStats = result.data;
                } else {
                    this.customDateError = result.message || 'Có lỗi xảy ra khi tải dữ liệu';
                }
            } catch (error) {
                console.error('Error fetching custom date stats:', error);
                this.customDateError = 'Có lỗi xảy ra khi tải dữ liệu';
            } finally {
                this.customLoading = false;
            }
        },

        // Format number with thousand separators
        formatNumber(num) {
            return num.toLocaleString('vi-VN');
        }
    }
}
