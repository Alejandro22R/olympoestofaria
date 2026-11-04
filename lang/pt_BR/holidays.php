<?php

return [
    'model' => 'Feriado ou fechamento',
    'plural' => 'Feriados e fechamentos',
    'fields' => [
        'date' => 'Data',
        'name' => 'Motivo',
        'capacity' => 'Capacidade especial',
        'capacity_help' => 'Deixe vazio para fechar o dia. Um número > 0 abre o dia com essa capacidade.',
        'branch' => 'Unidade',
        'all_branches' => 'Todas as unidades',
    ],
];
