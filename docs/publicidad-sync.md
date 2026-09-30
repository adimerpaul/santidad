# Publicidad sincronizada por sucursal (protocolo 2)

Android TV 6.4.0 y pantallaCobro comparten una programación persistente: sucursal, revisión, versión de archivos, duraciones y hora de inicio. El reproductor calcula la posición con un reloj monotónico. Las imágenes duran 10 segundos; los videos conservan su duración completa. Android mantiene el audio y el volumen del mando; cobro conserva su comportamiento de audio anterior.

## Flujo y carga

- Laravel genera la programación al subir, activar/desactivar, eliminar o pulsar Reproducir. Una revisión nueva permite reiniciar aunque los archivos sean idénticos.
- El socket guarda una copia atómica en sockets/.data/advertising y publica ad_schedule en la sala publicidad:{agencia}. Al reiniciar recupera esas copias. Este directorio debe ser escribible y persistir entre despliegues.
- Los clientes confirman recepción y comunican ready/applied/error. Solo se reenvía la revisión pendiente; un reintento antiguo no puede reemplazar una revisión nueva.
- Al conectar, el socket entrega la versión vigente desde memoria. Si perdió su copia, permite a un solo cliente por sucursal solicitar GET /api/publicidad-sync; Laravel vuelve a entregar el estado al socket. Los demás esperan, evitando consultas simultáneas de todas las pantallas.
- Cada cinco minutos se intercambian hora y revisión mediante ad_clock. Se mide la ida y vuelta y se descartan muestras lentas. Esto supone 12 intercambios/hora por pantalla, sin consultas periódicas de lista ni de base de datos por pantalla. Los mensajes de transporte, estados de caja, reconexiones y descargas son adicionales.
- Los reintentos de una publicación que Laravel no pudo entregar se conservan en publicidad_schedules.pending. El scheduler revisa esa cola una vez por minuto: es una consulta indexada por servidor, independiente del número de TVs.
- Los videos antiguos sin duración se miden una vez después de descargarlos. Se reportan a POST /api/publicidad-sync/durations; se acepta la primera medición de la versión y sucursal correctas. La programación no se activa hasta tener todas las duraciones.

## Archivos y reproducción

Los archivos nuevos tienen nombre único, tamaño y SHA-256. Se descargan de R2 uno por uno a .part, se verifica integridad y se renombran al completar. Un archivo ya verificado no se vuelve a descargar. Los archivos antiguos sin hash conservan comprobaciones de longitud/descarga atómica cuando hay información disponible.

Una lista incompleta no sustituye la anterior. Se reintentan errores con espera creciente hasta cinco minutos. Se comprueba espacio libre antes de descargar y se conserva la lista activa. Android limita la caché de imágenes a 64 MB y la anchura de decodificación a 1080 píxeles. Se libera el controlador anterior antes de inicializar el siguiente.

La programación tiene una activación futura. Las pantallas listas esperan esa hora; las que terminan tarde se incorporan al anuncio y segundo vigentes. Una TV desconectada no bloquea a las demás. Al reiniciar sin conexión se reproduce la caché secuencialmente hasta recuperar una referencia de hora.

La duración canónica dirige las transiciones. Un video que termina antes, falla o deja de avanzar muestra el respaldo durante el resto de su intervalo. No se elimina individualmente de la lista, porque eso cambiaría el ciclo. Pequeñas diferencias se corrigen con velocidad; diferencias mayores de dos segundos se corrigen buscando la posición. La sincronización es aproximada y debe verificarse en los equipos reales; durante una descarga, falta de conexión o fallo de decodificación pueden mostrar imágenes distintas.

QR y datos conservan las salas terminal:{agencia}:{caja}. Registrar publicidad no elimina ni modifica la sala de caja.

## Despliegue

1. Resguardar la base de datos y desplegar backend. Revisar las migraciones pendientes y ejecutar php artisan migrate en el entorno correspondiente. La nueva migración añade metadatos de archivos y publicidad_schedules; no elimina anuncios existentes.
2. Mantener funcionando el scheduler de Laravel (cron de schedule:run cada minuto, o php artisan schedule:work bajo supervisión). Esto permite recuperar publicaciones fallidas. Se sigue usando SOCKET_SERVER_URL con la misma dirección del socket. Si se usa configuración cacheada, regenerarla con php artisan config:cache tras desplegar.
3. Desplegar sockets/advertising.js junto con sockets/index.js y reiniciar el proceso Node. Conservar .data/advertising. No requiere Redis, otro puerto ni otra dirección de conexión. Mantener los relojes de los servidores actualizados automáticamente.
4. Actualizar pantallaCobro y luego instalar la APK nueva. No instalar solamente la APK contra el backend anterior: necesita el protocolo 2.
5. Configurar la misma sucursal en las pantallas que deban coincidir; cada caja mantiene su número independiente.

La notificación del panel confirma que la orden quedó guardada, no que todas las pantallas ya terminaron de descargar. Los estados ready/applied/error se conservan en memoria en el socket; no se añadió un panel de monitoreo.

## Verificación

Desde la raíz del repositorio:

    node --test sockets/tests/advertising.test.js pantallaCobro/tests/adSync.test.mjs pantallaCobro/tests/adPlayer.test.mjs pantallaCobro/tests/mediaDownload.test.mjs pantallaCobro/tests/localMediaResponse.test.mjs

Desde back:

    php vendor/bin/phpunit --filter PublicidadScheduleTest

Los tests PHP utilizan SQLite en memoria, sin tocar la base de negocio. Las pruebas de socket requieren las dependencias instaladas de sockets y pantallaCobro.

Desde apk-publicidad:

    dart --enable-asserts tool/check_ad_sync.dart
    flutter test test/ad_media_test.dart
    flutter analyze
    flutter build apk --release

Desde pantallaCobro:

    npx quasar build -m electron --skip-pkg

Prueba física antes de distribuir: dos cajas y una TV en la misma sucursal, otra pantalla en sucursal distinta; encender una tarde; reproducir videos reales de 100–200 MB y distintas duraciones; enviar QR a cada caja; cambiar publicidad durante una descarga; cortar y recuperar la red; reiniciar el socket y la TV; usar el mando para volumen. Las pruebas de archivos sintéticos verifican integridad y descarga, no la compatibilidad de codecs del televisor.

Cobro sirve los archivos locales con soporte explícito de rangos de bytes (206/416), para buscar dentro de videos grandes sin cargarlos completos en memoria.

Para compilar Android en otra máquina, preparar .env y .env.production a partir de .env.example con las direcciones del sistema; esos archivos siguen excluidos de Git.

El lint general de cobro actualmente referencia @quasar/app-vite/eslint, que no está disponible en la versión instalada de Quasar. La compilación y las pruebas específicas se ejecutan por separado. El analizador Dart conserva avisos de estilo y dos advertencias del menú anterior.
