# Precios y ventas TRAE

El administrador dispone de **Gestión > Promociones > TRAE · Ventas y descuentos** (`/promociones?canal=trae`; `/trae` redirige allí). Puede configurar descuentos propios o seguir los de web, APK o una sucursal, además de la comisión de la plataforma. La página incluye búsqueda, selección múltiple y vista previa del precio por unidad.

## Origen de descuentos

**Elegir origen de descuentos** permite seleccionar **Propios de TRAE**, **Igual que página web**, **Igual que APK móvil** o **Igual que sucursal**. Las instalaciones existentes conservan el modo propio al migrar. Al seguir otro canal se utiliza el mismo cálculo vigente de promociones y descuento permanente del producto, incluidos sus redondeos, exclusiones y prioridades. Las reglas propias de TRAE quedan guardadas y no se suman; su edición se deshabilita en la interfaz hasta volver al modo propio.

Para sucursal se exige una **Sucursal de referencia**. Para web y APK es opcional: sin referencia se toman las promociones generales del canal, como una consulta normal sin sucursal; con referencia también se evalúan las promociones de esa sucursal. La referencia elegida fija un precio único para el catálogo delivery y caja TRAE. El parámetro `agencia_id` de la API continúa filtrando stock; no cambia el origen de precios. Por ejemplo, seguir la sucursal Matriz utiliza sus promociones aunque la venta o el stock consultado pertenezcan a otra sucursal.

La configuración se guarda en `trae_settings.fuente_precios` y `fuente_agencia_id`. No se copian promociones ni se sincronizan registros periódicamente: los precios se calculan al consultar, reutilizando el servicio normal y sus cachés. Los cambios y vigencias del canal se reflejan en nuevas consultas; el consumidor externo debe refrescar su catálogo. Comisión TRAE, ventas históricas y combos internos conservan sus respectivos cálculos. Cambiar el origen no modifica las promociones del canal elegido.

La configuración privada incluye una `revision` que la interfaz devuelve al guardar. Si otra sesión cambió los valores, el servidor responde 409 y la pantalla carga los actuales para revisarlos antes de guardar; no reintenta el cambio automáticamente. Los clientes antiguos que omiten los campos de origen conservan el origen vigente. Cambiar únicamente la comisión no recorre el catálogo para validar precios, porque no modifica los precios de los productos. Al actualizar el origen se limpia la selección anterior del panel.

En **Precios y descuentos**, las tarjetas superiores resumen el descuento general, la comisión y las reglas activas. Los botones de edición abren la configuración sin ocupar el espacio del catálogo. Busca productos por nombre o código, filtra categorías y marca las casillas: la selección se conserva entre búsquedas y páginas (máximo 200). El panel **Tu selección** permite revisar el precio propuesto, elegir un porcentaje rápido o escribir uno y aplicarlo al grupo. Si falla el guardado, la selección se conserva. En celular, **Ver selección** lleva al editor del grupo. El catálogo carga 20, 40 o 60 filas por página y cancela consultas anteriores al cambiar la búsqueda. Los medicamentos excluidos no se pueden seleccionar. Las reglas configuradas tienen su propia búsqueda, filtro por tipo, edición, pausa y eliminación.

## Resumen de ventas TRAE

La pestaña **Ventas TRAE** muestra cantidad de ventas, total vendido, comisión y neto restante, además del detalle por venta. Se puede consultar todo el historial o filtrar por sucursal y fechas (ambos días incluidos). Los totales abarcan todas las páginas del filtro; excluyen ventas anuladas y eliminadas. Solo se incluyen las ventas registradas con `canal_venta=trae`: no se atribuyen a TRAE ventas antiguas sin identificación.

El resumen reutiliza su conteo para paginar, sin otra consulta de conteo. Dos índices exclusivos del filtro TRAE permiten localizar sus ventas por fecha y por sucursal sin recorrer las ventas de otros canales. La pestaña Ventas carga su propio reporte y reutiliza el catálogo de sucursales de la sesión; precios, reglas y productos se solicitan al abrir Precios y descuentos. Cambiar filtros cancela la petición anterior y descarta respuestas atrasadas. Los importes de ventas no se almacenan en caché entre consultas.

Cada comisión se toma del importe guardado en la venta (12 % por defecto), aunque se cambie después la configuración. El neto resta únicamente esa comisión: no descuenta costos ni impuestos y no significa utilidad ni confirmación de un abono bancario. El reporte es de consulta y no modifica cobros ni liquidaciones. El endpoint `GET /api/trae/sales` requiere administrador, admite `agencia_id`, `desde`, `hasta`, `page` y `per_page` (máximo 100), y no permite caché.

