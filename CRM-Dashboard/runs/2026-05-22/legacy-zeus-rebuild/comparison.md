# So sánh rebuild Zeus vs legacy

## Period detect
- Target month: **2026-04** (2026-04-01 → 2026-04-30)
- Base month: **2026-03** (2026-03-01 → 2026-03-31)

## Population
- Legacy rows: **5054**
- Rebuilt rows: **5054**
- Common student_id: **5054**
- Only legacy: **0**
- Only rebuilt: **0**

## Match summary
| Field | Exact | Exact % | Within 5 | Within 5 % |
|---|---:|---:|---:|---:|
| score_target | 5054 | 100% | 5054 | 100% |
| score_base | 4617 | 91.4% | 4623 | 91.5% |
| variance | 4804 | 95.1% | 4818 | 95.3% |
| target_category | 5054 | 100% | 5054 | 100% |
| base_category | 4616 | 91.3% | 4616 | 91.3% |
| movement_group | 4618 | 91.4% | 4618 | 91.4% |
| teacher_disruption_rate | 3790 | 75% | 5054 | 100% |
| unfinished_rate | 4715 | 93.3% | 5054 | 100% |
| activation_speed | 4644 | 91.9% | 4644 | 91.9% |
| renewal_status | 4644 | 91.9% | 4644 | 91.9% |
| renewal_product | 4644 | 91.9% | 4644 | 91.9% |
| lifecycle_status | 4644 | 91.9% | 4644 | 91.9% |
| management_health_score | 4982 | 98.6% | 4982 | 98.6% |
| learning_pace | 5054 | 100% | 5054 | 100% |
| teacher_disruption_cumulative | 2314 | 45.8% | 5054 | 100% |

## Nhận xét nhanh
- `score_target/base/category/movement_group` là nhóm cột quan trọng nhất để xác định có thể tái tạo legacy-style từ Zeus hay không.
- Các cột `activation_speed`, `renewal_status`, `renewal_product`, `lifecycle_status` hiện đang rebuild bằng heuristic vì legacy gốc trông giống snapshot business rút gọn hơn là raw Zeus truth.
- `remaining_sessions` không đưa vào bảng match chính vì legacy cũ là transformed negative values, không phản ánh balance thật từ Zeus.
