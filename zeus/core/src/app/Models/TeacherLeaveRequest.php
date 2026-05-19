<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveRequest extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_leave_requests';
    protected $primaryKey = 'tlr_id';
    public $timestamps = false;

    // Leave Type constants
    const TYPE_SHORT_TERM = 1; // <= 6 days
    const TYPE_LONG_TERM = 2;  // >= 7 days

    // Status constants (matching Zeus Core)
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;
    const STATUS_AUTO_APPROVED = 2;
    const STATUS_APPROVED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_MORE_INFO = 5;
    const STATUS_CANCELED = 6;

    // Reason Type constants
    const REASON_TYPE_PERSONAL = 1;
    const REASON_TYPE_FORCE_MAJEURE = 2;

    protected $fillable = [
        'tlr_teacher_id',
        'tlr_start_date',
        'tlr_end_date',
        'tlr_leave_type',
        'tlr_reason',
        'tlr_reason_type',
        'tlr_status',
        'tlr_total_days',
        'tlr_is_valid',
        'tlr_validation_result',
        'tlr_advance_notice_days',
        'tlr_approved_by',
        'tlr_approved_at',
        'tlr_created_at',
        'tlr_updated_at',
    ];

    protected $casts = [
        'tlr_start_date' => 'datetime',
        'tlr_end_date' => 'datetime',
        'tlr_approved_at' => 'datetime',
        'tlr_created_at' => 'datetime',
        'tlr_updated_at' => 'datetime',
    ];

    /**
     * Relationship to teacher (user)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'tlr_teacher_id', 'user_id');
    }

    /**
     * Get pending leave requests
     */
    public function scopePending($query)
    {
        return $query->where('tlr_status', self::STATUS_PENDING);
    }

    /**
     * Get approved leave requests (both auto and manual)
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('tlr_status', [self::STATUS_AUTO_APPROVED, self::STATUS_APPROVED]);
    }

    /**
     * Get rejected leave requests
     */
    public function scopeRejected($query)
    {
        return $query->where('tlr_status', self::STATUS_REJECTED);
    }

    /**
     * Get canceled leave requests
     */
    public function scopeCanceled($query)
    {
        return $query->where('tlr_status', self::STATUS_CANCELED);
    }

    /**
     * Get short-term leave requests
     */
    public function scopeShortTerm($query)
    {
        return $query->where('tlr_leave_type', self::TYPE_SHORT_TERM);
    }

    /**
     * Get long-term leave requests
     */
    public function scopeLongTerm($query)
    {
        return $query->where('tlr_leave_type', self::TYPE_LONG_TERM);
    }

    /**
     * Get leave requests for current month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tlr_start_date', now()->month)
            ->whereYear('tlr_start_date', now()->year);
    }

    /**
     * Get leave requests for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('tlr_created_at', today());
    }

    /**
     * Get status label
     */
    public static function getStatusLabel($status): string
    {
        return match($status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_AUTO_APPROVED => 'Tự động duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_MORE_INFO => 'Cần thêm thông tin',
            self::STATUS_CANCELED => 'Đã hủy',
            default => 'Không rõ',
        };
    }

    /**
     * Get leave type label
     */
    public static function getLeaveTypeLabel($type): string
    {
        return match($type) {
            self::TYPE_SHORT_TERM => 'Ngắn hạn (≤6 ngày)',
            self::TYPE_LONG_TERM => 'Dài hạn (≥7 ngày)',
            default => 'Không rõ',
        };
    }

    /**
     * Get reason type label
     */
    public static function getReasonTypeLabel($type): string
    {
        return match($type) {
            self::REASON_TYPE_PERSONAL => 'Cá nhân',
            self::REASON_TYPE_FORCE_MAJEURE => 'Bất khả kháng',
            default => 'Không rõ',
        };
    }
}
