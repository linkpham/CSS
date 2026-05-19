<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Model - Zeus Core Database
 * 
 * @property int $user_id
 * @property string $user_first_name
 * @property string $user_last_name
 * @property string $user_email
 * @property int $user_is_teacher
 * @property int $user_is_parent
 * @property int $user_is_affiliate
 * @property int $user_active
 * @property \DateTime $user_created
 * @property \DateTime|null $user_verified
 * @property \DateTime|null $user_deleted
 */
class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_first_name',
        'user_last_name',
        'user_email',
        'user_is_teacher',
        'user_is_parent',
        'user_is_affiliate',
        'user_active',
    ];

    protected $casts = [
        'user_created' => 'datetime',
        'user_verified' => 'datetime',
        'user_deleted' => 'datetime',
    ];

    /**
     * Scope for active users (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('user_deleted');
    }

    /**
     * Scope for teachers
     */
    public function scopeTeachers($query)
    {
        return $query->where('user_is_teacher', 1);
    }

    /**
     * Scope for learners (students)
     * Includes users where user_is_teacher = 0 OR user_is_teacher IS NULL
     * SQL: SELECT COUNT(*) FROM tbl_users WHERE (user_is_teacher = 0 OR user_is_teacher IS NULL) AND user_deleted IS NULL
     */
    public function scopeLearners($query)
    {
        return $query->where(function ($q) {
            $q->where('user_is_teacher', 0)
              ->orWhereNull('user_is_teacher');
        });
    }

    /**
     * Scope for parents
     */
    public function scopeParents($query)
    {
        return $query->where('user_is_parent', 1);
    }

    /**
     * Scope for affiliates
     */
    public function scopeAffiliates($query)
    {
        return $query->where('user_is_affiliate', 1);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('user_verified');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->user_first_name . ' ' . $this->user_last_name);
    }
}
