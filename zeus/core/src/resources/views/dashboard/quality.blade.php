@extends('layouts.app')

@section('title', 'ICan Dashboard - Chất lượng')
@section('page-title', 'Chất lượng Học tập')

@section('content')
@php
    $activeProgram = request('program', 'all');
@endphp

<div class="space-y-4 md:space-y-6">
    <!-- Program Tabs -->
    <x-program-tabs :activeProgram="$activeProgram" />
    
    <!-- Content -->
    
    <!-- Session Quality Summary (NEW) -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4">📊 Tổng quan Chất lượng Ca học ({{ $sessionQuality['period'] ?? '30 ngày' }})</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-4">
            <div class="text-center p-3 md:p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($sessionQuality['total_lessons'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">Tổng ca học <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng ca học</span><br>
Tổng số ca học đã lên lịch, hoàn thành hoặc đã hủy trong kỳ.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status IN (2,3,4)</span>
</span></span></p>
            </div>
            <div class="text-center p-3 md:p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-xl md:text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($sessionQuality['completed'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-green-600/80 dark:text-green-400/80">Thành công <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Thành công</span><br>
Số ca học đã hoàn thành thành công.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 3</span>
</span></span></p>
            </div>
            <div class="text-center p-3 md:p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                <p class="text-xl md:text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($sessionQuality['cancelled'] ?? 0) }}</p>
                <p class="text-xs md:text-sm text-red-600/80 dark:text-red-400/80">Đã hủy <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy</span><br>
Số ca học đã bị hủy bởi GV hoặc HS.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 4</span>
</span></span></p>
            </div>
            <div class="text-center p-3 md:p-4 {{ ($sessionQuality['missed'] ?? 0) > 0 ? 'bg-orange-500/10 border-orange-500/30' : 'bg-gray-500/5 border-gray-500/20' }} rounded-lg border">
                <p class="text-xl md:text-3xl font-bold {{ ($sessionQuality['missed'] ?? 0) > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}">{{ number_format($sessionQuality['missed'] ?? 0) }}</p>
                <p class="text-xs md:text-sm {{ ($sessionQuality['missed'] ?? 0) > 0 ? 'text-orange-600/80 dark:text-orange-400/80' : 'text-gray-600/80 dark:text-gray-400/80' }}">Missed/No-show <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Missed/No-show</span><br>
Ca học đã qua thời gian nhưng GV hoặc HS không tham gia.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 2<br>
AND ordles_endtime < NOW()<br>
AND (ordles_teacher_starttime IS NULL<br>
OR ordles_student_starttime IS NULL)</span>
</span></span></p>
            </div>
            <div class="text-center p-3 md:p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-xl md:text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $sessionQuality['completion_rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ thành công <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ thành công</span><br>
Phần trăm ca học hoàn thành trên tổng số ca học.<br><br>
<span class="tooltip-sql">= (completed / total) * 100</span>
</span></span></p>
            </div>
            <div class="text-center p-3 md:p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $sessionQuality['teacher_attendance_rate'] ?? 0 }}%</p>
                <p class="text-xs md:text-sm text-purple-600/80 dark:text-purple-400/80">GV tham gia <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">GV tham gia</span><br>
Tỷ lệ giáo viên có mặt trong ca học đã lên lịch.<br><br>
<span class="tooltip-sql">= (lessons_with_teacher / total_scheduled) * 100<br>
WHERE ordles_teacher_starttime IS NOT NULL</span>
</span></span></p>
            </div>
        </div>
        <!-- Trial vs Regular comparison -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center justify-between p-4 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                <div>
                    <span class="text-sm font-medium text-cyan-600 dark:text-cyan-400">Trial <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Trial</span><br>
Thống kê riêng cho Trial lessons (bài học thử).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 1</span>
</span></span></span>
                    <p class="text-xs text-cyan-600/60 dark:text-cyan-400/60">{{ $sessionQuality['trial']['completed'] ?? 0 }}/{{ $sessionQuality['trial']['total'] ?? 0 }} hoàn thành</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ $sessionQuality['trial']['rate'] ?? 0 }}%</span>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-violet-500/5 dark:bg-violet-500/10 rounded-lg border border-violet-500/20">
                <div>
                    <span class="text-sm font-medium text-violet-600 dark:text-violet-400">Regular <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Regular</span><br>
