# Phase 214 ✅

## Yêu cầu
- Chỉnh sửa lại, cho phép các roles có quyền giống như admin_username là `huyentdt` hoặc `giangctt` trong bảng dữ liệu tbl_admin, `tbl_admin_permissions`, `tbl_admin_roles` được phép truy cập trang Quản trị GV.

## Phân tích dữ liệu

### Roles của `giangctt` (admin_id=4):
| Role ID | Tên role (VN) | Tên role (EN) |
|---------|---------------|---------------|
| 3 | CS | Student Affairs |
| 6 | Admin Vận hành | Operator Admin |
| 7 | Gia hạn Khách hàng | Customer Retention |
| 14 | Quản trị CSS | CSS Admin |

### Roles của `huyentdt` (admin_id=55):
| Role ID | Tên role (VN) | Tên role (EN) |
|---------|---------------|---------------|
| 4 | Xem thông tin giáo viên | View Teacher Info |
| 29 | Quản lý Giáo viên | Manage Teacher |

## Thay đổi
- Mở rộng `TEACHER_MGMT_ROLE_IDS` từ `[9, 11]` thành `[3, 4, 6, 7, 9, 11, 14, 29]`
- Bất kỳ user nào có ít nhất 1 trong các role_id trên (hoặc là privileged user) đều có thể truy cập trang Quản trị GV
- Cập nhật thông báo lỗi middleware cho ngắn gọn hơn

### Files thay đổi:
- `src/app/Http/Controllers/Auth/AuthController.php` — mở rộng `TEACHER_MGMT_ROLE_IDS`
- `src/app/Http/Middleware/CanViewTeacherManagement.php` — cập nhật comment và error message
- `src/routes/web.php` — cập nhật comment

