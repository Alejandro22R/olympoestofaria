<?php

return [
    'model' => 'Item de estoque',
    'plural' => 'Estoque',
    'movements' => 'Movimentações',
    'units' => ['m2' => 'm²', 'm' => 'm', 'unit' => 'un.', 'l' => 'L', 'kg' => 'kg'],
    'movement_types' => ['in' => 'Entrada', 'consumption' => 'Consumo', 'adjustment' => 'Ajuste'],
    'fields' => [
        'name' => 'Nome',
        'material_variant' => 'Material do catálogo',
        'unit' => 'Unidade',
        'unit_cost' => 'Custo unitário',
        'stock' => 'Estoque',
        'min_stock' => 'Estoque mínimo',
        'quantity' => 'Quantidade',
        'adjust_quantity' => 'Diferença (+/−)',
        'note' => 'Observação',
        'type' => 'Tipo',
        'stock_after' => 'Saldo',
    ],
    'filters' => ['low' => 'Abaixo do mínimo'],
    'actions' => ['stockIn' => 'Registrar entrada', 'adjust' => 'Ajustar estoque'],
    'messages' => [
        'recorded' => 'Movimentação registrada.',
        'stock_by_movements' => 'O estoque só muda por movimentações (entrada, consumo ou ajuste).',
        'adjust_help' => 'Use valores negativos para baixar o estoque (ex.: −2,5).',
    ],
    'errors' => [
        'quantity_positive' => 'A quantidade deve ser maior que zero.',
        'quantity_zero' => 'O ajuste não pode ser zero.',
        'insufficient' => 'Estoque insuficiente de ":item" (disponível: :stock :unit).',
        'missing_for_order' => 'Falta material para esta ordem:',
        'missing_line' => ':item — precisa :needed, há :stock :unit',
    ],
    'cards' => [
        'low' => 'Repor',
        'ok' => 'Em estoque',
        'stock' => 'Em estoque: :stock :unit',
        'min' => 'Mínimo: :min',
        'cost' => ':cost por :unit',
    ],
];
