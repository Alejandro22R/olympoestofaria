<x-mail::message>
# {{ __('inspections.mail.greeting', ['name' => $customer->name]) }}

{{ __('inspections.mail.body', ['plate' => $vehicle->formattedPlate()]) }}

{{ __('inspections.mail.footer') }}

{{ config('app.name') }}
</x-mail::message>
