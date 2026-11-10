<?php

// Textos de los tutoriales guiados del panel (pasos en App\Domain\Tours\TourCatalog).
return [
    'labels' => [
        'next' => 'Siguiente',
        'prev' => 'Atrás',
        'done' => 'Entendido',
        'progress' => '{{current}} de {{total}}',
        'start' => 'Ver tutorial de esta pantalla',
        'reset' => 'Volver a ver los tutoriales',
        'reset_help' => 'Los tutoriales de cada pantalla vuelven a abrirse solos en la próxima visita.',
        'reset_done' => '¡Listo! Los tutoriales volverán a aparecer.',
    ],
    'errors' => [
        'unknown' => 'Tutorial desconocido.',
    ],
    'list' => [
        'create' => ['title' => 'Crear uno nuevo', 'body' => 'Usa este botón para registrar un elemento nuevo.'],
        'search' => ['title' => 'Buscar', 'body' => 'Escribe un nombre, placa o teléfono para encontrarlo rápido.'],
        'filters' => ['title' => 'Filtrar y ordenar', 'body' => 'Aquí filtras la lista y eliges el orden.'],
        'card' => ['title' => 'Abrir un elemento', 'body' => 'Cada tarjeta es un registro. Haz clic para ver los detalles o editar.'],
    ],
    'dashboard' => [
        'welcome' => ['title' => '¡Bienvenido al panel de Olympo!', 'body' => 'Hagamos un recorrido rápido. Puedes volver a verlo cuando quieras con el botón “?” de arriba.'],
        'menu' => ['title' => 'Menú', 'body' => 'Todo está ordenado por el trabajo del día: Día a día (agenda, tablero y órdenes), Clientes y ventas, Taller y Sitio web.'],
        'actions' => ['title' => 'Accesos rápidos', 'body' => 'Atajos a lo que más haces: agendar, abrir el tablero, armar una cotización o registrar un cliente.'],
        'today' => ['title' => 'Recepciones de hoy', 'body' => 'Los autos que llegan hoy. Cuando llegue el cliente, toca “Recibir vehículo” para hacer el check-in.'],
        'pending' => ['title' => 'Pendientes', 'body' => 'Autos listos para entregar, órdenes atrasadas, solicitudes nuevas y cotizaciones esperando al cliente. Haz clic para ver la lista.'],
        'search' => ['title' => 'Búsqueda rápida', 'body' => 'Busca cualquier cliente, vehículo u orden desde aquí.'],
        'bell' => ['title' => 'Avisos', 'body' => 'Aquí llegan los avisos en tiempo real: nueva solicitud, pago recibido, stock bajo…'],
        'profile' => ['title' => 'Tu perfil', 'body' => 'Cambia tu idioma y contraseña, activa la verificación en dos pasos y elige qué avisos recibir por correo.'],
        'help' => ['title' => 'Ayuda en cada pantalla', 'body' => 'En cualquier pantalla, este botón muestra un tutorial de cómo usarla.'],
    ],
    'calendar' => [
        'intro' => ['title' => 'Agenda de recepciones', 'body' => 'Cada día muestra cuántos autos ya están agendados y cuántos caben todavía.'],
        'new' => ['title' => 'Agendar', 'body' => 'Marca el día y la hora en que el cliente trae el auto.'],
        'day' => ['title' => 'Los días', 'body' => 'Haz clic en una cita para verla o cambiarla. Los días llenos o cerrados aparecen marcados.'],
        'list' => ['title' => 'Ver en lista', 'body' => '¿Prefieres una lista? Aquí ves todas las citas con búsqueda y filtros.'],
    ],
    'appointments' => [
        'intro' => ['title' => 'Citas', 'body' => 'Todas las citas en tarjetas. Abre una para confirmar, reprogramar o hacer el check-in.'],
    ],
    'checkin' => [
        'intro' => ['title' => 'Recepción del vehículo (check-in)', 'body' => 'Aquí registras cómo llegó el auto. Al final se genera el acta en PDF y se envía al cliente.'],
        'state' => ['title' => 'Estado general', 'body' => 'Kilometraje, combustible y objetos que quedaron en el auto.'],
        'diagram' => ['title' => 'Daños', 'body' => 'Toca el dibujo del auto donde hay un daño, elige el tipo y, si quieres, toma una foto.'],
        'consent' => ['title' => 'Consentimiento', 'body' => 'Confirma que el cliente autorizó el uso de sus datos (LGPD). No hace falta firma.'],
        'finish' => ['title' => 'Terminar', 'body' => 'Al guardar se crea el acta y puedes enviarla por WhatsApp o correo.'],
    ],
    'board' => [
        'intro' => ['title' => 'Tablero de producción', 'body' => 'Cada columna es una etapa del trabajo y cada tarjeta es un auto en el taller.'],
        'column' => ['title' => 'Etapas', 'body' => 'El número indica cuántos autos hay en esta etapa.'],
        'card' => ['title' => 'Mover el auto', 'body' => 'Arrastra la tarjeta a la siguiente etapa. El cliente ve el avance en tiempo real.'],
        'move' => ['title' => 'En el celular', 'body' => 'En el celular, usa esta lista para elegir la nueva etapa.'],
    ],
    'inspections' => [
        'intro' => ['title' => 'Recepciones', 'body' => 'Todos los check-ins hechos, con los daños registrados y si el acta ya se envió.'],
    ],
    'work_orders' => [
        'intro' => ['title' => 'Órdenes de trabajo', 'body' => 'Cada auto en el taller tiene una orden con servicios, materiales, plazo y responsable.'],
        'create' => ['title' => 'Nueva orden', 'body' => 'Normalmente la orden nace del check-in o de la cotización aprobada, pero puedes crear una aquí.'],
    ],
    'work_order' => [
        'intro' => ['title' => 'La orden de trabajo', 'body' => 'Aquí están los servicios, los materiales y el historial del auto.'],
        'move' => ['title' => 'Cambiar de etapa', 'body' => 'Pasa el auto a la siguiente etapa del tablero.'],
        'progress' => ['title' => 'Registrar avance', 'body' => 'Agrega fotos y un comentario. Si es visible al cliente, lo verá en su página de seguimiento.'],
        'deliver' => ['title' => 'Entregar', 'body' => 'Cuando esté listo, registra la entrega: compara los daños, crea la garantía y el acta de entrega.'],
    ],
    'projects' => [
        'intro' => ['title' => 'Solicitudes del sitio', 'body' => 'Los proyectos que arman los clientes en el sitio llegan aquí. Responde rápido por WhatsApp y conviértelos en cotización.'],
    ],
    'quotes' => [
        'intro' => ['title' => 'Cotizaciones', 'body' => 'Arma la cotización, envíala al cliente y sigue si la aprobó y si pagó el anticipo.'],
        'create' => ['title' => 'Nueva cotización', 'body' => 'También puedes crear la cotización directamente desde una solicitud.'],
    ],
    'quote' => [
        'intro' => ['title' => 'Editar la cotización', 'body' => 'Los totales se calculan solos.'],
        'items' => ['title' => 'Ítems', 'body' => 'Agrega los servicios y materiales con cantidad y precio.'],
        'send' => ['title' => 'Enviar', 'body' => 'Envíala por WhatsApp o correo. El cliente la aprueba con el enlace, sin contraseña.'],
        'payment' => ['title' => 'Registrar pago', 'body' => 'Si el cliente paga en efectivo, Pix directo o tarjeta, regístralo aquí.'],
    ],
    'payments' => [
        'intro' => ['title' => 'Pagos', 'body' => 'Todos los anticipos y saldos: Pix por Mercado Pago y pagos registrados a mano.'],
        'create' => ['title' => 'Registrar pago', 'body' => 'Los pagos se registran desde la cotización o la orden.'],
    ],
    'customers' => [
        'intro' => ['title' => 'Clientes', 'body' => 'La ficha de cada cliente con sus vehículos, documentos y consentimientos.'],
        'create' => ['title' => 'Nuevo cliente', 'body' => 'Registra al cliente; después agrega los vehículos dentro de la ficha.'],
    ],
    'vehicles' => [
        'intro' => ['title' => 'Vehículos', 'body' => 'Todos los autos de los clientes, con placa, modelo y kilometraje.'],
    ],
    'inventory' => [
        'intro' => ['title' => 'Inventario', 'body' => 'Cuero, hilos, espumas… El stock baja solo cuando una orden entra en producción.'],
        'card' => ['title' => 'Reponer material', 'body' => 'Los ítems con la etiqueta “Reponer” están en el mínimo. Abre el ítem para registrar una entrada.'],
    ],
    'catalog' => [
        'intro' => ['title' => 'Catálogo', 'body' => 'Los servicios y materiales que aparecen en el sitio y en las cotizaciones, con precios y colores.'],
    ],
    'gallery' => [
        'intro' => ['title' => 'Galería del sitio', 'body' => 'Fotos y videos de los trabajos que aparecen en el sitio. Elige dónde aparece cada uno.'],
    ],
    'reviews' => [
        'intro' => ['title' => 'Reseñas', 'body' => 'Lo que opinaron los clientes. Publica en el sitio solo las que ellos autorizaron.'],
    ],
    'reports' => [
        'intro' => ['title' => 'Reportes', 'body' => 'Ingresos, servicios más vendidos, marcas frecuentes, tiempo por trabajo y rentabilidad.'],
        'period' => ['title' => 'Periodo', 'body' => 'Elige el periodo que quieres ver.'],
    ],
    'settings' => [
        'intro' => ['title' => 'Ajustes', 'body' => 'Aquí el dueño configura cómo funciona el sistema, sin necesitar a un programador.'],
        'sections' => ['title' => 'Secciones', 'body' => 'Elige qué configurar: taller, ventas, comunicación, equipo y sistema.'],
        'save' => ['title' => 'Guardar', 'body' => 'Después de cambiar algo, haz clic en guardar. Cada cambio queda en la auditoría.'],
    ],
];
