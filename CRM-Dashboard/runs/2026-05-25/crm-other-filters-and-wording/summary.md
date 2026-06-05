# CRM other filters audit + wording optimization

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## 1) Additional filter audit completed
Checked coherence across:
- `/api/dashboard`
- `/api/students?download=all`
- `/api/learner-journey/students`

### Filter families checked
- `css`
- `healthMovementGroup`
- `targetCategory`
- `baseCategory`
- `lifecycleStatus`
- `product` (sample set including `Onboarding` + first 9 product values)

### Combination checks also run
- `css + healthMovementGroup`
- `css + targetCategory`
- `css + lifecycleStatus`
- `healthMovementGroup + renewalStatus`
- `targetCategory + renewalStatus`
- `baseCategory + healthMovementGroup`

### Result
- Total checks run: `33`
- Failed single-filter checks: `0`
- Failed combo checks: `0`

So the remaining business filters audited here are currently coherent between dashboard, students, and learner-profile sources.

## 2) Wording optimization shipped to UI
Updated filter panel copy to be more business-friendly:
- `Bộ lọc nâng cao` panel title -> `Phạm vi & điều kiện lọc`
- caption -> `Dùng chung cho bàn điều hành, danh sách học viên và hồ sơ học viên.`
- `Nhóm Health Movement` -> `Xu hướng sức khỏe`
- `CSS / người chăm sóc` -> `CSS phụ trách`
- `Nhóm chuyển dịch chi tiết` -> `Chuyển dịch chi tiết`
- `Phân loại Target` -> `Sức khỏe kỳ này`
- `Phân loại Base` -> `Sức khỏe kỳ trước`
- `Sản phẩm gia hạn` -> `Gói / sản phẩm`
- `Vòng đời` -> `Trạng thái hành trình`
- chip `0 bộ lọc nâng cao` -> `0 điều kiện lọc`

Also improved active filter summary to read more naturally, for example:
- `Xu hướng: Trượt dốc`
- `Kỳ này: 1. Báo động (<60)`
- `Gia hạn: ✅ Đã gia hạn`
- `Hành trình: 4. Hết gói (Gap chờ phí)`

## Production verification
Verified root HTML contains:
- `Phạm vi & điều kiện lọc`
- `Xu hướng sức khỏe`
- `CSS phụ trách`
- `Sức khỏe kỳ này`
- `Sức khỏe kỳ trước`
- `Gói / sản phẩm`
- `Trạng thái hành trình`
- `0 điều kiện lọc`

Quick live filter calls also still responded correctly after wording deploy.
