<?php

namespace App\Jobs;

use App\Services\UsageReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ProcessUsageReportExport - Background job for exporting usage reports
 *
 * Phase 28: Implements background job processing for large exports to prevent
 * 504 Gateway Timeout errors. Uses Redis cache to track progress and status.
 *
 * Phase 31: Changed export format from CSV to Excel (.xlsx) with:
 * - Column comments explaining data source
 * - Formulas for calculated fields
 * - Proper formatting for numbers/dates
 *
 * Phase 43: Removed database (ExportJob model) dependency:
 * - Uses only Redis cache for job tracking
 * - Database is readonly on server, cannot create tables
 */
class ProcessUsageReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour max

    /**
     * The number of times the job may be attempted.
     * Phase 46: Set to 1 - no retries, job should either succeed or fail immediately
     */
    public int $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     * Phase 46: Set to 1 - prevents job from being retried after exception
     */
    public int $maxExceptions = 1;

    /**
     * Indicate if the job should be marked as failed on timeout.
     * Phase 46: Ensures job fails cleanly if it times out
     */
    public bool $failOnTimeout = true;

    /**
     * Delete the job if its models no longer exist.
     * Phase 46: Prevents serialization issues
     */
    public bool $deleteWhenMissingModels = true;

    protected string $exportId;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $exportId, Carbon $startDate, Carbon $endDate, int $userId)
    {
        $this->exportId = $exportId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     * Phase 43: Uses only Redis cache for tracking (removed database dependency)
     * Phase 48: Added system metrics logging for debugging failures
     */
    public function handle(): void
    {
        $cacheKey = "export_job:{$this->exportId}";

        // Phase 48: Log initial system metrics for debugging
        $this->logSystemMetrics('JOB_START');

        try {
            // Update status to processing
            $this->updateProgress($cacheKey, 'processing', 0, 'Đang khởi tạo...');

            // Step 1: Generate report data (50% of progress)
            $this->updateProgress($cacheKey, 'processing', 10, 'Đang truy vấn dữ liệu đơn hàng...');

            $service = new UsageReportService($this->startDate, $this->endDate);
            $report = $service->generateReport();

            $this->updateProgress($cacheKey, 'processing', 50, 'Đã xử lý ' . count($report['data']) . ' bản ghi...');

            // Step 2: Generate Excel file (remaining 50%)
            $this->updateProgress($cacheKey, 'processing', 60, 'Đang tạo file Excel...');

            $filename = $this->generateExcelFile($report);

            $this->updateProgress($cacheKey, 'processing', 90, 'Đang hoàn tất...');

            // Mark as completed in Redis cache
            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Xuất báo cáo thành công!',
                'filename' => $filename,
                'download_url' => "/api/download-export/{$this->exportId}",
                'record_count' => count($report['data']),
                'completed_at' => now()->toIso8601String(),
                'period' => [
                    'start' => $this->startDate->format('d/m/Y'),
                    'end' => $this->endDate->format('d/m/Y'),
                ],
            ], now()->addHours(24)); // Keep for 24 hours

            Log::info("Export job completed: {$this->exportId}", [
                'records' => count($report['data']),
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            // Mark as failed in Redis cache
            Cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ], now()->addHours(1));

            Log::error("Export job failed: {$this->exportId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate Excel file from report data
     * Phase 31: Changed from CSV to Excel with comments and formulas
     * Phase 50: Removed cell comments to fix memory exhaustion (512MB limit)
     *           - Comments are now in a separate "Chú thích" worksheet
     *           - This prevents PhpSpreadsheet's sortCellReferenceArray from OOM
     */
    protected function generateExcelFile(array $report): string
    {
        $filename = "bao-cao-su-dung_{$this->exportId}.xlsx";
        $filepath = "exports/{$filename}";
        $columns = $report['columns'];

        $spreadsheet = new Spreadsheet();
        
        // Phase 50: Disable calculation engine to prevent memory exhaustion
        // Formulas will be stored as-is without being calculated by PHP
        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)
            ->disableCalculationCache();
        
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo sử dụng');

        // Set header row (Phase 50: removed per-cell comments to save memory)
        $colIndex = 1;
        foreach ($columns as $col) {
            $cellRef = $this->getColumnLetter($colIndex) . '1';
            $sheet->setCellValue($cellRef, $col['label']);
            $colIndex++;
        }

        // Style header row
        $lastColLetter = $this->getColumnLetter(count($columns));
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Data rows
        $total = count($report['data']);
        $rowIndex = 2;

        // Phase 50: Removed colMap variable - no longer needed since formulas were removed

        foreach ($report['data'] as $index => $row) {
            $colIndex = 1;
            foreach ($columns as $col) {
                $cellRef = $this->getColumnLetter($colIndex) . $rowIndex;
                $value = $row[$col['key']] ?? '';

                // Phase 50: Removed formula columns to prevent memory exhaustion
                // The calculation engine was using too much memory with large datasets
                // Now all values are pre-calculated in PHP instead of Excel formulas
                if (in_array($col['key'], ['payment_date', 'start_date', 'end_date', 'cancel_date']) && $value) {
                    // Format dates
                    $dateValue = Carbon::parse($value)->format('d/m/Y H:i:s');
                    $sheet->setCellValue($cellRef, $dateValue);
                } elseif ((str_contains($col['key'], '_amount') || $col['key'] === 'price_per_lesson') && is_numeric($value)) {
                    // Keep numeric values as numbers (not formatted strings)
                    $sheet->setCellValue($cellRef, (float)$value);
                    $sheet->getStyle($cellRef)->getNumberFormat()->setFormatCode('#,##0');
                } elseif (str_contains($col['key'], '_lessons') && is_numeric($value)) {
                    $sheet->setCellValue($cellRef, (int)$value);
                } else {
                    $sheet->setCellValue($cellRef, $value);
                }

                $colIndex++;
            }

            // Update progress periodically (every 100 records)
            if ($index % 100 === 0) {
                $progress = 60 + (int)(($index / $total) * 30);
                $this->updateProgress(
                    "export_job:{$this->exportId}",
                    'processing',
                    $progress,
                    "Đang ghi file... ({$index}/{$total})"
                );
            }

            $rowIndex++;
        }

        // Apply borders to data area
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("A2:{$lastColLetter}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);
        }

        // Auto-size columns (with max width)
        foreach (range(1, count($columns)) as $colIdx) {
            $colLetter = $this->getColumnLetter($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Phase 50: Add a separate "Chú thích" (Notes) sheet for column documentation
        // This replaces per-cell comments which caused memory exhaustion
        $this->addNotesSheet($spreadsheet, $columns);

        // Save to temp file then move to storage
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Move to storage
        Storage::disk('local')->put($filepath, file_get_contents($tempFile));
        unlink($tempFile);

        return $filename;
    }

    /**
     * Get column letter from index (1=A, 2=B, ... 27=AA, etc)
     */
    protected function getColumnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $mod = ($columnIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnIndex = intval(($columnIndex - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Phase 50: Add a separate "Chú thích" (Notes) sheet for column documentation
     * This is more memory-efficient than per-cell comments which cause OOM
     */
    protected function addNotesSheet(Spreadsheet $spreadsheet, array $columns): void
    {
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Chú thích cột');

        // Header row
        $notesSheet->setCellValue('A1', 'Tên cột');
        $notesSheet->setCellValue('B1', 'Mô tả / Nguồn dữ liệu');

        // Style header
        $notesSheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
        ]);

        // Get column comments
        $columnComments = $this->getColumnComments();

        // Add data rows
        $rowIndex = 2;
        foreach ($columns as $col) {
            $notesSheet->setCellValue('A' . $rowIndex, $col['label']);
            $comment = $columnComments[$col['key']] ?? 'Không có mô tả';
            // Replace newlines with spaces for cleaner display
            $comment = str_replace("\n", ' | ', $comment);
            $notesSheet->setCellValue('B' . $rowIndex, $comment);
            $rowIndex++;
        }

        // Auto-size columns
        $notesSheet->getColumnDimension('A')->setWidth(30);
        $notesSheet->getColumnDimension('B')->setWidth(80);

        // Set active sheet back to data sheet
        $spreadsheet->setActiveSheetIndex(0);
    }

    /**
     * Get comments for each column explaining data source
     * Phase 31: Added column comments
     */
    /**
     * Phase 106: Updated column comments to match reduced column set
     */
    protected function getColumnComments(): array
    {
        return [
            'package_type' => "Phân loại gói học\nNguồn: Phân tích từ tên môn học (tlang_identifier)\nGiá trị: SPW, IE, EP, ES, FT, OTHER",
            'student_code' => "Mã học viên\nNguồn: tbl_users.user_username\nHoặc STU + user_id nếu không có username",
            'student_name' => "Tên đầy đủ của học viên\nNguồn: tbl_users.user_first_name + user_last_name",
            'email' => "Email học viên\nNguồn: tbl_users.user_email",
            'level' => "Trình độ học viên\nNguồn: Hiện chưa có dữ liệu",
            'class_size' => "Quy mô lớp học\nNguồn: Phân tích từ tên môn học (tlang_identifier)\nGiá trị: 1:1, 1:2, 1:3, 1:6, 1:8, Group, N/A",
            'teacher_nationality' => "Quốc tịch giáo viên\nNguồn: tbl_countries.country_code (qua teacher.user_country_id)\nGiá trị: VN, NN, PHIL, MIX, N/A\nFallback: Phân tích từ tên môn học nếu không có dữ liệu",
            'zeus_package_id' => "Mã gói trong hệ thống Zeus\nNguồn: tbl_order_lessons.ordles_tlang_id",
            'billing_id' => "ID giao dịch thanh toán\nNguồn: tbl_order_payments.ordpay_txn_id (đơn billing)\nHoặc: order_extra_data.package_id (đơn import CSV)",
            'package_name' => "Tên gói học/môn học\nNguồn: tbl_teach_languages.tlang_identifier",
            'total_sessions' => "Tổng số buổi học hoàn thành trong kỳ\nNguồn: COUNT(tbl_order_lessons.ordles_id) WHERE ordles_status = 3 AND ordles_lesson_starttime trong kỳ",
            'item_id' => "ID item trong đơn hàng\nNguồn: order_extra_data.item_id",
            'payment_date' => "Ngày thanh toán đơn hàng\nNguồn: tbl_orders.order_addedon",
            'start_date' => "Ngày bắt đầu buổi học đầu tiên\nNguồn: MIN(tbl_order_lessons.ordles_lesson_starttime)",
            'end_date' => "Ngày kết thúc buổi học cuối cùng\nNguồn: MAX(tbl_order_lessons.ordles_lesson_endtime)",
            'cancel_date' => "Ngày hủy gói\nNguồn: Không áp dụng ở cấp order-lesson",
            'status' => "Trạng thái gói học\nNguồn: Tính toán từ số buổi đã sử dụng\nGiá trị: Completed, Active, Pending, Unknown",
            'price_per_lesson' => "Giá thực tế mỗi buổi học\nCông thức: (Tổng tiền - Giảm giá) / Số buổi\n= (SUM(ordles_amount) - SUM(ordles_discount)) / COUNT(ordles_id)",
            'opening_lessons' => "Số buổi dư đầu kỳ\nNguồn: Tổng buổi - Số buổi đã hoàn thành trước kỳ báo cáo\nLưu ý: = 0 nếu đơn hàng được tạo sau ngày đầu kỳ",
            'opening_amount' => "Số tiền dư đầu kỳ\nCông thức: Số buổi dư đầu kỳ × Giá/buổi\nLưu ý: = 0 nếu đơn hàng được tạo sau ngày đầu kỳ",
            'purchased_lessons' => "Số buổi mua trong kỳ\nNguồn: COUNT(ordles_id) nếu order_addedon trong kỳ",
            'purchased_amount' => "Số tiền mua trong kỳ\nNguồn: SUM(ordles_amount - ordles_discount) nếu trong kỳ",
            'used_lessons' => "Số buổi đã sử dụng trong kỳ\nNguồn: COUNT(ordles_id) WHERE ordles_status=3 AND ordles_lesson_endtime trong kỳ",
            'used_amount' => "Số tiền đã sử dụng trong kỳ\nCông thức: Số buổi sử dụng × Giá/buổi",
            'cancelled_lessons' => "Số buổi bị hủy\nNguồn: COUNT(ordles_id) WHERE ordles_status=4\nLưu ý: Tách riêng khỏi tổng giảm, chỉ mang tính thông tin",
            'closing_lessons' => "Số buổi cuối kỳ\nCông thức: Đầu kỳ + Tổng tăng - Tổng giảm",
            'closing_amount' => "Số tiền cuối kỳ\nCông thức: Đầu kỳ + Tổng tăng - Tổng giảm",
            'zeus_order_id' => "Mã đơn hàng Zeus\nNguồn: tbl_orders.order_id",
        ];
    }

    /**
     * Check if column should have a formula
     * @deprecated Phase 50: Formulas removed to fix memory exhaustion. Values are now pre-calculated in PHP.
     */
    protected function isFormulaColumn(string $key, array $colMap): bool
    {
        // Phase 50: Always return false - formulas disabled to prevent memory issues
        return false;
    }

    /**
     * Get Excel formula for calculated columns
     * Phase 31: Added formulas for calculated cells
     * @deprecated Phase 50: Formulas removed to fix memory exhaustion. Values are now pre-calculated in PHP.
     */
    protected function getFormulaForColumn(string $key, int $rowIndex, array $colMap): ?string
    {
        // Phase 50: Formulas disabled - return null to use pre-calculated values
        return null;
    }

    /**
     * Update progress in cache
     * Phase 43: Uses only Redis cache (removed database dependency)
     */
    protected function updateProgress(string $cacheKey, string $status, int $progress, string $message): void
    {
        // Update Redis cache for real-time polling
        $data = Cache::get($cacheKey, []);
        $data['status'] = $status;
        $data['progress'] = $progress;
        $data['message'] = $message;
        $data['updated_at'] = now()->toIso8601String();

        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    /**
     * Handle a job failure.
     * Phase 43: Uses only Redis cache (removed database dependency)
     * Phase 47: Enhanced logging to capture full exception details (ROOT CAUSE)
     */
    public function failed(\Throwable $exception): void
    {
        $cacheKey = "export_job:{$this->exportId}";

        // Phase 47: Find the root cause exception (unwrap MaxAttemptsExceededException if present)
        $rootCause = $exception;
        while ($rootCause->getPrevious() !== null) {
            $rootCause = $rootCause->getPrevious();
        }

        // Phase 47: Include full error details for debugging
        $errorDetails = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'root_cause_class' => get_class($rootCause),
            'root_cause_message' => $rootCause->getMessage(),
            'root_cause_file' => $rootCause->getFile(),
            'root_cause_line' => $rootCause->getLine(),
        ];

        // Update Redis cache with detailed error info
        Cache::put($cacheKey, [
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Lỗi: ' . $rootCause->getMessage(),
            'error' => $rootCause->getMessage(),
            'error_details' => $errorDetails,
            'failed_at' => now()->toIso8601String(),
        ], now()->addHours(1));

        // Phase 47: Log with CRITICAL level and FULL stack trace
        Log::critical("🔴 ProcessUsageReportExport FAILED - ROOT CAUSE", [
            'export_id' => $this->exportId,
            'user_id' => $this->userId,
            'date_range' => $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d'),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'root_cause_class' => get_class($rootCause),
            'root_cause_message' => $rootCause->getMessage(),
            'root_cause_file' => $rootCause->getFile(),
            'root_cause_line' => $rootCause->getLine(),
            'root_cause_trace' => $rootCause->getTraceAsString(),
        ]);

        // Phase 47: Also log to stderr so it appears in docker logs
        error_log("🔴 ProcessUsageReportExport FAILED - ROOT CAUSE");
        error_log("Export ID: {$this->exportId}");
        error_log("User ID: {$this->userId}");
        error_log("Date Range: " . $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d'));
        error_log("Exception: " . get_class($exception) . ": " . $exception->getMessage());
        if ($rootCause !== $exception) {
            error_log("Root Cause: " . get_class($rootCause) . ": " . $rootCause->getMessage());
        }
        error_log("File: " . $rootCause->getFile() . ":" . $rootCause->getLine());
        error_log("Stack Trace:\n" . $rootCause->getTraceAsString());

        // Phase 48: Log system metrics when failure occurs
        $this->logSystemMetrics('JOB_FAILED');
    }

    /**
     * Log system metrics for debugging resource issues.
     * Phase 48: Added to diagnose memory/timeout issues
     */
    protected function logSystemMetrics(string $context): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        $metrics = [
            'context' => $context,
            'export_id' => $this->exportId,
            'memory_current' => $this->formatBytes($memoryUsage),
            'memory_peak' => $this->formatBytes($memoryPeak),
            'memory_limit' => $memoryLimit,
            'memory_usage_percent' => $this->calculateMemoryPercent($memoryUsage, $memoryLimit),
            'date_range' => $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d'),
            'php_version' => PHP_VERSION,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info("📊 System Metrics [{$context}]", $metrics);
        
        // Also log to stderr for docker logs visibility
        error_log("📊 System Metrics [{$context}]");
        error_log("  Memory: {$metrics['memory_current']} / {$metrics['memory_limit']} ({$metrics['memory_usage_percent']}%)");
        error_log("  Memory Peak: {$metrics['memory_peak']}");
        error_log("  Export ID: {$this->exportId}");
        error_log("  Date Range: {$metrics['date_range']}");
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }

    /**
     * Calculate memory usage percentage.
     */
    protected function calculateMemoryPercent(int $currentBytes, string $limit): float
    {
        // Parse memory limit (e.g., "512M", "1G", "256000000")
        $limit = trim($limit);
        if (preg_match('/^(\d+)(M|G|K)?$/i', $limit, $matches)) {
            $value = (int) $matches[1];
            $unit = strtoupper($matches[2] ?? '');
            
            switch ($unit) {
                case 'G':
                    $limitBytes = $value * 1024 * 1024 * 1024;
                    break;
                case 'M':
                    $limitBytes = $value * 1024 * 1024;
                    break;
                case 'K':
                    $limitBytes = $value * 1024;
                    break;
                default:
                    $limitBytes = $value;
            }
            
            if ($limitBytes > 0) {
                return round(($currentBytes / $limitBytes) * 100, 1);
            }
        }
        
        return 0;
    }
}
