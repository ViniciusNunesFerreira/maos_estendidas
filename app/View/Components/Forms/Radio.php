<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class Radio extends Component
{
    public function __construct(
        public string $name,
        public string $value,
        public ?string $label = null,
        public ?string $description = null,
        public bool $checked = false,
        public bool $required = false,
    ) {}

    public function render()
    {
        return view('components.forms.radio');
    }

    public function errors()
    {
        return session('errors') ? session('errors')->get($this->name) : [];
    }

    public function hasError(): bool
    {
        return session('errors') && session('errors')->has($this->name);
    }

    public function isChecked(): bool
    {
        $old = old($this->name);
        
        if ($old !== null) {
            return $old == $this->value;
        }
        
        return $this->checked;
    }
}