Thống kê riêng cho Regular lessons (bài học chính thức).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 2</span>
</span></span></span>
                    <p class="text-xs text-violet-600/60 dark:text-violet-400/60">{{ $sessionQuality['regular']['completed'] ?? 0 }}/{{ $sessionQuality['regular']['total'] ?? 0 }} hoàn thành</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ $sessionQuality['regular']['rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-green-500/50 transition shadow-sm">
            <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ hoàn thành <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ hoàn thành</span><br>
Phần trăm bài học hoàn thành trên tổng số bài học.<br><br>
<span class="tooltip-sql">= (completed / total) * 100<br>
FROM tbl_order_lessons</span>
</span></span></p>
            <p class="text-2xl md:text-4xl font-bold text-green-500 mt-2">{{ $lessonQuality['completion_rate'] ?? 0 }}%</p>
            <div class="w-full bg-light-card-alt dark:bg-zeus-card-light rounded-full h-2 mt-3">
                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $lessonQuality['completion_rate'] ?? 0 }}%"></div>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-red-500/50 transition shadow-sm">
            <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Tỷ lệ hủy <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ hủy</span><br>
Phần trăm bài học bị hủy trên tổng số bài học.<br><br>
<span class="tooltip-sql">= (cancelled / total) * 100<br>
FROM tbl_order_lessons</span>
</span></span></p>
            <p class="text-2xl md:text-4xl font-bold text-red-500 mt-2">{{ $lessonQuality['cancellation_rate'] ?? 0 }}%</p>
            <div class="w-full bg-light-card-alt dark:bg-zeus-card-light rounded-full h-2 mt-3">
                <div class="bg-red-500 h-2 rounded-full" style="width: {{ min($lessonQuality['cancellation_rate'] ?? 0, 100) }}%"></div>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-amber-500/50 transition shadow-sm">
            <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Đánh giá TB <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đánh giá TB</span><br>
Điểm đánh giá trung bình của học sinh cho giáo viên.<br><br>
<span class="tooltip-sql">SELECT AVG(ratrev_overall)<br>
FROM tbl_rating_reviews<br>
WHERE ratrev_status = 1</span>
</span></span></p>
            <p class="text-2xl md:text-4xl font-bold text-amber-500 mt-2">{{ $ratings['average_rating'] ?? 0 }} ⭐</p>
            <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">{{ $ratings['approved_reviews'] ?? 0 }} đánh giá</p>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-blue-500/50 transition shadow-sm">
            <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Thời lượng TB <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Thời lượng TB</span><br>
