<?php

return [
    'model' => 'Solicitud',
    'plural' => 'Solicitudes',
    'status' => [
        'submitted' => 'Nueva',
        'contacted' => 'Contactada',
        'quoted' => 'Cotizada',
        'approved' => 'Aprobada',
        'lost' => 'Perdida',
    ],
    'fields' => [
        'number' => 'N.º',
        'customer' => 'Cliente',
        'phone' => 'WhatsApp',
        'vehicle' => 'Vehículo',
        'status' => 'Estado',
        'estimate' => 'Estimación',
        'submitted_at' => 'Enviada el',
        'pieces' => 'Piezas',
        'stitching' => 'Costura',
        'notes' => 'Observaciones del cliente',
        'lost_reason' => 'Motivo de la pérdida',
        'opt_in' => 'Acepta contacto',
    ],
    'actions' => [
        'contacted' => 'Marcar como contactada',
        'lost' => 'Marcar como perdida',
        'whatsapp' => 'Abrir WhatsApp del cliente',
    ],
    'whatsapp' => [
        'message' => "¡Hola! Armé un proyecto en el sitio de Olympo (:number).\nVehículo: :vehicle\n:pieces\nEstimación: :min – :max",
        'reply' => '¡Hola, :name! Te escribimos de Olympo. Recibimos tu proyecto :number para el :vehicle.',
    ],
    'errors' => [
        'material_required' => 'Elige el material para ":service".',
        'material_unavailable' => 'Este material no está disponible.',
        'invalid_transition' => 'No se puede pasar de ":from" a ":to".',
    ],
];
