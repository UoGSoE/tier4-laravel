<?php

return [

    'connections' => [
        'oldtier4' => [
            'driver' => 'mysql',
            'url' => env('OLD_DATABASE_URL'),
            'host' => env('OLD_DB_HOST', '127.0.0.1'),
            'port' => env('OLD_DB_PORT', '3306'),
            'database' => env('OLD_DB_DATABASE', 'forge'),
            'username' => env('OLD_DB_USERNAME', 'forge'),
            'password' => env('OLD_DB_PASSWORD', ''),
            'unix_socket' => env('OLD_DB_SOCKET', ''),
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

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => false, // disable to preserve original behavior for existing applications
    ],

];
