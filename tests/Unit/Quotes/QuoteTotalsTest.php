<?php

use App\Domain\Quotes\QuoteTotals as T;

it('computes item totals in cents', function (float $qty, int $unit, int $total) {
    expect(T::itemTotal($qty, $unit))->toBe($total);
})->with([[1, 280000, 280000], [2, 45000, 90000], [1.5, 10001, 15002], [0.25, 3, 1]]);

it('rejects invalid items', function (float $qty, int $unit) {
    T::itemTotal($qty, $unit);
})->with([[0, 100], [-1, 100], [1, -1]])->throws(InvalidArgumentException::class);

it('computes subtotal, discount, total and deposit', function () {
    expect(T::compute([280000, 90000], 20000, 10, 30))->toBe([
        'subtotal' => 370000, 'discount' => 20000, 'total' => 350000, 'deposit' => 105000,
    ]);
});

it('truncates the deposit to the cent', function () {
    expect(T::compute([33333], 0, 0, 30)['deposit'])->toBe(9999);
});

it('enforces the discount limits', function (int $discount, int $max) {
    T::compute([100000], $discount, $max, 30);
})->with([
    'above max %' => [10001, 10],
    'negative' => [-1, 10],
    'above subtotal' => [100001, 100],
])->throws(InvalidArgumentException::class);

it('rejects an invalid deposit percent', function () {
    T::compute([100], 0, 0, 101);
})->throws(InvalidArgumentException::class);