Thời lượng trung bình của mỗi ca học (tính bằng phút).<br><br>
<span class="tooltip-sql">SELECT AVG(ordles_duration)<br>
FROM tbl_order_lessons<br>
WHERE ordles_status = 3</span>
</span></span></p>
            <p class="text-2xl md:text-4xl font-bold text-blue-500 mt-2">{{ $lessonQuality['average_duration'] ?? 0 }}</p>
            <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">phút/ca</p>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">🎯 Chuyển đổi Trial → Paid</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($conversionFunnel['total_trials'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Trial <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng Trial</span><br>
Tổng số bài học thử (Trial) trong kỳ.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 1</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($conversionFunnel['completed_trials'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Trial hoàn thành <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Trial hoàn thành</span><br>
Số bài học thử đã hoàn thành thành công.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 1<br>
AND ordles_status = 3</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $conversionFunnel['trial_completion_rate'] ?? 0 }}%</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Tỷ lệ hoàn thành Trial <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ hoàn thành Trial</span><br>
Phần trăm Trial hoàn thành trên tổng số Trial.<br><br>
<span class="tooltip-sql">= (completed_trials / total_trials) * 100</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($conversionFunnel['converted_users'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đã mua hàng <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã mua hàng</span><br>
Số người dùng đã mua gói học sau khi học thử.<br><br>
<span class="tooltip-sql">SELECT COUNT(DISTINCT user_id)<br>
FROM tbl_orders<br>
WHERE order_type = 'paid'<br>
AND user_id IN (trial_users)</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $conversionFunnel['conversion_rate'] ?? 0 }}%</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Tỷ lệ chuyển đổi <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ chuyển đổi</span><br>
Phần trăm người dùng mua hàng sau khi học thử.<br><br>
<span class="tooltip-sql">= (converted_users / trial_users) * 100</span>
</span></span></p>
            </div>
        </div>
    </div>

    <!-- Lesson Chart -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">📊 Bài học 30 ngày qua <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bài học 30 ngày qua</span><br>
Biểu đồ thống kê số lượng bài học theo ngày trong 30 ngày gần nhất.<br><br>
<span class="tooltip-sql">SELECT DATE(ordles_starttime), COUNT(*)<br>
FROM tbl_order_lessons<br>
WHERE ordles_starttime >= DATE_SUB(NOW(), INTERVAL 30 DAY)<br>
GROUP BY DATE(ordles_starttime)</span>
</span></span></h3>
        <canvas id="lessonChart" height="100"></canvas>
    </div>

    <!-- Teacher Feedback Quality Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">📝 Chất lượng Nhật ký Học tập (Feedback GV)</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($feedbackStats['total'] ?? 0) }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Tổng Feedback <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng Feedback</span><br>
Tổng số feedback giáo viên đã nộp.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($feedbackStats['pending'] ?? 0) }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Chờ duyệt <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Chờ duyệt</span><br>
Số feedback đang chờ được duyệt (status = 1: pending).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks<br>
WHERE teafeed_status = 1</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($feedbackStats['approved'] ?? 0) }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã duyệt <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã duyệt</span><br>
Số feedback đã được duyệt (status = 2: approved).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks<br>
WHERE teafeed_status = 2</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($feedbackStats['trial'] ?? 0) }}</p>
                <p class="text-sm text-cyan-600/80 dark:text-cyan-400/80">Trial <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Trial Feedback</span><br>
Số feedback cho bài học thử. Xác định bằng cách kiểm tra ordles_type = 1 (Trial) trong tbl_order_lessons.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks tf<br>
WHERE tf.teafeed_record_type = 1<br>
AND tf.teafeed_record_id IN (<br>
&nbsp;&nbsp;SELECT ordles_id FROM tbl_order_lessons<br>
&nbsp;&nbsp;WHERE ordles_type = 1<br>
)</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($feedbackStats['regular'] ?? 0) }}</p>
                <p class="text-sm text-purple-600/80 dark:text-purple-400/80">Regular <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Regular Feedback</span><br>
Số feedback cho bài học chính thức. Xác định bằng cách kiểm tra ordles_type = 2 (Regular) trong tbl_order_lessons.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks tf<br>
WHERE tf.teafeed_record_type = 1<br>
AND tf.teafeed_record_id IN (<br>
&nbsp;&nbsp;SELECT ordles_id FROM tbl_order_lessons<br>
&nbsp;&nbsp;WHERE ordles_type = 2<br>
)</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ number_format($feedbackStats['today'] ?? 0) }}</p>
                <p class="text-sm text-teal-600/80 dark:text-teal-400/80">Hôm nay <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hôm nay</span><br>
Số feedback được nộp trong ngày hôm nay.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks<br>
WHERE DATE(teafeed_created_at) = CURDATE()</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $feedbackStats['approval_rate'] ?? 0 }}%</p>
                <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ duyệt <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ duyệt</span><br>
Phần trăm feedback đã được duyệt.<br><br>
<span class="tooltip-sql">= (approved / total) * 100</span>
</span></span></p>
            </div>
        </div>

        <!-- Feedback Status Alert -->
        <div class="mt-6 pt-6 border-t border-light-border dark:border-zeus-border">
            <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">⚠️ Cảnh báo Feedback</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'bg-red-500/10 border-red-500/30' : 'bg-green-500/10 border-green-500/30' }} rounded-lg border">
                    <p class="text-2xl font-bold {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format($feedbackStatus['pending_feedback'] ?? 0) }}</p>
                    <p class="text-xs {{ ($feedbackStatus['pending_feedback'] ?? 0) > 0 ? 'text-red-600/80 dark:text-red-400/80' : 'text-green-600/80 dark:text-green-400/80' }}">GV chưa nộp feedback <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">GV chưa nộp feedback</span><br>
