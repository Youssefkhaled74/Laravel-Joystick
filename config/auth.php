<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
        'storekeeper' => [
            'driver' => 'jwt',
            'provider' => 'storekeepers',
        ],
        'technicion' => [
            'driver' => 'jwt',
            'provider' => 'technicions',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'storekeepers' => [
            'driver' => 'eloquent',
            'model' => App\Models\StoreKeeper::class,
        ],

        'technicions' => [
            'driver' => 'eloquent',
            'model' => App\Models\Technicion::class,
        ]
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],


    'password_timeout' => 10800,

];
