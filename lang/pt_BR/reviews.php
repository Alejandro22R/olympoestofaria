<?php

return [
    'label' => 'Avaliação',
    'plural' => 'Avaliações',
    'fields' => [
        'rating' => 'Nota',
        'comment' => 'Comentário',
        'customer' => 'Cliente',
        'work_order' => 'Ordem',
        'is_published' => 'Publicada no site',
        'consent_publish' => 'Cliente autorizou publicar',
        'before' => 'Foto antes',
        'after' => 'Foto depois',
        'created_at' => 'Recebida em',
    ],
    'portal' => [
        'title' => 'Como foi o serviço?',
        'text' => 'Conte como ficou o seu :vehicle. Leva menos de um minuto.',
        'stars' => '{1} :count estrela|[2,*] :count estrelas',
        'consent' => 'Autorizo a Olympo a publicar minha avaliação no site (só meu primeiro nome).',
        'submit' => 'Enviar avaliação',
        'thanks' => 'Obrigado pela avaliação!',
    ],
    'landing_title' => 'O que dizem nossos clientes',
    'errors' => [
        'not_delivered' => 'Só é possível avaliar depois da entrega.',
        'rating' => 'Escolha uma nota de 1 a 5.',
        'already_published' => 'Esta avaliação já foi publicada.',
        'no_consent' => 'O cliente não autorizou a publicação.',
    ],
    'actions' => [
        'publish' => 'Publicar',
        'unpublish' => 'Retirar do site',
    ],
];
