<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Curriculum Model - Zeus Core Database
 * 
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int $lang_id
 * @property string $status
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Curriculum extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_curriculum';
    public $timestamps = true;

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'title',
        'description',
        'lang_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for active curriculum
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Relationship: Sections
     */
    public function sections()
    {
        return $this->hasMany(CurriculumSection::class, 'curriculum_id', 'id');
    }

    /**
     * Relationship: Lectures
     */
    public function lectures()
    {
        return $this->hasMany(CurriculumLecture::class, 'curriculum_id', 'id');
    }

    /**
     * Relationship: Sessions
     */
    public function sessions()
    {
        return $this->hasManyThrough(
            CurriculumSession::class,
            CurriculumLecture::class,
            'curriculum_id',
            'curriculum_lecture_id',
            'id',
            'id'
        );
    }
}
