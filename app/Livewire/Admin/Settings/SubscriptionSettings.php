<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class SubscriptionSettings extends Component
{
    // Valores dos planos
    public float $monthly_amount = 350.00;
    public float $quarterly_amount = 950.00;
    public float $yearly_amount = 3500.00;
    
    // Configurações de faturamento
    public int $billing_close_day = 25;
    public int $payment_due_days = 10;
    public int $max_overdue_invoices = 3;
    
    // Multa e juros
    public float $late_fee_percentage = 2.0;
    public float $daily_interest_percentage = 0.033;
    
    // Notificações
    public bool $send_invoice_email = true;
    public bool $send_invoice_sms = false;
    public int $reminder_days_before = 3;
    public bool $send_overdue_reminder = true;

    // Limite de crédito padrão
    public float $default_credit_limit = 500.00;

    protected $rules = [
        'monthly_amount' => 'required|numeric|min:0',
        'quarterly_amount' => 'required|numeric|min:0',
        'yearly_amount' => 'required|numeric|min:0',
        'billing_close_day' => 'required|integer|min:1|max:28',
        'payment_due_days' => 'required|integer|min:1|max:30',
        'max_overdue_invoices' => 'required|integer|min:1|max:12',
        'late_fee_percentage' => 'required|numeric|min:0|max:10',
        'daily_interest_percentage' => 'required|numeric|min:0|max:1',
        'send_invoice_email' => 'boolean',
        'send_invoice_sms' => 'boolean',
        'reminder_days_before' => 'required|integer|min:1|max:15',
        'send_overdue_reminder' => 'boolean',
        'default_credit_limit' => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $settings = config('casalar.subscription', []);
        
        $this->monthly_amount = $settings['monthly_amount'] ?? 350.00;
        $this->quarterly_amount = $settings['quarterly_amount'] ?? 950.00;
        $this->yearly_amount = $settings['yearly_amount'] ?? 3500.00;
        $this->billing_close_day = $settings['billing_close_day'] ?? 25;
        $this->payment_due_days = $settings['payment_due_days'] ?? 10;
        $this->max_overdue_invoices = $settings['max_overdue_invoices'] ?? 3;
        $this->late_fee_percentage = $settings['late_fee_percentage'] ?? 2.0;
        $this->daily_interest_percentage = $settings['daily_interest_percentage'] ?? 0.033;
        $this->send_invoice_email = $settings['send_invoice_email'] ?? true;
        $this->send_invoice_sms = $settings['send_invoice_sms'] ?? false;
        $this->reminder_days_before = $settings['reminder_days_before'] ?? 3;
        $this->send_overdue_reminder = $settings['send_overdue_reminder'] ?? true;
        $this->default_credit_limit = $settings['default_credit_limit'] ?? 500.00;
    }

    public function save(): void
    {
        $this->validate();

        $settings = [
            'subscription.monthly_amount' => $this->monthly_amount,
            'subscription.quarterly_amount' => $this->quarterly_amount,
            'subscription.yearly_amount' => $this->yearly_amount,
            'subscription.billing_close_day' => $this->billing_close_day,
            'subscription.payment_due_days' => $this->payment_due_days,
            'subscription.max_overdue_invoices' => $this->max_overdue_invoices,
            'subscription.late_fee_percentage' => $this->late_fee_percentage,
            'subscription.daily_interest_percentage' => $this->daily_interest_percentage,
            'subscription.send_invoice_email' => $this->send_invoice_email,
            'subscription.send_invoice_sms' => $this->send_invoice_sms,
            'subscription.reminder_days_before' => $this->reminder_days_before,
            'subscription.send_overdue_reminder' => $this->send_overdue_reminder,
            'subscription.default_credit_limit' => $this->default_credit_limit,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        Cache::forget('casalar_settings');
        
        session()->flash('message', 'Configurações de assinatura salvas com sucesso!');
    }

    public function render()
    {
        return view('livewire.admin.settings.subscription-settings');
    }
}