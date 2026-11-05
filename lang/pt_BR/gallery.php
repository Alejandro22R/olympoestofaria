<?php

return [
    'model' => 'Mídia',
    'plural' => 'Biblioteca de mídia',
    'placements' => [
        'hero' => 'Topo (vídeo)',
        'gallery' => 'Galeria',
        'before_after' => 'Antes e depois',
        'services' => 'Card de serviço',
        'reels' => 'Reels',
    ],
    'sections' => ['media' => 'Arquivos'],
    'fields' => [
        'published' => 'Publicado',
        'material' => 'Material',
        'placements' => 'Onde aparece',
        'image' => 'Foto (depois)',
        'before' => 'Foto do antes',
        'before_help' => 'Use a mesma moldura da foto do depois para o controle deslizante.',
        'video' => 'Vídeo',
        'video_help' => 'Sem áudio no site. Convertido automaticamente para MP4 e WebM, com pôster.',
        'video_status' => 'Vídeo',
    ],
    'video_status' => [
        'pending' => 'Na fila',
        'processing' => 'Convertendo',
        'ready' => 'Pronto',
        'failed' => 'Falhou',
    ],
    'messages' => ['video_queued' => 'Vídeo na fila de conversão. Avisaremos quando terminar.'],
];
