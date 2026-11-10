<?php

return [
    'model' => 'Cliente',
    'plural' => 'Clientes',
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'phone' => 'Telefone (WhatsApp)',
        'phone_help' => 'Formato internacional, ex.: +5511987654321',
        'locale' => 'Idioma',
        'document_type' => 'Tipo de documento',
        'document_number' => 'Número do documento',
        'document_keep' => 'Deixe em branco para manter o documento atual (:masked).',
        'document' => 'Documento',
        'lgpd_consent' => 'O cliente autoriza o armazenamento do documento (LGPD)',
        'lgpd_consent_help' => 'Os dados são usados apenas para identificação na oficina e podem ser excluídos a pedido.',
        'lgpd_consent_at' => 'Consentimento LGPD em',
        'notes' => 'Observações',
        'vehicles_count' => 'Veículos',
        'created_at' => 'Cadastrado em',
        'file' => 'Arquivo do documento',
        'file_help' => 'JPG, PNG, WEBP ou PDF, até 8 MB. Guardado criptografado.',
    ],
    'sections' => [
        'contact' => 'Contato',
        'document' => 'Documento de identidade',
        'document_file' => 'Arquivo do documento',
    ],
    'document_types' => [
        'cpf' => 'CPF',
        'cnpj' => 'CNPJ',
        'other' => 'Outro',
    ],
    'actions' => [
        'upload_document' => 'Enviar documento',
        'view_document' => 'Ver documento',
    ],
    'messages' => [
        'document_uploaded' => 'Documento salvo com criptografia.',
        'no_document' => 'Nenhum arquivo enviado.',
        'document_on_file' => 'Arquivo :mime enviado em :date por :user.',
    ],
    'errors' => [
        'document_photo_disabled' => 'A oficina não guarda fotos de documentos (Ajustes > Privacidade).',
        'consent_required' => 'É necessário o consentimento LGPD do cliente para guardar o documento.',
        'document_mime' => 'Tipo de arquivo não permitido. Envie JPG, PNG, WEBP ou PDF.',
        'document_size' => 'O arquivo não pode ter mais de :max MB.',
    ],
    'cards' => [
        'vehicles' => '{0} Sem veículos|{1} 1 veículo|[2,*] :count veículos',
        'no_email' => 'Sem e-mail',
        'since' => 'Cliente desde :date',
    ],
];