Số bài học hoàn thành (từ hôm qua trở về trước) nhưng GV chưa nộp feedback.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons ol<br>
WHERE ol.ordles_status = 3<br>
AND DATE(ol.ordles_lesson_starttime) &lt;= DATE_SUB(CURDATE(), INTERVAL 1 DAY)<br>
AND ol.ordles_id NOT IN (<br>
&nbsp;&nbsp;SELECT teafeed_record_id FROM tbl_teacher_feedbacks<br>
&nbsp;&nbsp;WHERE teafeed_record_type = 1<br>
)</span>
</span></span></p>
                </div>
                <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($feedbackStatus['submitted_today'] ?? 0) }}</p>
                    <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Nộp hôm nay <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nộp hôm nay</span><br>
Số feedback được nộp trong ngày hôm nay.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_teacher_feedbacks<br>
WHERE DATE(teafeed_created_at) = CURDATE()</span>
</span></span></p>
                </div>
                <div class="text-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($feedbackStatus['lessons_needing_feedback'] ?? 0) }}</p>
                    <p class="text-xs text-purple-600/80 dark:text-purple-400/80">Bài cần feedback <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bài cần feedback</span><br>
Số bài học hoàn thành hôm nay cần GV nộp feedback.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 3<br>
AND DATE(ordles_lesson_starttime) = CURDATE()</span>
</span></span></p>
                </div>
                <div class="text-center p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $feedbackStatus['on_time_rate'] ?? 0 }}%</p>
                    <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">Tỷ lệ đúng hạn <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỷ lệ đúng hạn</span><br>
