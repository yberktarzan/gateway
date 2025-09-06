<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ElasticLogService
{
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {}

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level, $context)) {
            return;
        }

        try {
            $document = $this->buildDocument($level, $message, $context);
            $elasticsearchSent = false;

            // Try Elasticsearch first
            if (config('api.elasticsearch.enabled', true)) {
                $elasticsearchSent = $this->sendToElasticsearch($document);
            }

            // Always log to local (with status indicator)
            if (config('api.log_to_local_fallback', true)) {
                $prefix = $elasticsearchSent ? '[ELASTIC]' : '[ELASTIC-FALLBACK]';
                Log::channel('daily')->log($level, "$prefix $message", $document);
            }
        } catch (Throwable $e) {
            // Emergency fallback
            Log::channel('daily')->error('[ELASTIC-ERROR] Failed to log', [
                'original_message' => $message,
                'original_level' => $level,
                'error' => $e->getMessage(),
                'fallback_used' => true,
            ]);
        }
    }

    private function sendToElasticsearch(array $document): bool
    {
        try {
            if (!config('api.elasticsearch.enabled', true)) {
                return false;
            }

            $host = config('api.elasticsearch.host', 'http://localhost:9200');
            $index = config('api.elasticsearch.index', 'gateway-logs-' . now()->format('Y.m.d'));
            $timeout = (int) config('api.elasticsearch.timeout', 3);

            // Health check first
            if (!$this->isElasticsearchHealthy($host, $timeout)) {
                return false;
            }

            // Send document
            $response = $this->httpClient
                ->timeout($timeout)
                ->post("{$host}/{$index}/_doc", $document);

            return $response->successful();
        } catch (Throwable $e) {
            Log::debug('Elasticsearch send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function isElasticsearchHealthy(string $host, int $timeout): bool
    {
        try {
            $response = $this->httpClient
                ->timeout($timeout)
                ->get("{$host}/_cluster/health");

            if (!$response->successful()) {
                return false;
            }

            $health = $response->json();
            return isset($health['status']) && in_array($health['status'], ['green', 'yellow']);
        } catch (Throwable $e) {
            Log::debug('Elasticsearch health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function shouldLog(string $level, array $context): bool
    {
        // Force logging
        if (config('api.log_force', false) || ($context['force'] ?? false)) {
            return true;
        }

        $status = $context['http']['status'] ?? null;

        // Always log 4xx/5xx
        if ($status && $status >= 400) {
            return true;
        }

        // Check 2xx config
        if ($status && $status >= 200 && $status < 300) {
            return config('api.log_responses_per_type.success', false);
        }

        // Always log error/warning levels
        return in_array($level, ['error', 'warning', 'critical', 'alert', 'emergency'], true);
    }

    private function buildDocument(string $level, string $message, array $context): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'app' => config('app.name', 'Laravel'),
            'env' => config('app.env', 'production'),
            'message' => $message,
            'domain' => $context['domain'] ?? null,
            'action' => $context['action'] ?? null,
            'http' => $this->buildHttpContext($context),
            'user' => $this->buildUserContext(),
            'exception' => $this->buildExceptionContext($context['exception'] ?? null),
            'context' => $this->redactSensitiveData(
                collect($context)
                    ->except(['domain', 'action', 'exception', 'http', 'user', 'force'])
                    ->filter()
                    ->toArray()
            ),
        ];
    }

    private function buildHttpContext(array $context): array
    {
        $request = request();

        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $context['http']['status'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->header('X-Request-Id', Str::uuid()->toString()),
        ];
    }

    private function buildUserContext(): array
    {
        $userId = request()->header('X-User-Id');

        if (!$userId) {
            return [];
        }

        $context = ['id' => (int) $userId];

        if ($userRole = request()->header('X-User-Role')) {
            $context['role'] = $userRole;
        }

        return $context;
    }

    private function buildExceptionContext(?Throwable $exception): ?array
    {
        if (!$exception) {
            return null;
        }

        $trace = collect($exception->getTrace())
            ->take(10)
            ->map(fn ($frame) => [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
            ])
            ->toArray();

        return [
            'class' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'trace' => $trace,
        ];
    }

    private function redactSensitiveData(array $data): array
    {
        $redactKeys = config('api.redact_keys', []);

        return collect($data)->map(function ($value, $key) use ($redactKeys) {
            if (is_array($value)) {
                return $this->redactSensitiveData($value);
            }

            $lowerKey = strtolower((string) $key);
            foreach ($redactKeys as $redactKey) {
                if (str_contains($lowerKey, strtolower($redactKey))) {
                    return '***REDACTED***';
                }
            }

            return $value;
        })->toArray();
    }
}
