- Đọc hiểu toàn bộ dự án trong thư mục `template`, `strongdm.md`, `AGENTS.md`. Hãy tạo file REQUESTS.md trong thư mục `template` với các chỉ dẫn để coding agent có thể bắt đầu chạy dựa trên file REQUESTS.md đó. File AGENTS.md trong `template` cần có cấu trúc như sau:

```
**Mục tiêu:** 
```
- Hãy dựa trên toàn bộ dữ liệu trong thư mục excels/ và tài liệu đặc tả thư mục CSS file spw.md để phân tích nghiệp vụ, cấu trúc dữ liệu, KPI, workflow và mối quan hệ giữa các dữ liệu, sau đó thiết kế và xây dựng một website dashboard production-ready hoàn chỉnh, hiện đại, trực quan và có khả năng mở rộng. Dashboard cần có kiến trúc khoa học gồm overview dashboard, các dashboard chuyên sâu theo từng module nghiệp vụ, hệ thống filters, drill-down, phân quyền người dùng, điều hướng UX hợp lý và trực quan hóa dữ liệu bằng các loại biểu đồ phù hợp như KPI cards, line chart, bar chart, heatmap, funnel, table, timeline,… dựa trên đúng ngữ cảnh dữ liệu thực tế. Giao diện cần đạt chất lượng enterprise-grade, responsive cho desktop/mobile, tối ưu readability, có loading/empty/error states, dark/light mode nếu phù hợp và ưu tiên trải nghiệm hỗ trợ ra quyết định thay vì chỉ hiển thị dữ liệu. 
```



## Cấu trúc:
```
CRM-Dashboard/
├── CRM-Dashboard/         
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

## Yêu cầu:

1. Thực hiện mọi yêu cầu theo các file mô tả trong thư mục `docs/specs`. Luôn kiểm tra xem có file nào trong `docs/specs`có thay đổi không, nếu có cứ thay đổi gì dù nhỏ thì cũng tạo lại code cho các files trong thư mục `scripts`.
----
2. Nếu có file `REQUESTS.md`:
   - Ưu tiên xử lý toàn bộ nội dung trong `REQUESTS.md` trước mọi task khác.
   - Sau khi xử lý xong, bắt buộc cập nhật kết quả vào `specs` (nếu cần thiết) và `regression` để đảm bảo không tái phát lỗi.
   - Chỉ tiếp tục các task khác khi các mục trong `REQUESTS.md` đã được resolve, đã ghi rõ trạng thái, hoặc file `REQUESTS.md` được human xóa bỏ.
----
3. Check your WIP with these commands, then pick up next step:

git status              # See current branch and uncommitted changes
git diff                # Review what you've modified
git log --oneline -5    # Recent commits to understand context

Then continue with your task (Phase 0 -> Phase N):

- **Phase 0** — << hướng dẫn và link tới tài liệu >>
- **Phase 1** — << hướng dẫn và link tới tài liệu >>
- **Phase 2** — << hướng dẫn và link tới tài liệu >>
- ...
- **Phase N (với N>2)** — << hướng dẫn và link tới tài liệu >>
- **Phase Tracker** — Theo dõi tiến độ + snapshot xác nhận: [docs/phase_status.md](docs/runbooks/phase_status.md)

- N là có thứ tự tăng dần, ví dụ: Phase 3, Phase 4, Phase 5, ... (N>3)
- Documentation cung cấp định hướng kỹ thuật; trong quá trình thực thi bạn được quyền điều chỉnh giải pháp nếu thực tế đòi hỏi, miễn bám nguyên tắc ghi lại lý do trong commit/note.
- After finishing the current steps, add a status note in your commit message documenting which tasks are complete and what is next, send the report to Discord channel based on discord.cnf

- Then make a git commit (Angular/Commitizen standard) with detailed body. Example:

git commit -m "feat(scheduler): implement selective parallel execution

- Add internal/scheduler package with parallel-safe tool classification
- Implement channel-based semaphore for serial tools (shell/patch/task)
- Verify timing: 3 parallel tools complete in ~100ms vs 300ms serial
- Add table-driven tests with race detector
- Phase 0 Task 0.4 complete, next: Task 0.5 (scheduler tests)

Refs: runbooks/phase_0.md"
```
- Tạo cấu trúc thư mục `template` 
```
template/
├── docs/
│   ├── regression/ 
│   ├── specs/        (chứa file mẫu SPEC_TEMPLATE.md)
│   ├── runbooks/     (chứa file mẫu REGRESSION_TEMPLATE.md)
├── scripts/
```

