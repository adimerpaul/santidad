# Historial y revisión de cierres de caja

La página `/cierres-caja` reemplaza la cuadrícula de los últimos 15 días por filtros de sucursal, cuenta del turno y fechas, un resumen y un historial paginado. No consulta el antiguo endpoint `cash-closures/gaps`.

- **Cuenta del turno:** usuario que abrió el turno (`user_id`), aunque otra persona haya registrado el cierre. El detalle muestra ambas personas. Las cuentas se ofrecen según las sucursales de sus turnos históricos, aunque luego hayan cambiado de sucursal.
- **Fechas:** por defecto, últimos 7 días incluyendo hoy, con fecha de Bolivia. El selector permite 30, 50, 90 días, todo el historial o fechas personalizadas. Limpiar vuelve a 7 días. Se usa la fecha de cierre/relevo; para turnos abiertos, la apertura. Desde y Hasta incluyen el día completo.
- **Anomalías:** únicamente estado `CERRADO`, con diferencia redondeada a centavos menor a **−Bs 20** o mayor a **Bs 150**. Los límites exactos no activan alertas. Los turnos abiertos y pendientes no tienen arqueo confirmado y se muestran aparte.
- **Diferencia:** efectivo físico menos efectivo esperado, según los importes guardados al registrar el cierre. No se compara el efectivo contado con los pagos digitales ni con el total de ventas. Este reporte no recalcula ni altera los cierres históricos.
- **Colores e importes:** cualquier faltante se muestra rojo y cualquier sobrante verde, aunque no alcance el umbral de anomalía. Cero es neutro. La columna Digital / QR muestra el importe digital guardado (incluye también otros pagos digitales), solo como referencia y sin sumarlo al efectivo para calcular la diferencia.
- **Resumen:** cubre todos los resultados de sucursal/cuenta/fechas, independientemente de la página o de la vista de anomalías seleccionada. Los acumulados de faltantes y sobrantes corresponden solo a los cierres que superan cada umbral.

## API privada de lectura

`GET /api/cash-closures/report-options`: sucursales y cuentas con turnos accesibles; solo identificadores y nombres.

`GET /api/cash-closures/report`: `agencia_id`, `user_id`, `desde`, `hasta`, `vista`, `page`, `per_page` (15, 30 o 50). Respuesta `resumen` y paginador `cierres`. Vistas: `todos`, `anomalias`, `faltantes`, `sobrantes`, `pendientes`, `abiertos`, `cerrados`.

Se mantiene el permiso existente: administrador (usuario 1) puede consultar todas las sucursales y diferencias; el resto solo su sucursal y sin importes del sistema ni diferencias. El servidor aplica el permiso incluso si el cliente manipula filtros. No hay consultas automáticas periódicas: se carga al entrar, aplicar filtros, cambiar vista/página o actualizar. Las respuestas tardías se descartan al cambiar de consulta, usuario o sucursal asignada.

La página espera a que esté disponible el usuario antes de cargar opciones, y reacciona a su carga posterior. Esto corrige el selector vacío al entrar directamente o recargar. Los fallos al cargar opciones tienen un botón de reintento.

## Publicación y verificación

Publicar backend y frontend compilado juntos. No requiere nuevas migraciones, cambios en `.env` ni procesos adicionales. El endpoint anterior `cash-closures` y las operaciones de apertura/cierre se conservan.

Pruebas: `php artisan test --filter=CashClosureReportTest` usa exclusivamente SQLite en memoria (incluye más de 200 registros, umbrales, filtros y permisos). `node --test tests/cash-closures.test.cjs` desde `front` verifica inicialización tardía, concurrencia de consultas, filtros y permisos visuales.
