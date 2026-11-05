<div class="mx-auto max-w-xl px-4 pb-24 pt-10 md:pt-16">
    <div class="card">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ $workOrder->number() }}</p>
        <h1 class="mb-2 text-3xl font-bold">{{ __('reviews.portal.title') }}</h1>
        <p class="mb-6 text-steel-300">{{ __('reviews.portal.text', ['vehicle' => $workOrder->vehicle->label()]) }}</p>

        @if ($sent)
            <p class="rounded-xl border border-success/40 bg-success/10 px-4 py-3" role="status">{{ __('reviews.portal.thanks') }}</p>
        @else
            <form wire:submit="save" class="flex flex-col gap-5" novalidate>
                <fieldset>
                    <legend class="mb-2 text-sm">{{ __('reviews.fields.rating') }}</legend>
                    <div class="flex gap-2" role="radiogroup">
                        @foreach (range(1, 5) as $star)
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="rating" value="{{ $star }}" class="peer sr-only" aria-label="{{ trans_choice('reviews.portal.stars', $star, ['count' => $star]) }}">
                                <span @class([
                                    'flex size-11 items-center justify-center rounded-full border font-display text-xl transition-transform duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 hover:scale-110',
                                    'border-brand-500 bg-brand-700 text-paper' => $rating >= $star,
                                    'border-ink-700 text-steel-300' => $rating < $star,
                                ]) aria-hidden="true">★</span>
                            </label>
                        @endforeach
                    </div>
                    @error('rating') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                </fieldset>

                <label class="flex flex-col gap-1 text-sm">
                    {{ __('reviews.fields.comment') }}
                    <textarea wire:model="comment" rows="4" maxlength="1000" class="input"></textarea>
                    @error('comment') <span class="text-danger">{{ $message }}</span> @enderror
                </label>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="consent" class="mt-1">
                    <span>{{ __('reviews.portal.consent') }}</span>
                </label>

                <button type="submit" class="btn-primary self-start" wire:loading.attr="disabled">{{ __('reviews.portal.submit') }}</button>
            </form>
        @endif
    </div>
</div>
