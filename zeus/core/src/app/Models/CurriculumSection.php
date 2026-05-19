<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Curriculum Section Model - Zeus Core Database
 * 
 * @property int $id
 * @property int $curriculum_id
 * @property string $title
 * @property string $description
 * @property int $after
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class CurriculumSection extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_curriculum_section';
    public $timestamps = true;

    protected $fillable = [
        'curriculum_id',
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
     * Relationship: Lectures
     */
    public function lectures()
    {
        return $this->hasMany(CurriculumLecture::class, 'curriculum_section_id', 'id');
    }
}
