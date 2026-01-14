<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Admin extends Component
{
    /**
     * Use o "Constructor Promotion" (PHP 8.0+) movendo as propriedades 
     * para dentro dos parênteses do __construct.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $breadcrumbs = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('admin.layouts.app');
    }
}