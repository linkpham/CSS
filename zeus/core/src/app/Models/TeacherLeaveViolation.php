<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveViolation extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_leave_violations';
    protected $primaryKey = 'tlv_id';
    public $timestamps = false;

    // Violation Type constants (matching actual DB)
    const TYPE_QUOTA = 1;
    const TYPE_DEADLINE = 2;
    const TYPE_NO_SHOW = 3;
    const TYPE_CLASS_IMPACT = 4;

    // Status constants
    const STATUS_PENDING = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_CANCELLED = 3;

    protected $fillable = [
        'tlv_teacher_id',
        'tlv_leave_request_id',
        'tlv_violation_type',
        'tlv_violation_level',
        'tlv_exceeded_days',
        'tlv_penalty_amount',
        'tlv_penalty_percentage',
        'tlv_affected_sessions',
        'tlv_affected_students',
        'tlv_affected_classes',
        'tlv_description',
        'tlv_status',
        'tlv_processed_by',
        'tlv_processed_at',
        'tlv_created_at',
        'tlv_updated_at',
    ];

    protected $casts = [
        'tlv_affected_classes' => 'array',
        'tlv_processed_at' => 'datetime',
        'tlv_created_at' => 'datetime',
        'tlv_updated_at' => 'datetime',
    ];

    /**
     * Relationship to teacher (user)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'tlv_teacher_id', 'user_id');
    }

    /**
     * Relationship to leave request
     */
    public function leaveRequest()
    {
        return $this->belongsTo(TeacherLeaveRequest::class, 'tlv_leave_request_id', 'tlr_id');
    }

    /**
     * Get no-show violations
     */
    public function scopeNoShow($query)
    {
        return $query->where('tlv_violation_type', self::TYPE_NO_SHOW);
    }

    /**
     * Get late submission (deadline) violations
     */
    public function scopeLateSubmission($query)
    {
        return $query->where('tlv_violation_type', self::TYPE_DEADLINE);
    }

    /**
     * Get exceeded quota violations
     */
    public function scopeExceededQuota($query)
    {
        return $query->where('tlv_violation_type', self::TYPE_QUOTA);
    }

    /**
     * Get violations this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tlv_created_at', now()->month)
            ->whereYear('tlv_created_at', now()->year);
    }

    /**
     * Get violation type label
     */
    public static function getTypeLabel($type): string
    {
        return match($type) {
            self::TYPE_QUOTA => 'Vượt quota',
            self::TYPE_DEADLINE => 'Nộp đơn trễ',
            self::TYPE_NO_SHOW => 'Không có mặt',
            self::TYPE_CLASS_IMPACT => 'Ảnh hưởng lớp',
            default => 'Không rõ',
        };
    }
}
