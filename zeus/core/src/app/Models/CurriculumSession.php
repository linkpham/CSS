<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Curriculum Session Model - Zeus Core Database
 * Tracks which lessons/sessions are assigned to each curriculum lecture
 * 
 * @property int $id
 * @property int $curriculum_lecture_id
 * @property string $session_type
 * @property string $session_item_id
 * @property int $clazz_id
 * @property string $status
 * @property \DateTime $created_at
 */
class CurriculumSession extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_curriculum_session';
    public $timestamps = false;

    // Status constants
    const STATUS_UPCOMING = '';
    const STATUS_COMPLETED = 'completed';
    const STATUS_INCOMPLETE = 'incomplete';

    // Session type constants
    const TYPE_LESSON = 'lesson';
    const TYPE_GROUP_CLASS = 'group_class';

    protected $fillable = [
        'curriculum_lecture_id',
        'session_type',
        'session_item_id',
        'clazz_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for incomplete sessions
     */
    public function scopeIncomplete($query)
    {
        return $query->where('status', self::STATUS_INCOMPLETE);
    }

    /**
     * Scope for upcoming sessions (empty status)
     */
    public function scopeUpcoming($query)
    {
        return $query->where(function($q) {
            $q->where('status', '')
              ->orWhereNull('status');
        });
    }

    /**
     * Scope for lesson type
     */
    public function scopeLessons($query)
    {
        return $query->where('session_type', self::TYPE_LESSON);
    }

    /**
     * Scope for group class type
     */
    public function scopeGroupClasses($query)
    {
        return $query->where('session_type', self::TYPE_GROUP_CLASS);
    }

    /**
     * Relationship: Lecture
     */
    public function lecture()
    {
        return $this->belongsTo(CurriculumLecture::class, 'curriculum_lecture_id', 'id');
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_INCOMPLETE => 'Chưa hoàn thành',
            '', null => 'Sắp học',
            default => 'Unknown',
        };
    }
}
