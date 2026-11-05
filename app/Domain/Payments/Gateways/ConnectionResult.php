<?php

namespace App\Domain\Payments\Gateways;

final readonly class ConnectionResult
{
    public function __construct(public bool $ok, public string $message) {}
}
