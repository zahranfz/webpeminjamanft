<?php
// config.example.php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'webpinjamft',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'url' => 'http://localhost:8000',
        'name' => 'Sistem Pengelolaan Ruangan FT Unsoed',
        'env' => 'development', // 'development' or 'production'
        'timezone' => 'Asia/Jakarta'
    ],
    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'user' => 'your_email@unsoed.ac.id',
        'pass' => 'your_password',
        'from_address' => 'noreply@unsoed.ac.id',
        'from_name' => 'Sistem Ruangan FT'
    ]
];
