# Phase 141

- Không tiếp tục sử dụng phương án PJAX để load trang mới. Tôi muốn dữ liệu được load và cache 1 lần với All còn lại `SPEAKWELL` và `EASY SPEAK` sẽ là phép lọc dữ liệu với:
 1. Khi nhấn vào tab `ALL` thì sẽ lọc mọi chỉ số như hiện tại với `ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)`
 2. Khi nhấn vào tab `SPEAKWELL` thì sẽ lọc mọi chỉ số như hiện tại với `ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577)
 3. Khi nhấn vào tab `EASY SPEAK` thì sẽ lọc mọi chỉ số như hiện tại với `ordles_tlang_id IN (403,404,471,582,583,584,585,586)
Không để sót bất cứ chỉ số nào đã từng thiết lập. 

