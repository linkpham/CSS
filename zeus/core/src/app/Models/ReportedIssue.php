<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ReportedIssue Model - Zeus Core Database
 */
class ReportedIssue extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_reported_issues';
    protected $primaryKey = 'repiss_id';
    public $timestamps = false;

    // Issue Status
    public const STATUS_PROGRESS = 1;
    public const STATUS_RESOLVED = 2;
    public const STATUS_ESCALATED = 3;
    public const STATUS_CLOSED = 4;

    protected $casts = [
        'repiss_reported_on' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('repiss_reported_on', now()->toDateString());
    }

    public function scopeInProgress($query)
    {
        return $query->where('repiss_status', self::STATUS_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('repiss_status', self::STATUS_RESOLVED);
    }
}
