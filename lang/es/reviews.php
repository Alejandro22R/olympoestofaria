<?php

return [
    'label' => 'Reseña',
    'plural' => 'Reseñas',
    'fields' => [
        'rating' => 'Nota',
        'comment' => 'Comentario',
        'customer' => 'Cliente',
        'work_order' => 'Orden',
        'is_published' => 'Publicada en el sitio',
        'consent_publish' => 'El cliente autorizó publicarla',
        'before' => 'Foto antes',
        'after' => 'Foto después',
        'created_at' => 'Recibida el',
    ],
    'portal' => [
        'title' => '¿Cómo fue el servicio?',
        'text' => 'Cuéntanos cómo quedó tu :vehicle. Toma menos de un minuto.',
        'stars' => '{1} :count estrella|[2,*] :count estrellas',
        'consent' => 'Autorizo a Olympo a publicar mi reseña en el sitio (solo mi primer nombre).',
        'submit' => 'Enviar reseña',
        'thanks' => '¡Gracias por tu reseña!',
    ],
    'landing_title' => 'Lo que dicen nuestros clientes',
    'errors' => [
        'not_delivered' => 'Solo se puede calificar después de la entrega.',
        'rating' => 'Elige una nota de 1 a 5.',
        'already_published' => 'Esta reseña ya fue publicada.',
        'no_consent' => 'El cliente no autorizó la publicación.',
    ],
    'actions' => [
        'publish' => 'Publicar',
        'unpublish' => 'Quitar del sitio',
    ],
];
