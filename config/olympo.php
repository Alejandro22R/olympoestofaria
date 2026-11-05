<?php

/*
 * Configuración de infraestructura de la aplicación. Los valores de NEGOCIO
 * (capacidad, anticipo, plazos…) no van aquí: viven en Ajustes (spatie/laravel-settings).
 */
return [
    // Disco para documentos y fotos sensibles (local "private" en dev; S3/R2 privado en producción).
    'private_disk' => env('PRIVATE_DISK', 'private'),

    // Destinos de copia disponibles; el dueño elige cuáles usar en Ajustes > Respaldos.
    // El remoto solo aparece si su bucket está configurado en el .env.
    'backup_disks' => array_values(array_filter([
        'backups',
        env('BACKUP_S3_BUCKET') ? 'backups_remote' : null,
    ])),

    // Base de datos vacía donde se prueba restaurar la última copia (opcional; sin ella solo se verifica el archivo).
    'restore_test_database' => env('BACKUP_RESTORE_TEST_DATABASE'),

    'security' => [
        // Content-Security-Policy: activa por defecto fuera de local (Vite en desarrollo usa otro origen).
        'csp' => (bool) env('SECURITY_CSP', env('APP_ENV') !== 'local'),
        // Forzar enlaces https detrás de un proxy/CDN con TLS.
        'force_https' => (bool) env('FORCE_HTTPS', env('APP_ENV') === 'production'),
        'hsts' => (bool) env('SECURITY_HSTS', env('APP_ENV') === 'production'),
        // Host del WebSocket tal como lo ve el navegador (para la CSP).
        'socket_host' => env('VITE_REVERB_HOST', env('REVERB_HOST')),
        'socket_port' => env('VITE_REVERB_PORT', env('REVERB_PORT')),
        // Proxies de confianza (IP o "*" detrás de Cloudflare/balanceador).
        'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1'),
        // Peticiones por minuto e IP en el portal público (subirlo solo para pruebas de carga).
        'portal_rate_limit' => (int) env('PORTAL_RATE_LIMIT', 120),
    ],
];
