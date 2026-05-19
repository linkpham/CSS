<?php

namespace App\Console\Commands;

use App\Services\CareSoftApiClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CareSoftSync extends Command
{
    protected $signature = 'caresoft:sync
                            {--type=all : Type to sync (agents|groups|services|tickets|calls|chats|messages|all)}
                            {--days=7 : Number of days to look back for incremental sync}
                            {--full : Force full sync from beginning}';

    protected $description = 'Sync data from CareSoft API to MySQL cache tables';

    private CareSoftApiClient $api;

    public function handle(): int
    {
        $this->api = new CareSoftApiClient();
        $type = $this->option('type');

        $this->ensureTablesExist();

        $syncTypes = $type === 'all'
            ? ['agents', 'groups', 'services', 'tickets', 'calls', 'chats', 'messages']
            : [$type];

        foreach ($syncTypes as $syncType) {
            $this->info("Syncing {$syncType}...");
            $startTime = microtime(true);

            try {
                $count = match ($syncType) {
                    'agents' => $this->syncAgents(),
                    'groups' => $this->syncGroups(),
                    'services' => $this->syncServices(),
                    'tickets' => $this->syncTickets(),
                    'calls' => $this->syncCalls(),
                    'chats' => $this->syncChats(),
                    'messages' => $this->syncChatMessages(),
                    default => 0,
                };

                $elapsed = round(microtime(true) - $startTime, 2);
                $this->info("  -> {$count} records synced in {$elapsed}s");

                $this->logSync($syncType, $count, null, $elapsed);

            } catch (\Exception $e) {
                $elapsed = round(microtime(true) - $startTime, 2);
                $this->error("  -> Error: {$e->getMessage()}");
                Log::error("CareSoft sync error [{$syncType}]", ['error' => $e->getMessage()]);
                $this->logSync($syncType, 0, $e->getMessage(), $elapsed);
            }
        }

        $this->info('Sync completed.');
        return 0;
    }

    private function ensureTablesExist(): void
    {
        // Use MySQL-compatible table creation
        if (!Schema::connection('caresoft')->hasTable('cs_agents')) {
            Schema::connection('caresoft')->create('cs_agents', function ($table) {
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

        if (!Schema::connection('caresoft')->hasTable('cs_groups')) {
            Schema::connection('caresoft')->create('cs_groups', function ($table) {
                $table->bigInteger('group_id')->primary();
                $table->string('group_name')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        if (!Schema::connection('caresoft')->hasTable('cs_services')) {
            Schema::connection('caresoft')->create('cs_services', function ($table) {
                $table->bigInteger('service_id')->primary();
                $table->string('service_name')->nullable();
                $table->string('service_type')->nullable();
                $table->integer('type')->nullable();
                $table->text('detail')->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        if (!Schema::connection('caresoft')->hasTable('cs_tickets')) {
            Schema::connection('caresoft')->create('cs_tickets', function ($table) {
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

        if (!Schema::connection('caresoft')->hasTable('cs_calls')) {
            Schema::connection('caresoft')->create('cs_calls', function ($table) {
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

        if (!Schema::connection('caresoft')->hasTable('cs_chats')) {
            Schema::connection('caresoft')->create('cs_chats', function ($table) {
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

        if (!Schema::connection('caresoft')->hasTable('cs_sync_logs')) {
            Schema::connection('caresoft')->create('cs_sync_logs', function ($table) {
                $table->id();
                $table->string('sync_type')->index();
                $table->integer('record_count')->default(0);
                $table->text('error')->nullable();
                $table->decimal('elapsed_seconds', 10, 2)->nullable();
                $table->timestamp('synced_at')->nullable()->index();
            });
        }

        // Chat messages table - stores detailed chat message content
        if (!Schema::connection('caresoft')->hasTable('cs_chat_messages')) {
            Schema::connection('caresoft')->create('cs_chat_messages', function ($table) {
                $table->id();
                $table->string('msg_id')->unique();
                $table->string('conversation_id')->index();
                $table->integer('conversation_type')->default(0)->index();
                $table->integer('message_index')->nullable();
                $table->text('content')->nullable();
                $table->integer('type')->nullable()->comment('1=text, 2=file, 3=system, 4=template');
                $table->timestamp('time')->nullable()->index();
                $table->bigInteger('service_id')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->string('sender_agent_name')->nullable();
                $table->bigInteger('sender_agent_id')->nullable();
                $table->string('sender_visitor_name')->nullable();
                $table->bigInteger('sender_visitor_id')->nullable();
                $table->bigInteger('last_agent_user_id')->nullable();
                $table->bigInteger('ticket_id')->nullable()->index();
                $table->bigInteger('requester_id')->nullable();
                $table->string('oa_name')->nullable()->comment('Zalo OA name');
                $table->string('oa_id')->nullable()->comment('Zalo OA ID');
                $table->string('page_name')->nullable()->comment('Facebook page name');
                $table->string('page_id')->nullable()->comment('Facebook page ID');
                $table->string('platform')->nullable()->comment('MESSENGER or INSTAGRAM');
                $table->string('category')->nullable()->index()->comment('Auto-classified category');
                $table->timestamp('synced_at')->nullable();
            });
        }
    }

    private function syncAgents(): int
    {
        $response = $this->api->getAgents();
        if (!$response || !isset($response['agents'])) return 0;

        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();
        $count = 0;

        foreach ($response['agents'] as $agent) {
            $db->table('cs_agents')->updateOrInsert(
                ['id' => $agent['id']],
                [
                    'username' => $agent['username'] ?? null,
                    'email' => $agent['email'] ?? null,
                    'phone_no' => $agent['phone_no'] ?? null,
                    'agent_id' => $agent['agent_id'] ?? null,
                    'group_id' => $agent['group_id'] ?? null,
                    'group_name' => $agent['group_name'] ?? null,
                    'role_id' => $agent['role_id'] ?? null,
                    'call_status' => $agent['call_status'] ?? null,
                    'ticket_status' => $agent['ticket_status'] ?? $agent['login_status'] ?? null,
                    'chat_status' => $agent['chat_status'] ?? null,
                    'created_at' => $agent['created_at'] ?? null,
                    'updated_at' => $agent['updated_at'] ?? null,
                    'synced_at' => $now,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncGroups(): int
    {
        $response = $this->api->getGroups();
        if (!$response || !isset($response['groups'])) return 0;

        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();
        $count = 0;

        foreach ($response['groups'] as $group) {
            $db->table('cs_groups')->updateOrInsert(
                ['group_id' => $group['group_id']],
                [
                    'group_name' => $group['group_name'] ?? null,
                    'created_at' => $group['created_at'] ?? null,
                    'synced_at' => $now,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncServices(): int
    {
        $response = $this->api->getServices();
        if (!$response || !isset($response['services'])) return 0;

        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();
        $count = 0;

        foreach ($response['services'] as $svc) {
            $db->table('cs_services')->updateOrInsert(
                ['service_id' => $svc['service_id']],
                [
                    'service_name' => $svc['service_name'] ?? null,
                    'service_type' => $svc['service_type'] ?? null,
                    'type' => $svc['type'] ?? null,
                    'detail' => isset($svc['detail']) ? json_encode($svc['detail']) : null,
                    'synced_at' => $now,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncTickets(): int
    {
        $days = $this->option('full') ? 365 : (int) $this->option('days');
        $since = Carbon::now()->subDays($days)->startOfDay()->format('Y-m-d\TH:i:s\Z');
        $to = Carbon::now()->format('Y-m-d\TH:i:s\Z');

        $maxDaySpan = 31;
        $count = 0;
        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();

        $windowStart = Carbon::parse($since);
        $windowEnd = Carbon::parse($to);

        while ($windowStart->lt($windowEnd)) {
            $chunkEnd = $windowStart->copy()->addDays($maxDaySpan - 1);
            if ($chunkEnd->gt($windowEnd)) $chunkEnd = $windowEnd->copy();

            $params = [
                'updated_since' => $windowStart->format('Y-m-d\TH:i:s\Z'),
                'updated_to' => $chunkEnd->format('Y-m-d\TH:i:s\Z'),
            ];

            $tickets = $this->api->getAllPages('getTickets', $params, 'tickets', function ($page, $pageCount, $total) {
                $this->output->write("\r  Tickets page {$page} ({$pageCount} items, total: {$total})");
            });

            foreach ($tickets as $t) {
                $db->table('cs_tickets')->updateOrInsert(
                    ['ticket_id' => $t['id'] ?? $t['ticket_id'] ?? 0],
                    [
                        'ticket_no' => $t['ticket_no'] ?? null,
                        'subject' => $t['subject'] ?? null,
                        'ticket_status' => $t['ticket_status'] ?? null,
                        'ticket_priority' => $t['ticket_priority'] ?? null,
                        'ticket_source' => $t['ticket_source'] ?? null,
                        'requester_id' => $t['requester_id'] ?? null,
                        'assignee_id' => $t['assignee_id'] ?? null,
                        'group_id' => $t['group_id'] ?? null,
                        'service_id' => $t['service_id'] ?? null,
                        'satisfaction' => $t['satisfaction'] ?? null,
                        'created_at' => isset($t['created_at']) ? str_replace(['T', 'Z'], [' ', ''], $t['created_at']) : null,
                        'updated_at' => isset($t['updated_at']) ? str_replace(['T', 'Z'], [' ', ''], $t['updated_at']) : null,
                        'synced_at' => $now,
                    ]
                );
                $count++;
            }

            $this->newLine();
            $windowStart = $chunkEnd->copy()->addSecond();
        }

        return $count;
    }

    private function syncCalls(): int
    {
        $days = $this->option('full') ? 365 : (int) $this->option('days');
        $since = Carbon::now()->subDays($days)->startOfDay();
        $to = Carbon::now();

        $maxDaySpan = 31;
        $count = 0;
        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();

        $windowStart = $since->copy();

        while ($windowStart->lt($to)) {
            $chunkEnd = $windowStart->copy()->addDays($maxDaySpan - 1);
            if ($chunkEnd->gt($to)) $chunkEnd = $to->copy();

            $params = [
                'start_time_since' => $windowStart->format('Y-m-d\TH:i:s\Z'),
                'start_time_to' => $chunkEnd->format('Y-m-d\TH:i:s\Z'),
            ];

            $calls = $this->api->getAllPages('getCalls', $params, 'calls', function ($page, $pageCount, $total) {
                $this->output->write("\r  Calls page {$page} ({$pageCount} items, total: {$total})");
            });

            foreach ($calls as $c) {
                $db->table('cs_calls')->updateOrInsert(
                    ['call_id' => $c['call_id']],
                    [
                        'caller' => $c['caller'] ?? null,
                        'called' => $c['called'] ?? null,
                        'user_id' => $c['user_id'] ?? null,
                        'agent_id' => $c['agent_id'] ?? null,
                        'group_id' => $c['group_id'] ?? null,
                        'call_type' => $c['call_type'] ?? null,
                        'call_status' => $c['call_status'] ?? null,
                        'start_time' => $c['start_time'] ?? null,
                        'end_time' => $c['end_time'] ?? null,
                        'wait_time' => $c['wait_time'] ?? null,
                        'hold_time' => $c['hold_time'] ?? null,
                        'talk_time' => $c['talk_time'] ?? null,
                        'end_status' => $c['end_status'] ?? null,
                        'ticket_id' => $c['ticket_id'] ?? null,
                        'missed_reason' => $c['missed_reason'] ?? null,
                        'service_id' => $c['service_id'] ?? null,
                        'last_user_id' => $c['last_user_id'] ?? null,
                        'synced_at' => $now,
                    ]
                );
                $count++;
            }

            $this->newLine();
            $windowStart = $chunkEnd->copy()->addSecond();
        }

        return $count;
    }

    private function syncChats(): int
    {
        $days = $this->option('full') ? 365 : (int) $this->option('days');
        $since = Carbon::now()->subDays($days)->startOfDay();
        $to = Carbon::now();

        $maxDaySpan = 31;
        $count = 0;
        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();

        $conversationTypes = [0, 1, 3]; // Livechat, Facebook/Instagram, Zalo

        foreach ($conversationTypes as $convType) {
            $windowStart = $since->copy();

            while ($windowStart->lt($to)) {
                $chunkEnd = $windowStart->copy()->addDays($maxDaySpan - 1);
                if ($chunkEnd->gt($to)) $chunkEnd = $to->copy();

                $params = [
                    'start_time_since' => $windowStart->format('Y-m-d\TH:i:s\Z'),
                    'start_time_to' => $chunkEnd->format('Y-m-d\TH:i:s\Z'),
                    'conversation_type' => $convType,
                ];

                $typeName = match ($convType) { 0 => 'Livechat', 1 => 'Facebook', 3 => 'Zalo', default => 'Unknown' };

                $chats = $this->api->getAllPages('getChats', $params, 'chats', function ($page, $pageCount, $total) use ($typeName) {
                    $this->output->write("\r  Chats [{$typeName}] page {$page} ({$pageCount} items, total: {$total})");
                });

                foreach ($chats as $ch) {
                    $db->table('cs_chats')->updateOrInsert(
                        ['conversation_id' => $ch['conversation_id']],
                        [
                            'ticket_id' => $ch['ticket_id'] ?? null,
                            'ticket_no' => $ch['ticket_no'] ?? null,
                            'customer_id' => $ch['customer_id'] ?? null,
                            'cus_name' => $ch['cus_name'] ?? null,
                            'cus_phone' => $ch['cus_phone'] ?? null,
                            'cus_email' => $ch['cus_email'] ?? null,
                            'start_time' => $ch['start_time'] ?? null,
                            'end_time' => $ch['end_time'] ?? null,
                            'chat_duration' => $ch['chat_duration'] ?? null,
                            'chat_status' => $ch['chat_status'] ?? null,
                            'agent_name' => $ch['agent_name'] ?? null,
                            'agent_email' => $ch['agent_email'] ?? null,
                            'group_name' => $ch['group_name'] ?? null,
                            'service_id' => $ch['service_id'] ?? null,
                            'conversation_type' => $convType,
                            'synced_at' => $now,
                        ]
                    );
                    $count++;
                }

                $this->newLine();
                $windowStart = $chunkEnd->copy()->addSecond();
            }
        }

        return $count;
    }

    /**
     * Sync chat messages from CareSoft API
     * Fetches detailed chat messages and auto-classifies them
     */
    private function syncChatMessages(): int
    {
        $days = $this->option('full') ? 30 : min((int) $this->option('days'), 30); // Max 31 days per API limit
        $since = Carbon::now()->subDays($days)->startOfDay();
        $to = Carbon::now();

        $maxDaySpan = 30; // API limit: max 31 days
        $count = 0;
        $db = DB::connection('caresoft');
        $now = now()->toDateTimeString();

        $conversationTypes = [0, 1, 3]; // Livechat, Facebook/Instagram, Zalo

        foreach ($conversationTypes as $convType) {
            $windowStart = $since->copy();

            while ($windowStart->lt($to)) {
                $chunkEnd = $windowStart->copy()->addDays($maxDaySpan - 1);
                if ($chunkEnd->gt($to)) $chunkEnd = $to->copy();

                $params = [
                    'start_time_since' => $windowStart->format('Y-m-d\TH:i:s\Z'),
                    'start_time_to' => $chunkEnd->format('Y-m-d\TH:i:s\Z'),
                    'conversation_type' => $convType,
                ];

                $typeName = match ($convType) { 0 => 'Livechat', 1 => 'Facebook', 3 => 'Zalo', default => 'Unknown' };

                $messages = $this->api->getAllPages('getChatMessages', $params, 'chats', function ($page, $pageCount, $total) use ($typeName) {
                    $this->output->write("\r  Messages [{$typeName}] page {$page} ({$pageCount} items, total: {$total})");
                });

                foreach ($messages as $m) {
                    // Auto-classify the message
                    $category = $this->classifyMessage($m);

                    $db->table('cs_chat_messages')->updateOrInsert(
                        ['msg_id' => $m['msg_id']],
                        [
                            'conversation_id' => $m['conversation_id'] ?? null,
                            'conversation_type' => $m['conversation_type'] ?? $convType,
                            'message_index' => $m['message_index'] ?? null,
                            'content' => is_array($m['content'] ?? null) ? json_encode($m['content']) : ($m['content'] ?? null),
                            'type' => $m['type'] ?? null,
                            'time' => $m['time'] ?? null,
                            'service_id' => $m['service_id'] ?? null,
                            'start_time' => $m['start_time'] ?? null,
                            'sender_agent_name' => $m['sender_agent_name'] ?? null,
                            'sender_agent_id' => $m['sender_agent_id'] ?? null,
                            'sender_visitor_name' => $m['sender_visitor_name'] ?? null,
                            'sender_visitor_id' => $m['sender_visitor_id'] ?? null,
                            'last_agent_user_id' => $m['last_agent_user_id'] ?? null,
                            'ticket_id' => $m['ticket_id'] ?? null,
                            'requester_id' => $m['requester_id'] ?? null,
                            'oa_name' => $m['oa_name'] ?? null,
                            'oa_id' => $m['oa_id'] ?? null,
                            'page_name' => $m['page_name'] ?? null,
                            'page_id' => $m['page_id'] ?? null,
                            'platform' => $m['platform'] ?? null,
                            'category' => $category,
                            'synced_at' => $now,
                        ]
                    );
                    $count++;
                }

                $this->newLine();
                $windowStart = $chunkEnd->copy()->addSecond();
            }
        }

        return $count;
    }

    /**
     * Auto-classify chat message content
     * Classifies messages into categories based on keywords
     */
    private function classifyMessage(array $message): ?string
    {
        // Skip system messages (type=3) and file attachments (type=2)
        $type = $message['type'] ?? 1;
        if ($type === 3) return 'system';
        if ($type === 2) return 'attachment';
        if ($type === 4) return 'template';

        $content = $message['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content);
        }
        $content = mb_strtolower($content);

        // Skip empty content
        if (empty(trim($content))) return 'empty';

        // Determine sender type
        $isFromCustomer = empty($message['sender_agent_id']) && !empty($message['sender_visitor_id']);
        $isFromAgent = !empty($message['sender_agent_id']) && $message['sender_agent_id'] > 0;

        // Classification rules for customer messages
        if ($isFromCustomer) {
            // Inquiry/Question keywords
            $inquiryKeywords = ['hỏi', 'thắc mắc', 'cho hỏi', 'xin hỏi', 'muốn hỏi', 'tư vấn', 'cho mình hỏi', 
                               'thông tin', 'giá', 'bao nhiêu', 'như thế nào', 'làm sao', 'cách nào', 'ở đâu',
                               'khi nào', 'bao lâu', '?', 'có không', 'được không', 'có thể', 'help', 'support'];
            foreach ($inquiryKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'inquiry';
            }

            // Complaint keywords
            $complaintKeywords = ['khiếu nại', 'phản ánh', 'không hài lòng', 'tệ quá', 'dở', 'chậm', 'lỗi', 'sai',
                                 'thất vọng', 'bực', 'tức', 'phiền', 'chán', 'không được', 'bị lỗi', 'không hoạt động',
                                 'hỏng', 'bad', 'terrible', 'poor', 'complaint'];
            foreach ($complaintKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'complaint';
            }

            // Order/Purchase keywords
            $orderKeywords = ['đặt hàng', 'mua', 'order', 'đơn hàng', 'thanh toán', 'giao hàng', 'ship',
                             'vận chuyển', 'đặt mua', 'đăng ký', 'subscribe', 'purchase'];
            foreach ($orderKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'order';
            }

            // Feedback/Review keywords
            $feedbackKeywords = ['góp ý', 'đánh giá', 'review', 'feedback', 'ý kiến', 'nhận xét', 
                                'cảm ơn', 'hài lòng', 'tốt', 'tuyệt vời', 'great', 'good', 'excellent'];
            foreach ($feedbackKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'feedback';
            }

            // Support request keywords
            $supportKeywords = ['hỗ trợ', 'giúp', 'help', 'support', 'cần hỗ trợ', 'nhờ', 'xin', 'làm ơn'];
            foreach ($supportKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'support';
            }

            // Greeting
            $greetingKeywords = ['xin chào', 'hello', 'hi', 'chào', 'alo', 'hey'];
            foreach ($greetingKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false && mb_strlen($content) < 50) return 'greeting';
            }

            return 'customer_message';
        }

        // Classification for agent messages
        if ($isFromAgent) {
            // Greeting from agent
            $greetingKeywords = ['xin chào', 'chào bạn', 'chào anh', 'chào chị', 'hello', 'xin kính chào'];
            foreach ($greetingKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'agent_greeting';
            }

            // Response with solution
            $solutionKeywords = ['hướng dẫn', 'cách làm', 'giải quyết', 'xử lý', 'đã xong', 'hoàn thành',
                                'vui lòng', 'bạn có thể', 'anh/chị có thể'];
            foreach ($solutionKeywords as $kw) {
                if (mb_strpos($content, $kw) !== false) return 'agent_solution';
            }

            return 'agent_response';
        }

        return 'other';
    }

    private function logSync(string $type, int $count, ?string $error, float $elapsed): void
    {
        DB::connection('caresoft')->table('cs_sync_logs')->insert([
            'sync_type' => $type,
            'record_count' => $count,
            'error' => $error,
            'elapsed_seconds' => $elapsed,
            'synced_at' => now()->toDateTimeString(),
        ]);
    }
}
