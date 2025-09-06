<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Logging\ElasticLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Throwable;

class LogMonitorController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ElasticLogService $elasticLogService,
    ) {}

    public function index(): View
    {
        return view('log-monitor');
    }

    public function getLogs(Request $request): JsonResponse
    {
        try {
            $logs = $this->queryLogs(
                level: $request->get('level', 'all'),
                domain: $request->get('domain', 'all'),
                action: $request->get('action', 'all'),
                search: $request->get('search') ?? '',
                since: $request->get('since'),
                limit: (int) $request->get('limit', 100)
            );

            return $this->successResponse([
                'logs' => $logs->values(),
                'count' => $logs->count(),
                'fallback_mode' => ! $this->isElasticsearchAvailable(),
            ], shouldLog: false);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to fetch logs', 500, [
                'error' => $e->getMessage(),
            ], shouldLog: false);
        }
    }

    public function getFilters(): JsonResponse
    {
        try {
            $filters = $this->getAvailableFilters();

            return $this->successResponse($filters, shouldLog: false);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to fetch filters', 500, [
                'error' => $e->getMessage(),
            ], shouldLog: false);
        }
    }

    public function createTestLog(Request $request): JsonResponse
    {
        try {
            $level = $request->get('level', 'info');
            $message = "Test {$level} log created at ".now()->format('H:i:s');

            $context = [
                'domain' => 'monitor',
                'action' => 'test_log',
                'test_data' => [
                    'created_at' => now()->toISOString(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ];

            $this->elasticLogService->log($level, $message, $context);

            return $this->successResponse([
                'logged' => true,
                'level' => $level,
                'message' => $message,
            ], shouldLog: false);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to create test log', 500, [
                'error' => $e->getMessage(),
            ], shouldLog: false);
        }
    }

    private function queryLogs(
        string $level,
        string $domain,
        string $action,
        ?string $search,
        ?string $since,
        int $limit
    ): Collection {
        // Try Elasticsearch first
        if ($this->isElasticsearchAvailable()) {
            try {
                return $this->queryElasticsearch($level, $domain, $action, $search, $since, $limit);
            } catch (Throwable $e) {
                // Fall back to local logs
            }
        }

        // Fallback to local log files
        return $this->queryLocalLogs($level, $domain, $action, $search, $since, $limit);
    }

    private function queryElasticsearch(
        string $level,
        string $domain,
        string $action,
        ?string $search,
        ?string $since,
        int $limit
    ): Collection {
        $host = config('api.elasticsearch.host', 'http://localhost:9200');
        $index = config('api.elasticsearch.index', 'gateway-logs');

        $query = [
            'size' => $limit,
            'sort' => [['timestamp' => ['order' => 'desc']]],
            'query' => [
                'bool' => [
                    'must' => [],
                ],
            ],
        ];

        // Add filters
        if ($level !== 'all') {
            $query['query']['bool']['must'][] = ['term' => ['level.keyword' => $level]];
        }

        if ($domain !== 'all') {
            $query['query']['bool']['must'][] = ['term' => ['domain.keyword' => $domain]];
        }

        if ($action !== 'all') {
            $query['query']['bool']['must'][] = ['term' => ['action.keyword' => $action]];
        }

        if ($search) {
            $query['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $search,
                    'fields' => ['message', 'context.*'],
                ],
            ];
        }

        if ($since) {
            $query['query']['bool']['must'][] = [
                'range' => [
                    'timestamp' => ['gte' => $since],
                ],
            ];
        }

        $response = $this->httpClient
            ->timeout(config('api.elasticsearch.timeout', 3))
            ->post("{$host}/{$index}/_search", $query);

        if (! $response->successful()) {
            throw new \Exception('Elasticsearch query failed');
        }

        $data = $response->json();
        $hits = $data['hits']['hits'] ?? [];

        return collect($hits)->map(function ($hit) {
            $source = $hit['_source'];

            return [
                'id' => $hit['_id'],
                'timestamp' => $source['timestamp'],
                'level' => $source['level'],
                'level_name' => __('logging.levels.'.$source['level'], [], 'tr'),
                'message' => $source['message'],
                'domain' => $source['domain'] ?? null,
                'domain_name' => $source['domain'] ? __('logging.domains.'.$source['domain'], [], 'tr') : null,
                'action' => $source['action'] ?? null,
                'action_name' => $source['action'] ? __('logging.actions.'.$source['action'], [], 'tr') : null,
                'app' => $source['app'] ?? 'Laravel',
                'env' => $source['env'] ?? 'local',
                'http' => $source['http'] ?? [],
                'user' => $source['user'] ?? [],
                'context' => $source['context'] ?? [],
                'exception' => $source['exception'] ?? null,
            ];
        });
    }

    private function queryLocalLogs(
        string $level,
        string $domain,
        string $action,
        ?string $search,
        ?string $since,
        int $limit
    ): Collection {
        $logPath = storage_path('logs');
        $logFiles = File::glob("{$logPath}/laravel-*.log");

        // Sort files by date (newest first)
        usort($logFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $logs = collect();
        $sinceTime = $since ? Carbon::parse($since) : null;

        foreach ($logFiles as $file) {
            if ($logs->count() >= $limit) {
                break;
            }

            $content = File::get($file);
            $lines = explode("\n", $content);

            foreach (array_reverse($lines) as $line) {
                if ($logs->count() >= $limit || empty(trim($line))) {
                    continue;
                }

                $parsed = $this->parseLogLine($line);
                if (! $parsed) {
                    continue;
                }

                // Apply filters
                if ($level !== 'all' && $parsed['level'] !== $level) {
                    continue;
                }

                if ($domain !== 'all' && $parsed['domain'] !== $domain) {
                    continue;
                }

                if ($action !== 'all' && $parsed['action'] !== $action) {
                    continue;
                }

                if ($search && ! str_contains(strtolower($parsed['message'].' '.json_encode($parsed['context'])), strtolower($search))) {
                    continue;
                }

                if ($sinceTime && Carbon::parse($parsed['timestamp'])->lt($sinceTime)) {
                    continue;
                }

                $logs->push($parsed);
            }
        }

        return $logs->sortByDesc('timestamp');
    }

    private function parseLogLine(string $line): ?array
    {
        // Parse Laravel log format: [2025-09-06 15:43:29] local.INFO: [ELASTIC] Message {"json":"data"}
        if (! preg_match('/^\[([^\]]+)\]\s+(\w+)\.(\w+):\s+(.+)$/', $line, $matches)) {
            return null;
        }

        $timestamp = $matches[1];
        $env = $matches[2];
        $level = strtolower($matches[3]);
        $messageAndContext = $matches[4];

        // Extract message and JSON context
        $lastBracePos = strrpos($messageAndContext, '{');
        if ($lastBracePos === false) {
            $message = trim($messageAndContext);
            $contextData = [];
        } else {
            $message = trim(substr($messageAndContext, 0, $lastBracePos));
            $jsonContext = substr($messageAndContext, $lastBracePos);
            $contextData = json_decode($jsonContext, true) ?? [];
        }

        // Remove [ELASTIC] or [ELASTIC-FALLBACK] prefix from message
        $message = preg_replace('/^\[ELASTIC(-FALLBACK)?\]\s*/', '', $message);

        return [
            'id' => md5($line),
            'timestamp' => Carbon::parse($timestamp)->toISOString(),
            'level' => $level,
            'level_name' => __('logging.levels.'.$level, [], 'tr'),
            'message' => $message,
            'domain' => $contextData['domain'] ?? null,
            'domain_name' => isset($contextData['domain']) ? __('logging.domains.'.$contextData['domain'], [], 'tr') : null,
            'action' => $contextData['action'] ?? null,
            'action_name' => isset($contextData['action']) ? __('logging.actions.'.$contextData['action'], [], 'tr') : null,
            'app' => $contextData['app'] ?? 'Laravel',
            'env' => $env,
            'http' => $contextData['http'] ?? [],
            'user' => $contextData['user'] ?? [],
            'context' => $contextData['context'] ?? [],
            'exception' => $contextData['exception'] ?? null,
        ];
    }

    private function getAvailableFilters(): array
    {
        // Try Elasticsearch aggregations first
        if ($this->isElasticsearchAvailable()) {
            try {
                return $this->getElasticsearchFilters();
            } catch (Throwable $e) {
                // Fall back to local analysis
            }
        }

        // Fallback to local log analysis
        return $this->getLocalFilters();
    }

    private function getElasticsearchFilters(): array
    {
        $host = config('api.elasticsearch.host', 'http://localhost:9200');
        $index = config('api.elasticsearch.index', 'gateway-logs');

        $query = [
            'size' => 0,
            'aggs' => [
                'levels' => ['terms' => ['field' => 'level.keyword', 'size' => 20]],
                'domains' => ['terms' => ['field' => 'domain.keyword', 'size' => 50]],
                'actions' => ['terms' => ['field' => 'action.keyword', 'size' => 100]],
            ],
        ];

        $response = $this->httpClient
            ->timeout(config('api.elasticsearch.timeout', 3))
            ->post("{$host}/{$index}/_search", $query);

        if (! $response->successful()) {
            throw new \Exception('Elasticsearch aggregation failed');
        }

        $data = $response->json();
        $aggregations = $data['aggregations'] ?? [];

        return [
            'levels' => collect($aggregations['levels']['buckets'] ?? [])->pluck('key')->toArray(),
            'domains' => collect($aggregations['domains']['buckets'] ?? [])->pluck('key')->filter()->toArray(),
            'actions' => collect($aggregations['actions']['buckets'] ?? [])->pluck('key')->filter()->toArray(),
        ];
    }

    private function getLocalFilters(): array
    {
        $logPath = storage_path('logs');
        $logFiles = File::glob("{$logPath}/laravel-*.log");

        $levels = collect();
        $domains = collect();
        $actions = collect();

        foreach (array_slice($logFiles, -3) as $file) { // Only recent files
            $content = File::get($file);
            $lines = array_slice(explode("\n", $content), -1000); // Recent lines

            foreach ($lines as $line) {
                $parsed = $this->parseLogLine($line);
                if (! $parsed) {
                    continue;
                }

                $levels->push($parsed['level']);
                if ($parsed['domain']) {
                    $domains->push($parsed['domain']);
                }
                if ($parsed['action']) {
                    $actions->push($parsed['action']);
                }
            }
        }

        return [
            'levels' => $levels->unique()->sort()->values()->toArray(),
            'domains' => $domains->unique()->sort()->values()->toArray(),
            'actions' => $actions->unique()->sort()->values()->toArray(),
        ];
    }

    private function isElasticsearchAvailable(): bool
    {
        if (! config('api.elasticsearch.enabled', true)) {
            return false;
        }

        try {
            $host = config('api.elasticsearch.host', 'http://localhost:9200');
            $response = $this->httpClient
                ->timeout(1)
                ->get("{$host}/_cluster/health");

            return $response->successful();
        } catch (Throwable $e) {
            return false;
        }
    }
}
