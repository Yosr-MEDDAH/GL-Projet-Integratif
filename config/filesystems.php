<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'image' => [
            'driver' => 'local',
            'root' => "C:\Users\yefer\Desktop\storage",
            //'root' => storage_path("app/storage"),
            'throw' => false,
        ],

        'facture' => [
            'driver' => 'local',
            'root' => "C:\Users\yefer\Desktop\mes_bordreaux",
            //'root' => storage_path("app/mes_bordreaux"),
        ],

        'reclamation' => [
            'driver' => 'local',
            'root' => "C:\Users\yefer\Desktop\storage",
            //'root' => storage_path("app/storage"),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
