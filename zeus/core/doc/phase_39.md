# Phase 39
- Kiểm tra kết quả từ phase 38 vì vẫn tiếp tục lỗi:
```
Failed to load resource: the server responded with a status of 524 ()
revenue?program=speakwell:2275  Failed to parse response: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
    at JSON.parse (<anonymous>)
    at HTMLButtonElement.<anonymous> (revenue?program=speakwell:2273:42)
(anonymous) @ revenue?program=speakwell:2275
revenue?program=speakwell:2300  Export error: Error: Lỗi server (524): Không thể xử lý phản hồi
    at HTMLButtonElement.<anonymous> (revenue?program=speakwell:2276:27)
(anonymous) @ revenue?program=speakwell:2300
```. Không dừng lại nếu chưa hết lỗi, hãy kiểm tra kết nối thông qua DEPLOY_LOCAL.sh để đảm bảo kết quả chính xác. 

- Kiểm tra các chỉ số trong "👥 Học viên theo Size lớp" để đảm bảo lấy đúng các học viên thuộc khóa speakwell những học viên có `ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471))`

