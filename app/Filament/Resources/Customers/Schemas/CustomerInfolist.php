<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('customers.sections.contact'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label(__('customers.fields.name')),
                        TextEntry::make('phone')->label(__('customers.fields.phone'))
                            ->url(fn (Customer $record) => 'https://wa.me/'.ltrim($record->phone, '+'), shouldOpenInNewTab: true),
                        TextEntry::make('email')->label(__('customers.fields.email'))->placeholder('—'),
                        TextEntry::make('locale')->label(__('customers.fields.locale'))
                            ->formatStateUsing(fn (string $state) => __('settings.locales.'.$state)),
                        TextEntry::make('notes')->label(__('customers.fields.notes'))->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make(__('customers.sections.document'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('document_type')->label(__('customers.fields.document_type'))->badge()->placeholder('—'),
                        TextEntry::make('masked_document')->label(__('customers.fields.document_number'))
                            ->state(fn (Customer $record) => $record->maskedDocument())->placeholder('—'),
                        TextEntry::make('lgpd_consent_at')->label(__('customers.fields.lgpd_consent_at'))
                            ->dateTime()->placeholder('—'),
                        TextEntry::make('document_file')->label(__('customers.sections.document_file'))
                            ->state(function (Customer $record): string {
                                $doc = $record->document;

                                return $doc
                                    ? __('customers.messages.document_on_file', [
                                        'mime' => $doc->mime_type,
                                        'date' => $doc->created_at?->timezone(FilamentTimezone::get())->format('d/m/Y H:i'),
                                        'user' => $doc->uploader->name ?? '—',
                                    ])
                                    : __('customers.messages.no_document');
                            }),
                    ]),
                Section::make(__('privacy.consents.title'))
                    ->collapsible()->collapsed()
                    ->schema([
                        TextEntry::make('anonymized_at')->label(__('privacy.fields.anonymized_at'))->dateTime()
                            ->visible(fn (Customer $record) => $record->isAnonymized())->badge()->color('danger'),
                        RepeatableEntry::make('consents')->hiddenLabel()
                            ->placeholder(__('privacy.consents.empty'))
                            ->columns(4)
                            ->schema([
                                TextEntry::make('created_at')->label(__('privacy.consents.at'))->dateTime(),
                                TextEntry::make('type')->label(__('privacy.consents.type'))
                                    ->formatStateUsing(fn (string $state) => __('privacy.consents.types.'.$state)),
                                TextEntry::make('granted')->label(__('privacy.consents.granted'))->badge()
                                    ->formatStateUsing(fn (bool $state) => $state ? __('privacy.consents.yes') : __('privacy.consents.no'))
                                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                                TextEntry::make('source')->label(__('privacy.consents.source'))
                                    ->formatStateUsing(fn (string $state) => __('privacy.consents.sources.'.$state)),
                            ]),
                    ]),
            ]);
    }
}
