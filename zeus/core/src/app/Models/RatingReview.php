<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RatingReview Model - Zeus Core Database
 */
class RatingReview extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_rating_reviews';
    protected $primaryKey = 'ratrev_id';
    public $timestamps = false;

    // Status
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_DECLINED = 2;

    protected $casts = [
        'ratrev_overall' => 'float',
    ];

    public function scopePending($query)
    {
        return $query->where('ratrev_status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('ratrev_status', self::STATUS_APPROVED);
    }
}
