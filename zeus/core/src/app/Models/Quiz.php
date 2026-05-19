<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_quizzes';
    protected $primaryKey = 'quiz_id';
    public $timestamps = false;

    // Status constants (matching actual DB)
    const STATUS_INACTIVE = 0;
    const STATUS_DRAFT = 1;
    const STATUS_ACTIVE = 2;

    protected $fillable = [
        'quiz_type',
        'quiz_title',
        'quiz_detail',
        'quiz_user_id',
        'quiz_duration',
        'quiz_attempts',
        'quiz_marks',
        'quiz_passmark',
        'quiz_validity',
        'quiz_certificate',
        'quiz_questions',
        'quiz_failmsg',
        'quiz_passmsg',
        'quiz_active',
        'quiz_status',
        'quiz_created',
        'quiz_updated',
        'quiz_deleted',
    ];

    protected $casts = [
        'quiz_created' => 'datetime',
        'quiz_updated' => 'datetime',
        'quiz_deleted' => 'datetime',
    ];

    /**
     * Get active quizzes
     */
    public function scopeActive($query)
    {
        return $query->where('quiz_active', 1)
            ->whereNull('quiz_deleted');
    }

    /**
     * Get draft quizzes
     */
    public function scopeDraft($query)
    {
        return $query->where('quiz_active', 0)
            ->whereNull('quiz_deleted');
    }

    /**
     * Get quizzes with upcoming deadline (active but created recently)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('quiz_created', '>', now()->subDays(7))
            ->where('quiz_active', 1)
            ->whereNull('quiz_deleted');
    }

    /**
     * Get quizzes created this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('quiz_created', now()->month)
            ->whereYear('quiz_created', now()->year)
            ->whereNull('quiz_deleted');
    }

    /**
     * Get status label
     */
    public static function getStatusLabel($status): string
    {
        return match($status) {
            self::STATUS_INACTIVE => 'Không hoạt động',
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_ACTIVE => 'Đang hoạt động',
            default => 'Không rõ',
        };
    }
}
