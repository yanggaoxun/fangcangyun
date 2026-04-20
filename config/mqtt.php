<?php

return [
    'broker' => env('MQTT_BROKER', 'emqx'),
    'port' => env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'laravel_server_'.uniqid()),
    'username' => env('MQTT_USERNAME', 'laravel'),
    'password' => env('MQTT_PASSWORD', 'admin123'),
    'clean_session' => true,
    'keep_alive' => 60,
    'connect_timeout' => 10,
    'socket_timeout' => 10,
    'resend_timeout' => 10,
    'qos' => 1,
    'retain' => false,
    'last_will' => [
        'topic' => 'server/status',
        'message' => json_encode(['status' => 'offline', 'time' => now()->toIso8601String()]),
        'qos' => 1,
        'retain' => true,
    ],
];
