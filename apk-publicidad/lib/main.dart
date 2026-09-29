import 'dart:async';
import 'dart:ui' show PlatformDispatcher;
import 'dart:convert';
import 'dart:io' show File, Platform, exit;
import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:socket_io_client/socket_io_client.dart' as IO;
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

// flutter build (release) usa .env.production; flutter run (debug) usa .env.
// Se puede forzar con --dart-define=ENV=production o ENV=development.
const String _env = String.fromEnvironment('ENV', defaultValue: kReleaseMode ? 'production' : 'development');
const String appVersion = 'v6.3.0';
const MethodChannel _autoStartChannel = MethodChannel('com.santidad.app/autostart');
const Duration _imageDuration = Duration(seconds: 10);

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load(fileName: _env == 'production' ? '.env.production' : '.env');

  // Forzar orientación LANDSCAPE (nativa del TV)
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.landscapeLeft,
    DeviceOrientation.landscapeRight,
  ]);

  if (!kIsWeb) {
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    WakelockPlus.enable();
  }

  // Capturar errores fatales de Flutter para evitar loops de excepciones en timers
  FlutterError.onError = (details) {
    FlutterError.presentError(details);
    print('⚠️ FlutterError: ${details.exceptionAsString()}');
  };
  PlatformDispatcher.instance.onError = (error, stack) {
    print('⚠️ Unhandled: $error\n$stack');
    return true; // Marcar como manejado para no crashear
  };

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Santidad TV',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primarySwatch: Colors.blue,
      ),
      home: const PublicidadPlayer(),
    );
  }
}

class PublicidadPlayer extends StatefulWidget {
  const PublicidadPlayer({super.key});

  @override
  State<PublicidadPlayer> createState() => _PublicidadPlayerState();
}

/// Reproductor de publicidad sin sondeo: la playlist se pide UNA vez al iniciar
/// (publicidad-actual) y luego solo cambia cuando el panel envía el evento de
/// socket `publicidad_play`, que trae la playlist completa y el anuncio inicial.
/// Los archivos se guardan en el dispositivo y solo se descargan los que faltan.
class _PublicidadPlayerState extends State<PublicidadPlayer> with WidgetsBindingObserver {
  String get _rawServerUrl =>
      dotenv.env['SERVER_IP'] ?? dotenv.env['API_BASE_URL'] ?? 'https://bsantidad.tuprogam.com';

  String get apiBaseUrl {
    final base = _rawServerUrl.trim();
    if (base.endsWith('/api')) return base;
    if (base.endsWith('/')) return '${base}api';
    return '$base/api';
  }

  String get socketUrl {
    final socket = dotenv.env['SOCKET_IP'] ?? dotenv.env['SOCKET_URL'] ?? 'https://saventura.tuprogam.com';
    return socket.trim();
  }

