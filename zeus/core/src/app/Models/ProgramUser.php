<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Program User Model - Zeus Core Database
 * Tracks user enrollment in learning programs
 * 
 * @property int $id
 * @property int $program_id
 * @property int $user_id
 * @property int $teacher_id
 * @property \DateTime $created_at
 */
class ProgramUser extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_program_user';
    public $timestamps = false;

    protected $fillable = [
        'program_id',
        'user_id',
        'teacher_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: Program
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    /**
     * Relationship: User (Learner)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relationship: Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id', 'user_id');
    }
}
