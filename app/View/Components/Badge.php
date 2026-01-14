<?php
// app/View/Components/Badge.php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Badge extends Component
{
    public string $variant;
    public string $size;
    public bool $dot;
    public bool $removable;

    public function __construct(
        string $variant = 'default',
        string $size = 'md',
        bool $dot = false,
        bool $removable = false
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->dot = $dot;
        $this->removable = $removable;
    }

    public function render(): View
    {
        return view('components.badge');
    }

    public function getClasses(): string
    {
        $base = 'inline-flex items-center font-medium rounded-full';

        $sizes = [
            'xs' => 'px-2 py-0.5 text-xs gap-1',
            'sm' => 'px-2.5 py-0.5 text-xs gap-1',
            'md' => 'px-3 py-1 text-sm gap-1.5',
            'lg' => 'px-4 py-1.5 text-base gap-2',
        ];

        $variants = [
            'default' => 'bg-gray-100 text-gray-800',
            'primary' => 'bg-blue-100 text-blue-800',
            'success' => 'bg-green-100 text-green-800',
            'danger' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'info' => 'bg-cyan-100 text-cyan-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'pink' => 'bg-pink-100 text-pink-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
        ];

        return implode(' ', [
            $base,
            $sizes[$this->size] ?? $sizes['md'],
            $variants[$this->variant] ?? $variants['default'],
        ]);
    }

    public function getDotColor(): string
    {
        $colors = [
            'default' => 'bg-gray-500',
            'primary' => 'bg-blue-500',
            'success' => 'bg-green-500',
            'danger' => 'bg-red-500',
            'warning' => 'bg-yellow-500',
            'info' => 'bg-cyan-500',
            'purple' => 'bg-purple-500',
            'pink' => 'bg-pink-500',
            'indigo' => 'bg-indigo-500',
        ];

        return $colors[$this->variant] ?? $colors['default'];
    }
}