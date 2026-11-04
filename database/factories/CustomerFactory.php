<?php

namespace Database\Factories;

use App\Domain\Customers\Enums\DocumentType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+5511'.fake()->unique()->numerify('9########'),
            'locale' => 'pt_BR',
        ];
    }

    public function withConsent(): static
    {
        return $this->state(fn () => ['lgpd_consent_at' => now()]);
    }

    public function withCpf(string $cpf = '52998224725'): static
    {
        return $this->state(fn () => [
            'document_type' => DocumentType::Cpf,
            'document_number' => $cpf,
            'lgpd_consent_at' => now(),
        ]);
    }
}
