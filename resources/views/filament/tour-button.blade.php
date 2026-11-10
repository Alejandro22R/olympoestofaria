@if (\App\Domain\Tours\TourCatalog::keyForRoute(request()->route()?->getName()))
    <x-filament::icon-button
        icon="heroicon-o-question-mark-circle"
        color="gray"
        size="lg"
        :label="__('tours.labels.start')"
        :tooltip="__('tours.labels.start')"
        data-olympo-tour-start
    />
@endif
