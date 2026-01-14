<?php
// app/View/Components/Button.php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    /**
     * Variante do botão
     */
    public string $variant;

    /**
     * Tamanho do botão
     */
    public string $size;

    /**
     * Se é outline
     */
    public bool $outline;

    /**
     * Se está desabilitado
     */
    public bool $disabled;

    /**
     * Tipo do botão (button, submit, reset)
     */
    public string $type;

    /**
     * Link (se for link ao invés de botão)
     */
    public ?string $href;

    /**
     * Ícone à esquerda
     */
    public ?string $icon;

    /**
     * Ícone à direita
     */
    public ?string $iconRight;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'primary',
        string $size = 'md',
        bool $outline = false,
        bool $disabled = false,
        string $type = 'button',
        ?string $href = null,
        ?string $icon = null,
        ?string $iconRight = null
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->outline = $outline;
        $this->disabled = $disabled;
        $this->type = $type;
        $this->href = $href;
        $this->icon = $icon;
        $this->iconRight = $iconRight;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.button');
    }

    /**
     * Obter classes do botão
     */
    public function getClasses(): string
    {
        $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

        // Tamanhos
        $sizeClasses = [
            'xs' => 'px-2.5 py-1.5 text-xs gap-1',
            'sm' => 'px-3 py-2 text-sm gap-1.5',
            'md' => 'px-4 py-2.5 text-base gap-2',
            'lg' => 'px-6 py-3 text-lg gap-2.5',
            'xl' => 'px-8 py-4 text-xl gap-3',
        ];

        // Cores por variante
        $variantClasses = [
            'primary' => $this->outline
                ? 'border-2 border-blue-600 text-blue-600 bg-transparent hover:bg-blue-50 focus:ring-blue-500'
                : 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            'secondary' => $this->outline
                ? 'border-2 border-gray-300 text-gray-700 bg-transparent hover:bg-gray-50 focus:ring-gray-500'
                : 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
            'success' => $this->outline
                ? 'border-2 border-green-600 text-green-600 bg-transparent hover:bg-green-50 focus:ring-green-500'
                : 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            'danger' => $this->outline
                ? 'border-2 border-red-600 text-red-600 bg-transparent hover:bg-red-50 focus:ring-red-500'
                : 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            'warning' => $this->outline
                ? 'border-2 border-yellow-600 text-yellow-600 bg-transparent hover:bg-yellow-50 focus:ring-yellow-500'
                : 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
            'info' => $this->outline
                ? 'border-2 border-cyan-600 text-cyan-600 bg-transparent hover:bg-cyan-50 focus:ring-cyan-500'
                : 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500',
            'dark' => $this->outline
                ? 'border-2 border-gray-800 text-gray-800 bg-transparent hover:bg-gray-100 focus:ring-gray-700'
                : 'bg-gray-800 text-white hover:bg-gray-900 focus:ring-gray-700',
            'light' => $this->outline
                ? 'border-2 border-gray-200 text-gray-600 bg-transparent hover:bg-gray-50 focus:ring-gray-400'
                : 'bg-white text-gray-800 border border-gray-200 hover:bg-gray-50 focus:ring-gray-400',
        ];

        $disabledClasses = 'opacity-50 cursor-not-allowed pointer-events-none';

        return implode(' ', [
            $baseClasses,
            $sizeClasses[$this->size] ?? $sizeClasses['md'],
            $variantClasses[$this->variant] ?? $variantClasses['primary'],
            $this->disabled ? $disabledClasses : '',
        ]);
    }

    /**
     * Obter tamanho do ícone baseado no tamanho do botão
     */
    public function getIconSize(): string
    {
        return match($this->size) {
            'xs' => 'w-3 h-3',
            'sm' => 'w-4 h-4',
            'md' => 'w-5 h-5',
            'lg' => 'w-6 h-6',
            'xl' => 'w-7 h-7',
            default => 'w-5 h-5',
        };
    }
}