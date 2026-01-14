<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Rule;

class SubscriptionForm extends Form
{
    public $plan_name = 'Mensalidade Mãos Estendidas';
    public $amount = 120.00;
    public $billing_cycle = 'monthly';
    public $billing_day = 10;
    public $start_date;

    public function rules()
    {
        return [
            'plan_name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'billing_day' => 'required|integer|min:1|max:28',
            'start_date' => 'required|date',
        ];
    }

    public function validationAttributes()
    {
        return [
            'plan_name' => 'nome do plano',
            'amount' => 'valor',
            'billing_cycle' => 'ciclo',
            'billing_day' => 'dia de vencimento',
            'start_date' => 'data de início',
        ];
    }
}