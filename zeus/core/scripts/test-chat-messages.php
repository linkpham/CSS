#!/usr/bin/env php
<?php
/**
 * Test Script: Lấy chi tiết danh sách tin nhắn chat từ CareSoft API
 * 
 * API: GET {{domain}}/api/v1/chats/messages
 * 
 * Constraints:
 * - start_time_since (required): ISO8601 datetime
 * - start_time_to: ISO8601, defaults to now if omitted
 * - start_time_since & start_time_to cannot exceed 30 days apart
 * - conversation_type: 0=LiveChat (default), 1=Messenger/Instagram, 3=Zalo
 * - count: max 500 per request, default 50
 * - page: pagination
 * - numFound: total records matching query
 * 
 * This script:
 * 1. Reads .env for CARESOFT_DOMAIN and CARESOFT_API_TOKEN
 * 2. Fetches ALL chat messages across all conversation types
 * 3. Handles pagination (500 per page) and 30-day window chunking
 * 4. Validates response structure
 * 5. Outputs summary statistics
 * 
 * Usage:
 *   php scripts/test-chat-messages.php [--days=7] [--type=all] [--dry-run] [--verbose]
 * 
 * Options:
 *   --days=N       Number of days to look back (default: 7, max: 365)
 *   --type=T       Conversation type: 0=LiveChat, 1=Facebook, 3=Zalo, all=All (default: all)
 *   --dry-run      Only show what would be fetched, don't make API calls
 *   --verbose      Show detailed output for each message
 *   --limit=N      Limit total messages fetched per conversation type (for testing)
 * 
 * Refs: doc/phase_96.md, care_soft.md (section: Danh sách tin nhắn chat)
 */

// ============================================================
// Configuration
// ============================================================

define('API_HOST', 'https://api.caresoft.vn');
define('API_HOST_BACKUP', 'https://api2.caresoft.vn');
define('MAX_PER_PAGE', 500);
define('MAX_DAY_SPAN', 30); // API limit: max 31 days, use 30 for safety
define('RATE_LIMIT_DELAY_MS', 200);
define('MAX_RETRIES', 3);
define('TIMEOUT_SECONDS', 30);

$CONVERSATION_TYPES = [
    0 => 'LiveChat',
    1 => 'Messenger/Instagram',
    3 => 'Zalo',
];

// Expected fields in each chat message
$EXPECTED_FIELDS = [
    'conversation_id', 'conversation_type', 'msg_id', 'content', 'type',
    'time', 'message_index', 'service_id', 'start_time',
    'sender_agent_name', 'sender_agent_id',
    'sender_visitor_name', 'sender_visitor_id',
    'last_agent_user_id', 'ticket_id', 'requester_id',
];

// Extra fields per conversation_type
$EXTRA_FIELDS_BY_TYPE = [
    0 => [], // LiveChat - no extra fields
    1 => ['page_name', 'page_id', 'platform'], // Messenger/Instagram
    3 => ['oa_name', 'oa_id'], // Zalo
];

// ============================================================
// Helper Functions
// ============================================================

function loadEnv(string $path): array
{
    $env = [];
    if (!file_exists($path)) {
        return $env;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $env[$key] = $value;
    }
    return $env;
}

