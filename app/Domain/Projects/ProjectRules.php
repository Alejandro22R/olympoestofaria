<?php

namespace App\Domain\Projects;

use App\Domain\Shared\Rules\E164Phone;
use App\Domain\Shared\Rules\VehicleYear;
use Illuminate\Validation\Rule;

/**
 * Reglas de servidor del constructor (portal). Las usan el componente Livewire y la Action.
 */
final class ProjectRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function vehicle(): array
    {
        return [
            'vehicle.vehicle_model_id' => ['nullable', 'integer', Rule::exists('vehicle_models', 'id')->where('is_active', true)],
            'vehicle.brand' => ['required', 'string', 'max:60'],
            'vehicle.model' => ['required', 'string', 'max:60'],
            'vehicle.year' => ['nullable', new VehicleYear],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function pieces(): array
    {
        return [
            'pieces' => ['required', 'array', 'min:1', 'max:12'],
            'pieces.*.service_type_id' => ['required', 'integer', 'distinct', Rule::exists('service_types', 'id')->where('is_active', true)],
            'pieces.*.material_variant_id' => ['nullable', 'integer', Rule::exists('material_variants', 'id')->where('is_active', true)],
            'stitching' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function contact(): array
    {
        return [
            'contact.name' => ['required', 'string', 'min:2', 'max:120'],
            'contact.phone' => ['required', 'string', 'max:25'],
            'contact.email' => ['nullable', 'email', 'max:160'],
            'contact.locale' => ['required', Rule::in(['pt_BR', 'es'])],
            'privacy' => ['accepted'],
            'contact_opt_in' => ['boolean'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function all(): array
    {
        return [...self::vehicle(), ...self::pieces(), ...self::contact()];
    }

    public static function phoneIsValid(string $phone): bool
    {
        return E164Phone::passes(E164Phone::normalize($phone));
    }
}
