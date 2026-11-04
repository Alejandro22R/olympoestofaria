# Proyecto: Sistema para taller de tapicería automotriz (Olympo Estofaria)

Plan completo y fases: ver `PLAN.md`. Estado de cada fase: ver `docs/PROGRESS.md`.

## Stack
Laravel 13, PHP 8.3+, Filament 5 para el panel /admin, Livewire 4 + Alpine + Tailwind 4 para el portal, PostgreSQL, Redis + Horizon, Reverb + Echo, Pest 4, Pint, Larastan.

## Comandos
- Pruebas: `php artisan test` (usa PostgreSQL `olympo_test`, ver `phpunit.xml`)
- Estilo: `vendor/bin/pint` (verificar con `vendor/bin/pint --test`)
- Análisis estático: `vendor/bin/phpstan analyse --memory-limit=1G`
- Front: `npm run build` / `npm run dev`
- Datos demo: `php artisan migrate:fresh --seed` (usuarios demo en `database/seeders/UserSeeder.php`)

## Reglas
- Responde y comenta en español; el código y los nombres de clases en inglés.
- Lógica de negocio en Actions/Services en app/Domain/*, nunca en controladores ni recursos Filament.
- Estados con Enums. Autorización con Policies y spatie/laravel-permission.
- Todo texto visible con traducciones (lang/pt_BR y lang/es).
- Dinero en centavos (enteros) y moneda BRL.
- Archivos sensibles siempre en disco privado con URLs firmadas.
- Cada tarea termina con: tests Pest en verde, `pint` y `phpstan` sin errores.
- Antes de instalar paquetes, verifica compatibilidad de versiones.
- Notificaciones y validaciones según la sección 2.5: un solo sistema multicanal con Laravel Notifications, validación en tres capas (cliente, Form Requests y reglas de negocio en Actions), máquina de estados para transiciones, mensajes traducidos y una prueba por cada regla.
- Tiempo real: Reverb + Echo según la sección 2.4. Canales privados o de presencia con autorización por Policy, eventos por cola con afterCommit y sin datos sensibles en el socket.
- Landing y catálogo: sigue la sección 2.2 (movimiento). Anima solo transform y opacity, respeta prefers-reduced-motion y carga GSAP/Lenis solo donde se usen.
- Diseño: usa solo los tokens de color de la sección 2.1 de PLAN.md (nada de colores sueltos en las vistas), tema oscuro por defecto y botones primarios en brand-700 con texto blanco.
- Tipografía: Sora para títulos y cifras, Manrope para texto, tablas y formularios, autoalojadas y solo con los pesos usados. No uses otras fuentes.
- Check-in: no lleva firma del cliente. Guarda consentimiento LGPD y envía el acta en PDF por WhatsApp o correo, registrando quién, cuándo y por qué canal.
- Ajustes (sección 2.6): todo valor de negocio configurable (capacidad por día, anticipo, vigencias, plazos, umbrales de alerta, etapas, plantillas) se lee del servicio de ajustes con caché; nada fijo en el código. Solo el dueño edita, cada cambio se audita y los secretos se guardan cifrados y nunca se muestran.
- Trabaja por fases según PLAN.md; no avances a la siguiente sin cumplir la aceptación.
