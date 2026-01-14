<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class RadioGroup extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public array $options = [], // ['value' => 'Label']
        public ?string $value = null,
        public bool $required = false,
        public ?string $help = null,
        public bool $inline = false, // Exibir em linha ou coluna
    ) {}

    public function render()
    {
        return view('components.forms.radio-group');
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