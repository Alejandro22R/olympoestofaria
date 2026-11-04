<?php

namespace App\Domain\Customers\Validation;

use App\Domain\Customers\Enums\DocumentType;
use App\Domain\Shared\Rules\E164Phone;
use App\Models\Customer;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Reglas de servidor del cliente, compartidas por el panel (Filament) y el portal (Form Requests).
 */
final class CustomerRules
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, list<mixed>>
     */
    public static function rules(array $data, ?Customer $customer = null): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['required', new E164Phone],
            'locale' => ['required', Rule::in(['pt_BR', 'es'])],
            'document_type' => ['nullable', Rule::enum(DocumentType::class), 'required_with:document_number'],
            'document_number' => ['nullable', ...DocumentRule::for($data['document_type'] ?? null), self::uniqueDocument($customer)],
            // Guardar documento exige consentimiento LGPD explícito (también se verifica en SaveCustomer).
            'lgpd_consent' => [
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($data, $customer): void {
                    if (filled($data['document_number'] ?? null) && ! $value && $customer?->lgpd_consent_at === null) {
                        $fail('customers.errors.consent_required')->translate();
                    }
                },
            ],
        ];
    }

    public static function uniqueDocument(?Customer $customer): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($customer): void {
            if (blank($value)) {
                return;
            }

            $exists = Customer::query()
                ->where('document_hash', Customer::hashDocument((string) $value))
                ->when($customer?->exists, fn ($q) => $q->whereKeyNot($customer->getKey()))
                ->exists();

            if ($exists) {
                $fail('validation.unique')->translate();
            }
        };
    }
}
