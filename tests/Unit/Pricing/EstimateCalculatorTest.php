<?php

use App\Domain\Catalog\Enums\PricingUnit as U;
use App\Domain\Pricing\EstimateCalculator as C;
use App\Domain\Pricing\EstimateLine;

it('computes each line in integer cents', function (int $base, U $unit, ?float $m2, int $qty, ?int $matPrice, int $labor, int $material) {
    $line = C::line(1, $base, $unit, $m2, $qty, $matPrice);

    expect($line->laborCents)->toBe($labor)
        ->and($line->materialCents)->toBe($material)
        ->and($line->totalCents())->toBe($labor + $material);
})->with([
    'vehicle, leather 6.5 m²' => [180000, U::Vehicle, 6.5, 1, 32000, 180000, 208000],
    'piece × 2, no material' => [45000, U::Piece, null, 2, null, 90000, 0],
    'piece × 2 with material' => [45000, U::Piece, 0.4, 2, 32000, 90000, 25600],
    'hour, no m²' => [10000, U::Hour, null, 3, 32000, 30000, 0],
    'priced per m²' => [5000, U::SquareMeter, 4.0, 1, 12000, 20000, 48000],
    'rounding to nearest cent' => [0, U::Piece, 0.333, 1, 1001, 0, 333],
]);

it('rejects invalid inputs', function (int $base, int $qty, ?int $mat, ?float $m2) {
    C::line(1, $base, U::Piece, $m2, $qty, $mat);
})->with([
    'negative base' => [-1, 1, null, null],
    'zero quantity' => [100, 0, null, null],
    'negative material price' => [100, 1, -5, 1.0],
    'negative m²' => [100, 1, 100, -1.0],
])->throws(InvalidArgumentException::class);

it('builds the range with margin and rounding (min down, max up)', function () {
    $lines = [new EstimateLine(1, 180000, 208000, 6.5)]; // subtotal 388000

    $estimate = C::range($lines, 15, 5000);

    // 388000 × 0.85 = 329800 → 325000 ; 388000 × 1.15 = 446200 → 450000
    expect($estimate->subtotalCents)->toBe(388000)
        ->and($estimate->minCents)->toBe(325000)
        ->and($estimate->maxCents)->toBe(450000);
});

it('keeps an exact value when the margin is zero and rounding is one cent', function () {
    $estimate = C::range([new EstimateLine(1, 12345, 0, 0)], 0, 1);

    expect($estimate->minCents)->toBe(12345)->and($estimate->maxCents)->toBe(12345);
});

it('returns an empty range without lines', function () {
    $estimate = C::range([], 15, 5000);

    expect($estimate->isEmpty())->toBeTrue()->and($estimate->minCents)->toBe(0)->and($estimate->maxCents)->toBe(0);
});

it('rejects invalid settings', function (int $margin, int $rounding) {
    C::range([new EstimateLine(1, 100, 0, 0)], $margin, $rounding);
})->with([[-1, 100], [101, 100], [10, 0]])->throws(InvalidArgumentException::class);

it('never returns a max below the min', function () {
    $estimate = C::range([new EstimateLine(1, 100, 0, 0)], 100, 5000);

    expect($estimate->minCents)->toBe(0)->and($estimate->maxCents)->toBeGreaterThanOrEqual($estimate->minCents);
});
