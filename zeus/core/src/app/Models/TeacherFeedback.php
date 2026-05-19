<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Teacher Feedback Model - Zeus Core Database
 * 
 * @property int $teafeed_id
 * @property int $teafeed_lang_id
 * @property int $teafeed_teacher_id
 * @property int $teafeed_learner_id
 * @property int $teafeed_record_id
 * @property int $teafeed_record_type
 * @property int $teafeed_type
 * @property int $teafeed_form_id
 * @property array $teafeed_values
 * @property int $teafeed_status
 * @property int $teafeed_notify_status
 * @property int $teafeed_crm
 * @property \DateTime $teafeed_created_at
 * @property \DateTime $teafeed_updated_at
 * @property bool $teafeed_ignore_feedback
 */
class TeacherFeedback extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_feedbacks';
    protected $primaryKey = 'teafeed_id';
    public $timestamps = false;

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_HIDDEN = 4;

    // Type constants
    const TYPE_TRIAL = 1;
    const TYPE_REGULAR = 2;
    const TYPE_MIDTERM = 3;
    const TYPE_FINAL = 4;

    // Record type constants
    const RECORD_TYPE_ONE_ON_ONE = 1;
    const RECORD_TYPE_GROUP = 2;

    // Notify status constants
    const NOTIFY_NOT_SENT = 0;
    const NOTIFY_SUCCESS = 1;
    const NOTIFY_ERROR = 2;

    protected $fillable = [
        'teafeed_lang_id',
        'teafeed_teacher_id',
        'teafeed_learner_id',
        'teafeed_record_id',
        'teafeed_record_type',
        'teafeed_type',
        'teafeed_form_id',
        'teafeed_values',
        'teafeed_status',
        'teafeed_notify_status',
        'teafeed_crm',
        'teafeed_ignore_feedback',
    ];

    protected $casts = [
        'teafeed_values' => 'array',
        'teafeed_created_at' => 'datetime',
        'teafeed_updated_at' => 'datetime',
        'teafeed_ignore_feedback' => 'boolean',
    ];

    /**
     * Scope for pending feedback
     */
    public function scopePending($query)
    {
        return $query->where('teafeed_status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved feedback
     */
    public function scopeApproved($query)
    {
        return $query->where('teafeed_status', self::STATUS_APPROVED);
    }

    /**
     * Scope for today's feedback
     */
    public function scopeToday($query)
    {
        return $query->whereDate('teafeed_created_at', today());
    }

    /**
     * Scope for trial feedback (based on teafeed_type - OLD/DEPRECATED)
     * Note: This uses teafeed_type which may not be accurate.
     * Use trialByLesson() for accurate trial feedback based on lesson type.
     */
    public function scopeTrial($query)
    {
        return $query->where('teafeed_type', self::TYPE_TRIAL);
    }

    /**
     * Scope for regular feedback (based on teafeed_type - OLD/DEPRECATED)
     * Note: This uses teafeed_type which may not be accurate.
     * Use regularByLesson() for accurate regular feedback based on lesson type.
     */
    public function scopeRegular($query)
    {
        return $query->where('teafeed_type', self::TYPE_REGULAR);
    }

    /**
     * Scope for trial feedback based on lesson type (CORRECT)
     * Joins with tbl_order_lessons to check ordles_type = 1 (Trial)
     */
    public function scopeTrialByLesson($query)
    {
        return $query->where('teafeed_record_type', self::RECORD_TYPE_ONE_ON_ONE)
            ->whereIn('teafeed_record_id', function($subquery) {
                $subquery->select('ordles_id')
                    ->from('tbl_order_lessons')
                    ->where('ordles_type', 1); // TYPE_TRIAL
            });
    }

    /**
     * Scope for regular feedback based on lesson type (CORRECT)
     * Joins with tbl_order_lessons to check ordles_type = 2 (Regular)
     */
    public function scopeRegularByLesson($query)
    {
        return $query->where('teafeed_record_type', self::RECORD_TYPE_ONE_ON_ONE)
            ->whereIn('teafeed_record_id', function($subquery) {
                $subquery->select('ordles_id')
                    ->from('tbl_order_lessons')
                    ->where('ordles_type', 2); // TYPE_REGULAR
            });
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_HIDDEN => 'Ẩn',
            default => 'Unknown',
        };
    }

    /**
     * Get type label
     */
    public static function getTypeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_TRIAL => 'Trial',
            self::TYPE_REGULAR => 'Regular',
            self::TYPE_MIDTERM => 'Midterm',
            self::TYPE_FINAL => 'Final',
            default => 'Unknown',
        };
    }

    /**
     * Relationship: Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teafeed_teacher_id', 'user_id');
    }

    /**
     * Relationship: Learner
     */
    public function learner()
    {
        return $this->belongsTo(User::class, 'teafeed_learner_id', 'user_id');
    }
}
