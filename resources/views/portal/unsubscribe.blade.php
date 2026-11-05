@extends('layouts.portal', ['title' => __('communication.unsubscribe_page.title').' · Olympo'])

@section('content')
<div class="mx-auto max-w-xl px-4 py-16 md:py-24">
    <div class="card">
        <h1 class="mb-3 text-3xl font-bold">{{ __('communication.unsubscribe_page.title') }}</h1>
        @if ($done)
            <p class="text-steel-300" role="status">{{ __('communication.unsubscribe_page.done') }}</p>
        @else
            <p class="mb-6 text-steel-300">{{ __('communication.unsubscribe_page.text', ['name' => $customer->name]) }}</p>
            <form method="post" action="{{ request()->fullUrl() }}">
                @csrf
                <button type="submit" class="btn-primary">{{ __('communication.unsubscribe_page.confirm') }}</button>
            </form>
        @endif
        <p class="mt-6 text-xs text-steel-300">{{ __('communication.unsubscribe_page.note') }}</p>
    </div>
</div>
@endsection
