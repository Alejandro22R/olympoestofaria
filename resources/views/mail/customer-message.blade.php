<x-mail::message>
{!! nl2br(e($body)) !!}

{{ config('app.name') }}

@if ($unsubscribeUrl)
<x-slot:subcopy>
[{{ __('communication.unsubscribe') }}]({{ $unsubscribeUrl }})
</x-slot:subcopy>
@endif
</x-mail::message>
