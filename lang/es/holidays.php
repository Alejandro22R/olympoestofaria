<?php

return [
    'model' => 'Feriado o cierre',
    'plural' => 'Feriados y cierres',
    'fields' => [
        'date' => 'Fecha',
        'name' => 'Motivo',
        'capacity' => 'Capacidad especial',
        'capacity_help' => 'Déjalo vacío para cerrar el día. Un número > 0 abre el día con esa capacidad.',
        'branch' => 'Sucursal',
        'all_branches' => 'Todas las sucursales',
    ],
];
