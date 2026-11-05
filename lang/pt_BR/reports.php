<?php

return [
    'greeting' => 'Olá, :name!',
    'inspection' => [
        'subject' => 'Termo de recepção do veículo :plate',
        'body' => 'Recebemos o seu veículo :plate. Em anexo está o termo de recepção com o estado em que ele chegou à oficina.',
        'footer' => 'Se algo não estiver correto, responda este e-mail em até 24 horas.',
        'whatsapp' => 'Olá, :name! Recebemos o seu veículo :plate na Olympo. Este é o termo de recepção com o estado do veículo: :url',
    ],
    'delivery' => [
        'subject' => 'Termo de entrega do veículo :plate',
        'body' => 'O seu veículo :plate foi entregue. Em anexo está o termo de entrega com os serviços realizados e a garantia.',
        'footer' => 'Obrigado por confiar na Olympo!',
        'whatsapp' => 'Olá, :name! O seu veículo :plate foi entregue. Este é o termo de entrega com os serviços e a garantia: :url',
    ],
    'quote' => [
        'subject' => 'Orçamento para o seu veículo :plate',
        'body' => 'Segue em anexo o orçamento do seu veículo :plate. Você também pode aprová-lo e pagar o sinal pelo link que enviamos por WhatsApp.',
        'footer' => 'Qualquer dúvida, responda este e-mail.',
        'whatsapp' => 'Olá, :name! Seu orçamento da Olympo está pronto. Veja os detalhes, aprove e pague o sinal aqui: :url',
    ],
    'errors' => [
        'no_pdf' => 'O PDF ainda não foi gerado.',
    ],
];
