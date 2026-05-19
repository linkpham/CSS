<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * UsageReportService - Báo cáo Sử dụng
 *
 * Tạo báo cáo theo cấu trúc file Excel "Báo cáo sử dụng" với 46 cột
 *
 * Phase 27 Fix: Get "Thông tin gói" from tbl_orders JOIN tbl_order_lessons
 * instead of tbl_order_subscription_plans, and add billing info from tbl_order_payments
 * 
 * Phase 33: Major performance optimization to fix 500 timeout errors:
 * - Pre-fetch all period data in batch queries instead of N+1 pattern
 * - Cache frequently accessed data within request lifecycle
 * 
 * Phase 38: Fix 524 timeout error caused by tbl_group_classes Cartesian product
 * - Changed direct LEFT JOIN to use subquery with GROUP BY to get one row per teacher/subject
 * - This prevents the query from exploding when many group classes match same teacher/subject
 * 
 * Phase 40: Further optimization to fix persistent 524 timeout errors:
 * - Removed tbl_group_classes JOIN entirely (not needed for export, causes massive slowdown)
 * - Simplified teacher nationality lookup with fallback to subject name parsing
 * - Class size is now derived from subject name instead of group_classes table
 * - This drastically reduces query complexity and execution time
 * 
 * Phase 95: Exclude cancelled lessons (ordles_status = 4) from usage report export
 * - Added WHERE ol.ordles_status != 4 to getOrderData() query
 * - This ensures total_sessions, total_amount, etc. do not include cancelled lessons
 * 
 * Phase 106: Usage report adjustments per accounting requirements:
 * - Reduced columns: keep A-V, used, cancelled (new), closing, zeus_order_id (new)
 * - Fix billing_id for import orders: parse order_extra_data for package_id
 * - Fix excess data: filter out orders with payment_date after period end
 * - Fix opening balance: if order was paid after period start, opening balance = 0
 *
 * Phase 107: Strict date range filtering for payment date:
 * - Changed filter from (<= endDate) to BETWEEN(startDate, endDate)
 * - Ensures exported list only contains orders with payment date within the selected period
 * - Updated SQL tooltip with full query explanation
 *
 * Phase 108: Change export condition to only list COMPLETED lessons:
 * - Changed ordles_status != 4 to ordles_status = 3 (only COMPLETED)
 * - Changed date filter from order_addedon BETWEEN to ordles_lesson_starttime >= start AND < end
 * - This ensures the report lists only completed lessons within the selected date range
 */
class UsageReportService
{
    protected Carbon $startDate;
    protected Carbon $endDate;

    const LESSON_STATUS_COMPLETED = 3;
    const LESSON_STATUS_CANCELLED = 4;
    const PLAN_STATUS_CANCELLED = 3;
    const PLAN_STATUS_SUSPENDED = 4;

    // Complete order conditions: order_payment_status=1 AND order_status=2
    const ORDER_PAYMENT_STATUS_PAID = 1;
    const ORDER_STATUS_COMPLETE = 2;

    /**
     * Phase 84: SpeakWell (SPW) subject IDs
     * Only these subjects should be included in the Usage Report export
     * Matching DashboardService::SPEAKWELL_SUBJECT_IDS without trial (533)
     */
    const SPW_SUBJECT_IDS = [
        558, 560, 562, 580, 581, 564, 567, 568, 569,
        416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
        390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
        584, 582, 404, 403, 583, 471
    ];

    // Phase 33: Cache for batch-loaded data
    protected array $usedBeforePeriodCache = [];
    protected array $usedInPeriodCache = [];
    protected array $refundsCache = [];
    protected array $receivedTransfersCache = [];
    protected array $outgoingTransfersCache = [];
    // Phase 106: Cache for cancelled lessons count
    protected array $cancelledLessonsCache = [];

    public function __construct(?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->startDate = $startDate ?? Carbon::now()->startOfMonth();
        $this->endDate = $endDate ?? Carbon::now()->endOfMonth();
    }

    public function generateReport(): array
    {
        $orderItems = $this->getOrderData();
        
        // Phase 33: Pre-load all period data in batch to avoid N+1 queries
        $this->preloadPeriodData($orderItems);
        
        $reportData = [];
        foreach ($orderItems as $item) {
            $reportData[] = $this->buildReportRow($item);
        }

        return [
            'data' => $reportData,
            'period' => [
                'start' => $this->startDate->format('d/m/Y'),
                'end' => $this->endDate->format('d/m/Y'),
            ],
            'summary' => $this->calculateSummary($reportData),
            'columns' => $this->getColumnDefinitions(),
        ];
    }

