# Atención al cliente

## Alcance actual

Ruta autenticada `/atencion-cliente`, accesible desde Operación. La bandeja usa `/api/support` y requiere la migración `2026_11_15_000001_create_support_cases.php`. Desde `back`, aplícala con `php artisan migrate --path=database/migrations/2026_11_15_000001_create_support_cases.php`.

- **Nuevo caso → Pedido online** abre el caso por número de pedido y toma el origen y la sucursal de la orden guardada. Un pedido tiene como máximo un caso. La sucursal de atención puede cambiar sin editar la orden.
- El administrador (usuario 1) ve todas las sucursales y registra consultas directas. Los casos sin sucursal aparecen en **Sin asignar** hasta que el administrador los asigne desde **Detalles de atención**. Los operadores consultan y modifican casos de su propia sucursal.
- Las notas y respuestas quedan en el historial. El modo bot es una preferencia persistida.
- `demo.js` contiene datos ficticios. Los borradores y los cambios de demostración viven en memoria mientras está abierta la pantalla.

## Webhook de prueba

En `back`, publica la ruta `/api/webhooks/whatsapp` en un servidor HTTPS accesible para Meta y aplica la migración `php artisan migrate --path=database/migrations/2026_11_15_000002_create_whatsapp_test_events_table.php`.

Configura en el entorno privado del backend estas tres variables:

- `WHATSAPP_VERIFY_TOKEN`: genera una clave aleatoria y usa ese mismo valor en **Identificador de verificación** de Meta.
- `WHATSAPP_APP_SECRET`: secreto de la aplicación que se muestra en su configuración básica de Meta. Laravel lo usa para verificar la firma de cada evento.
- `WHATSAPP_TEST_PHONE_NUMBER_ID`: **Phone Number ID** de **Paso 1. Probar**. Usa el identificador numérico, distinto del número visible y del identificador de la cuenta de mensajes.

Si se cachea la configuración de Laravel, regénérala después de definir las variables. En Meta, abre **Paso 2 → Configurar Webhooks**, pega la URL HTTPS y el identificador de verificación, pulsa **Verificar y guardar** y suscribe el campo `messages`.

El webhook valida la firma sobre el cuerpo original y registra en `whatsapp_test_events` el tipo, la hora, el identificador del número de prueba y una huella para deduplicar. Una prueba del panel con un identificador de ejemplo distinto puede recibir HTTP 200 sin dejar un registro.

Para mostrar mensajes de texto en la bandeja, aplica además `php artisan migrate --path=database/migrations/2026_11_16_000001_add_whatsapp_thread_key_to_support_cases.php` desde `back`. Luego envía un mensaje nuevo al número de prueba. Los mensajes se agrupan por remitente. El primer mensaje queda en **Sin asignar** para el administrador y, después de la asignación, el historial es visible para esa sucursal. Un mensaje nuevo reabre la conversación resuelta en modo manual. Los eventos antiguos solo contienen los datos de diagnóstico.

## Responder desde el número de prueba

Aplica `php artisan migrate --path=database/migrations/2026_11_16_000002_add_whatsapp_replies_to_support.php` desde `back`. Define `WHATSAPP_ACCESS_TOKEN` en el entorno privado de Laravel con un token de la misma app de Meta con permiso `whatsapp_business_messaging`. Si tu servidor usa la caché de configuración, regénérala. El identificador del número de prueba es el mismo que usa el webhook.

Desde el celular autorizado, manda un texto nuevo al número de prueba para guardar el destinatario y abrir la ventana de 24 horas. Asigna el caso a una sucursal y, en atención manual, pulsa **Enviar por WhatsApp**. La bandeja muestra si Meta aceptó el envío. Para confirmar la entrega de la prueba, abre el WhatsApp del celular. Las notas internas se guardan en Santidad.

## Actualización en vivo

Define un valor aleatorio de al menos 32 bytes para `SUPPORT_SOCKET_SECRET` en el entorno privado de Laravel y el mismo valor en el entorno del proceso Node de sockets. Conserva la URL existente `SOCKET_SERVER_URL` en Laravel. Reinicia Node y actualiza la caché de configuración de Laravel si tu despliegue la usa. El frontend usa la conexión Socket.IO de la interfaz. Esta parte no requiere migraciones.

El administrador recibe los avisos de todas las conversaciones. Cada sucursal recibe los avisos de sus casos. La bandeja carga los cambios cuando recibe un aviso y al reconectar. El botón **Actualizar** permite sincronizar manualmente.

## Integración posterior

- Un número general de WhatsApp para todas las sucursales. El teléfono del cliente identifica el hilo externo, y el caso interno identifica sucursal/pedido. Evitar distribuir automáticamente todo el historial externo entre sucursales distintas.
- Resolver la referencia recibida usando la orden y el caso correspondiente. Aplicar los mismos permisos a envíos y eventos nuevos. Las notas internas y las respuestas guardadas antes de conectar WhatsApp quedan en el historial interno.
- El cambio a manual debe ser atómico y detener las respuestas automáticas pendientes del caso. Revalidar el modo antes de enviar desde un worker. Resolver pausa el bot; reabrir inicia en manual. Registrar auditoría y evitar que dos operadores se apropien del caso a la vez.
- Al ampliar la integración, sincronizar los estados de entrega y lectura de Meta y definir el flujo de plantillas fuera de la ventana de atención.
- Reutilizar la validación de firmas y deduplicación del webhook de prueba al conectar el número comercial. Implementar la recuperación del historial tras reconexión y los eventos autorizados por sucursal.
- Confirmar la disponibilidad de coexistencia con WhatsApp Business para el número antes de implementar la sincronización de respuestas del administrador desde su teléfono.
- Añadir adjuntos y consulta real de stock al conectar sus servicios.

## Validación local

En `back`, `php artisan test --filter=SupportCaseTest` verifica la separación de sucursales, el vínculo con pedidos y las reasignaciones en SQLite en memoria.

`node tests/customer-support.smoke.cjs` utiliza Playwright y las dependencias Vite/Quasar existentes. Inicia un servidor temporal, monta la página con una sesión ficticia y simula las respuestas de Laravel. Comprueba que se pueda abrir un caso por pedido, guardar una nota y recuperarla al volver a entrar. Cierra servidor y navegador al terminar.

Playwright debe estar disponible en el entorno (por ejemplo vía `NODE_PATH`). Puede indicarse un Chromium instalado mediante `SUPPORT_CHROMIUM_PATH`. Para capturas de escritorio y móvil, indicar una carpeta existente en `SUPPORT_SCREENSHOT_DIR`. No se agregaron dependencias al proyecto.