## Reglas propias

Las reglas por categoría y por productos elegidos pueden tener **vigencia indefinida** o un plazo con **inicio y fin, incluyendo hora**, en `America/La_Paz` (Bolivia). El inicio es inclusivo y el fin exclusivo: si termina a las 18:00, desde las 18:00 ya no aplica. Antes del inicio y después del fin se utiliza la siguiente regla vigente (categoría o general). API y caja evalúan el horario en el servidor en cada nueva consulta, sin depender de tareas programadas; la aplicación externa debe refrescar los precios que consume.

Las reglas existentes conservan vigencia indefinida. Sigue existiendo una regla por producto o categoría: editarla reemplaza su porcentaje y su vigencia; no reserva una segunda promoción ni recupera automáticamente una versión anterior. La tabla muestra **PROGRAMADA, ACTIVA, VENCIDA o PAUSADA**. Pausar no borra las fechas; para volver una regla indefinida se limpian sus fechas. Las ventas registradas conservan sus precios y comisiones. El servidor valida precios actuales y futuros, incluidos los cambios al vencer excepciones, y rechaza cambios que puedan dejar un precio cero.

- Prioridad: producto activo, categoría activa y, finalmente, descuento general.
- Un porcentaje explícito de **0 %** deja el producto sin descuento, aunque su categoría tenga otra regla.
- Pausar o eliminar una regla permite heredar la siguiente prioridad. No significa bloquear todos los descuentos.
- Los productos nuevos heredan la regla de su categoría.
- TRAE no acumula descuentos permanentes del producto ni promociones de web, APK o sucursal.
- Se conserva la exclusión de promociones de la subcategoría «Medicamentos Controlados».
- El redondeo es el existente del sistema: precio base con centavos y precio final por unidad a Bs 0,10.
- Inicialmente el descuento general es **0 %** y la comisión es **12 %**. Al publicar, revisar las reglas definitivas: el catálogo de delivery deja de heredar las promociones web.
- Los porcentajes que producirían un precio cero se rechazan considerando las excepciones activas del producto y la categoría. También se verifica el descuento heredado al pausar o eliminar reglas; si es inválido, se cancela todo el cambio. La vista de abono no constituye una estimación de utilidad: faltan mercadería, impuestos y gastos.

## API pública existente

Se conservan las rutas y el formato del PDF: `GET /api/delivery/productos`, `GET /api/delivery/productos/{id}` y `GET /api/delivery/sucursales`. Los productos conservan `precio`, `descuento_porcentaje`, `descuento_monto`, `precio_venta`, imagen y disponibilidad por sucursal. Listado y detalle usan el mismo cálculo que caja para TRAE y envían `Cache-Control: no-store`.

La plataforma debe consumir `precio_venta` como precio final y refrescar su catálogo. No hay pedidos entrantes ni cotizaciones con precio reservado. Se utiliza el precio vigente al vender. Las imágenes y el dominio conservan el funcionamiento del servidor actual.

## Caja

En Venta, **Venta TRAE** cambia el canal de toda la canasta y consulta los precios del servidor. Si falla esa consulta, se conserva el canal y la canasta anteriores. No se permite mezclar pedidos online de la web/APK con TRAE.

El cambio de canal se bloquea durante la importación de pedidos online. Las respuestas pendientes se descartan si se cambia de usuario, se reemplaza la canasta o se abandona la página. Al cerrar sesión se limpia la canasta TRAE y se restaura el canal sucursal.

El precio se verifica de nuevo al cobrar. Si cambió desde la apertura del diálogo de pago, la venta se rechaza y debe cerrarse el diálogo y volver a verificar la canasta antes de cobrar. No se permiten descuentos manuales ni aportes adicionales en este canal. El banco y SIAT mantienen sus integraciones existentes.

El catálogo de caja descarta respuestas anteriores aunque la cancelación HTTP llegue tarde. También descarta respuestas de una sesión, sucursal o canal anterior y las que llegan después de abandonar la página. La carga inicial del usuario no invalida una consulta del mismo inicio de sesión.

La venta guarda `canal_venta=trae`, porcentaje de comisión y monto de comisión. El total de venta y el medio de pago conservan el importe completo de los productos: la comisión es un dato separado, no un descuento adicional al cliente ni una liquidación bancaria automática. Los movimientos identifican TRAE y muestran la comisión. Al terminar o vaciar la canasta se vuelve al canal sucursal.

Las anulaciones y reversiones conservan los importes históricos y utilizan el flujo de stock existente. Para corregir una venta TRAE se anula y registra una nueva; la edición genérica de ventas no puede reemplazar sus precios por importes arbitrarios.

