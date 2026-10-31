# Confirmación de ventas y reintentos

La venta, sus detalles y el stock se confirman en base de datos antes de procesar SIAT y enviar el correo. Una demora o respuesta perdida en esa etapa podía dejar la venta registrada y la pantalla sin confirmación. Repetir el POST generaba otra venta porque cada petición se trataba como una operación nueva.

## Protección

- Caja genera un `request_id` UUID por intento y conserva una copia de los datos enviados en `sessionStorage`, por usuario y pestaña, antes de enviarlos. El envío tiene un bloqueo independiente de los otros indicadores de carga.
- Mientras se confirma el resultado se bloquean los datos, el cierre del cobro y los cambios de canasta. Un resultado desconocido permite **Comprobar venta** o **Reintentar misma venta**: se reutilizan el identificador y los datos originales. Recargar la misma pestaña recupera ese intento y consulta su resultado; no envía una venta automáticamente.
- `sale_requests` guarda el identificador único, propietario, huella de la petición y venta resultante. Se reserva dentro de la misma transacción que guarda la venta, antes del contador de facturas y el stock. Dos peticiones iguales se serializan y la segunda devuelve el resultado existente. La validación fallida revierte también la reserva.
- La clave no permite cambiar los datos ni consultar una operación de otro usuario. Incluso una venta anulada o archivada sigue siendo el resultado de su intento original y no se vuelve a crear con la misma clave. Una nueva venta legítima, aunque tenga productos idénticos, utiliza otra clave.
- Solo el primer envío procesa SIAT y correo. Un reintento o `GET /api/sale-requests/{uuid}` consulta lo guardado. Si el procesamiento continúa, se informa que la venta ya existe y se pide revisar su factura en Movimientos; no se imprime anticipadamente una nota como si fuera la factura.
- Los fallos posteriores al guardado y los errores de impresión se distinguen de una venta rechazada. No provocan un nuevo POST. Una recuperación refresca el stock mostrado en lugar de restarlo otra vez.

Esta protección identifica reintentos de la misma operación; no intenta deducir duplicados por coincidencia de cliente, productos o importe. Los clientes antiguos que no envían `request_id` conservan su comportamiento. Se deben publicar frontend y backend juntos para proteger caja.

## Publicación

Aplicar en el backend, antes de utilizar la nueva pantalla:

```sh
php artisan migrate --path=database/migrations/2026_10_30_000002_create_sale_requests.php --force
```

La migración añade una tabla; no modifica ventas anteriores ni los datos bancarios o fiscales. No requiere variables de entorno ni un nuevo trabajador de colas. No se anulan automáticamente duplicados históricos. La integración SIAT sigue pudiendo tardar o fallar; la recuperación consulta la venta y no vuelve a emitirla.

## Validación

Las pruebas de `TraePricingTest` cubren una factura con CI, reintento durante SIAT, error después del commit, contador y stock únicos, aislamiento por usuario, anulación y claves distintas para ventas legítimas. SIAT se sustituye por un servicio simulado. Las pruebas de `trae-sale.test.cjs` cubren doble envío, respuesta perdida, recarga, datos congelados, fallo de impresión y errores de validación.

Además se comprobó la reserva simultánea en dos procesos de MySQL local mediante tablas temporales aisladas: una sola venta, un solo número y un solo descuento de stock. Las pruebas de interfaz usan servicios simulados y no emiten facturas reales.
