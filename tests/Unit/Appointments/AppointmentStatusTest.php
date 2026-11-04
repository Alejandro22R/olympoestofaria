<?php

use App\Domain\Appointments\Enums\AppointmentStatus as S;

it('allows only the defined transitions', function (S $from, S $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    [S::Scheduled, S::Confirmed, true],
    [S::Scheduled, S::Cancelled, true],
    [S::Scheduled, S::CheckedIn, true],
    [S::Confirmed, S::CheckedIn, true],
    [S::Confirmed, S::NoShow, true],
    [S::Confirmed, S::Scheduled, false],
    [S::CheckedIn, S::Cancelled, false],
    [S::Cancelled, S::Scheduled, false],
    [S::Cancelled, S::Confirmed, false],
    [S::NoShow, S::CheckedIn, false],
]);

it('marks which statuses occupy capacity', function () {
    expect(S::occupying())->toBe([S::Scheduled, S::Confirmed, S::CheckedIn]);
});
