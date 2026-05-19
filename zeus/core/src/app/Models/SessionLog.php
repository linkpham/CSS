<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SessionLog Model - Zeus Core Database
 * 
 * Records changes to session (lesson) statuses, including cancellations.
 * 
 * @property int $sesslog_id
 * @property int $sesslog_record_id     FK to tbl_order_lessons.ordles_id when sesslog_record_type=1
 * @property int $sesslog_record_type   1=order lesson (1-on-1)
 * @property int $sesslog_user_id       FK to tbl_users.user_id (who performed the action)
 * @property int $sesslog_user_type     1=student, 2=teacher, 3=admin
 * @property int $sesslog_prev_status
 * @property int $sesslog_changed_status 4=cancelled
 * @property string|null $sesslog_prev_starttime
 * @property string|null $sesslog_prev_endtime
 * @property string|null $sesslog_changed_starttime
 * @property string|null $sesslog_changed_endtime
 * @property string|null $sesslog_comment   Reason for the change
 * @property \DateTime $sesslog_created     When the change was made
 * @property int $sesslog_admin_id
 */
class SessionLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_session_logs';
    protected $primaryKey = 'sesslog_id';
    public $timestamps = false;

    // Record types
    public const RECORD_TYPE_ORDER_LESSON = 1;
    public const RECORD_TYPE_GROUP_CLASS = 2;

    // User types (who performed the action)
    public const USER_TYPE_STUDENT = 1;
    public const USER_TYPE_TEACHER = 2;
    public const USER_TYPE_ADMIN = 3;

    // Status values
    public const STATUS_UNSCHEDULED = 1;
    public const STATUS_SCHEDULED = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    protected $casts = [
        'sesslog_created' => 'datetime',
    ];

    /**
     * Scope for cancelled sessions
     */
    public function scopeCancelled($query)
    {
        return $query->where('sesslog_changed_status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for 1-on-1 lessons
     */
    public function scopeOrderLessons($query)
    {
        return $query->where('sesslog_record_type', self::RECORD_TYPE_ORDER_LESSON);
    }

    /**
     * Get user type label
     */
    public static function getUserTypeLabel(int $userType): string
    {
        return match ($userType) {
            self::USER_TYPE_STUDENT => 'Học sinh',
            self::USER_TYPE_TEACHER => 'Giáo viên',
            self::USER_TYPE_ADMIN => 'Admin',
            default => 'Không xác định',
        };
    }
}