  VideoPlayerController? _controller;
  List<Map<String, dynamic>> _playlist = [];
  int _currentIndex = 0;
  String? _localPath;
  String? _agenciaId;
  int _rotationTurns = 1; // 1 = 90° giro para TV horizontal con contenido vertical
  bool _isLoading = true;
  bool _dialogOpen = false; // Pausar timers cuando hay diálogos abiertos
  bool _initializing = false;
  bool _startupReady = false;
  String _status = 'Iniciando...';
  IO.Socket? socket;
  Timer? _imageTimer;
  int _playGeneration = 0;
  // Las actualizaciones de playlist se ejecutan en orden, nunca en paralelo
  Future<void> _playlistQueue = Future.value();
  String get _cacheKey => 'playlist_v2:$apiBaseUrl|$_agenciaId';
  DateTime? _lastEnterPress;
  final FocusNode _playerFocusNode = FocusNode(debugLabel: 'PublicidadPlayer');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _playerFocusNode.requestFocus();
    });
    _initApp();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    if (state == AppLifecycleState.resumed) {
      print('📺 TV reanudada o encendida (AppLifecycleState.resumed)');
      if (!kIsWeb) {
        SystemChrome.setPreferredOrientations([
          DeviceOrientation.landscapeLeft,
          DeviceOrientation.landscapeRight,
        ]);
        SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
        WakelockPlus.enable();
      }
      if (!_startupReady || _agenciaId == null) return;
      // Solo reanudar lo local; no se consulta la API
      if (_controller != null && _controller!.value.isInitialized && !_controller!.value.isPlaying) {
        _controller!.play();
      } else if (_playlist.isNotEmpty && _controller == null) {
        _playCurrent();
      }
      if (socket == null || socket!.disconnected) _initSocket();
    }
  }

  Future<void> _initApp() async {
    if (_initializing) return;
    _initializing = true;
    _startupReady = false;
    socket?.dispose();
    socket = null;

    try {
      await _loadSavedState();
      while (mounted && _agenciaId == null) {
        final selected = await _selectAgencia();
        if (!selected && mounted) {
          await Future.delayed(const Duration(seconds: 3));
        }
      }
      if (!mounted) return;

      await _configureAutoStart();
      if (!mounted) return;

      _startupReady = true;
      _initSocket();
      _loadPlaylist();
    } finally {
      _initializing = false;
    }
  }

  Future<void> _configureAutoStart() async {
    if (kIsWeb || !Platform.isAndroid || !mounted) return;

    try {
      final status =
          await _autoStartChannel.invokeMapMethod<String, dynamic>('getStatus') ??
              <String, dynamic>{};
      if (!mounted) return;

      // Nunca abrir pantallas de configuracion durante un arranque automatico.
      if (status['bootLaunch'] == true) return;

      bool homeConfigured = status['home'] == true;
      bool overlayGranted = status['overlay'] == true;

      if (!homeConfigured) {
        setState(() => _status = 'Configurando aplicacion de inicio...');
        homeConfigured = await _autoStartChannel
                .invokeMethod<bool>('requestHomeRole')
                .timeout(const Duration(seconds: 90), onTimeout: () => false) ??
            false;
      }

      // Algunos Android TV ignoran el launcher HOME elegido al reiniciar. El
      // permiso de superposicion es el respaldo que permite al servicio de boot
      // abrir la pantalla desde segundo plano en Android 11+.
      if (!overlayGranted && mounted) {
        final configureOverlay = await showDialog<bool>(
              context: context,
              barrierDismissible: false,
              builder: (dialogContext) => AlertDialog(
                title: const Text('Activar inicio automatico'),
                content: const Text(
                  'En la siguiente pantalla activa "Permitir mostrar sobre otras apps" '
                  'para Santidad TV y luego vuelve con el boton Atras. Este permiso '
                  'permite abrir la publicidad despues de reiniciar el televisor.',
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(dialogContext, false),
                    child: const Text('Ahora no'),
                  ),
                  FilledButton(
                    autofocus: true,
                    onPressed: () => Navigator.pop(dialogContext, true),
                    child: const Text('Configurar'),
                  ),
                ],
              ),
            ) ??
            false;

        if (configureOverlay && mounted) {
          setState(() => _status = 'Esperando permiso de inicio automatico...');
          overlayGranted = await _autoStartChannel
                  .invokeMethod<bool>('requestOverlayPermission')
                  .timeout(const Duration(seconds: 90), onTimeout: () => false) ??
              false;
        }
      }

      if (!mounted) return;
      if (overlayGranted) {
        setState(() => _status = 'Inicio automatico reforzado activado');
      } else if (homeConfigured) {
        setState(() => _status = 'Inicio automatico HOME configurado');
      } else {
        setState(() => _status = 'Falta activar el permiso de inicio automatico');
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            duration: Duration(seconds: 8),
            content: Text(
              'El televisor puede bloquear el arranque. Reinicia la app y activa '
              '"Mostrar sobre otras apps" para completar la configuracion.',
            ),
          ),
        );
      }
    } on PlatformException catch (e) {
      if (mounted) {
        setState(() => _status = 'No se pudo configurar el inicio: ${e.code}');
      }
    } catch (e) {
      if (mounted) {
        setState(() => _status = 'No se pudo verificar el inicio automatico');
      }
    }
  }

  void _initSocket() {
    try {
      socket?.dispose();
      print('Inicializando Socket.IO en: $socketUrl');

      socket = IO.io(socketUrl, IO.OptionBuilder()
        .setTransports(['websocket', 'polling'])
        .enableAutoConnect()
        .enableReconnection()
        .setReconnectionAttempts(double.infinity)
        .setReconnectionDelay(1000)
        .setReconnectionDelayMax(5000)
        .enableForceNew()
        .build());

      socket?.connect();
      socket?.onConnect((_) => print('✅ Conectado a Socket Server: $socketUrl'));
      socket?.onDisconnect((reason) => print('⚠️ Socket desconectado: $reason'));
      socket?.onError((err) => print('❌ Error en Socket: $err'));

      // Único evento que cambia la reproducción. No se reacciona a otros eventos
      // ni a reconexiones: así la pantalla nunca vuelve a consultar la API sola.
      socket?.on('publicidad_play', _onPlayCommand);
    } catch (e) {
      print('Error iniciando socket: $e');
    }
  }

  void _onPlayCommand(dynamic data) {
    if (!mounted || data is! Map || data['items'] is! List) return;
    final target = data['agencia_id'];
    if (target != null && target.toString() != _agenciaId) return;
    print('▶️ Orden de reproducción recibida (inicio: ${data['start_id'] ?? "principio"})');
    _enqueue(() => _applyPlaylist(
          data['items'] as List,
          startId: data['start_id']?.toString(),
          restart: true,
        ));
  }

  void _enqueue(Future<void> Function() task) {
    _playlistQueue = _playlistQueue.then((_) async {
      try {
        await task();
      } catch (e) {
        print('Error actualizando playlist; se mantiene la reproducción local: $e');
      }
    });
  }

  Future<void> _loadSavedState() async {
    final prefs = await SharedPreferences.getInstance();
    _agenciaId = prefs.getString('agencia_id');
    _rotationTurns = prefs.getInt('rotation_turns') ?? 1; // Default 90°
    if (_agenciaId == null) return;

    final cachedJson = prefs.getString(_cacheKey);
    if (cachedJson == null || cachedJson.isEmpty) return;
    try {
      final cached = (jsonDecode(cachedJson) as List)
          .map((e) => Map<String, dynamic>.from(e))
          .where((ad) => kIsWeb || File(ad['path'] ?? '').existsSync())
          .toList();
      if (cached.isEmpty || !mounted) return;
      setState(() {
        _playlist = cached;
        _currentIndex = 0;
        _isLoading = false;
        _status = 'Iniciando reproducción local...';
      });
      _playCurrent();
    } catch (e) {
      print('Error cargando playlist en caché: $e');
    }
  }

  Future<bool> _selectAgencia() async {
    if (!mounted) return false;
    _dialogOpen = true;
    _imageTimer?.cancel();
    _controller?.pause();
    setState(() => _status = 'Cargando sucursales...');
    try {
      final dio = Dio();
      final response = await dio.get('$apiBaseUrl/sucursales');
      if (response.statusCode == 200 && mounted) {
        List agencias = [];
        if (response.data is List) {
          agencias = response.data;
        } else if (response.data is Map && response.data['data'] is List) {
          agencias = response.data['data'];
        } else if (response.data is Map && response.data['sucursales'] is List) {
          agencias = response.data['sucursales'];
        }

        if (agencias.isEmpty) {
          setState(() => _status = 'No hay sucursales disponibles');
          await Future.delayed(const Duration(seconds: 3));
          return false;
        }

        final selected = await showDialog<String>(
          context: context,
          barrierDismissible: false,
          barrierColor: Colors.black54,
          builder: (dialogContext) => Center(
            child: Material(
              color: Colors.transparent,
              child: FocusScope(
                autofocus: true,
                child: Container(
                  width: 340,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  decoration: BoxDecoration(
                    color: const Color(0xFF16162A).withOpacity(0.95),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: Colors.white.withOpacity(0.08), width: 1),
                    boxShadow: [
                      BoxShadow(color: Colors.blueAccent.withOpacity(0.15), blurRadius: 30, spreadRadius: 2),
                      BoxShadow(color: Colors.black.withOpacity(0.5), blurRadius: 20),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: Colors.blueAccent.withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.store, color: Colors.blueAccent, size: 16),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Seleccionar Sucursal',
                                style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(colors: [Colors.blueAccent.withOpacity(0.3), Colors.purpleAccent.withOpacity(0.3)]),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              appVersion,
                              style: TextStyle(color: Colors.lightBlueAccent, fontSize: 11, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Usa las flechas (▲/▼) y OK del control remoto:',
                        style: TextStyle(color: Colors.white.withOpacity(0.4), fontSize: 10),
                      ),
                      Divider(color: Colors.white.withOpacity(0.08), height: 16),
                      ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 250),
                        child: SingleChildScrollView(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: List.generate(agencias.length, (index) {
                              final agencia = agencias[index];
                              final String nombre = agencia['nombre'] ?? 'Sucursal ${agencia['id']}';
                              final String idStr = agencia['id'].toString();
                              return _FocusableSucursalItem(
                                nombre: nombre,
                                isFirst: index == 0,
                                onTap: () => Navigator.pop(dialogContext, idStr),
                              );
                            }),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );

        if (selected != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('agencia_id', selected);
          if (mounted) {
            if (_agenciaId != selected) {
              _clearAdvertising();
            }
            setState(() => _agenciaId = selected);
          }
        }
      }
      return _agenciaId != null;
    } catch (e) {
      print('Error loading agencias: $e');
      if (mounted) {
        setState(() => _status = 'Error al cargar sucursales');
      }
      await Future.delayed(const Duration(seconds: 5));
      return false;
    } finally {
      _dialogOpen = false;
      if (mounted) {
        if (_controller != null && _controller!.value.isInitialized) {
          _controller!.play();
        } else if (_playlist.isNotEmpty) {
          _playCurrent();
        }
      }
    }
  }

  /// Única consulta HTTP de publicidad: al iniciar la app o al cambiar de sucursal.
  void _loadPlaylist() {
    _enqueue(() async {
      if (!mounted || _agenciaId == null) return;
      final agencia = _agenciaId;
      final response = await _adHttp().get('$apiBaseUrl/publicidad-actual',
          queryParameters: {'agencia_id': agencia});
      if (!mounted || agencia != _agenciaId) return;
      if (response.data is List) {
        await _applyPlaylist(response.data as List);
      } else if (response.data is Map && response.data['message'] != null) {
        await _applyPlaylist(const []);
      }
    });
  }

  Dio _adHttp() => Dio(BaseOptions(
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 30),
    headers: {'Cache-Control': 'no-cache'},
  ));

  /// HTTP client sin timeout de recepción para descargas de archivos grandes
  Dio _adDownloadHttp() => Dio(BaseOptions(
    connectTimeout: const Duration(seconds: 15),
    receiveTimeout: Duration.zero, // Sin timeout — videos de 130MB+ en WiFi lento
  ));

  /// Nombre local estable por anuncio: un anuncio nuevo siempre tiene id nuevo.
  String _localFileName(Map<String, dynamic> ad) {
    final base = ad['file_id'].toString().split('/').last.replaceAll('\\', '_');
    return 'publicidad_${ad['id']}_$base';
  }

  /// Aplica una playlist: descarga solo lo que falta, la guarda en caché y
  /// reproduce. Con [restart] empieza en [startId] (o desde el principio).
  Future<void> _applyPlaylist(List<dynamic> raw, {String? startId, bool restart = false}) async {
    final agencia = _agenciaId;
    if (!mounted || agencia == null) return;

    // Una orden global trae anuncios de todas las sucursales: quedarse con los propios
    final ads = raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .where((ad) => ad['agencia_id'] == null || ad['agencia_id'].toString() == agencia)
        .toList();

    final directory = kIsWeb ? null : await getApplicationDocumentsDirectory();
    final prepared = <Map<String, dynamic>>[];
    for (var i = 0; i < ads.length; i++) {
      if (!mounted || agencia != _agenciaId) return;
      final ad = ads[i];
      final url = ad['url'].toString();
      final path = kIsWeb ? url : '${directory!.path}/${_localFileName(ad)}';

      if (!kIsWeb && !await File(path).exists()) {
        if (mounted) setState(() => _status = 'Descargando ${i + 1}/${ads.length}: ${ad['name']}...');
        // Descarga atómica: archivo .part → rename al completar
        final partial = File('$path.part');
        try {
          await _adDownloadHttp().download(url, partial.path);
          await partial.rename(path);
        } catch (e) {
          print('Error descargando ${ad['name']}: $e');
          if (await partial.exists()) await partial.delete();
          continue;
        }
      }
      prepared.add({
        'id': ad['id'].toString(),
        'file_id': ad['file_id'],
        'path': path,
        'type': ad['type'],
        'name': ad['name'],
      });
    }
    if (!mounted || agencia != _agenciaId) return;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cacheKey, jsonEncode(prepared));
    if (!mounted || agencia != _agenciaId) return;

    if (prepared.isEmpty) {
      _clearAdvertising();
    } else {
      final currentId = _playlist.isNotEmpty && _currentIndex < _playlist.length
          ? _playlist[_currentIndex]['id']
          : null;
      var index = restart
          ? (startId == null ? 0 : prepared.indexWhere((a) => a['id'] == startId))
          : prepared.indexWhere((a) => a['id'] == currentId);

      setState(() {
        _playlist = prepared;
        _isLoading = false;
        _status = 'Playlist: ${prepared.length} items';
      });

      if (restart || index < 0) {
        _currentIndex = index < 0 ? 0 : index;
        _playCurrent();
      } else {
        // El anuncio actual sigue en la playlist: no cortar la reproducción
        _currentIndex = index;
      }
    }

    // Borrar archivos que ya no pertenecen a la playlist (incluye .part huérfanos)
    if (directory != null) {
      final active = prepared.map((a) => File(a['path']).uri.pathSegments.last).toSet();
      for (final file in directory.listSync().whereType<File>()) {
        final name = file.uri.pathSegments.last;
        if (name.startsWith('publicidad_') && !active.contains(name)) {
          try { await file.delete(); } catch (_) {}
        }
      }
    }
  }

  void _clearAdvertising() {
    _imageTimer?.cancel();
    _playGeneration++;
    _controller?.dispose();
    _controller = null;
    setState(() {
      _playlist = []; _localPath = null; _isLoading = false;
      _status = 'No hay publicidad activa';
    });
  }

  void _playCurrent() {
    if (_playlist.isEmpty || !mounted) return;
    if (_currentIndex >= _playlist.length) _currentIndex = 0;
    final currentAd = _playlist[_currentIndex];

    _imageTimer?.cancel();
    if (currentAd['type'] != 'video') {
      _playGeneration++;
      _controller?.dispose();
      _controller = null;
    }

    setState(() {
      _localPath = currentAd['path'];
      _status = 'Reproduciendo: ${currentAd['name']}';
    });

    if (currentAd['type'] == 'video') {
      _playVideo(currentAd['path']);
    } else {
      _displayImage();
    }
  }

  void _nextItem() {
    if (_playlist.isEmpty || !mounted) return;
    _currentIndex = (_currentIndex + 1) % _playlist.length;
    _playCurrent();
  }

  Future<void> _playVideo(String path) async {
    final generation = ++_playGeneration;
    final previous = _controller;
    _controller = null;
    // Diferir dispose para que AnimatedSwitcher complete su exit animation (300ms)
    if (previous != null) {
      previous.removeListener(_videoListener);
      Future.delayed(const Duration(milliseconds: 350), () async {
        try { await previous.dispose(); } catch (_) {}
      });
    }
    if (!mounted || generation != _playGeneration) return;
    final controller = kIsWeb
        ? VideoPlayerController.networkUrl(Uri.parse(path))
        : VideoPlayerController.file(File(path));
    _controller = controller;
    try {
      await controller.initialize().timeout(const Duration(seconds: 15));
      if (!mounted || generation != _playGeneration || !identical(controller, _controller)) return;
      setState(() => _isLoading = false);
      controller.addListener(_videoListener);
      if (!_dialogOpen) await controller.play();
    } catch (err) {
      print('Error cargando video: $err');
      // Pausa corta para no girar en bucle si todos los videos fallan
      await Future.delayed(const Duration(seconds: 1));
      if (mounted && generation == _playGeneration) _nextItem();
    }
  }

  void _videoListener() {
    final controller = _controller;
    if (controller == null || !mounted) return;
    final value = controller.value;
    if (value.duration > Duration.zero && value.position >= value.duration && !value.isPlaying) {
      controller.removeListener(_videoListener);
      _nextItem();
    }
  }

  void _displayImage() {
    if (mounted) setState(() => _isLoading = false);
    // Solo iniciar timer si no hay un diálogo abierto
    if (!_dialogOpen) {
      _imageTimer = Timer(_imageDuration, _nextItem);
    }
  }

  void _handleKeyEvent(KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;
      if (key == LogicalKeyboardKey.select ||
          key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter ||
          key == LogicalKeyboardKey.gameButtonA) {
        final now = DateTime.now();
        if (_lastEnterPress != null &&
            now.difference(_lastEnterPress!) < const Duration(milliseconds: 500)) {
          _lastEnterPress = null;
          _showSecretSettingsMenu();
        } else {
          _lastEnterPress = now;
        }
      }
    }
  }

  String _getRotationText(int turns) {
    switch (turns % 4) {
      case 1:
        return '90° (TV Horizontal)';
      case 2:
        return '180° (Invertido)';
      case 3:
        return '270° (TV Horizontal Invertido)';
      case 0:
      default:
        return '0° (Vertical Original)';
    }
  }

  void _showSecretSettingsMenu() {
    _dialogOpen = true;
    _imageTimer?.cancel();
    _controller?.pause();

    int tempRotation = _rotationTurns;

    showDialog(
      context: context,
      barrierColor: Colors.black54,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return Center(
              child: Material(
                color: Colors.transparent,
                child: Container(
                  width: 340,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  decoration: BoxDecoration(
                    color: const Color(0xFF16162A).withOpacity(0.95),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: Colors.white.withOpacity(0.08), width: 1),
                    boxShadow: [
                      BoxShadow(color: Colors.blueAccent.withOpacity(0.15), blurRadius: 30, spreadRadius: 2),
                      BoxShadow(color: Colors.black.withOpacity(0.5), blurRadius: 20),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Header con versión
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: Colors.blueAccent.withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.settings, color: Colors.blueAccent, size: 16),
                              ),
                              const SizedBox(width: 8),
                              const Text('Configuración', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(colors: [Colors.blueAccent.withOpacity(0.3), Colors.purpleAccent.withOpacity(0.3)]),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(appVersion, style: TextStyle(color: Colors.lightBlueAccent, fontSize: 11, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Sucursal: ${_agenciaId ?? "—"}  •  ${_playlist.length} items',
                        style: TextStyle(color: Colors.white.withOpacity(0.4), fontSize: 10),
                      ),
                      Divider(color: Colors.white.withOpacity(0.08), height: 16),

                      // Botón de rotación con contador visual
                      Focus(
                        autofocus: true,
                        onKeyEvent: (node, event) {
                          if (event is KeyDownEvent) {
                            final key = event.logicalKey;
                            if (key == LogicalKeyboardKey.select ||
                                key == LogicalKeyboardKey.enter ||
                                key == LogicalKeyboardKey.numpadEnter ||
                                key == LogicalKeyboardKey.space) {
                              tempRotation = (tempRotation + 1) % 4;
                              setDialogState(() {});
                              // Aplicar rotación en tiempo real
                              setState(() { _rotationTurns = tempRotation; });
                              SharedPreferences.getInstance().then((p) => p.setInt('rotation_turns', tempRotation));
                              return KeyEventResult.handled;
                            }
                          }
                          return KeyEventResult.ignored;
                        },
                        child: Builder(
                          builder: (context) {
                            final hasFocus = Focus.of(context).hasFocus;
                            return GestureDetector(
                              onTap: () {
                                tempRotation = (tempRotation + 1) % 4;
                                setDialogState(() {});
                                setState(() { _rotationTurns = tempRotation; });
                                SharedPreferences.getInstance().then((p) => p.setInt('rotation_turns', tempRotation));
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 150),
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                decoration: BoxDecoration(
                                  color: hasFocus ? Colors.amberAccent.withOpacity(0.15) : Colors.white.withOpacity(0.04),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: hasFocus ? Colors.amberAccent.withOpacity(0.5) : Colors.transparent),
                                ),
                                child: Row(
                                  children: [
                                    Icon(Icons.screen_rotation, color: Colors.amberAccent, size: 18),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          const Text('Rotación', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500)),
                                          Text(_getRotationText(tempRotation), style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 10)),
                                        ],
                                      ),
                                    ),
                                    // Indicador visual de grados
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: Colors.amberAccent.withOpacity(0.2),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text('${tempRotation * 90}°', style: const TextStyle(color: Colors.amberAccent, fontSize: 12, fontWeight: FontWeight.bold)),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 4),

                      // Cambiar Sucursal
                      _CompactMenuItem(
                        icon: Icons.store, color: Colors.blueAccent, label: 'Cambiar Sucursal',
                        onTap: () {
                          Navigator.pop(dialogContext);
                          Future.delayed(const Duration(milliseconds: 300), () async {
                            if (!mounted) return;
                            await _selectAgencia();
                            if (mounted) _loadPlaylist();
                          });
                        },
                      ),
                      // Reiniciar
                      _CompactMenuItem(
                        icon: Icons.refresh, color: Colors.greenAccent, label: 'Reiniciar App',
                        onTap: () { Navigator.pop(dialogContext); _initApp(); },
                      ),
                      // Salir
                      _CompactMenuItem(
                        icon: Icons.power_settings_new, color: Colors.redAccent, label: 'Salir',
                        onTap: () { Navigator.pop(dialogContext); SystemNavigator.pop(); exit(0); },
                      ),

                      const SizedBox(height: 6),
                      // Info compacta
                      Text(
                        'API: $apiBaseUrl',
                        style: TextStyle(color: Colors.white.withOpacity(0.2), fontSize: 8),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    ).whenComplete(() {
      _dialogOpen = false;
      if (_controller != null && _controller!.value.isInitialized) {
        _controller!.play();
      } else if (_playlist.isNotEmpty) {
        _playCurrent();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return KeyboardListener(
      focusNode: _playerFocusNode,
      onKeyEvent: _handleKeyEvent,
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onDoubleTap: _showSecretSettingsMenu,
        child: Scaffold(
          backgroundColor: Colors.black,
          body: Stack(
            children: [
              // Visor multimedia rotado 90° para TV horizontal con ajuste COVER completo
              RotatedBox(
                quarterTurns: _rotationTurns,
                child: SizedBox.expand(
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 300),
                    switchInCurve: Curves.easeInOutCubic,
                    switchOutCurve: Curves.easeInOutCubic,
                    transitionBuilder: (Widget child, Animation<double> animation) {
                      final fadeAnimation = CurvedAnimation(
                        parent: animation,
                        curve: Curves.easeInOutCubic,
                      );
                      final scaleAnimation = Tween<double>(begin: 0.96, end: 1.0).animate(fadeAnimation);
                      return FadeTransition(
                        opacity: fadeAnimation,
                        child: ScaleTransition(
                          scale: scaleAnimation,
                          child: child,
                        ),
                      );
                    },
                    child: Container(
                      key: ValueKey<String>(_localPath ?? 'empty_$_currentIndex'),
                      color: Colors.black,
                      width: double.infinity,
                      height: double.infinity,
                      child: _controller != null && _controller!.value.isInitialized
                          ? SizedBox.expand(
                              child: FittedBox(
                                fit: BoxFit.cover,
                                child: SizedBox(
                                  width: _controller!.value.size.width > 0 ? _controller!.value.size.width : 1080,
                                  height: _controller!.value.size.height > 0 ? _controller!.value.size.height : 1920,
                                  child: VideoPlayer(_controller!),
                                ),
                              ),
                            )
                          : _localPath != null
                              ? SizedBox.expand(
                                  child: kIsWeb 
                                      ? Image.network(_localPath!, fit: BoxFit.cover)
                                      : Image.file(File(_localPath!), fit: BoxFit.cover),
                                )
                              : const SizedBox.shrink(),
                    ),
                  ),
                ),
              ),
              if (_isLoading)
                Container(
                  color: Colors.black54,
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const CircularProgressIndicator(),
                        const SizedBox(height: 20),
                        Text(_status, style: const TextStyle(color: Colors.white)),
                      ],
                    ),
                  ),
                ),
              if (!_isLoading)
                Positioned(
                  bottom: 10,
                  right: 10,
                  child: Text(_status, style: const TextStyle(color: Colors.white24, fontSize: 10)),
                ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _playGeneration++;
    WidgetsBinding.instance.removeObserver(this);
    socket?.dispose();
    _controller?.removeListener(_videoListener);
    _controller?.dispose();
    _imageTimer?.cancel();
    _playerFocusNode.dispose();
    super.dispose();
  }
}


class _FocusableSucursalItem extends StatefulWidget {
  final String nombre;
  final bool isFirst;
  final VoidCallback onTap;

  const _FocusableSucursalItem({
    required this.nombre,
    required this.isFirst,
    required this.onTap,
  });

  @override
  State<_FocusableSucursalItem> createState() => _FocusableSucursalItemState();
}

class _FocusableSucursalItemState extends State<_FocusableSucursalItem> {
  bool _isFocused = false;

  KeyEventResult _handleKeyResult(FocusNode node, KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;
      if (key == LogicalKeyboardKey.select ||
          key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter ||
          key == LogicalKeyboardKey.space ||
          key == LogicalKeyboardKey.gameButtonA) {
        widget.onTap();
        return KeyEventResult.handled;
      }
    }
    return KeyEventResult.ignored;
  }

  @override
  Widget build(BuildContext context) {
    return Focus(
      autofocus: widget.isFirst,
      onFocusChange: (focused) {
        setState(() {
          _isFocused = focused;
        });
      },
      onKeyEvent: _handleKeyResult,
      child: GestureDetector(
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          margin: const EdgeInsets.symmetric(vertical: 3),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: _isFocused ? const Color(0xFF1E88E5).withOpacity(0.3) : Colors.white.withOpacity(0.04),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: _isFocused ? Colors.lightBlueAccent : Colors.transparent,
              width: 1,
            ),
          ),
          child: Row(
            children: [
              Icon(
                _isFocused ? Icons.radio_button_checked : Icons.radio_button_unchecked,
                color: _isFocused ? Colors.lightBlueAccent : Colors.white38,
                size: 16,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  widget.nombre,
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 13,
                    fontWeight: _isFocused ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
              ),
              if (_isFocused)
                const Icon(
                  Icons.arrow_forward_ios,
                  color: Colors.lightBlueAccent,
                  size: 14,
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FocusableMenuItem extends StatefulWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final bool isFirst;
  final VoidCallback onTap;

  const _FocusableMenuItem({
    required this.icon,
    required this.iconColor,
    required this.title,
    this.isFirst = false,
    required this.onTap,
  });

  @override
  State<_FocusableMenuItem> createState() => _FocusableMenuItemState();
}

class _FocusableMenuItemState extends State<_FocusableMenuItem> {
  bool _isFocused = false;

  void _handleKey(KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;
      if (key == LogicalKeyboardKey.select ||
          key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter ||
          key == LogicalKeyboardKey.space ||
          key == LogicalKeyboardKey.gameButtonA) {
        widget.onTap();
      }
    }
  }

  // Retornar handled para que el foco no se pierda después de presionar Enter
  KeyEventResult _handleKeyResult(FocusNode node, KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;
      if (key == LogicalKeyboardKey.select ||
          key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter ||
          key == LogicalKeyboardKey.space ||
          key == LogicalKeyboardKey.gameButtonA) {
        widget.onTap();
        return KeyEventResult.handled;
      }
    }
    return KeyEventResult.ignored;
  }

  @override
  Widget build(BuildContext context) {
    return Focus(
      autofocus: widget.isFirst,
      onFocusChange: (focused) {
        setState(() {
          _isFocused = focused;
        });
      },
      onKeyEvent: _handleKeyResult,
      child: GestureDetector(
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          margin: const EdgeInsets.symmetric(vertical: 4),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            color: _isFocused ? Colors.white.withOpacity(0.15) : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: _isFocused ? Colors.white54 : Colors.transparent,
              width: 1,
            ),
          ),
          child: Row(
            children: [
              Icon(widget.icon, color: widget.iconColor),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  widget.title,
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: _isFocused ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
              ),
              if (_isFocused)
                const Icon(Icons.chevron_right, color: Colors.white54),
            ],
          ),
        ),
      ),
    );
  }
}

class _CompactMenuItem extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  final VoidCallback onTap;

  const _CompactMenuItem({
    required this.icon,
    required this.color,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Focus(
      onKeyEvent: (node, event) {
        if (event is KeyDownEvent) {
          final key = event.logicalKey;
          if (key == LogicalKeyboardKey.select ||
              key == LogicalKeyboardKey.enter ||
              key == LogicalKeyboardKey.numpadEnter ||
              key == LogicalKeyboardKey.space) {
            onTap();
            return KeyEventResult.handled;
          }
        }
        return KeyEventResult.ignored;
      },
      child: Builder(
        builder: (context) {
          final hasFocus = Focus.of(context).hasFocus;
          return GestureDetector(
            onTap: onTap,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 150),
              margin: const EdgeInsets.symmetric(vertical: 2),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                color: hasFocus ? color.withOpacity(0.15) : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: hasFocus ? color.withOpacity(0.4) : Colors.transparent),
              ),
              child: Row(
                children: [
                  Icon(icon, color: color, size: 16),
                  const SizedBox(width: 10),
                  Text(label, style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: hasFocus ? FontWeight.w600 : FontWeight.normal)),
                  const Spacer(),
                  if (hasFocus) Icon(Icons.chevron_right, color: color.withOpacity(0.6), size: 16),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
