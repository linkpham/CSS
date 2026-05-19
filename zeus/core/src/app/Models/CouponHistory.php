<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Coupon History Model
 * 
 * Records when coupons are applied to orders.
 * Table: tbl_coupons_history
 */
class CouponHistory extends Model
{
    protected $table = 'tbl_coupons_history';
    protected $primaryKey = 'couhis_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'couhis_order_id',
        'couhis_coupon_id',
        'couhis_coupon',
        'couhis_created',
        'couhis_released',
    ];
    
    protected $casts = [
        'couhis_coupon' => 'array',
        'couhis_created' => 'datetime',
        'couhis_released' => 'datetime',
    ];
    
    /**
     * Get the coupon
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'couhis_coupon_id', 'coupon_id');
    }
    
    /**
     * Get the order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'couhis_order_id', 'order_id');
    }
    
    /**
     * Scope for applied (not released) coupons
     */
    public function scopeApplied($query)
    {
        return $query->whereNull('couhis_released');
    }
    
    /**
     * Scope for released coupons
     */
    public function scopeReleased($query)
    {
        return $query->whereNotNull('couhis_released');
    }
    
    /**
     * Scope for history created within a date range
     */
    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('couhis_created', [$start, $end]);
    }
    
    /**
     * Get the discount amount from stored coupon data
     */
    public function getDiscountAmountAttribute(): float
    {
        $couponData = $this->couhis_coupon;
        return (float) ($couponData['coupon_discount'] ?? 0);
    }
}