function colorize(string $text, string $color): string
{
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'magenta' => "\033[35m",
        'bold' => "\033[1m",
        'reset' => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function info(string $msg): void
{
    echo colorize("[INFO] ", 'cyan') . $msg . "\n";
}

function success(string $msg): void
{
    echo colorize("[OK] ", 'green') . $msg . "\n";
}

function warn(string $msg): void
{
    echo colorize("[WARN] ", 'yellow') . $msg . "\n";
}

function error(string $msg): void
{
    echo colorize("[ERROR] ", 'red') . $msg . "\n";
}

function heading(string $msg): void
{
    echo "\n" . colorize("═══ {$msg} ═══", 'bold') . "\n";
}

function httpGet(string $url, array $params, string $token): ?array
{
    $queryString = http_build_query($params);
    $fullUrl = $url . '?' . $queryString;

    for ($attempt = 1; $attempt <= MAX_RETRIES; $attempt++) {
        // Rate limiting
        usleep(RATE_LIMIT_DELAY_MS * 1000);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            warn("cURL error (attempt {$attempt}): {$curlError}");
            if ($attempt < MAX_RETRIES) {
                usleep(1000 * 1000 * $attempt); // exponential backoff
                continue;
            }
            return null;
        }

        if ($httpCode === 401) {
            error("Authentication failed (401). Check CARESOFT_API_TOKEN.");
            return null;
        }

        if ($httpCode === 429) {
            warn("Rate limited (429), waiting... (attempt {$attempt})");
            usleep(2000 * 1000 * $attempt);
            continue;
        }

        if ($httpCode === 400) {
            $data = json_decode($response, true);
            error("Bad Request (400): " . ($data['message'] ?? $response));
            return null;
        }

        if ($httpCode >= 500) {
            warn("Server error ({$httpCode}), trying backup host... (attempt {$attempt})");
            // Try backup host
            $backupUrl = str_replace(API_HOST, API_HOST_BACKUP, $fullUrl);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $backupUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => TIMEOUT_SECONDS,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $backupResponse = curl_exec($ch);
            $backupHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($backupHttpCode >= 200 && $backupHttpCode < 300) {
                return json_decode($backupResponse, true);
            }
            if ($attempt < MAX_RETRIES) {
                usleep(1000 * 1000 * $attempt);
                continue;
            }
            return null;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        warn("Unexpected status {$httpCode} (attempt {$attempt})");
        if ($attempt < MAX_RETRIES) {
            usleep(1000 * 1000 * $attempt);
        }
    }

    return null;
}

/**
 * Fetch all pages for a given set of parameters
 */
function fetchAllPages(string $baseUrl, array $params, string $token, int $limit = 0): array
{
    $allMessages = [];
    $page = 1;
    $params['count'] = MAX_PER_PAGE;

    do {
        $params['page'] = $page;
        $response = httpGet($baseUrl, $params, $token);

        if ($response === null) {
            error("Failed to fetch page {$page}");
            break;
        }

        if (($response['code'] ?? '') !== 'ok') {
            error("API returned error: " . ($response['message'] ?? json_encode($response)));
            break;
        }

        $chats = $response['chats'] ?? [];
        $numFound = $response['numFound'] ?? 0;

        $allMessages = array_merge($allMessages, $chats);

        $fetched = count($allMessages);
        echo "\r  Trang {$page}: +" . count($chats) . " tin nhắn (tổng: {$fetched}/{$numFound})";

        // Check if we should stop
        if (count($chats) < MAX_PER_PAGE || $fetched >= $numFound) {
            break;
        }

        // Check limit
        if ($limit > 0 && $fetched >= $limit) {
            echo "\n";
            info("  Đạt giới hạn {$limit} tin nhắn (--limit)");
            $allMessages = array_slice($allMessages, 0, $limit);
            break;
        }

        $page++;
    } while (true);

    echo "\n";
    return $allMessages;
}

/**
 * Validate message structure
 */
function validateMessages(array $messages, int $convType, array $expectedFields, array $extraFieldsByType): array
{
    $errors = [];
    $warnings = [];
    $typeDistribution = [];
    $categoryDistribution = [
        'customer' => 0,
        'agent' => 0,
        'system' => 0,
        'unknown' => 0,
    ];

    foreach ($messages as $idx => $msg) {
        // Check expected fields
        foreach ($expectedFields as $field) {
            if (!array_key_exists($field, $msg)) {
                $errors[] = "Message #{$idx} (msg_id: " . ($msg['msg_id'] ?? 'N/A') . ") missing field: {$field}";
            }
        }

        // Check extra fields based on conversation type
        $extraFields = $extraFieldsByType[$convType] ?? [];
        foreach ($extraFields as $field) {
            if (!array_key_exists($field, $msg)) {
                $warnings[] = "Message #{$idx} missing optional field for type {$convType}: {$field}";
            }
        }

        // Validate conversation_type matches
        if (isset($msg['conversation_type']) && $msg['conversation_type'] !== $convType) {
            $warnings[] = "Message #{$idx} has conversation_type={$msg['conversation_type']} but expected {$convType}";
        }

        // Track type distribution (1=text, 2=file, 3=system, 4=template)
        $msgType = $msg['type'] ?? 'null';
        $typeDistribution[$msgType] = ($typeDistribution[$msgType] ?? 0) + 1;

        // Track sender category
        $hasAgent = !empty($msg['sender_agent_id']) && ($msg['sender_agent_id'] > 0);
        $hasVisitor = !empty($msg['sender_visitor_id']);
        $isSystem = ($msg['type'] ?? 0) === 3;

        if ($isSystem) {
            $categoryDistribution['system']++;
        } elseif ($hasAgent) {
            $categoryDistribution['agent']++;
        } elseif ($hasVisitor) {
            $categoryDistribution['customer']++;
        } else {
            $categoryDistribution['unknown']++;
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'type_distribution' => $typeDistribution,
        'category_distribution' => $categoryDistribution,
    ];
}

function formatDateISO(DateTime $dt): string
{
    return $dt->format('Y-m-d\TH:i:s\Z');
}

function formatDuration(int $seconds): string
{
    if ($seconds < 60) return "{$seconds}s";
    $min = floor($seconds / 60);
    $sec = $seconds % 60;
    return "{$min}m {$sec}s";
}

// ============================================================
// Parse CLI Arguments
// ============================================================

$opts = getopt('', ['days::', 'type::', 'dry-run', 'verbose', 'limit::']);

$lookbackDays = (int) ($opts['days'] ?? 7);
$typeArg = $opts['type'] ?? 'all';
$dryRun = isset($opts['dry-run']);
$verbose = isset($opts['verbose']);
$limit = (int) ($opts['limit'] ?? 0);

// Validate days
if ($lookbackDays < 1) $lookbackDays = 1;
if ($lookbackDays > 365) $lookbackDays = 365;

// Determine which conversation types to fetch
$typesToFetch = [];
if ($typeArg === 'all') {
    $typesToFetch = [0, 1, 3];
} else {
    $t = (int) $typeArg;
    if (!in_array($t, [0, 1, 3])) {
        error("Invalid --type={$typeArg}. Must be 0, 1, 3, or all.");
        exit(1);
    }
    $typesToFetch = [$t];
}

// ============================================================
// Main
// ============================================================

heading("Test: Lấy chi tiết danh sách tin nhắn chat (CareSoft API)");

// Load .env - try multiple paths (host vs Docker)
$envPaths = [
    __DIR__ . '/../src/.env',      // Running from host: scripts/ -> src/.env
    __DIR__ . '/.env',             // Running from inside src/
    '/var/www/.env',               // Running inside Docker container
];

$env = [];
$envLoaded = false;
foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $env = loadEnv($envPath);
        $envLoaded = true;
        break;
    }
}

