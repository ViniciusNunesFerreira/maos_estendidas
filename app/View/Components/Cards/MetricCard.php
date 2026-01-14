<?php
// app/View/Components/Cards/MetricCard.php

namespace App\View\Components\Cards;

use Illuminate\View\Component;
use Illuminate\View\View;

class MetricCard extends Component
{
    public string $title;
    public string $value;
    public ?string $icon;
    public string $variant;
    public ?string $trend;
    public ?string $trendValue;
    public ?string $footer;

    public function __construct(
        string $title,
        string $value,
        ?string $icon = null,
        string $variant = 'default',
        ?string $trend = null,
        ?string $trendValue = null,
        ?string $footer = null
    ) {
        $this->title = $title;
        $this->value = $value;
        $this->icon = $icon;
        $this->variant = $variant;
        $this->trend = $trend;
        $this->trendValue = $trendValue;
        $this->footer = $footer;
    }

    public function render(): View
    {
        return view('components.cards.metric-card');
    }

    public function getIconBgColor(): string
    {
        $colors = [
            'default' => 'bg-gray-100',
            'primary' => 'bg-blue-100',
            'success' => 'bg-green-100',
            'danger' => 'bg-red-100',
            'warning' => 'bg-yellow-100',
            'info' => 'bg-cyan-100',
        ];

        return $colors[$this->variant] ?? $colors['default'];
    }

    public function getIconTextColor(): string
    {
        $colors = [
            'default' => 'text-gray-600',
            'primary' => 'text-blue-600',
            'success' => 'text-green-600',
            'danger' => 'text-red-600',
            'warning' => 'text-yellow-600',
            'info' => 'text-cyan-600',
        ];

        return $colors[$this->variant] ?? $colors['default'];
    }

    public function getTrendIcon(): string
    {
        return match($this->trend) {
            'up' => 'trending-up',
            'down' => 'trending-down',
            default => 'minus',
        };
    }

    public function getTrendColor(): string
    {
        return match($this->trend) {
            'up' => 'text-green-600',
            'down' => 'text-red-600',
            default => 'text-gray-500',
        };
    }
}