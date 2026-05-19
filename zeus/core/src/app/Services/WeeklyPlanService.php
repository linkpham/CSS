<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * WeeklyPlanService - Xuất file Excel theo định dạng Plan
 *
 * Tạo báo cáo số ca dự kiến theo tuần cho SPW
 * Phân loại theo: class_size (1v1, 1v2, 1v3, 1v8) và teacher_nationality (Vietnamese, Philippines, Native 1, Native 2)
 *
 * Phase 60: Initial implementation
 * Phase 61: Updated to use month range, include unscheduled sessions, apply correct colors from Plan.xlsx
 */
class WeeklyPlanService
{
    protected Carbon $startMonth;
    protected Carbon $endMonth;

    // SpeakWell subject IDs - copied from DashboardService
    public const SPEAKWELL_SUBJECT_IDS = [
        533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
        390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
        584, 582, 404, 403, 583, 471
    ];

    // Class size definitions
    public const CLASS_SIZES = ['1v1', '1v2', '1v3', '1v8'];
    
    // Teacher nationality categories
    public const TEACHER_NATIONALITIES = ['Vietnamese', 'Philippines', 'Native 1', 'Native 2'];

    // Excel colors from Plan.xlsx (RGB without FF prefix)
    public const COLOR_SPW_HEADER = 'E69138';       // Orange - SPW row
    public const COLOR_TOTAL_ROW = 'F6B26B';        // Light orange - Tổng ca dự kiến row
    public const COLOR_CLASS_SIZE = 'FCE5CD';       // Peach - Class size rows (1v1, 1v2, etc.)
    public const COLOR_WHITE = 'FFFFFF';            // White - Nationality detail rows

    public function __construct(?Carbon $startMonth = null, ?Carbon $endMonth = null)
    {
        $this->startMonth = $startMonth ?? Carbon::now()->startOfMonth();
        $this->endMonth = $endMonth ?? Carbon::now()->endOfMonth();
    }

    /**
     * Generate weekly plan report data
     * Returns data structured for Excel export matching Plan.xlsx format
     */
    public function generatePlanReport(): array
    {
        $weeks = $this->getWeeksInRange();
        $weeklyData = [];

        foreach ($weeks as $weekIndex => $week) {
            $weeklyData[$weekIndex] = $this->getWeekSessionStats($week['start'], $week['end']);
        }

        return [
            'weeks' => $weeks,
            'data' => $weeklyData,
            'period' => [
                'start' => $this->startMonth->format('m/Y'),
                'end' => $this->endMonth->format('m/Y'),
            ],
        ];
    }

    /**
     * Get all weeks in the selected month range
     * A week belongs to the month that contains its Monday
     * Phase 71: Use ISO-8601 week numbering (WEEK mode 3)
     *   - Week starts Monday, ends Sunday
     *   - Week 1 of 2026 = 01/01/2026 (Thu) to 04/01/2026 (Sun) - only 4 days
     *   - Week 2 of 2026 = 05/01/2026 (Mon) to 11/01/2026 (Sun) - full week
     */
    protected function getWeeksInRange(): array
    {
        $weeks = [];
        $current = $this->startMonth->copy()->startOfMonth();
        $end = $this->endMonth->copy()->endOfMonth();

        while ($current->lte($end)) {
            // Get the first day of this month
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            // Get week number within this month
            $weekInMonth = 1;
            $weekStart = $monthStart->copy();
            
            // Adjust to Monday if not already Monday
            if ($weekStart->dayOfWeek !== Carbon::MONDAY) {
                // Find the Monday of this week (might be in previous month)
                $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
            }

            while ($weekStart->lte($monthEnd)) {
                $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
                
                // Cap weekEnd at month end for display purposes
                $displayEnd = $weekEnd->gt($monthEnd) ? $monthEnd->copy() : $weekEnd->copy();
                $displayStart = $weekStart->lt($monthStart) ? $monthStart->copy() : $weekStart->copy();

                // Only include weeks that have at least one day in the current month
                if ($displayStart->month === $current->month || $displayEnd->month === $current->month) {
                    // Phase 71: Use ISO week number (mode 3) - same as MySQL WEEK(date, 3)
                    $weekOfYear = (int) $displayStart->format('W');
                    
                    $weeks[] = [
                        'week_number' => $weekInMonth,
                        'week_of_year' => $weekOfYear,
                        'month' => $current->format('m/Y'),
                        'month_name' => $current->format('F Y'),
                        'start' => $displayStart,
                        'end' => $displayEnd,
                        'label' => "Tuần {$weekOfYear}",
                    ];
                    $weekInMonth++;
                }

                $weekStart = $weekStart->addWeek();
            }

            // Move to next month
            $current = $current->addMonth()->startOfMonth();
        }

        return $weeks;
    }

