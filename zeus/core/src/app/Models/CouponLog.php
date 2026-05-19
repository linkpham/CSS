<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Coupon Log Model
 * 
 * Records all coupon-related activities (apply, release, etc.)
 * Table: tbl_coupon_logs
 */
class CouponLog extends Model
{
    protected $table = 'tbl_coupon_logs';
    protected $primaryKey = 'clog_id';
    
    public $timestamps = false;
    
    /**
     * Action type constants
     * Based on clog_action values from data
     */
    public const ACTION_APPLY = 4;    // Coupon applied to order
    public const ACTION_RELEASE = 5;  // Coupon released from order
    
    /**
     * Actor type constants
     */
    public const ACTOR_USER = 'user';
    public const ACTOR_ADMIN = 'admin';
    public const ACTOR_SYSTEM = 'system';
    
    protected $fillable = [
        'clog_coupon_id',
        'clog_action',
        'clog_actor_type',
        'clog_actor_id',
        'clog_beneficiary_id',
        'clog_related_object_id',
        'clog_related_object_type',
        'clog_note',
        'clog_details',
        'clog_created_at',
    ];
    
    protected $casts = [
        'clog_details' => 'array',
        'clog_created_at' => 'datetime',
    ];
    
    /**
     * Get the coupon
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'clog_coupon_id', 'coupon_id');
    }
    
    /**
     * Get the actor (user who performed the action)
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'clog_actor_id', 'user_id');
    }
    
    /**
     * Get the beneficiary (user who received the benefit)
     */
    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'clog_beneficiary_id', 'user_id');
    }
    
    /**
     * Scope for apply actions
     */
    public function scopeApplied($query)
    {
        return $query->where('clog_action', self::ACTION_APPLY);
    }
    
    /**
     * Scope for release actions
     */
    public function scopeReleased($query)
    {
        return $query->where('clog_action', self::ACTION_RELEASE);
    }
    
    /**
     * Scope for logs within a date range
     */
    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('clog_created_at', [$start, $end]);
    }
    
    /**
     * Scope for logs by actor type
     */
    public function scopeByActorType($query, $type)
    {
        return $query->where('clog_actor_type', $type);
    }
    
    /**
     * Get action labels
     */
    public static function getActionLabels(): array
    {
        return [
            self::ACTION_APPLY => 'Áp dụng mã',
            self::ACTION_RELEASE => 'Hủy áp dụng mã',
        ];
    }
    
    /**
     * Get action label for this log
     */
    public function getActionLabelAttribute(): string
    {
        return self::getActionLabels()[$this->clog_action] ?? 'Unknown';
    }
    
    /**
     * Get order ID from details
     */
    public function getOrderIdAttribute(): ?int
    {
        return $this->clog_details['order_id'] ?? null;
    }
}