## Combos internos de TRAE

En Precios y descuentos → Combos internos se crean recetas con cantidades y precios independientes del catálogo público. Admiten porcentaje global, precios por producto o un total fijo repartido en precios unitarios múltiplos de Bs 0,10. Un total fijo debe sumar exactamente; si las cantidades impiden representarlo, se rechaza y se pide ajustar el total o las cantidades. Los porcentajes efectivos se muestran con dos decimales sin recalcular los importes ya repartidos.

En Ventas, **Combos TRAE** carga la receta completa y activa automáticamente el canal TRAE. La comisión se calcula sobre el total vendido. Las cantidades se cambian por combo completo; el mismo producto puede aparecer en varios combos y también suelto, conservando cada precio. El stock se comprueba sumando todas esas líneas. La receta y sus precios quedan guardados en el historial de la venta aunque luego se edite o elimine el combo.

Los precios guardados del combo se mantienen hasta editarlo. Si cambia su versión o vigencia, se rechaza una canasta desactualizada. Si un producto pasa a estar excluido de descuentos, el combo debe actualizarse antes de venderlo con descuento. Al reactivar una venta anulada se comprueba el stock total y de sucursal antes de solicitar la reversión a SIAT. Las respuestas pendientes de caja se descartan al cambiar usuario, sucursal o canasta; cerrar y reabrir el selector tampoco aplica selecciones antiguas.

Estos combos usan rutas privadas `internal-combos`, sin modificar el formato ni los descuentos de `/api/delivery/productos`. No realizan consultas periódicas: se consulta al abrir, buscar, paginar, repartir un total o verificar una venta.

## Publicación

1. Respaldar la base de producción y subir los archivos del backend y las migraciones de esta misma versión antes de activar la nueva interfaz.
2. En `back`, ejecutar `php artisan migrate --path=database/migrations/2026_10_29_000001_create_trae_pricing.php --force`. La nueva migración agrega dos tablas exclusivas y tres campos con valores por defecto a ventas, sin reemplazar inventario, promociones ni ventas existentes. El comando se limita a esta migración para no ejecutar otros cambios pendientes.
   Después ejecutar `php artisan migrate --path=database/migrations/2026_10_29_000002_add_trae_sales_indexes.php --force` para crear los índices del reporte. Si TRAE ya estaba instalado, solo se necesita esta segunda migración; la primera debe constar como ejecutada. Estos índices no cambian los importes ni los registros de ventas.
   Ejecutar además `php artisan migrate --path=database/migrations/2026_10_29_000003_add_validity_to_trae_discount_rules.php --force` antes de activar la interfaz que admite vigencias. Agrega tres campos a las reglas TRAE, con vigencia indefinida por defecto para las actuales. No altera ventas ni inventario. Si las anteriores ya constan como ejecutadas, basta esta nueva migración.
   Para los combos internos ejecutar también `php artisan migrate --path=database/migrations/2026_10_29_000004_create_internal_combos.php --force`. Agrega las tablas de recetas y los campos de historial en ventas y detalles; no crea descuentos públicos ni cambia el stock existente.
   Para habilitar los orígenes compartidos ejecutar `php artisan migrate --path=database/migrations/2026_10_30_000001_add_price_source_to_trae_settings.php --force`. Agrega únicamente dos campos a la configuración TRAE y conserva el modo propio como valor inicial. Debe aplicarse antes de usar el nuevo selector.
3. Compilar/publicar el frontend como habitualmente (`npm run build` en `front`). El código fuente por sí solo no actualiza una web que sirve archivos compilados.
4. Conservar el `.env` de producción y los datos bancarios/SIAT. No se necesitan variables nuevas para TRAE. No copiar cachés locales ni reemplazar la base de producción por la local.
5. Configurar los descuentos definitivos en el panel de producción. Los registros introducidos localmente no viajan con Git.
6. Comprobar listado y detalle de delivery y una canasta TRAE; verificar también los precios habituales de web/APK/sucursal.

No se ejecutan migraciones en producción ni se publican cambios automáticamente desde este trabajo.

## Verificación

Desde `back`: `php vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter 'TraePricingTest|PromotionScopeTest|WebOrderStockTest|OnlineSaleCancellationTest|MoneyRoundingTest'`.

Desde `front`: `node --test tests/trae-sale.test.cjs tests/trae-report.test.cjs tests/trae-pricing.test.cjs tests/internal-combos.test.cjs tests/combo-picker.test.cjs`, lint de los archivos modificados y `npm run build`.

Las pruebas PHP usan bases SQLite temporales en memoria y sustituyen el servicio SIAT; no hacen operaciones bancarias ni fiscales reales.
