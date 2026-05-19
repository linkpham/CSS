# Phase 31 - Excel Export với Comments và Formulas

## Yêu cầu

1. **Fix "Giá/buổi" column**: Cột `Giá/buổi` hiện đang lấy giá niêm yết, cần sửa thành `ordles_amount - ordles_discount` để ra giá trị thực
2. **Đổi định dạng file**: Export file định dạng Excel (.xlsx) thay vì CSV
3. **Danh sách file đã xuất**: Sau khi có kết quả, cần có danh sách các file đã thực hiện để người dùng có thể download về
4. **Column comments**: Tại mỗi cột cần có comment ghi lại giải thích dữ liệu lấy từ đâu
5. **Excel formulas**: Các ô trong excel có tính toán thì cần kèm theo công thức

## Thay đổi

### 1. Cài đặt PhpSpreadsheet
```bash
composer require phpoffice/phpspreadsheet
```

### 2. Sửa tính toán Giá/buổi trong UsageReportService.php
- Thêm query `SUM(COALESCE(ol.ordles_discount, 0)) as total_discount`
- Tính `price_per_lesson = (total_amount - total_discount) / total_lessons`

### 3. Cập nhật ProcessUsageReportExport.php
- Đổi từ CSV sang Excel (.xlsx)
- Thêm comments cho từng cột header giải thích nguồn dữ liệu
- Thêm Excel formulas cho các cột tính toán:
  - `total_increase_lessons/amount`: Tổng các khoản tăng
  - `total_decrease_lessons/amount`: Tổng các khoản giảm
  - `closing_lessons/amount`: =MAX(0, Đầu kỳ + Tổng tăng - Tổng giảm)
- Format số với #,##0
- Freeze header row
- Auto-size columns

### 4. Cập nhật DashboardController.php
- Thêm method `generateExcelForSync()` cho sync mode export
- Cập nhật `apiDownloadExport()` để hỗ trợ cả CSV và Excel
- Thêm API `apiExportList()` để lấy danh sách file đã xuất
- Thêm helper methods: `getColumnLetter()`, `isFormulaColumnForExcel()`, `getFormulaForColumnExcel()`, `getColumnCommentsForExcel()`, `formatFileSize()`

### 5. Thêm route mới
- `GET /api/export-list` - Lấy danh sách file đã xuất

## Files đã thay đổi

- `src/composer.json` - Thêm phpoffice/phpspreadsheet
- `src/app/Services/UsageReportService.php` - Fix tính Giá/buổi
- `src/app/Jobs/ProcessUsageReportExport.php` - Excel generation with comments & formulas
- `src/app/Http/Controllers/DashboardController.php` - Sync Excel export + export list API
- `src/routes/web.php` - Thêm route export-list

## API Response: GET /api/export-list

```json
{
  "success": true,
  "data": [
    {
      "export_id": "exp_abc123_1234567890",
      "filename": "bao-cao-su-dung_exp_abc123_1234567890.xlsx",
      "extension": "xlsx",
      "size": 123456,
      "size_formatted": "120.6 KB",
      "created_at": "2026-01-22 10:30:00",
      "created_at_formatted": "22/01/2026 10:30",
      "download_url": "/api/download-export/exp_abc123_1234567890",
      "period": {
        "start": "01/01/2026",
        "end": "22/01/2026"
      },
      "record_count": 150
    }
  ]
}
```

## Column Comments (trong file Excel)

Mỗi cột header có tooltip comment giải thích:
- Nguồn dữ liệu (table/column)
- Công thức tính toán (nếu có)
- Giá trị có thể có

Ví dụ:
- `price_per_lesson`: "Giá thực tế mỗi buổi học\nCông thức: (SUM(ordles_amount) - SUM(ordles_discount)) / COUNT(ordles_id)"
- `closing_lessons`: "Số buổi cuối kỳ\nCông thức: =MAX(0,Đầu kỳ+Tổng tăng-Tổng giảm)"

## Excel Formulas

Các cột sau sử dụng công thức Excel thực sự (không phải giá trị tĩnh):
- Tổng tăng - Số buổi: `=Mua+Nhận CN+Bù cam kết+Bù vận hành`
- Tổng tăng - Số tiền: `=Tổng các cột tiền tăng`
- Tổng giảm - Số buổi: `=SD+CN đi+Xóa dư+Hoàn+Deactive`
- Tổng giảm - Số tiền: `=Tổng các cột tiền giảm`
- Cuối kỳ - Số buổi: `=MAX(0,Đầu kỳ+Tổng tăng-Tổng giảm)`
- Cuối kỳ - Số tiền: `=MAX(0,Đầu kỳ+Tổng tăng-Tổng giảm)`

## Kết quả

✅ Giá/buổi tính đúng = (ordles_amount - ordles_discount) / số buổi
✅ Export file Excel (.xlsx) thay vì CSV
✅ Có danh sách file đã xuất qua API /api/export-list
✅ Mỗi cột có comment giải thích nguồn dữ liệu
✅ Các ô tính toán có công thức Excel
