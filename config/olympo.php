<?php

/*
 * Configuración de infraestructura de la aplicación. Los valores de NEGOCIO
 * (capacidad, anticipo, plazos…) no van aquí: viven en Ajustes (spatie/laravel-settings).
 */
return [
    // Disco para documentos y fotos sensibles (local "private" en dev; S3/R2 privado en producción).
    'private_disk' => env('PRIVATE_DISK', 'private'),
];
