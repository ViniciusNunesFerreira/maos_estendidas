<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*', 
        'app/*', 
        'broadcasting/auth', 
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'webhooks/*'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://filhos.paitonayede.com.br',      
        'https://maosestendidas.paitonayede.com.br', // API (útil para o PDV)
        'http://localhost:3000',             
        'http://localhost:5173',
        'http://localhost:1420',
        'tauri://localhost',
        'http://tauri.localhost'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type', 
        'X-Requested-With', 
        'Authorization', 
        'Accept', 
        'Origin', 
        'X-XSRF-TOKEN', // Essencial para Sanctum
        'X-Socket-Id',    // Essencial para Broadcasting
        'X-Origin',
        'Cache-Control', // Adicionado
        'Pragma',        // Adicionado
        'Expires',       // Adicionado
        'X-App-Inertia', // Se usar Inertia
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
