<?php

return [
    'model' => 'Ítem de inventario',
    'plural' => 'Inventario',
    'movements' => 'Movimientos',
    'units' => ['m2' => 'm²', 'm' => 'm', 'unit' => 'u.', 'l' => 'L', 'kg' => 'kg'],
    'movement_types' => ['in' => 'Entrada', 'consumption' => 'Consumo', 'adjustment' => 'Ajuste'],
    'fields' => [
        'name' => 'Nombre',
        'material_variant' => 'Material del catálogo',
        'unit' => 'Unidad',
        'unit_cost' => 'Costo unitario',
        'stock' => 'Stock',
        'min_stock' => 'Stock mínimo',
        'quantity' => 'Cantidad',
        'adjust_quantity' => 'Diferencia (+/−)',
        'note' => 'Observación',
        'type' => 'Tipo',
        'stock_after' => 'Saldo',
    ],
    'filters' => ['low' => 'Bajo el mínimo'],
    'actions' => ['stockIn' => 'Registrar entrada', 'adjust' => 'Ajustar stock'],
    'messages' => [
        'recorded' => 'Movimiento registrado.',
        'stock_by_movements' => 'El stock solo cambia con movimientos (entrada, consumo o ajuste).',
        'adjust_help' => 'Usa valores negativos para bajar el stock (p. ej.: −2,5).',
    ],
    'errors' => [
        'quantity_positive' => 'La cantidad debe ser mayor que cero.',
        'quantity_zero' => 'El ajuste no puede ser cero.',
        'insufficient' => 'Stock insuficiente de ":item" (disponible: :stock :unit).',
        'missing_for_order' => 'Falta material para esta orden:',
        'missing_line' => ':item — necesita :needed, hay :stock :unit',
    ],
    'cards' => [
        'low' => 'Reponer',
        'ok' => 'En stock',
        'stock' => 'En stock: :stock :unit',
        'min' => 'Mínimo: :min',
        'cost' => ':cost por :unit',
    ],
];
