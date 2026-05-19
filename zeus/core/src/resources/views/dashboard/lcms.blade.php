@extends('layouts.app')

@section('title', 'ICan Dashboard - LCMS')
@section('page-title', 'Báo cáo Tiến độ Học tập (LCMS)')

@section('content')
<div class="space-y-4 md:space-y-6" x-data="lcmsReport()" x-init="init()">

    <!-- Loading State -->
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center py-12">
            <div class="spinner"></div>
            <p class="mt-4 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải dữ liệu LCMS<span class="loading-dots"></span></p>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="error && !loading">
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-6 text-center">
            <p class="text-red-600 dark:text-red-400 font-medium" x-text="error"></p>
            <button @click="init()" class="mt-3 px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Thử lại</button>
        </div>
    </template>

    <!-- Main Content -->
    <template x-if="!loading && !error">
        <div class="space-y-4 md:space-y-6">

            <!-- ===== SECTION 1: Grand Overview KPI ===== -->
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                    📚 Tổng quan Tiến độ Học tập LCMS
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">lcms_user_assignments</span>, <span class="tooltip-table">lcms_courses</span>, <span class="tooltip-table">lcms_student_scores</span><br>Tổng hợp trên tất cả các khóa SpeakWell (ID: 346, 563, 595, 1084)<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084)<br><span class="tooltip-label">SQL Logic</span><br>— Section hoàn thành khi MIN(usrasi_completion_state) = 1<br>— Điểm chỉ tính khi HV làm đủ tất cả Quiz trong Section<br>— cou_section_type: 2 = BTVN, 3 = Bài kiểm tra<br>— Lọc theo usrasi_course_id IN (346, 563, 595, 1084)<br><span class="tooltip-sql">-- Tổng học viên:
SELECT COUNT(DISTINCT ua.usrasi_student_id) AS total
FROM lcms_user_assignments ua
WHERE ua.usrasi_course_id IN (346, 563, 595, 1084)

-- Danh sách khóa:
SELECT DISTINCT cou_id, cou_name
FROM lcms_courses
WHERE cou_id IN (346, 563, 595, 1084)</span></span></span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-4">
                    <!-- Total Students -->
                    <div class="text-center p-3 md:p-4 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                        <p class="text-xl md:text-3xl font-bold text-indigo-600 dark:text-indigo-400" x-text="formatNumber(overview.total_students)">0</p>
                        <p class="text-xs md:text-sm text-indigo-600/80 dark:text-indigo-400/80">Tổng học viên
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tổng học viên</span><br>Số học viên (unique) được giao bài trong các khóa SpeakWell, chỉ tính HV thuộc sản phẩm SPEAKWELL.<br><br><span class="tooltip-sql">SELECT COUNT(DISTINCT ua.usrasi_student_id) AS total
FROM lcms_user_assignments ua
WHERE ua.usrasi_course_id IN (346, 563, 595, 1084)</span></span></span>
                        </p>
                    </div>
                    <!-- Total Courses -->
                    <div class="text-center p-3 md:p-4 bg-violet-500/5 dark:bg-violet-500/10 rounded-lg border border-violet-500/20">
                        <p class="text-xl md:text-3xl font-bold text-violet-600 dark:text-violet-400" x-text="overview.total_courses">0</p>
                        <p class="text-xs md:text-sm text-violet-600/80 dark:text-violet-400/80">Số khóa học
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Số khóa học SpeakWell</span><br>Các khóa SpeakWell cố định trong hệ thống LCMS.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br><span class="tooltip-sql">-- SpeakWell Course IDs (hard-coded):
-- 346, 563, 595, 1084
SELECT DISTINCT cou_id, cou_name
FROM lcms_courses
WHERE cou_id IN (346, 563, 595, 1084)
ORDER BY cou_id</span></span></span>
                        </p>
                    </div>
                    <!-- Homework Completion Rate -->
                    <div class="text-center p-3 md:p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                        <p class="text-xl md:text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="overview.homework.completion_ratio + '%'">0%</p>
                        <p class="text-xs md:text-sm text-blue-600/80 dark:text-blue-400/80">Tỉ lệ hoàn thành BTVN
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỉ lệ hoàn thành Bài tập về nhà</span><br>
Phần trăm các Section BTVN đã hoàn thành trên tổng số được giao.<br>Section hoàn thành khi MIN(completion_state) = 1.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br>
<span class="tooltip-sql">SELECT
  COUNT(sub.section_id) AS total_sections,
  SUM(sub.is_section_completed) AS completed_sections,
  ROUND(
    (SUM(sub.is_section_completed)
     / NULLIF(COUNT(sub.section_id), 0)) * 100, 2
  ) AS completion_ratio
