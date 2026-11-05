<?php

return [
    'model' => 'Solicitação',
    'plural' => 'Solicitações',
    'status' => [
        'submitted' => 'Nova',
        'contacted' => 'Contatada',
        'quoted' => 'Orçada',
        'approved' => 'Aprovada',
        'lost' => 'Perdida',
    ],
    'fields' => [
        'number' => 'Nº',
        'customer' => 'Cliente',
        'phone' => 'WhatsApp',
        'vehicle' => 'Veículo',
        'status' => 'Status',
        'estimate' => 'Estimativa',
        'submitted_at' => 'Enviada em',
        'pieces' => 'Peças',
        'stitching' => 'Costura',
        'notes' => 'Observações do cliente',
        'lost_reason' => 'Motivo da perda',
        'opt_in' => 'Aceita contato',
    ],
    'actions' => [
        'contacted' => 'Marcar como contatada',
        'lost' => 'Marcar como perdida',
        'whatsapp' => 'Abrir WhatsApp do cliente',
    ],
    'whatsapp' => [
        'message' => "Olá! Montei um projeto no site da Olympo (:number).\nVeículo: :vehicle\n:pieces\nEstimativa: :min – :max",
        'reply' => 'Olá, :name! Aqui é da Olympo. Recebemos o seu projeto :number para o :vehicle.',
    ],
    'errors' => [
        'material_required' => 'Escolha o material para ":service".',
        'material_unavailable' => 'Este material não está disponível.',
        'invalid_transition' => 'Não é possível passar de ":from" para ":to".',
    ],
];
