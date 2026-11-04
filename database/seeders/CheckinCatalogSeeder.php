<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\DamageType;
use Illuminate\Database\Seeder;

/**
 * Tipos de daño y objetos del vehículo iniciales (editables en Ajustes > Check-in).
 */
class CheckinCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $damages = [
            ['scratch', 'Risco', 'Rayón', '#E0A100'],
            ['tear', 'Rasgo', 'Rotura', '#D64545'],
            ['stain', 'Mancha', 'Mancha', '#737373'],
            ['burn', 'Queimadura', 'Quemadura', '#8E1B1B'],
            ['dent', 'Amassado', 'Abolladura', '#0877B5'],
            ['wear', 'Desgaste', 'Desgaste', '#3A3A3A'],
            ['broken', 'Peça quebrada', 'Pieza rota', '#D64545'],
        ];
        foreach ($damages as $i => [$slug, $pt, $es, $color]) {
            DamageType::query()->updateOrCreate(['slug' => $slug], [
                'name' => ['pt_BR' => $pt, 'es' => $es], 'color_hex' => $color, 'sort' => $i, 'is_active' => true,
            ]);
        }

        $items = [
            ['stereo', 'Som / multimídia', 'Estéreo / multimedia'],
            ['spare_tire', 'Estepe', 'Rueda de auxilio'],
            ['jack', 'Macaco', 'Gato'],
            ['wheel_wrench', 'Chave de roda', 'Llave de rueda'],
            ['triangle', 'Triângulo', 'Triángulo'],
            ['floor_mats', 'Tapetes', 'Alfombras'],
            ['documents', 'Documentos do veículo', 'Documentos del vehículo'],
            ['personal_items', 'Objetos pessoais', 'Objetos personales'],
        ];
        foreach ($items as $i => [$slug, $pt, $es]) {
            ChecklistItem::query()->updateOrCreate(['slug' => $slug], [
                'name' => ['pt_BR' => $pt, 'es' => $es], 'sort' => $i, 'is_active' => true,
            ]);
        }
    }
}
