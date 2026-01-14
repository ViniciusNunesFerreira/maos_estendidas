<?php
// app/Jobs/SendFilhoApprovalNotification.php

namespace App\Jobs;

use App\Models\Filho;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFilhoApprovalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Filho $filho
    ) {}

    public function handle(SmsService $smsService): void
    {
        if (!$this->filho->phone) {
            return;
        }
        
        $message = sprintf(
            'Mãos Estendidas: Seu cadastro foi aprovado! Baixe o app para acompanhar nossa agenda.',
            number_format($this->filho->credit_limit, 2, ',', '.')
        );
        
        $smsService->send($this->filho->phone, $message);
    }
}
