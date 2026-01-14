@props(['product', 'size' => 'square'])

@php
    $classes = [
        'square' => 'aspect-square object-cover rounded-lg shadow-sm',
        'thumb' => 'w-12 h-12 object-cover rounded shadow-sm',
    ][$size] ?? 'w-full h-auto object-cover';
@endphp

<img 
    src="{{ $product->image_url }}" 
    alt="{{ $product->name }}" 
    {{ $attributes->merge(['class' => $classes]) }}
    loading="lazy"
    onerror="this.src='{{ asset('images/no-image.png') }}'"
>