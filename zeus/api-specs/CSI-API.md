# CSI API - Đặc tả API trang "Chăm sóc CSI"

> **Trang**: `/csi` — Chăm sóc CSI Dashboard  
> **Controller**: `App\Http\Controllers\CsiController`  
> **Service**: `App\Services\CsiService` (zeus_core MySQL), `App\Services\LcmsService` (LCMS enrichment)  
> **Middleware**: `auth.admin` (tất cả route đều yêu cầu đăng nhập admin)  
> **Base URL**: `/api/csi`

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Bảng dữ liệu sử dụng](#2-bảng-dữ-liệu-sử-dụng)
3. [Acceptance Code Reference](#3-acceptance-code-reference)
4. [Page Route](#4-page-route)
5. [API Endpoints](#5-api-endpoints)
   - 5.1 [GET /api/csi/summary](#51-get-apicsisummary)
   - 5.2 [GET /api/csi/students](#52-get-apicsistudents)
   - 5.3 [GET /api/csi/health-distribution](#53-get-apicsihealth-distribution)
   - 5.4 [GET /api/csi/css-performance](#54-get-apicsicss-performance)
   - 5.5 [GET /api/csi/score-distribution](#55-get-apicsiscore-distribution)
   - 5.6 [GET /api/csi/teacher-warning](#56-get-apicsiteacher-warning)
   - 5.7 [GET /api/csi/ews](#57-get-apicsiews)
   - 5.8 [GET /api/csi/ews/{studentId}/detail](#58-get-apicsiewsstudentiddetail)
   - 5.9 [GET /api/csi/students/{studentId}/detail](#59-get-apicsistudentsstudentiddetail)
   - 5.10 [GET /api/csi/trends](#510-get-apicsitrends)
   - 5.11 [GET /api/csi/health-trends](#511-get-apicsihealth-trends)
   - 5.12 [GET /api/csi/ontrack-trends](#512-get-apicsiontrack-trends)
   - 5.13 [GET /api/csi/search](#513-get-apicsisearch)
   - 5.14 [GET /api/csi/spw-inactive](#514-get-apicsispw-inactive)

---

## 1. Tổng quan kiến trúc

### Luồng dữ liệu

```
Browser (Alpine.js)
  ├── fetch('/api/csi/summary?...')          → CsiController::apiSummary()     → CsiService::getSummary()
  ├── fetch('/api/csi/students?...')         → CsiController::apiStudents()    → CsiService::getStudents() + LcmsService
  ├── fetch('/api/csi/health-distribution?') → CsiController::apiHealthDistribution() → CsiService::getHealthDistribution()
  ├── fetch('/api/csi/css-performance?')     → CsiController::apiCssPerformance()     → CsiService::getCssPerformance()
  ├── fetch('/api/csi/score-distribution?')  → CsiController::apiScoreDistribution()  → CsiService::getScoreDistribution()
  ├── fetch('/api/csi/teacher-warning?')     → CsiController::apiTeacherWarning()     → CsiService::getTeacherWarningDistribution()
  ├── fetch('/api/csi/ews?...')              → CsiController::apiEws()          → CsiService::getEwsStudents()
  ├── fetch('/api/csi/ews/{id}/detail?')     → CsiController::apiEwsDetail()    → CsiService::getEwsStudentDetail()
  ├── fetch('/api/csi/students/{id}/detail') → CsiController::apiStudentDetail() → CsiService::getStudentDetail() + LcmsService
  ├── fetch('/api/csi/trends?...')           → CsiController::apiTrends()       → CsiService::getTrends()
  ├── fetch('/api/csi/health-trends?...')    → CsiController::apiHealthTrends() → CsiService::getHealthTrends()
  ├── fetch('/api/csi/ontrack-trends?...')   → CsiController::apiOntrackTrends() → CsiService::getOntrackTrends()
  ├── fetch('/api/csi/search?q=...')         → CsiController::apiSearch()       → CsiService::searchStudent()
  └── fetch('/api/csi/spw-inactive?...')     → CsiController::apiSpwInactive()  → CsiService::getInactiveStudentsList()
```

### Cơ chế tính toán CSI

Tất cả chỉ số CSI được tính toán trực tiếp (live) từ database zeus_core MySQL thông qua **Common Table Expression (CTE)**:

1. **`joined`** CTE: Lọc buổi học SPEAKWELL hoàn thành (`ordles_status = 3`) từ `tbl_order_lessons`, kèm `ole_acceptance_code` từ `tbl_order_lessons_extras` (lấy row mới nhất theo `ole_id DESC`).
2. **`first_3_ranked`** / **`first_3_pivot`** CTE: Xếp hạng 3 buổi học đầu tiên của mỗi HV (loại trừ trial) để tính first-3 success rate.
3. **`leave_per_student`** CTE: Đếm số buổi GV xin nghỉ phép ảnh hưởng đến HV.
4. **`csi_data`** CTE: Tổng hợp metrics per-student (total_scheduled, total_success, student_noshow, health_score, ...).
5. **`csi_full`** CTE: Thêm `health_category` (Xanh/Vàng/Đỏ) và `teacher_warning` classification.

### Điều kiện lọc SPEAKWELL

```sql
FIND_IN_SET(ordles_tlang_id, (
    SELECT REPLACE(conf_val, ' ', '')
    FROM tbl_configurations
    WHERE conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS'
    LIMIT 1
))
```

Hiện tại có **36 mã khóa học SPEAKWELL** (bao gồm trial id 533):
`389, 390, 392, 403, 404, 405, 406, 407, 411, 412, 413, 414, 415, 416, 471, 533, 558, 560, 562, 564, 567, 568, 569, 571, 572, 574, 575, 576, 577, 580, 581, 582, 583, 584, 585, 586`

### Phân loại chương trình (SPEAKWELL vs EASYSPEAK)

- **EASYSPEAK**: `ordles_tlang_id IN (403, 404, 471, 582, 583, 584, 585, 586)` — 8 subject IDs
- **SPEAKWELL**: Tất cả `ordles_tlang_id` còn lại trong danh sách CONF_SPEAKWELL_SUBJECT_IDS

---

## 2. Bảng dữ liệu sử dụng

| Bảng | Database | Mục đích |
|------|----------|----------|
| `tbl_order_lessons` | zeus_core | Dữ liệu buổi học (chính) |
| `tbl_order_lessons_extras` | zeus_core | Acceptance code cho mỗi buổi |
| `tbl_users` | zeus_core | Thông tin học viên (tên, email) |
| `tbl_user_settings` | zeus_core | Số điện thoại HV |
| `tbl_user_extras` | zeus_core | Mapping HV → CSS staff (usrextra_css_id) |
| `tbl_admin` | zeus_core | Tên chuyên viên CSS (admin_username) |
| `tbl_configurations` | zeus_core | Config SPEAKWELL subject IDs, trial subject ID |
| `tbl_teacher_leave_requests` | zeus_core | Đơn xin nghỉ phép GV |
| `tbl_teacher_leave_request_sessions` | zeus_core | Chi tiết buổi học bị ảnh hưởng khi GV nghỉ |
| `tbl_orders` | zeus_core | Thông tin đơn hàng (student detail) |
| `tbl_payment_methods` | zeus_core | Phương thức thanh toán |
| `tbl_order_subscription_plans` | zeus_core | Gói đăng ký HV |
| `tbl_subscription_plans` | zeus_core | Thông tin gói subscription |
| `tbl_order_classes` | zeus_core | Lớp nhóm (group classes) — cho SpeakWell stats |
| `tbl_group_classes` | zeus_core | Chi tiết lớp nhóm |
| `lcms_user_assignments` | zeus_core | LCMS assignments — enrichment cho students & student detail |
| `lcms_courses` | zeus_core | LCMS courses — enrichment |
| `lcms_students` | zeus_core | LCMS student mapping |
| `lcms_student_scores` | zeus_core | LCMS scores — enrichment |

---

## 3. Acceptance Code Reference

| Code | Ý nghĩa | Phân loại | GV Noshow? |
|------|----------|-----------|------------|
| `0` | HV Noshow, GV Noshow | `student_noshow` | ✅ |
| `1` | Khác | — | — |
| `2` | HV < 1/2 giờ, GV Noshow | `student_half` | ✅ |
| `3` | Thành công, GV Noshow | `total_success` | ✅ |
| `4` | HV Noshow, GV < 1/2 giờ | `student_noshow` | ❌ |
| `5` | HV < 1/2 giờ, GV < 1/2 giờ | `student_half` | ❌ |
| `6` | Thành công, GV < 1/2 giờ | `total_success` | ❌ |
| `7` | HV Noshow, GV > 1/2 giờ | `student_noshow` | ❌ |
| `8` | HV < 1/2 giờ, GV > 1/2 giờ | `student_half` | ❌ |
| `9` | Thành công, GV > 1/2 giờ | `total_success` | ❌ |
| `10` | HV Noshow, GV đầy đủ | `student_noshow` | ❌ |
| `11` | HV < 1/2 giờ, GV đầy đủ | `student_half` | ❌ |
| `12` | Thành công, GV đầy đủ | `total_success` | ❌ |
| `NULL` | Chưa có dữ liệu | `student_noshow` | ✅ |

**Tóm tắt phân nhóm:**
- **Buổi thành công** (success): `ole_acceptance_code IN (3, 6, 9, 12)`
- **HV Noshow**: `ole_acceptance_code IN (0, 4, 7, 10) OR IS NULL`
- **HV < 1/2 giờ** (half): `ole_acceptance_code IN (2, 5, 8, 11)`
- **GV Noshow**: `ole_acceptance_code IN (0, 2, 3) OR IS NULL`

---

## 4. Page Route

### `GET /csi`

**Mô tả**: Render trang "Chăm sóc CSI Dashboard" (Blade view).

**Middleware**: `auth.admin`

**Controller**: `CsiController::index()`

**Server-side data** (truyền vào Blade template):

| Biến | Kiểu | Mô tả |
|------|------|-------|
| `isAvailable` | `bool` | Có dữ liệu CSI hay không (check COUNT buổi học SPEAKWELL sau 2025-11-04) |
| `meta` | `array` | `{ imported_at, source }` — metadata nguồn dữ liệu |
| `cssStaffList` | `string[]` | Danh sách tên CSS staff (distinct) cho dropdown filter |

**Ghi chú**: Trang sử dụng Alpine.js. Sau khi render, frontend gọi các API endpoints bên dưới để tải dữ liệu.

---

## 5. API Endpoints

### Bộ lọc chung (Common Filters)

Các endpoint 5.1–5.6 sử dụng chung bộ lọc `extractFilters()`:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `health_category` | `string` | `""` | Nhóm rủi ro: `""` (tất cả), `"red_yellow"`, `"red"`, `"yellow"`, `"green"` |
| `css_staff` | `string` | `""` | Tên chuyên viên CSS (khớp chính xác `admin_username`) |
| `teacher_warning` | `string` | `""` | Mức cảnh báo GV: `""`, `"has_warning"`, `"Bình thường"`, `"Có ảnh hưởng (GV nghỉ 1b)"`, `"Nghiêm trọng (GV nghỉ >=2b)"`, `"Khẩn cấp (GV nghỉ >= 4 buổi)"` |
| `search` | `string` | `""` | Tìm kiếm HV theo ID, email, tên hoặc SĐT (LIKE %...%) |
| `date_from` | `string` | `""` | Ngày bắt đầu lọc (Y-m-d). Mặc định: `2025-11-04` |
| `date_to` | `string` | `""` | Ngày kết thúc lọc (Y-m-d). Mặc định: `NOW()` |
| `lesson_1_from` | `string` | `""` | Buổi 1 từ ngày (Y-m-d) — lọc theo `lesson_1_date` |
| `lesson_1_to` | `string` | `""` | Buổi 1 đến ngày (Y-m-d) — lọc theo `lesson_1_date` |
| `first_3_from` | `string` | `""` | 3 buổi đầu từ ngày (Y-m-d) — lọc HV có `first_3_total >= 3` và `lesson_1_date >= first_3_from` |
| `first_3_to` | `string` | `""` | 3 buổi đầu đến ngày (Y-m-d) — lọc HV có `first_3_total >= 3` và `lesson_3_date <= first_3_to` |
| `ontrack_status` | `string` | `""` | Trạng thái ontrack: `""`, `"ontrack"` (health_score ≥ 90), `"not_ontrack"` (health_score < 90) |
| `program` | `string` | `""` | Chương trình: `""`, `"SPEAKWELL"`, `"EASYSPEAK"` |

---

### 5.1 `GET /api/csi/summary`

**Mô tả**: Lấy tổng hợp KPI cho các thẻ chỉ số tổng quan (KPI cards) trên đầu trang.

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters)

**Response** (`application/json`):

```json
{
    "total_students": 450,
    "green": 280,
    "yellow": 120,
    "red": 50,
    "no_class": 0,
    "green_pct": 62.2,
    "yellow_pct": 26.7,
    "red_pct": 11.1,
    "total_scheduled": 12500,
    "total_success": 10200,
    "total_noshow": 1500,
    "total_half": 800,
    "total_teacher_noshow": 350,
    "avg_score": 78.5,
    "success_rate": 81.6,
    "avg_lessons_per_week": 2.35,
    "total_active": 380,
    "ontrack_count": 250,
    "ontrack_rate": 65.8,
    "teacher_warning": {
        "normal": 350,
        "affect_1": 60,
        "serious_2": 30,
        "critical_4": 10
    },
    "leave_affected": {
        "total_affected_sessions": 120,
        "need_replacement": 80,
        "no_replacement": 40
    },
    "speakwell_total": 800,
    "speakwell_active": 450,
    "speakwell_inactive": 350
}
```

**Chi tiết response fields:**

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `total_students` | `int` | Tổng số HV SPEAKWELL có buổi học hoàn thành thỏa mãn bộ lọc |
| `green` | `int` | Số HV nhóm Xanh (health_score ≥ 85%) |
| `yellow` | `int` | Số HV nhóm Vàng (60% ≤ health_score < 85%) |
| `red` | `int` | Số HV nhóm Đỏ (health_score < 60%) |
| `no_class` | `int` | Luôn = 0 (live query chỉ trả HV có buổi học) |
| `green_pct` | `float` | % HV Xanh = green / total_students × 100 |
| `yellow_pct` | `float` | % HV Vàng = yellow / total_students × 100 |
| `red_pct` | `float` | % HV Đỏ = red / total_students × 100 |
| `total_scheduled` | `int` | Tổng buổi hoàn thành (SUM total_scheduled của tất cả HV) |
| `total_success` | `int` | Tổng buổi thành công (acceptance_code IN 3,6,9,12) |
| `total_noshow` | `int` | Tổng buổi HV noshow (acceptance_code IN 0,4,7,10 hoặc NULL) |
| `total_half` | `int` | Tổng buổi HV < 1/2 giờ (acceptance_code IN 2,5,8,11) |
| `total_teacher_noshow` | `int` | Tổng buổi GV noshow (acceptance_code IN 0,2,3 hoặc NULL) |
| `avg_score` | `float` | Trung bình cộng health_score (%) của tất cả HV |
| `success_rate` | `float` | Tỉ lệ học TC = total_success / total_scheduled × 100 |
| `avg_lessons_per_week` | `float` | Trung bình cộng avg_per_week của các HV. Mỗi HV: avg_per_week = buổi thành công / tổng_tuần |
| `total_active` | `int` | Số HV có ít nhất 1 buổi thành công code 12 (GV đầy đủ) |
| `ontrack_count` | `int` | Số HV có ontrack_score ≥ 90% (ontrack_score = buổi code 12 / tổng buổi × 100) |
| `ontrack_rate` | `float` | Trung bình cộng cột Ontrack (%) từ bảng số liệu chi tiết theo tuần |
| `teacher_warning.normal` | `int` | HV không bị ảnh hưởng bởi GV nghỉ |
| `teacher_warning.affect_1` | `int` | HV bị GV nghỉ 1 buổi |
| `teacher_warning.serious_2` | `int` | HV bị GV nghỉ ≥ 2 buổi |
| `teacher_warning.critical_4` | `int` | HV bị GV nghỉ ≥ 4 buổi |
| `leave_affected.total_affected_sessions` | `int` | Tổng buổi bị ảnh hưởng do GV xin nghỉ phép (đã duyệt) |
| `leave_affected.need_replacement` | `int` | Buổi cần GV thay thế |
| `leave_affected.no_replacement` | `int` | Buổi không cần thay thế |
| `speakwell_total` | `int` | Tổng HV SpeakWell (1-1 + lớp nhóm, distinct) |
| `speakwell_active` | `int` | HV Active SpeakWell (last 30 days, đã hoàn thành buổi hoặc lớp nhóm đang mở) |
| `speakwell_inactive` | `int` | HV Inactive SpeakWell = total - active |

**SQL chính** (getSummary):

```sql
-- CTE tính toán CSI từ tbl_order_lessons + tbl_order_lessons_extras
WITH joined AS (...), first_3_ranked AS (...), first_3_pivot AS (...),
     leave_per_student AS (...), csi_data AS (...), csi_full AS (...)
SELECT
    COUNT(*) as total_students,
    SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) as green_count,
    SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) as yellow_count,
    SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) as red_count,
    SUM(total_scheduled) as total_scheduled,
    SUM(total_success) as total_success,
    SUM(student_noshow) as total_noshow,
    SUM(student_half) as total_half,
    SUM(teacher_noshow) as total_teacher_noshow,
    ROUND(AVG(health_score), 1) as avg_score,
    -- ... teacher_warning counts, ontrack counts, avg_lessons_per_week
FROM csi_full
WHERE <bộ lọc>
```

**SQL phụ** (SpeakWell stats — không bị ảnh hưởng bởi bộ lọc chung):

```sql
-- Tổng HV SpeakWell (1-1 UNION lớp nhóm)
SELECT COUNT(DISTINCT t.student_id) FROM (
    SELECT DISTINCT ol.ordles_beneficiary_id AS student_id
    FROM tbl_order_lessons ol WHERE ol.ordles_tlang_id IN (389,390,...,586)
    UNION ALL
    SELECT DISTINCT oc.ordcls_beneficiary_id AS student_id
    FROM tbl_order_classes oc
    INNER JOIN tbl_group_classes gce ON oc.ordcls_grpcls_id = gce.grpcls_id
    WHERE gce.grpcls_tlang_id IN (389,390,...,586)
) t
```

**SQL phụ** (Leave affected sessions):

```sql
SELECT
    COUNT(*) as total_affected_sessions,
    SUM(CASE WHEN lrs.tlrs_need_replacement = 1 THEN 1 ELSE 0 END) as need_replacement,
    SUM(CASE WHEN lrs.tlrs_need_replacement = 0 OR lrs.tlrs_need_replacement IS NULL THEN 1 ELSE 0 END) as no_replacement
FROM tbl_teacher_leave_requests lr
INNER JOIN tbl_teacher_leave_request_sessions lrs ON lr.tlr_id = lrs.tlrs_leave_request_id
WHERE lr.tlr_status IN (2, 3)
  AND lrs.tlrs_session_date >= :date_from
  AND lrs.tlrs_session_date <= :date_to
```

---

### 5.2 `GET /api/csi/students`

**Mô tả**: Lấy danh sách học viên với thông tin CSI, phân trang, sắp xếp. Mỗi HV được enrichment thêm chỉ số LCMS (homework/test).

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters) + các tham số bổ sung:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `page` | `int` | `1` | Trang hiện tại |
| `per_page` | `int` | `50` | Số bản ghi mỗi trang |
| `sort_by` | `string` | `"health_score"` | Cột sắp xếp (xem danh sách bên dưới) |
| `sort_dir` | `string` | `"asc"` | Hướng sắp xếp: `"asc"` hoặc `"desc"` |

**Cột sắp xếp hợp lệ** (server-side): `health_score`, `student_id`, `student_name`, `total_scheduled`, `total_success`, `student_noshow`, `student_half`, `success_rate`, `teacher_noshow`, `leave_sessions`, `css_staff`, `lesson_1_date`, `lesson_2_date`, `lesson_3_date`, `first_3_success_rate`, `avg_per_week`

**Cột sắp xếp client-side** (Alpine.js sort sau khi nhận response): `hw_completion_rate`, `hw_avg_score`, `test_avg_score`

**Response** (`application/json`):

```json
{
    "data": [
        {
            "student_id": 12345,
            "student_name": "Nguyễn Văn A",
            "email": "a@example.com",
            "phone": "0901234567",
            "css_staff": "css_user1",
            "total_scheduled": 30,
            "total_success": 25,
            "student_noshow": 3,
            "student_half": 2,
            "teacher_noshow": 1,
            "leave_sessions": 0,
            "health_score": 83.3,
            "success_rate": 0.833,
            "lesson_1_date": "2025-11-05 09:00:00",
            "lesson_1_code": 12,
            "lesson_2_date": "2025-11-07 09:00:00",
            "lesson_2_code": 12,
            "lesson_3_date": "2025-11-10 09:00:00",
            "lesson_3_code": 9,
            "first_3_success": 3,
            "first_3_total": 3,
            "first_3_success_rate": 100.0,
            "avg_per_week": 2.5,
            "total_success_12": 20,
            "ontrack_score": 66.7,
            "course_names": "SPEAKWELL",
            "health_category": "Vàng (Cảnh báo)",
            "teacher_warning": "Có ảnh hưởng (GV nghỉ 1b)",
            "hw_completion_rate": 85.5,
            "hw_avg_score": 7.2,
            "test_avg_score": 6.8
        }
    ],
    "total": 450,
    "page": 1,
    "per_page": 50,
    "total_pages": 9
}
```

**Chi tiết trường trong `data[]`:**

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `student_id` | `int` | ID học viên (`ordles_beneficiary_id` / `user_id`) |
| `student_name` | `string` | Họ tên HV (`user_last_name` + `user_first_name`) |
| `email` | `string` | Email HV |
| `phone` | `string\|null` | SĐT HV (từ `tbl_user_settings`) |
| `css_staff` | `string\|null` | Tên chuyên viên CSS phụ trách (từ `tbl_admin.admin_username`) |
| `total_scheduled` | `int` | Tổng buổi hoàn thành của HV |
| `total_success` | `int` | Buổi thành công (code 3,6,9,12) |
| `student_noshow` | `int` | Buổi HV noshow (code 0,4,7,10 hoặc NULL) |
| `student_half` | `int` | Buổi HV < 1/2 giờ (code 2,5,8,11) |
| `teacher_noshow` | `int` | Buổi GV noshow (code 0,2,3 hoặc NULL) |
| `leave_sessions` | `int` | Số buổi GV xin nghỉ phép (đã duyệt) ảnh hưởng HV này |
| `health_score` | `float` | Điểm sức khỏe = total_success / total_scheduled × 100 |
| `success_rate` | `float` | Tỉ lệ thành công (0–1) = total_success / total_scheduled |
| `lesson_1_date` | `string\|null` | Ngày buổi học đầu tiên (sau trial) |
| `lesson_1_code` | `int\|null` | Acceptance code buổi 1 |
| `lesson_2_date` | `string\|null` | Ngày buổi học thứ 2 |
| `lesson_2_code` | `int\|null` | Acceptance code buổi 2 |
| `lesson_3_date` | `string\|null` | Ngày buổi học thứ 3 |
| `lesson_3_code` | `int\|null` | Acceptance code buổi 3 |
| `first_3_success` | `int\|null` | Số buổi thành công trong 3 buổi đầu |
| `first_3_total` | `int\|null` | Tổng buổi trong 3 buổi đầu (tối đa 3) |
| `first_3_success_rate` | `float\|null` | Tỉ lệ TC 3 buổi đầu (%) |
| `avg_per_week` | `float` | Số buổi TC trung bình / tuần = total_success / tổng_tuần |
| `total_success_12` | `int` | Buổi thành công code 12 (GV đầy đủ) |
| `ontrack_score` | `float` | Ontrack = total_success_12 / total_scheduled × 100 |
| `course_names` | `string` | Tên chương trình: `"SPEAKWELL"`, `"EASYSPEAK"`, hoặc `"EASYSPEAK, SPEAKWELL"` |
| `health_category` | `string` | Phân loại: `"Xanh (Khỏe mạnh)"` / `"Vàng (Cảnh báo)"` / `"Đỏ (Báo động)"` |
| `teacher_warning` | `string` | Mức cảnh báo GV (xem [bộ lọc chung](#bộ-lọc-chung-common-filters)) |
| `hw_completion_rate` | `float\|null` | LCMS: Tỉ lệ hoàn thành BTVN (%) — enrichment từ LcmsService |
| `hw_avg_score` | `float\|null` | LCMS: Điểm TB BTVN (thang 10) — enrichment từ LcmsService |
| `test_avg_score` | `float\|null` | LCMS: Điểm TB bài kiểm tra (thang 10) — enrichment từ LcmsService |

**Ghi chú**: Các trường `hw_completion_rate`, `hw_avg_score`, `test_avg_score` được enrich bởi `LcmsService::getStudentLcmsStatsBatch()` sau khi truy vấn CsiService. Nếu LCMS không có dữ liệu cho HV, các trường này là `null`.

---

### 5.3 `GET /api/csi/health-distribution`

**Mô tả**: Lấy phân bố sức khỏe HV theo 3 nhóm (Xanh/Vàng/Đỏ) cho biểu đồ tròn (pie chart).

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters)

**Response** (`application/json`):

```json
[
    { "health_category": "Xanh (Khỏe mạnh)", "count": 280 },
    { "health_category": "Vàng (Cảnh báo)", "count": 120 },
    { "health_category": "Đỏ (Báo động)", "count": 50 }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `health_category` | `string` | Nhóm sức khỏe |
| `count` | `int` | Số HV trong nhóm |

**SQL:**

```sql
WITH joined AS (...), ..., csi_full AS (...)
SELECT health_category, COUNT(*) as count
FROM csi_full
WHERE <bộ lọc>
GROUP BY health_category
ORDER BY count DESC
```

---

### 5.4 `GET /api/csi/css-performance`

**Mô tả**: Lấy thống kê hiệu suất theo chuyên viên CSS cho biểu đồ cột xếp chồng (stacked bar chart).

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters)

**Response** (`application/json`):

```json
[
    {
        "css_staff": "css_user1",
        "total": 80,
        "green": 50,
        "yellow": 20,
        "red": 10,
        "avg_score": 82.5,
        "avg_success_rate": 81.3
    }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `css_staff` | `string` | Tên chuyên viên CSS |
| `total` | `int` | Tổng HV mà CSS phụ trách |
| `green` | `int` | Số HV Xanh |
| `yellow` | `int` | Số HV Vàng |
| `red` | `int` | Số HV Đỏ |
| `avg_score` | `float` | Điểm SK trung bình của HV do CSS phụ trách |
| `avg_success_rate` | `float` | Tỉ lệ TC trung bình (%) |

**Ghi chú**: Chỉ bao gồm HV có CSS staff (loại bỏ `css_staff IS NULL`).

**SQL:**

```sql
WITH joined AS (...), ..., csi_full AS (...)
SELECT
    css_staff, COUNT(*) as total,
    SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) as green,
    SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) as yellow,
    SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) as red,
    ROUND(AVG(health_score), 1) as avg_score,
    ROUND(AVG(success_rate) * 100, 1) as avg_success_rate
FROM csi_full
WHERE <bộ lọc> AND css_staff IS NOT NULL AND css_staff != ''
GROUP BY css_staff ORDER BY css_staff
```

---

### 5.5 `GET /api/csi/score-distribution`

**Mô tả**: Lấy phân bố điểm sức khỏe theo khoảng % cho biểu đồ cột (histogram).

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters)

**Response** (`application/json`):

```json
[
    { "label": "0-20", "count": 15 },
    { "label": "21-40", "count": 25 },
    { "label": "41-60", "count": 40 },
    { "label": "61-80", "count": 120 },
    { "label": "81-100", "count": 250 }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `label` | `string` | Khoảng điểm (%) |
| `count` | `int` | Số HV trong khoảng |

**Ghi chú**: Luôn trả đủ 5 khoảng (0-20, 21-40, 41-60, 61-80, 81-100). Nếu có điểm < 0 sẽ có thêm bucket `"< 0"` ở đầu.

**SQL:**

```sql
WITH joined AS (...), ..., csi_full AS (...)
SELECT
    CASE
        WHEN health_score >= 0 AND health_score <= 20 THEN '0-20'
        WHEN health_score > 20 AND health_score <= 40 THEN '21-40'
        WHEN health_score > 40 AND health_score <= 60 THEN '41-60'
        WHEN health_score > 60 AND health_score <= 80 THEN '61-80'
        WHEN health_score > 80 AND health_score <= 100 THEN '81-100'
        ELSE '< 0'
    END as label,
    COUNT(*) as count
FROM csi_full
WHERE <bộ lọc>
GROUP BY label ORDER BY sort_order
```

---

### 5.6 `GET /api/csi/teacher-warning`

**Mô tả**: Lấy phân bố cảnh báo GV theo mức độ cho biểu đồ tròn (pie chart).

**Query Parameters**: [Bộ lọc chung](#bộ-lọc-chung-common-filters)

**Response** (`application/json`):

```json
[
    { "teacher_warning": "Bình thường", "count": 350 },
    { "teacher_warning": "Có ảnh hưởng (GV nghỉ 1b)", "count": 60 },
    { "teacher_warning": "Nghiêm trọng (GV nghỉ >=2b)", "count": 30 },
    { "teacher_warning": "Khẩn cấp (GV nghỉ >= 4 buổi)", "count": 10 }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `teacher_warning` | `string` | Mức cảnh báo GV |
| `count` | `int` | Số HV ở mức cảnh báo đó |

**Quy tắc phân loại:**
- `teacher_noshow >= 4` → `"Khẩn cấp (GV nghỉ >= 4 buổi)"`
- `teacher_noshow >= 2` → `"Nghiêm trọng (GV nghỉ >=2b)"`
- `teacher_noshow = 1` → `"Có ảnh hưởng (GV nghỉ 1b)"`
- `teacher_noshow = 0` → `"Bình thường"`

---

### 5.7 `GET /api/csi/ews`

**Mô tả**: Lấy danh sách HV có chuỗi buổi liên tiếp không thành công (Early Warning System — EWS). Đếm ngược từ buổi gần nhất: nếu buổi gần nhất là noshow/half, tiếp tục đếm cho đến khi gặp buổi thành công.

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `search` | `string` | `""` | Tìm kiếm HV (ID, tên, SĐT, email) |
| `css_staff` | `string` | `""` | Lọc theo CSS staff |
| `date_from` | `string` | `""` | Từ ngày (Y-m-d) |
| `date_to` | `string` | `""` | Đến ngày (Y-m-d) |
| `min_missed` | `int` | `0` | Chỉ hiện HV có total_missed ≥ giá trị này |
| `page` | `int` | `1` | Trang hiện tại |
| `per_page` | `int` | `50` | Số bản ghi mỗi trang |
| `sort_by` | `string` | `"total_missed"` | Cột sắp xếp: `total_missed`, `student_id`, `student_name`, `css_staff`, `last_success_time` |
| `sort_dir` | `string` | `"desc"` | Hướng sắp xếp: `"asc"` hoặc `"desc"` |

**Response** (`application/json`):

```json
{
    "data": [
        {
            "student_id": 12345,
            "student_name": "Nguyễn Văn A",
            "phone": "0901234567",
            "email": "a@example.com",
            "total_missed": 5,
            "css_staff": "css_user1",
            "last_success_time": "2025-12-01 09:00:00"
        }
    ],
    "total": 120,
    "page": 1,
    "per_page": 50,
    "total_pages": 3
}
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `student_id` | `int` | ID học viên |
| `student_name` | `string` | Họ tên HV |
| `phone` | `string\|null` | SĐT |
| `email` | `string` | Email |
| `total_missed` | `int` | Số buổi liên tiếp không thành công (tính từ buổi gần nhất) |
| `css_staff` | `string\|null` | Chuyên viên CSS |
| `last_success_time` | `string\|null` | Thời gian buổi thành công gần nhất |

**Thuật toán tính consecutive streak:**

```sql
-- 1. Xếp hạng buổi học theo thời gian DESC
student_lessons_ranked AS (
    SELECT user_id,
        CASE WHEN ole_acceptance_code IN (3,6,9,12) THEN 0 ELSE 1 END AS is_missed,
        ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY lesson_starttime DESC) as rn
    FROM joined
)
-- 2. Tìm buổi thành công đầu tiên (từ gần nhất)
first_non_missed AS (
    SELECT user_id, MIN(rn) as first_ok_rn
    FROM student_lessons_ranked WHERE is_missed = 0
    GROUP BY user_id
)
-- 3. Streak = first_ok_rn - 1 (hoặc total_lessons nếu chưa bao giờ thành công)
ews_calc AS (
    SELECT user_id,
        COALESCE(first_ok_rn, total_lessons + 1) - 1 as total_missed
    WHERE total_missed > 0
)
```

---

### 5.8 `GET /api/csi/ews/{studentId}/detail`

**Mô tả**: Chi tiết EWS của một học viên: thông tin tổng hợp + toàn bộ lịch sử buổi học + consecutive streak + buổi GV nghỉ phép.

**Path Parameters**:

| Tham số | Kiểu | Mô tả |
|---------|------|-------|
| `studentId` | `int` | ID học viên (user_id / ordles_beneficiary_id) |

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `date_from` | `string` | `""` | Từ ngày (Y-m-d) |
| `date_to` | `string` | `""` | Đến ngày (Y-m-d) |

**Response** (`application/json`):

```json
{
    "student": {
        "student_id": 12345,
        "student_name": "Nguyễn Văn A",
        "email": "a@example.com",
        "phone": "0901234567",
        "css_staff": "css_user1",
        "total_lessons": 30,
        "total_success": 25,
        "total_noshow": 3,
        "total_half": 2,
        "last_success_time": "2025-12-01 09:00:00"
    },
    "lessons": [
        {
            "lesson_id": 99001,
            "lesson_time": "2025-12-10 09:00:00",
            "acceptance_code": 0,
            "status": "noshow",
            "status_label": "HV Noshow"
        },
        {
            "lesson_id": 99000,
            "lesson_time": "2025-12-08 09:00:00",
            "acceptance_code": 12,
            "status": "success",
            "status_label": "Thành công"
        }
    ],
    "consecutive_streak": 1,
    "leave_sessions": [
        {
            "lesson_id": 99005,
            "session_date": "2025-12-05",
            "tlrs_need_replacement": 1,
            "tlrs_replacement_type": null,
            "teacher_name": "Trần GV B"
        }
    ]
}
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `student` | `object\|null` | Thông tin tổng hợp HV (null nếu không tìm thấy) |
| `student.total_lessons` | `int` | Tổng buổi |
| `student.total_success` | `int` | Buổi TC |
| `student.total_noshow` | `int` | Buổi HV noshow |
| `student.total_half` | `int` | Buổi HV < 1/2 giờ |
| `student.last_success_time` | `string\|null` | Lần TC gần nhất |
| `lessons` | `array` | Danh sách buổi học (sắp xếp DESC theo thời gian) |
| `lessons[].status` | `string` | `"success"`, `"noshow"`, `"half"`, `"unknown"` |
| `lessons[].status_label` | `string` | Nhãn tiếng Việt: `"Thành công"`, `"HV Noshow"`, `"Chưa có dữ liệu"`, `"HV < 1/2 giờ"` |
| `consecutive_streak` | `int` | Chuỗi buổi liên tiếp không TC (tính từ gần nhất) |
| `leave_sessions` | `array` | Buổi GV nghỉ phép ảnh hưởng HV (từ `tbl_teacher_leave_request_sessions`) |

---

### 5.9 `GET /api/csi/students/{studentId}/detail`

**Mô tả**: Chi tiết đầy đủ của một học viên: CSI summary, lịch sử buổi học, thông tin đơn hàng, gói subscription, buổi GV nghỉ phép, và chỉ số LCMS.

**Path Parameters**:

| Tham số | Kiểu | Mô tả |
|---------|------|-------|
| `studentId` | `int` | ID học viên |

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `date_from` | `string` | `""` | Từ ngày (Y-m-d) |
| `date_to` | `string` | `""` | Đến ngày (Y-m-d) |

**Response** (`application/json`):

```json
{
    "student": {
        "student_id": 12345,
        "student_name": "Nguyễn Văn A",
        "email": "a@example.com",
        "phone": "0901234567",
        "css_staff": "css_user1",
        "total_scheduled": 30,
        "total_success": 25,
        "student_noshow": 3,
        "student_half": 2,
        "teacher_noshow": 1,
        "leave_sessions": 0,
        "health_score": 83.3,
        "success_rate": 0.833,
        "lesson_1_date": "2025-11-05 09:00:00",
        "lesson_1_code": 12,
        "lesson_2_date": "2025-11-07 09:00:00",
        "lesson_2_code": 12,
        "lesson_3_date": "2025-11-10 09:00:00",
        "lesson_3_code": 9,
        "first_3_success": 3,
        "first_3_total": 3,
        "first_3_success_rate": 100.0,
        "avg_per_week": 2.5,
        "total_success_12": 20,
        "ontrack_score": 66.7,
        "course_names": "SPEAKWELL",
        "health_category": "Vàng (Cảnh báo)",
        "teacher_warning": "Có ảnh hưởng (GV nghỉ 1b)"
    },
    "lessons": [
        {
            "lesson_id": 99001,
            "lesson_time": "2025-12-10 09:00:00",
            "acceptance_code": 12,
            "status": "success",
            "status_label": "Thành công",
            "is_teacher_noshow": 0
        }
    ],
    "consecutive_streak": 0,
    "orders": [
        {
            "order_id": 5001,
            "order_type": 1,
            "order_total_amount": 5000000,
            "order_net_amount": 4500000,
            "order_discount_value": 500000,
            "order_payment_status": 1,
            "order_status": 1,
            "order_addedon": "2025-10-01 10:00:00",
            "order_item_count": 1,
            "order_currency_code": "VND",
            "payment_method": "MOMO"
        }
    ],
    "packages": [
        {
            "ordsplan_id": 3001,
            "ordsplan_order_id": 5001,
            "ordsplan_plan_id": 10,
            "ordsplan_amount": 5000000,
            "ordsplan_lesson_amount": 50,
            "ordsplan_lessons": 50,
            "ordsplan_used_lesson_count": 30,
            "ordsplan_validity": 365,
            "ordsplan_duration": 12,
            "ordsplan_start_date": "2025-10-01",
            "ordsplan_end_date": "2026-10-01",
            "ordsplan_status": 1,
            "ordsplan_created": "2025-10-01 10:00:00",
            "ordsplan_refund": 0,
            "plan_title": "SpeakWell 50 buổi",
            "plan_lesson_count": 50,
            "plan_price": 5000000
        }
    ],
    "leave_sessions": [
        {
            "lesson_id": 99005,
            "session_date": "2025-12-05",
            "tlrs_need_replacement": 1,
            "tlrs_replacement_type": null,
            "teacher_name": "Trần GV B"
        }
    ],
    "lcms": {
        "hw_completion_rate": 85.5,
        "hw_avg_score": 7.2,
        "test_avg_score": 6.8
    }
}
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `student` | `object\|null` | Thông tin CSI đầy đủ của HV (từ csi_full CTE). null nếu không tìm thấy |
| `lessons` | `array` | Toàn bộ buổi học (DESC theo thời gian). Mỗi lesson có thêm `is_teacher_noshow` (1/0) |
| `consecutive_streak` | `int` | Chuỗi buổi liên tiếp không TC từ buổi gần nhất |
| `orders` | `array` | Danh sách đơn hàng liên kết qua `tbl_order_lessons.ordles_order_id` |
| `packages` | `array` | Gói subscription (từ `tbl_order_subscription_plans` + `tbl_subscription_plans`) |
| `leave_sessions` | `array` | Buổi GV nghỉ phép ảnh hưởng HV (từ `tbl_teacher_leave_request_sessions`, status 2/3 = đã duyệt) |
| `lcms` | `object` | LCMS metrics: `hw_completion_rate` (% BTVN), `hw_avg_score` (thang 10), `test_avg_score` (thang 10) |

**SQL lấy orders:**

```sql
SELECT o.order_id, o.order_type, o.order_total_amount, o.order_net_amount,
       o.order_discount_value, o.order_payment_status, o.order_status,
       o.order_addedon, o.order_item_count, o.order_currency_code,
       pm.pmethod_code AS payment_method
FROM tbl_orders o
LEFT JOIN tbl_payment_methods pm ON o.order_pmethod_id = pm.pmethod_id
WHERE o.order_id IN (
    SELECT DISTINCT ol.ordles_order_id
    FROM tbl_order_lessons ol WHERE ol.ordles_beneficiary_id = :studentId
)
ORDER BY o.order_addedon DESC
```

**SQL lấy packages:**

```sql
SELECT sp.*, spl.subplan_title AS plan_title,
       spl.subplan_lesson_count AS plan_lesson_count,
       spl.subplan_price AS plan_price
FROM tbl_order_subscription_plans sp
LEFT JOIN tbl_subscription_plans spl ON sp.ordsplan_plan_id = spl.subplan_id
WHERE sp.ordsplan_beneficiary_id = :studentId
   OR sp.ordsplan_order_id IN (
       SELECT DISTINCT ol.ordles_order_id
       FROM tbl_order_lessons ol WHERE ol.ordles_beneficiary_id = :studentId
   )
ORDER BY sp.ordsplan_created DESC
```

**SQL lấy leave sessions:**

```sql
SELECT lrs.tlrs_session_id AS lesson_id, lrs.tlrs_session_date AS session_date,
       lrs.tlrs_need_replacement, lrs.tlrs_replacement_type,
       CONCAT(COALESCE(tu.user_last_name, ''), ' ', COALESCE(tu.user_first_name, '')) AS teacher_name
FROM tbl_teacher_leave_request_sessions lrs
INNER JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
LEFT JOIN tbl_users tu ON lr.tlr_teacher_id = tu.user_id
WHERE lr.tlr_status IN (2, 3)
  AND lrs.tlrs_session_type = 1
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) = :studentId
  AND lrs.tlrs_session_date BETWEEN :date_from AND :date_to
ORDER BY lrs.tlrs_session_date ASC
```

---

### 5.10 `GET /api/csi/trends`

**Mô tả**: Lấy dữ liệu xu hướng (trend) theo tuần hoặc tháng cho biểu đồ so sánh.

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `group_by` | `string` | `"week"` | Nhóm theo: `"week"` hoặc `"month"` |
| `date_from` | `string` | `""` | Từ ngày (Y-m-d) |
| `date_to` | `string` | `""` | Đến ngày (Y-m-d) |
| `css_staff` | `string` | `""` | Lọc theo CSS staff (tùy chọn) |

**Response** (`application/json`):

```json
[
    {
        "period": "W01 2026",
        "total_scheduled": 200,
        "total_success": 170,
        "total_noshow": 20,
        "total_half": 10,
        "success_rate": 85.0,
        "unique_students": 80
    },
    {
        "period": "W02 2026",
        "total_scheduled": 210,
        "total_success": 180,
        "total_noshow": 18,
        "total_half": 12,
        "success_rate": 85.7,
        "unique_students": 82
    }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `period` | `string` | Nhãn kỳ: `"W01 2026"` (tuần) hoặc `"01/2026"` (tháng) |
| `total_scheduled` | `int` | Tổng buổi hoàn thành trong kỳ |
| `total_success` | `int` | Buổi TC trong kỳ |
| `total_noshow` | `int` | Buổi HV noshow trong kỳ |
| `total_half` | `int` | Buổi HV < 1/2 giờ trong kỳ |
| `success_rate` | `float` | Tỉ lệ TC (%) = total_success / total_scheduled × 100 |
| `unique_students` | `int` | Số HV (distinct) có buổi học trong kỳ |

**SQL:**

```sql
WITH joined AS (...)
SELECT
    CONCAT('W', LPAD(WEEK(j.ordles_lesson_starttime, 3), 2, '0'), ' ', YEAR(...)) as period_label,
    COUNT(*) as total_scheduled,
    SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) as total_success,
    SUM(CASE WHEN j.ole_acceptance_code IN (0,4,7,10) OR IS NULL THEN 1 ELSE 0 END) as total_noshow,
    SUM(CASE WHEN j.ole_acceptance_code IN (2,5,8,11) THEN 1 ELSE 0 END) as total_half,
    ROUND(SUM(success) * 100.0 / COUNT(*), 1) as success_rate,
    COUNT(DISTINCT j.ordles_beneficiary_id) as unique_students
FROM joined j
[LEFT JOIN tbl_admin IF css_staff filter]
GROUP BY period_label, period_order
ORDER BY period_order ASC
```

---

### 5.11 `GET /api/csi/health-trends`

**Mô tả**: Lấy xu hướng phân bố sức khỏe theo tuần/tháng. Mỗi kỳ tính health_score riêng cho từng HV (chỉ dùng buổi trong kỳ đó), rồi đếm xanh/vàng/đỏ.

**Query Parameters**: Giống [5.10 trends](#510-get-apicsitrends)

**Response** (`application/json`):

```json
[
    {
        "period": "W01 2026",
        "total_students": 80,
        "green": 50,
        "yellow": 20,
        "red": 10
    }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `period` | `string` | Nhãn kỳ |
| `total_students` | `int` | Tổng HV trong kỳ |
| `green` | `int` | HV Xanh (health_score ≥ 85%) trong kỳ |
| `yellow` | `int` | HV Vàng (60% ≤ health_score < 85%) trong kỳ |
| `red` | `int` | HV Đỏ (health_score < 60%) trong kỳ |

**SQL:**

```sql
WITH joined AS (...),
period_student_scores AS (
    SELECT
        period_label, period_order, j.ordles_beneficiary_id as student_id,
        ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END)
              * 100.0 / COUNT(*), 1) AS health_score
    FROM joined j
    GROUP BY period_label, period_order, j.ordles_beneficiary_id
)
SELECT period_label, period_order,
    COUNT(*) as total_students,
    SUM(CASE WHEN health_score >= 85 THEN 1 ELSE 0 END) as green_count,
    SUM(CASE WHEN health_score >= 60 AND health_score < 85 THEN 1 ELSE 0 END) as yellow_count,
    SUM(CASE WHEN health_score < 60 THEN 1 ELSE 0 END) as red_count
FROM period_student_scores
GROUP BY period_label, period_order ORDER BY period_order ASC
```

---

### 5.12 `GET /api/csi/ontrack-trends`

**Mô tả**: Lấy xu hướng tỉ lệ Ontrack theo tuần/tháng. Mỗi kỳ tính: Ontrack rate = HV ontrack (≥ 90% success) / Tổng HV active × 100.

**Query Parameters**: Giống [5.10 trends](#510-get-apicsitrends)

**Response** (`application/json`):

```json
[
    {
        "period": "W01 2026",
        "total_students": 80,
        "total_active": 65,
        "total_scheduled": 200,
        "ontrack_count": 30,
        "ontrack_by_success": 45,
        "ontrack_rate": 56.3
    }
]
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `period` | `string` | Nhãn kỳ |
| `total_students` | `int` | Tổng HV distinct trong kỳ |
| `total_active` | `int` | HV có ít nhất 1 buổi thành công code 12 (GV đầy đủ) trong kỳ |
| `total_scheduled` | `int` | Tổng buổi hoàn thành trong kỳ |
| `ontrack_count` | `int` | HV có ontrack_score ≥ 90% (chỉ đếm code 12) trong kỳ |
| `ontrack_by_success` | `int` | HV có success_rate ≥ 90% (tất cả code TC: 3,6,9,12) trong kỳ |
| `ontrack_rate` | `float` | = ontrack_by_success / total_students × 100 |

**SQL:**

```sql
WITH joined AS (...),
period_student_scores AS (
    SELECT
        period_label, period_order, j.ordles_beneficiary_id as student_id,
        COUNT(*) as total_scheduled,
        SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) as total_success_12,
        ROUND(SUM(CASE WHEN j.ole_acceptance_code = 12 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as ontrack_score,
        SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) as total_success,
        ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as success_rate
    FROM joined j
    GROUP BY period_label, period_order, j.ordles_beneficiary_id
)
SELECT period_label, period_order,
    COUNT(*) as total_students,
    SUM(CASE WHEN total_success_12 > 0 THEN 1 ELSE 0 END) as total_active,
    SUM(total_scheduled) as total_scheduled,
    SUM(CASE WHEN ontrack_score >= 90 THEN 1 ELSE 0 END) as ontrack_count,
    SUM(CASE WHEN success_rate >= 90 THEN 1 ELSE 0 END) as ontrack_by_success,
    ROUND(SUM(CASE WHEN success_rate >= 90 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as ontrack_rate
FROM period_student_scores
GROUP BY period_label, period_order ORDER BY period_order ASC
```

---

### 5.13 `GET /api/csi/search`

**Mô tả**: Tìm kiếm nhanh học viên theo ID, email, tên hoặc SĐT. Trả về tối đa 100 kết quả.

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `q` | `string` | `""` | Từ khóa tìm kiếm (tối thiểu 1 ký tự) |

**Response** (`application/json`):

```json
[
    {
        "student_id": 12345,
        "student_name": "Nguyễn Văn A",
        "email": "a@example.com",
        "phone": "0901234567",
        "css_staff": "css_user1",
        "total_scheduled": 30,
        "total_success": 25,
        "health_score": 83.3,
        "health_category": "Vàng (Cảnh báo)",
        "teacher_warning": "Bình thường"
    }
]
```

Trả về mảng rỗng `[]` nếu `q` rỗng hoặc ít hơn 1 ký tự.

**Ghi chú**: Kết quả sắp xếp theo `health_score ASC` (HV yếu nhất trước).

**SQL:**

```sql
WITH joined AS (...), ..., csi_full AS (...)
SELECT * FROM csi_full
WHERE CAST(student_id AS CHAR) LIKE :keyword
   OR email LIKE :keyword
   OR student_name LIKE :keyword
   OR phone LIKE :keyword
ORDER BY health_score ASC
LIMIT 100
```

---

### 5.14 `GET /api/csi/spw-inactive`

**Mô tả**: Lấy danh sách HV Inactive SpeakWell (không hoạt động trong 30 ngày) cùng số buổi còn lại (unscheduled/scheduled).

**Query Parameters**:

| Tham số | Kiểu | Mặc định | Mô tả |
|---------|------|----------|-------|
| `page` | `int` | `1` | Trang hiện tại |
| `per_page` | `int` | `50` | Số bản ghi mỗi trang |
| `search` | `string` | `""` | Tìm theo ID, tên, email, username |
| `sort_by` | `string` | `"remaining_total"` | Cột sắp xếp (xem bên dưới) |
| `sort_dir` | `string` | `"asc"` | Hướng sắp xếp: `"asc"` hoặc `"desc"` |

**Cột sắp xếp hợp lệ**: `student_id`, `user_username`, `student_name`, `user_email`, `unscheduled_count`, `scheduled_count`, `remaining_total`

**Response** (`application/json`):

```json
{
    "data": [
        {
            "student_id": 12345,
            "user_username": "user123",
            "student_name": "Nguyễn Văn A",
            "user_email": "a@example.com",
            "unscheduled_count": 10,
            "scheduled_count": 5,
            "remaining_total": 15
        }
    ],
    "total": 350,
    "page": 1,
    "per_page": 50,
    "total_pages": 7,
    "zero_lessons_count": 45
}
```

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `data[]` | `array` | Danh sách HV inactive |
| `data[].student_id` | `int` | ID học viên |
| `data[].user_username` | `string\|null` | Username |
| `data[].student_name` | `string` | Họ tên |
| `data[].user_email` | `string` | Email |
| `data[].unscheduled_count` | `int` | Số buổi SPW chưa lên lịch (`ordles_status = 1`) |
| `data[].scheduled_count` | `int` | Số buổi SPW đã lên lịch (`ordles_status = 2`) |
| `data[].remaining_total` | `int` | Tổng còn lại = unscheduled_count + scheduled_count |
| `total` | `int` | Tổng số HV inactive |
| `page` | `int` | Trang hiện tại |
| `per_page` | `int` | Số bản ghi mỗi trang |
| `total_pages` | `int` | Tổng số trang |
| `zero_lessons_count` | `int` | Số HV có 0 buổi còn lại (remaining_total = 0) |

**Định nghĩa Inactive**:
- HV thuộc SpeakWell (ordles_tlang_id hoặc grpcls_tlang_id trong danh sách SPW_TLANG_IDS)
- KHÔNG nằm trong tập Active (user_lastseen ≥ 30 ngày gần nhất AND có buổi hoàn thành hoặc lớp nhóm đang mở trong 30 ngày)

**SPW_TLANG_IDS** (SpeakWell + EasySpeak): `389,390,392,403,404,405,406,407,411,412,413,414,415,416,471,558,560,562,564,567,568,569,571,572,574,575,576,577,580,581,582,583,584,585,586`

**SQL:**

```sql
WITH q1 AS (
    -- Tất cả HV SpeakWell (1-1 UNION lớp nhóm)
    SELECT ol.ordles_beneficiary_id AS student_id FROM tbl_order_lessons ol
    WHERE ol.ordles_tlang_id IN (389,...,586)
    UNION
    SELECT oc.ordcls_beneficiary_id FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ON gce.grpcls_id = oc.ordcls_grpcls_id
    WHERE gce.grpcls_tlang_id IN (389,...,586)
),
q2 AS (
    -- HV Active (last 30 days)
    SELECT u.user_id AS student_id FROM tbl_users u
    JOIN tbl_orders o ON o.order_user_id = u.user_id
    JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
    WHERE u.user_lastseen >= NOW() - INTERVAL 30 DAY
      AND ol.ordles_status = 3 AND ol.ordles_tlang_id IN (...) AND ol.ordles_lesson_endtime BETWEEN ...
    UNION
    SELECT oc.ordcls_beneficiary_id FROM tbl_order_classes oc
    JOIN tbl_group_classes gce ... JOIN tbl_users u ...
    WHERE gce.grpcls_status = 2 AND u.user_lastseen >= NOW() - INTERVAL 30 DAY
),
inactive_students AS (
    SELECT q1.student_id FROM q1
    WHERE NOT EXISTS (SELECT 1 FROM q2 WHERE q2.student_id = q1.student_id)
),
remaining_lessons AS (
    SELECT ol.ordles_beneficiary_id AS student_id,
        SUM(CASE WHEN ol.ordles_status = 1 THEN 1 ELSE 0 END) AS unscheduled_count,
        SUM(CASE WHEN ol.ordles_status = 2 THEN 1 ELSE 0 END) AS scheduled_count
    FROM tbl_order_lessons ol
    WHERE ol.ordles_tlang_id IN (...) AND ol.ordles_status IN (1, 2)
    GROUP BY ol.ordles_beneficiary_id
),
inactive_full AS (
    SELECT ist.student_id, u.user_username,
        CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
        u.user_email,
        COALESCE(rl.unscheduled_count, 0) AS unscheduled_count,
        COALESCE(rl.scheduled_count, 0) AS scheduled_count
    FROM inactive_students ist
    JOIN tbl_users u ON u.user_id = ist.student_id
    LEFT JOIN remaining_lessons rl ON rl.student_id = ist.student_id
)
SELECT *, (unscheduled_count + scheduled_count) AS remaining_total
FROM inactive_full
[WHERE search_condition]
ORDER BY :sort_by :sort_dir
LIMIT :per_page OFFSET :offset
```

---

## Phụ lục: Health Category Thresholds

| Nhóm | Điều kiện | Màu |
|------|-----------|-----|
| Xanh (Khỏe mạnh) | `health_score >= 85` | 🟢 |
| Vàng (Cảnh báo) | `60 <= health_score < 85` | 🟡 |
| Đỏ (Báo động) | `health_score < 60` | 🔴 |

## Phụ lục: Teacher Warning Thresholds

| Mức cảnh báo | Điều kiện |
|-------------|-----------|
| Bình thường | `teacher_noshow = 0` |
| Có ảnh hưởng (GV nghỉ 1b) | `teacher_noshow = 1` |
| Nghiêm trọng (GV nghỉ >=2b) | `2 <= teacher_noshow < 4` |
| Khẩn cấp (GV nghỉ >= 4 buổi) | `teacher_noshow >= 4` |

## Phụ lục: Ontrack Definition

- **Ontrack score** (per student): `total_success_12 / total_scheduled × 100` (chỉ code 12 = GV đầy đủ)
- **Success rate** (per student): `total_success / total_scheduled × 100` (code 3,6,9,12)
- **HV Ontrack** (trong một kỳ): HV có `success_rate >= 90%`
- **Ontrack rate** (kỳ): `HV ontrack / Tổng HV × 100`
- **KPI Ontrack** (tổng): Trung bình cộng ontrack_rate của tất cả các kỳ (tuần)

## Phụ lục: Date Defaults

- **date_from** mặc định: `2025-11-04` (ngày bắt đầu thu thập dữ liệu CSI)
- **date_to** mặc định: `NOW()` (thời điểm hiện tại)
- **First-3 lessons**: Luôn tính từ `2025-11-04` bất kể bộ lọc date_from (trừ khi date_to bị giới hạn)
- **Trial lesson**: Loại trừ khỏi first-3 bằng `CONF_TRIAL_SUBJECT_ID` từ `tbl_configurations`
