<?php
// config/casalar.php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurações de Assinatura/Mensalidade
    |--------------------------------------------------------------------------
    */
    'subscription' => [
        // Valor padrão da mensalidade (R$)
        'default_amount' => env('CASALAR_SUBSCRIPTION_AMOUNT', 120.00),
        
        // Dias após aprovação para primeira cobrança
        'first_billing_days' => env('CASALAR_FIRST_BILLING_DAYS', 30),
        
        // Dia padrão de vencimento da mensalidade
        'default_billing_day' => env('CASALAR_BILLING_DAY', 28),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Crédito
    |--------------------------------------------------------------------------
    */
    'credit' => [
        // Limite de crédito padrão para novos filhos (R$)
        'default_limit' => env('CASALAR_CREDIT_LIMIT', 500.00),
        
        // Máximo de faturas vencidas antes de bloquear
        'max_overdue_invoices' => env('CASALAR_MAX_OVERDUE', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Faturamento
    |--------------------------------------------------------------------------
    */
    'billing' => [
        // Dia padrão de fechamento da fatura de consumo
        'default_close_day' => env('CASALAR_CLOSE_DAY', 28),
        // Dias para vencimento após emissão
        'invoice_due_days' => env('CASALAR_DUE_DAYS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de SMS
    |--------------------------------------------------------------------------
    */
    'sms' => [
        // URL da API de SMS
        'api_url' => env('SMS_API_URL', 'https://api.sms.to/v1/send'),
        
        // Chave de API
        'api_key' => env('SMS_API_KEY'),
        
        // Nome do remetente
        'sender' => env('SMS_SENDER', 'CASALAR'),
        
        // Tempo de expiração do código de reset (minutos)
        'reset_code_expiry' => env('SMS_RESET_EXPIRY', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Pagamento
    |--------------------------------------------------------------------------
    */
    'payment' => [
        // Chave PIX para recebimento
        'pix_key' => env('CASALAR_PIX_KEY'),
        
        // Nome do recebedor PIX
        'pix_receiver' => env('CASALAR_PIX_RECEIVER', 'Mãos Estendidas'),
        
        // CNPJ para recebimento
        'cnpj' => env('CASALAR_CNPJ'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações do SAT
    |--------------------------------------------------------------------------
    */
    'sat' => [
        'enabled' => env('SAT_ENABLED', false),
        'code' => env('SAT_CODE'),
        'signature' => env('SAT_SIGNATURE'),
    ],

];