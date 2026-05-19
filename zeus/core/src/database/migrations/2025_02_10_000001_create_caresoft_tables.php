<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * CareSoft cache tables - synced from CareSoft API
     */
    public function up(): void
    {
        // Agents table
        if (!Schema::hasTable('cs_agents')) {
            Schema::create('cs_agents', function (Blueprint $table) {
                $table->bigInteger('id')->primary();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('phone_no')->nullable();
                $table->string('agent_id')->nullable();
                $table->bigInteger('group_id')->nullable()->index();
                $table->string('group_name')->nullable();
                $table->integer('role_id')->nullable();
                $table->string('call_status')->nullable();
                $table->string('ticket_status')->nullable();
                $table->string('chat_status')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Groups table
        if (!Schema::hasTable('cs_groups')) {
            Schema::create('cs_groups', function (Blueprint $table) {
                $table->bigInteger('group_id')->primary();
                $table->string('group_name')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Services table
        if (!Schema::hasTable('cs_services')) {
            Schema::create('cs_services', function (Blueprint $table) {
                $table->bigInteger('service_id')->primary();
                $table->string('service_name')->nullable();
                $table->string('service_type')->nullable();
                $table->integer('type')->nullable();
                $table->text('detail')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Tickets table
        if (!Schema::hasTable('cs_tickets')) {
            Schema::create('cs_tickets', function (Blueprint $table) {
                $table->bigInteger('ticket_id')->primary();
                $table->bigInteger('ticket_no')->nullable()->index();
                $table->string('subject')->nullable();
                $table->string('ticket_status')->nullable()->index();
                $table->string('ticket_priority')->nullable();
                $table->string('ticket_source')->nullable();
                $table->bigInteger('requester_id')->nullable();
                $table->bigInteger('assignee_id')->nullable()->index();
                $table->bigInteger('group_id')->nullable()->index();
                $table->bigInteger('service_id')->nullable();
                $table->integer('satisfaction')->nullable();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Calls table
        if (!Schema::hasTable('cs_calls')) {
            Schema::create('cs_calls', function (Blueprint $table) {
                $table->id();
                $table->string('call_id')->unique();
                $table->string('caller')->nullable();
                $table->string('called')->nullable();
                $table->bigInteger('user_id')->nullable();
                $table->string('agent_id')->nullable();
                $table->bigInteger('group_id')->nullable()->index();
                $table->integer('call_type')->nullable();
                $table->string('call_status')->nullable()->index();
                $table->timestamp('start_time')->nullable()->index();
                $table->timestamp('end_time')->nullable();
                $table->string('wait_time')->nullable();
                $table->string('hold_time')->nullable();
                $table->string('talk_time')->nullable();
                $table->string('end_status')->nullable();
                $table->bigInteger('ticket_id')->nullable();
                $table->string('missed_reason')->nullable();
                $table->bigInteger('service_id')->nullable();
                $table->bigInteger('last_user_id')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Chats table
        if (!Schema::hasTable('cs_chats')) {
            Schema::create('cs_chats', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('ticket_id')->nullable();
                $table->bigInteger('ticket_no')->nullable();
                $table->bigInteger('customer_id')->nullable();
                $table->string('conversation_id')->unique();
                $table->string('cus_name')->nullable();
                $table->string('cus_phone')->nullable();
                $table->string('cus_email')->nullable();
                $table->timestamp('start_time')->nullable()->index();
                $table->timestamp('end_time')->nullable();
                $table->integer('chat_duration')->nullable();
                $table->string('chat_status')->nullable()->index();
                $table->string('agent_name')->nullable();
                $table->string('agent_email')->nullable();
                $table->string('group_name')->nullable();
                $table->bigInteger('service_id')->nullable();
                $table->integer('conversation_type')->default(0);
                $table->timestamp('synced_at')->nullable();
            });
        }

        // Sync logs table
        if (!Schema::hasTable('cs_sync_logs')) {
            Schema::create('cs_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('sync_type')->index();
                $table->integer('record_count')->default(0);
                $table->text('error')->nullable();
                $table->decimal('elapsed_seconds', 10, 2)->nullable();
                $table->timestamp('synced_at')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_sync_logs');
        Schema::dropIfExists('cs_chats');
        Schema::dropIfExists('cs_calls');
        Schema::dropIfExists('cs_tickets');
        Schema::dropIfExists('cs_services');
        Schema::dropIfExists('cs_groups');
        Schema::dropIfExists('cs_agents');
    }
};
