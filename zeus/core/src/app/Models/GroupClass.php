<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GroupClass Model - Zeus Core Database
 */
class GroupClass extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_group_classes';
    protected $primaryKey = 'grpcls_id';
    public $timestamps = false;

    // Group Class Status
    public const STATUS_SCHEDULED = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_CANCELLED = 3;

    protected $casts = [
        'grpcls_start_datetime' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('grpcls_start_datetime', now()->toDateString());
    }

    public function scopeScheduled($query)
    {
        return $query->where('grpcls_status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('grpcls_status', self::STATUS_COMPLETED);
    }

    public function getBookingRateAttribute(): float
    {
        if ($this->grpcls_total_seats == 0) {
            return 0;
        }
        return round(($this->grpcls_booked_seats / $this->grpcls_total_seats) * 100, 2);
    }
}
