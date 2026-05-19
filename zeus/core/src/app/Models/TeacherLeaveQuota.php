<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveQuota extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_leave_quotas';
    protected $primaryKey = 'tlq_id';
    public $timestamps = false;

    protected $fillable = [
        'tlq_teacher_id',
        'tlq_year',
        'tlq_quarter',
        'tlq_month',
        'tlq_join_date',
        'tlq_months_worked',
        'tlq_base_quota',
        'tlq_used_days',
        'tlq_used_this_month',
    ];

    /**
     * Relationship to teacher (user)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'tlq_teacher_id', 'user_id');
    }

    /**
     * Get quota for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('tlq_year', now()->year)
            ->where('tlq_month', now()->month);
    }

    /**
     * Get quota for current year
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('tlq_year', now()->year);
    }

    /**
     * Get quota for a specific teacher
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('tlq_teacher_id', $teacherId);
    }
}
