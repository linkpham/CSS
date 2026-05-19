<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Login Log Model - Zeus Core Database
 * Tracks user login activities from tbl_sales_lead_logs
 * 
 * @property int $sllg_id
 * @property string $sllg_actor_type
 * @property string $sllg_actor_id
 * @property int|null $sllg_user_id
 * @property string $sllg_action_type
 * @property string $sllg_action
 * @property array|null $sllg_metadata
 * @property \DateTime $sllg_occurred_at
 * @property string|null $sllg_source
 * @property int $sllg_status
 * @property \DateTime $sllg_created_at
 * @property \DateTime|null $sllg_updated_at
 */
class UserLoginLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_sales_lead_logs';
    protected $primaryKey = 'sllg_id';
    public $timestamps = false;

    // Action types for login
    const ACTION_LOGIN_SUCCESS = 'USER_LOGIN_SUCCESS';
    const ACTION_LOGIN_FAILED = 'USER_LOGIN_FAILED';
    const ACTION_REGISTERED = 'USER_REGISTERED';

    // Device types based on metadata analysis (extracted from ci_processed_sessions Device field)
    const DEVICE_UNKNOWN = 0;
    const DEVICE_WEB = 1;
    const DEVICE_IOS_APP = 2;
    const DEVICE_ANDROID_APP = 3;
    const DEVICE_WEB_MOBILE = 4;
    const DEVICE_DESKTOP_APP = 7;
    const DEVICE_TABLET = 9;
    const DEVICE_IPAD = 10;
    const DEVICE_ANDROID_TABLET = 11;

    protected $fillable = [
        'sllg_actor_type',
        'sllg_actor_id',
        'sllg_user_id',
        'sllg_action_type',
        'sllg_action',
        'sllg_metadata',
        'sllg_occurred_at',
        'sllg_source',
        'sllg_status',
    ];

    protected $casts = [
        'sllg_metadata' => 'array',
        'sllg_occurred_at' => 'datetime',
        'sllg_created_at' => 'datetime',
        'sllg_updated_at' => 'datetime',
    ];

    /**
     * Scope for successful login events
     */
    public function scopeLoginSuccess($query)
    {
        return $query->where('sllg_action', self::ACTION_LOGIN_SUCCESS);
    }

    /**
     * Scope for failed login events  
     */
    public function scopeLoginFailed($query)
    {
        return $query->where('sllg_action', self::ACTION_LOGIN_FAILED);
    }

    /**
     * Scope for today's events
     */
    public function scopeToday($query)
    {
        return $query->whereDate('sllg_occurred_at', today());
    }

    /**
     * Scope for this week's events
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sllg_occurred_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for this month's events
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sllg_occurred_at', now()->month)
                     ->whereYear('sllg_occurred_at', now()->year);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sllg_occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope for actor type (user type)
     */
    public function scopeActorType($query, string $type)
    {
        return $query->where('sllg_actor_type', $type);
    }

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'sllg_user_id', 'user_id');
    }

    /**
     * Get device label from device code
     */
    public static function getDeviceLabel(int $deviceCode): string
    {
        return match ($deviceCode) {
            self::DEVICE_UNKNOWN => 'Không xác định',
            self::DEVICE_WEB => 'Web',
            self::DEVICE_IOS_APP => 'iOS App',
            self::DEVICE_ANDROID_APP => 'Android App',
            self::DEVICE_WEB_MOBILE => 'Web Mobile',
            self::DEVICE_DESKTOP_APP => 'Desktop App',
            self::DEVICE_TABLET => 'Tablet',
            self::DEVICE_IPAD => 'iPad',
            self::DEVICE_ANDROID_TABLET => 'Android Tablet',
            default => 'Khác',
        };
    }

    /**
     * Get action label
     */
    public static function getActionLabel(string $action): string
    {
        return match ($action) {
            self::ACTION_LOGIN_SUCCESS => 'Đăng nhập thành công',
            self::ACTION_LOGIN_FAILED => 'Đăng nhập thất bại',
            self::ACTION_REGISTERED => 'Đăng ký mới',
            default => $action,
        };
    }
}
