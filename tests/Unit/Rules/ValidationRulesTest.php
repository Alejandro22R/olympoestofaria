<?php

use App\Domain\Shared\Rules\BrazilianPlate;
use App\Domain\Shared\Rules\Cnpj;
use App\Domain\Shared\Rules\Cpf;
use App\Domain\Shared\Rules\E164Phone;
use App\Domain\Shared\Rules\VehicleYear;
use Illuminate\Support\Facades\Validator;

function passesRule(object $rule, mixed $value): bool
{
    return Validator::make(['v' => $value], ['v' => [$rule]])->passes();
}

it('validates Brazilian plates', function (string $plate, bool $valid) {
    expect(passesRule(new BrazilianPlate, $plate))->toBe($valid);
})->with([
    'old with dash' => ['ABC-1234', true],
    'old without dash' => ['ABC1234', true],
    'mercosul' => ['ABC1D23', true],
    'mercosul lowercase' => ['abc1d23', true],
    'too short' => ['AB1234', false],
    'letters at end' => ['ABC12DE', false],
    'mercosul with dash' => ['ABC-1D23', true],
    'random' => ['1234ABC', false],
]);

it('rejects empty plates in the raw check', function () {
    expect(BrazilianPlate::passes(''))->toBeFalse()
        ->and(BrazilianPlate::passes(null))->toBeFalse();
});

it('normalizes plates', function () {
    expect(BrazilianPlate::normalize(' abc-1d23 '))->toBe('ABC1D23');
});

it('validates CPF check digits', function (string $cpf, bool $valid) {
    expect(passesRule(new Cpf, $cpf))->toBe($valid);
})->with([
    'formatted' => ['529.982.247-25', true],
    'digits' => ['52998224725', true],
    'wrong digit' => ['52998224724', false],
    'repeated' => ['111.111.111-11', false],
    'short' => ['1234567890', false],
]);

it('validates CNPJ check digits', function (string $cnpj, bool $valid) {
    expect(passesRule(new Cnpj, $cnpj))->toBe($valid);
})->with([
    'formatted' => ['11.222.333/0001-81', true],
    'digits' => ['11222333000181', true],
    'wrong digit' => ['11222333000180', false],
    'repeated' => ['00000000000000', false],
    'short' => ['1122233300018', false],
]);

it('validates E.164 phones', function (string $phone, bool $valid) {
    expect(passesRule(new E164Phone, $phone))->toBe($valid);
})->with([
    'brazil mobile' => ['+5511987654321', true],
    'spain' => ['+34612345678', true],
    'no plus' => ['5511987654321', false],
    'spaces' => ['+55 11 98765 4321', false],
    'leading zero' => ['+0551198765', false],
    'too short' => ['+551234', false],
]);

it('normalizes phones to E.164', function () {
    expect(E164Phone::normalize('+55 (11) 98765-4321'))->toBe('+5511987654321');
});

it('validates vehicle years', function (mixed $year, bool $valid) {
    expect(passesRule(new VehicleYear, $year))->toBe($valid);
})->with([
    'min' => [1950, true],
    'below min' => [1949, false],
    'current' => [(int) date('Y'), true],
    'next year' => [(int) date('Y') + 1, true],
    'two years ahead' => [(int) date('Y') + 2, false],
    'text' => ['abc', false],
    'decimal' => [2000.5, false],
]);

it('returns translated messages', function () {
    app()->setLocale('es');
    $v = Validator::make(['plate' => 'X'], ['plate' => [new BrazilianPlate]]);

    expect($v->errors()->first('plate'))->toContain('Mercosul');
});
