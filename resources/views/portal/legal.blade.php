@extends('layouts.portal', ['title' => $title.' · Olympo'])

@section('content')
<article class="legal mx-auto max-w-3xl px-4 pb-24 pt-10 md:pt-16">
    <h1 class="mb-2 text-3xl font-bold md:text-4xl">{{ $title }}</h1>
    @if ($updatedAt)
        <p class="mb-8 text-sm text-steel-300">{{ __('privacy.legal.updated_at', ['date' => \Illuminate\Support\Carbon::parse($updatedAt)->format('d/m/Y')]) }}</p>
    @endif
    {{ $html }}
</article>
@endsection
