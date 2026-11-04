<?php

return [
    'greeting' => '¡Hola, :name!',
    'inspection' => [
        'subject' => 'Acta de recepción del vehículo :plate',
        'body' => 'Recibimos tu vehículo :plate. Adjuntamos el acta de recepción con el estado en que llegó al taller.',
        'footer' => 'Si algo no es correcto, responde este correo dentro de las 24 horas.',
        'whatsapp' => '¡Hola, :name! Recibimos tu vehículo :plate en Olympo. Esta es el acta de recepción con el estado del vehículo: :url',
    ],
    'delivery' => [
        'subject' => 'Acta de entrega del vehículo :plate',
        'body' => 'Tu vehículo :plate fue entregado. Adjuntamos el acta de entrega con los servicios realizados y la garantía.',
        'footer' => '¡Gracias por confiar en Olympo!',
        'whatsapp' => '¡Hola, :name! Tu vehículo :plate fue entregado. Esta es el acta de entrega con los servicios y la garantía: :url',
    ],
    'errors' => [
        'no_pdf' => 'El PDF todavía no se generó.',
    ],
];
