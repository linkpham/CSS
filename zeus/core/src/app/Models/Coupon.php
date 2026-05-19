<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Coupon Model
 * 
 * Represents discount coupons/vouchers in the system.
 * Table: tbl_coupons
 * 
 * Discount Types:
 * - 1: Percentage discount (%)
 * - 2: Fixed amount discount (VND)
 */
class Coupon extends Model
{
    protected $table = 'tbl_coupons';
    protected $primaryKey = 'coupon_id';
    
    const CREATED_AT = 'coupon_created';
    const UPDATED_AT = 'coupon_updated';
    
    /**
     * Discount type constants
     */
    public const DISCOUNT_TYPE_PERCENT = 1;
    public const DISCOUNT_TYPE_FIXED = 2;
    
    protected $fillable = [
        'coupon_identifier',
        'coupon_code',
        'coupon_min_order',
        'coupon_max_discount',
        'coupon_discount_type',
        'coupon_discount_value',
        'coupon_max_uses',
        'coupon_user_uses',
        'coupon_used_uses',
        'coupon_start_date',
        'coupon_end_date',
        'coupon_active',
    ];
    
    protected $casts = [
        'coupon_min_order' => 'decimal:2',
        'coupon_max_discount' => 'decimal:2',
        'coupon_discount_value' => 'decimal:2',
        'coupon_start_date' => 'datetime',
        'coupon_end_date' => 'datetime',
        'coupon_active' => 'boolean',
    ];
    
    /**
     * Get coupon usage history
     */
    public function history()
    {
        return $this->hasMany(CouponHistory::class, 'couhis_coupon_id', 'coupon_id');
    }
    
    /**
     * Get coupon logs
     */
    public function logs()
    {
        return $this->hasMany(CouponLog::class, 'clog_coupon_id', 'coupon_id');
    }
    
    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('coupon_active', 1);
    }
    
    /**
     * Scope for currently valid coupons (within date range)
     */
    public function scopeValid($query)
    {
        $now = now();
        return $query->where('coupon_start_date', '<=', $now)
                     ->where('coupon_end_date', '>=', $now);
    }
    
    /**
     * Scope for expired coupons
     */
    public function scopeExpired($query)
    {
        return $query->where('coupon_end_date', '<', now());
    }
    
    /**
     * Scope for upcoming coupons (not yet started)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('coupon_start_date', '>', now());
    }
    
    /**
     * Check if coupon has remaining uses
     */
    public function hasRemainingUses(): bool
    {
        return $this->coupon_used_uses < $this->coupon_max_uses;
    }
    
    /**
     * Get remaining uses count
     */
    public function getRemainingUsesAttribute(): int
    {
        return max(0, $this->coupon_max_uses - $this->coupon_used_uses);
    }
    
    /**
     * Get usage rate percentage
     */
    public function getUsageRateAttribute(): float
    {
        if ($this->coupon_max_uses == 0) return 0;
        return round(($this->coupon_used_uses / $this->coupon_max_uses) * 100, 1);
    }
    
    /**
     * Get discount type labels
     */
    public static function getDiscountTypeLabels(): array
    {
        return [
            self::DISCOUNT_TYPE_PERCENT => 'Phần trăm (%)',
            self::DISCOUNT_TYPE_FIXED => 'Số tiền cố định (VND)',
        ];
    }
    
    /**
     * Get discount type label for this coupon
     */
    public function getDiscountTypeLabelAttribute(): string
    {
        return self::getDiscountTypeLabels()[$this->coupon_discount_type] ?? 'Unknown';
    }
}
