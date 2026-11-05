<?php

namespace App\Http\Middleware;

use App\Domain\Customers\Access\EnsureCustomerUser;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\WorkOrder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enlaces del cliente (cotización, seguimiento): con firma válida se abre la sesión del cliente
 * dueño del recurso. Sin firma, solo entra quien ya tiene sesión (el cliente o el personal).
 * Nunca reemplaza la sesión de un miembro del personal.
 */
class AuthenticateCustomerLink
{
    public function __construct(private readonly EnsureCustomerUser $ensure) {}

    public function handle(Request $request, Closure $next): Response
    {
        $customer = $this->customerFor($request);
        abort_if($customer === null, 404);

        $current = $request->user();

        if ($request->hasValidSignature()) {
            if ($current === null || (! $current->isStaff() && $current->customer?->id !== $customer->id)) {
                Auth::login($this->ensure->execute($customer));
                $request->session()->regenerate();
            }
        } elseif ($current === null) {
            abort(403);
        }

        return $next($request);
    }

    private function customerFor(Request $request): ?Customer
    {
        $quote = $request->route('quote');
        if ($quote instanceof Quote) {
            return $quote->customer;
        }

        $order = $request->route('workOrder');
        if ($order instanceof WorkOrder) {
            return $order->vehicle->customer;
        }

        return null;
    }
}
