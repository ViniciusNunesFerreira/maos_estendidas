<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class FileUpload extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $accept = null,
        public bool $required = false,
        public ?string $help = null,
        public bool $multiple = false,
        public ?string $preview = null, // URL da imagem atual para preview
        public int $maxSize = 5120, // KB (padrão 5MB)
    ) {}

    public function render()
    {
        return view('components.forms.file-upload');
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
        return old($this->name);
    }
}