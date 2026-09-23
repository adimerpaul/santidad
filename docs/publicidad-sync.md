# Publicidad sincronizada por sucursal

Electron y Android consultan `GET /api/publicidad-sync?agencia_id=...` cada 15 segundos y al recibir avisos de publicidad. El endpoint conserva el filtro de sucursal más publicidad global, en orden descendente de ID. `/api/publicidad-actual` mantiene su contrato para los clientes anteriores.

El manifiesto incluye hora del servidor, versión, una época común y duraciones canónicas. Las imágenes duran 10 segundos. Para los videos existentes y nuevos, el primer reproductor que descarga y lee sus metadatos informa su duración a `POST /api/publicidad-sync/durations`. El backend acepta una sola medición por archivo, valida su versión y sucursal y no modifica `updated_at`. Ningún reproductor activa un manifiesto incompleto.

Cada aplicación calcula la posición con la hora del servidor y un reloj monotónico, compensando aproximadamente la mitad del tiempo de ida y vuelta. Conserva la mejor de las últimas cinco muestras y descarta respuestas de más de cinco segundos. Revisa la posición cada 250 ms y corrige diferencias de video superiores a 400 ms. Esto ofrece sincronización aproximada, no una garantía de coincidencia de fotogramas entre equipos diferentes.

Las descargas se escriben a archivos temporales; la lista nueva se activa cuando todos sus archivos están listos. Los nombres locales incluyen la versión, y la caché se separa por servidor y sucursal. Si se pierde la conexión durante la reproducción, continúa la programación ya descargada usando el reloj monotónico. Si la aplicación reinicia sin conexión, reproduce su caché secuencialmente hasta recuperar una muestra de hora del servidor. Al cambiar una lista, cada equipo se incorpora a la nueva programación cuando finaliza sus descargas; durante esa preparación pueden mostrar listas diferentes.

## Puesta en funcionamiento

1. Desplegar el backend con la migración `2026_09_09_000001_add_duration_ms_to_publicidads_table.php` y ejecutar `php artisan migrate --force` en el servidor correspondiente. Es una columna nullable; no elimina anuncios.
2. Compilar y distribuir las dos aplicaciones actualizadas. Los clientes antiguos siguen reproduciendo, pero no se sincronizan.
3. Configurar el mismo servidor y la misma sucursal en las pantallas que deban coincidir. Cada caja mantiene su identificación independiente para cobro y QR.
4. Esperar la descarga y la primera medición de los videos. Comprobar en dos equipos que una pantalla encendida tarde se incorpora al anuncio en curso, probar un corte de red y una actualización de publicidad.

No se modificaron los eventos de cobro, QR, registro de caja ni autoarranque Android.

## Verificación local

- Backend: desde `back`, `php vendor/bin/phpunit --filter PublicidadSyncTest` (SQLite en memoria; no usa la base de negocio).
- Electron: `node --test tests/adSync.test.mjs tests/mediaDownload.test.mjs` y `quasar build -m electron --skip-pkg`.
- Android: desde `AppMovilPubli`, `dart tool/check_ad_sync.dart`, `flutter analyze` y `flutter build apk --release --dart-define=ENV=production`.

Los tests cubren el filtro de sucursal, contrato anterior, mediciones concurrentes, versiones obsoletas, desfases de reloj, límites de ciclo y descargas truncadas. La precisión física debe verificarse en las pantallas de destino.
