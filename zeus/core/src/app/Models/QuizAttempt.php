<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_quiz_attempts';
    protected $primaryKey = 'quizat_id';
    public $timestamps = false;

    // Status constants (matching actual DB)
    const STATUS_IN_PROGRESS = 0;
    const STATUS_COMPLETED = 2;
    const STATUS_ABANDONED = 3;

    // Evaluation constants
    const EVAL_NOT_EVALUATED = 0;
    const EVAL_PASSED = 1;
    const EVAL_FAILED = 2;

    protected $fillable = [
        'quizat_quilin_id',
        'quizat_user_id',
        'quizat_scored',
        'quizat_marks',
        'quizat_progress',
        'quizat_qulinqu_id',
        'quizat_evaluation',
        'quizat_certificate_number',
        'quizat_status',
        'quizat_active',
        'quizat_started',
        'quizat_created',
        'quizat_updated',
        'quizat_count',
    ];

    protected $casts = [
        'quizat_started' => 'datetime',
        'quizat_created' => 'datetime',
        'quizat_updated' => 'datetime',
    ];

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'quizat_user_id', 'user_id');
    }

    /**
     * Get completed attempts
     */
    public function scopeCompleted($query)
    {
        return $query->where('quizat_status', self::STATUS_COMPLETED);
    }

    /**
     * Get in-progress attempts
     */
    public function scopeInProgress($query)
    {
        return $query->where('quizat_status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Get passed attempts (evaluation = 1)
     */
    public function scopePassed($query)
    {
        return $query->where('quizat_evaluation', self::EVAL_PASSED);
    }

    /**
     * Get failed attempts (evaluation = 2)
     */
    public function scopeFailed($query)
    {
        return $query->where('quizat_evaluation', self::EVAL_FAILED);
    }

    /**
     * Get attempts this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('quizat_started', now()->month)
            ->whereYear('quizat_started', now()->year);
    }

    /**
     * Get attempts today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('quizat_started', today());
    }

    /**
     * Get status label
     */
    public static function getStatusLabel($status): string
    {
        return match($status) {
            self::STATUS_IN_PROGRESS => 'Đang làm',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_ABANDONED => 'Bỏ dở',
            default => 'Không rõ',
        };
    }
}
