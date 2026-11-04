{{-- Acta de recepción (PDF). Colores = tokens de PLAN.md §2.1 en versión para fondo claro. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ __('inspections.report.title') }}</title>
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
    .grid td { border: 1px solid #C9C9C9; }
    .label { color: #737373; font-size: 9px; text-transform: uppercase; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; color: #FFFFFF; font-weight: 700; }
    .photo { width: 120px; height: 90px; object-fit: cover; margin: 3px 3px 0 0; border: 1px solid #C9C9C9; }
    .box { display: inline-block; width: 10px; height: 10px; border: 1px solid #3A3A3A; text-align: center; font-size: 8px; line-height: 10px; font-weight: 700; }
    .legal { margin-top: 14px; padding: 8px; border: 1px solid #C9C9C9; font-size: 9px; color: #3A3A3A; }
</style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>{{ __('inspections.report.title') }}</h1>
                    <span class="muted">{{ $business->business_name }} · {{ $business->city }}</span>
                </td>
                <td style="text-align:right">
                    <span class="label">{{ __('inspections.report.number') }}</span><br>
                    <span class="num" style="font-size:16px; font-weight:700">#{{ str_pad((string) $inspection->id, 6, '0', STR_PAD_LEFT) }}</span><br>
                    <span class="muted">{{ $inspection->created_at->setTimezone($timezone)->format('d/m/Y H:i') }}</span>
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
            <td><span class="label">{{ __('inspections.fields.mileage_km') }}</span><br><span class="num">{{ $inspection->mileage_km !== null ? number_format($inspection->mileage_km, 0, ',', '.').' km' : '—' }}</span></td>
            <td><span class="label">{{ __('inspections.fields.fuel_level') }}</span><br>{{ $inspection->fuel_level->getLabel() }}</td>
            <td><span class="label">{{ __('inspections.report.inspector') }}</span><br>{{ $inspection->inspector?->name ?? '—' }}</td>
        </tr>
    </table>

    <table style="margin-top:10px">
        <tr>
            <td style="width:220px">
                <h2>{{ __('inspections.report.diagram') }}</h2>
                <img src="{{ $diagram }}" style="width:200px; height:400px">
            </td>
            <td>
                <h2>{{ __('inspections.report.marks') }}</h2>
                @forelse ($inspection->marks as $i => $mark)
                    <div style="margin-bottom:8px">
                        <span class="badge" style="background: {{ $mark->damageType->color_hex }}">{{ $i + 1 }}</span>
                        <strong>{{ __('inspections.zones.'.$mark->zone) }}</strong> — {{ $mark->damageType->name }}
                        @if ($mark->note)<br><span class="muted">{{ $mark->note }}</span>@endif
                        @if (! empty($photos[$mark->id]))
                            <div>
                                @foreach ($photos[$mark->id] as $photo)
                                    <img class="photo" src="{{ $photo }}">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="muted">{{ __('inspections.report.no_marks') }}</p>
                @endforelse

                <h2>{{ __('inspections.fields.items_present') }}</h2>
                <table class="grid">
                    @foreach ($items->chunk(2) as $pair)
                        <tr>
                            @foreach ($pair as $item)
                                <td><span class="box">{{ in_array($item->id, $inspection->items_present, true) ? 'X' : '' }}</span> {{ $item->name }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>

                <h2>{{ __('inspections.fields.observations') }}</h2>
                <p>{{ $inspection->observations ?: '—' }}</p>
            </td>
        </tr>
    </table>

    <div class="legal">
        <p>{{ $legalText }}</p>
        <p><strong>LGPD:</strong> {{ $consentText }} — {{ $inspection->lgpd_consent_at->setTimezone($timezone)->format('d/m/Y H:i') }}</p>
        <p class="muted">{{ __('inspections.report.no_signature') }}</p>
    </div>
</body>
</html>
