<?php

return [
    'anonymous_name' => 'Cliente anonimizado #:id',
    'fields' => [
        'anonymized_at' => 'Anonimizado em',
    ],
    'actions' => [
        'group' => 'Privacidade',
        'export' => 'Exportar dados (LGPD)',
        'anonymize' => 'Anonimizar cliente',
        'anonymize_heading' => 'Anonimizar :name?',
        'anonymize_help' => 'Apaga nome, contatos, documento, fotos e termos do cliente e troca as placas por um código. Valores, serviços e datas são mantidos por obrigação fiscal. Não pode ser desfeito.',
        'anonymize_confirm' => 'Digite ANONIMIZAR para confirmar',
        'anonymize_word' => 'ANONIMIZAR',
        'anonymize_mismatch' => 'Digite exatamente ANONIMIZAR.',
    ],
    'messages' => [
        'anonymized' => 'Cliente anonimizado.',
    ],
    'errors' => [
        'already_anonymized' => 'Este cliente já foi anonimizado.',
        'open_items' => 'O cliente tem ordem, agendamento ou pagamento em aberto. Conclua ou cancele antes de anonimizar.',
    ],
    'consents' => [
        'label' => 'Consentimento',
        'title' => 'Registro de consentimentos',
        'empty' => 'Nenhum consentimento registrado.',
        'at' => 'Data',
        'type' => 'Tipo',
        'granted' => 'Concedido',
        'source' => 'Origem',
        'recorded_by' => 'Registrado por',
        'yes' => 'Concedido',
        'no' => 'Retirado / negado',
        'types' => [
            'privacy' => 'Uso dos dados (LGPD)',
            'contact' => 'Contato e avisos',
            'document' => 'Foto do documento',
        ],
        'sources' => [
            'portal' => 'Site (construtor)',
            'checkin' => 'Recepção (check-in)',
            'panel' => 'Painel',
            'email_link' => 'Link do e-mail',
        ],
    ],
    'legal' => [
        'privacy_title' => 'Política de Privacidade',
        'terms_title' => 'Termos de Uso',
        'updated_at' => 'Atualizado em :date',
    ],
    'settings' => [
        'title' => 'Privacidade e LGPD',
        'data' => 'Dados e retenção',
        'months' => 'meses',
        'store_document_photo' => 'Guardar a foto do documento do cliente',
        'store_document_photo_help' => 'Desativado, só se guarda o número (criptografado). Fotos já guardadas não são apagadas.',
        'inactive_customer_months' => 'Anonimizar clientes sem atividade após',
        'inactive_customer_months_help' => '0 = nunca. Clientes com algo em aberto não são anonimizados.',
        'lost_lead_months' => 'Apagar solicitações perdidas após',
        'contact_email' => 'Contato do encarregado de dados',
        'contact_email_help' => 'Aparece na Política de Privacidade. Vazio = e-mail da oficina.',
        'legal_updated_at' => 'Data da versão dos textos legais',
        'legal' => 'Textos legais',
        'legal_help' => 'Markdown. {oficina} e {contato} são substituídos automaticamente. Revise com seu advogado antes de publicar.',
    ],
];
