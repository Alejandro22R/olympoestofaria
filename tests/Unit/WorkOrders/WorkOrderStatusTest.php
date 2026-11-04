<?php

use App\Domain\WorkOrders\Enums\WorkOrderStatus as S;

it('follows the work order state machine', function (S $from, S $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'received → production' => [S::Received, S::InProduction, true],
    'received → ready (skip)' => [S::Received, S::Ready, false],
    'received → delivered (skip)' => [S::Received, S::Delivered, false],
    'production → ready' => [S::InProduction, S::Ready, true],
    'production → delivered (skip)' => [S::InProduction, S::Delivered, false],
    'ready → delivered' => [S::Ready, S::Delivered, true],
    'ready → production (rework)' => [S::Ready, S::InProduction, true],
    'ready → cancelled' => [S::Ready, S::Cancelled, false],
    'delivered → anything' => [S::Delivered, S::InProduction, false],
    'cancelled → anything' => [S::Cancelled, S::Received, false],
]);
