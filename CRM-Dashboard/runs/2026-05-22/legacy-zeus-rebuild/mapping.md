# Mapping legacy Data_Model ↔ Zeus (best-effort rebuild)

## Quy ước hiện tại
- Target period được detect tự động bằng tháng CSI có population gần nhất với legacy count.
- Base period được rebuild theo **tháng baseline sớm nhất trước target mà học viên có CSI data**; mốc tháng liền trước chỉ dùng làm tham chiếu tổng quan.
- Rebuild hiện tại là **best-effort** từ Zeus DB, dùng logic gần nhất với CSI monthly snapshot + learner journey data.

| Cột legacy | Nguồn Zeus | Công thức rebuild hiện tại | Trạng thái |
|---|---|---|---|
| Student_ID / Tên / Email / SĐT / CSS | CSI + learner journey | lấy trực tiếp theo student_id | direct |
| Score_Target | CSI target month | dùng trực tiếp `health_score` của CSI target month | derived |
| Score_Base | CSI baseline month per student | dùng `health_score` của tháng sớm nhất trước target mà học viên có data | derived |
| MoM/QoQ_Variance | target/base | target - base, nếu base thiếu thì 0 | derived |
| Phân loại Target | Score_Target | >=85 / >=60 / <60 | derived |
| Phân loại Base | Score_Base | >=85 / >=60 / <60, nếu không có baseline month => Mới | derived |
| Nhóm | base category + target category | matrix 1..9 suy ra từ transition | derived |
| Tỉ lệ gián đoạn do GV | CSI target month | teacher_noshow / total_scheduled | derived |
| Tỉ lệ học dở | CSI target month | (total_scheduled - total_success - noshow - half)/total_scheduled | derived |
| Tốc độ kích hoạt | heuristic | base=Mới => Thiếu ngày, ngược lại rỗng | heuristic |
| Trạng thái gia hạn | heuristic | base=Mới => Bán mới, ngược lại ⏳ Chưa gia hạn | heuristic |
| Doanh thu gia hạn | heuristic | 0 | heuristic |
| Sản phẩm gia hạn chi tiết | heuristic | base=Mới => Onboarding, ngược lại rỗng | heuristic |
| Số buổi còn lại | learner journey | remaining_sessions hiện tại từ Zeus | direct but not period-locked |
| Trạng thái vòng đời | heuristic | base=Mới => 1. Mới (Onboarding), ngược lại 4. Hết gói (Gap chờ phí) | heuristic |
| Điểm sức khỏe quản trị | same as target | = Score_Target | heuristic/derived |
| Nhịp độ học tập | CSI target month | avg_per_week | derived |
| Gián đoạn do GV (tích lũy) | heuristic | same as teacher_disruption_rate | heuristic |

## Điểm chưa chắc chắn
- Legacy có thể dùng rule base period tinh vi hơn; hiện script đang dùng baseline month sớm nhất trước target theo từng học viên.
- `Tốc độ kích hoạt`, `Trạng thái gia hạn`, `Trạng thái vòng đời`, `Sản phẩm gia hạn chi tiết` trong legacy hiện giống snapshot business rút gọn hơn là raw Zeus truth.
- `Số buổi còn lại` trong legacy cũ bị transform âm, nên không nên dùng nó làm ground truth.
