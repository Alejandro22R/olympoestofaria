<?php

return [
    'anonymous_name' => 'Cliente anonimizado #:id',
    'fields' => [
        'anonymized_at' => 'Anonimizado el',
    ],
    'actions' => [
        'group' => 'Privacidad',
        'export' => 'Exportar datos (LGPD)',
        'anonymize' => 'Anonimizar cliente',
        'anonymize_heading' => '¿Anonimizar a :name?',
        'anonymize_help' => 'Borra nombre, contactos, documento, fotos y actas del cliente y cambia las placas por un código. Se conservan montos, servicios y fechas por obligación fiscal. No se puede deshacer.',
        'anonymize_confirm' => 'Escribe ANONIMIZAR para confirmar',
        'anonymize_word' => 'ANONIMIZAR',
        'anonymize_mismatch' => 'Escribe exactamente ANONIMIZAR.',
    ],
    'messages' => [
        'anonymized' => 'Cliente anonimizado.',
    ],
    'errors' => [
        'already_anonymized' => 'Este cliente ya fue anonimizado.',
        'open_items' => 'El cliente tiene una orden, cita o pago abierto. Termínalo o cancélalo antes de anonimizar.',
    ],
    'consents' => [
        'label' => 'Consentimiento',
        'title' => 'Registro de consentimientos',
        'empty' => 'No hay consentimientos registrados.',
        'at' => 'Fecha',
        'type' => 'Tipo',
        'granted' => 'Otorgado',
        'source' => 'Origen',
        'recorded_by' => 'Registrado por',
        'yes' => 'Otorgado',
        'no' => 'Retirado / negado',
        'types' => [
            'privacy' => 'Uso de datos (LGPD)',
            'contact' => 'Contacto y avisos',
            'document' => 'Foto del documento',
        ],
        'sources' => [
            'portal' => 'Sitio (constructor)',
            'checkin' => 'Recepción (check-in)',
            'panel' => 'Panel',
            'email_link' => 'Enlace del correo',
        ],
    ],
    'legal' => [
        'privacy_title' => 'Política de Privacidad',
        'terms_title' => 'Términos de Uso',
        'updated_at' => 'Actualizado el :date',
    ],
    'settings' => [
        'title' => 'Privacidad y LGPD',
        'data' => 'Datos y retención',
        'months' => 'meses',
        'store_document_photo' => 'Guardar la foto del documento del cliente',
        'store_document_photo_help' => 'Desactivado, solo se guarda el número (cifrado). Las fotos ya guardadas no se borran.',
        'inactive_customer_months' => 'Anonimizar clientes sin actividad tras',
        'inactive_customer_months_help' => '0 = nunca. Los clientes con algo abierto no se anonimizan.',
        'lost_lead_months' => 'Borrar solicitudes perdidas tras',
        'contact_email' => 'Contacto del encargado de datos',
        'contact_email_help' => 'Aparece en la Política de Privacidad. Vacío = correo del taller.',
        'legal_updated_at' => 'Fecha de versión de los textos legales',
        'legal' => 'Textos legales',
        'legal_help' => 'Markdown. {oficina} y {contato} se reemplazan solos. Revísalos con tu abogado antes de publicar.',
    ],
];
