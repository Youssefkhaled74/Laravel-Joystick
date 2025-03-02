<?php
return [
    'database' => [
        'uri' => env('FIREBASE_DATABASE_URL', 'https://joystick-3da1f-default-rtdb.firebaseio.com'),
    ],
    'credentials' => [
        'file' => base_path(env('FIREBASE_CREDENTIALS')), // Prepend base path
        'auto_discovery' => true,
    ],
];
?>
