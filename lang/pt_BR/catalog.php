<?php

return [
    'service' => [
        'model' => 'Serviço',
        'plural' => 'Serviços',
    ],
    'material' => [
        'model' => 'Material',
        'plural' => 'Materiais',
    ],
    'variant' => [
        'model' => 'Cor / variante',
        'plural' => 'Cores e variantes',
    ],
    'fields' => [
        'slug' => 'Identificador',
        'name_pt' => 'Nome (português)',
        'name_es' => 'Nome (espanhol)',
        'description_pt' => 'Descrição (português)',
        'description_es' => 'Descrição (espanhol)',
        'description' => 'Descrição',
        'name' => 'Nome',
        'base_price' => 'Preço base',
        'pricing_unit' => 'Unidade',
        'estimated_hours' => 'Horas estimadas',
        'material_m2' => 'Material estimado (m²)',
        'sort' => 'Ordem',
        'is_active' => 'Ativo',
        'type' => 'Tipo',
        'supplier' => 'Fornecedor',
        'origin' => 'Origem',
        'cost_per_m2' => 'Custo por m²',
        'price_per_m2' => 'Preço por m²',
        'color_hex' => 'Cor',
        'sku' => 'SKU',
        'variants_count' => 'Variantes',
    ],
    'material_types' => [
        'leather' => 'Couro',
        'fabric' => 'Tecido',
        'vinyl' => 'Vinil',
        'ink' => 'Tinta (Colortek)',
    ],
    'pricing_units' => [
        'piece' => 'Peça',
        'm2' => 'm²',
        'hour' => 'Hora',
        'vehicle' => 'Veículo',
    ],
];
