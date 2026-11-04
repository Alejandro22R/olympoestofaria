<?php

return [
    'title' => 'Entrega de :number · :plate',
    'outcomes' => ['repaired' => 'Reparado', 'unchanged' => 'Sin cambios'],
    'fields' => ['notes' => 'Observaciones de la entrega'],
    'sections' => [
        'comparison' => 'Inspección inicial × estado final',
        'comparison_help' => 'Indica cómo está cada daño registrado en el check-in.',
        'final_state' => 'Estado en la entrega',
    ],
    'actions' => [
        'deliver' => 'Registrar entrega',
        'confirm' => 'Confirmar entrega y generar acta',
        'download' => 'Ver acta de entrega (PDF)',
        'send' => 'Enviar acta de entrega',
    ],
    'messages' => ['delivered' => 'Entrega registrada. Garantía creada y acta generada.'],
    'errors' => [
        'not_ready' => 'Solo se puede entregar una orden "Lista" (estado actual: :status).',
        'mark_outcome_required' => 'Indica el estado final de este daño.',
    ],
    'report' => [
        'title' => 'Acta de entrega del vehículo',
        'mileage_in' => 'Km en la recepción',
        'mileage_out' => 'Km en la entrega',
        'delivered_by' => 'Entregado por',
        'services' => 'Servicios realizados',
        'comparison' => 'Daños: recepción × entrega',
        'at_check_in' => 'En la recepción',
        'at_delivery' => 'En la entrega',
        'no_marks' => 'No se registraron daños en la recepción.',
        'warranty' => 'Garantía',
        'warranty_period' => 'del :from al :to.',
        'no_signature' => 'Documento sin firma. El envío de esta acta al cliente queda registrado en el sistema (fecha, canal y responsable).',
    ],
    'settings' => [
        'title' => 'Garantías',
        'term' => 'Plazo de garantía',
        'terms' => 'Plazos por servicio',
        'months' => 'Plazo',
        'months_suffix' => 'meses',
        'default_months' => 'Plazo por defecto (meses)',
        'default_months_help' => 'Se usa cuando ningún servicio de la orden tiene plazo propio. La garantía empieza en la fecha de entrega.',
        'review_reminder_days' => 'Recordatorio de revisión antes del vencimiento',
        'conditions' => 'Condiciones de la garantía',
    ],
];
