<?php
// app/View/Components/Forms/Textarea.php

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\ViewErrorBag;

class Textarea extends Component
{
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
    public int $rows;

    public function __construct(
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
        int $rows = 4
    ) {
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
        $this->rows = $rows;
    }

    public function render(): View
    {
        return view('components.forms.textarea');
    }

    /**
     * Verifica se o campo tem erro de validação
     */
    public function hasError(): bool
    {
        if ($this->error !== null) {
            return true;
        }

        $errors = session()->get('errors', new ViewErrorBag());
        return $errors->has($this->name);
    }

    /**
     * Retorna a mensagem de erro
     */
    public function getErrorMessage(): ?string
    {
        if ($this->error !== null) {
            return $this->error;
        }

        $errors = session()->get('errors', new ViewErrorBag());
        return $errors->first($this->name);
    }

    /**
     * Retorna as classes CSS do textarea
     */
    public function getTextareaClasses(): string
    {
        $base = 'block w-full rounded-lg border shadow-sm transition-colors focus:ring-2 focus:ring-offset-0 sm:text-sm p-3';
        
        $border = $this->hasError()
            ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
        
        $state = $this->disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '';
        $state .= $this->readonly ? ' bg-gray-50' : '';
        
        return implode(' ', array_filter([$base, $border, $state]));
    }
}