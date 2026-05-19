<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Order Model - Zeus Core Database
 * 
 * @property int $order_id
 * @property int $order_type
 * @property int $order_status
 * @property int $order_payment_status
 * @property float $order_total_amount
 * @property float $order_discount_value
 * @property float $order_reward_value
 * @property \DateTime $order_addedon
 */
class Order extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_orders';
    protected $primaryKey = 'order_id';
    public $timestamps = false;

    // Order Types
    public const TYPE_LESSON = 1;
    public const TYPE_SUBSCR = 2;
    public const TYPE_GCLASS = 3;
    public const TYPE_PACKGE = 4;
    public const TYPE_COURSE = 5;
    public const TYPE_WALLET = 6;
    public const TYPE_GFTCRD = 7;
    public const TYPE_SUBPLAN = 18;
    public const TYPE_ZCOUPON = 20;
    public const TYPE_RESERVATION = 21;

    // Order Status
    public const STATUS_INPROCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_CANCELLED = 3;

    // Payment Status
    public const PAYMENT_UNPAID = 0;
    public const PAYMENT_PAID = 1;

    protected $casts = [
        'order_addedon' => 'datetime',
        'order_total_amount' => 'float',
        'order_discount_value' => 'float',
        'order_reward_value' => 'float',
    ];

    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_LESSON => 'Lesson',
            self::TYPE_SUBSCR => 'Subscription',
            self::TYPE_GCLASS => 'Group Class',
            self::TYPE_PACKGE => 'Package',
            self::TYPE_COURSE => 'Course',
            self::TYPE_WALLET => 'Wallet Deposit',
            self::TYPE_GFTCRD => 'Giftcard',
            self::TYPE_SUBPLAN => 'Subscription Plan',
            self::TYPE_ZCOUPON => 'ZCoupon',
            self::TYPE_RESERVATION => 'Reservation',
        ];
    }

    public function scopePaid($query)
    {
        return $query->where('order_payment_status', self::PAYMENT_PAID);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', self::STATUS_COMPLETED);
    }
}
