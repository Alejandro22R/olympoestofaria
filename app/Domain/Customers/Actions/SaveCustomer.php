<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Shared\Rules\E164Phone;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Validation\ValidationException;

/**
 * Crea o actualiza un cliente aplicando las reglas de negocio:
 * teléfono normalizado a E.164 y documento solo con consentimiento LGPD.
 */
class SaveCustomer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Customer $customer = null): Customer
    {
        $customer ??= new Customer;

        $consent = (bool) ($data['lgpd_consent'] ?? false);
        unset($data['lgpd_consent']);

        if (isset($data['phone']) && is_string($data['phone'])) {
            $data['phone'] = E164Phone::normalize($data['phone']);
        }

        if (array_key_exists('document_number', $data) && blank($data['document_number'])) {
            $data['document_number'] = null;
            $data['document_type'] = null;
        }

        $customer->fill($data);

        if ($consent && $customer->lgpd_consent_at === null) {
            $customer->lgpd_consent_at = now();
        }

        if ($customer->document_number !== null && $customer->lgpd_consent_at === null) {
            throw ValidationException::withMessages([
                'lgpd_consent' => __('customers.errors.consent_required'),
            ]);
        }

        $customer->branch_id ??= Branch::default()?->id;
        $customer->save();

        return $customer;
    }
}
