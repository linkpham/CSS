<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveRequestSession extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_leave_request_sessions';
    protected $primaryKey = 'tlrs_id';
    public $timestamps = false;

    // Session type
    const SESSION_TYPE_LESSON = 1;  // Lesson 1-1
    const SESSION_TYPE_CLASS = 2;    // Class 1-n

    // Replacement type
    const REPLACEMENT_TYPE_SUBSTITUTE = 1;
    const REPLACEMENT_TYPE_REPLACE = 2;

    protected $fillable = [
        'tlrs_leave_request_id',
        'tlrs_session_id',
        'tlrs_session_type',
        'tlrs_session_date',
        'tlrs_need_replacement',
        'tlrs_replacement_type',
        'tlrs_session_info',
        'tlrs_created_at',
    ];

    protected $casts = [
        'tlrs_session_date' => 'date',
        'tlrs_session_type' => 'integer',
        'tlrs_need_replacement' => 'boolean',
        'tlrs_replacement_type' => 'integer',
        'tlrs_session_info' => 'array',
        'tlrs_created_at' => 'datetime',
    ];

    /**
     * Relationship to leave request
     */
    public function leaveRequest()
    {
        return $this->belongsTo(TeacherLeaveRequest::class, 'tlrs_leave_request_id', 'tlr_id');
    }

    /**
     * Relationship to lesson (when session_type = 1)
     */
    public function lesson()
    {
        return $this->belongsTo(OrderLesson::class, 'tlrs_session_id', 'ordles_id');
    }

    /**
     * Check if this is a 1-1 lesson session
     */
    public function isLesson(): bool
    {
        return $this->tlrs_session_type === self::SESSION_TYPE_LESSON;
    }

    /**
     * Check if this is a group class session
     */
    public function isClass(): bool
    {
        return $this->tlrs_session_type === self::SESSION_TYPE_CLASS;
    }

    /**
     * Get session type label
     */
    public static function getSessionTypeLabel(int $type): string
    {
        return match ($type) {
            self::SESSION_TYPE_LESSON => 'Buổi học 1-1',
            self::SESSION_TYPE_CLASS => 'Lớp nhóm',
            default => 'Không rõ',
        };
    }

    /**
     * Get replacement type label
     */
    public static function getReplacementTypeLabel(?int $type): string
    {
        return match ($type) {
            self::REPLACEMENT_TYPE_SUBSTITUTE => 'Dạy thay (tạm thời)',
            self::REPLACEMENT_TYPE_REPLACE => 'Thay GV mới',
            null => 'Nghỉ buổi (không thay)',
            default => 'Không rõ',
        };
    }

    /**
     * Get replacement type short label
     */
    public static function getReplacementTypeShortLabel(?int $type): string
    {
        return match ($type) {
            self::REPLACEMENT_TYPE_SUBSTITUTE => 'Dạy thay',
            self::REPLACEMENT_TYPE_REPLACE => 'Thay GV',
            null => 'Không thay',
            default => 'Không rõ',
        };
    }
}