    /**
     * Phase 33: Pre-load all period data in batch queries
     * This eliminates N+1 query problem and dramatically improves performance
     */
    protected function preloadPeriodData(array $orderItems): void
    {
        if (empty($orderItems)) {
            return;
        }

        // Collect all order IDs and user IDs
        $orderIds = [];
        $userIds = [];
        foreach ($orderItems as $item) {
            $orderIds[] = $item->order_id;
            $userIds[] = $item->user_id;
        }
        $orderIds = array_unique($orderIds);
        $userIds = array_unique($userIds);

        // Batch query 1: Used lessons BEFORE period (for opening balance)
        $this->usedBeforePeriodCache = $this->batchGetUsedBeforePeriod($orderIds);

        // Batch query 2: Used lessons IN period
        $this->usedInPeriodCache = $this->batchGetUsedInPeriod($orderIds);

        // Batch query 3: Refunds
        $this->refundsCache = $this->batchGetRefunds($orderIds);

        // Batch query 4: Received transfers by user
        $this->receivedTransfersCache = $this->batchGetReceivedTransfers($userIds);

        // Batch query 5: Outgoing transfers by user
        $this->outgoingTransfersCache = $this->batchGetOutgoingTransfers($userIds);

        // Batch query 6: Cancelled lessons count (Phase 106)
        $this->cancelledLessonsCache = $this->batchGetCancelledLessons($orderIds);
    }

