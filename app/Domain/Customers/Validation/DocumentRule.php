<?php

namespace App\Domain\Customers\Validation;

use App\Domain\Customers\Enums\DocumentType;
use App\Domain\Shared\Rules\Cnpj;
use App\Domain\Shared\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;

final class DocumentRule
{
    /**
     * Regla del número según el tipo de documento (configurable por país en Ajustes > General).
     *
     * @return list<ValidationRule|string>
     */
    public static function for(DocumentType|string|null $type): array
    {
        $type = is_string($type) ? DocumentType::tryFrom($type) : $type;

        return match ($type) {
            DocumentType::Cpf => [new Cpf],
            DocumentType::Cnpj => [new Cnpj],
            DocumentType::Other => ['string', 'max:30'],
            null => ['prohibited'],
        };
    }
}