FROM (
  SELECT
    ua.usrasi_student_id,
    ua.usrasi_course_id AS course_id,
    ua.usrasi_section_id AS section_id,
    CASE WHEN MIN(ua.usrasi_completion_state) = 1
         THEN 1 ELSE 0
    END AS is_section_completed
  FROM lcms_user_assignments ua
  JOIN lcms_courses c
    ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = 2  -- BTVN
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
           ua.usrasi_course_id,
           ua.usrasi_section_id
) AS sub</span></span></span>
                        </p>
                    </div>
                    <!-- Homework Avg Score -->
                    <div class="text-center p-3 md:p-4 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                        <p class="text-xl md:text-3xl font-bold text-cyan-600 dark:text-cyan-400" x-text="overview.homework.avg_score !== null ? overview.homework.avg_score : 'N/A'">N/A</p>
                        <p class="text-xs md:text-sm text-cyan-600/80 dark:text-cyan-400/80">Điểm TB BTVN
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Điểm trung bình Bài tập về nhà</span><br>
Chỉ tính điểm khi HV làm đủ tất cả Quiz trong Section.<br>Lấy điểm cao nhất mỗi Quiz, rồi tính trung bình.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br>
<span class="tooltip-sql">SELECT ROUND(AVG(sub.avg_section_score), 2) AS avg_score
FROM (
  SELECT ua.usrasi_student_id,
    ua.usrasi_section_id AS section_id,
    (SELECT CASE
       WHEN COUNT(ms.highest_score) >= (
         SELECT COUNT(*) FROM lcms_courses
         WHERE cou_parent_id = ua.usrasi_section_id
           AND cou_type = 'quiz')
       THEN AVG(ms.highest_score) ELSE NULL END
     FROM (
       SELECT MAX(CAST(ss.stusco_overall_score
         AS DECIMAL(10,2))) AS highest_score
       FROM lcms_courses c2
       JOIN lcms_student_scores ss
         ON c2.cou_id = ss.stusco_course_id
       WHERE c2.cou_parent_id = ua.usrasi_section_id
         AND c2.cou_type = 'quiz'
         AND ss.stusco_student_id = ua.usrasi_student_id
       GROUP BY c2.cou_id
     ) AS ms
    ) AS avg_section_score
  FROM lcms_user_assignments ua
  JOIN lcms_courses c
    ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = 2  -- BTVN
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub</span></span></span>
                        </p>
                    </div>
                    <!-- Test Completion Rate -->
                    <div class="text-center p-3 md:p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                        <p class="text-xl md:text-3xl font-bold text-emerald-600 dark:text-emerald-400" x-text="overview.test.completion_ratio + '%'">0%</p>
                        <p class="text-xs md:text-sm text-emerald-600/80 dark:text-emerald-400/80">Tỉ lệ hoàn thành BKT
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Tỉ lệ hoàn thành Bài kiểm tra</span><br>
Phần trăm các Section Bài kiểm tra đã hoàn thành trên tổng số được giao.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br>
<span class="tooltip-sql">SELECT
  COUNT(sub.section_id) AS total_sections,
  SUM(sub.is_section_completed) AS completed_sections,
  ROUND(
    (SUM(sub.is_section_completed)
     / NULLIF(COUNT(sub.section_id), 0)) * 100, 2
  ) AS completion_ratio
FROM (
  SELECT
    ua.usrasi_student_id,
    ua.usrasi_course_id AS course_id,
    ua.usrasi_section_id AS section_id,
    CASE WHEN MIN(ua.usrasi_completion_state) = 1
         THEN 1 ELSE 0
    END AS is_section_completed
  FROM lcms_user_assignments ua
  JOIN lcms_courses c
    ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = 3  -- Bài kiểm tra
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
           ua.usrasi_course_id,
           ua.usrasi_section_id
) AS sub</span></span></span>
                        </p>
                    </div>
                    <!-- Test Avg Score -->
                    <div class="text-center p-3 md:p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                        <p class="text-xl md:text-3xl font-bold text-amber-600 dark:text-amber-400" x-text="overview.test.avg_score !== null ? overview.test.avg_score : 'N/A'">N/A</p>
                        <p class="text-xs md:text-sm text-amber-600/80 dark:text-amber-400/80">Điểm TB BKT
                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Điểm trung bình Bài kiểm tra</span><br>
Chỉ tính điểm khi HV làm đủ tất cả Quiz trong Section kiểm tra.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br>
<span class="tooltip-sql">SELECT ROUND(AVG(sub.avg_section_score), 2) AS avg_score
FROM (
  SELECT ua.usrasi_student_id,
    ua.usrasi_section_id AS section_id,
    (SELECT CASE
       WHEN COUNT(ms.highest_score) >= (
         SELECT COUNT(*) FROM lcms_courses
         WHERE cou_parent_id = ua.usrasi_section_id
           AND cou_type = 'quiz')
       THEN AVG(ms.highest_score) ELSE NULL END
     FROM (
       SELECT MAX(CAST(ss.stusco_overall_score
         AS DECIMAL(10,2))) AS highest_score
       FROM lcms_courses c2
       JOIN lcms_student_scores ss
         ON c2.cou_id = ss.stusco_course_id
       WHERE c2.cou_parent_id = ua.usrasi_section_id
         AND c2.cou_type = 'quiz'
         AND ss.stusco_student_id = ua.usrasi_student_id
       GROUP BY c2.cou_id
     ) AS ms
    ) AS avg_section_score
  FROM lcms_user_assignments ua
  JOIN lcms_courses c
    ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = 3  -- Bài kiểm tra
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub</span></span></span>
                        </p>
                    </div>
                </div>
                <!-- Homework & Test detail cards -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-lg">📝</span>
                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">Bài tập về nhà (BTVN)</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p class="text-lg font-bold text-light-text dark:text-zeus-text" x-text="formatNumber(overview.homework.total_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tổng lượt giao</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-green-600 dark:text-green-400" x-text="formatNumber(overview.homework.completed_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đã hoàn thành</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(overview.homework.total_sections - overview.homework.completed_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Chưa hoàn thành</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-lg">📋</span>
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Bài kiểm tra (BKT)</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p class="text-lg font-bold text-light-text dark:text-zeus-text" x-text="formatNumber(overview.test.total_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tổng lượt giao</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-green-600 dark:text-green-400" x-text="formatNumber(overview.test.completed_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đã hoàn thành</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(overview.test.total_sections - overview.test.completed_sections)">0</p>
                                <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Chưa hoàn thành</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 2: Course Breakdown ===== -->
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                    📊 Chi tiết theo Khóa học
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">lcms_user_assignments</span>, <span class="tooltip-table">lcms_courses</span>, <span class="tooltip-table">lcms_student_scores</span><br>Phân tích tỉ lệ hoàn thành và điểm số theo từng khóa học.<br><br><span class="tooltip-label">SQL — Tỉ lệ hoàn thành (per course)</span><span class="tooltip-sql">SELECT sub.course_id,
  COUNT(sub.section_id) AS total_sections,
  SUM(CASE WHEN sub.is_section_completed = 1
    THEN 1 ELSE 0 END) AS completed_sections,
  ROUND((SUM(...) / NULLIF(COUNT(...), 0)) * 100, 2)
    AS completion_ratio
FROM (
  SELECT ua.usrasi_student_id,
    ua.usrasi_course_id AS course_id,
    ua.usrasi_section_id AS section_id,
    CASE WHEN MIN(ua.usrasi_completion_state) = 1
      THEN 1 ELSE 0 END AS is_section_completed
  FROM lcms_user_assignments ua
  JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = ?  -- 2=BTVN, 3=BKT
    AND ua.usrasi_course_id = ?  -- per course
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub GROUP BY sub.course_id</span><br><span class="tooltip-label">SQL — Điểm TB (per course)</span><span class="tooltip-sql">SELECT ROUND(AVG(sub.avg_section_score), 2) AS avg_score
FROM (
  SELECT ua.usrasi_section_id AS section_id,
    (SELECT CASE WHEN COUNT(ms.highest_score) >= (...)
      THEN AVG(ms.highest_score) ELSE NULL END
     FROM (SELECT MAX(CAST(ss.stusco_overall_score
       AS DECIMAL(10,2))) AS highest_score
       FROM lcms_courses c2
       JOIN lcms_student_scores ss ...
       WHERE c2.cou_parent_id = ua.usrasi_section_id
         AND c2.cou_type = 'quiz'
       GROUP BY c2.cou_id) AS ms
    ) AS avg_section_score
  FROM lcms_user_assignments ua
  JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = ?  -- 2=BTVN, 3=BKT
    AND ua.usrasi_course_id = ?
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub</span><br><span class="tooltip-label">SQL — Số HV per course</span><span class="tooltip-sql">SELECT COUNT(DISTINCT usrasi_student_id) AS total
FROM lcms_user_assignments
WHERE usrasi_course_id = ?</span></span></span>
                </h3>

                <!-- Loading course data -->
                <div x-show="loadingCourses" class="flex items-center justify-center py-8">
                    <span class="spinner-inline spinner-lg"></span>
                    <span class="ml-3 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải dữ liệu khóa học...</span>
                </div>

                <div x-show="!loadingCourses && courses.length > 0" x-cloak>
                        <!-- Completion Rate Chart -->
                        <div class="mb-6">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="courseCompletionChart"></canvas>
                            </div>
                        </div>

                        <!-- Course Table -->
                        <div class="table-container">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-light-border dark:border-zeus-border">
                                        <th class="text-left py-3 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Khóa học</th>
                                        <th class="text-center py-3 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium">HV</th>
                                        <th class="text-center py-3 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium" colspan="3">
                                            📝 BTVN
                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bài tập về nhà</span><br>Lọc theo <b>cou_section_type = 2</b> trong bảng lcms_courses.<br>Hoàn thành / Tỉ lệ / Điểm TB cho từng khóa.<br><br><span class="tooltip-sql">-- Completion per course:
WHERE c.cou_section_type = 2
  AND ua.usrasi_course_id = ?
-- Score: AVG of MAX quiz scores per section
-- NULL nếu HV chưa làm đủ quiz</span></span></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium" colspan="3">
                                            📋 Bài kiểm tra
                                            <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bài kiểm tra</span><br>Lọc theo <b>cou_section_type = 3</b> trong bảng lcms_courses.<br>Hoàn thành / Tỉ lệ / Điểm TB cho từng khóa.<br><br><span class="tooltip-sql">-- Completion per course:
WHERE c.cou_section_type = 3
  AND ua.usrasi_course_id = ?
-- Score: AVG of MAX quiz scores per section
-- NULL nếu HV chưa làm đủ quiz</span></span></span>
                                        </th>
                                    </tr>
                                    <tr class="border-b border-light-border dark:border-zeus-border text-xs">
                                        <th class="py-2 px-3"></th>
                                        <th class="py-2 px-2"></th>
                                        <th class="text-center py-2 px-2 text-blue-600 dark:text-blue-400">Hoàn thành</th>
                                        <th class="text-center py-2 px-2 text-blue-600 dark:text-blue-400">Tỉ lệ</th>
                                        <th class="text-center py-2 px-2 text-blue-600 dark:text-blue-400">Điểm TB</th>
                                        <th class="text-center py-2 px-2 text-emerald-600 dark:text-emerald-400">Hoàn thành</th>
                                        <th class="text-center py-2 px-2 text-emerald-600 dark:text-emerald-400">Tỉ lệ</th>
                                        <th class="text-center py-2 px-2 text-emerald-600 dark:text-emerald-400">Điểm TB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="course in courses" :key="course.course_id">
                                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                                            <td class="py-3 px-3">
                                                <div class="font-medium text-light-text dark:text-zeus-text text-sm" x-text="course.course_name"></div>
                                                <div class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'ID: ' + course.course_id"></div>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold text-light-text dark:text-zeus-text" x-text="course.student_count"></span>
                                            </td>
                                            <!-- Homework -->
                                            <td class="text-center py-3 px-2">
                                                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="course.homework.completed_sections + '/' + course.homework.total_sections"></span>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold" :class="getCompletionColor(course.homework.completion_ratio)" x-text="course.homework.completion_ratio + '%'"></span>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold" :class="getScoreColor(course.homework.avg_score)" x-text="course.homework.avg_score !== null ? course.homework.avg_score : '—'"></span>
                                            </td>
                                            <!-- Test -->
                                            <td class="text-center py-3 px-2">
                                                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="course.test.completed_sections + '/' + course.test.total_sections"></span>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold" :class="getCompletionColor(course.test.completion_ratio)" x-text="course.test.completion_ratio + '%'"></span>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold" :class="getScoreColor(course.test.avg_score)" x-text="course.test.avg_score !== null ? course.test.avg_score : '—'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>

            <!-- ===== SECTION 2.5: Charts - Section Distribution, Score Distribution, Completion Trend ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
                <!-- Section Type Distribution (Donut) -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-sm md:text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        🧩 Phân bố Loại nội dung
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Phân bố Section</span><br>Bảng: <span class="tooltip-table">lcms_courses</span>, <span class="tooltip-table">lcms_user_assignments</span><br>Đếm số Section (unique) được giao theo từng loại nội dung.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br><span class="tooltip-sql">SELECT
  c.cou_section_type,
  COUNT(DISTINCT c.cou_id) AS section_count
FROM lcms_courses c
WHERE c.cou_section_type IN (1, 2, 3, 4)
  AND c.cou_id IN (
    SELECT DISTINCT usrasi_section_id
    FROM lcms_user_assignments
    WHERE usrasi_course_id IN (346,563,595,1084)
  )
GROUP BY c.cou_section_type
ORDER BY c.cou_section_type
-- 1=Bài giảng, 2=BTVN, 3=BKT, 4=Tài nguyên</span></span></span>
                    </h3>
                    <div x-show="loadingSectionDist" class="flex items-center justify-center py-8"><span class="spinner-inline"></span></div>
                    <div x-show="!loadingSectionDist" x-cloak class="chart-container" style="height: 220px;">
                        <canvas id="sectionDistChart"></canvas>
                    </div>
                </div>

                <!-- Score Distribution (Histogram) -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-sm md:text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        📈 Phân bố Điểm số Học viên
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Phân bố Điểm (thang 10)</span><br>Bảng: <span class="tooltip-table">lcms_user_assignments</span>, <span class="tooltip-table">lcms_student_scores</span><br>Đếm số HV trong từng khoảng điểm (0-2, 2-4, 4-6, 6-8, 8-10).<br>Tách biệt BTVN (cou_section_type=2) và BKT (cou_section_type=3).<br><br><span class="tooltip-sql">SELECT
  CASE
    WHEN student_avg &lt; 2 THEN '0-2'
    WHEN student_avg &lt; 4 THEN '2-4'
    WHEN student_avg &lt; 6 THEN '4-6'
    WHEN student_avg &lt; 8 THEN '6-8'
    ELSE '8-10'
  END AS score_range,
  COUNT(*) AS student_count
FROM (
  SELECT sub.student_id,
    ROUND(AVG(sub.avg_section_score), 2) AS student_avg
  FROM (
    SELECT ua.usrasi_student_id AS student_id,
      ua.usrasi_section_id AS section_id,
      (SELECT CASE WHEN COUNT(ms.highest_score) >= (...)
        THEN AVG(ms.highest_score) ELSE NULL END
       FROM (SELECT MAX(CAST(ss.stusco_overall_score
         AS DECIMAL(10,2))) AS highest_score
         FROM lcms_courses c2
         JOIN lcms_student_scores ss ...
         WHERE c2.cou_parent_id = ua.usrasi_section_id
           AND c2.cou_type = 'quiz'
         GROUP BY c2.cou_id) AS ms
      ) AS avg_section_score
    FROM lcms_user_assignments ua
    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
    WHERE c.cou_section_type = ?  -- 2 hoặc 3
      AND ua.usrasi_course_id IN (346,563,595,1084)
    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id,
      ua.usrasi_section_id
  ) sub GROUP BY sub.student_id
  HAVING student_avg IS NOT NULL
) dist
GROUP BY score_range
ORDER BY FIELD(score_range,'0-2','2-4','4-6','6-8','8-10')</span></span></span>
                    </h3>
                    <div x-show="loadingScoreDist" class="flex items-center justify-center py-8"><span class="spinner-inline"></span></div>
                    <div x-show="!loadingScoreDist" x-cloak class="chart-container" style="height: 220px;">
                        <canvas id="scoreDistChart"></canvas>
                    </div>
                </div>

                <!-- Completion Trend Over Time -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-sm md:text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        📅 Xu hướng Hoàn thành theo Tháng
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Completion Trend</span><br>Bảng: <span class="tooltip-table">lcms_user_assignments</span>, <span class="tooltip-table">lcms_courses</span><br>Đếm số lượt hoàn thành theo tháng (BTVN + BKT).<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br><span class="tooltip-sql">SELECT
  DATE_FORMAT(ua.usrasi_completion_time, '%Y-%m')
    AS month,
  COUNT(*) AS completions
FROM lcms_user_assignments ua
JOIN lcms_courses c
  ON ua.usrasi_section_id = c.cou_id
WHERE ua.usrasi_completion_state = 1
  AND ua.usrasi_completion_time IS NOT NULL
  AND ua.usrasi_course_id IN (346,563,595,1084)
  AND c.cou_section_type IN (2, 3)
GROUP BY DATE_FORMAT(
  ua.usrasi_completion_time, '%Y-%m')
ORDER BY month ASC</span></span></span>
                    </h3>
                    <div x-show="loadingTrend" class="flex items-center justify-center py-8"><span class="spinner-inline"></span></div>
                    <div x-show="!loadingTrend" x-cloak class="chart-container" style="height: 220px;">
                        <canvas id="completionTrendChart"></canvas>
                    </div>
                </div>
            </div>



            <!-- ===== SECTION 2.6: Enrollment Overview & Demographics ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                <!-- Course Enrollment from lcms_course_student -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-sm md:text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        🎓 Tình trạng Đăng ký Khóa học
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">lcms_course_student</span>, <span class="tooltip-table">lcms_courses</span><br>Thống kê đăng ký, hoàn thành và trạng thái đồng bộ theo từng khóa.<br><br><span class="tooltip-sql">SELECT
  cs.coustu_course_id AS course_id,
  c.cou_name AS course_name,
  COUNT(*) AS total_enrolled,
  SUM(CASE WHEN cs.coustu_course_end IS NOT NULL
    AND cs.coustu_course_end != ''
    THEN 1 ELSE 0 END) AS completed_course,
  SUM(CASE WHEN cs.coustu_is_sync = 1
    THEN 1 ELSE 0 END) AS synced_count,
  SUM(CASE WHEN cs.coustu_is_sync = 0
    THEN 1 ELSE 0 END) AS unsynced_count
FROM lcms_course_student cs
LEFT JOIN lcms_courses c
  ON cs.coustu_course_id = c.cou_id
WHERE cs.coustu_course_id IN (346,563,595,1084)
GROUP BY cs.coustu_course_id, c.cou_name
ORDER BY cs.coustu_course_id</span></span></span>
                    </h3>
                    <template x-if="loadingEnrollment">
                        <div class="flex items-center justify-center py-6"><span class="spinner-inline"></span></div>
                    </template>
                    <template x-if="!loadingEnrollment && enrollment.length > 0">
                        <div class="space-y-3">
                            <template x-for="e in enrollment" :key="e.course_id">
                                <div class="p-3 rounded-lg bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-light-text dark:text-zeus-text" x-text="e.course_name"></span>
                                        <span class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'ID: ' + e.course_id"></span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="formatNumber(e.total_enrolled)"></p>
                                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đăng ký</p>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(e.completed_course)"></p>
                                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Kết thúc</p>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold" :class="e.unsynced_count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'" x-text="formatNumber(e.synced_count) + '/' + formatNumber(e.total_enrolled)"></p>
                                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Đã đồng bộ</p>
                                        </div>
                                    </div>
                                    <!-- Progress bar -->
                                    <div class="mt-2 score-bar">
                                        <div class="score-bar-fill bg-indigo-500" :style="'width: ' + (e.total_enrolled > 0 ? Math.round(e.completed_course / e.total_enrolled * 100) : 0) + '%'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!loadingEnrollment && enrollment.length === 0">
                        <p class="text-sm text-light-text-muted dark:text-zeus-text-muted text-center py-6">Chưa có dữ liệu đăng ký</p>
                    </template>
                </div>

                <!-- Student Demographics -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-sm md:text-base font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        👥 Thống kê Học viên
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Nguồn dữ liệu</span><br>Bảng: <span class="tooltip-table">lcms_students</span>, <span class="tooltip-table">lcms_user_assignments</span>, <span class="tooltip-table">lcms_student_scores</span><br>Thống kê nhân khẩu học, phân bố giới tính, tình trạng có điểm/chưa có điểm.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br><span class="tooltip-label">SQL — HV trong LCMS</span><span class="tooltip-sql">SELECT COUNT(DISTINCT s.stu_id) AS total
FROM lcms_students s
WHERE s.stu_id IN (
  SELECT DISTINCT ua.usrasi_student_id
  FROM lcms_user_assignments ua
  WHERE ua.usrasi_course_id IN (346,563,595,1084)
)</span><br><span class="tooltip-label">SQL — Phân bố giới tính</span><span class="tooltip-sql">SELECT
  COALESCE(NULLIF(s.stu_gender, ''),
    'Không xác định') AS gender,
  COUNT(*) AS count
FROM lcms_students s
WHERE s.stu_id IN (
  SELECT DISTINCT ua.usrasi_student_id
  FROM lcms_user_assignments ua
  WHERE ua.usrasi_course_id IN (346,563,595,1084)
)
GROUP BY gender ORDER BY count DESC</span><br><span class="tooltip-label">SQL — Assignments & HV có điểm</span><span class="tooltip-sql">SELECT COUNT(*) AS total_assignments,
  COUNT(DISTINCT usrasi_student_id) AS unique_students,
  COUNT(DISTINCT usrasi_section_id) AS unique_sections
FROM lcms_user_assignments
WHERE usrasi_course_id IN (346,563,595,1084)

-- HV có điểm:
SELECT COUNT(DISTINCT ua.usrasi_student_id)
FROM lcms_user_assignments ua
WHERE ua.usrasi_course_id IN (346,563,595,1084)
  AND ua.usrasi_student_id IN (
    SELECT DISTINCT stusco_student_id
    FROM lcms_student_scores)</span></span></span>
                    </h3>
                    <template x-if="loadingDemographics">
                        <div class="flex items-center justify-center py-6"><span class="spinner-inline"></span></div>
                    </template>
                    <template x-if="!loadingDemographics && demographics">
                        <div>
                            <!-- Summary KPIs -->
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="text-center p-3 bg-purple-500/5 dark:bg-purple-500/10 rounded-lg border border-purple-500/20">
                                    <p class="text-xl font-bold text-purple-600 dark:text-purple-400" x-text="formatNumber(demographics.total_lcms_students)"></p>
                                    <p class="text-xs text-purple-600/80 dark:text-purple-400/80">HV trong LCMS</p>
                                </div>
                                <div class="text-center p-3 bg-teal-500/5 dark:bg-teal-500/10 rounded-lg border border-teal-500/20">
                                    <p class="text-xl font-bold text-teal-600 dark:text-teal-400" x-text="formatNumber(demographics.total_assignments)"></p>
                                    <p class="text-xs text-teal-600/80 dark:text-teal-400/80">Tổng lượt giao bài</p>
                                </div>
                                <div class="text-center p-3 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNumber(demographics.students_with_scores)"></p>
                                    <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">HV có điểm</p>
                                </div>
                                <div class="text-center p-3 bg-orange-500/5 dark:bg-orange-500/10 rounded-lg border border-orange-500/20">
                                    <p class="text-xl font-bold text-orange-600 dark:text-orange-400" x-text="formatNumber(demographics.students_without_scores)"></p>
                                    <p class="text-xs text-orange-600/80 dark:text-orange-400/80">HV chưa có điểm</p>
                                </div>
                            </div>
                            <!-- Gender Distribution -->
                            <template x-if="demographics.gender_distribution && demographics.gender_distribution.length > 0">
                                <div>
                                    <p class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted mb-2">Phân bố Giới tính</p>
                                    <div class="space-y-2">
                                        <template x-for="g in demographics.gender_distribution" :key="g.gender">
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm text-light-text dark:text-zeus-text w-28 truncate" x-text="g.gender"></span>
                                                <div class="flex-1 score-bar">
                                                    <div class="score-bar-fill bg-violet-500" :style="'width: ' + (demographics.total_lcms_students > 0 ? Math.round(g.count / demographics.total_lcms_students * 100) : 0) + '%'"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-light-text dark:text-zeus-text w-10 text-right" x-text="g.count"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <!-- Sections & Students summary -->
                            <div class="mt-4 pt-3 border-t border-light-border dark:border-zeus-border">
                                <div class="flex items-center justify-between text-xs text-light-text-muted dark:text-zeus-text-muted">
                                    <span>Unique Sections: <strong class="text-light-text dark:text-zeus-text" x-text="formatNumber(demographics.unique_sections)"></strong></span>
                                    <span>Unique Students: <strong class="text-light-text dark:text-zeus-text" x-text="formatNumber(demographics.unique_students)"></strong></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loadingDemographics && !demographics">
                        <p class="text-sm text-light-text-muted dark:text-zeus-text-muted text-center py-6">Chưa có dữ liệu</p>
                    </template>
                </div>
            </div>

            <!-- ===== SECTION 3: Top Students & At Risk ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                <!-- Top Students -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        🏆 Top Học viên Điểm cao
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Xếp hạng</span><br>Top 10 HV có điểm trung bình (BTVN + BKT) cao nhất.<br>Chỉ tính Section đã làm đủ Quiz.<br><br><span class="tooltip-sql">SELECT sub.student_id,
  ROUND(AVG(sub.avg_section_score), 2)
    AS overall_avg_score,
  COUNT(sub.section_id) AS total_sections,
  SUM(sub.is_section_completed) AS completed,
  ROUND((SUM(sub.is_section_completed)
    / NULLIF(COUNT(sub.section_id), 0)) * 100, 2)
    AS completion_ratio
FROM (
  SELECT ua.usrasi_student_id AS student_id,
    ua.usrasi_section_id AS section_id,
    CASE WHEN MIN(ua.usrasi_completion_state) = 1
      THEN 1 ELSE 0 END AS is_section_completed,
    (SELECT CASE WHEN COUNT(ms.highest_score) >= (...)
      THEN AVG(ms.highest_score) ELSE NULL END
     FROM (SELECT MAX(CAST(ss.stusco_overall_score
       AS DECIMAL(10,2))) AS highest_score ...
       GROUP BY c2.cou_id) AS ms
    ) AS avg_section_score
  FROM lcms_user_assignments ua
  JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type IN (2, 3)
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub
GROUP BY sub.student_id
HAVING overall_avg_score IS NOT NULL
ORDER BY overall_avg_score DESC,
  completion_ratio DESC
LIMIT 10</span></span></span>
                    </h3>
                    <template x-if="loadingTop">
                        <div class="flex items-center justify-center py-6">
                            <span class="spinner-inline"></span>
                        </div>
                    </template>
                    <template x-if="!loadingTop && topStudents.length > 0">
                        <div class="space-y-2">
                            <template x-for="(s, idx) in topStudents" :key="s.student_id">
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                         :class="idx === 0 ? 'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400' : idx === 1 ? 'bg-gray-300/20 text-gray-600 dark:text-gray-400' : idx === 2 ? 'bg-amber-700/20 text-amber-700 dark:text-amber-500' : 'bg-light-border dark:bg-zeus-border text-light-text-muted dark:text-zeus-text-muted'"
                                         x-text="idx + 1"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-light-text dark:text-zeus-text truncate" x-text="s.student_name"></p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                                            <span x-text="'stu_user_id: ' + s.student_user_id"></span>
                                            <template x-if="s.student_email">
                                                <span x-text="' • ' + s.student_email"></span>
                                            </template>
                                        </p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'Hoàn thành: ' + s.completed_sections + '/' + s.total_sections + ' (' + s.completion_ratio + '%)'"></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400" x-text="s.overall_avg_score"></p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Điểm TB</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!loadingTop && topStudents.length === 0">
                        <p class="text-sm text-light-text-muted dark:text-zeus-text-muted text-center py-6">Chưa có dữ liệu</p>
                    </template>
                </div>

                <!-- At Risk Students -->
                <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                    <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                        ⚠️ Học viên Cần chú ý
                        <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Cảnh báo</span><br>Top 10 HV có tỉ lệ hoàn thành (BTVN + BKT) thấp nhất.<br>Cần hỗ trợ thêm để cải thiện tiến độ.<br><br><span class="tooltip-sql">SELECT sub.student_id,
  COUNT(sub.section_id) AS total_sections,
  SUM(sub.is_section_completed) AS completed,
  ROUND((SUM(sub.is_section_completed)
    / NULLIF(COUNT(sub.section_id), 0)) * 100, 2)
    AS completion_ratio
FROM (
  SELECT ua.usrasi_student_id AS student_id,
    ua.usrasi_section_id AS section_id,
    CASE WHEN MIN(ua.usrasi_completion_state) = 1
      THEN 1 ELSE 0 END AS is_section_completed
  FROM lcms_user_assignments ua
  JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type IN (2, 3)
    AND ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id,
    ua.usrasi_course_id, ua.usrasi_section_id
) AS sub
GROUP BY sub.student_id
HAVING total_sections > 0
ORDER BY completion_ratio ASC,
  total_sections DESC
LIMIT 10</span></span></span>
                    </h3>
                    <template x-if="loadingAtRisk">
                        <div class="flex items-center justify-center py-6">
                            <span class="spinner-inline"></span>
                        </div>
                    </template>
                    <template x-if="!loadingAtRisk && atRiskStudents.length > 0">
                        <div class="space-y-2">
                            <template x-for="(s, idx) in atRiskStudents" :key="s.student_id">
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 bg-red-500/20 text-red-600 dark:text-red-400" x-text="idx + 1"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-light-text dark:text-zeus-text truncate" x-text="s.student_name"></p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                                            <span x-text="'stu_user_id: ' + s.student_user_id"></span>
                                            <template x-if="s.student_email">
                                                <span x-text="' • ' + s.student_email"></span>
                                            </template>
                                        </p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted" x-text="'Hoàn thành: ' + s.completed_sections + '/' + s.total_sections"></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-lg font-bold" :class="s.completion_ratio < 30 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'" x-text="s.completion_ratio + '%'"></p>
                                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">Tỉ lệ HT</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!loadingAtRisk && atRiskStudents.length === 0">
                        <p class="text-sm text-light-text-muted dark:text-zeus-text-muted text-center py-6">Chưa có dữ liệu</p>
                    </template>
                </div>
            </div>

            <!-- ===== SECTION 4: Student Detail Table (Phase 122: Enhanced) ===== -->
            <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm">
                <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text mb-4 flex items-center gap-2">
                    👨‍🎓 Chi tiết Tiến độ Học viên
                    <span class="info-tooltip">ⓘ<span class="tooltip-content tooltip-wide"><span class="tooltip-label">Bảng chi tiết</span><br>Hiển thị tỉ lệ hoàn thành và điểm trung bình của từng học viên theo từng khóa học.<br>Hỗ trợ tìm kiếm theo ID, tên, giới tính, và lọc theo khoảng điểm/hoàn thành.<br>Nhấn vào HV để xem báo cáo chi tiết từng Section.<br>Lọc theo usrasi_course_id IN (346, 563, 595, 1084).<br><br><span class="tooltip-label">SQL — Danh sách HV (phân trang)</span><span class="tooltip-sql">SELECT DISTINCT sub.student_id, sub.course_id
FROM (
  SELECT ua.usrasi_student_id AS student_id,
    ua.usrasi_course_id AS course_id
  FROM lcms_user_assignments ua
  WHERE ua.usrasi_course_id IN (346,563,595,1084)
  GROUP BY ua.usrasi_student_id, ua.usrasi_course_id
) AS sub
-- Tìm kiếm nâng cao: LEFT JOIN lcms_students,
-- tbl_users để lọc theo tên, giới tính
ORDER BY sub.student_id ASC
LIMIT ? OFFSET ?</span><br><span class="tooltip-label">SQL — Stats per HV-course</span><span class="tooltip-sql">-- Completion:
SELECT COUNT(section_id) AS total,
  SUM(is_completed) AS completed
FROM (SELECT usrasi_section_id AS section_id,
  CASE WHEN MIN(usrasi_completion_state)=1
    THEN 1 ELSE 0 END AS is_completed
  FROM lcms_user_assignments ua
  JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
  WHERE c.cou_section_type = ?
    AND ua.usrasi_student_id = ?
    AND ua.usrasi_course_id = ?
  GROUP BY usrasi_student_id,
    usrasi_course_id, usrasi_section_id) sub
-- Score: AVG of MAX quiz scores per section</span></span></span>
                </h3>

                <!-- Phase 122: Enhanced Filters -->
                <div class="space-y-3 mb-4">
                    <!-- Row 1: Course + Search -->
                    <div class="flex flex-wrap items-center gap-3">
                        <select x-model="studentFilter.courseId" @change="loadStudentStats(1)" class="px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                            <option value="">Tất cả khóa học</option>
                            <template x-for="course in courses" :key="course.course_id">
                                <option :value="course.course_id" x-text="course.course_name + ' (ID: ' + course.course_id + ')'"></option>
                            </template>
                        </select>
                        <div class="relative">
                            <input type="text" x-model="studentFilter.studentIds" @keyup.enter="loadStudentStats(1)" placeholder="stu_user_id (VD: 9288 hoặc 9288,9305,9400)..." class="px-3 py-2 pl-9 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent w-64">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                        </div>
                        <div class="relative">
                            <input type="text" x-model="studentFilter.searchName" @keyup.enter="loadStudentStats(1)" placeholder="Tìm theo tên HV..." class="px-3 py-2 pl-9 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent w-48">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Row 2: Gender + Completion range + Score range + Actions -->
                    <div class="flex flex-wrap items-center gap-3">
                        <select x-model="studentFilter.gender" class="px-3 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                            <option value="">Giới tính</option>
                            <option value="Male">Nam (Male)</option>
                            <option value="Female">Nữ (Female)</option>
                        </select>
                        <div class="flex items-center gap-1 text-xs text-light-text-muted dark:text-zeus-text-muted">
                            <span>HT BTVN:</span>
                            <input type="number" x-model="studentFilter.minHwCompletion" placeholder="Min %" min="0" max="100" class="w-16 px-2 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                            <span>-</span>
                            <input type="number" x-model="studentFilter.maxHwCompletion" placeholder="Max %" min="0" max="100" class="w-16 px-2 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                            <span>%</span>
                        </div>
                        <div class="flex items-center gap-1 text-xs text-light-text-muted dark:text-zeus-text-muted">
                            <span>Điểm (thang 10):</span>
                            <input type="number" x-model="studentFilter.minScore" placeholder="Min" min="0" max="10" step="0.1" class="w-16 px-2 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                            <span>-</span>
                            <input type="number" x-model="studentFilter.maxScore" placeholder="Max" min="0" max="10" step="0.1" class="w-16 px-2 py-2 text-sm rounded-lg border border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card-light text-light-text dark:text-zeus-text focus:ring-2 focus:ring-zeus-accent">
                        </div>
                        <button @click="loadStudentStats(1)" class="px-4 py-2 text-sm bg-zeus-accent text-white rounded-lg hover:bg-zeus-accent-light transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Tìm
                        </button>
                        <button @click="clearStudentFilters()" class="px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted border border-light-border dark:border-zeus-border rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                            ✕ Xóa bộ lọc
                        </button>
                    </div>
                </div>

                <!-- Student Table -->
                <template x-if="loadingStudents">
                    <div class="flex items-center justify-center py-8">
                        <span class="spinner-inline spinner-lg"></span>
                        <span class="ml-3 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải...</span>
                    </div>
                </template>

                <template x-if="!loadingStudents && studentData.data && studentData.data.length > 0">
                    <div>
                        <div class="table-container">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-light-border dark:border-zeus-border">
                                        <th class="text-left py-3 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer select-none hover:text-light-text dark:hover:text-zeus-text transition" @click="sortStudents('student_name')">
                                            Học viên <span x-text="getSortIcon('student_name')" class="text-xs"></span>
                                        </th>
                                        <th class="text-left py-3 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium cursor-pointer select-none hover:text-light-text dark:hover:text-zeus-text transition" @click="sortStudents('course_name')">
                                            Khóa học <span x-text="getSortIcon('course_name')" class="text-xs"></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-blue-600 dark:text-blue-400 font-medium cursor-pointer select-none hover:opacity-80 transition" @click="sortStudents('hw_completion_ratio')">
                                            BTVN % <span x-text="getSortIcon('hw_completion_ratio')" class="text-xs"></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-blue-600 dark:text-blue-400 font-medium cursor-pointer select-none hover:opacity-80 transition" @click="sortStudents('hw_avg_score')">
                                            BTVN Điểm <span x-text="getSortIcon('hw_avg_score')" class="text-xs"></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-emerald-600 dark:text-emerald-400 font-medium cursor-pointer select-none hover:opacity-80 transition" @click="sortStudents('test_completion_ratio')">
                                            BKT % <span x-text="getSortIcon('test_completion_ratio')" class="text-xs"></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-emerald-600 dark:text-emerald-400 font-medium cursor-pointer select-none hover:opacity-80 transition" @click="sortStudents('test_avg_score')">
                                            BKT Điểm <span x-text="getSortIcon('test_avg_score')" class="text-xs"></span>
                                        </th>
                                        <th class="text-center py-3 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="s in sortedStudentData" :key="s.student_id + '-' + s.course_id">
                                        <tr class="border-b border-light-border/50 dark:border-zeus-border/50 hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition cursor-pointer" @click="openStudentDetail(s.student_id)">
                                            <td class="py-3 px-3">
                                                <div class="font-medium text-light-text dark:text-zeus-text text-sm" x-text="s.student_name"></div>
                                                <div class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                                                    <span x-text="'stu_user_id: ' + s.student_user_id"></span>
                                                    <template x-if="s.student_gender">
                                                        <span class="ml-1" x-text="'• ' + s.student_gender"></span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="py-3 px-2">
                                                <div class="text-sm text-light-text dark:text-zeus-text" x-text="s.course_name"></div>
                                            </td>
                                            <!-- Homework -->
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold text-sm" :class="getCompletionColor(s.homework.completion_ratio)" x-text="s.homework.completion_ratio + '%'"></span>
                                                <div class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="s.homework.completed_sections + '/' + s.homework.total_sections"></div>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold text-sm" :class="getScoreColor(s.homework.avg_score)" x-text="s.homework.avg_score !== null ? s.homework.avg_score : '—'"></span>
                                            </td>
                                            <!-- Test -->
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold text-sm" :class="getCompletionColor(s.test.completion_ratio)" x-text="s.test.completion_ratio + '%'"></span>
                                                <div class="text-[10px] text-light-text-muted dark:text-zeus-text-muted" x-text="s.test.completed_sections + '/' + s.test.total_sections"></div>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span class="font-semibold text-sm" :class="getScoreColor(s.test.avg_score)" x-text="s.test.avg_score !== null ? s.test.avg_score : '—'"></span>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <button @click.stop="openStudentDetail(s.student_id)" class="text-zeus-accent hover:text-zeus-accent-light transition" title="Xem chi tiết">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-light-border dark:border-zeus-border">
                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                                Trang <span x-text="studentData.page"></span> / <span x-text="studentData.last_page"></span>
                                (Tổng: <span x-text="formatNumber(studentData.total)"></span>)
                            </p>
                            <div class="flex items-center gap-2">
                                <button @click="loadStudentStats(studentData.page - 1)" :disabled="studentData.page <= 1" class="px-3 py-1.5 text-xs rounded-lg border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition disabled:opacity-40 disabled:cursor-not-allowed">
                                    ← Trước
                                </button>
                                <button @click="loadStudentStats(studentData.page + 1)" :disabled="studentData.page >= studentData.last_page" class="px-3 py-1.5 text-xs rounded-lg border border-light-border dark:border-zeus-border text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition disabled:opacity-40 disabled:cursor-not-allowed">
                                    Sau →
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!loadingStudents && (!studentData.data || studentData.data.length === 0)">
                    <p class="text-sm text-light-text-muted dark:text-zeus-text-muted text-center py-8">Không tìm thấy dữ liệu học viên</p>
                </template>
            </div>


            <!-- ===== SECTION 5: Student Detail Modal (Phase 122) ===== -->
            <template x-if="showStudentDetail">
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showStudentDetail = false" @keydown.escape.window="showStudentDetail = false">
                    <div class="bg-light-card dark:bg-zeus-card rounded-xl border border-light-border dark:border-zeus-border shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <!-- Modal Header -->
                        <div class="sticky top-0 z-10 bg-light-card dark:bg-zeus-card border-b border-light-border dark:border-zeus-border p-4 md:p-6 rounded-t-xl flex items-center justify-between">
                            <div>
                                <h3 class="text-base md:text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                                    📋 Báo cáo Chi tiết Học viên
                                </h3>
                                <template x-if="studentDetail">
                                    <p class="text-sm text-light-text-muted dark:text-zeus-text-muted mt-1">
                                        <span x-text="studentDetail.student_name"></span>
                                        <span class="ml-2 text-xs" x-text="'(stu_user_id: ' + studentDetail.student_user_id + ')'"></span>
                                        <template x-if="studentDetail.student_email">
                                            <span class="ml-2 text-xs" x-text="'• ' + studentDetail.student_email"></span>
                                        </template>
                                        <template x-if="studentDetail.student_gender">
                                            <span class="ml-2 text-xs" x-text="'• ' + studentDetail.student_gender"></span>
                                        </template>
                                    </p>
                                </template>
                            </div>
                            <button @click="showStudentDetail = false" class="p-2 rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition text-light-text-muted dark:text-zeus-text-muted">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Modal Loading -->
                        <template x-if="loadingStudentDetail">
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="spinner"></div>
                                <p class="mt-4 text-sm text-light-text-muted dark:text-zeus-text-muted">Đang tải báo cáo chi tiết...</p>
                            </div>
                        </template>

                        <!-- Modal Content -->
                        <template x-if="!loadingStudentDetail && studentDetail">
                            <div class="p-4 md:p-6 space-y-5">
                                <!-- Overall Summary KPIs -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="text-center p-3 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                                        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="studentDetail.summary.total_courses"></p>
                                        <p class="text-xs text-indigo-600/80 dark:text-indigo-400/80">Khóa học</p>
                                    </div>
                                    <div class="text-center p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="studentDetail.summary.homework.completion_ratio + '%'"></p>
                                        <p class="text-xs text-blue-600/80 dark:text-blue-400/80">HT BTVN tổng</p>
                                    </div>
                                    <div class="text-center p-3 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="studentDetail.summary.test.completion_ratio + '%'"></p>
                                        <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80">HT BKT tổng</p>
                                    </div>
                                    <div class="text-center p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-lg border border-amber-500/20">
                                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400" x-text="(studentDetail.summary.homework.avg_score !== null || studentDetail.summary.test.avg_score !== null) ? ((studentDetail.summary.homework.avg_score || 0) + (studentDetail.summary.test.avg_score || 0)) / ((studentDetail.summary.homework.avg_score !== null ? 1 : 0) + (studentDetail.summary.test.avg_score !== null ? 1 : 0)) : 'N/A'"></p>
                                        <p class="text-xs text-amber-600/80 dark:text-amber-400/80">Điểm TB chung</p>
                                    </div>
                                </div>

                                <!-- Overall Summary Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="p-3 bg-blue-500/5 dark:bg-blue-500/10 rounded-lg border border-blue-500/20">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-base">📝</span>
                                            <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">BTVN Tổng hợp</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                            <div>
                                                <p class="text-sm font-bold text-light-text dark:text-zeus-text" x-text="studentDetail.summary.homework.completed_sections + '/' + studentDetail.summary.homework.total_sections"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Hoàn thành</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="getCompletionColor(studentDetail.summary.homework.completion_ratio)" x-text="studentDetail.summary.homework.completion_ratio + '%'"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Tỉ lệ</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="getScoreColor(studentDetail.summary.homework.avg_score)" x-text="studentDetail.summary.homework.avg_score !== null ? studentDetail.summary.homework.avg_score : '—'"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Điểm TB</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-base">📋</span>
                                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">BKT Tổng hợp</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                            <div>
                                                <p class="text-sm font-bold text-light-text dark:text-zeus-text" x-text="studentDetail.summary.test.completed_sections + '/' + studentDetail.summary.test.total_sections"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Hoàn thành</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="getCompletionColor(studentDetail.summary.test.completion_ratio)" x-text="studentDetail.summary.test.completion_ratio + '%'"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Tỉ lệ</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="getScoreColor(studentDetail.summary.test.avg_score)" x-text="studentDetail.summary.test.avg_score !== null ? studentDetail.summary.test.avg_score : '—'"></p>
                                                <p class="text-light-text-muted dark:text-zeus-text-muted">Điểm TB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Per-Course Breakdown -->
                                <template x-for="course in studentDetail.courses" :key="course.course_id">
                                    <div class="border border-light-border dark:border-zeus-border rounded-lg overflow-hidden">
                                        <!-- Course Header -->
                                        <div class="p-3 bg-light-card-alt dark:bg-zeus-card-light flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <span class="text-sm font-semibold text-light-text dark:text-zeus-text" x-text="course.course_name"></span>
                                                <span class="text-xs text-light-text-muted dark:text-zeus-text-muted ml-2" x-text="'ID: ' + course.course_id"></span>
                                            </div>
                                            <div class="flex items-center gap-3 text-xs">
                                                <span class="text-blue-600 dark:text-blue-400" x-text="'BTVN: ' + course.homework.completion_ratio + '% | ' + (course.homework.avg_score !== null ? course.homework.avg_score + ' điểm' : '—')"></span>
                                                <span class="text-emerald-600 dark:text-emerald-400" x-text="'BKT: ' + course.test.completion_ratio + '% | ' + (course.test.avg_score !== null ? course.test.avg_score + ' điểm' : '—')"></span>
                                            </div>
                                        </div>
                                        <!-- Section Detail Table -->
                                        <template x-if="course.sections && course.sections.length > 0">
                                            <div class="table-container">
                                                <table class="w-full text-xs">
                                                    <thead>
                                                        <tr class="border-b border-light-border dark:border-zeus-border bg-light-card dark:bg-zeus-card">
                                                            <th class="text-left py-2 px-3 text-light-text-muted dark:text-zeus-text-muted font-medium">Section</th>
                                                            <th class="text-center py-2 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium">Loại</th>
                                                            <th class="text-center py-2 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium">Trạng thái</th>
                                                            <th class="text-center py-2 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium">Điểm</th>
                                                            <th class="text-center py-2 px-2 text-light-text-muted dark:text-zeus-text-muted font-medium">Ngày HT</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="sec in course.sections" :key="sec.section_id">
                                                            <tr class="border-b border-light-border/30 dark:border-zeus-border/30">
                                                                <td class="py-2 px-3">
                                                                    <span class="text-light-text dark:text-zeus-text" x-text="sec.section_name"></span>
                                                                    <span class="text-light-text-muted dark:text-zeus-text-muted ml-1" x-text="'(#' + sec.section_id + ')'"></span>
                                                                </td>
                                                                <td class="text-center py-2 px-2">
                                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="sec.section_type === 2 ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'" x-text="sec.section_type_label"></span>
                                                                </td>
                                                                <td class="text-center py-2 px-2">
                                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="sec.is_completed ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400'" x-text="sec.is_completed ? '✓ Hoàn thành' : '✕ Chưa HT'"></span>
                                                                </td>
                                                                <td class="text-center py-2 px-2">
                                                                    <span class="font-semibold" :class="getScoreColor(sec.score)" x-text="sec.score !== null ? sec.score : '—'"></span>
                                                                </td>
                                                                <td class="text-center py-2 px-2 text-light-text-muted dark:text-zeus-text-muted" x-text="sec.completion_time ? sec.completion_time.substring(0, 10) : '—'"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                        <template x-if="!course.sections || course.sections.length === 0">
                                            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted text-center py-4">Chưa có dữ liệu section</p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    /* Score progress bar */
    .score-bar {
        height: 6px;
        border-radius: 3px;
        background: rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .dark .score-bar {
        background: rgba(255,255,255,0.1);
    }
    .score-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease;
    }
</style>
@endpush

@push('scripts')
<script>
function lcmsReport() {
    return {
        loading: true,
        error: null,
        overview: {
            total_students: 0,
            total_courses: 0,
            homework: { total_sections: 0, completed_sections: 0, completion_ratio: 0, avg_score: null },
            test: { total_sections: 0, completed_sections: 0, completion_ratio: 0, avg_score: null },
            course_list: [],
        },
        courses: [],
        loadingCourses: true,
        topStudents: [],
        loadingTop: true,
        atRiskStudents: [],
        loadingAtRisk: true,
        studentData: { data: [], total: 0, page: 1, per_page: 20, last_page: 1 },
        loadingStudents: false,
        studentSortField: '',
        studentSortDir: 'asc',
        studentFilter: {
            courseId: '',
            studentIds: '',
            searchName: '',
            gender: '',
            minHwCompletion: '',
            maxHwCompletion: '',
            minScore: '',
            maxScore: '',
        },
        courseChart: null,
        // Phase 121: New state variables
        sectionDistData: [],
        loadingSectionDist: true,
        scoreDistData: { homework: [], test: [] },
        loadingScoreDist: true,
        trendData: [],
        loadingTrend: true,
        enrollment: [],
        loadingEnrollment: true,
        demographics: null,
        loadingDemographics: true,
        sectionDistChart: null,
        scoreDistChart: null,
        trendChart: null,
        // Phase 122: Student detail
        showStudentDetail: false,
        loadingStudentDetail: false,
        studentDetail: null,



        /**
         * Phase 127: Safely destroy a Chart.js instance
         * Prevents 'Cannot read properties of null' errors during animation frames
         */
        safeDestroyChart(chartRef) {
            if (chartRef) {
                try {
                    chartRef.destroy();
                } catch (e) {
                    // Ignore errors during destroy (canvas may already be detached)
                }
            }
            return null;
        },

        /**
         * Phase 176: Safely parse JSON from fetch response
         * Handles 524 timeout, non-JSON responses (HTML error pages), and network errors
         */
        async safeJsonParse(res, label) {
            if (!res.ok) {
                const status = res.status;
                if (status === 524) {
                    throw new Error(`[${label}] Server timeout (524). Dữ liệu đang được xử lý, vui lòng thử lại sau.`);
                }
                // Try to read response text to detect HTML error pages
                let bodyText = '';
                try { bodyText = await res.text(); } catch (_) {}
                if (bodyText.startsWith('<!DOCTYPE') || bodyText.startsWith('<html')) {
                    throw new Error(`[${label}] Server trả về lỗi (HTTP ${status}). Vui lòng thử lại sau.`);
                }
                // Try to parse as JSON error
                try {
                    const errJson = JSON.parse(bodyText);
                    throw new Error(errJson.message || `[${label}] Lỗi HTTP ${status}`);
                } catch (parseErr) {
                    if (parseErr.message.startsWith('[')) throw parseErr;
                    throw new Error(`[${label}] Lỗi HTTP ${status}`);
                }
            }
            // Response is OK — safely parse JSON
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error(`[${label}] Invalid JSON response:`, text.substring(0, 200));
                throw new Error(`[${label}] Phản hồi không hợp lệ từ server.`);
            }
        },

        /**
         * Phase 127: Check if canvas is ready for Chart.js rendering
         * Canvas must be in the DOM and have non-zero dimensions
         */
        isCanvasReady(canvas) {
            if (!canvas) return false;
            if (!canvas.getContext) return false;
            // Check canvas is attached to DOM and visible
            if (!document.body.contains(canvas)) return false;
            // Check parent is visible (offsetParent is null for hidden elements)
            const parent = canvas.closest('[x-show]') || canvas.parentElement;
            if (parent && parent.offsetParent === null && getComputedStyle(parent).position !== 'fixed') {
                return false;
            }
            return true;
        },

        async init() {
            // Destroy existing chart instances before re-init to prevent stale canvas references
            this.courseChart = this.safeDestroyChart(this.courseChart);
            this.sectionDistChart = this.safeDestroyChart(this.sectionDistChart);
            this.scoreDistChart = this.safeDestroyChart(this.scoreDistChart);
            this.trendChart = this.safeDestroyChart(this.trendChart);

            this.loading = true;
            this.error = null;
            FilterProgress.show();
            try {
                // Load overview first
                const overviewRes = await fetch('/api/lcms/overview');
                const overviewJson = await this.safeJsonParse(overviewRes, 'Tổng quan');
                if (overviewJson.success) {
                    this.overview = overviewJson.data;
                } else {
                    throw new Error(overviewJson.message || 'Lỗi tải dữ liệu tổng quan');
                }
                this.loading = false;
                FilterProgress.hide();

                // Load all data sections in parallel
                this.loadCourseBreakdown();
                this.loadTopStudents();
                this.loadAtRiskStudents();
                this.loadStudentStats(1);
                // Phase 121: Load new data
                this.loadSectionDistribution();
                this.loadScoreDistribution();
                this.loadCompletionTrend();
                this.loadEnrollmentOverview();
                this.loadStudentDemographics();
            } catch (e) {
                this.error = e.message || 'Có lỗi xảy ra khi tải dữ liệu';
                this.loading = false;
                FilterProgress.hide();
            }
        },

        async loadCourseBreakdown() {
            this.loadingCourses = true;
            try {
                const res = await fetch('/api/lcms/course-breakdown');
                const json = await this.safeJsonParse(res, 'Phân tích khóa học');
                if (json.success) {
                    this.courses = json.data;
                    this.$nextTick(() => requestAnimationFrame(() => this.renderCourseChart()));
                }
            } catch (e) {
                console.error('Load course breakdown error:', e);
            }
            this.loadingCourses = false;
        },

        async loadTopStudents() {
            this.loadingTop = true;
            try {
                const res = await fetch('/api/lcms/top-students?limit=10');
                const json = await this.safeJsonParse(res, 'Top học viên');
                if (json.success) {
                    this.topStudents = json.data;
                }
            } catch (e) {
                console.error('Load top students error:', e);
            }
            this.loadingTop = false;
        },

        async loadAtRiskStudents() {
            this.loadingAtRisk = true;
            try {
                const res = await fetch('/api/lcms/at-risk-students?limit=10');
                const json = await this.safeJsonParse(res, 'Học viên cần chú ý');
                if (json.success) {
                    this.atRiskStudents = json.data;
                }
            } catch (e) {
                console.error('Load at-risk students error:', e);
            }
            this.loadingAtRisk = false;
        },

        async loadStudentStats(page) {
            if (page < 1) return;
            this.loadingStudents = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 20,
                });
                if (this.studentFilter.courseId) params.append('course_id', this.studentFilter.courseId);
                if (this.studentFilter.studentIds) params.append('student_ids', this.studentFilter.studentIds);
                if (this.studentFilter.searchName) params.append('search_name', this.studentFilter.searchName);
                if (this.studentFilter.gender) params.append('gender', this.studentFilter.gender);
                if (this.studentFilter.minHwCompletion) params.append('min_hw_completion', this.studentFilter.minHwCompletion);
                if (this.studentFilter.maxHwCompletion) params.append('max_hw_completion', this.studentFilter.maxHwCompletion);
                if (this.studentFilter.minScore) params.append('min_score', this.studentFilter.minScore);
                if (this.studentFilter.maxScore) params.append('max_score', this.studentFilter.maxScore);

                // Use advanced search API if any advanced filter is active, otherwise use simple API
                const hasAdvancedFilters = this.studentFilter.studentIds || this.studentFilter.searchName || this.studentFilter.gender || this.studentFilter.minHwCompletion || this.studentFilter.maxHwCompletion || this.studentFilter.minScore || this.studentFilter.maxScore;
                const apiUrl = hasAdvancedFilters ? '/api/lcms/student-stats-advanced' : '/api/lcms/student-stats';

                // For simple API, map studentIds to search param (single ID backwards compat)
                if (!hasAdvancedFilters && this.studentFilter.studentIds) {
                    params.append('search', this.studentFilter.studentIds);
                }

                const res = await fetch(apiUrl + '?' + params.toString());
                const json = await this.safeJsonParse(res, 'Thống kê học viên');
                if (json.success) {
                    this.studentData = json.data;
                }
            } catch (e) {
                console.error('Load student stats error:', e);
            }
            this.loadingStudents = false;
        },

        clearStudentFilters() {
            this.studentFilter = {
                courseId: '',
                studentIds: '',
                searchName: '',
                gender: '',
                minHwCompletion: '',
                maxHwCompletion: '',
                minScore: '',
                maxScore: '',
            };
            this.studentSortField = '';
            this.studentSortDir = 'asc';
            this.loadStudentStats(1);
        },

        // === Phase 121: New data loading methods ===

        async loadSectionDistribution() {
            this.loadingSectionDist = true;
            try {
                const res = await fetch('/api/lcms/section-distribution');
                const json = await this.safeJsonParse(res, 'Phân bố section');
                if (json.success) {
                    this.sectionDistData = json.data;
                    this.$nextTick(() => requestAnimationFrame(() => this.renderSectionDistChart()));
                }
            } catch (e) {
                console.error('Load section distribution error:', e);
            }
            this.loadingSectionDist = false;
        },

        async loadScoreDistribution() {
            this.loadingScoreDist = true;
            try {
                const res = await fetch('/api/lcms/score-distribution');
                const json = await this.safeJsonParse(res, 'Phân bố điểm');
                if (json.success) {
                    this.scoreDistData = json.data;
                    this.$nextTick(() => requestAnimationFrame(() => this.renderScoreDistChart()));
                }
            } catch (e) {
                console.error('Load score distribution error:', e);
            }
            this.loadingScoreDist = false;
        },

        async loadCompletionTrend() {
            this.loadingTrend = true;
            try {
                const res = await fetch('/api/lcms/completion-trend');
                const json = await this.safeJsonParse(res, 'Xu hướng hoàn thành');
                if (json.success) {
                    this.trendData = json.data;
                    this.$nextTick(() => requestAnimationFrame(() => this.renderTrendChart()));
                }
            } catch (e) {
                console.error('Load completion trend error:', e);
            }
            this.loadingTrend = false;
        },

        async loadEnrollmentOverview() {
            this.loadingEnrollment = true;
            try {
                const res = await fetch('/api/lcms/enrollment-overview');
                const json = await this.safeJsonParse(res, 'Tổng quan enrollment');
                if (json.success) {
                    this.enrollment = json.data;
                }
            } catch (e) {
                console.error('Load enrollment overview error:', e);
            }
            this.loadingEnrollment = false;
        },

        async loadStudentDemographics() {
            this.loadingDemographics = true;
            try {
                const res = await fetch('/api/lcms/student-demographics');
                const json = await this.safeJsonParse(res, 'Nhân khẩu học');
                if (json.success) {
                    this.demographics = json.data;
                }
            } catch (e) {
                console.error('Load student demographics error:', e);
            }
            this.loadingDemographics = false;
        },

        // === Phase 121: New chart rendering methods ===

        renderSectionDistChart() {
            const canvas = document.getElementById('sectionDistChart');
            if (!this.isCanvasReady(canvas) || this.sectionDistData.length === 0) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            this.sectionDistChart = this.safeDestroyChart(this.sectionDistChart);
            try {

            const isDark = document.documentElement.classList.contains('dark');
            const colors = ['rgba(99, 102, 241, 0.8)', 'rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)'];
            const borderColors = ['rgba(99, 102, 241, 1)', 'rgba(59, 130, 246, 1)', 'rgba(16, 185, 129, 1)', 'rgba(245, 158, 11, 1)'];

            this.sectionDistChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.sectionDistData.map(d => d.label),
                    datasets: [{
                        data: this.sectionDistData.map(d => d.count),
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: isDark ? '#E5E7EB' : '#1E293B',
                                font: { size: 11 },
                                padding: 12,
                                usePointStyle: true,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                }
                            }
                        }
                    },
                },
            });
            } catch (e) { console.warn('Chart render error (sectionDist):', e.message); }
        },

        renderScoreDistChart() {
            const canvas = document.getElementById('scoreDistChart');
            if (!this.isCanvasReady(canvas)) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            this.scoreDistChart = this.safeDestroyChart(this.scoreDistChart);
            try {

            const isDark = document.documentElement.classList.contains('dark');
            const hwData = this.scoreDistData.homework || [];
            const testData = this.scoreDistData.test || [];
            const labels = hwData.map(d => d.range);

            this.scoreDistChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'BTVN',
                            data: hwData.map(d => d.count),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'BKT',
                            data: testData.map(d => d.count),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Số HV', color: isDark ? '#9CA3AF' : '#64748B' },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B', stepSize: 1 },
                            grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        },
                        x: {
                            title: { display: true, text: 'Khoảng điểm (thang 10)', color: isDark ? '#9CA3AF' : '#64748B' },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B' },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: { color: isDark ? '#E5E7EB' : '#1E293B', font: { size: 11 } },
                        },
                    },
                    animation: { duration: 0 },
                },
            });
            } catch (e) { console.warn('Chart render error (scoreDist):', e.message); }
        },

        renderTrendChart() {
            const canvas = document.getElementById('completionTrendChart');
            if (!this.isCanvasReady(canvas) || this.trendData.length === 0) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            this.trendChart = this.safeDestroyChart(this.trendChart);
            try {

            const isDark = document.documentElement.classList.contains('dark');

            this.trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.trendData.map(d => d.month),
                    datasets: [{
                        label: 'Hoàn thành',
                        data: this.trendData.map(d => d.completions),
                        borderColor: 'rgba(99, 102, 241, 1)',
                        backgroundColor: 'rgba(99, 102, 241, 0.15)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                        tension: 0.3,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Lượt HT', color: isDark ? '#9CA3AF' : '#64748B' },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B' },
                            grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        },
                        x: {
                            title: { display: true, text: 'Tháng', color: isDark ? '#9CA3AF' : '#64748B' },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B', maxRotation: 45 },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: { color: isDark ? '#E5E7EB' : '#1E293B', font: { size: 11 } },
                        },
                    },
                    animation: { duration: 0 },
                },
            });
            } catch (e) { console.warn('Chart render error (trend):', e.message); }
        },

        renderCourseChart() {
            const canvas = document.getElementById('courseCompletionChart');
            if (!this.isCanvasReady(canvas) || this.courses.length === 0) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            this.courseChart = this.safeDestroyChart(this.courseChart);
            try {

            const isDark = document.documentElement.classList.contains('dark');
            const labels = this.courses.map(c => c.course_name || `Course ${c.course_id}`);
            const hwRates = this.courses.map(c => c.homework.completion_ratio);
            const testRates = this.courses.map(c => c.test.completion_ratio);
            const hwScores = this.courses.map(c => c.homework.avg_score);
            const testScores = this.courses.map(c => c.test.avg_score);

            this.courseChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'BTVN Hoàn thành (%)',
                            data: hwRates,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'BKT Hoàn thành (%)',
                            data: testRates,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Điểm TB BTVN',
                            data: hwScores,
                            type: 'line',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                            yAxisID: 'y1',
                            tension: 0.3,
                        },
                        {
                            label: 'Điểm TB BKT',
                            data: testScores,
                            type: 'line',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointBackgroundColor: 'rgba(245, 158, 11, 1)',
                            yAxisID: 'y1',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Tỉ lệ hoàn thành (%)',
                                color: isDark ? '#9CA3AF' : '#64748B',
                            },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B' },
                            grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                        },
                        y1: {
                            beginAtZero: true,
                            max: 10,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Điểm trung bình (thang 10)',
                                color: isDark ? '#9CA3AF' : '#64748B',
                            },
                            ticks: { color: isDark ? '#9CA3AF' : '#64748B' },
                            grid: { display: false },
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#9CA3AF' : '#64748B',
                                maxRotation: 45,
                                font: { size: 11 },
                            },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: { color: isDark ? '#E5E7EB' : '#1E293B', font: { size: 12 } },
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const val = ctx.parsed.y;
                                    if (val === null) return ctx.dataset.label + ': N/A';
                                    if (ctx.dataset.yAxisID === 'y1') return ctx.dataset.label + ': ' + val;
                                    return ctx.dataset.label + ': ' + val + '%';
                                }
                            }
                        }
                    },
                    animation: { duration: 0 },
                },
            });
            } catch (e) { console.warn('Chart render error (course):', e.message); }
        },

        // === Phase 122: Student detail methods ===

        async openStudentDetail(studentId) {
            this.showStudentDetail = true;
            this.loadingStudentDetail = true;
            this.studentDetail = null;
            try {
                const res = await fetch('/api/lcms/student-detail?student_id=' + studentId);
                const json = await this.safeJsonParse(res, 'Chi tiết học viên');
                if (json.success) {
                    this.studentDetail = json.data;
                } else {
                    console.error('Student detail error:', json.message);
                    this.showStudentDetail = false;
                }
            } catch (e) {
                console.error('Load student detail error:', e);
                this.showStudentDetail = false;
            }
            this.loadingStudentDetail = false;
        },

        formatNumber(n) {
            if (n === null || n === undefined) return '0';
            return n.toLocaleString('vi-VN');
        },

        getCompletionColor(ratio) {
            if (ratio >= 80) return 'text-emerald-600 dark:text-emerald-400';
            if (ratio >= 50) return 'text-amber-600 dark:text-amber-400';
            if (ratio >= 20) return 'text-orange-600 dark:text-orange-400';
            return 'text-red-600 dark:text-red-400';
        },

        getScoreColor(score) {
            if (score === null) return 'text-light-text-muted dark:text-zeus-text-muted';
            if (score >= 8) return 'text-emerald-600 dark:text-emerald-400';
            if (score >= 6) return 'text-blue-600 dark:text-blue-400';
            if (score >= 4) return 'text-amber-600 dark:text-amber-400';
            return 'text-red-600 dark:text-red-400';
        },

        // === Phase 123: Sorting for student detail table ===

        get sortedStudentData() {
            if (!this.studentData.data || !this.studentSortField) {
                return this.studentData.data || [];
            }
            const data = [...this.studentData.data];
            const dir = this.studentSortDir === 'asc' ? 1 : -1;
            const field = this.studentSortField;

            data.sort((a, b) => {
                let valA, valB;
                switch (field) {
                    case 'student_name':
                        valA = (a.student_name || '').toLowerCase();
                        valB = (b.student_name || '').toLowerCase();
                        return valA < valB ? -dir : valA > valB ? dir : 0;
                    case 'course_name':
                        valA = (a.course_name || '').toLowerCase();
                        valB = (b.course_name || '').toLowerCase();
                        return valA < valB ? -dir : valA > valB ? dir : 0;
                    case 'hw_completion_ratio':
                        valA = a.homework.completion_ratio ?? -1;
                        valB = b.homework.completion_ratio ?? -1;
                        return (valA - valB) * dir;
                    case 'hw_avg_score':
                        valA = a.homework.avg_score ?? -1;
                        valB = b.homework.avg_score ?? -1;
                        return (valA - valB) * dir;
                    case 'test_completion_ratio':
                        valA = a.test.completion_ratio ?? -1;
                        valB = b.test.completion_ratio ?? -1;
                        return (valA - valB) * dir;
                    case 'test_avg_score':
                        valA = a.test.avg_score ?? -1;
                        valB = b.test.avg_score ?? -1;
                        return (valA - valB) * dir;
                    default:
                        return 0;
                }
            });
            return data;
        },

        sortStudents(field) {
            if (this.studentSortField === field) {
                this.studentSortDir = this.studentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.studentSortField = field;
                this.studentSortDir = 'asc';
            }
        },

        getSortIcon(field) {
            if (this.studentSortField !== field) return '⇅';
            return this.studentSortDir === 'asc' ? '↑' : '↓';
        },
    };
}
</script>
@endpush
