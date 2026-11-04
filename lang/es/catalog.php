<?php

return [
    'service' => [
        'model' => 'Servicio',
        'plural' => 'Servicios',
    ],
    'material' => [
        'model' => 'Material',
        'plural' => 'Materiales',
    ],
    'variant' => [
        'model' => 'Color / variante',
        'plural' => 'Colores y variantes',
    ],
    'fields' => [
        'slug' => 'Identificador',
        'name_pt' => 'Nombre (portugués)',
        'name_es' => 'Nombre (español)',
        'description_pt' => 'Descripción (portugués)',
        'description_es' => 'Descripción (español)',
        'description' => 'Descripción',
        'name' => 'Nombre',
        'base_price' => 'Precio base',
        'pricing_unit' => 'Unidad',
        'estimated_hours' => 'Horas estimadas',
        'material_m2' => 'Material estimado (m²)',
        'sort' => 'Orden',
        'is_active' => 'Activo',
        'type' => 'Tipo',
        'supplier' => 'Proveedor',
        'origin' => 'Origen',
        'cost_per_m2' => 'Costo por m²',
        'price_per_m2' => 'Precio por m²',
        'color_hex' => 'Color',
        'sku' => 'SKU',
        'variants_count' => 'Variantes',
    ],
    'material_types' => [
        'leather' => 'Cuero',
        'fabric' => 'Tela',
        'vinyl' => 'Vinilo',
        'ink' => 'Tinta (Colortek)',
    ],
    'pricing_units' => [
        'piece' => 'Pieza',
        'm2' => 'm²',
        'hour' => 'Hora',
        'vehicle' => 'Vehículo',
    ],
];
