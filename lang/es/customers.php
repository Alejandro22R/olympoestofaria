<?php

return [
    'model' => 'Cliente',
    'plural' => 'Clientes',
    'fields' => [
        'name' => 'Nombre',
        'email' => 'Correo',
        'phone' => 'Teléfono (WhatsApp)',
        'phone_help' => 'Formato internacional, p. ej.: +5511987654321',
        'locale' => 'Idioma',
        'document_type' => 'Tipo de documento',
        'document_number' => 'Número de documento',
        'document_keep' => 'Déjalo en blanco para conservar el documento actual (:masked).',
        'document' => 'Documento',
        'lgpd_consent' => 'El cliente autoriza guardar su documento (LGPD)',
        'lgpd_consent_help' => 'Los datos se usan solo para identificarlo en el taller y se pueden eliminar a pedido.',
        'lgpd_consent_at' => 'Consentimiento LGPD el',
        'notes' => 'Observaciones',
        'vehicles_count' => 'Vehículos',
        'created_at' => 'Registrado el',
        'file' => 'Archivo del documento',
        'file_help' => 'JPG, PNG, WEBP o PDF, hasta 8 MB. Se guarda cifrado.',
    ],
    'sections' => [
        'contact' => 'Contacto',
        'document' => 'Documento de identidad',
        'document_file' => 'Archivo del documento',
    ],
    'document_types' => [
        'cpf' => 'CPF',
        'cnpj' => 'CNPJ',
        'other' => 'Otro',
    ],
    'actions' => [
        'upload_document' => 'Subir documento',
        'view_document' => 'Ver documento',
    ],
    'messages' => [
        'document_uploaded' => 'Documento guardado cifrado.',
        'no_document' => 'No se subió ningún archivo.',
        'document_on_file' => 'Archivo :mime subido el :date por :user.',
    ],
    'errors' => [
        'document_photo_disabled' => 'El taller no guarda fotos de documentos (Ajustes > Privacidad).',
        'consent_required' => 'Se necesita el consentimiento LGPD del cliente para guardar el documento.',
        'document_mime' => 'Tipo de archivo no permitido. Sube JPG, PNG, WEBP o PDF.',
        'document_size' => 'El archivo no puede pesar más de :max MB.',
    ],
    'cards' => [
        'vehicles' => '{0} Sin vehículos|{1} 1 vehículo|[2,*] :count vehículos',
        'no_email' => 'Sin correo',
        'since' => 'Cliente desde :date',
    ],
];
