<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CareSoftApiClient
{
    private string $host;
    private string $hostBackup;
    private string $domain;
    private string $token;
    private int $perPage;
    private int $maxRetries;
    private int $retryDelayMs;
    private int $rateLimitDelayMs;
    private int $timeout;

    public function __construct()
    {
        $this->host = config('caresoft.api_host');
        $this->hostBackup = config('caresoft.api_host_backup');
        $this->domain = config('caresoft.domain');
        $this->token = config('caresoft.api_token');
        $this->perPage = config('caresoft.per_page', 500);
        $this->maxRetries = config('caresoft.max_retries', 3);
        $this->retryDelayMs = config('caresoft.retry_delay_ms', 1000);
        $this->rateLimitDelayMs = config('caresoft.rate_limit_delay_ms', 200);
        $this->timeout = config('caresoft.timeout', 30);
    }

    private function baseUrl(bool $backup = false): string
    {
        $host = $backup ? $this->hostBackup : $this->host;
        return rtrim($host, '/') . '/' . $this->domain;
    }

    public function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl() . '/' . ltrim($endpoint, '/');

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                usleep($this->rateLimitDelayMs * 1000);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])->timeout($this->timeout)->get($url, $params);

                if ($response->status() === 401) {
                    Log::error('CareSoft API auth failed', ['endpoint' => $endpoint]);
                    return null;
                }

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 429) {
                    Log::warning('CareSoft API rate limited', ['attempt' => $attempt]);
                    usleep($this->retryDelayMs * 1000 * $attempt);
                    continue;
                }

                if ($response->serverError()) {
                    $backupUrl = $this->baseUrl(true) . '/' . ltrim($endpoint, '/');
                    $backupResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->token,
                        'Content-Type' => 'application/json',
                    ])->timeout($this->timeout)->get($backupUrl, $params);

                    if ($backupResponse->successful()) {
                        return $backupResponse->json();
                    }
                }

                Log::warning('CareSoft API error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'attempt' => $attempt,
                ]);

            } catch (\Exception $e) {
                Log::warning('CareSoft API exception', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelayMs * 1000 * $attempt);
                }
            }
        }

        return null;
    }

    public function getAgents(): ?array
    {
        return $this->get('api/v1/agents');
    }

    public function getGroups(): ?array
    {
        return $this->get('api/v1/groups');
    }

    public function getServices(?int $type = null): ?array
    {
        $params = [];
        if ($type !== null) $params['type'] = $type;
        return $this->get('api/v1/services', $params);
    }

    public function getTickets(array $params = []): ?array
    {
        if (!isset($params['count'])) $params['count'] = $this->perPage;
        if (!isset($params['page'])) $params['page'] = 1;
        return $this->get('api/v1/tickets', $params);
    }

    public function getCalls(array $params = []): ?array
    {
        if (!isset($params['count'])) $params['count'] = $this->perPage;
        if (!isset($params['page'])) $params['page'] = 1;
        return $this->get('api/v1/calls', $params);
    }

    public function getChats(array $params = []): ?array
    {
        if (!isset($params['count'])) $params['count'] = $this->perPage;
        if (!isset($params['page'])) $params['page'] = 1;
        return $this->get('api/v1/chats', $params);
    }

    /**
     * Get chat messages detail
     * API: GET {{domain}}/api/v1/chats/messages
     * @param array $params - start_time_since (required), start_time_to, conversation_type, conversation_id, etc.
     */
    public function getChatMessages(array $params = []): ?array
    {
        if (!isset($params['count'])) $params['count'] = $this->perPage;
        if (!isset($params['page'])) $params['page'] = 1;
        return $this->get('api/v1/chats/messages', $params);
    }

    public function getAllPages(string $method, array $params, string $dataKey, ?callable $onPage = null): array
    {
        $allData = [];
        $page = 1;
        $params['count'] = $this->perPage;

        do {
            $params['page'] = $page;
            $response = $this->$method($params);

            if (!$response || !isset($response[$dataKey])) {
                break;
            }

            $items = $response[$dataKey];
            $allData = array_merge($allData, $items);

            if ($onPage) {
                $onPage($page, count($items), $response['numFound'] ?? count($allData));
            }

            $numFound = $response['numFound'] ?? 0;
            $hasMore = count($items) >= $this->perPage && count($allData) < $numFound;
            $page++;

        } while ($hasMore);

        return $allData;
    }
}
