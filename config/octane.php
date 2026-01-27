<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | OPcache Pre-Compilation
    |--------------------------------------------------------------------------
    |
    | Indicates if Octane should pre-compile the application's PHP files using
    | OPcache. This will significantly speed up the boot time of the Octane
    | workers. You should ensure OPcache is enabled in your PHP build.
    |
    */

    'cache' => env('OCTANE_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            // Minimal initialization for faster startup
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
        ],

        RequestHandled::class => [
            // No additional processing
        ],

        RequestTerminated::class => [
            // Minimal cleanup
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TaskTerminated::class => [
            // Minimal processing
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TickTerminated::class => [
            // Minimal processing
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            // Remove database disconnection for faster processing
            // DisconnectFromDatabases::class,
            // Remove garbage collection for faster processing
            // CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            // Minimal cleanup
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        // A curated list of services are pre-warmed to improve performance
        // and reduce latency, while trying to balance memory usage.
        'db',
        'cache',
        'log',
        'session',
        'url',
        'view',
        'translator',
        'queue',
        'events',
        'files',
        'config',
        'router',
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole specific configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Swoole specific settings, such as the number
    | of task workers and the location of the Swoole log file.
    |
    */
    'swoole' => [
        'options' => [
            // LOW LATENCY OPTIMIZED CONFIGURATION
            'worker_num' => env('SWOOLE_WORKER_NUM', (function_exists('swoole_cpu_num') ? swoole_cpu_num() : 4)),
            'max_request' => env('SWOOLE_MAX_REQUEST', 5000), // High to minimize worker restarts
            'task_worker_num' => env('SWOOLE_TASK_WORKER_NUM', (function_exists('swoole_cpu_num') ? swoole_cpu_num() : 4)),
            'max_wait_time' => 60,
            'heartbeat_check_interval' => 20, // Balanced heartbeat checks
            'heartbeat_idle_time' => 45,      // Balanced idle time
            'log_file' => storage_path('logs/swoole_http.log'),

            // LOW LATENCY OPTIMIZATIONS
            'reactor_num' => function_exists('swoole_cpu_num') ? swoole_cpu_num() : 4, // Match reactor threads to CPU cores
            'dispatch_mode' => 2, // Packet dispatch mode for consistent load balancing
            'enable_coroutine' => true,
            'coroutine' => [
                'enable_preemptive_scheduler' => false, // Disable for more predictable behavior
                'hook_flags' => SWOOLE_HOOK_ALL,
            ],

            // BALANCED MEMORY AND BUFFER OPTIMIZATIONS
            'buffer_output_size' => 1024 * 1024 * 2, // 2MB output buffer (balanced)
            'socket_buffer_size' => 1024 * 1024 * 128, // 128MB socket buffer (balanced)

            // HTTP-SPECIFIC LOW LATENCY
            'http_parse_post' => true,
            'http_parse_cookie' => true,

            // LOW LATENCY NETWORK TWEAKS
            'open_tcp_nodelay' => true, // Disable Nagle's algorithm for lower latency
            'tcp_fastopen' => true, // Enable TCP fast open
            'enable_reuse_port' => true, // Enable port reuse
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'Modules/**/*.php',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => env('OCTANE_GARBAGE_COLLECTION', 100), // Increased threshold

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | being handled by Octane. You may set this value to 0 to indicate that
    | there isn't a specific time limit on Octane request execution time.
    |
    */

    'max_execution_time' => 30,

];
