<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class DatePicker extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $value = null,
        public ?string $placeholder = 'Selecione uma data',
        public bool $required = false,
        public ?string $help = null,
        public ?string $min = null,
        public ?string $max = null,
    ) {}

    public function render()
    {
        return view('components.forms.date-picker');
    }

    public function errors()
    {
        return session('errors') ? session('errors')->get($this->name) : [];
    }

    public function hasError(): bool
    {
        return session('errors') && session('errors')->has($this->name);
    }

    public function old()
    {
        return old($this->name, $this->value);
    }
}