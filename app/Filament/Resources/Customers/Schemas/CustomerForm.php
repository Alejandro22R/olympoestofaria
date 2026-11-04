<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Domain\Customers\Enums\DocumentType;
use App\Domain\Customers\Validation\CustomerRules;
use App\Domain\Customers\Validation\DocumentRule;
use App\Domain\Shared\Rules\E164Phone;
use App\Models\Customer;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('customers.sections.contact'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label(__('customers.fields.name'))
                            ->required()->maxLength(160),
                        TextInput::make('phone')->label(__('customers.fields.phone'))
                            ->tel()->telRegex('/^[+0-9\s().-]{6,25}$/')->required()
                            ->placeholder('+5511987654321')
                            ->helperText(__('customers.fields.phone_help'))
                            ->dehydrateStateUsing(fn (?string $state) => $state ? E164Phone::normalize($state) : null)
                            ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) {
                                if (! E164Phone::passes(E164Phone::normalize((string) $value))) {
                                    $fail('validation.custom_rules.phone_e164')->translate();
                                }
                            }),
                        TextInput::make('email')->label(__('customers.fields.email'))
                            ->email()->maxLength(160),
                        Select::make('locale')->label(__('customers.fields.locale'))
                            ->options(['pt_BR' => __('settings.locales.pt_BR'), 'es' => __('settings.locales.es')])
                            ->default('pt_BR')->required()->native(false),
                        Textarea::make('notes')->label(__('customers.fields.notes'))
                            ->rows(3)->columnSpanFull(),
                    ]),
                Section::make(__('customers.sections.document'))
                    ->columns(2)
                    ->schema([
                        Select::make('document_type')->label(__('customers.fields.document_type'))
                            ->options(DocumentType::class)
                            ->live()
                            ->requiredWith('document_number'),
                        TextInput::make('document_number')->label(__('customers.fields.document_number'))
                            ->maxLength(30)
                            // Nunca se rellena con el valor descifrado: en edición se muestra enmascarado.
                            ->formatStateUsing(fn () => null)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->placeholder(fn (?Customer $record) => $record?->maskedDocument())
                            ->helperText(fn (?Customer $record) => $record?->document_number
                                ? __('customers.fields.document_keep', ['masked' => $record->maskedDocument()])
                                : null)
                            ->rules(fn (Get $get) => DocumentRule::for($get('document_type')) === ['prohibited'] ? [] : DocumentRule::for($get('document_type')))
                            ->rule(fn (?Customer $record) => CustomerRules::uniqueDocument($record)),
                        Checkbox::make('lgpd_consent')->label(__('customers.fields.lgpd_consent'))
                            ->helperText(__('customers.fields.lgpd_consent_help'))
                            ->visible(fn (?Customer $record) => $record?->lgpd_consent_at === null)
                            ->accepted(fn (Get $get) => filled($get('document_number')))
                            ->validationMessages(['accepted' => __('customers.errors.consent_required')])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
