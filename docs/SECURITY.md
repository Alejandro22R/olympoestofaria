# Seguridad y cumplimiento (Fase 7)

Resumen de la auditoría de seguridad y de cómo se cubre cada punto de PLAN.md §7.

| Punto | Cómo se cubre | Prueba |
|---|---|---|
| Datos y documentos sensibles | Número de documento cifrado en la base (`encrypted`) con hash para búsqueda; archivo del documento cifrado en disco privado; fotos y actas en disco privado con URLs firmadas y de corta duración | `CustomerDocumentTest`, `InspectionReportTest` |
| Consentimiento LGPD | Registro de consentimientos (`consent_events`) desde el sitio, el check-in, el panel y el enlace de baja, con hash de IP (no la IP) | `PrivacyTest` |
| Exportar y eliminar | Exportar (JSON) y anonimizar desde la ficha del cliente (solo el dueño, con confirmación escrita); retención automática configurable | `PrivacyTest` |
| Autorización | Policies + permisos por rol en cada recurso; los clientes no entran a ningún recurso del panel; un cliente nunca ve datos de otro | `SecurityAndAuditTest`, `CustomerPortalTest`, pruebas de cada fase |
| Webhooks | Firma HMAC verificada, tolerancia de tiempo contra repetición, idempotencia y consulta a la API | `MercadoPagoWebhookTest` |
| Límites de tasa | Portal 120/min por IP; constructor 5 envíos/hora; enlaces firmados 30/min; webhooks 240/min; login con intentos configurables | `SecurityAndAuditTest`, `ProjectBuilderTest`, `TeamSettingsTest` |
| Spam | Honeypot + Turnstile opcional | `ProjectBuilderTest` |
| Auditoría | Activity log de estados, precios, documentos, ajustes, permisos, privacidad y sistema; visor con filtros, exportación CSV y retención | `SecurityAndAuditTest` |
| Secretos | Infraestructura en `.env`; credenciales de servicios cifradas en ajustes, de solo escritura y enmascaradas en la auditoría; exportar configuración nunca incluye secretos | `PaymentSettingsTest`, `CommunicationSettingsTest`, `BackupAndHealthTest` |
| Sesión y acceso | 2FA (obligatorio opcional), cierre por inactividad, cookies seguras y cifradas en producción, Horizon solo para el dueño | `TeamSettingsTest`, `SecurityAndAuditTest` |
| Cabeceras HTTP | `Content-Security-Policy` (orígenes propios + Turnstile, Reverb y Sentry), `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS por https | `SecurityAndAuditTest` |
| Terceros | Avatares generados localmente (Filament usaba ui-avatars.com y enviaba el nombre); fuentes autoalojadas | `SecurityAndAuditTest` |
| Dependencias | `composer audit` y `npm audit` sin vulnerabilidades conocidas (10/11/2026) | CI |

**Notas:**
- La CSP permite scripts en línea y `unsafe-eval` porque Livewire, Alpine y Filament los necesitan. La protección está en la lista cerrada de orígenes.
- Los textos legales son una base. Deben revisarse con un abogado antes de publicar.
- ClamAV (antivirus de subidas) sigue como opcional del plan. Hoy las subidas se validan por tipo MIME real y tamaño, y los documentos se cifran.
