**Mục tiêu:**

Xây dựng và vận hành dự án `CRM-Dashboard` theo chuẩn DUCKMIND: có seed/spec rõ ràng, validator hành vi end-to-end, memory trên filesystem, log chạy replayable và kiểm soát side effect tối thiểu.

Agent trong template này phải dựa trên toàn bộ dữ liệu trong `excels/` và tài liệu đặc tả `CSS/SPW.md` hoặc `CSS/spw.md` để phân tích nghiệp vụ, cấu trúc dữ liệu, KPI, workflow và mối quan hệ giữa các dữ liệu. Từ đó, agent thiết kế và xây dựng website dashboard production-ready, hiện đại, trực quan, có khả năng mở rộng, gồm overview dashboard, dashboard chuyên sâu theo từng module nghiệp vụ, hệ thống filters, drill-down, phân quyền người dùng, điều hướng UX hợp lý và trực quan hóa dữ liệu bằng KPI cards, line chart, bar chart, heatmap, funnel, table, timeline hoặc biểu đồ phù hợp khác dựa trên đúng ngữ cảnh dữ liệu thực tế.

Dashboard phải đạt chất lượng enterprise-grade: responsive cho desktop/mobile, tối ưu readability, có loading/empty/error states, có dark/light mode nếu phù hợp, và ưu tiên trải nghiệm hỗ trợ ra quyết định thay vì chỉ hiển thị dữ liệu.

---
## Cấu trúc:
```
CRM-Dashboard/
├── CRM-Dashboard/
├── zeus/
│   ├── zeus_core.sql    (schema và dữ liệu mẫu của CSDL Zeus)
│   ├── core/    (chứa mã nguồn tham khảo để hiểu được các nghiệp vụ và các kết nối CSDL thông qua schema zeus_core.sql)
├── docs/
│   ├── regression/    (thư mục chứa các tệp yêu cầu sửa lỗi)
│   ├── specs/         (thư mục chứa các tệp spec )
│   ├── runbooks/      (ghi lại những yêu cầu đã thực hiện)
├── scripts/
│   ├── build.sh       (build/compile web)
│   └── deploy.sh      (deploy CRM-Dashboard và run web)
├── conf/              (chứa các credentials) 
├── dist/              (kết quả được build) 
└── README.md          (hướng dẫn sử dụng do người dùng tự điền)
```

---

## 1. Working contract bắt buộc

Trước khi chạy bất kỳ task nào, agent phải ghi rõ:

1. **Input**: user cung cấp gì, format nào, dữ liệu nằm ở đâu?
2. **Output**: kết quả gồm file, behavior, build artifact, report nào?
3. **Failure modes**: task có thể hỏng theo những cách nào?
4. **Side effects**: file nào được tạo/sửa, command nào được chạy, service nào bị ảnh hưởng?
5. **Permissions**: agent cần quyền đọc/ghi/chạy ở đâu?

Nếu thiếu input quan trọng như `excels/`, `strongdm.md`, credential hoặc spec, agent không được đoán; phải ghi blocker và hỏi human.

---

## 2. Luồng làm việc chuẩn

1. Đọc `REQUESTS.md` trước mọi task khác.
2. Đọc `docs/specs/*`; nếu file spec thay đổi dù nhỏ, review và cập nhật lại `scripts/`.
3. Chạy preflight:
   ```bash
   git status
   git diff
   git log --oneline -5
   ```
4. Tạo run trace:
   ```text
   runs/<YYYY-MM-DD>/<run-id>/
   ├── input.md
   ├── contract.md
   ├── plan.md
   ├── commands.log
   ├── artifacts/
   └── result.json
   ```
5. Tạo hoặc cập nhật scenario/validator trước khi sửa code lớn.
6. Triển khai thay đổi nhỏ, có thể rollback.
7. Validate bằng hành vi end-to-end, không chỉ đọc code.
8. Cập nhật regression/runbook/status.
9. Báo cáo kết quả và residual risk.

---

## 3. Quy tắc dữ liệu

- Không đọc trực tiếp Google Sheet/Excel từ dashboard khi user mở trang.
- Dữ liệu phải đi qua sync/import job, validate, normalize và lưu vào database/cache nội bộ.
- Không ghi ngược vào dữ liệu nguồn nếu chưa có quyền rõ.
- Không dùng dòng thiếu mã học viên để join sức khỏe với doanh thu/gia hạn.
- Không log secret, token, credential hoặc dữ liệu cá nhân nhạy cảm.
- Mọi data quality issue phải được ghi vào report.

---

## 4. Quy tắc dashboard

- Mỗi KPI phải có công thức, input fields, filter scope và behavior khi thiếu dữ liệu.
- Chart phải phù hợp với câu hỏi nghiệp vụ, không dùng biểu đồ chỉ để trang trí.
- Drill-down phải khớp với KPI tổng.
- Filters phải rõ trạng thái đang áp dụng.
- Empty/error/loading states là bắt buộc.
- API phải enforce phân quyền, không chỉ ẩn UI.

---

## 5. Definition of Done

Task chỉ được coi là xong khi:

- Seed/spec rõ ràng và được lưu trong `docs/specs/` hoặc `REQUESTS.md`.
- Validator/scenario hành vi đã chạy và pass.
- Regression được cập nhật nếu có bug hoặc rủi ro tái phát.
- Run trace đầy đủ trong `runs/`.
- `docs/runbooks/phase_status.md` được cập nhật.
- Scripts liên quan đã được review nếu specs thay đổi.
- Không có secret trong diff/log.
- Báo cáo cuối nêu rõ: đã đổi gì, kiểm chứng bằng gì, còn rủi ro/blocker gì.