<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OrderLessonExtra Model - Zeus Core Database
 * 
 * Stores additional data about lesson sessions from ClassIn:
 * - Acceptance code (12 = success)
 * - Teacher/Student first join and last leave times
 * - Overlapped duration (time both participants were in class)
 * 
 * @property int $ole_id
 * @property int $ole_ordles_id
 * @property int $ole_acceptance_code
 * @property string|null $ole_teacher_first_join
 * @property string|null $ole_teacher_last_leave
 * @property string|null $ole_student_first_join
 * @property string|null $ole_student_last_leave
 * @property int $ole_overlapped_duration
 * @property int|null $ole_processed_lesson_id
 */
class OrderLessonExtra extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_order_lessons_extras';
    protected $primaryKey = 'ole_id';
    public $timestamps = false;

    // Acceptance codes - full list from code.xlsx
    public const ACCEPTANCE_SUCCESS = 12; // Session was successful (GV ≥ 2/3 thời gian + HV bình thường)

    /**
     * Full acceptance codes list with descriptions (Vietnamese)
     * Format: [code => ['session' => description, 'teacher' => status, 'student' => status]]
     */
    public const ACCEPTANCE_CODES = [
        1  => ['session' => 'Có diễn ra', 'teacher' => 'No show (không có mặt)', 'student' => 'No show'],
        2  => ['session' => 'Có diễn ra', 'teacher' => 'No show (không có mặt)', 'student' => 'Không đủ 1/2 thời gian học'],
        3  => ['session' => 'Có diễn ra', 'teacher' => 'No show (không có mặt)', 'student' => 'Bình thường'],
        4  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt dưới 1/2 thời gian', 'student' => 'No show'],
        5  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt dưới 1/2 thời gian', 'student' => 'Không đủ 1/2 thời gian học'],
        6  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt dưới 1/2 thời gian', 'student' => 'Bình thường'],
        7  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 1/2 đến 2/3 thời gian', 'student' => 'No show'],
        8  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 1/2 đến 2/3 thời gian', 'student' => 'Không đủ 1/2 thời gian học'],
        9  => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 1/2 đến 2/3 thời gian', 'student' => 'Bình thường'],
        10 => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 2/3 thời gian trở lên', 'student' => 'No show'],
        11 => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 2/3 thời gian trở lên', 'student' => 'Không đủ 1/2 thời gian học'],
        12 => ['session' => 'Có diễn ra', 'teacher' => 'Tổng thời gian có mặt từ 2/3 thời gian trở lên', 'student' => 'Bình thường'],
        13 => ['session' => 'Bị hủy', 'teacher' => 'Xin nghỉ đúng quy định', 'student' => 'Không có nhu cầu nghỉ'],
        14 => ['session' => 'Bị hủy', 'teacher' => 'Xin nghỉ sai quy định', 'student' => 'Không có nhu cầu nghỉ'],
        15 => ['session' => 'Bị hủy', 'teacher' => 'Không có nhu cầu nghỉ', 'student' => 'Xin nghỉ đúng quy định'],
        16 => ['session' => 'Bị hủy', 'teacher' => 'Không có nhu cầu nghỉ', 'student' => 'Xin nghỉ sai quy định'],
        17 => ['session' => 'Bị hủy', 'teacher' => 'Lỗi kỹ thuật / bất khả kháng', 'student' => 'Lỗi kỹ thuật / bất khả kháng'],
    ];

    /**
     * Get acceptance code description
     */
    public static function getAcceptanceCodeDescription(int $code): ?array
    {
        return self::ACCEPTANCE_CODES[$code] ?? null;
    }

    /**
     * Get short label for acceptance code
     */
    public static function getAcceptanceCodeLabel(int $code): string
    {
        return match ($code) {
            1 => 'GV No-show + HV No-show',
            2 => 'GV No-show + HV < 1/2',
            3 => 'GV No-show + HV bình thường',
            4 => 'GV < 1/2 + HV No-show',
            5 => 'GV < 1/2 + HV < 1/2',
            6 => 'GV < 1/2 + HV bình thường',
            7 => 'GV 1/2-2/3 + HV No-show',
            8 => 'GV 1/2-2/3 + HV < 1/2',
            9 => 'GV 1/2-2/3 + HV bình thường',
            10 => 'GV ≥ 2/3 + HV No-show',
            11 => 'GV ≥ 2/3 + HV < 1/2',
            12 => '✓ Thành công (GV ≥ 2/3 + HV bình thường)',
            13 => 'Hủy: GV nghỉ đúng quy định',
            14 => 'Hủy: GV nghỉ sai quy định',
            15 => 'Hủy: HV nghỉ đúng quy định',
            16 => 'Hủy: HV nghỉ sai quy định',
            17 => 'Hủy: Lỗi kỹ thuật/bất khả kháng',
            default => 'Không xác định',
        };
    }

    /**
     * Get all acceptance codes list with labels
     */
    public static function getAllAcceptanceCodesWithLabels(): array
    {
        $result = [];
        foreach (self::ACCEPTANCE_CODES as $code => $info) {
            $result[$code] = [
                'code' => $code,
                'label' => self::getAcceptanceCodeLabel($code),
                'session' => $info['session'],
                'teacher' => $info['teacher'],
                'student' => $info['student'],
                'is_success' => $code === self::ACCEPTANCE_SUCCESS,
            ];
        }
        return $result;
    }

    protected $casts = [
        'ole_acceptance_code' => 'integer',
        'ole_overlapped_duration' => 'integer',
        'ole_teacher_first_join' => 'datetime',
        'ole_teacher_last_leave' => 'datetime',
        'ole_student_first_join' => 'datetime',
        'ole_student_last_leave' => 'datetime',
    ];

    /**
     * Get the order lesson this extra belongs to
     */
    public function orderLesson()
    {
        return $this->belongsTo(OrderLesson::class, 'ole_ordles_id', 'ordles_id');
    }

    /**
     * Scope: successful sessions (acceptance_code = 12)
     */
    public function scopeSuccessful($query)
    {
        return $query->where('ole_acceptance_code', self::ACCEPTANCE_SUCCESS);
    }

    /**
     * Scope: failed sessions (acceptance_code != 12)
     */
    public function scopeFailed($query)
    {
        return $query->where('ole_acceptance_code', '!=', self::ACCEPTANCE_SUCCESS);
    }

    /**
     * Scope: teacher no-show (teacher didn't join)
     */
    public function scopeTeacherNoShow($query)
    {
        return $query->whereNull('ole_teacher_first_join');
    }

    /**
     * Scope: student no-show (student didn't join)
     */
    public function scopeStudentNoShow($query)
    {
        return $query->whereNull('ole_student_first_join');
    }
}