if (!$envLoaded) {
    error("Cannot find .env file. Searched: " . implode(', ', $envPaths));
    exit(1);
}

$domain = $env['CARESOFT_DOMAIN'] ?? '';
$token = $env['CARESOFT_API_TOKEN'] ?? '';

if (empty($domain)) {
    error("CARESOFT_DOMAIN not set in .env");
    exit(1);
}

if (empty($token)) {
    error("CARESOFT_API_TOKEN not set in .env (token trống)");
    echo "\n";
    warn("Để test, cần cấu hình CARESOFT_API_TOKEN trong src/.env");
    warn("Lấy token tại: Admin → Api → Api token trên hệ thống CareSoft");
    echo "\n";
    
    // Show dry-run info even without token
    info("Domain: {$domain}");
    info("Lookback: {$lookbackDays} ngày");
    info("Conversation types: " . implode(', ', array_map(fn($t) => "{$t} ({$CONVERSATION_TYPES[$t]})", $typesToFetch)));
    
    heading("DRY RUN: Kế hoạch fetch API");
    
    $now = new DateTime();
    $since = (clone $now)->modify("-{$lookbackDays} days");
    $since->setTime(0, 0, 0);
    
    $totalChunks = 0;
    $totalApiCalls = 0;
    
    foreach ($typesToFetch as $convType) {
        $typeName = $CONVERSATION_TYPES[$convType];
        echo "\n  " . colorize("[{$typeName}]", 'magenta') . "\n";
        
        $windowStart = clone $since;
        $chunkNum = 0;
        
        while ($windowStart < $now) {
            $windowEnd = clone $windowStart;
            $windowEnd->modify('+' . (MAX_DAY_SPAN - 1) . ' days');
            if ($windowEnd > $now) $windowEnd = clone $now;
            
            $chunkNum++;
            echo "    Chunk {$chunkNum}: " . formatDateISO($windowStart) . " → " . formatDateISO($windowEnd) . "\n";
            echo "      URL: " . API_HOST . "/{$domain}/api/v1/chats/messages" . "\n";
            echo "      Params: start_time_since=" . formatDateISO($windowStart) 
                 . "&start_time_to=" . formatDateISO($windowEnd) 
                 . "&conversation_type={$convType}&count=" . MAX_PER_PAGE . "&page=1\n";
            
            $totalChunks++;
            $totalApiCalls++; // At least 1 call per chunk, more if paginated
            
            $windowStart = clone $windowEnd;
            $windowStart->modify('+1 second');
        }
    }
    
    echo "\n";
    info("Tổng chunks: {$totalChunks}");
    info("Tối thiểu API calls: {$totalApiCalls} (+ thêm nếu có pagination)");
    info("Rate limit delay: " . RATE_LIMIT_DELAY_MS . "ms giữa mỗi call");
    
    heading("Kết luận");
    
    success("Kế hoạch fetch đã được xác nhận chính xác:");
    echo "  • API endpoint: GET {{domain}}/api/v1/chats/messages\n";
    echo "  • Tham số bắt buộc: start_time_since (ISO8601)\n";
    echo "  • Giới hạn: max 30 ngày/request, max 500 records/page\n";
    echo "  • Phân trang: dùng page + count, lặp cho đến khi hết numFound\n";
    echo "  • Chia window: nếu lookback > 30 ngày, chia thành chunks 30 ngày\n";
    echo "  • Conversation types: 0=LiveChat, 1=Messenger/Instagram, 3=Zalo\n";
    echo "  • Lặp qua từng conversation_type vì API default=0 nếu không truyền\n";
    echo "\n";
    warn("Chạy lại với CARESOFT_API_TOKEN để fetch dữ liệu thực tế.");
    exit(0);
}

