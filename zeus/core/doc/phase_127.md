# Phase 127

- Loại bỏ yêu cầu mọi chỉ số trong LCMS phải đảm bảo là đúng các chỉ số thuộc khóa học SPEAKWELL với:
```sql
ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)
```
mà trả lại về các chỉ số thuộc khóa học SPEAKWELL với:
```
usrasi_course_id IN (346, 563, 595, 1084)
```

- Các hiển thị studentID và tìm kiếm theo student ID phải theo trường `stu_user_id`

- Fix triệt để lỗi:
```
chart.js:13 
 Uncaught TypeError: Cannot read properties of null (reading 'save')
Ie	@	chart.js:13
_drawDataset	@	chart.js:13
_drawDatasets	@	chart.js:13
draw	@	chart.js:13
(anonymous)	@	chart.js:7
_update	@	chart.js:7
(anonymous)	@	chart.js:7
requestAnimationFrame		
_refresh	@	chart.js:7
(anonymous)	@	chart.js:7
requestAnimationFrame		
_refresh	@	chart.js:7
(anonymous)	@	chart.js:7
requestAnimationFrame		
_refresh	@	chart.js:7
(anonymous)	@	chart.js:7
requestAnimationFrame		
_refresh	@	chart.js:7
(anonymous)	@	chart.js:7
requestAnimationFrame		
_refresh	@	chart.js:7
start	@	chart.js:7
render	@	chart.js:13
update	@	chart.js:13
Tn	@	chart.js:13
renderCourseChart	@	lcms:2963
(anonymous)	@	lcms:2620
requestAnimationFrame		
(anonymous)	@	lcms:2620
(anonymous)	@	cdn.min.js:5
jt	@	cdn.min.js:5
(anonymous)	@	cdn.min.js:5
setTimeout		
(anonymous)	@	cdn.min.js:5
```