<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // CareSoft cache tables (SQLite or MySQL for writable data)
        // Use SQLite by default (DASHBOARD_DB_CONNECTION=sqlite) to avoid requiring Docker MySQL
        // Set DASHBOARD_DB_CONNECTION=mysql if you have a MySQL server available
        'caresoft' => env('DASHBOARD_DB_CONNECTION', 'sqlite') === 'sqlite' ? [
            'driver' => 'sqlite',
            'database' => env('DASHBOARD_DB_DATABASE', database_path('caresoft.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ] : [
            'driver' => 'mysql',
            'host' => env('DASHBOARD_DB_HOST', 'mysql'),
            'port' => env('DASHBOARD_DB_PORT', '3306'),
            'database' => env('DASHBOARD_DB_DATABASE', 'zeus_dashboard'),
            'username' => env('DASHBOARD_DB_USERNAME', 'dashboard_user'),
            'password' => env('DASHBOARD_DB_PASSWORD', 'dashboard_password'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // CSI (Chăm sóc CSI) Dashboard data (SQLite)
        'csi' => [
            'driver' => 'sqlite',
            'database' => env('CSI_DB_DATABASE', database_path('csi.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        // Zeus Core Database (read-only connection to main LMS)
        'zeus_core' => [
            'driver' => 'mysql',
            'host' => env('ZEUS_DB_HOST', '127.0.0.1'),
            'port' => env('ZEUS_DB_PORT', '3306'),
            'database' => env('ZEUS_DB_DATABASE', 'zeus_core'),
            'username' => env('ZEUS_DB_USERNAME', 'forge'),
            'password' => env('ZEUS_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'zeus_dashboard_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
