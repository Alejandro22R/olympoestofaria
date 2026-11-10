{{-- Acta de entrega (PDF). Colores = tokens de PLAN.md §2.1 en versión para fondo claro. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ __('deliveries.report.title') }}</title>
<style>
    @font-face { font-family: 'Sora'; font-weight: 600; src: url('{{ $fontDir }}/sora-600.ttf') format('truetype'); }
    @font-face { font-family: 'Sora'; font-weight: 700; src: url('{{ $fontDir }}/sora-700.ttf') format('truetype'); }
    @font-face { font-family: 'Manrope'; font-weight: 400; src: url('{{ $fontDir }}/manrope-400.ttf') format('truetype'); }
    @font-face { font-family: 'Manrope'; font-weight: 700; src: url('{{ $fontDir }}/manrope-700.ttf') format('truetype'); }
    @page { margin: 28px 34px; }
    body { font-family: 'Manrope', sans-serif; font-size: 10.5px; color: #0D0D0D; }
    h1, h2, .num { font-family: 'Sora', sans-serif; }
    h1 { font-size: 20px; margin: 0; font-weight: 700; }
    h2 { font-size: 12px; margin: 16px 0 6px; font-weight: 600; color: #0877B5; text-transform: uppercase; letter-spacing: .5px; }
    .muted { color: #737373; }
    .header { border-bottom: 3px solid #0B8FD9; padding-bottom: 8px; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 4px 6px; vertical-align: top; text-align: left; }
    .grid td, .grid th { border: 1px solid #C9C9C9; }
    .grid th { background: #F4F4F4; font-size: 9px; text-transform: uppercase; color: #3A3A3A; }
    .label { color: #737373; font-size: 9px; text-transform: uppercase; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; color: #FFFFFF; font-weight: 700; }
    .ok { color: #2FA36B; font-weight: 700; }
    .warn { color: #E0A100; font-weight: 700; }
    .box { margin-top: 14px; padding: 8px; border: 1px solid #C9C9C9; font-size: 9.5px; color: #3A3A3A; }
</style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width:58px"><img src="{{ public_path('images/brand/emblem-256.png') }}" alt="" style="width:50px;height:50px"></td>
                <td>
                    <h1>{{ __('deliveries.report.title') }}</h1>
                    <span class="muted">{{ $business->business_name }} · {{ $business->city }}</span>
                </td>
                <td style="text-align:right">
                    <span class="label">{{ __('work_orders.model') }}</span><br>
                    <span class="num" style="font-size:16px; font-weight:700">{{ $workOrder->number() }}</span><br>
                    <span class="muted">{{ $delivery->delivered_at->setTimezone($timezone)->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td><span class="label">{{ __('inspections.report.customer') }}</span><br>{{ $customer->name }}<br><span class="muted">{{ $customer->phone }}</span></td>
            <td><span class="label">{{ __('vehicles.fields.plate') }}</span><br><span class="num" style="font-weight:700">{{ $vehicle->formattedPlate() }}</span></td>
            <td><span class="label">{{ __('vehicles.model') }}</span><br>{{ $vehicle->brand }} {{ $vehicle->model }} {{ $vehicle->year }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ __('deliveries.report.mileage_in') }}</span><br>{{ $inspection?->mileage_km !== null ? number_format($inspection->mileage_km, 0, ',', '.').' km' : '—' }}</td>
            <td><span class="label">{{ __('deliveries.report.mileage_out') }}</span><br>{{ $delivery->mileage_km !== null ? number_format($delivery->mileage_km, 0, ',', '.').' km' : '—' }}</td>
            <td><span class="label">{{ __('deliveries.report.delivered_by') }}</span><br>{{ $delivery->deliverer?->name ?? '—' }}</td>
        </tr>
    </table>

    <h2>{{ __('deliveries.report.services') }}</h2>
    <table class="grid">
        <tr><th>{{ __('work_orders.fields.service') }}</th><th>{{ __('work_orders.fields.description') }}</th><th style="text-align:right">{{ __('work_orders.fields.price') }}</th></tr>
        @forelse ($workOrder->items as $item)
            <tr>
                <td>{{ $item->serviceType?->name ?? '—' }}</td>
                <td>{{ $item->description ?? '' }}</td>
                <td style="text-align:right" class="num">{{ \App\Filament\Support\Money::format($item->price_cents) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">—</td></tr>
        @endforelse
        <tr><td colspan="2" style="text-align:right"><strong>{{ __('work_orders.fields.total') }}</strong></td><td style="text-align:right" class="num"><strong>{{ \App\Filament\Support\Money::format($workOrder->totalCents()) }}</strong></td></tr>
    </table>

    <h2>{{ __('deliveries.report.comparison') }}</h2>
    @if ($inspection && $inspection->marks->isNotEmpty())
        <table>
            <tr>
                <td style="width:130px">@if ($diagram)<img src="{{ $diagram }}" style="width:120px; height:240px">@endif</td>
                <td>
                    <table class="grid">
                        <tr><th>#</th><th>{{ __('inspections.fields.zone') }}</th><th>{{ __('deliveries.report.at_check_in') }}</th><th>{{ __('deliveries.report.at_delivery') }}</th></tr>
                        @foreach ($inspection->marks as $i => $mark)
                            @php($outcome = \App\Domain\Deliveries\Enums\MarkOutcome::tryFrom($delivery->marks_status[(string) $mark->id] ?? ''))
                            <tr>
                                <td><span class="badge" style="background: {{ $mark->damageType->color_hex }}">{{ $i + 1 }}</span></td>
                                <td>{{ __('inspections.zones.'.$mark->zone) }}</td>
                                <td>{{ $mark->damageType->name }}@if ($mark->note) — <span class="muted">{{ $mark->note }}</span>@endif</td>
                                <td class="{{ $outcome === \App\Domain\Deliveries\Enums\MarkOutcome::Repaired ? 'ok' : 'warn' }}">{{ $outcome?->getLabel() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    @else
        <p class="muted">{{ __('deliveries.report.no_marks') }}</p>
    @endif

    @if ($delivery->notes)
        <h2>{{ __('deliveries.fields.notes') }}</h2>
        <p>{{ $delivery->notes }}</p>
    @endif

    @if ($workOrder->warranty)
        <div class="box">
            <strong>{{ __('deliveries.report.warranty') }}:</strong>
            {{ __('deliveries.report.warranty_period', ['from' => $workOrder->warranty->starts_at->format('d/m/Y'), 'to' => $workOrder->warranty->ends_at->format('d/m/Y')]) }}
            <p>{{ $workOrder->warranty->conditions }}</p>
        </div>
    @endif

    <p class="muted" style="margin-top:10px">{{ __('deliveries.report.no_signature') }}</p>
</body>
</html>
