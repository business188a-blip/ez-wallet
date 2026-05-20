<?php
return [
    'app_name' => 'EZ Wallet Secure Financial Transaction',
    'base_path' => '/ez_wallet_secure/public',
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'ez_wallet_secure',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'currency' => 'PKR',
    'upload_dir' => dirname(__DIR__) . '/uploads/avatars',
    'max_upload_size' => 2097152,
];
