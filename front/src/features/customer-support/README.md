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

## Asistente de WhatsApp

El asistente reutiliza el número configurado, el webhook, las conversaciones y los avisos en vivo. `WHATSAPP_TEST_PHONE_NUMBER_ID` conserva su nombre por compatibilidad y acepta el **Phone Number ID** activo. El token queda en `WHATSAPP_ACCESS_TOKEN`. Para coexistencia, suscribe `smb_message_echoes` junto a `messages` para que una respuesta desde WhatsApp Business aparezca en la bandeja y pause el bot. [Guía de coexistencia de Meta](https://developers.facebook.com/documentation/business-messaging/whatsapp/embedded-signup/onboarding-business-app-users)

En un entorno de prueba, desde `back`, revisa `php artisan migrate:status` y aplica las migraciones pendientes con `php artisan migrate`. Las dos migraciones nuevas agregan memoria, estados, horarios, clasificación, límites y una cola dedicada. Configura el entorno privado de Laravel.

| Variable | Uso |
| --- | --- |
| `WHATSAPP_BOT_ENABLED` | `true` permite respuestas automáticas. El valor por defecto es `false`. Los casos manuales requieren **Activar bot** desde la bandeja. |
| `WHATSAPP_BOT_QUEUE` | `sync` ejecuta solo reglas locales después de responder al webhook. Usa `database` con un proceso permanente para trabajo en segundo plano. |
| `WHATSAPP_CONTEXT_HOURS` | 48 horas por defecto. Admite de 1 a 168 horas. |
| `WHATSAPP_CATALOG_PRICE_CHANNEL` | Vacía omite precios. `physical` permite cotizar el precio vigente de mostrador en la sucursal identificada. |
| `AI_ENABLED`, `AI_ADULT_ONLY_CHANNEL` | Ambas quedan en `false` por defecto. Mantén Gemini apagado en un número público de farmacia. Solo considera activarlo en un canal que cumpla los términos de Google para acceso de adultos y usos de IA. |
| `AI_PROVIDER`, `AI_MODEL`, `AI_API_KEY` | `gemini`, el ID de un modelo que admita salida estructurada y una clave privada del backend. |
| `AI_TIMEOUT`, `AI_MAX_INPUT_TOKENS`, `AI_MAX_OUTPUT_TOKENS` | 4 segundos, 4096 y 256 por defecto. El límite de entrada aplica en bytes al cuerpo completo como cota conservadora. |
| `AI_PER_USER_PER_MINUTE`, `AI_CONCURRENCY` | 2 solicitudes por conversación y minuto y 2 solicitudes simultáneas por defecto. Usa una caché compartida entre procesos para mantener el límite global. |
| `AI_MONTHLY_BUDGET_MICROUSD`, `AI_REQUEST_RESERVE_MICROUSD` | Con cero se bloquean las llamadas. Define un cupo mensual y una reserva conservadora por llamada en millonésimas de USD, según el modelo y sus límites de entrada y salida. Controla también el gasto en el proveedor. |

Con `WHATSAPP_BOT_QUEUE=database`, ejecuta el proceso desde `back` con `php artisan queue:work whatsapp_support --queue=whatsapp-support --timeout=60`. Gestiona su arranque y reinicio con el supervisor de procesos del servidor. Recarga la configuración cacheada y reinicia el worker después de cambiarla. La tarea se almacena junto con el mensaje. La cola revisa el modo del caso, el último mensaje y la ventana de 24 horas antes del envío. Si llegan varios mensajes antes de que el proceso responda, se conserva el historial y se prioriza el último. Un envío sin confirmación queda en el historial para revisión.

### Horarios y catálogo

Registra horarios verificados mediante `php artisan whatsapp:branch-hours <ID_SUCURSAL> <ARCHIVO_JSON>`. Los días `1` a `7` corresponden a lunes a domingo. Cada sucursal tiene sus propias fechas de excepción. Un arreglo vacío para un feriado significa cierre programado ese día.

```json
{
  "weekly": {
    "1": [{"start": "08:00", "end": "20:00"}],
    "2": [{"start": "08:00", "end": "20:00"}],
    "3": [{"start": "08:00", "end": "20:00"}],
    "4": [{"start": "08:00", "end": "20:00"}],
    "5": [{"start": "20:00", "end": "04:00"}],
    "6": [{"start": "09:00", "end": "12:00"}, {"start": "15:00", "end": "20:00"}],
    "7": []
  },
  "exceptions": {"2026-12-25": []}
}
```

El bot usa la hora de Bolivia. Un cierre anterior a la apertura continúa al día siguiente, salvo que una excepción de ese día indique otro horario. Si solo existe texto libre, lo muestra como información registrada y evita afirmar si la sucursal está abierta. Un horario programado no verifica que la sucursal esté operando ni que haya empleados libres.

El catálogo queda cerrado al aplicar la migración. Clasifica cada producto revisado con `php artisan whatsapp:classify-product <ID_PRODUCTO> <CATEGORÍA>`. Categorías admitidas `non_regulated`, `otc`, `prescription`, `controlled` y `pending`. Para registrar un OTC, verifica la clasificación oficial y añade `--verified-otc`. Solo `non_regulated` permite consultas automáticas de catálogo. La política también bloquea las subcategorías de medicamentos controlados y otros indicios de restricción, aunque un producto tenga una clasificación incorrecta. El bot no tramita compras de medicamentos. Los pedidos de productos permitidos quedan como solicitudes para revisión humana. La política de Meta también aplica a respuestas del personal. [Política de mensajería de WhatsApp](https://whatsappbusiness.com/es-la/policy/)

### Ejemplos y operación

- «¿Hasta qué hora atienden?» → pide la sucursal. «Casco» → muestra el horario registrado. «¿Y mañana a las 3?» → confirma a. m. o p. m. antes de consultar el horario de esa fecha.
- «¿Cuál me queda más cerca?» → permite compartir la ubicación para comparar distancias en línea recta. El chat guarda «Ubicación compartida» y la cola cifra las coordenadas necesarias durante el trabajo.
- «¿Tienen Umbrela de 50 ml?» → busca presentaciones autorizadas y comprueba el inventario de la sucursal elegida. La disponibilidad es informativa y no implica una reserva.
- «Quiero hablar con alguien de Ex Terminal» → pasa a atención manual. Si el caso estaba sin asignar y la sucursal es inequívoca, queda allí y se avisa por la conexión en vivo. La bandeja muestra el motivo interno.

Gemini recibe únicamente palabras de logística seleccionadas de una lista permitida. No recibe historial, coordenadas, teléfono, marcas ni el texto original. Laravel valida el esquema, la confianza y las sucursales detectadas, consulta los datos y redacta la respuesta. Si el servicio falla o supera límites, el flujo pide claridad o pasa al equipo. [Salidas estructuradas de Gemini](https://ai.google.dev/gemini-api/docs/structured-output) · [Términos de Gemini](https://ai.google.dev/gemini-api/terms)

`php artisan whatsapp:assistant metrics` muestra los últimos estados agregados de los casos. `php artisan whatsapp:assistant prune` elimina la memoria conversacional y los límites de IA vencidos. El programador de Laravel ejecuta esa tarea diariamente. El historial de atención y los registros de deduplicación siguen la retención del sistema. Quedan pendientes la sincronización de estados de entrega, imágenes de producto, plantillas para fuera de la ventana de atención y el ingreso automático de pedidos de la web o la app.

## Validación local

En `back`, `php artisan test --filter='SupportCaseTest|WhatsAppTestWebhookTest'` verifica el asistente, los permisos por sucursal, el vínculo con pedidos y los webhooks en SQLite en memoria. Las llamadas a Meta y Gemini se simulan.

`node tests/customer-support.smoke.cjs` utiliza Playwright y las dependencias Vite/Quasar existentes. Inicia un servidor temporal, monta la página con una sesión ficticia y simula las respuestas de Laravel. Comprueba que se pueda abrir un caso por pedido, guardar una nota y recuperarla al volver a entrar. Cierra servidor y navegador al terminar.

Playwright debe estar disponible en el entorno (por ejemplo vía `NODE_PATH`). Puede indicarse un Chromium instalado mediante `SUPPORT_CHROMIUM_PATH`. Para capturas de escritorio y móvil, indicar una carpeta existente en `SUPPORT_SCREENSHOT_DIR`. No se agregaron dependencias al proyecto.