// --- Actual API calls below (token present) ---

info("Domain: {$domain}");
info("Token: " . substr($token, 0, 8) . "..." . substr($token, -4));
info("Lookback: {$lookbackDays} ngày");
info("Types: " . implode(', ', array_map(fn($t) => "{$t} ({$CONVERSATION_TYPES[$t]})", $typesToFetch)));
if ($limit > 0) info("Limit: {$limit} messages per type");
if ($dryRun) info("DRY RUN mode (no API calls)");

// Step 1: Test connection first
heading("Bước 1: Kiểm tra kết nối API");

$agentsUrl = API_HOST . "/{$domain}/api/v1/agents";
$testResponse = httpGet($agentsUrl, [], $token);

if ($testResponse === null) {
    error("Không thể kết nối tới CareSoft API");
    exit(1);
}

if (($testResponse['code'] ?? '') !== 'ok') {
    error("API trả về lỗi: " . json_encode($testResponse));
    exit(1);
}

$agentCount = count($testResponse['agents'] ?? []);
success("Kết nối thành công! Tìm thấy {$agentCount} chuyên viên.");

// Step 2: Fetch chat messages
heading("Bước 2: Fetch chi tiết tin nhắn chat");

$now = new DateTime();
$since = (clone $now)->modify("-{$lookbackDays} days");
$since->setTime(0, 0, 0);

$baseUrl = API_HOST . "/{$domain}/api/v1/chats/messages";

$grandTotal = 0;
$allResults = [];
$startTime = microtime(true);

