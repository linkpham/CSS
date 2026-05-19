@extends('layouts.app')

@section('title', 'ICan Dashboard - Lộ trình Học tập')
@section('page-title', 'Lộ trình Học tập & Nhật ký GV')

@section('content')
@php
    $activeProgram = request('program', 'all');
@endphp

<div class="space-y-6">
    <!-- Program Tabs -->
    <x-program-tabs :activeProgram="$activeProgram" />
    
    <!-- Content -->
    
    <!-- Session Outcome Stats (NEW) -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📊 Tình trạng Ca học
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Thống kê theo ordles_status<br>Hôm nay/Tuần/Tháng theo thời gian</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $sessionOutcome['today']['total'] ?? 0 }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Hôm nay</p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $sessionOutcome['today']['completed'] ?? 0 }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Thành công</p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $sessionOutcome['today']['scheduled'] ?? 0 }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đang chờ</p>
            </div>
            <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $sessionOutcome['today']['cancelled'] ?? 0 }}</p>
                <p class="text-sm text-red-600/80 dark:text-red-400/80">Đã hủy</p>
            </div>
            <div class="text-center p-4 {{ ($sessionOutcome['today']['no_show'] ?? 0) > 0 ? 'bg-orange-500/10 border-orange-500/30' : 'bg-gray-500/5 border-gray-500/20' }} rounded-lg border">
                <p class="text-3xl font-bold {{ ($sessionOutcome['today']['no_show'] ?? 0) > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}">{{ $sessionOutcome['today']['no_show'] ?? 0 }}</p>
                <p class="text-sm {{ ($sessionOutcome['today']['no_show'] ?? 0) > 0 ? 'text-orange-600/80 dark:text-orange-400/80' : 'text-gray-600/80 dark:text-gray-400/80' }}">No-show</p>
            </div>
            <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $sessionOutcome['today']['success_rate'] ?? 0 }}%</p>
                <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ TK</p>
            </div>
        </div>
        <!-- Week/Month comparison -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tuần này</span>
                <div class="text-right">
                    <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ $sessionOutcome['this_week']['completed'] ?? 0 }}/{{ $sessionOutcome['this_week']['total'] ?? 0 }}</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-500/10 text-green-600 dark:text-green-400">{{ $sessionOutcome['this_week']['success_rate'] ?? 0 }}%</span>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tháng này</span>
                <div class="text-right">
                    <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ $sessionOutcome['this_month']['completed'] ?? 0 }}/{{ $sessionOutcome['this_month']['total'] ?? 0 }}</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-500/10 text-green-600 dark:text-green-400">{{ $sessionOutcome['this_month']['success_rate'] ?? 0 }}%</span>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-sm text-light-text-muted dark:text-zeus-text-muted">Tổng cộng</span>
                <div class="text-right">
                    <span class="text-lg font-bold text-light-text dark:text-zeus-text">{{ number_format($sessionOutcome['all_time']['completed'] ?? 0) }}/{{ number_format($sessionOutcome['all_time']['total'] ?? 0) }}</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-500/10 text-green-600 dark:text-green-400">{{ $sessionOutcome['all_time']['success_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Path Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-blue-500/50 transition shadow-sm">
            <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                📚 HV có Lộ trình
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_user_programs</span><br>COUNT DISTINCT user_id<br>Học viên đã được gán chương trình học</span></span>
            </p>
            <p class="text-4xl font-bold text-blue-500 mt-2">{{ number_format($learningPathStats['users_with_program'] ?? 0) }}</p>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">người đã gán lộ trình</p>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-green-500/50 transition shadow-sm">
            <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                ✅ Bài học Hoàn thành
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 3 (COMPLETED)<br>Tổng bài học đã hoàn thành trong lộ trình</span></span>
            </p>
            <p class="text-4xl font-bold text-green-500 mt-2">{{ number_format($learningPathStats['completed_sessions'] ?? 0) }}</p>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">
                {{ $learningPathStats['completion_rate'] ?? 0 }}% tỷ lệ hoàn thành
            </p>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-amber-500/50 transition shadow-sm">
            <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                📅 Bài học Upcoming
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 2 (SCHEDULED), ordles_lesson_starttime > NOW()<br>Bài học đã lên lịch sắp diễn ra</span></span>
            </p>
            <p class="text-4xl font-bold text-amber-500 mt-2">{{ number_format($learningPathStats['upcoming_sessions'] ?? 0) }}</p>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">sắp diễn ra</p>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border hover:border-red-500/50 transition shadow-sm">
            <p class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">
                ⏳ Bài học Incomplete
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 1 (UNSCHEDULED) hoặc bị hủy/no-show<br>Bài học chưa hoàn thành</span></span>
            </p>
            <p class="text-4xl font-bold text-red-500 mt-2">{{ number_format($learningPathStats['incomplete_sessions'] ?? 0) }}</p>
            <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">chưa hoàn thành</p>
        </div>
    </div>

    <!-- Session Status Distribution & Attendance Issues -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Session Distribution Pie -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📊 Phân bố Trạng thái Bài học
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>GROUP BY ordles_status<br>Phân bố: Hoàn thành, Sắp học, Chưa hoàn thành</span></span>
            </h3>
            <canvas id="sessionDistributionChart" height="200"></canvas>
        </div>

        <!-- Attendance Issues (NEW) -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                ⚠️ Vấn đề Tham gia (3 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>GV/HS không tham gia buổi học đã lên lịch<br>3 ngày gần nhất, sắp xếp theo thời gian</span></span>
            </h3>
            @if(!empty($attendanceIssues))
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-light-card dark:bg-zeus-card">
                        <tr class="border-b border-light-border dark:border-zeus-border">
                            <th class="text-left py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">GV</th>
                            <th class="text-left py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời gian</th>
                            <th class="text-left py-2 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Vấn đề</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendanceIssues as $issue)
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                            <td class="py-2 px-2 text-sm text-light-text dark:text-zeus-text">{{ $issue['teacher_name'] }}</td>
                            <td class="py-2 px-2 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $issue['start_time'] }}</td>
                            <td class="py-2 px-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-red-500/10 text-red-600 dark:text-red-400">{{ $issue['issue'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-center text-light-text-muted dark:text-zeus-text-muted py-8">Không có vấn đề tham gia 🎉</p>
            @endif
        </div>
    </div>

    <!-- Program Enrollment -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🎓 Chương trình Học tập
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_programs</span> + <span class="tooltip-table">tbl_curriculums</span><br>Chương trình: khóa học được thiết kế<br>Giáo trình: nội dung chi tiết từng bài</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $learningPathStats['total_programs'] ?? 0 }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Chương trình</p>
            </div>
            <div class="text-center p-4 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $learningPathStats['total_curriculums'] ?? 0 }}</p>
                <p class="text-sm text-indigo-600/80 dark:text-indigo-400/80">Giáo trình</p>
            </div>
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $programStats['total_enrollments'] ?? 0 }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Lượt ghi danh</p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $programStats['unique_learners'] ?? 0 }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">HV duy nhất</p>
            </div>
        </div>
        @if(!empty($programStats['programs']))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($programStats['programs'] as $program)
            <div class="flex justify-between items-center p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <span class="text-light-text dark:text-zeus-text text-sm truncate">{{ $program['title'] }}</span>
                <span class="px-3 py-1 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium">{{ $program['users_count'] }} HV</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Teacher Feedback Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📝 Nhật ký Học tập (Teacher Feedback)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_learner_records</span><br>Feedback GV gửi sau buổi học<br>lrnrec_status: 0=Chờ duyệt, 1=Đã duyệt</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($feedbackStats['total'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Feedback</p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($feedbackStats['pending'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Chờ duyệt</p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($feedbackStats['approved'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã duyệt</p>
            </div>
            <div class="text-center p-4 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($feedbackStats['trial'] ?? 0) }}</p>
                <p class="text-sm text-cyan-600/80 dark:text-cyan-400/80">Trial</p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($feedbackStats['regular'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Regular</p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ number_format($feedbackStats['today'] ?? 0) }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Hôm nay</p>
            </div>
            <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $feedbackStats['approval_rate'] ?? 0 }}%</p>
                <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ duyệt</p>
            </div>
        </div>
    </div>

    <!-- Feedback Status Alert -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🚨 Trạng thái Feedback
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_learner_records</span> + <span class="tooltip-table">tbl_order_lessons</span><br>GV chưa nộp: bài học COMPLETED nhưng chưa có feedback<br>Tỷ lệ đúng hạn: nộp trong 24h sau buổi học</span></span>
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'bg-red-500/10 border-red-500/30' : 'bg-green-500/10 border-green-500/30' }} rounded-lg border">
                    <p class="text-3xl font-bold {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format($feedbackStatus['pending_feedback'] ?? 0) }}</p>
                    <p class="text-sm {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'text-red-600/80 dark:text-red-400/80' : 'text-green-600/80 dark:text-green-400/80' }}">GV chưa nộp</p>
                </div>
                <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($feedbackStatus['submitted_today'] ?? 0) }}</p>
                    <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Nộp hôm nay</p>
                </div>
                <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($feedbackStatus['lessons_needing_feedback'] ?? 0) }}</p>
                    <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Bài cần feedback</p>
                </div>
                <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $feedbackStatus['on_time_rate'] ?? 0 }}%</p>
                    <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ đúng hạn</p>
                </div>
            </div>
        </div>

        <!-- Feedback Submission Trend Chart -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📈 Xu hướng Nộp Feedback (14 ngày)
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_learner_records</span><br>GROUP BY ngày, lrnrec_status<br>Số feedback đã nộp và chưa nộp theo ngày</span></span>
            </h3>
            <canvas id="feedbackTrendChart" height="160"></canvas>
        </div>
    </div>

    <!-- Top Teachers by Feedback & Recent Feedback -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Teachers by Feedback -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                🏆 Top GV theo Feedback
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_learner_records</span><br>GROUP BY lrnrec_teacher_id<br>Top GV nộp nhiều feedback nhất</span></span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-light-border dark:border-zeus-border">
                            <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">#</th>
                            <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Giáo viên</th>
                            <th class="text-right py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topTeachersByFeedback as $index => $teacher)
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                            <td class="py-3 px-2 text-light-text dark:text-zeus-text">
                                @if($index < 3)
                                <span class="text-lg">{{ ['🥇', '🥈', '🥉'][$index] }}</span>
                                @else
                                {{ $index + 1 }}
                                @endif
                            </td>
                            <td class="py-3 px-2">
                                <div class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $teacher['name'] }}</div>
                                <div class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['email'] }}</div>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <span class="px-3 py-1 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium">{{ $teacher['feedback_count'] }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Feedback with View Content -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                📋 Feedback Gần đây <span class="text-sm font-normal text-light-text-muted dark:text-zeus-text-muted">(nhấn để xem chi tiết)</span>
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_learner_records</span><br>Sắp xếp theo lrnrec_addedon DESC<br>LIMIT 10 feedback mới nhất</span></span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-light-border dark:border-zeus-border">
                            <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">GV / HV</th>
                            <th class="text-left py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Nội dung</th>
                            <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Loại</th>
                            <th class="text-center py-3 px-2 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentFeedback as $feedback)
                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition cursor-pointer" onclick="viewFeedback({{ $feedback['id'] }})">
                            <td class="py-3 px-2">
                                <div class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $feedback['teacher_name'] }}</div>
                                <div class="text-xs text-light-text-muted dark:text-zeus-text-muted">→ {{ $feedback['learner_name'] }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <div class="text-sm text-light-text-muted dark:text-zeus-text-muted max-w-xs truncate">{{ $feedback['content_preview'] }}</div>
                            </td>
                            <td class="py-3 px-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $feedback['type'] === 'Trial' ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400' : 'bg-purple-500/10 text-purple-600 dark:text-purple-400' }}">
                                    {{ $feedback['type'] }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-center">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($feedback['status'] === 'Đã duyệt') bg-green-500/10 text-green-600 dark:text-green-400
                                    @elseif($feedback['status'] === 'Chờ duyệt') bg-amber-500/10 text-amber-600 dark:text-amber-400
                                    @else bg-light-card-alt dark:bg-zeus-card-light text-light-text-muted dark:text-zeus-text-muted
                                    @endif">
                                    {{ $feedback['status'] }}
                                </span>
                            </td>
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

    <!-- Curriculum Session Chart -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📊 Bài học Lộ trình (30 ngày)
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>GROUP BY ngày, COUNT bài được tạo và hoàn thành<br>30 ngày gần nhất</span></span>
        </h3>
        <canvas id="sessionChart" height="100"></canvas>
    </div>
</div>

<!-- Feedback Detail Modal -->
<div id="feedbackModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-light-card dark:bg-zeus-card rounded-xl max-w-2xl w-full max-h-[90vh] overflow-hidden border border-light-border dark:border-zeus-border shadow-xl">
        <div class="flex justify-between items-center p-4 border-b border-light-border dark:border-zeus-border">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text">📝 Chi tiết Feedback</h3>
            <button onclick="closeFeedbackModal()" class="text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="feedbackModalContent" class="p-4 overflow-y-auto max-h-[70vh]">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        </div>
    </div>
    
</div>
@endsection

@push('scripts')
<script>
// Only initialize charts if we're on the SpeakWell tab (charts exist in DOM)
const activeProgram = '{{ $activeProgram ?? "all" }}';

{
    // Get theme-aware colors
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#9CA3AF' : '#64748B';
    const gridColor = isDarkMode ? '#2A2E35' : '#E2E8F0';

    // Dark theme chart defaults
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // Session Distribution Pie Chart
    const distributionEl = document.getElementById('sessionDistributionChart');
    if (distributionEl) {
        const distributionCtx = distributionEl.getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hoàn thành', 'Sắp học', 'Chưa hoàn thành'],
                datasets: [{
                    data: [
                        {{ $sessionDistribution['completed'] ?? 0 }},
                        {{ $sessionDistribution['upcoming'] ?? 0 }},
                        {{ $sessionDistribution['incomplete'] ?? 0 }}
                    ],
                    backgroundColor: [
                        '#22c55e',
                        '#f59e0b',
                        '#ef4444'
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

    // Feedback Trend Chart
    const feedbackTrendEl = document.getElementById('feedbackTrendChart');
    if (feedbackTrendEl) {
        const feedbackTrendCtx = feedbackTrendEl.getContext('2d');
        new Chart(feedbackTrendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($feedbackTrend['labels']) !!},
                datasets: [
                    {
                        label: 'Đã nộp',
                        data: {!! json_encode($feedbackTrend['datasets']['submitted']) !!},
                        backgroundColor: '#22c55e',
                        borderRadius: 4
                    },
                    {
                        label: 'Chưa nộp',
                        data: {!! json_encode($feedbackTrend['datasets']['pending']) !!},
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

    // Session Chart
    const sessionEl = document.getElementById('sessionChart');
    if (sessionEl) {
        const sessionCtx = sessionEl.getContext('2d');
        new Chart(sessionCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($sessionChart['labels']) !!},
                datasets: [
                    {
                        label: 'Bài học được tạo',
                        data: {!! json_encode($sessionChart['datasets']['created']) !!},
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    },
                    {
                        label: 'Bài học hoàn thành',
                        data: {!! json_encode($sessionChart['datasets']['completed']) !!},
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
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
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
} // End of chart initialization block

// Feedback Modal Functions (always available)
function viewFeedback(feedbackId) {
    const modal = document.getElementById('feedbackModal');
    const content = document.getElementById('feedbackModalContent');
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div></div>';
    
    fetch(`/api/feedback/${feedbackId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = renderFeedbackDetail(data.data);
            } else {
                content.innerHTML = '<p class="text-center text-red-500 py-8">Không thể tải feedback</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p class="text-center text-red-500 py-8">Lỗi khi tải feedback</p>';
        });
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('feedbackModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFeedbackModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFeedbackModal();
    }
});

function renderFeedbackDetail(feedback) {
    let lessonInfo = '';
    if (feedback.lesson_info) {
        lessonInfo = `
            <div class="mb-4 p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">📅 Thông tin buổi học</p>
                <p class="text-sm text-light-text dark:text-zeus-text mt-1">Thời gian: ${feedback.lesson_info.start_time}</p>
                <p class="text-sm text-light-text dark:text-zeus-text">Thời lượng: ${feedback.lesson_info.duration} phút</p>
                <p class="text-sm text-light-text dark:text-zeus-text">Trạng thái: ${feedback.lesson_info.status}</p>
            </div>
        `;
    }

    let valuesHtml = '';
    if (feedback.values && Object.keys(feedback.values).length > 0) {
        valuesHtml = '<div class="space-y-3">';
        for (const [key, value] of Object.entries(feedback.values)) {
            let displayValue = value;
            if (typeof value === 'object') {
                displayValue = JSON.stringify(value, null, 2);
            }
            const label = formatLabel(key);
            valuesHtml += `
                <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wide">${label}</p>
                    <p class="text-sm text-light-text dark:text-zeus-text mt-1 whitespace-pre-wrap">${escapeHtml(String(displayValue))}</p>
                </div>
            `;
        }
        valuesHtml += '</div>';
    } else {
        valuesHtml = '<p class="text-center text-light-text-muted dark:text-zeus-text-muted py-4">Không có nội dung chi tiết</p>';
    }

    return `
        <div class="space-y-4">
            <!-- Header Info -->
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Giáo viên</p>
                    <p class="text-sm font-medium text-light-text dark:text-zeus-text">${feedback.teacher_name}</p>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">${feedback.teacher_email}</p>
                </div>
                <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Học viên</p>
                    <p class="text-sm font-medium text-light-text dark:text-zeus-text">${feedback.learner_name}</p>
                    <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">${feedback.learner_email}</p>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 text-xs rounded-full ${feedback.type === 'Trial' ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400' : 'bg-purple-500/10 text-purple-600 dark:text-purple-400'}">${feedback.type}</span>
                <span class="px-3 py-1 text-xs rounded-full ${feedback.status === 'Đã duyệt' ? 'bg-green-500/10 text-green-600 dark:text-green-400' : feedback.status === 'Chờ duyệt' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-gray-500/10 text-gray-600 dark:text-gray-400'}">${feedback.status}</span>
                <span class="px-3 py-1 text-xs rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400">${feedback.record_type}</span>
                <span class="px-3 py-1 text-xs rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400">${feedback.created_at}</span>
            </div>

            ${lessonInfo}

            <!-- Feedback Content -->
            <div>
                <h4 class="text-sm font-semibold text-light-text dark:text-zeus-text mb-3">📝 Nội dung Feedback</h4>
                ${valuesHtml}
            </div>
        </div>
    `;
}

function formatLabel(key) {
    return key
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, str => str.toUpperCase())
        .trim();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
@endpush
