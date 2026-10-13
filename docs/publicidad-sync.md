# Publicidad: envío manual y diario a las 09:00

La APK Android TV y pantallaCobro reciben la programación por Socket.IO (protocolo 2).
Las pantallas no consultan `/api/publicidad-sync` ni `/api/publicidad-sync/durations`.
Ambas rutas permanecen deshabilitadas.

## Cuándo se publica

- Al pulsar Reproducir desde el inicio (o desde un anuncio), el panel hace una única
  petición POST `/api/publicidad/play`. Laravel genera la programación y la envía
  una vez al socket por sucursal seleccionada. Global publica todas las sucursales.
- A las 09:00 de America/La_Paz, Laravel ejecuta `publicidad:publish-pending --daily`
  y reinicia las programaciones de todas las sucursales. No hay tarea por minuto
  de publicación o reintentos de publicidad.
- Subir, activar, desactivar y borrar guardan los cambios; su reproducción se
  actualiza con el siguiente envío manual o diario.

## Pantallas y carga

Al conectar o reconectar, cada pantalla registra su sucursal una vez. El socket
entrega su copia persistida en `.data/advertising`, sin consultar Laravel.
Si no tiene copia, espera el próximo envío manual o diario. Las pantallas siguen
reproduciendo los archivos locales disponibles. No hacen polling ni intercambios
periódicos de reloj de publicidad: cada publicación incluye una referencia horaria
para recalibrar su reloj monotónico. La sincronización visual es aproximada.

Los videos sin duración se miden después de descargar y se comunican mediante
`ad_durations` por el mismo socket. El socket valida la revisión y versión, guarda
la primera duración válida y activa la lista cuando todas estén disponibles.
Las duraciones medidas se reutilizan en publicaciones posteriores del mismo archivo.
No hay petición HTTP ni lectura de base de datos para estas mediciones.

Los archivos se descargan desde R2 solamente cuando hacen falta; una descarga
fallida puede reintentarse. Socket.IO mantiene sus mensajes de conexión y reconexión.
El socket puede repetir la entrega de una publicación que no fue confirmada; esto
no inicia consultas a la API ni crea nuevas revisiones de programación.

QR, datos del cliente y confirmación de venta mantienen sus salas independientes
`terminal:{agencia}:{caja}`. La suscripción a publicidad no cambia esas salas ni
su latido de estado. La publicidad no reemplaza el QR o los datos visibles.

## Puesta en producción

1. Desplegar backend y socket juntos, conservando `.data/advertising`; reiniciar Node
   para cargar el código nuevo. `SOCKET_SERVER_URL` debe apuntar al socket utilizado
   por las pantallas del mismo entorno.
2. Mantener el scheduler de Laravel funcionando: cron `php artisan schedule:run`
   cada minuto, o `php artisan schedule:work` bajo supervisión. El scheduler comprueba
   la hora; la publicación de publicidad se ejecuta únicamente a las 09:00.
3. Actualizar pantallaCobro e instalar la APK compilada con estos cambios.
4. Pulsar Reproducir una vez para poblar el socket sin esperar las 09:00.

Si el socket falla, el panel muestra que la programación quedó pendiente. No hay
reintento automático hacia el backend: volver a pulsar Reproducir o esperar el
siguiente envío diario. Si el scheduler está apagado a las 09:00, no se publica ese día.
Los dispositivos apagados recuperan la última programación al reconectarse.

## Verificación

- `php artisan test --filter=PublicidadScheduleTest` desde back.
- `node --test sockets/tests/advertising.test.js pantallaCobro/tests/adSync.test.mjs pantallaCobro/tests/adPlayer.test.mjs pantallaCobro/tests/terminalDisplay.test.mjs pantallaCobro/tests/terminalConfig.test.mjs` desde la raíz.
- `dart --enable-asserts tool/check_ad_sync.dart`, `flutter test test/ad_media_test.dart`
  y `flutter build apk --release` desde apk-publicidad.
- `npx quasar build -m electron --skip-pkg` desde pantallaCobro.

Verificar en equipos reales: dos cajas y una TV de la misma sucursal; envío manual,
recuperación tras desconexión, video sin duración y recepción de QR/datos mientras
se descarga publicidad. Una pantalla con errores o apagada no bloquea a las demás.
