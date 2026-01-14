<?php
// app/View/Components/Forms/Input.php

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\ViewErrorBag;

class Input extends Component
{
    public string $type;
    public ?string $label;
    public string $name;
    public ?string $id;
    public mixed $value;
    public string $placeholder;
    public bool $required;
    public bool $disabled;
    public bool $readonly;
    public ?string $help;
    public mixed $error;
    public ?string $prefix;
    public ?string $suffix;
    public ?string $icon;
    public ?string $mask;

    public function __construct(
        string $type = 'text',
        ?string $label = null,
        string $name = '',
        ?string $id = null,
        mixed $value = null,
        string $placeholder = '',
        bool $required = false,
        bool $disabled = false,
        bool $readonly = false,
        ?string $help = null,
        mixed $error = null,
        ?string $prefix = null,
        ?string $suffix = null,
        ?string $icon = null,
        ?string $mask = null
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->value = $value ?? old($name);
        $this->placeholder = $placeholder;
        $this->required = $required;
        $this->disabled = $disabled;
        $this->readonly = $readonly;
        $this->help = $help;
        $this->error = $error;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->icon = $icon;
        $this->mask = $mask;
    }

    public function render(): View
    {
        return view('components.forms.input');
    }

    /**
     * Verifica se o campo tem erro de validação
     */
    public function hasError(): bool
    {
        return !empty($this->error);
    }

    /**
     * Retorna a mensagem de erro
     */
    public function getErrorMessage(): ?string
    {
       return $this->error;
    }

    /**
     * Retorna as classes CSS do input
     */
    public function getInputClasses(): string
    {
        $base = 'block w-full rounded-lg border shadow-sm transition-colors focus:ring-2 focus:ring-offset-0 sm:text-sm py-2.5';
        
        // Simplificamos a lógica de cores
        $border = $this->hasError()
            ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
        
        $state = $this->disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '';
        $state .= $this->readonly ? ' bg-gray-50' : '';
        
        $padding = ($this->prefix || $this->icon) ? 'pl-10' : 'pl-3';
        $padding .= ($this->suffix || $this->hasError()) ? ' pr-10' : ' pr-3';
        
        return implode(' ', array_filter([$base, $border, $state, $padding]));
    }
}