<x-mail::message>
# {{ __('reports.greeting', ['name' => $customer->name]) }}

{{ __("reports.{$kind}.body", ['plate' => $vehicle->formattedPlate()]) }}

{{ __("reports.{$kind}.footer") }}

{{ config('app.name') }}
</x-mail::message>