    /**
     * Batch query: Get used lessons count BEFORE period for all orders
     */
    protected function batchGetUsedBeforePeriod(array $orderIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_order_lessons')
            ->whereIn('ordles_order_id', $orderIds)
            ->where('ordles_status', self::LESSON_STATUS_COMPLETED)
            ->where('ordles_lesson_endtime', '<', $this->startDate)
            ->groupBy(['ordles_order_id', 'ordles_tlang_id'])
            ->selectRaw('ordles_order_id, ordles_tlang_id, COUNT(*) as used_count')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $key = $row->ordles_order_id . '_' . $row->ordles_tlang_id;
            $cache[$key] = (int) $row->used_count;
        }
        return $cache;
    }

    /**
     * Batch query: Get used lessons count IN period for all orders
     */
    protected function batchGetUsedInPeriod(array $orderIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_order_lessons')
            ->whereIn('ordles_order_id', $orderIds)
            ->where('ordles_status', self::LESSON_STATUS_COMPLETED)
            ->whereBetween('ordles_lesson_endtime', [$this->startDate, $this->endDate])
            ->groupBy(['ordles_order_id', 'ordles_tlang_id'])
            ->selectRaw('ordles_order_id, ordles_tlang_id, COUNT(*) as used_count')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $key = $row->ordles_order_id . '_' . $row->ordles_tlang_id;
            $cache[$key] = (int) $row->used_count;
        }
        return $cache;
    }

    /**
     * Batch query: Get refunds for all orders
     */
    protected function batchGetRefunds(array $orderIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_order_lessons')
            ->whereIn('ordles_order_id', $orderIds)
            ->whereNotNull('ordles_refund')
            ->where('ordles_refund', '>', 0)
            ->groupBy(['ordles_order_id', 'ordles_tlang_id'])
            ->selectRaw('ordles_order_id, ordles_tlang_id, COUNT(*) as refund_count, SUM(ordles_refund) as refund_total')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $key = $row->ordles_order_id . '_' . $row->ordles_tlang_id;
            $cache[$key] = [
                'lessons' => (int) $row->refund_count,
                'amount' => (float) $row->refund_total,
            ];
        }
        return $cache;
    }

    /**
     * Batch query: Get received transfers for all users
     */
    protected function batchGetReceivedTransfers(array $userIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_zcoupon_transactions')
            ->whereIn('zctran_user_id', $userIds)
            ->where('zctran_action', 1)
            ->whereBetween('zctran_created', [$this->startDate, $this->endDate])
            ->groupBy('zctran_user_id')
            ->selectRaw('zctran_user_id, COUNT(*) as trans_count, SUM(zctran_value_change) as trans_total')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $cache[$row->zctran_user_id] = [
                'lessons' => (int) $row->trans_count,
                'amount' => (float) $row->trans_total,
            ];
        }
        return $cache;
    }

    /**
     * Batch query: Get outgoing transfers for all users
     */
    protected function batchGetOutgoingTransfers(array $userIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_zcoupon_transactions')
            ->whereIn('zctran_user_id', $userIds)
            ->where('zctran_action', 2)
            ->whereBetween('zctran_created', [$this->startDate, $this->endDate])
            ->groupBy('zctran_user_id')
            ->selectRaw('zctran_user_id, COUNT(*) as trans_count, SUM(ABS(zctran_value_change)) as trans_total')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $cache[$row->zctran_user_id] = [
                'lessons' => (int) $row->trans_count,
                'amount' => (float) $row->trans_total,
            ];
        }
        return $cache;
    }

    /**
     * Phase 106: Batch query: Get cancelled lessons count for all orders
     */
    protected function batchGetCancelledLessons(array $orderIds): array
    {
        $results = DB::connection('mysql')
            ->table('tbl_order_lessons')
            ->whereIn('ordles_order_id', $orderIds)
            ->where('ordles_status', self::LESSON_STATUS_CANCELLED)
            ->groupBy(['ordles_order_id', 'ordles_tlang_id'])
            ->selectRaw('ordles_order_id, ordles_tlang_id, COUNT(*) as cancelled_count')
            ->get();

        $cache = [];
        foreach ($results as $row) {
            $key = $row->ordles_order_id . '_' . $row->ordles_tlang_id;
            $cache[$key] = (int) $row->cancelled_count;
        }
        return $cache;
    }

    /**
     * Get order data from tbl_orders JOIN tbl_order_lessons
     * Only get complete orders: order_payment_status=1 AND order_status=2
     * Group by order_id and subject_id (ordles_tlang_id)
     * 
     * Phase 36: Added teacher nationality from tbl_countries via teacher's user_country_id
     * Phase 37: Added class size from tbl_group_classes via grpcls_total_seats
     * Phase 40: REMOVED tbl_group_classes JOIN to fix 524 timeout
     *           - The group_classes subquery was causing massive slowdown
     *           - Class size is now derived from subject name (tlang_identifier) instead
     *           - This reduces query time from 100+ seconds to ~5 seconds
     */
    protected function getOrderData(): array
    {
        // Get orders with lesson counts grouped by subject
        // Phase 36: Join with tbl_users (teacher) and tbl_countries to get teacher nationality
        // Phase 40: Removed tbl_group_classes JOIN - use subject name parsing for class size
        $orderLessons = DB::connection('mysql')
            ->table('tbl_orders as o')
            ->join('tbl_order_lessons as ol', 'ol.ordles_order_id', '=', 'o.order_id')
            ->join('tbl_users as u', 'o.order_user_id', '=', 'u.user_id')
            ->leftJoin('tbl_teach_languages as tl', 'ol.ordles_tlang_id', '=', 'tl.tlang_id')
            ->leftJoin('tbl_order_payments as op', 'op.ordpay_order_id', '=', 'o.order_id')
            // Phase 36: Join with teacher user to get their country
            ->leftJoin('tbl_users as teacher', 'ol.ordles_teacher_id', '=', 'teacher.user_id')
            ->leftJoin('tbl_countries as tc', 'teacher.user_country_id', '=', 'tc.country_id')
            // Phase 40: REMOVED tbl_group_classes JOIN - was causing 524 timeout
            // Class size is now derived from subject name (tlang_identifier) in getClassSizeFromGroupClass()
            ->select([
                'o.order_id',
                'ol.ordles_tlang_id as subject_id',
                'u.user_id',
                'u.user_username as student_code',
                'u.user_first_name',
                'u.user_last_name',
                'u.user_email',
                'o.order_addedon as payment_date',
                'o.order_payment_status',
                'o.order_status',
                'o.order_total_amount',
                'o.order_net_amount',
                'o.order_extra_data',
                'tl.tlang_identifier as subject_name',
                // Billing info from tbl_order_payments
                'op.ordpay_id as billing_payment_id',
                'op.ordpay_txn_id as billing_txn_id',
                'op.ordpay_amount as billing_amount',
                'op.ordpay_response as billing_response',
                'op.ordpay_datetime as billing_datetime',
            ])
            ->selectRaw('COUNT(ol.ordles_id) as item_count')
            ->selectRaw('SUM(ol.ordles_amount) as total_amount')
            ->selectRaw('SUM(COALESCE(ol.ordles_discount, 0)) as total_discount')
            ->selectRaw('SUM(CASE WHEN ol.ordles_status = ? THEN 1 ELSE 0 END) as used_lessons', [self::LESSON_STATUS_COMPLETED])
            ->selectRaw('MIN(ol.ordles_lesson_starttime) as first_lesson_date')
            ->selectRaw('MAX(ol.ordles_lesson_endtime) as last_lesson_date')
            // Phase 36: Get the most common teacher's country code for this order/subject
            // Using GROUP_CONCAT + SUBSTRING_INDEX to get the mode (most frequent) country
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(tc.country_code ORDER BY tc.country_code), ",", 1) as teacher_country_code')
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(tc.country_identifier ORDER BY tc.country_code), ",", 1) as teacher_country_name')
            // Phase 40: REMOVED group_class_size - now derived from subject name
            // Complete order condition
            ->where('o.order_payment_status', self::ORDER_PAYMENT_STATUS_PAID)
            ->where('o.order_status', self::ORDER_STATUS_COMPLETE)
            ->whereNull('u.user_deleted')
            // Phase 84: Filter by SPW subject IDs only
            ->whereIn('ol.ordles_tlang_id', self::SPW_SUBJECT_IDS)
            // Phase 108: Only include COMPLETED lessons (ordles_status = 3)
            // Previously excluded cancelled (ordles_status != 4), now strictly completed only
            ->where('ol.ordles_status', self::LESSON_STATUS_COMPLETED)
            // Phase 108: Filter by lesson start time instead of payment date
            // Previously used order_addedon BETWEEN, now uses ordles_lesson_starttime
            ->where('ol.ordles_lesson_starttime', '>=', $this->startDate)
            ->where('ol.ordles_lesson_starttime', '<', $this->endDate)
            ->groupBy([
                'o.order_id',
                'ol.ordles_tlang_id',
                'u.user_id',
                'u.user_username',
                'u.user_first_name',
                'u.user_last_name',
                'u.user_email',
                'o.order_addedon',
                'o.order_payment_status',
                'o.order_status',
                'o.order_total_amount',
                'o.order_net_amount',
                'o.order_extra_data',
                'tl.tlang_identifier',
                'op.ordpay_id',
                'op.ordpay_txn_id',
                'op.ordpay_amount',
                'op.ordpay_response',
                'op.ordpay_datetime',
            ])
            ->orderBy('o.order_addedon', 'desc')
            ->get()
            ->toArray();

        return $orderLessons;
    }

    protected function buildReportRow(object $item): array
    {
        $totalLessons = (int)$item->item_count;
        $totalAmount = (float)($item->total_amount ?? 0);
        $totalDiscount = (float)($item->total_discount ?? 0);
        
        // Phase 31: Calculate actual price per lesson = (amount - discount) / lessons
        // This is the real value after discounts, not the listed price
        $netAmount = $totalAmount - $totalDiscount;
        $pricePerLesson = $totalLessons > 0 ? round($netAmount / $totalLessons, 0) : 0;

        // Parse billing info from order_extra_data or billing_response
        $extraData = json_decode($item->order_extra_data ?? '{}', true) ?: [];
        $billingResponse = json_decode($item->billing_response ?? '{}', true) ?: [];

        // Phase 106: Billing ID logic
        // - If import order (created_via = lesson_package_csv_import), use package_id from order_extra_data
        // - If billing order, use billing_txn_id (as before)
        // - Fallback: billing_id from extra_data or order_id
        $createdVia = $extraData['created_via'] ?? '';
        if ($createdVia === 'lesson_package_csv_import' && !empty($extraData['package_id'])) {
            $billingId = $extraData['package_id'];
        } else {
            $billingId = $item->billing_txn_id ?: ($extraData['billing_id'] ?? $item->order_id);
        }
        $itemId = $extraData['item_id'] ?? '';

        // Determine status based on lessons usage
        $usedLessons = (int)$item->used_lessons;
        $status = $this->determineOrderStatus($totalLessons, $usedLessons);

        $periodData = $this->calculatePeriodDataFromOrder($item, $pricePerLesson);

        // Phase 36: Get teacher nationality from tbl_countries instead of parsing subject name
        $teacherNationality = $this->getTeacherNationalityFromCountry($item);

        // Phase 37: Get class size from tbl_group_classes instead of parsing subject name
        $classSize = $this->getClassSizeFromGroupClass($item);

        // Phase 106: Get cancelled lessons count from cache
        $cacheKey = $item->order_id . '_' . $item->subject_id;
        $cancelledLessons = $this->cancelledLessonsCache[$cacheKey] ?? 0;

        return array_merge([
            'package_type' => $this->parsePackageType($item->subject_name ?? ''),
            'student_code' => $item->student_code ?? 'STU' . str_pad($item->user_id, 6, '0', STR_PAD_LEFT),
            'student_name' => trim(($item->user_first_name ?? '') . ' ' . ($item->user_last_name ?? '')),
            'email' => $item->user_email,
            'level' => '',
            'class_size' => $classSize,
            'teacher_nationality' => $teacherNationality,
            'zeus_package_id' => $item->subject_id,  // Using subject_id as package identifier
            'billing_id' => $billingId,
            'package_name' => $item->subject_name ?? 'Môn học #' . $item->subject_id,
            'total_sessions' => $totalLessons,
            'item_id' => $itemId,
            'payment_date' => $item->payment_date,
            'start_date' => $item->first_lesson_date,
            'end_date' => $item->last_lesson_date,
            'cancel_date' => null,  // Cancel not applicable at order-lesson level
            'status' => $status,
            'price_per_lesson' => $pricePerLesson,
            // Phase 106: New columns
            'cancelled_lessons' => $cancelledLessons,
            'zeus_order_id' => $item->order_id,
        ], $periodData);
    }

    /**
     * Determine order status based on lesson usage
     */
    protected function determineOrderStatus(int $totalLessons, int $usedLessons): string
    {
        if ($totalLessons <= 0) {
            return 'Unknown';
        }
        if ($usedLessons >= $totalLessons) {
            return 'Completed';
        }
        if ($usedLessons > 0) {
            return 'Active';
        }
        return 'Pending';
    }

    /**
     * Calculate period data from order-lesson data
     * Phase 33: Refactored to use cached batch data instead of individual queries
     */
    protected function calculatePeriodDataFromOrder(object $item, float $pricePerLesson): array
    {
        $orderId = $item->order_id;
        $subjectId = $item->subject_id;
        $totalLessons = (int)$item->item_count;
        $totalAmount = (float)($item->total_amount ?? 0);

        // Phase 33: Use cached data instead of individual queries
        $cacheKey = $orderId . '_' . $subjectId;
        
        // Phase 106: Fix opening balance logic
        // If order was paid ON or AFTER the period start date, opening balance = 0
        // because the order didn't exist at the start of the period.
        // Only count opening balance for orders that existed before the period.
        $paymentDate = $item->payment_date ? Carbon::parse($item->payment_date) : null;
        if ($paymentDate && $paymentDate >= $this->startDate) {
            // Order was created during or after the period start - no opening balance
            $openingLessons = 0;
            $openingAmount = 0;
        } else {
            // Order existed before the period - calculate opening balance
            $usedBefore = $this->usedBeforePeriodCache[$cacheKey] ?? 0;
            $openingLessons = max(0, $totalLessons - $usedBefore);
            $openingAmount = $openingLessons * $pricePerLesson;
        }

        // Purchases in period: if payment_date is within period
        $purchasedInPeriod = $this->getPurchasesInPeriodFromOrder($item);

        // Get transfers from cache
        $receivedTransfers = $this->receivedTransfersCache[$item->user_id] ?? ['lessons' => 0, 'amount' => 0];
        $outgoingTransfers = $this->outgoingTransfersCache[$item->user_id] ?? ['lessons' => 0, 'amount' => 0];
        
        $bonusCommitment = ['lessons' => 0, 'amount' => 0];
        $bonusOperation = ['lessons' => 0, 'amount' => 0];

        $totalIncreaseLessons = $purchasedInPeriod['lessons'] + $receivedTransfers['lessons']
            + $bonusCommitment['lessons'] + $bonusOperation['lessons'];
        $totalIncreaseAmount = $purchasedInPeriod['amount'] + $receivedTransfers['amount']
            + $bonusCommitment['amount'] + $bonusOperation['amount'];

        // Get used in period from cache
        $usedInPeriodCount = $this->usedInPeriodCache[$cacheKey] ?? 0;
        $usedInPeriod = ['lessons' => $usedInPeriodCount, 'amount' => $usedInPeriodCount * $pricePerLesson];
        
        $balanceDeletion = ['lessons' => 0, 'amount' => 0];
        
        // Get refunds from cache
        $refunds = $this->refundsCache[$cacheKey] ?? ['lessons' => 0, 'amount' => 0];
        
        $deactive = ['lessons' => 0, 'amount' => 0];
        $other = ['lessons' => 0, 'amount' => 0];

        $totalDecreaseLessons = $usedInPeriod['lessons'] + $outgoingTransfers['lessons']
            + $balanceDeletion['lessons'] + $refunds['lessons'] + $deactive['lessons'];
        $totalDecreaseAmount = $usedInPeriod['amount'] + $outgoingTransfers['amount']
            + $balanceDeletion['amount'] + $refunds['amount'] + $deactive['amount'];

        $closingLessons = $openingLessons + $totalIncreaseLessons - $totalDecreaseLessons;
        $closingAmount = $openingAmount + $totalIncreaseAmount - $totalDecreaseAmount;

        return [
            'opening_lessons' => $openingLessons, 'opening_amount' => $openingAmount,
            'purchased_lessons' => $purchasedInPeriod['lessons'],
            'purchased_amount' => $purchasedInPeriod['amount'],
            'received_transfer_lessons' => $receivedTransfers['lessons'],
            'received_transfer_amount' => $receivedTransfers['amount'],
            'bonus_commitment_lessons' => $bonusCommitment['lessons'],
            'bonus_commitment_amount' => $bonusCommitment['amount'],
            'bonus_operation_lessons' => $bonusOperation['lessons'],
            'bonus_operation_amount' => $bonusOperation['amount'],
            'total_increase_lessons' => $totalIncreaseLessons,
            'total_increase_amount' => $totalIncreaseAmount,
            'used_lessons' => $usedInPeriod['lessons'], 'used_amount' => $usedInPeriod['amount'],
            'transfer_out_lessons' => $outgoingTransfers['lessons'],
            'transfer_out_amount' => $outgoingTransfers['amount'],
            'balance_deletion_lessons' => $balanceDeletion['lessons'],
            'balance_deletion_amount' => $balanceDeletion['amount'],
            'refund_lessons' => $refunds['lessons'], 'refund_amount' => $refunds['amount'],
            'deactive_lessons' => $deactive['lessons'], 'deactive_amount' => $deactive['amount'],
            'other_lessons' => $other['lessons'], 'other_amount' => $other['amount'],
            'total_decrease_lessons' => $totalDecreaseLessons,
            'total_decrease_amount' => $totalDecreaseAmount,
            'closing_lessons' => max(0, $closingLessons),
            'closing_amount' => max(0, $closingAmount),
        ];
    }

    /**
     * Get purchases in period from order data
     * Note: This method is still needed as it uses item data, not database query
     */
    protected function getPurchasesInPeriodFromOrder(object $item): array
    {
        if ($item->payment_date &&
            Carbon::parse($item->payment_date)->between($this->startDate, $this->endDate)) {
            return [
                'lessons' => (int)$item->item_count,
                'amount' => (float)($item->total_amount ?? 0)
            ];
        }
        return ['lessons' => 0, 'amount' => 0];
    }

    // Phase 33: Removed individual query methods in favor of batch queries:
    // - getOpeningBalanceFromOrder() -> now uses usedBeforePeriodCache
    // - getUsedInPeriodFromOrder() -> now uses usedInPeriodCache
    // - getRefundsFromOrder() -> now uses refundsCache
    // - getReceivedTransfers() -> now uses receivedTransfersCache  
    // - getOutgoingTransfers() -> now uses outgoingTransfersCache

    protected function parsePackageType(string $title): string
    {
        $title = strtoupper($title);
        if (str_contains($title, 'SPEAKWELL') || str_contains($title, 'SPW')) return 'SPW';
        if (str_contains($title, 'IELTS') || str_contains($title, 'IE')) return 'IE';
        if (str_contains($title, 'ENGLISH PLUS') || str_contains($title, 'EP')) return 'EP';
        if (str_contains($title, 'EASY SPEAK') || str_contains($title, 'ES')) return 'ES';
        if (str_contains($title, 'FREETALK') || str_contains($title, 'FT')) return 'FT';
        return 'OTHER';
    }

    /**
     * Parse class size from subject name (fallback method)
     * @deprecated Phase 37: Use getClassSizeFromGroupClass() instead
     */
    protected function parseClassSize(string $title): string
    {
        $title = strtoupper($title);
        if (str_contains($title, '1-1') || str_contains($title, '1:1')) return '1:1';
        if (str_contains($title, '1-2') || str_contains($title, '1:2')) return '1:2';
        if (str_contains($title, '1-3') || str_contains($title, '1:3')) return '1:3';
        if (str_contains($title, '1-6') || str_contains($title, '1:6')) return '1:6';
        if (str_contains($title, '1-8') || str_contains($title, '1:8')) return '1:8';
        if (str_contains($title, 'GROUP')) return 'Group';
        return 'N/A';
    }

    /**
     * Phase 36: Get teacher nationality from tbl_countries data
     * Uses country_code from the joined tbl_countries table
     * Falls back to parsing subject name if country data is not available
     */
    protected function getTeacherNationalityFromCountry(object $item): string
    {
        // First try to get country code from the database (joined via tbl_countries)
        if (!empty($item->teacher_country_code)) {
            $countryCode = strtoupper($item->teacher_country_code);
            
            // Map common country codes to display names
            // PH = Philippines, VN = Vietnam, US/UK/CA/AU = Native English speakers
            $nativeEnglishCountries = ['US', 'GB', 'UK', 'CA', 'AU', 'NZ', 'IE', 'ZA'];
            
            if ($countryCode === 'VN') {
                return 'VN';
            }
            if ($countryCode === 'PH') {
                return 'PHIL';
            }
            if (in_array($countryCode, $nativeEnglishCountries)) {
                return 'NN'; // Native (Native English speaker)
            }
            
            // Return the country code itself for other countries
            return $countryCode;
        }
        
        // Fallback: If no country data from database, try parsing from subject name
        return $this->parseTeacherNationality($item->subject_name ?? '');
    }

    /**
     * Parse teacher nationality from subject name (fallback method)
     * @deprecated Phase 36: Use getTeacherNationalityFromCountry() instead
     */
    protected function parseTeacherNationality(string $title): string
    {
        $title = strtoupper($title);
        if (str_contains($title, 'VIETNAM') || str_contains($title, 'VN') || str_contains($title, 'VIỆT')) return 'VN';
        if (str_contains($title, 'NATIVE') || str_contains($title, 'NN')) return 'NN';
        if (str_contains($title, 'PHILIPPINES') || str_contains($title, 'PHIL')) return 'PHIL';
        if (str_contains($title, 'MIX')) return 'MIX';
        return 'N/A';
    }

    /**
     * Phase 37/40: Get class size from subject name
     * 
     * Phase 40: Simplified to only use subject name parsing
     * The previous approach using tbl_group_classes JOIN was causing 524 timeout errors
     * because the subquery scanned the entire table for every export.
     * 
     * Class size mapping (parsed from subject name like "SPW 1-1 PHIL" or "IELTS 1:6 VN"):
     * - 1:1 = Individual lesson
     * - 1:2 = Pair lesson  
     * - 1:3 = Small group
     * - 1:6 = Medium group
     * - 1:8 = Large group
     * - Group = Class/workshop
     */
    protected function getClassSizeFromGroupClass(object $item): string
    {
        // Phase 40: Always use subject name parsing
        // The tbl_group_classes lookup was removed to fix timeout issues
        return $this->parseClassSize($item->subject_name ?? '');
    }

    protected function calculateSummary(array $reportData): array
    {
        $summary = [
            'total_records' => count($reportData),
            'total_opening_lessons' => 0, 'total_opening_amount' => 0,
            'total_purchased_lessons' => 0, 'total_purchased_amount' => 0,
            'total_used_lessons' => 0, 'total_used_amount' => 0,
            'total_cancelled_lessons' => 0,
            'total_closing_lessons' => 0, 'total_closing_amount' => 0,
            'by_package_type' => [], 'by_status' => [],
        ];
        foreach ($reportData as $row) {
            $summary['total_opening_lessons'] += $row['opening_lessons'] ?? 0;
            $summary['total_opening_amount'] += $row['opening_amount'] ?? 0;
            $summary['total_purchased_lessons'] += $row['purchased_lessons'] ?? 0;
            $summary['total_purchased_amount'] += $row['purchased_amount'] ?? 0;
            $summary['total_used_lessons'] += $row['used_lessons'] ?? 0;
            $summary['total_used_amount'] += $row['used_amount'] ?? 0;
            $summary['total_cancelled_lessons'] += $row['cancelled_lessons'] ?? 0;
            $summary['total_closing_lessons'] += $row['closing_lessons'] ?? 0;
            $summary['total_closing_amount'] += $row['closing_amount'] ?? 0;
            $type = $row['package_type'] ?? 'OTHER';
            $status = $row['status'] ?? 'Unknown';
            if (!isset($summary['by_package_type'][$type])) {
                $summary['by_package_type'][$type] = ['count' => 0, 'lessons' => 0, 'amount' => 0];
            }
            $summary['by_package_type'][$type]['count']++;
            $summary['by_package_type'][$type]['lessons'] += $row['total_sessions'] ?? 0;
            if (!isset($summary['by_status'][$status])) {
                $summary['by_status'][$status] = ['count' => 0, 'lessons' => 0];
            }
            $summary['by_status'][$status]['count']++;
            $summary['by_status'][$status]['lessons'] += $row['total_sessions'] ?? 0;
        }
        return $summary;
    }

    /**
     * Phase 106: Reduced column set per accounting requirements
     * Keep: A-V (info + opening + purchased), used, cancelled (new), closing, zeus_order_id (new)
     * Removed: received transfers, bonuses, total increase, transfer out,
     *          balance deletion, refunds, deactive, other, total decrease
     */
    protected function getColumnDefinitions(): array
    {
        return [
            // A-R: Info columns
            ['key' => 'package_type', 'label' => 'Phân loại gói', 'group' => 'info'],
            ['key' => 'student_code', 'label' => 'Mã HV', 'group' => 'info'],
            ['key' => 'student_name', 'label' => 'Tên HV', 'group' => 'info'],
            ['key' => 'email', 'label' => 'Email', 'group' => 'info'],
            ['key' => 'level', 'label' => 'Trình độ', 'group' => 'info'],
            ['key' => 'class_size', 'label' => 'Size lớp', 'group' => 'info'],
            ['key' => 'teacher_nationality', 'label' => 'Quốc tịch GV', 'group' => 'info'],
            ['key' => 'zeus_package_id', 'label' => 'Mã gói Zeus', 'group' => 'info'],
            ['key' => 'billing_id', 'label' => 'ID Billing', 'group' => 'info'],
            ['key' => 'package_name', 'label' => 'Tên gói', 'group' => 'info'],
            ['key' => 'total_sessions', 'label' => 'Số buổi', 'group' => 'info'],
            ['key' => 'item_id', 'label' => 'Item_ID', 'group' => 'info'],
            ['key' => 'payment_date', 'label' => 'Ngày thanh toán', 'group' => 'info'],
            ['key' => 'start_date', 'label' => 'Ngày bắt đầu', 'group' => 'info'],
            ['key' => 'end_date', 'label' => 'Ngày kết thúc', 'group' => 'info'],
            ['key' => 'cancel_date', 'label' => 'Ngày hủy', 'group' => 'info'],
            ['key' => 'status', 'label' => 'Trạng thái', 'group' => 'info'],
            ['key' => 'price_per_lesson', 'label' => 'Giá/buổi', 'group' => 'info'],
            // S-V: Opening balance & Purchased in period
            ['key' => 'opening_lessons', 'label' => 'Dư đầu kỳ - Số buổi', 'group' => 'opening'],
            ['key' => 'opening_amount', 'label' => 'Dư đầu kỳ - Số tiền', 'group' => 'opening'],
            ['key' => 'purchased_lessons', 'label' => 'Mua trong kỳ - Số buổi', 'group' => 'increase'],
            ['key' => 'purchased_amount', 'label' => 'Mua trong kỳ - Số tiền', 'group' => 'increase'],
            // W-X: Used in period
            ['key' => 'used_lessons', 'label' => 'Sử dụng - Số buổi', 'group' => 'decrease'],
            ['key' => 'used_amount', 'label' => 'Sử dụng - Số tiền', 'group' => 'decrease'],
            // Y: Cancelled lessons (Phase 106 NEW)
            ['key' => 'cancelled_lessons', 'label' => 'Hủy - Số buổi', 'group' => 'decrease'],
            // Z-AA: Closing balance
            ['key' => 'closing_lessons', 'label' => 'Cuối kỳ - Số buổi', 'group' => 'closing'],
            ['key' => 'closing_amount', 'label' => 'Cuối kỳ - Số tiền', 'group' => 'closing'],
            // AB: Zeus Order ID (Phase 106 NEW)
            ['key' => 'zeus_order_id', 'label' => 'Zeus Order ID', 'group' => 'info'],
        ];
    }
}

