<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 41: Create export_jobs table for persistent background export tracking
 * 
 * This table replaces Redis cache for export job tracking, allowing:
 * - Users to close browser and come back later
 * - Persistent job history
 * - Better reliability for long-running exports
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('export_id', 64)->unique()->comment('Unique export identifier (exp_xxx_timestamp)');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who initiated the export');
            $table->string('type', 50)->default('usage_report')->comment('Type of export (usage_report, session_stats, etc.)');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('progress')->default(0)->comment('Progress percentage 0-100');
            $table->string('message', 255)->nullable()->comment('Current status message');
            $table->date('period_start')->nullable()->comment('Report period start date');
            $table->date('period_end')->nullable()->comment('Report period end date');
            $table->string('filename', 255)->nullable()->comment('Generated file name');
            $table->unsignedInteger('record_count')->nullable()->comment('Number of records in export');
            $table->text('error')->nullable()->comment('Error message if failed');
            $table->timestamp('started_at')->nullable()->comment('When processing started');
            $table->timestamp('completed_at')->nullable()->comment('When processing completed');
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('created_at'); // For cleanup of old exports
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
