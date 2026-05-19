# Phase 138
- Chỉnh sửa lại trang `Vận hành` (`/daily-ops?program=speakwell`) theo yêu cầu sau: 
Tạo 3 tabs: `All`, `SPEAKWELL` (hiện tại đang là `🗣️SpeakWellⓘ`), `EASY SPEAK` với tất cả các chỉ số lấy theo lần lượt:
	1. Khi nhấn vào tab `ALL` thì sẽ liệt kê mọi chỉ số như hiện tại với `ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)`
	2. Khi nhấn vào tab `SPEAKWELL` thì sẽ liệt kê mọi chỉ số như hiện tại với `ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577)
	3. Khi nhấn vào tab `EASY SPEAK` thì sẽ liệt kê mọi chỉ số như hiện tại với `ordles_tlang_id IN (403,404,471,582,583,584,585,586)
Không để sót bất cứ chỉ số nào đã từng thiết lập. 
- Chỉnh sửa tên tab `🗣️SpeakWellⓘ` trang `Doanh thu` (`/revenue?program=speakwell`) và trang `Quản trị GV` (/teacher-management?program=speakwell) thành `📊 All ⓘ`. Đổi tham số `/revenue?program=speakwell` thành `/revenue?program=all` và `/teacher-management?program=speakwell` thành `/teacher-management?program=all` 

- Phải cache dữ liệu của cả `All`, `SPEAKWELL` và `EASY SPEAK` để switch các tab này nhanh nhất có thể. Khi nhấn nút `Làm mới` cũng sẽ lấy dữ liệu mới nhất và cache cho cả `All`, `SPEAKWELL` và `EASY SPEAK`. Cần cho người dùng biết tiến độ caching. Việc Cache dữ liệu áp dụng cho tất cả các trang: 
```
KPI
Vận hành
Doanh thu
Chất lượng
Người dùng
Funnel
Quản trị GV
LCMS
Chăm sóc CSI
```