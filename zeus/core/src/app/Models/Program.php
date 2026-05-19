<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Program Model - Zeus Core Database
 * Represents a learning program/curriculum bundle
 * 
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $type
 * @property string $type_item_id
 * @property string $tag
 * @property int $parent
 * @property int $after
 * @property string $status
 * @property string $video
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Program extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_program';
    public $timestamps = true;

    // Status constants
    const STATUS_PUBLISHED = 'published';
    const STATUS_DRAFT = 'draft';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'description',
        'type',
        'type_item_id',
        'tag',
        'parent',
        'after',
        'status',
        'video',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for published programs
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Relationship: Users enrolled in this program
     */
    public function users()
    {
        return $this->hasMany(ProgramUser::class, 'program_id', 'id');
    }
}
