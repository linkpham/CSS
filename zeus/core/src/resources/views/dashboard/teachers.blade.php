@extends('layouts.app')

@section('title', 'ICan Dashboard - Giáo viên')
@section('page-title', 'Quản lý Giáo viên')

@section('content')
@php
    $activeProgram = request('program', 'all');
@endphp

<div class="space-y-4 md:space-y-6">
    <!-- Program Tabs -->
    <x-program-tabs :activeProgram="$activeProgram" />
    
    <!-- Content -->
    
    <!-- Teacher Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-blue-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted truncate">
                        Tổng bài đã dạy
                        <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span><br>Điều kiện: ordles_status = 3 (COMPLETED)<br>Tổng số bài học đã hoàn thành</span></span>
                    </p>
                    <p class="text-xl md:text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($teacherStats['total_lessons_taught'] ?? 0) }}</p>
                </div>
                <div class="bg-blue-500/10 dark:bg-blue-500/20 p-2 md:p-3 rounded-xl flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-green-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted truncate">
                        Lớp nhóm đã dạy
                        <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_group_classes</span><br>Điều kiện: grpcls_status = 2 (COMPLETED)<br>Tổng số lớp nhóm đã hoàn thành</span></span>
                    </p>
                    <p class="text-xl md:text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($teacherStats['total_classes_taught'] ?? 0) }}</p>
                </div>
                <div class="bg-green-500/10 dark:bg-green-500/20 p-2 md:p-3 rounded-xl flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-amber-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted truncate">
                        Học sinh unique
                        <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_order_lessons</span> JOIN <span class="tooltip-table">tbl_orders</span><br>COUNT DISTINCT order_user_id<br>Số học sinh khác nhau đã học với GV</span></span>
                    </p>
                    <p class="text-xl md:text-3xl font-bold text-light-text dark:text-zeus-text mt-1">{{ number_format($teacherStats['unique_students'] ?? 0) }}</p>
                </div>
                <div class="bg-amber-500/10 dark:bg-amber-500/20 p-2 md:p-3 rounded-xl flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border hover:border-purple-500/50 transition shadow-sm">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs md:text-sm font-medium text-light-text-muted dark:text-zeus-text-muted truncate">
                        Đánh giá TB
                        <span class="info-tooltip hidden md:inline-flex">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_rating_reviews</span><br>AVG(ratrev_rating) WHERE ratrev_status = 2 (APPROVED)<br>Điểm đánh giá trung bình của tất cả GV</span></span>
                    </p>
                    <p class="text-xl md:text-3xl font-bold text-amber-500 mt-1">{{ $teacherStats['average_rating'] ?? 0 }} ⭐</p>
                </div>
                <div class="bg-purple-500/10 dark:bg-purple-500/20 p-2 md:p-3 rounded-xl flex-shrink-0">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted mt-2">{{ number_format($teacherStats['total_reviews'] ?? 0) }} đánh giá</p>
        </div>
    </div>

    <!-- Top Teachers Table -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            🏆 Top Giáo viên
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_stats</span> JOIN <span class="tooltip-table">tbl_users</span><br>Sắp xếp theo testat_lessons DESC<br>Top GV theo số bài dạy và đánh giá</span></span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-light-border dark:border-zeus-border">
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">#</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Giáo viên</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Email</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Số bài dạy</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Học sinh</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Đánh giá</th>
                        <th class="text-right py-3 px-4 text-sm font-medium text-light-text-muted dark:text-zeus-text-muted">Reviews</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topTeachers as $index => $teacher)
                    <tr class="border-b border-light-border dark:border-zeus-border hover:bg-light-card-alt dark:hover:bg-zeus-card-light">
                        <td class="py-3 px-4">
                            <span class="w-6 h-6 flex items-center justify-center text-sm font-bold {{ $index < 3 ? 'text-amber-500' : 'text-light-text-muted dark:text-zeus-text-muted' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm font-medium text-light-text dark:text-zeus-text">{{ $teacher['name'] }}</td>
                        <td class="py-3 px-4 text-sm text-light-text-muted dark:text-zeus-text-muted">{{ $teacher['email'] }}</td>
                        <td class="py-3 px-4 text-sm text-right font-semibold text-blue-500">{{ number_format($teacher['lessons']) }}</td>
                        <td class="py-3 px-4 text-sm text-right text-light-text dark:text-zeus-text">{{ number_format($teacher['students']) }}</td>
                        <td class="py-3 px-4 text-sm text-right text-amber-500">{{ $teacher['rating'] }} ⭐</td>
                        <td class="py-3 px-4 text-sm text-right text-light-text-muted dark:text-zeus-text-muted">{{ number_format($teacher['reviews']) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-light-text-muted dark:text-zeus-text-muted">Chưa có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Teacher Payment Stats -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                💰 Thanh toán Giáo viên
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_wallet_transactions</span><br>Phân loại theo waltrn_type<br>Thống kê thanh toán và hoa hồng cho GV</span></span>
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-green-600 dark:text-green-400">Tổng đã trả GV</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($paymentStats['total_paid'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <span class="text-amber-600 dark:text-amber-400">Đang chờ thanh toán</span>
                    <span class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($paymentStats['pending_payment'] ?? 0) }} ca</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-blue-600 dark:text-blue-400">Hoa hồng hệ thống</span>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($paymentStats['system_commission'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                    <span class="text-purple-600 dark:text-purple-400">Hoa hồng Affiliate</span>
                    <span class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($paymentStats['affiliate_commission'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Ratings -->
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
                ⭐ Đánh giá & Phản hồi
                <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_rating_reviews</span><br>ratrev_status: 0=Chờ duyệt, 2=Đã duyệt, 3=Từ chối<br>Thống kê đánh giá của học viên cho GV</span></span>
            </h3>
            <div class="text-center mb-6 p-6 bg-amber-500/5 dark:bg-amber-500/10 rounded-xl border border-amber-500/20">
                <p class="text-6xl font-bold text-amber-500">{{ $ratings['average_rating'] ?? 0 }}</p>
                <p class="text-light-text-muted dark:text-zeus-text-muted">Điểm đánh giá trung bình</p>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $ratings['pending_reviews'] ?? 0 }}</p>
                    <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Chờ duyệt</p>
                </div>
                <div class="text-center p-3 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $ratings['approved_reviews'] ?? 0 }}</p>
                    <p class="text-xs text-green-600/80 dark:text-green-400/80">Đã duyệt</p>
                </div>
                <div class="text-center p-3 bg-red-500/5 dark:bg-red-500/10 rounded-lg border border-red-500/20">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $ratings['declined_reviews'] ?? 0 }}</p>
                    <p class="text-xs text-red-600/80 dark:text-red-400/80">Từ chối</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Teacher Stats -->
    <div class="bg-light-card dark:bg-zeus-card rounded-xl p-6 border border-light-border dark:border-zeus-border shadow-sm">
        <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text mb-4">
            📊 Thống kê khác
            <span class="info-tooltip">ⓘ<span class="tooltip-content"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">tbl_teacher_availability</span> + <span class="tooltip-table">tbl_users</span><br>Lịch khả dụng: GV có thiết lập availability<br>Profile hoàn thiện: user_profile_complete = 1</span></span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherStats['with_availability'] ?? 0 }}</p>
                <p class="text-sm text-blue-600/80 dark:text-blue-400/80">Có lịch khả dụng</p>
            </div>
            <div class="text-center p-4 bg-green-500/5 dark:bg-green-500/10 rounded-lg border border-green-500/20">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $teacherStats['complete_profile'] ?? 0 }}</p>
                <p class="text-sm text-green-600/80 dark:text-green-400/80">Profile hoàn thiện</p>
            </div>
        </div>
    </div>
    
</div>
@endsection
