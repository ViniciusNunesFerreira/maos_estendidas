<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zapi' => [
        'base_url' => env('ZAPI_BASE_URL'),
        'token' => env('ZAPI_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Getnet Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com Getnet via Cloud API
    | Documentação: https://developers.getnet.com.br/api
    |
    */
    'getnet' => [
        // Ambiente: 'sandbox' ou 'production'
        'environment' => env('GETNET_ENVIRONMENT', 'sandbox'),
        
        // URLs base da API
        'api_url' => env('GETNET_ENVIRONMENT', 'sandbox') === 'sandbox'
            ? 'https://api-sandbox.getnet.com.br'
            : 'https://api.getnet.com.br',
        
        // Credenciais OAuth
        'client_id' => env('GETNET_CLIENT_ID'),
        'client_secret' => env('GETNET_CLIENT_SECRET'),
        
        // Seller ID (fornecido pela Getnet)
        'seller_id' => env('GETNET_SELLER_ID'),
        
        // Secret para validação de webhooks
        'webhook_secret' => env('GETNET_WEBHOOK_SECRET'),
        
        // Timeout para requisições (segundos)
        'timeout' => env('GETNET_TIMEOUT', 30),
        
        // Configurações de retry
        'max_retries' => env('GETNET_MAX_RETRIES', 3),
        'retry_delay' => env('GETNET_RETRY_DELAY', 2), // segundos
        
        // Timeout para transações no terminal (minutos)
        'terminal_timeout' => env('GETNET_TERMINAL_TIMEOUT', 5),
        
        // Habilita logs detalhados
        'debug' => env('GETNET_DEBUG', false),
    ],


];