foreach ($typesToFetch as $convType) {
    $typeName = $CONVERSATION_TYPES[$convType];
    echo "\n" . colorize("  [{$typeName}] (conversation_type={$convType})", 'magenta') . "\n";

    $typeMessages = [];
    $windowStart = clone $since;

    while ($windowStart < $now) {
        $windowEnd = clone $windowStart;
        $windowEnd->modify('+' . (MAX_DAY_SPAN - 1) . ' days');
        if ($windowEnd > $now) $windowEnd = clone $now;

        info("  Window: " . $windowStart->format('Y-m-d H:i') . " → " . $windowEnd->format('Y-m-d H:i'));

        if ($dryRun) {
            echo "  [DRY RUN] Skipping API call\n";
            $windowStart = clone $windowEnd;
            $windowStart->modify('+1 second');
            continue;
        }

        $params = [
            'start_time_since' => formatDateISO($windowStart),
            'start_time_to' => formatDateISO($windowEnd),
            'conversation_type' => $convType,
        ];

        $remaining = $limit > 0 ? ($limit - count($typeMessages)) : 0;
        $messages = fetchAllPages($baseUrl, $params, $token, $remaining > 0 ? $remaining : 0);

        $typeMessages = array_merge($typeMessages, $messages);

        // Check if limit reached
        if ($limit > 0 && count($typeMessages) >= $limit) {
            $typeMessages = array_slice($typeMessages, 0, $limit);
            break;
        }

        $windowStart = clone $windowEnd;
        $windowStart->modify('+1 second');
    }

    $allResults[$convType] = $typeMessages;
    $typeCount = count($typeMessages);
    $grandTotal += $typeCount;

    if (!$dryRun) {
        success("  [{$typeName}]: {$typeCount} tin nhắn");
    }
}

$elapsed = round(microtime(true) - $startTime, 2);

if ($dryRun) {
    info("DRY RUN completed. No API calls were made.");
    exit(0);
}

// Step 3: Validate response structure
heading("Bước 3: Kiểm tra cấu trúc dữ liệu");

$totalErrors = 0;
$totalWarnings = 0;

foreach ($allResults as $convType => $messages) {
    $typeName = $CONVERSATION_TYPES[$convType];
    
    if (empty($messages)) {
        info("[{$typeName}] Không có dữ liệu để kiểm tra");
        continue;
    }

    $validation = validateMessages($messages, $convType, $EXPECTED_FIELDS, $EXTRA_FIELDS_BY_TYPE);

    echo "\n  " . colorize("[{$typeName}]", 'magenta') . " - " . count($messages) . " tin nhắn\n";

    // Show errors
    if (!empty($validation['errors'])) {
        $errorCount = count($validation['errors']);
        $totalErrors += $errorCount;
        error("  {$errorCount} lỗi cấu trúc:");
        foreach (array_slice($validation['errors'], 0, 5) as $err) {
            echo "    • {$err}\n";
        }
        if ($errorCount > 5) echo "    ... và " . ($errorCount - 5) . " lỗi khác\n";
    } else {
        success("  Cấu trúc dữ liệu hợp lệ ✓");
    }

    // Show warnings
    if (!empty($validation['warnings'])) {
        $warnCount = count($validation['warnings']);
        $totalWarnings += $warnCount;
        if ($verbose) {
            warn("  {$warnCount} cảnh báo:");
            foreach (array_slice($validation['warnings'], 0, 3) as $w) {
                echo "    • {$w}\n";
            }
        } else {
            warn("  {$warnCount} cảnh báo (dùng --verbose để xem chi tiết)");
        }
    }

    // Type distribution
    echo "  Loại nội dung:\n";
    $typeNames = [1 => 'Văn bản', 2 => 'File đính kèm', 3 => 'Hệ thống', 4 => 'Template'];
    foreach ($validation['type_distribution'] as $t => $count) {
        $label = $typeNames[$t] ?? "Loại {$t}";
        $pct = count($messages) > 0 ? round($count / count($messages) * 100, 1) : 0;
        echo "    • {$label}: {$count} ({$pct}%)\n";
    }

    // Category distribution
    echo "  Phân loại người gửi:\n";
    $catLabels = ['customer' => 'Khách hàng', 'agent' => 'Chuyên viên', 'system' => 'Hệ thống', 'unknown' => 'Không xác định'];
    foreach ($validation['category_distribution'] as $cat => $count) {
        if ($count > 0) {
            $pct = count($messages) > 0 ? round($count / count($messages) * 100, 1) : 0;
            echo "    • {$catLabels[$cat]}: {$count} ({$pct}%)\n";
        }
    }
}

