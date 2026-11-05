@extends('layouts.portal', ['title' => __('portal.catalog.title').' · Olympo'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12 md:py-20">
    <header class="mb-10">
        <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ __('portal.catalog.kicker') }}</p>
        <h1 class="text-3xl font-bold md:text-5xl">{{ __('portal.catalog.title') }}</h1>
        <p class="mt-3 max-w-2xl text-steel-300">{{ __('portal.catalog.intro') }}</p>
    </header>

    <section class="mb-20" aria-labelledby="materials-heading" x-data="{ ...materialPanel(), type: 'all' }">
        <h2 id="materials-heading" class="mb-4 text-2xl font-semibold">{{ __('portal.catalog.materials') }}</h2>
        <div class="mb-6 flex flex-wrap gap-2" role="toolbar" aria-label="{{ __('catalog.fields.type') }}">
            <button type="button" class="chip" :aria-pressed="(type === 'all').toString()" x-on:click="type = 'all'" aria-pressed="true">{{ __('portal.gallery.all') }}</button>
            @foreach ($types as $type)
                <button type="button" class="chip" :aria-pressed="(type === '{{ $type->value }}').toString()" x-on:click="type = '{{ $type->value }}'" aria-pressed="false">{{ $type->getLabel() }}</button>
            @endforeach
        </div>
        <div x-effect="$el.querySelectorAll('[data-material-type]').forEach((li) => li.hidden = type !== 'all' && li.dataset.materialType !== type)">
            @include('portal.partials.materials-grid', ['materials' => $materials])
        </div>
        @include('portal.partials.material-panel')
    </section>

    <section aria-labelledby="gallery-heading">
        <h2 id="gallery-heading" class="mb-4 text-2xl font-semibold">{{ __('portal.catalog.gallery') }}</h2>
        <x-portal.gallery-grid :items="$galleryItems" />
    </section>

    <div class="mt-16 text-center">
        <a href="{{ route('portal.builder') }}" class="btn-primary">{{ __('portal.home.cta_project') }}</a>
    </div>
</div>
@endsection
