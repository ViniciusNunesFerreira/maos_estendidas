<?php
// app/View/Components/Forms/Select.php

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\ViewErrorBag;

class Select extends Component
{
    public ?string $label;
    public string $name;
    public ?string $inputId;
    public mixed $value;
    public bool $required;
    public bool $disabled;
    public ?string $help;
    public mixed $error;
    public string $placeholder;
    public array $options;

    public function __construct(
        ?string $label = null,
        string $name = '',
        ?string $inputId = null,
        mixed $value = null,
        bool $required = false,
        bool $disabled = false,
        ?string $help = null,
        mixed $error = null,
        string $placeholder = 'Selecione...',
        mixed $options = [] 
    ) {
        $this->label = $label;
        $this->name = $name;
        $this->inputId = $inputId ?? $name;
        $this->value = $value ?? old($name);
        $this->required = $required;
        $this->disabled = $disabled;
        $this->help = $help;
        $this->error = $error;
        $this->placeholder = $placeholder;
        
        
        $this->options = $options instanceof \Illuminate\Support\Collection 
            ? $options->toArray() 
            : $options;
    }

    public function render(): View
    {
        return view('components.forms.select', [
            'selectClasses' => $this->getSelectClasses(),
            'errorMessage' => $this->getErrorMessage() // Passa o resultado para a view
        ]);
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
     * Retorna as classes CSS do select
     */
    public function getSelectClasses(): string
    {
        $base = 'block w-full rounded-lg border shadow-sm transition-colors focus:ring-2 focus:ring-offset-0 sm:text-sm py-2.5';
        
        // Simplificamos a lógica de cores
        $border = $this->hasError()
            ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
        
        $state = $this->disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '';
    
        
        return implode(' ', array_filter([$base, $border, $state]));
    }
}