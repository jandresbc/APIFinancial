<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','siigo_log'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'siigo_db' => [
            'driver' => 'custom',
            'handler' => App\Http\Integrations\Siigo\v2\Logging\MySQLLoggingHandler::class,
            'via' => App\Http\Integrations\Siigo\v2\Logging\MySQLCustomLogger::class,
            'level' => 'debug',
         ],

        'siigo_single' => [
            'driver' => 'single',
            'path' => storage_path('logs/Siigo.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'Siigo' => [
            'driver' => 'stack',
            'channels' => ['siigo_single','siigo_db'],
            'ignore_exceptions' => false,
        ],
        'emailage_db' => [
            'driver' => 'custom',
            'handler' => App\Http\Integrations\Emailage\Logger\EmailageMySqlHandler::class,
            'via' => App\Http\Integrations\Emailage\Logger\EmailageMySqlCustom::class,
            'level' => 'debug',
        ],
        'emailage_single' => [
            'driver' => 'single',
            'path' => storage_path('logs/emailage.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'emailage' => [
            'driver' => 'stack',
            'channels' => ['emailage_db', 'emailage_single'],
            'ignore_exceptions' => false,
        ],
    ],

];
