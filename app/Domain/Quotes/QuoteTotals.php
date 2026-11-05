<?php

namespace App\Domain\Quotes;

use InvalidArgumentException;

/**
 * Totales de la cotización en centavos enteros: ítem = round(cantidad × unitario);
 * total = subtotal − descuento; anticipo = total × % (truncado al centavo).
 */
final class QuoteTotals
{
    public static function itemTotal(float $quantity, int $unitPriceCents): int
    {
        if ($quantity <= 0 || $unitPriceCents < 0) {
            throw new InvalidArgumentException('Invalid quote item');
        }

        return (int) round($quantity * $unitPriceCents);
    }

    /**
     * @param  list<int>  $itemTotals
     * @return array{subtotal: int, discount: int, total: int, deposit: int}
     */
    public static function compute(array $itemTotals, int $discountCents, int $maxDiscountPercent, int $depositPercent): array
    {
        $subtotal = array_sum($itemTotals);

        if ($discountCents < 0 || $discountCents > $subtotal) {
            throw new InvalidArgumentException('Invalid discount');
        }
        if ($discountCents * 100 > $subtotal * $maxDiscountPercent) {
            throw new InvalidArgumentException('Discount above the allowed maximum');
        }
        if ($depositPercent < 0 || $depositPercent > 100) {
            throw new InvalidArgumentException('Invalid deposit percent');
        }

        $total = $subtotal - $discountCents;

        return [
            'subtotal' => $subtotal,
            'discount' => $discountCents,
            'total' => $total,
            'deposit' => intdiv($total * $depositPercent, 100),
        ];
    }
}