Phần trăm feedback được nộp đúng hạn (trong vòng 24h sau khi kết thúc bài học).<br><br>
<span class="tooltip-sql">= (on_time_submissions / total_submissions) * 100</span>
</span></span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Lesson Stats -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">📚 Thống kê Bài học</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                    <span class="text-light-text-muted dark:text-zeus-text-muted">Tổng số bài học <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng số bài học</span><br>
Tổng số bài học trong hệ thống.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons</span>
</span></span></span>
                    <span class="text-2xl font-bold text-light-text dark:text-zeus-text">{{ number_format($lessonQuality['total'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">✅ Hoàn thành <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Hoàn thành</span><br>
Số bài học đã hoàn thành thành công.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 3</span>
</span></span></span>
                    <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($lessonQuality['completed'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <span class="text-red-600 dark:text-red-400">❌ Đã hủy <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã hủy</span><br>
Số bài học đã bị hủy.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_status = 4</span>
</span></span></span>
                    <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($lessonQuality['cancelled'] ?? 0) }}</span>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">Phân loại bài học</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($lessonQuality['trial_lessons'] ?? 0) }}</p>
                        <p class="text-xs text-blue-600/80 dark:text-blue-400/80">Trial <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Trial</span><br>
Số bài học thử (Trial).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 1</span>
</span></span></p>
                    </div>
                    <div class="text-center p-3 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($lessonQuality['regular_lessons'] ?? 0) }}</p>
                        <p class="text-xs text-purple-600/80 dark:text-purple-400/80">Regular <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Regular</span><br>
Số bài học chính thức (Regular).<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_order_lessons<br>
WHERE ordles_type = 2</span>
</span></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ratings Detail -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">⭐ Chi tiết Đánh giá</h3>

            <div class="text-center mb-6 p-6 bg-amber-500/5 dark:bg-amber-500/10 rounded-xl border border-amber-500/20">
                <p class="text-6xl font-bold text-amber-500">{{ $ratings['average_rating'] ?? 0 }}</p>
                <p class="text-light-text-muted dark:text-zeus-text-muted mt-2">Điểm đánh giá trung bình <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Điểm đánh giá trung bình</span><br>
Điểm đánh giá trung bình của học sinh cho giáo viên.<br><br>
<span class="tooltip-sql">SELECT AVG(ratrev_overall)<br>
FROM tbl_rating_reviews<br>
WHERE ratrev_status = 1</span>
</span></span></p>
                <div class="flex justify-center mt-2">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($ratings['average_rating'] ?? 0))
                            <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-light-border dark:text-zeus-border" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endif
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/30">
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $ratings['pending_reviews'] ?? 0 }}</p>
                    <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Chờ duyệt <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Chờ duyệt</span><br>
Số đánh giá đang chờ được duyệt.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_rating_reviews<br>
WHERE ratrev_status = 0</span>
</span></span></p>
                </div>
                <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/30">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $ratings['approved_reviews'] ?? 0 }}</p>
                    <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã duyệt <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã duyệt</span><br>
Số đánh giá đã được duyệt.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_rating_reviews<br>
WHERE ratrev_status = 1</span>
</span></span></p>
                </div>
                <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/30">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $ratings['declined_reviews'] ?? 0 }}</p>
                    <p class="text-sm text-red-600/80 dark:text-red-400/80">Từ chối <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Từ chối</span><br>
Số đánh giá đã bị từ chối.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_rating_reviews<br>
WHERE ratrev_status = 2</span>
</span></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancellation Breakdown Section -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            ❌ Phân loại Hủy ({{ $cancellationBreakdown['period'] ?? '30 ngày' }})
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span> + <span class="tooltip-table">tbl_orders</span><br>Phân loại: theo loại bài (Trial/Regular), No-show (GV/HS), và loại đơn hàng</span></span>
        </h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Lesson Cancellations by Type -->
            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">
                    📚 Hủy theo Loại Bài học
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>ordles_status = 4 (CANCELLED)<br>ordles_type: 1=Trial, 2=Regular</span></span>
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                        <span class="text-red-600 dark:text-red-400">Tổng hủy</span>
                        <span class="text-xl font-bold text-red-600 dark:text-red-400">{{ $cancellationBreakdown['lessons']['total_cancelled'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                        <div>
                            <span class="text-cyan-600 dark:text-cyan-400">Trial</span>
                            <span class="text-xs text-cyan-600/60 dark:text-cyan-400/60 ml-1">({{ $cancellationBreakdown['lessons']['trial_rate'] ?? 0 }}%)</span>
                        </div>
                        <span class="text-xl font-bold text-cyan-600 dark:text-cyan-400">{{ $cancellationBreakdown['lessons']['trial_cancelled'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-violet-500/5 dark:bg-violet-500/10 rounded-lg border border-violet-500/20">
                        <div>
                            <span class="text-violet-600 dark:text-violet-400">Regular</span>
                            <span class="text-xs text-violet-600/60 dark:text-violet-400/60 ml-1">({{ $cancellationBreakdown['lessons']['regular_rate'] ?? 0 }}%)</span>
                        </div>
                        <span class="text-xl font-bold text-violet-600 dark:text-violet-400">{{ $cancellationBreakdown['lessons']['regular_cancelled'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            
            <!-- No-Show Breakdown -->
            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">
                    👤 No-Show (Vắng mặt)
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status=2 (SCHEDULED) nhưng đã qua thời gian kết thúc<br>ordles_teacher_starttime/ordles_student_starttime = NULL</span></span>
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                        <span class="text-amber-600 dark:text-amber-400">Tổng No-show</span>
                        <span class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $cancellationBreakdown['no_show']['total'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                        <span class="text-orange-600 dark:text-orange-400">GV vắng (riêng)</span>
                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $cancellationBreakdown['no_show']['teacher_only'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-pink-500/5 dark:bg-pink-500/10 rounded-lg border border-pink-500/20">
                        <span class="text-pink-600 dark:text-pink-400">HS vắng (riêng)</span>
                        <span class="text-xl font-bold text-pink-600 dark:text-pink-400">{{ $cancellationBreakdown['no_show']['student_only'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                        <span class="text-red-600 dark:text-red-400">Cả hai vắng</span>
                        <span class="text-xl font-bold text-red-600 dark:text-red-400">{{ $cancellationBreakdown['no_show']['both_no_show'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Order Cancellations by Type -->
            <div class="bg-light-card-alt dark:bg-zeus-card-light rounded-lg p-4 border border-light-border dark:border-zeus-border">
                <h4 class="text-sm font-medium text-light-text-muted dark:text-zeus-text-muted mb-3">
                    🛒 Hủy Đơn hàng theo Loại
                    <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_orders</span><br>order_status = 3 (CANCELLED)<br>Phân loại theo order_type</span></span>
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                        <span class="text-red-600 dark:text-red-400">Tổng đơn hủy</span>
                        <span class="text-xl font-bold text-red-600 dark:text-red-400">{{ $cancellationBreakdown['orders']['total_cancelled'] ?? 0 }}</span>
                    </div>
                    @foreach(($cancellationBreakdown['orders']['by_type'] ?? []) as $type => $count)
                    <div class="flex justify-between items-center p-3 bg-slate-500/5 dark:bg-slate-500/10 rounded-lg border border-slate-500/20">
                        <span class="text-slate-600 dark:text-slate-400">{{ $type }}</span>
                        <span class="text-xl font-bold text-slate-600 dark:text-slate-400">{{ $count }}</span>
                    </div>
                    @endforeach
                    @if(empty($cancellationBreakdown['orders']['by_type']))
                    <div class="text-center p-3 text-light-text-muted dark:text-zeus-text-muted text-sm">
                        Không có đơn hủy trong kỳ
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Issues -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">🚨 Vấn đề Báo cáo</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/30">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $issues['in_progress'] ?? 0 }}</p>
                <p class="text-sm text-amber-600/80 dark:text-amber-400/80">Đang xử lý <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đang xử lý</span><br>
Số vấn đề đang được xử lý.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_reported_issues<br>
WHERE repiss_status = 1</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/30">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $issues['resolved'] ?? 0 }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Đã giải quyết <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã giải quyết</span><br>
Số vấn đề đã được giải quyết.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_reported_issues<br>
WHERE repiss_status = 2</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/30">
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $issues['escalated'] ?? 0 }}</p>
                <p class="text-sm text-red-600/80 dark:text-red-400/80">Đã escalate <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã escalate</span><br>
Số vấn đề đã được chuyển lên cấp cao hơn.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_reported_issues<br>
WHERE repiss_status = 3</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border">
                <p class="text-3xl font-bold text-light-text-muted dark:text-zeus-text-muted">{{ $issues['closed'] ?? 0 }}</p>
                <p class="text-sm text-light-text-muted dark:text-zeus-text-muted">Đã đóng <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Đã đóng</span><br>
Số vấn đề đã được đóng.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_reported_issues<br>
WHERE repiss_status = 4</span>
</span></span></p>
            </div>
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/30">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $issues['new_today'] ?? 0 }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Mới hôm nay <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Mới hôm nay</span><br>
Số vấn đề mới được báo cáo trong ngày hôm nay.<br><br>
<span class="tooltip-sql">SELECT COUNT(*) FROM tbl_reported_issues<br>
WHERE DATE(repiss_reported_on) = CURDATE()</span>
</span></span></p>
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

    // Lesson Chart
    const lessonEl = document.getElementById('lessonChart');
    if (lessonEl) {
        const lessonCtx = lessonEl.getContext('2d');
        new Chart(lessonCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($lessonChart['labels']) !!},
                datasets: [
                    {
                        label: 'Hoàn thành',
                        data: {!! json_encode($lessonChart['datasets']['completed']) !!},
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2
                    },
                    {
                        label: 'Đã lên lịch',
                        data: {!! json_encode($lessonChart['datasets']['scheduled']) !!},
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2
                    },
                    {
                        label: 'Đã hủy',
                        data: {!! json_encode($lessonChart['datasets']['cancelled']) !!},
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: false,
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
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
} // End of chart initialization block
</script>
@endpush
