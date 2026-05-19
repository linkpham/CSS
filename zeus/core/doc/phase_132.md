# Phase 132

- Dữ liệu trong `'/Users/que/Downloads/zeus/Chăm sóc CSI.xlsx'` chỉ là dữ liệu mô phỏng, không được lấy dữ liệu sẵn có trong sheet `1. Raw data` mà pHải đảm bảo lấy được từ các bảng dữ liệu trong hệ thống cho các khóa học của SPEAKWELL với
```
`ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)`. 
```

- Fix triệt để lỗi ```chart.js:13 
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
renderHealthChart	@	csi:2010
renderCharts	@	csi:1976
loadData	@	csi:1870
await in loadData		
init	@	csi:1845
(anonymous)	@	cdn.min.js:5
ur	@	cdn.min.js:1
N	@	cdn.min.js:5
(anonymous)	@	cdn.min.js:5
r	@	cdn.min.js:5
n	@	cdn.min.js:5
Er	@	cdn.min.js:5
S	@	cdn.min.js:5
(anonymous)	@	cdn.min.js:5
Tr	@	cdn.min.js:5
(anonymous)	@	cdn.min.js:5```