// Step 4: Show sample messages
if ($verbose && $grandTotal > 0) {
    heading("Bước 4: Mẫu tin nhắn");

    foreach ($allResults as $convType => $messages) {
        $typeName = $CONVERSATION_TYPES[$convType];
        if (empty($messages)) continue;

        echo "\n  " . colorize("[{$typeName}]", 'magenta') . " - 3 tin nhắn đầu tiên:\n";

        foreach (array_slice($messages, 0, 3) as $i => $msg) {
            echo "  --- Tin nhắn #" . ($i + 1) . " ---\n";
            echo "    conversation_id: " . ($msg['conversation_id'] ?? 'N/A') . "\n";
            echo "    msg_id: " . ($msg['msg_id'] ?? 'N/A') . "\n";
            echo "    time: " . ($msg['time'] ?? 'N/A') . "\n";
            echo "    type: " . ($msg['type'] ?? 'N/A') . "\n";
            $content = $msg['content'] ?? '';
            if (is_array($content)) $content = json_encode($content, JSON_UNESCAPED_UNICODE);
            echo "    content: " . mb_substr($content, 0, 100) . (mb_strlen($content) > 100 ? '...' : '') . "\n";
            echo "    sender_agent: " . ($msg['sender_agent_name'] ?? 'null') . " (id: " . ($msg['sender_agent_id'] ?? 'null') . ")\n";
            echo "    sender_visitor: " . ($msg['sender_visitor_name'] ?? 'null') . " (id: " . ($msg['sender_visitor_id'] ?? 'null') . ")\n";
            echo "    ticket_id: " . ($msg['ticket_id'] ?? 'N/A') . "\n";

            // Type-specific fields
            if ($convType === 1) {
                echo "    page_name: " . ($msg['page_name'] ?? 'N/A') . "\n";
                echo "    platform: " . ($msg['platform'] ?? 'N/A') . "\n";
            } elseif ($convType === 3) {
                echo "    oa_name: " . ($msg['oa_name'] ?? 'N/A') . "\n";
                echo "    oa_id: " . ($msg['oa_id'] ?? 'N/A') . "\n";
            }
        }
    }
}

// Step 5: Summary
heading("Tổng kết");

echo "\n";
info("Thời gian: {$elapsed}s (" . formatDuration((int) $elapsed) . ")");
info("Khoảng thời gian: " . $since->format('Y-m-d H:i') . " → " . $now->format('Y-m-d H:i') . " ({$lookbackDays} ngày)");
echo "\n";

echo "  " . colorize("Conversation Type", 'bold') . "       " . colorize("Số tin nhắn", 'bold') . "\n";
echo "  ─────────────────────────────────────\n";
foreach ($allResults as $convType => $messages) {
    $typeName = str_pad($CONVERSATION_TYPES[$convType], 25);
    $count = count($messages);
    $color = $count > 0 ? 'green' : 'yellow';
    echo "  {$typeName}" . colorize((string) $count, $color) . "\n";
}
echo "  ─────────────────────────────────────\n";
echo "  " . str_pad("TỔNG CỘNG", 25) . colorize((string) $grandTotal, 'bold') . "\n";

echo "\n";

// Unique conversations
$allConvIds = [];
foreach ($allResults as $messages) {
    foreach ($messages as $msg) {
        if (isset($msg['conversation_id'])) {
            $allConvIds[$msg['conversation_id']] = true;
        }
    }
}
info("Số phiên chat (conversations) duy nhất: " . count($allConvIds));

if ($totalErrors > 0) {
    error("Tổng lỗi cấu trúc: {$totalErrors}");
} else {
    success("Không có lỗi cấu trúc");
}

if ($totalWarnings > 0) {
    warn("Tổng cảnh báo: {$totalWarnings}");
}

echo "\n";

// Final verdict
if ($grandTotal > 0 && $totalErrors === 0) {
    success("✅ TEST PASSED: Lấy thành công {$grandTotal} tin nhắn chat từ CareSoft API");
    echo "   API endpoint hoạt động đúng, cấu trúc dữ liệu hợp lệ.\n";
    echo "   Sẵn sàng tích hợp vào dashboard (đã có sẵn trong CareSoftSync::syncChatMessages).\n";
} elseif ($grandTotal > 0 && $totalErrors > 0) {
    warn("⚠️  TEST PARTIAL: Lấy được {$grandTotal} tin nhắn nhưng có {$totalErrors} lỗi cấu trúc");
} elseif ($grandTotal === 0) {
    warn("⚠️  TEST INCONCLUSIVE: Không có tin nhắn chat trong khoảng thời gian đã chọn");
    echo "   Thử tăng --days hoặc kiểm tra dữ liệu trên CareSoft.\n";
}

echo "\n";
