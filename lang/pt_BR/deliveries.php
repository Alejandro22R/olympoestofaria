<?php

return [
    'title' => 'Entrega de :number · :plate',
    'outcomes' => ['repaired' => 'Reparado', 'unchanged' => 'Sem alteração'],
    'fields' => ['notes' => 'Observações da entrega'],
    'sections' => [
        'comparison' => 'Vistoria inicial × estado final',
        'comparison_help' => 'Indique como está cada avaria registrada no check-in.',
        'final_state' => 'Estado na entrega',
    ],
    'actions' => [
        'deliver' => 'Registrar entrega',
        'confirm' => 'Confirmar entrega e gerar termo',
        'download' => 'Ver termo de entrega (PDF)',
        'send' => 'Enviar termo de entrega',
    ],
    'messages' => ['delivered' => 'Entrega registrada. Garantia criada e termo gerado.'],
    'errors' => [
        'not_ready' => 'Só é possível entregar uma ordem "Pronta" (status atual: :status).',
        'mark_outcome_required' => 'Indique o estado final desta avaria.',
    ],
    'report' => [
        'title' => 'Termo de entrega do veículo',
        'mileage_in' => 'Km na recepção',
        'mileage_out' => 'Km na entrega',
        'delivered_by' => 'Entregue por',
        'services' => 'Serviços realizados',
        'comparison' => 'Avarias: recepção × entrega',
        'at_check_in' => 'Na recepção',
        'at_delivery' => 'Na entrega',
        'no_marks' => 'Nenhuma avaria registrada na recepção.',
        'warranty' => 'Garantia',
        'warranty_period' => 'de :from a :to.',
        'no_signature' => 'Documento sem assinatura. O envio deste termo ao cliente fica registrado no sistema (data, canal e responsável).',
    ],
    'settings' => [
        'title' => 'Garantias',
        'term' => 'Prazo de garantia',
        'terms' => 'Prazos por serviço',
        'months' => 'Prazo',
        'months_suffix' => 'meses',
        'default_months' => 'Prazo padrão (meses)',
        'default_months_help' => 'Usado quando nenhum serviço da ordem tem prazo próprio. A garantia começa na data de entrega.',
        'review_reminder_days' => 'Lembrete de revisão antes do vencimento',
        'conditions' => 'Condições da garantia',
    ],
];
