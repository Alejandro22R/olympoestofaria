<?php

namespace App\Filament\Support;

use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Component;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Listas del panel en tarjetas (en lugar de tablas anchas): título, estado y 2–3 datos clave
 * con íconos. Búsqueda, filtros y acciones siguen igual.
 */
final class CardList
{
    public static function grid(Table $table, ?string $module = null): Table
    {
        if ($module !== null) {
            // Lista vacía: qué es y qué hacer, en vez de "Nenhum registro".
            $table->emptyStateHeading(__("help.{$module}.empty"))
                ->emptyStateDescription(__("help.{$module}.empty_hint"));
        }

        return $table
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->paginated([12, 24, 48, 96])
            ->defaultPaginationPageOption(12);
    }

    /**
     * Tarjeta estándar: fila de título + estado a la derecha y debajo los detalles.
     *
     * @param  list<Column|Component>  $details
     */
    public static function card(TextColumn $title, ?TextColumn $status = null, array $details = []): Stack
    {
        $title->weight(FontWeight::Bold)->size(TextSize::Large);

        return Stack::make([
            $status ? Split::make([$title, $status->badge()->grow(false)]) : $title,
            Stack::make($details)->space(1),
        ])->space(3);
    }

    /** Dato secundario con ícono (teléfono, fecha, vehículo…). */
    public static function detail(TextColumn $column, string $icon): TextColumn
    {
        return $column->icon($icon)->color('gray')->size(TextSize::Small);
    }
}
