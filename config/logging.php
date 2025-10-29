<?php

use App\Logging\CorrelationTap;
use App\Logging\JsonFormatterConfigurator;
use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['stderr'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'tap' => [JsonFormatterConfigurator::class, CorrelationTap::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'with' => [ 'stream' => 'php://stderr' ],
            'tap' => [JsonFormatterConfigurator::class, CorrelationTap::class],
        ],
    ],
];

