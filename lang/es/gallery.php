<?php

return [
    'model' => 'Medio',
    'plural' => 'Biblioteca de medios',
    'placements' => [
        'hero' => 'Portada (video)',
        'gallery' => 'Galería',
        'before_after' => 'Antes y después',
        'services' => 'Tarjeta de servicio',
        'reels' => 'Reels',
    ],
    'sections' => ['media' => 'Archivos'],
    'fields' => [
        'published' => 'Publicado',
        'material' => 'Material',
        'placements' => 'Dónde se muestra',
        'image' => 'Foto (después)',
        'before' => 'Foto del antes',
        'before_help' => 'Usa el mismo encuadre que la foto del después para el control deslizante.',
        'video' => 'Video',
        'video_help' => 'Sin audio en el sitio. Se convierte automáticamente a MP4 y WebM, con póster.',
        'video_status' => 'Video',
    ],
    'video_status' => [
        'pending' => 'En cola',
        'processing' => 'Convirtiendo',
        'ready' => 'Listo',
        'failed' => 'Falló',
    ],
    'messages' => ['video_queued' => 'Video en cola de conversión. Te avisaremos cuando termine.'],
];