    /**
     * Get session statistics for a specific week
     * Returns count of active sessions grouped by class_size and teacher_nationality
     * Phase 61: Include both Scheduled (status=2) and Unscheduled (status=1) sessions
     * "Tổng ca dự kiến đang chạy" = Scheduled + Unscheduled (HV còn buổi chưa lên lịch)
     */
    protected function getWeekSessionStats(Carbon $start, Carbon $end): array
    {
        // Convert to UTC for database query (VN = UTC+7)
        $startUtc = $start->copy()->startOfDay()->subHours(7);
        $endUtc = $end->copy()->endOfDay()->subHours(7);

        // Query to get SCHEDULED sessions (status=2) with class size and teacher nationality
        // These are sessions that have been scheduled within the week
        $scheduledSessions = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'ol.ordles_teacher_id')
            ->leftJoin('tbl_countries as c', 'c.country_id', '=', 'teacher.user_country_id')
            ->leftJoin(DB::raw('(
                SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                FROM tbl_group_classes 
                GROUP BY grpcls_tlang_id, grpcls_teacher_id
            ) as gc'), function ($join) {
                $join->on('gc.grpcls_tlang_id', '=', 'ol.ordles_tlang_id')
                     ->on('gc.grpcls_teacher_id', '=', 'ol.ordles_teacher_id');
            })
            ->whereIn('ol.ordles_tlang_id', self::TEACHER_COUNTRY_SUBJECT_IDS)
            ->whereIn('ol.ordles_status', [2, 3, 4]) // Scheduled, Completed, Cancelled
            ->whereBetween('ol.ordles_lesson_starttime', [$startUtc, $endUtc])
            ->where('o.order_status', 2)
            ->where('o.order_payment_status', 1)
            ->selectRaw("
                CASE 
                    WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN '1v1'
                    WHEN gc.grpcls_total_seats = 2 THEN '1v2'
                    WHEN gc.grpcls_total_seats = 3 THEN '1v3'
                    WHEN gc.grpcls_total_seats BETWEEN 4 AND 10 THEN '1v8'
                    ELSE '1v8'
                END as class_size,
                CASE 
                    WHEN c.country_code = 'VN' OR c.country_code = 'vn' THEN 'Vietnamese'
                    WHEN c.country_code = 'PH' OR c.country_code = 'ph' THEN 'Philippines'
                    WHEN c.country_code = 'ZA' OR c.country_code = 'za' THEN 'Native 1'
                    WHEN c.country_code = 'GB' OR c.country_code = 'gb' THEN 'Native 2'
                    ELSE 'Native 2'
                END as teacher_nationality,
                COUNT(*) as session_count
            ")
            ->groupBy('class_size', 'teacher_nationality')
            ->get();

        // Query to get UNSCHEDULED sessions (status=1) - HV còn buổi chưa lên lịch
        // These don't have ordles_lesson_starttime set yet, so we estimate based on order status
        // For unscheduled lessons, we count them for the current period if the order is active
        $unscheduledSessions = DB::connection('mysql')
            ->table('tbl_order_lessons as ol')
            ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'ol.ordles_teacher_id')
            ->leftJoin('tbl_countries as c', 'c.country_id', '=', 'teacher.user_country_id')
            ->leftJoin(DB::raw('(
                SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                FROM tbl_group_classes 
                GROUP BY grpcls_tlang_id, grpcls_teacher_id
            ) as gc'), function ($join) {
                $join->on('gc.grpcls_tlang_id', '=', 'ol.ordles_tlang_id')
                     ->on('gc.grpcls_teacher_id', '=', 'ol.ordles_teacher_id');
            })
            ->whereIn('ol.ordles_tlang_id', self::TEACHER_COUNTRY_SUBJECT_IDS)
            ->where('ol.ordles_status', 1) // Unscheduled
            ->where('o.order_status', 2)
            ->where('o.order_payment_status', 1)
            ->selectRaw("
                CASE 
                    WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN '1v1'
                    WHEN gc.grpcls_total_seats = 2 THEN '1v2'
                    WHEN gc.grpcls_total_seats = 3 THEN '1v3'
                    WHEN gc.grpcls_total_seats BETWEEN 4 AND 10 THEN '1v8'
                    ELSE '1v8'
                END as class_size,
                CASE 
                    WHEN c.country_code = 'VN' OR c.country_code = 'vn' THEN 'Vietnamese'
                    WHEN c.country_code = 'PH' OR c.country_code = 'ph' THEN 'Philippines'
                    WHEN c.country_code = 'ZA' OR c.country_code = 'za' THEN 'Native 1'
                    WHEN c.country_code = 'GB' OR c.country_code = 'gb' THEN 'Native 2'
                    ELSE 'Native 2'
                END as teacher_nationality,
                COUNT(*) as session_count
            ")
            ->groupBy('class_size', 'teacher_nationality')
            ->get();

        // Initialize result structure
        $result = [
            'total' => 0,
            'scheduled' => 0,
            'unscheduled' => 0,
            'by_class_size' => [],
        ];

        foreach (self::CLASS_SIZES as $classSize) {
            $result['by_class_size'][$classSize] = [
                'total' => 0,
                'scheduled' => 0,
                'unscheduled' => 0,
                'by_nationality' => [],
            ];
            foreach (self::TEACHER_NATIONALITIES as $nationality) {
                $result['by_class_size'][$classSize]['by_nationality'][$nationality] = 0;
            }
        }

        // Fill in scheduled sessions
        foreach ($scheduledSessions as $row) {
            $classSize = $row->class_size;
            $nationality = $row->teacher_nationality;
            $count = (int) $row->session_count;

            if (isset($result['by_class_size'][$classSize])) {
                $result['by_class_size'][$classSize]['by_nationality'][$nationality] += $count;
                $result['by_class_size'][$classSize]['total'] += $count;
                $result['by_class_size'][$classSize]['scheduled'] += $count;
                $result['total'] += $count;
                $result['scheduled'] += $count;
            }
        }

        // Fill in unscheduled sessions
        // For unscheduled, we distribute evenly across weeks in the period
        // Since they don't have a specific date, we count them once for the first week only
        // to avoid double counting across multiple weeks
        if ($start->eq($this->startMonth->copy()->startOfMonth())) {
            foreach ($unscheduledSessions as $row) {
                $classSize = $row->class_size;
                $nationality = $row->teacher_nationality;
                $count = (int) $row->session_count;

                if (isset($result['by_class_size'][$classSize])) {
                    $result['by_class_size'][$classSize]['by_nationality'][$nationality] += $count;
                    $result['by_class_size'][$classSize]['total'] += $count;
                    $result['by_class_size'][$classSize]['unscheduled'] += $count;
                    $result['total'] += $count;
                    $result['unscheduled'] += $count;
                }
            }
        }

        return $result;
    }

    /**
     * Generate Excel file for weekly plan
     * Returns filename of generated file
     * Phase 66: Added Teacher Country sheet with weekly + unscheduled data
     * Phase 79: Use getTeacherCountryWeeklyWithClassSize() for Scheduled sheet to match UI
     * Phase 224: Added optional from_date/to_date for day-level date range filtering
     */
    public function generateExcel(?string $fromDate = null, ?string $toDate = null): string
    {
        // Phase 79/224: Use same data source as UI table "🌍 Tổng số ca Scheduled"
        $scheduledData = self::getTeacherCountryWeeklyWithClassSize($fromDate, $toDate);
        
        $exportId = 'plan_' . uniqid() . '_' . time();
        $filename = "ke-hoach-ca-hoc_{$exportId}.xlsx";
        $filepath = "exports/{$filename}";

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Scheduled');

        // Phase 79: Build the sheet using same data as UI
        $this->buildScheduledSheetFromApi($sheet, $scheduledData);

        // Phase 66: Add Teacher Country sheet with weekly scheduled + unscheduled data
        $teacherCountrySheet = $spreadsheet->createSheet();
        $teacherCountrySheet->setTitle('Unscheduled');
        $this->buildTeacherCountrySheet($teacherCountrySheet);

        // Set Plan sheet as active (first sheet) so it opens by default
        $spreadsheet->setActiveSheetIndex(0);

        // Save to temp file then move to storage
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        \Illuminate\Support\Facades\Storage::disk('local')->put($filepath, file_get_contents($tempFile));
        unlink($tempFile);

        return $filename;
    }

    /**
     * Phase 79: Build Scheduled sheet using same data as UI table
     * Uses getTeacherCountryWeeklyWithClassSize() data format
     */
    protected function buildScheduledSheetFromApi(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $data): void
    {
        $weeks = $data['weeks'];
        $classSizes = $data['class_sizes'];
        $nationalities = $data['nationalities'];

        if (empty($weeks)) {
            $sheet->setCellValue('A1', 'Không có dữ liệu');
            return;
        }

        // Header row 1: "Tuần" label and week labels
        $sheet->setCellValue('A1', 'Tuần');
        $col = 4; // Start from column D
        foreach ($weeks as $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '1', 'Tuần ' . $week['week_of_year']);
            $col++;
        }
        $sheet->setCellValue($this->getColumnLetter($col) . '1', 'SUM');

        // Row 2: "Từ ngày" - week start dates
        $sheet->setCellValue('A2', 'Từ ngày');
        $col = 4;
        foreach ($weeks as $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '2', $week['week_start']);
            $col++;
        }

        // Row 3: "Đến ngày" - week end dates
        $sheet->setCellValue('A3', 'Đến ngày');
        $col = 4;
        foreach ($weeks as $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '3', $week['week_end']);
            $col++;
        }

        // Row 4: "SPW" header
        $sheet->setCellValue('A4', 'SPW');
        
        // Row 5: "Tổng ca dự kiến đang chạy"
        $sheet->setCellValue('B5', 'Tổng ca dự kiến đang chạy');
        $col = 4;
        $grandTotal = 0;
        foreach ($weeks as $week) {
            $total = $week['total'] ?? 0;
            $sheet->setCellValue($this->getColumnLetter($col) . '5', $total);
            $grandTotal += $total;
            $col++;
        }
        $sheet->setCellValue($this->getColumnLetter($col) . '5', $grandTotal);

        // Build class size sections starting from row 6
        $currentRow = 6;
        foreach ($classSizes as $classSize) {
            // Class size header row with Sum column
            $sheet->setCellValue('B' . $currentRow, $classSize);
            $sheet->setCellValue('C' . $currentRow, 'Sum');
            
            // Calculate sums for this class size
            $col = 4;
            $classSizeTotal = 0;
            foreach ($weeks as $week) {
                $count = $week['by_class_size'][$classSize]['total'] ?? 0;
                $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $count);
                $classSizeTotal += $count;
                $col++;
            }
            $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $classSizeTotal);
            $currentRow++;

            // Nationality breakdown rows
            foreach ($nationalities as $nationality) {
                $sheet->setCellValue('C' . $currentRow, $nationality);
                
                $col = 4;
                $nationalityTotal = 0;
                foreach ($weeks as $week) {
                    $count = $week['by_class_size'][$classSize]['by_nationality'][$nationality] ?? 0;
                    $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $count ?: '-');
                    $nationalityTotal += $count;
                    $col++;
                }
                $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $nationalityTotal ?: '-');
                $currentRow++;
            }
        }

        // Apply styles
        $this->applyStyles($sheet, count($weeks), $currentRow - 1);
    }

    /**
     * Build the Plan sheet with proper format
     */
    protected function buildPlanSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $report): void
    {
        $weeks = $report['weeks'];
        $data = $report['data'];

        // Header row 1: "Tuần" label and week labels
        $sheet->setCellValue('A1', 'Tuần');
        $col = 4; // Start from column D
        foreach ($weeks as $index => $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '1', $week['label']);
            $col++;
        }
        $sheet->setCellValue($this->getColumnLetter($col) . '1', 'SUM');

        // Row 2: "Từ ngày" - week start dates
        $sheet->setCellValue('A2', 'Từ ngày');
        $col = 4;
        foreach ($weeks as $index => $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '2', $week['start']->format('d/m/Y'));
            $col++;
        }

        // Row 3: "Đến ngày" - week end dates
        $sheet->setCellValue('A3', 'Đến ngày');
        $col = 4;
        foreach ($weeks as $index => $week) {
            $sheet->setCellValue($this->getColumnLetter($col) . '3', $week['end']->format('d/m/Y'));
            $col++;
        }

        // Row 4: "SPW" header
        $sheet->setCellValue('A4', 'SPW');
        
        // Row 5: "Tổng ca dự kiến đang chạy"
        $sheet->setCellValue('B5', 'Tổng ca dự kiến đang chạy');
        $col = 4;
        $grandTotal = 0;
        foreach ($weeks as $index => $week) {
            $total = $data[$index]['total'] ?? 0;
            $sheet->setCellValue($this->getColumnLetter($col) . '5', $total);
            $grandTotal += $total;
            $col++;
        }
        $sheet->setCellValue($this->getColumnLetter($col) . '5', $grandTotal);

        // Build class size sections starting from row 6
        $currentRow = 6;
        foreach (self::CLASS_SIZES as $classSize) {
            // Class size header row with Sum column
            $sheet->setCellValue('B' . $currentRow, $classSize);
            $sheet->setCellValue('C' . $currentRow, 'Sum');
            
            // Calculate sums for this class size
            $col = 4;
            $classSizeTotal = 0;
            foreach ($weeks as $index => $week) {
                $count = $data[$index]['by_class_size'][$classSize]['total'] ?? 0;
                $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $count);
                $classSizeTotal += $count;
                $col++;
            }
            $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $classSizeTotal);
            $currentRow++;

            // Nationality breakdown rows
            foreach (self::TEACHER_NATIONALITIES as $nationality) {
                $sheet->setCellValue('C' . $currentRow, $nationality);
                
                $col = 4;
                $nationalityTotal = 0;
                foreach ($weeks as $index => $week) {
                    $count = $data[$index]['by_class_size'][$classSize]['by_nationality'][$nationality] ?? 0;
                    $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $count);
                    $nationalityTotal += $count;
                    $col++;
                }
                $sheet->setCellValue($this->getColumnLetter($col) . $currentRow, $nationalityTotal);
                $currentRow++;
            }
        }

        // Apply styles
        $this->applyStyles($sheet, count($weeks), $currentRow - 1);
    }

    /**
     * Apply styles to the sheet
     * Phase 61: Updated to use colors matching Plan.xlsx
     */
    protected function applyStyles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $weekCount, int $lastRow): void
    {
        $lastCol = $this->getColumnLetter(4 + $weekCount);

        // Style header rows (row 1-3: Tuần, Từ ngày, Đến ngày)
        $sheet->getStyle("A1:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Style the SPW header row (row 4) - Orange: E69138
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SPW_HEADER],
            ],
        ]);

        // Style "Tổng ca dự kiến đang chạy" row (row 5) - Light orange: F6B26B
        $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_TOTAL_ROW],
            ],
        ]);

        // Style class size rows (1v1, 1v2, 1v3, 1v8 Sum rows) - Peach: FCE5CD
        // These are at rows 6, 11, 16, 21 (every 5 rows starting from 6)
        $classSizeRows = [6, 11, 16, 21];
        foreach ($classSizeRows as $row) {
            if ($row <= $lastRow) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_CLASS_SIZE],
                    ],
                ]);
            }
        }

        // Apply borders
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $lastCol) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header rows
        $sheet->freezePane('D4');
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
     * Phase 66/68/74: Build Teacher Country sheet with unscheduled data only
     * Phase 74: Removed weekly section, keep only unscheduled data
     */
    protected function buildTeacherCountrySheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        // Get data
        $unscheduledData = self::getTeacherCountryUnscheduledSummary();

        // Get all unique countries from unscheduled data
        $countries = array_column($unscheduledData['data'], 'country_name');
        sort($countries);

        // Build unscheduled lookup (Phase 226: with class type breakdown)
        $unscheduledByCountry = [];
        $oneOnOneByCountry = [];
        $oneOnTwoByCountry = [];
        foreach ($unscheduledData['data'] as $row) {
            $unscheduledByCountry[$row['country_name']] = $row['unscheduled_count'];
            $oneOnOneByCountry[$row['country_name']] = $row['one_on_one'] ?? 0;
            $oneOnTwoByCountry[$row['country_name']] = $row['one_on_two'] ?? 0;
        }

        $currentRow = 1;

        // Section: 📅 Tổng số ca Unscheduled theo Quốc gia GV
        // Use same structure as weekly section (countries as rows)
        $sheet->setCellValue('A' . $currentRow, '📅 Tổng số ca Unscheduled theo Quốc gia GV');
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SPW_HEADER],
            ],
        ]);
        $currentRow += 2;

        // Phase 226: Header row: Quốc gia | 1:1 | 1:2 | TỔNG
        $unscheduledHeaderRow = $currentRow;
        $sheet->setCellValue('A' . $currentRow, 'Quốc gia');
        $sheet->setCellValue('B' . $currentRow, 'Lớp 1:1');
        $sheet->setCellValue('C' . $currentRow, 'Lớp 1:2');
        $sheet->setCellValue('D' . $currentRow, 'TỔNG');
        $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_CLASS_SIZE],
            ],
        ]);
        $currentRow++;

        // Total row first
        $sheet->setCellValue('A' . $currentRow, '📊 TỔNG CỘNG');
        $sheet->setCellValue('B' . $currentRow, $unscheduledData['one_on_one_total'] ?? 0);
        $sheet->setCellValue('C' . $currentRow, $unscheduledData['one_on_two_total'] ?? 0);
        $sheet->setCellValue('D' . $currentRow, $unscheduledData['total']);
        $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_TOTAL_ROW],
            ],
        ]);
        $currentRow++;

        // Country rows
        foreach ($countries as $country) {
            $count = $unscheduledByCountry[$country] ?? 0;
            $oneOnOne = $oneOnOneByCountry[$country] ?? 0;
            $oneOnTwo = $oneOnTwoByCountry[$country] ?? 0;
            $sheet->setCellValue('A' . $currentRow, $country);
            $sheet->setCellValue('B' . $currentRow, $oneOnOne ?: '-');
            $sheet->setCellValue('C' . $currentRow, $oneOnTwo ?: '-');
            $sheet->setCellValue('D' . $currentRow, $count ?: '-');
            $currentRow++;
        }

        $unscheduledEndRow = $currentRow - 1;

        // Apply borders to unscheduled section
        $sheet->getStyle("A{$unscheduledHeaderRow}:D{$unscheduledEndRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'Z') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    // Phase 66: Specific SpeakWell subject IDs for teacher country queries
    public const TEACHER_COUNTRY_SUBJECT_IDS = [
        558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413,
        571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412,
        577, 586, 585, 584, 582, 404, 403, 583, 471
    ];

    /**
     * Phase 62/66/69/71: Get teacher country weekly summary
     * Returns session count by teacher country for each week (scheduled sessions)
     * Phase 66: Added conditions for paid orders (order_status=2, order_payment_status=1)
     * Phase 69: Updated query to use ordles_status IN (2,3,4) and dynamic year range
     * Phase 71: Use ISO-8601 week numbering (MySQL WEEK mode 3)
     *   - Week 1 of 2026 = 01/01/2026 (Thu) to 04/01/2026 (Sun) - only 4 days
     *   - Week 2 of 2026 = 05/01/2026 (Mon) to 11/01/2026 (Sun) - full week
     */
    public static function getTeacherCountryWeeklySummary(): array
    {
        // Query sessions grouped by teacher country and week
        // Phase 71: Use WEEK mode 3 (ISO-8601) - week starts Monday, first week contains Jan 4
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        
        $results = DB::connection('mysql')
            ->select("
                SELECT
                    base.teacher_country_id,
                    base.teacher_country_name,
                    YEARWEEK(base.starttime_utc7, 3)   AS year_week,
                    YEAR(base.starttime_utc7)           AS year,
                    WEEK(base.starttime_utc7, 3)       AS week_of_year,
                    DATE_FORMAT(
                        MIN(DATE(base.starttime_utc7)) - INTERVAL WEEKDAY(MIN(DATE(base.starttime_utc7))) DAY,
                        '%d/%m/%Y'
                    ) AS week_start_ddmmYYYY,
                    DATE_FORMAT(
                        MIN(DATE(base.starttime_utc7)) - INTERVAL WEEKDAY(MIN(DATE(base.starttime_utc7))) DAY + INTERVAL 6 DAY,
                        '%d/%m/%Y'
                    ) AS week_end_ddmmYYYY,
                    COUNT(*) AS lesson_count
                FROM (
                    SELECT
                        teacher.user_country_id     AS teacher_country_id,
                        IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
                        CONVERT_TZ(ordles.ordles_lesson_starttime, '+00:00', '+07:00') AS starttime_utc7
                    FROM tbl_order_lessons ordles
                    INNER JOIN tbl_orders o ON o.order_id = ordles.ordles_order_id 
                    INNER JOIN tbl_users teacher
                        ON teacher.user_id = ordles.ordles_teacher_id
                    LEFT JOIN tbl_countries c
                        ON c.country_id = teacher.user_country_id
                    LEFT JOIN tbl_countries_lang cl
                        ON cl.countrylang_country_id = c.country_id
                        AND cl.countrylang_lang_id = 1
                    WHERE ordles.ordles_status IN (2,3,4)
                      AND ordles.ordles_lesson_starttime >= '{$currentYear}-01-01'
                      AND ordles.ordles_lesson_starttime < '{$nextYear}-01-01'
                      AND ordles.ordles_tlang_id IN (" . implode(',', self::TEACHER_COUNTRY_SUBJECT_IDS) . ")
                      AND o.order_status = 2 
                      AND o.order_payment_status = 1
                ) AS base
                GROUP BY
                    base.teacher_country_id,
                    base.teacher_country_name,
                    YEARWEEK(base.starttime_utc7, 3),
                    YEAR(base.starttime_utc7),
                    WEEK(base.starttime_utc7, 3)
                ORDER BY year_week, base.teacher_country_name
            ");

        // Group results by week for display
        // Phase 71: Use year_week as key (YEARWEEK with mode 3)
        $weeklyData = [];
        $countries = [];
        
        foreach ($results as $row) {
            $weekKey = $row->year_week;
            
            if (!isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] = [
                    'week_of_year' => $row->week_of_year,
                    'week_start' => $row->week_start_ddmmYYYY,
                    'week_end' => $row->week_end_ddmmYYYY,
                    'total' => 0,
                    'by_country' => [],
                ];
            }
            
            $countryName = $row->teacher_country_name ?: 'Unknown';
            $weeklyData[$weekKey]['by_country'][$countryName] = (int) $row->lesson_count;
            $weeklyData[$weekKey]['total'] += (int) $row->lesson_count;
            
            // Track all unique countries
            if (!in_array($countryName, $countries)) {
                $countries[] = $countryName;
            }
        }

        // Sort by year_week and countries for consistent display
        ksort($weeklyData);
        sort($countries);

        return [
            'weeks' => array_values($weeklyData),
            'countries' => $countries,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Phase 74: Get teacher country weekly summary with class size breakdown
     * Returns session count by week -> class_size -> nationality (matching Plan.xlsx structure)
     * Phase 224: Added optional from_date/to_date filtering (YYYY-MM-DD)
     */
    public static function getTeacherCountryWeeklyWithClassSize(?string $fromDate = null, ?string $toDate = null): array
    {
        // Phase 224: Use custom date range if provided, otherwise default to current year
        // Validate date format (YYYY-MM-DD) to prevent SQL injection
        if ($fromDate && $toDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $startCondition = "'{$fromDate} 00:00:00'";
            $endCondition = "'{$toDate} 23:59:59'";
        } else {
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $startCondition = "'{$currentYear}-01-01'";
            $endCondition = "'{$nextYear}-01-01'";
        }

        // Phase 224: Build date filter using UTC conversion for day-level filtering
        $isCustomRange = $fromDate && $toDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate);
        $dateFilter = $isCustomRange
            ? "AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') >= {$startCondition}
               AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') <= {$endCondition}"
            : "AND ol.ordles_lesson_starttime >= {$startCondition}
               AND ol.ordles_lesson_starttime < {$endCondition}";
        
        $results = DB::connection('mysql')
            ->select("
                SELECT
                    YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS year_week,
                    WEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS week_of_year,
                    DATE_FORMAT(
                        DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) - 
                        INTERVAL WEEKDAY(DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'))) DAY,
                        '%d/%m/%Y'
                    ) AS week_start,
                    DATE_FORMAT(
                        DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) - 
                        INTERVAL WEEKDAY(DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'))) DAY + INTERVAL 6 DAY,
                        '%d/%m/%Y'
                    ) AS week_end,
                    CASE 
                        WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN '1v1'
                        WHEN gc.grpcls_total_seats = 2 THEN '1v2'
                        WHEN gc.grpcls_total_seats = 3 THEN '1v3'
                        WHEN gc.grpcls_total_seats BETWEEN 4 AND 10 THEN '1v8'
                        ELSE '1v8'
                    END AS class_size,
                    CASE 
                        WHEN c.country_code = 'VN' OR c.country_code = 'vn' THEN 'Vietnamese'
                        WHEN c.country_code = 'PH' OR c.country_code = 'ph' THEN 'Philippines'
                        WHEN c.country_code = 'ZA' OR c.country_code = 'za' THEN 'Native 1'
                        WHEN c.country_code = 'GB' OR c.country_code = 'gb' THEN 'Native 2'
                        ELSE IFNULL(cl.country_name, c.country_identifier)
                    END AS teacher_nationality,
                    COUNT(*) AS lesson_count
                FROM tbl_order_lessons ol
                INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
                INNER JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id
                LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id
                LEFT JOIN tbl_countries_lang cl ON cl.countrylang_country_id = c.country_id AND cl.countrylang_lang_id = 1
                LEFT JOIN (
                    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                    FROM tbl_group_classes 
                    GROUP BY grpcls_tlang_id, grpcls_teacher_id
                ) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
                WHERE ol.ordles_status IN (2,3,4)
                  {$dateFilter}
                  AND ol.ordles_tlang_id IN (" . implode(',', self::TEACHER_COUNTRY_SUBJECT_IDS) . ")
                  AND o.order_status = 2 
                  AND o.order_payment_status = 1
                GROUP BY year_week, week_of_year, week_start, week_end, class_size, teacher_nationality
                ORDER BY year_week, class_size, teacher_nationality
            ");

        // Group results by week -> class_size -> nationality
        $weeklyData = [];
        $classSizes = self::CLASS_SIZES;
        $allNationalities = [];
        
        foreach ($results as $row) {
            $weekKey = $row->year_week;
            
            if (!isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] = [
                    'week_of_year' => $row->week_of_year,
                    'week_start' => $row->week_start,
                    'week_end' => $row->week_end,
                    'total' => 0,
                    'by_class_size' => [],
                ];
                // Initialize all class sizes
                foreach ($classSizes as $cs) {
                    $weeklyData[$weekKey]['by_class_size'][$cs] = [
                        'total' => 0,
                        'by_nationality' => [],
                    ];
                }
            }
            
            $classSize = $row->class_size;
            $nationality = $row->teacher_nationality ?: 'Unknown';
            $count = (int) $row->lesson_count;
            
            $weeklyData[$weekKey]['total'] += $count;
            if (isset($weeklyData[$weekKey]['by_class_size'][$classSize])) {
                $weeklyData[$weekKey]['by_class_size'][$classSize]['total'] += $count;
                $weeklyData[$weekKey]['by_class_size'][$classSize]['by_nationality'][$nationality] = 
                    ($weeklyData[$weekKey]['by_class_size'][$classSize]['by_nationality'][$nationality] ?? 0) + $count;
            }
            
            if (!in_array($nationality, $allNationalities)) {
                $allNationalities[] = $nationality;
            }
        }

        ksort($weeklyData);
        sort($allNationalities);

        // Phase 77: Add is_current flag to highlight current week
        $currentWeekOfYear = (int) Carbon::now()->format('W');
        $currentYearWeek = Carbon::now()->format('oW'); // ISO year + week
        
        $weeksArray = array_values($weeklyData);
        foreach ($weeksArray as &$week) {
            // Check if this week is the current week based on ISO year-week
            $week['is_current'] = ($week['week_of_year'] == $currentWeekOfYear);
        }
        unset($week);

        return [
            'weeks' => $weeksArray,
            'class_sizes' => $classSizes,
            'nationalities' => $allNationalities,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Phase 66: Get teacher country unscheduled summary
     * Returns unscheduled session count by teacher country
     * Conditions: status=1 (unscheduled), paid orders, speakwell subjects
     * Phase 226: Added class type breakdown (1:1 vs 1:2) using tbl_group_classes join
     */
    public static function getTeacherCountryUnscheduledSummary(): array
    {
        $subjectIds = implode(',', self::TEACHER_COUNTRY_SUBJECT_IDS);

        $results = DB::connection('mysql')
            ->select("
                SELECT 
                    teacher.user_country_id AS teacher_country_id,
                    IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
                    CASE 
                        WHEN gc.grpcls_total_seats = 2 THEN '1:2'
                        ELSE '1:1'
                    END AS class_type,
                    COUNT(ol.ordles_id) AS unscheduled_count
                FROM tbl_order_lessons ol 
                INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id 
                INNER JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id
                LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id
                LEFT JOIN tbl_countries_lang cl
                    ON cl.countrylang_country_id = c.country_id
                    AND cl.countrylang_lang_id = 1
                LEFT JOIN (
                    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                    FROM tbl_group_classes 
                    GROUP BY grpcls_tlang_id, grpcls_teacher_id
                ) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
                WHERE ol.ordles_status = 1 
                  AND ol.ordles_tlang_id IN ({$subjectIds})
                  AND o.order_status = 2 
                  AND o.order_payment_status = 1
                GROUP BY teacher.user_country_id, teacher_country_name, class_type
                ORDER BY teacher_country_name, class_type
            ");

        $data = [];
        $total = 0;
        $oneOnOneTotal = 0;
        $oneOnTwoTotal = 0;
        $countryMap = []; // country_name => [country_id, unscheduled_count, one_on_one, one_on_two]

        foreach ($results as $row) {
            $countryName = $row->teacher_country_name ?: 'Unknown';
            $count = (int) $row->unscheduled_count;
            $classType = $row->class_type;

            if (!isset($countryMap[$countryName])) {
                $countryMap[$countryName] = [
                    'country_id' => $row->teacher_country_id,
                    'country_name' => $countryName,
                    'unscheduled_count' => 0,
                    'one_on_one' => 0,
                    'one_on_two' => 0,
                ];
            }

            $countryMap[$countryName]['unscheduled_count'] += $count;
            if ($classType === '1:1') {
                $countryMap[$countryName]['one_on_one'] += $count;
                $oneOnOneTotal += $count;
            } else {
                $countryMap[$countryName]['one_on_two'] += $count;
                $oneOnTwoTotal += $count;
            }
            $total += $count;
        }

        $data = array_values($countryMap);

        return [
            'data' => $data,
            'total' => $total,
            'one_on_one_total' => $oneOnOneTotal,
            'one_on_two_total' => $oneOnTwoTotal,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Phase 80/81: Get weekly unscheduled breakdown
     * Calculates unscheduled sessions per week = (active_students × lessons_per_week) - scheduled
     * 
     * Active students: Students with paid orders AND lessons with status IN (1,2) (pending or scheduled, not completed)
     * Scheduled: Actual count from bảng "🌍 Tổng số ca Scheduled" using same query as getTeacherCountryWeeklyWithClassSize()
     * 
     * Phase 81: Fixed to use same WEEK mode 3 (ISO-8601) and UTC+7 conversion as Scheduled table
     * Phase 227: Added class type breakdown (1:1 vs 1:2) using tbl_group_classes join
     */
    public static function getWeeklyUnscheduledBreakdown(int $lessonsPerWeek = 2): array
    {
        $subjectIds = implode(',', self::TEACHER_COUNTRY_SUBJECT_IDS);
        
        // Get count of active students (have paid order with pending lessons)
        $activeStudentsResult = DB::connection('mysql')
            ->selectOne("
                SELECT COUNT(DISTINCT o.order_user_id) AS count
                FROM tbl_orders o
                INNER JOIN tbl_order_lessons ol ON o.order_id = ol.ordles_order_id
                WHERE o.order_status = 2 
                  AND o.order_payment_status = 1
                  AND ol.ordles_status IN (1, 2)
                  AND ol.ordles_tlang_id IN ({$subjectIds})
            ");
        
        $activeStudents = (int) ($activeStudentsResult->count ?? 0);
        
        // Phase 227: Get active students by class type (1:1 vs 1:2)
        $activeStudentsByClassType = DB::connection('mysql')
            ->select("
                SELECT 
                    CASE 
                        WHEN gc.grpcls_total_seats = 2 THEN '1:2'
                        ELSE '1:1'
                    END AS class_type,
                    COUNT(DISTINCT o.order_user_id) AS count
                FROM tbl_orders o
                INNER JOIN tbl_order_lessons ol ON o.order_id = ol.ordles_order_id
                LEFT JOIN (
                    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                    FROM tbl_group_classes 
                    GROUP BY grpcls_tlang_id, grpcls_teacher_id
                ) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
                WHERE o.order_status = 2 
                  AND o.order_payment_status = 1
                  AND ol.ordles_status IN (1, 2)
                  AND ol.ordles_tlang_id IN ({$subjectIds})
                GROUP BY class_type
            ");
        
        $activeStudentsOneOnOne = 0;
        $activeStudentsOneOnTwo = 0;
        foreach ($activeStudentsByClassType as $row) {
            if ($row->class_type === '1:1') {
                $activeStudentsOneOnOne = (int) $row->count;
            } else {
                $activeStudentsOneOnTwo = (int) $row->count;
            }
        }
        
        // Get scheduled sessions by week for next 8 weeks + previous 4 weeks
        // Phase 81: Use WEEK mode 3 (ISO-8601) and convert to UTC+7 - same as getTeacherCountryWeeklyWithClassSize()
        $now = Carbon::now();
        $startDate = $now->copy()->subWeeks(4)->startOfWeek(Carbon::MONDAY);
        $endDate = $now->copy()->addWeeks(8)->endOfWeek(Carbon::SUNDAY);
        
        // Phase 81: Use exact same query pattern as getTeacherCountryWeeklyWithClassSize() for consistency
        $scheduledByWeek = DB::connection('mysql')
            ->select("
                SELECT 
                    YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS year_week,
                    WEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS week_of_year,
                    DATE_FORMAT(
                        DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) - 
                        INTERVAL WEEKDAY(DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'))) DAY,
                        '%d/%m'
                    ) AS week_start,
                    DATE_FORMAT(
                        DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) - 
                        INTERVAL WEEKDAY(DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'))) DAY + INTERVAL 6 DAY,
                        '%d/%m'
                    ) AS week_end,
                    COUNT(*) AS scheduled_count
                FROM tbl_order_lessons ol
                INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
                WHERE ol.ordles_status IN (2, 3, 4)
                  AND ol.ordles_tlang_id IN ({$subjectIds})
                  AND o.order_status = 2
                  AND o.order_payment_status = 1
                  AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') BETWEEN ? AND ?
                GROUP BY year_week, week_of_year, week_start, week_end
                ORDER BY year_week
            ", [$startDate->format('Y-m-d 00:00:00'), $endDate->format('Y-m-d 23:59:59')]);
        
        // Phase 227: Get scheduled sessions by week AND class type
        $scheduledByWeekAndClassType = DB::connection('mysql')
            ->select("
                SELECT 
                    YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) AS year_week,
                    CASE 
                        WHEN gc.grpcls_total_seats = 2 THEN '1:2'
                        ELSE '1:1'
                    END AS class_type,
                    COUNT(*) AS scheduled_count
                FROM tbl_order_lessons ol
                INNER JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
                LEFT JOIN (
                    SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats 
                    FROM tbl_group_classes 
                    GROUP BY grpcls_tlang_id, grpcls_teacher_id
                ) gc ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
                WHERE ol.ordles_status IN (2, 3, 4)
                  AND ol.ordles_tlang_id IN ({$subjectIds})
                  AND o.order_status = 2
                  AND o.order_payment_status = 1
                  AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') BETWEEN ? AND ?
                GROUP BY year_week, class_type
                ORDER BY year_week
            ", [$startDate->format('Y-m-d 00:00:00'), $endDate->format('Y-m-d 23:59:59')]);
        
        // Build week range with all weeks (even if no data)
        $weeks = [];
        $currentWeekYearWeek = (int) $now->format('oW'); // ISO year-week
        
        // Create lookup for scheduled data
        $scheduledLookup = [];
        foreach ($scheduledByWeek as $row) {
            $scheduledLookup[$row->year_week] = $row;
        }
        
        // Phase 227: Create lookup for scheduled data by class type
        $scheduledByClassTypeLookup = []; // year_week => ['1:1' => count, '1:2' => count]
        foreach ($scheduledByWeekAndClassType as $row) {
            $yearWeek = $row->year_week;
            if (!isset($scheduledByClassTypeLookup[$yearWeek])) {
                $scheduledByClassTypeLookup[$yearWeek] = ['1:1' => 0, '1:2' => 0];
            }
            $scheduledByClassTypeLookup[$yearWeek][$row->class_type] = (int) $row->scheduled_count;
        }
        
        // Generate all weeks in range
        $currentDate = $startDate->copy();
        $totalExpected = 0;
        $totalScheduled = 0;
        $totalUnscheduled = 0;
        // Phase 227: Totals by class type
        $totalOneOnOneExpected = 0;
        $totalOneOnOneScheduled = 0;
        $totalOneOnOneUnscheduled = 0;
        $totalOneOnTwoExpected = 0;
        $totalOneOnTwoScheduled = 0;
        $totalOneOnTwoUnscheduled = 0;
        
        while ($currentDate <= $endDate) {
            $yearWeek = (int) $currentDate->format('oW'); // ISO year-week for lookup
            $weekOfYear = (int) $currentDate->format('W');
            $weekStart = $currentDate->copy()->startOfWeek(Carbon::MONDAY)->format('d/m');
            $weekEnd = $currentDate->copy()->endOfWeek(Carbon::SUNDAY)->format('d/m');
            
            // Get scheduled from lookup, fallback to 0
            $scheduled = isset($scheduledLookup[$yearWeek]) ? (int) $scheduledLookup[$yearWeek]->scheduled_count : 0;
            $expected = $activeStudents * $lessonsPerWeek;
            $unscheduled = $expected - $scheduled; // Can be negative if more scheduled than expected
            
            // Phase 227: Get scheduled by class type
            $scheduledOneOnOne = $scheduledByClassTypeLookup[$yearWeek]['1:1'] ?? 0;
            $scheduledOneOnTwo = $scheduledByClassTypeLookup[$yearWeek]['1:2'] ?? 0;
            $expectedOneOnOne = $activeStudentsOneOnOne * $lessonsPerWeek;
            $expectedOneOnTwo = $activeStudentsOneOnTwo * $lessonsPerWeek;
            $unscheduledOneOnOne = $expectedOneOnOne - $scheduledOneOnOne;
            $unscheduledOneOnTwo = $expectedOneOnTwo - $scheduledOneOnTwo;
            
            $weeks[] = [
                'week_key' => $yearWeek,
                'week_of_year' => $weekOfYear,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'is_current' => ($yearWeek == $currentWeekYearWeek),
                'active_students' => $activeStudents,
                'expected' => $expected,
                'scheduled' => $scheduled,
                'unscheduled' => $unscheduled,
                // Phase 227: Class type breakdown
                'one_on_one_active_students' => $activeStudentsOneOnOne,
                'one_on_one_expected' => $expectedOneOnOne,
                'one_on_one_scheduled' => $scheduledOneOnOne,
                'one_on_one_unscheduled' => $unscheduledOneOnOne,
                'one_on_two_active_students' => $activeStudentsOneOnTwo,
                'one_on_two_expected' => $expectedOneOnTwo,
                'one_on_two_scheduled' => $scheduledOneOnTwo,
                'one_on_two_unscheduled' => $unscheduledOneOnTwo,
            ];
            
            $totalExpected += $expected;
            $totalScheduled += $scheduled;
            $totalUnscheduled += $unscheduled;
            // Phase 227: Accumulate class type totals
            $totalOneOnOneExpected += $expectedOneOnOne;
            $totalOneOnOneScheduled += $scheduledOneOnOne;
            $totalOneOnOneUnscheduled += $unscheduledOneOnOne;
            $totalOneOnTwoExpected += $expectedOneOnTwo;
            $totalOneOnTwoScheduled += $scheduledOneOnTwo;
            $totalOneOnTwoUnscheduled += $unscheduledOneOnTwo;
            
            $currentDate->addWeek();
        }
        
        return [
            'weeks' => $weeks,
            'lessons_per_week' => $lessonsPerWeek,
            'total_active_students' => $activeStudents,
            'total_expected' => $totalExpected,
            'total_scheduled' => $totalScheduled,
            'total_unscheduled' => $totalUnscheduled,
            // Phase 227: Class type totals
            'one_on_one_active_students' => $activeStudentsOneOnOne,
            'one_on_one_total_expected' => $totalOneOnOneExpected,
            'one_on_one_total_scheduled' => $totalOneOnOneScheduled,
            'one_on_one_total_unscheduled' => $totalOneOnOneUnscheduled,
            'one_on_two_active_students' => $activeStudentsOneOnTwo,
            'one_on_two_total_expected' => $totalOneOnTwoExpected,
            'one_on_two_total_scheduled' => $totalOneOnTwoScheduled,
            'one_on_two_total_unscheduled' => $totalOneOnTwoUnscheduled,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
