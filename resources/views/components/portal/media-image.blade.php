@props(['item', 'collection' => 'image', 'conversion' => 'large', 'eager' => false, 'alt' => null])
{{-- Imagen responsiva (WebP + srcset de medialibrary) o marcador si aún no hay foto. --}}
@php
    $media = $item?->getFirstMedia($collection);
    $label = $alt ?? $item?->title ?? '';
@endphp
@if ($media && $media->hasGeneratedConversion($conversion))
    {{ $media->img($conversion, [
        'alt' => $label,
        'loading' => $eager ? 'eager' : 'lazy',
        'decoding' => 'async',
        'class' => $attributes->get('class', 'h-full w-full object-cover'),
        'sizes' => $attributes->get('sizes', '(min-width: 1024px) 33vw, 100vw'),
    ]) }}
@elseif ($media)
    <img src="{{ $media->getUrl() }}" alt="{{ $label }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" class="{{ $attributes->get('class', 'h-full w-full object-cover') }}">
@else
    <div role="img" aria-label="{{ $label }}" class="{{ $attributes->get('class', 'h-full w-full') }} bg-[radial-gradient(circle_at_30%_20%,var(--color-ink-700),var(--color-ink-950))]"></div>
@endif
