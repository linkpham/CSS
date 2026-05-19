<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OrderLesson Model - Zeus Core Database
 * 
 * @property int $ordles_id
 * @property int $ordles_status
 * @property int $ordles_type
 * @property int $ordles_duration
 * @property float $ordles_teacher_paid
 * @property float $ordles_commission_amount
 * @property float $ordles_affiliate_commission
 * @property \DateTime $ordles_lesson_starttime
 * @property \DateTime|null $ordles_teacher_starttime
 * @property \DateTime|null $ordles_student_starttime
 */
class OrderLesson extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_order_lessons';
    protected $primaryKey = 'ordles_id';
    public $timestamps = false;

    /**
     * Get the extras record associated with this lesson.
     */
    public function extras()
    {
        return $this->hasOne(OrderLessonExtra::class, 'ole_ordles_id', 'ordles_id');
    }

    // Lesson Status
    public const STATUS_UNSCHEDULED = 1;
    public const STATUS_SCHEDULED = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    // Lesson Type
    public const TYPE_TRIAL = 1;
    public const TYPE_REGULAR = 2;

    protected $casts = [
        'ordles_lesson_starttime' => 'datetime',
        'ordles_teacher_starttime' => 'datetime',
        'ordles_student_starttime' => 'datetime',
        'ordles_teacher_paid' => 'float',
        'ordles_commission_amount' => 'float',
        'ordles_affiliate_commission' => 'float',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('ordles_lesson_starttime', now()->toDateString());
    }

    public function scopeCompleted($query)
    {
        return $query->where('ordles_status', self::STATUS_COMPLETED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('ordles_status', self::STATUS_SCHEDULED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('ordles_status', self::STATUS_CANCELLED);
    }

    public function scopeTrial($query)
    {
        return $query->where('ordles_type', self::TYPE_TRIAL);
    }

    public function scopeRegular($query)
    {
        return $query->where('ordles_type', self::TYPE_REGULAR);
    }

    public static function getStatusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_UNSCHEDULED => 'Chưa lên lịch',
            self::STATUS_SCHEDULED => 'Đã lên lịch',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => 'Unknown',
        };
    }

    public static function getTypeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_TRIAL => 'Trial',
            self::TYPE_REGULAR => 'Regular',
            default => 'Unknown',
        };
    }
}
