<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | This value determines the API version returned in responses.
    |
    */
    'version' => env('API_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Log API Responses
    |--------------------------------------------------------------------------
    |
    | This option controls whether API responses should be logged.
    |
    */
    'log_responses' => env('LOG_API_RESPONSES', true),

    /*
    |--------------------------------------------------------------------------
    | Force Log All Responses
    |--------------------------------------------------------------------------
    |
    | true ise success dahil tüm response'lar loglanır.
    |
    */
    'log_force' => env('LOG_FORCE', false),

    /*
    |--------------------------------------------------------------------------
    | Log Responses Per Type
    |--------------------------------------------------------------------------
    |
    | Fine-grained control over which response types should be logged.
    |
    */
    'log_responses_per_type' => [
        'success' => env('LOG_SUCCESS_RESPONSES', false),
        'error' => env('LOG_ERROR_RESPONSES', true),
        'validation' => env('LOG_VALIDATION_RESPONSES', true),
        'not_found' => env('LOG_NOT_FOUND_RESPONSES', true),
        'unauthorized' => env('LOG_UNAUTHORIZED_RESPONSES', true),
        'forbidden' => env('LOG_FORBIDDEN_RESPONSES', true),
        'server_error' => env('LOG_SERVER_ERROR_RESPONSES', true),
        'created' => env('LOG_CREATED_RESPONSES', false),
        'updated' => env('LOG_UPDATED_RESPONSES', false),
        'deleted' => env('LOG_DELETED_RESPONSES', true),
        'paginated' => env('LOG_PAGINATED_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redact Keys
    |--------------------------------------------------------------------------
    |
    | Hassas bilgileri log'larda maskeleyen anahtar kelimeler.
    | Bu listede olan kelimeler ***REDACTED*** olarak görünür.
    |
    */
    'redact_keys' => [
        'password',
        'password_confirmation',
        'token',
        'authorization',
        'cookie',
        'auth',
        'secret',
        'key',
        'api_key',
        'access_token',
        'refresh_token',
        'jwt',
        'bearer',
        'x-api-key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Elasticsearch sunucu ayarları ve log indeks konfigürasyonu.
    |
    */
    'elasticsearch' => [
        'host' => env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
        'index' => env('ELASTICSEARCH_INDEX', 'gateway-logs-'.date('Y.m.d')),
        'timeout' => env('ELASTICSEARCH_TIMEOUT', 3),
        'enabled' => env('ELASTICSEARCH_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Fallback Logging
    |--------------------------------------------------------------------------
    |
    | Elasticsearch başarısız olduğunda yerel log dosyasına yazsın mı?
    |
    */
    'log_to_local_fallback' => env('LOG_TO_LOCAL_FALLBACK', true),
];
