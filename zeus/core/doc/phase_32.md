# Phase 32 - Fix Excel Export Error Handling

## Vấn đề

Phase 31 gặp lỗi sau khi export:
```
Failed to load resource: the server responded with a status of 500 ()
revenue?program=speakwell:2287 
Export error: SyntaxError: Failed to execute 'json' on 'Response': Unexpected end of JSON input
    at HTMLButtonElement.<anonymous> (revenue?program=speakwell:2269:47)
```

## Nguyên nhân

1. **Frontend**: Khi server trả về lỗi 500 nhưng body không phải JSON hợp lệ, `response.json()` sẽ throw SyntaxError
2. **Backend**: Khi có exception trong quá trình export, PHP có thể crash trước khi trả về JSON response

## Giải pháp

### 1. Cải thiện error handling trong JavaScript (revenue.blade.php)

- Sử dụng `response.text()` trước rồi `JSON.parse()` để có thể xử lý lỗi parse
- Thêm try-catch riêng cho việc parse JSON response
- Kiểm tra `response.ok` trước khi xử lý kết quả
- Áp dụng cho cả start export và polling status

```javascript
// Phase 32: Better error handling for non-JSON responses
let result;
try {
    const text = await response.text();
    result = text ? JSON.parse(text) : {};
} catch (parseError) {
    console.error('Failed to parse response:', parseError);
    throw new Error(`Lỗi server (${response.status}): Không thể xử lý phản hồi`);
}

// Check for HTTP errors
if (!response.ok) {
    throw new Error(result.message || `Lỗi server (${response.status})`);
}
```

### 2. Cải thiện error handling trong PHP (DashboardController.php)

- Thêm outer try-catch với `\Throwable` để bắt tất cả lỗi không mong đợi
- Thêm logging cho sync export errors với đầy đủ thông tin (error message, stack trace, period)
- Đảm bảo luôn trả về JSON response hợp lệ dù có lỗi

```php
/**
 * Phase 32: Enhanced error handling and logging
 */
public function apiStartExportUsageReport(Request $request)
{
    try {
        // ... existing code ...
        
    } catch (\Throwable $e) {
        // Phase 32: Catch any unexpected errors and return valid JSON
        \Illuminate\Support\Facades\Log::error("Export unexpected error", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Lỗi không mong đợi: ' . $e->getMessage(),
        ], 500);
    }
}
```

### 3. Logging cho sync export errors

Khi có lỗi trong sync export mode, log đầy đủ thông tin:

```php
\Illuminate\Support\Facades\Log::error("Export sync failed: {$exportId}", [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'period' => [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d'),
    ],
]);
```

## Files đã thay đổi

- `src/resources/views/dashboard/revenue.blade.php` - Cải thiện JavaScript error handling
- `src/app/Http/Controllers/DashboardController.php` - Thêm outer try-catch và logging

## Kết quả

✅ Frontend xử lý được các response không phải JSON hợp lệ
✅ Backend luôn trả về JSON response ngay cả khi có lỗi không mong đợi  
✅ Errors được log đầy đủ để dễ debug
✅ User nhận được thông báo lỗi rõ ràng thay vì "Unexpected end of JSON input"
