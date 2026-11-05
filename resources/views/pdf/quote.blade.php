{{-- Cotización (PDF). Colores = tokens de PLAN.md §2.1 en versión para fondo claro. --}}
@php use App\Filament\Support\Money; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ __('quotes.pdf.title') }}</title>
<style>
    @font-face { font-family: 'Sora'; font-weight: 600; src: url('{{ $fontDir }}/sora-600.ttf') format('truetype'); }
    @font-face { font-family: 'Sora'; font-weight: 700; src: url('{{ $fontDir }}/sora-700.ttf') format('truetype'); }
    @font-face { font-family: 'Manrope'; font-weight: 400; src: url('{{ $fontDir }}/manrope-400.ttf') format('truetype'); }
    @font-face { font-family: 'Manrope'; font-weight: 700; src: url('{{ $fontDir }}/manrope-700.ttf') format('truetype'); }
    @page { margin: 28px 34px; }
    body { font-family: 'Manrope', sans-serif; font-size: 10.5px; color: #0D0D0D; }
    h1, .num { font-family: 'Sora', sans-serif; }
    h1 { font-size: 20px; margin: 0; font-weight: 700; }
    .muted { color: #3A3A3A; }
    .header { border-bottom: 3px solid #0B8FD9; padding-bottom: 8px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 5px 6px; text-align: left; vertical-align: top; }
    .grid td, .grid th { border: 1px solid #C9C9C9; }
    .grid th { background: #F4F4F4; font-size: 9px; text-transform: uppercase; color: #3A3A3A; }
    .r { text-align: right; }
    .total td { font-size: 13px; font-weight: 700; }
    .box { margin-top: 14px; padding: 8px; border: 1px solid #C9C9C9; font-size: 9.5px; }
</style>
</head>
<body>
    <div class="header">
        <table><tr>
            <td><h1>{{ __('quotes.pdf.title') }}</h1><span class="muted">{{ $business->business_name }} · {{ $business->city }}</span></td>
            <td class="r"><span class="num" style="font-size:16px; font-weight:700">{{ $quote->number() }}</span><br>
                <span class="muted">{{ ($quote->sent_at ?? now())->setTimezone($timezone)->format('d/m/Y') }}</span></td>
        </tr></table>
    </div>

    <table class="grid" style="margin-bottom:12px">
        <tr>
            <td><span class="muted">{{ __('quotes.fields.customer') }}</span><br>{{ $quote->customer->name }}<br>{{ $quote->customer->phone }}</td>
            <td><span class="muted">{{ __('quotes.fields.vehicle') }}</span><br>{{ $quote->vehicle_label ?? $quote->vehicle?->label() ?? '—' }}</td>
            <td><span class="muted">{{ __('quotes.fields.valid_until') }}</span><br>{{ $quote->valid_until?->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="grid">
        <tr><th>{{ __('quotes.fields.description') }}</th><th class="r">{{ __('work_orders.fields.quantity') }}</th><th class="r">{{ __('quotes.fields.unit_price') }}</th><th class="r">{{ __('quotes.fields.total') }}</th></tr>
        @foreach ($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="r">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="r num">{{ Money::format($item->unit_price_cents) }}</td>
                <td class="r num">{{ Money::format($item->total_cents) }}</td>
            </tr>
        @endforeach
        @if ($quote->discount_cents > 0)
            <tr><td colspan="3" class="r">{{ __('quotes.fields.subtotal') }}</td><td class="r num">{{ Money::format($quote->subtotal_cents) }}</td></tr>
            <tr><td colspan="3" class="r">{{ __('quotes.fields.discount') }}</td><td class="r num">− {{ Money::format($quote->discount_cents) }}</td></tr>
        @endif
        <tr class="total"><td colspan="3" class="r">{{ __('quotes.fields.total') }}</td><td class="r num">{{ Money::format($quote->total_cents) }}</td></tr>
    </table>

    <div class="box">
        @if ($quote->deposit_cents > 0)
            <p><strong>{{ __('quotes.fields.deposit') }}:</strong> {{ Money::format($quote->deposit_cents) }} ({{ $payments->deposit_percent }}%) — {{ __('quotes.pdf.deposit_note') }}</p>
        @endif
        <p>{{ __('quotes.pdf.validity', ['date' => $quote->valid_until?->format('d/m/Y')]) }}</p>
        @if ($quote->notes)<p>{{ $quote->notes }}</p>@endif
    </div>
</body>
</html>
