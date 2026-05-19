<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ExportJob Model - Dashboard local database
 * 
 * Phase 41: Model for tracking background export jobs
 * Uses the local dashboard database (not Zeus Core)
 * 
 * @property int $id
 * @property string $export_id Unique export identifier (exp_xxx_timestamp)
 * @property int|null $user_id User who initiated the export
 * @property string $type Type of export (usage_report, session_stats, etc.)
 * @property string $status pending|processing|completed|failed
 * @property int $progress Progress percentage 0-100
 * @property string|null $message Current status message
 * @property \Carbon\Carbon|null $period_start Report period start date
 * @property \Carbon\Carbon|null $period_end Report period end date
 * @property string|null $filename Generated file name
 * @property int|null $record_count Number of records in export
 * @property string|null $error Error message if failed
 * @property \Carbon\Carbon|null $started_at When processing started
 * @property \Carbon\Carbon|null $completed_at When processing completed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ExportJob extends Model
{
    /**
     * Use the default connection (dashboard local database)
     * This is configured in .env as DB_DATABASE=zeus_dashboard
     */
    protected $table = 'export_jobs';
    
    protected $fillable = [
        'export_id',
        'user_id',
        'type',
        'status',
        'progress',
        'message',
        'period_start',
        'period_end',
        'filename',
        'record_count',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'record_count' => 'integer',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Type constants
    public const TYPE_USAGE_REPORT = 'usage_report';
    public const TYPE_SESSION_STATS = 'session_stats';

    /**
     * Scope for pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for user's exports
     */
    public function scopeForUser($query, ?int $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    /**
     * Check if job is still running
     */
    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if job is complete
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if job failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get download URL for completed export
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if ($this->isComplete() && $this->filename) {
            return "/api/download-export/{$this->export_id}";
        }
        return null;
    }

    /**
     * Update progress with message
     */
    public function updateProgress(int $progress, string $message): self
    {
        $this->update([
            'progress' => $progress,
            'message' => $message,
        ]);
        return $this;
    }

    /**
     * Mark job as processing
     */
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(string $filename, int $recordCount): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'message' => 'Xuất báo cáo thành công!',
            'filename' => $filename,
            'record_count' => $recordCount,
            'completed_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $error): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'progress' => 0,
            'message' => 'Lỗi: ' . $error,
            'error' => $error,
            'completed_at' => now(),
        ]);
        return $this;
    }

    /**
     * Get formatted period string
     */
    public function getPeriodFormattedAttribute(): array
    {
        return [
            'start' => $this->period_start?->format('d/m/Y'),
            'end' => $this->period_end?->format('d/m/Y'),
        ];
    }

    /**
     * Convert to API response format
     */
    public function toApiResponse(): array
    {
        return [
            'export_id' => $this->export_id,
            'status' => $this->status,
            'progress' => $this->progress,
            'message' => $this->message,
            'period' => $this->period_formatted,
            'filename' => $this->filename,
            'record_count' => $this->record_count,
            'download_url' => $this->download_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'error' => $this->error,
        ];
    }
}
