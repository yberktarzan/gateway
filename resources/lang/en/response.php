<?php

return [
    'success' => [
        'default' => 'Operation completed successfully',
        'created' => 'Resource created successfully',
        'updated' => 'Resource updated successfully',
        'deleted' => 'Resource deleted successfully',
        'retrieved' => 'Data retrieved successfully',
        'redirecting' => 'Redirecting...',
    ],

    'error' => [
        'default' => 'An error occurred',
        'validation' => 'Validation failed',
        'not_found' => 'Resource not found',
        'unauthorized' => 'Unauthorized access',
        'forbidden' => 'Access forbidden',
        'bad_request' => 'Bad request',
        'server_error' => 'Internal server error',
        'rate_limited' => 'Too many requests',
        'timeout' => 'Request timeout',
        'service_unavailable' => 'Service temporarily unavailable',
    ],

    'logging' => [
        'request_logged' => 'Request has been logged',
        'log_failed' => 'Failed to log request',
        'elastic_unavailable' => 'Elasticsearch service unavailable',
        'fallback_used' => 'Using fallback logging method',
    ],
];
