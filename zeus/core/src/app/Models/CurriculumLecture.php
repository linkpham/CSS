<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Curriculum Lecture Model - Zeus Core Database
 * 
 * @property int $id
 * @property int $curriculum_id
 * @property int $curriculum_section_id
 * @property string $title
 * @property string $description
 * @property int $after
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class CurriculumLecture extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_curriculum_lecture';
    public $timestamps = true;

    protected $fillable = [
        'curriculum_id',
        'curriculum_section_id',
        'title',
        'description',
        'after',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Curriculum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id', 'id');
    }

    /**
     * Relationship: Section
     */
    public function section()
    {
        return $this->belongsTo(CurriculumSection::class, 'curriculum_section_id', 'id');
    }

    /**
     * Relationship: Sessions
     */
    public function sessions()
    {
        return $this->hasMany(CurriculumSession::class, 'curriculum_lecture_id', 'id');
    }
}
