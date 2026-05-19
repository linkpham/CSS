<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Transaction Model - Zeus Core Database
 */
class Transaction extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_user_transactions';
    protected $primaryKey = 'usrtxn_id';
    public $timestamps = false;

    // Transaction Types
    public const TYPE_LEARNER_REFUND = 8;
    public const TYPE_TEACHER_PAYMENT = 9;
    public const TYPE_MONEY_WITHDRAW = 10;
    public const TYPE_MONEY_DEPOSIT = 11;
    public const TYPE_REWARD_POINTS_REDEEMED = 15;
    public const TYPE_AFFILIATE_COMMISSION = 16;

    protected $casts = [
        'usrtxn_amount' => 'float',
        'usrtxn_datetime' => 'datetime',
    ];

    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_LEARNER_REFUND => 'Hoàn tiền học sinh',
            self::TYPE_TEACHER_PAYMENT => 'Thanh toán giáo viên',
            self::TYPE_MONEY_WITHDRAW => 'Rút tiền',
            self::TYPE_MONEY_DEPOSIT => 'Nạp tiền',
            self::TYPE_REWARD_POINTS_REDEEMED => 'Đổi điểm thưởng',
            self::TYPE_AFFILIATE_COMMISSION => 'Hoa hồng affiliate',
        ];
    }

    public function scopeDeposits($query)
    {
        return $query->where('usrtxn_type', self::TYPE_MONEY_DEPOSIT);
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('usrtxn_type', self::TYPE_MONEY_WITHDRAW);
    }

    public function scopeTeacherPayments($query)
    {
        return $query->where('usrtxn_type', self::TYPE_TEACHER_PAYMENT);
    }
}
