<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TeacherStat Model - Zeus Core Database
 */
class TeacherStat extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_teacher_stats';
    protected $primaryKey = 'testat_user_id';
    public $timestamps = false;

    protected $casts = [
        'testat_ratings' => 'float',
        'testat_minprice' => 'float',
        'testat_maxprice' => 'float',
    ];

    public function scopeWithAvailability($query)
    {
        return $query->where('testat_availability', 1);
    }

    public function scopeCompleteProfile($query)
    {
        return $query->where('testat_lessons', '>', 0)
            ->where('testat_students', '>', 0);
    }
}
