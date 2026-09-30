import 'dart:async';
import 'dart:ui' show PlatformDispatcher;
import 'dart:convert';
import 'dart:io' show File, Platform, exit;
import 'package:flutter/foundation.dart' show kIsWeb, kReleaseMode;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';
import 'package:dio/dio.dart';
import 'ad_sync.dart';
import 'ad_media.dart';
import 'package:socket_io_client/socket_io_client.dart' as IO;
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

// flutter build (release) usa .env.production; flutter run (debug) usa .env.
// Se puede forzar con --dart-define=ENV=production o ENV=development.
const String _env = String.fromEnvironment(
  'ENV',
  defaultValue: kReleaseMode ? 'production' : 'development',
);
const String appVersion = 'v6.4.0';
const MethodChannel _autoStartChannel = MethodChannel(
  'com.santidad.app/autostart',
);
const Duration _imageDuration = Duration(seconds: 10);

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  PaintingBinding.instance.imageCache.maximumSizeBytes = 64 * 1024 * 1024;
  await dotenv.load(
    fileName: _env == 'production' ? '.env.production' : '.env',
  );

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
      theme: ThemeData(brightness: Brightness.dark, primarySwatch: Colors.blue),
      home: const PublicidadPlayer(),
    );
  }
}

class PublicidadPlayer extends StatefulWidget {
  const PublicidadPlayer({super.key});

  @override
  State<PublicidadPlayer> createState() => _PublicidadPlayerState();
}

/// Programación por sucursal, reloj compartido y archivos locales.
class _PublicidadPlayerState extends State<PublicidadPlayer>
    with WidgetsBindingObserver {
  String get _rawServerUrl =>
      dotenv.env['SERVER_IP'] ??
      dotenv.env['API_BASE_URL'] ??
      'https://bsantidad.tuprogam.com';

  String get apiBaseUrl {
    final base = _rawServerUrl.trim();
    if (base.endsWith('/api')) return base;
    if (base.endsWith('/')) return '${base}api';
    return '$base/api';
  }

  String get socketUrl {
    final socket =
        dotenv.env['SOCKET_IP'] ??
        dotenv.env['SOCKET_URL'] ??
        'https://saventura.tuprogam.com';
    return socket.trim();
  }

  VideoPlayerController? _controller;
  List<Map<String, dynamic>> _playlist = [];
  int _currentIndex = 0;
  String? _localPath;
  String? _agenciaId;
  int _rotationTurns =
      1; // 1 = 90° giro para TV horizontal con contenido vertical
  bool _isLoading = true;
  bool _dialogOpen = false; // Pausar timers cuando hay diálogos abiertos
  bool _initializing = false;
  bool _startupReady = false;
  String _status = 'Iniciando...';
  IO.Socket? socket;
  Timer? _imageTimer;
  int _playGeneration = 0;
  final AdServerClock _serverClock = AdServerClock();
  Map<String, dynamic>? _manifest, _desired, _preparedManifest;
  List<Map<String, dynamic>>? _preparedItems;
  Timer? _syncTimer, _clockTimer, _prepareRetry, _bootstrapRetry, _clockRetry;
  bool _preparing = false,
      _bootstrapBusy = false,
      _foreground = true,
      _mediaFailed = false;
  bool _aligning = false;
  bool _socketClock = false;
  int _retrySeconds = 5;
  int _lastProgressTick = 0, _lastPositionMs = -1;
  int _seekRecoveries = 0;
  double _slotEndsAt = 0;
  String get _scope => apiBaseUrl + '|' + (_agenciaId ?? '');
  String get _cacheKey => 'playlist_v3:' + _scope;
  AdPosition? get _target => adPosition(_manifest, _serverClock.time);
  DateTime? _lastEnterPress;
  final FocusNode _playerFocusNode = FocusNode(debugLabel: 'PublicidadPlayer');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _playerFocusNode.requestFocus();
    });
    _syncTimer = Timer.periodic(
      const Duration(milliseconds: 250),
      (_) => _syncPlayback(),
    );
    _clockTimer = Timer.periodic(
      const Duration(minutes: 5),
      (_) => _requestClock(),
    );
    _initApp();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    _foreground = state == AppLifecycleState.resumed;
    if (!_foreground) {
      _controller?.pause();
      _imageTimer?.cancel();
    }
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
      if (_controller != null &&
          _controller!.value.isInitialized &&
          !_controller!.value.isPlaying) {
        _controller!.play();
      } else if (_playlist.isNotEmpty && _controller == null) {
        _playCurrent();
      }
      if (socket == null || socket!.disconnected) {
        _initSocket();
      } else {
        _requestClock(register: true);
      }
      _syncPlayback();
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
      _bootstrapRetry = Timer(const Duration(seconds: 5), () {
        if (_desired == null && !(socket?.connected ?? false)) _loadPlaylist();
      });
    } finally {
      _initializing = false;
    }
  }

  Future<void> _configureAutoStart() async {
    if (kIsWeb || !Platform.isAndroid || !mounted) return;

    try {
      final status =
          await _autoStartChannel.invokeMapMethod<String, dynamic>(
            'getStatus',
          ) ??
          <String, dynamic>{};
      if (!mounted) return;

      // Nunca abrir pantallas de configuracion durante un arranque automatico.
      if (status['bootLaunch'] == true) return;

      bool homeConfigured = status['home'] == true;
      bool overlayGranted = status['overlay'] == true;

      if (!homeConfigured) {
        setState(() => _status = 'Configurando aplicacion de inicio...');
        homeConfigured =
            await _autoStartChannel
                .invokeMethod<bool>('requestHomeRole')
                .timeout(const Duration(seconds: 90), onTimeout: () => false) ??
            false;
      }

      // Algunos Android TV ignoran el launcher HOME elegido al reiniciar. El
      // permiso de superposicion es el respaldo que permite al servicio de boot
      // abrir la pantalla desde segundo plano en Android 11+.
      if (!overlayGranted && mounted) {
        final configureOverlay =
            await showDialog<bool>(
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
          overlayGranted =
              await _autoStartChannel
                  .invokeMethod<bool>('requestOverlayPermission')
                  .timeout(
                    const Duration(seconds: 90),
                    onTimeout: () => false,
                  ) ??
              false;
        }
      }

      if (!mounted) return;
      if (overlayGranted) {
        setState(() => _status = 'Inicio automatico reforzado activado');
      } else if (homeConfigured) {
        setState(() => _status = 'Inicio automatico HOME configurado');
      } else {
        setState(
          () => _status = 'Falta activar el permiso de inicio automatico',
        );
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
    socket?.dispose();
    socket = IO.io(
      socketUrl,
      IO.OptionBuilder()
          .setTransports(['websocket', 'polling'])
          .disableAutoConnect()
          .enableReconnection()
          .setReconnectionAttempts(double.infinity)
          .setReconnectionDelay(1000)
          .setReconnectionDelayMax(30000)
          .enableForceNew()
          .build(),
    );
    socket!.onConnect((_) {
      _socketClock = false;
      _requestClock(register: true);
    });
    socket!.on('ad_schedule', _receiveSchedule);
    socket!.connect();
  }

  void _requestClock({bool register = false}) {
    final connection = socket;
    if (!mounted ||
        connection == null ||
        !connection.connected ||
        _agenciaId == null)
      return;
    final scope = _scope;
    final start = _serverClock.tick;
    var completed = false;
    _clockRetry?.cancel();
    _clockRetry = Timer(const Duration(seconds: 15), () {
      if (!completed && mounted && scope == _scope)
        _requestClock(register: true);
    });
    connection.emitWithAck(
      register ? 'ad_register' : 'ad_clock',
      {
        'agencia_id': int.parse(_agenciaId!),
        'revision': _desired?['revision'] ?? 0,
      },
      ack: (dynamic data) {
        if (completed ||
            !mounted ||
            connection != socket ||
            scope != _scope ||
            data is! Map)
          return;
        completed = true;
        _clockRetry?.cancel();
        if (data['success'] != true) {
          _clockRetry = Timer(
            const Duration(seconds: 15),
            () => _requestClock(register: true),
          );
          return;
        }
        if (!_socketClock) _serverClock.clear();
        _socketClock = true;
        if (data['server_time_ms'] is num)
          _serverClock.sample(data['server_time_ms'], start, _serverClock.tick);
        if (data['schedule'] is Map) _receiveSchedule(data['schedule']);
        if (data['needs_snapshot'] == true) _loadPlaylist();
        if (data['pending_bootstrap'] == true) {
          _clockRetry = Timer(
            const Duration(seconds: 16),
            () => _requestClock(register: true),
          );
        }
        _syncPlayback();
      },
    );
  }

  void _receiveSchedule(dynamic data) {
    if (!mounted ||
        data is! Map ||
        data['protocol'] != 2 ||
        data['items'] is! List ||
        data['agencia_id'].toString() != _agenciaId ||
        data['revision'] is! int)
      return;
    socket?.emit('ad_received', {'revision': data['revision']});
    if (_desired != null && data['revision'] <= _desired!['revision']) return;
    _desired = Map<String, dynamic>.from(data);
    _preparedManifest = null;
    _preparedItems = null;
    _prepareRetry?.cancel();
    _retrySeconds = 5;
    _prepareSchedule();
  }

  Future<void> _loadSavedState() async {
    final prefs = await SharedPreferences.getInstance();
    _agenciaId = prefs.getString('agencia_id');
    _rotationTurns = prefs.getInt('rotation_turns') ?? 1;
    if (_agenciaId == null) return;
    final saved = prefs.getString(_cacheKey);
    final legacy =
        prefs.getString('playlist_v2:' + _scope) ??
        prefs.getString('cached_playlist:' + _scope);
    try {
      final decoded = jsonDecode(saved ?? legacy ?? '[]');
      final List items = decoded is Map ? decoded['items'] : decoded;
      // Keep slot positions even when a cached file is missing; show fallback in its slot.
      final cached = items.map((e) => Map<String, dynamic>.from(e)).toList();
      if (decoded is Map && decoded['manifest'] is Map)
        _manifest = Map<String, dynamic>.from(decoded['manifest']);
      if (!mounted || cached.isEmpty) return;
      setState(() {
        _playlist = cached;
        _currentIndex = 0;
        _isLoading = false;
      });
      _playCurrent();
    } catch (e) {
      print('Error reading advertising cache: $e');
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
        } else if (response.data is Map &&
            response.data['sucursales'] is List) {
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
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 16,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF16162A).withOpacity(0.95),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: Colors.white.withOpacity(0.08),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.blueAccent.withOpacity(0.15),
                        blurRadius: 30,
                        spreadRadius: 2,
                      ),
                      BoxShadow(
                        color: Colors.black.withOpacity(0.5),
                        blurRadius: 20,
                      ),
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
                                child: const Icon(
                                  Icons.store,
                                  color: Colors.blueAccent,
                                  size: 16,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Seleccionar Sucursal',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [
                                  Colors.blueAccent.withOpacity(0.3),
                                  Colors.purpleAccent.withOpacity(0.3),
                                ],
                              ),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              appVersion,
                              style: TextStyle(
                                color: Colors.lightBlueAccent,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Usa las flechas (▲/▼) y OK del control remoto:',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.4),
                          fontSize: 10,
                        ),
                      ),
                      Divider(
                        color: Colors.white.withOpacity(0.08),
                        height: 16,
                      ),
                      ConstrainedBox(
                        constraints: const BoxConstraints(maxHeight: 250),
                        child: SingleChildScrollView(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: List.generate(agencias.length, (index) {
                              final agencia = agencias[index];
                              final String nombre =
                                  agencia['nombre'] ??
                                  'Sucursal ${agencia['id']}';
                              final String idStr = agencia['id'].toString();
                              return _FocusableSucursalItem(
                                nombre: nombre,
                                isFirst: index == 0,
                                onTap: () =>
                                    Navigator.pop(dialogContext, idStr),
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
              _desired = _preparedManifest = _manifest = null;
              _preparedItems = null;
              _serverClock.clear();
              _socketClock = false;
              _prepareRetry?.cancel();
              _clearAdvertising();
            }
            setState(() => _agenciaId = selected);
            if (_startupReady) _requestClock(register: true);
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
  Future<void> _loadPlaylist() async {
    if (!mounted || _agenciaId == null || _bootstrapBusy) return;
    _bootstrapBusy = true;
    final scope = _scope;
    final start = _serverClock.tick;
    try {
      final response =
          await Dio(
            BaseOptions(
              connectTimeout: const Duration(seconds: 15),
              receiveTimeout: const Duration(seconds: 30),
            ),
          ).get(
            apiBaseUrl + '/publicidad-sync',
            queryParameters: {'agencia_id': _agenciaId},
          );
      if (!mounted || scope != _scope) return;
      final state = response.data;
      if (state is! Map || state['protocol'] != 2)
        throw StateError('Backend de publicidad pendiente de actualizar');
      if (!_socketClock)
        _serverClock.sample(state['server_time_ms'], start, _serverClock.tick);
      _receiveSchedule(state);
    } catch (e) {
      print('Advertising startup failed: $e');
      if (mounted && scope == _scope) {
        _bootstrapRetry?.cancel();
        _bootstrapRetry = Timer(
          Duration(seconds: _retrySeconds),
          _loadPlaylist,
        );
        _retrySeconds = (_retrySeconds * 2).clamp(5, 300);
      }
    } finally {
      _bootstrapBusy = false;
    }
  }

  Future<void> _prepareSchedule() async {
    if (_preparing || _desired == null || !mounted) return;
    final state = _desired!;
    final scope = _scope;
    final media = AdMediaStore(scope);
    _preparing = true;
    try {
      final keep = _playlist.map((ad) => ad['path'].toString()).toList();
      for (final raw in state['items'] as List) {
        keep.add(await media.mediaPath(Map<String, dynamic>.from(raw)));
      }
      await media.cleanup(
        keep,
        stillCurrent: () => mounted && scope == _scope && state == _desired,
      );
      final prepared = <Map<String, dynamic>>[];
      final durations = <Map<String, dynamic>>[];
      for (final raw in state['items'] as List) {
        if (!mounted || scope != _scope || state != _desired) return;
        final ad = Map<String, dynamic>.from(raw);
        final path = await media.prepare(ad);
        prepared.add({...ad, 'path': path});
        if (ad['type'] == 'video' && ad['duration_ms'] == null) {
          durations.add({
            'id': ad['id'],
            'media_version': ad['media_version'],
            'duration_ms': await media.duration(path),
          });
        }
      }
      if (!mounted || scope != _scope || state != _desired) return;
      if (durations.isNotEmpty) {
        final dio = Dio(
          BaseOptions(
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 30),
          ),
        );
        for (var i = 0; i < durations.length; i += 100) {
          await dio.post(
            apiBaseUrl + '/publicidad-sync/durations',
            data: {
              'agencia_id': int.parse(_agenciaId!),
              'items': durations.skip(i).take(100).toList(),
            },
          );
        }
        return;
      }
      if (state['ready'] != true) return;
      _preparedManifest = state;
      _preparedItems = prepared;
      socket?.emit('ad_status', {
        'revision': state['revision'],
        'status': 'ready',
      });
      _activatePrepared();
      _retrySeconds = 5;
    } catch (e) {
      print('Keeping previous advertising: $e');
      socket?.emit('ad_status', {
        'revision': state['revision'],
        'status': 'error',
      });
      if (mounted && scope == _scope) {
        _prepareRetry?.cancel();
        _prepareRetry = Timer(
          Duration(seconds: _retrySeconds),
          _prepareSchedule,
        );
        _retrySeconds = (_retrySeconds * 2).clamp(5, 300);
        if (_playlist.isEmpty)
          setState(() {
            _isLoading = false;
            _mediaFailed = true;
            _status = 'Preparando publicidad; reintentando descarga';
          });
      }
    } finally {
      _preparing = false;
      if (mounted && _preparedManifest == null && identical(state, _manifest))
        await _cleanupMedia();
      if (mounted && _desired != null && state != _desired) _prepareSchedule();
    }
  }

  void _activatePrepared() {
    if (_dialogOpen || !_foreground || !mounted) return;
    final state = _preparedManifest;
    final now = _serverClock.time;
    if (state == null || now == null || now < state['epoch_ms']) return;
    _manifest = state;
    _playlist = _preparedItems!;
    _preparedManifest = null;
    _preparedItems = null;
    _currentIndex = _target?.index ?? 0;
    if (_playlist.isEmpty) {
      _clearAdvertising();
    } else {
      _playCurrent();
    }
    final key = _cacheKey;
    final data = jsonEncode({'manifest': state, 'items': _playlist});
    SharedPreferences.getInstance().then((prefs) => prefs.setString(key, data));
    socket?.emit('ad_status', {
      'revision': state['revision'],
      'status': 'applied',
    });
    // Prune after downloads settle; retain both active and newer pending files.
    if (!_preparing) _cleanupMedia();
  }

  Future<void> _cleanupMedia() async {
    if (_preparing || _preparedItems != null) return;
    try {
      await AdMediaStore(_scope).cleanup(
        _playlist.map((a) => a['path'].toString()),
        stillCurrent: () => !_preparing && mounted,
      );
    } catch (_) {}
  }

  void _clearAdvertising() {
    _imageTimer?.cancel();
    _playGeneration++;
    final previous = _controller;
    previous?.removeListener(_videoListener);
    _controller = null;
    setState(() {
      _playlist = [];
      _localPath = null;
      _isLoading = false;
      _mediaFailed = true;
      _status = 'No hay publicidad activa';
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => previous?.dispose());
  }

  void _playCurrent() {
    if (_playlist.isEmpty || !mounted || _dialogOpen || !_foreground) return;
    final target = _target;
    if (target != null) _currentIndex = target.index;
    if (_currentIndex >= _playlist.length) _currentIndex = 0;
    final ad = _playlist[_currentIndex];
    final generation = ++_playGeneration;
    final previous = _controller;
    previous?.removeListener(_videoListener);
    _imageTimer?.cancel();
    _controller = null;
    _lastPositionMs = -1;
    _seekRecoveries = 0;
    _lastProgressTick = _serverClock.tick;
    _slotEndsAt = target == null ? 0 : _serverClock.time! + target.remainingMs;
    setState(() {
      _localPath = ad['path'];
      _mediaFailed = false;
      _isLoading = false;
      _status = 'Reproduciendo: ' + ad['name'].toString();
    });
    if (target != null || ad['type'] != 'video') {
      _imageTimer = Timer(
        Duration(
          milliseconds:
              (target?.remainingMs ??
                      ad['duration_ms'] ??
                      _imageDuration.inMilliseconds)
                  .clamp(20, 86400000),
        ),
        _nextItem,
      );
    }
    _replacePlayer(ad, previous, generation);
  }

  Future<void> _replacePlayer(
    Map<String, dynamic> ad,
    VideoPlayerController? previous,
    int generation,
  ) async {
    await WidgetsBinding.instance.endOfFrame;
    try {
      await previous?.dispose().timeout(const Duration(seconds: 5));
    } catch (_) {}
    if (!mounted || generation != _playGeneration || ad['type'] != 'video')
      return;
    final controller = VideoPlayerController.file(File(ad['path']));
    _controller = controller;
    try {
      await controller.initialize().timeout(const Duration(seconds: 20));
      if (!mounted || generation != _playGeneration) return;
      await controller.setVolume(
        1,
      ); // Android's media volume remains under remote-control control.
      final target = _target;
      if (target != null && target.index == _currentIndex)
        await controller.seekTo(Duration(milliseconds: target.offsetMs));
      controller.addListener(_videoListener);
      setState(() {});
      if (!_dialogOpen && _foreground) await controller.play();
    } catch (e) {
      print('Video playback failed: $e');
      if (mounted && generation == _playGeneration) _showFallback();
    }
  }

  void _nextItem() {
    if (_playlist.isEmpty || !mounted) return;
    final target = _target;
    if (target != null &&
        target.index == _currentIndex &&
        _serverClock.time! < _slotEndsAt - 100) {
      _showFallback();
      return;
    }
    _currentIndex = target?.index ?? ((_currentIndex + 1) % _playlist.length);
    _playCurrent();
  }

  void _videoListener() {
    final value = _controller?.value;
    if (value == null || !mounted) return;
    if (value.hasError) {
      _showFallback();
      return;
    }
    if (value.duration > Duration.zero &&
        value.position >= value.duration &&
        !value.isPlaying)
      _nextItem();
  }

  void _showFallback() {
    if (!mounted || _mediaFailed) return;
    _playGeneration++;
    final previous = _controller;
    previous?.removeListener(_videoListener);
    _controller = null;
    setState(() {
      _mediaFailed = true;
      _isLoading = false;
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => previous?.dispose());
    _imageTimer?.cancel();
    _imageTimer = Timer(
      Duration(
        milliseconds: (_target?.remainingMs ?? 10000).clamp(200, 86400000),
      ),
      _nextItem,
    );
    socket?.emit('ad_status', {
      'revision': _manifest?['revision'],
      'status': 'error',
    });
  }

  void _syncPlayback() {
    if (!mounted || _dialogOpen || !_foreground) return;
    _activatePrepared();
    final target = _target;
    if (target != null && target.index != _currentIndex) {
      _currentIndex = target.index;
      _playCurrent();
      return;
    }
    final controller = _controller;
    if (controller == null || _mediaFailed) return;
    final value = controller.value;
    if (value.position.inMilliseconds != _lastPositionMs) {
      _lastPositionMs = value.position.inMilliseconds;
      _lastProgressTick = _serverClock.tick;
    } else if (_serverClock.tick - _lastProgressTick > 15000) {
      _showFallback();
      return;
    }
    if (!value.isInitialized || _aligning || target == null) return;
    _alignVideo(controller, target);
  }

  Future<void> _alignVideo(
    VideoPlayerController controller,
    AdPosition target,
  ) async {
    _aligning = true;
    try {
      final drift = target.offsetMs - controller.value.position.inMilliseconds;
      if (drift.abs() > 2000) {
        if (++_seekRecoveries > 2) {
          _showFallback();
          return;
        }
        await controller.seekTo(Duration(milliseconds: target.offsetMs));
      }
      final speed = drift.abs() < 200
          ? 1.0
          : drift > 0
          ? 1.04
          : 0.96;
      if (controller.value.playbackSpeed != speed)
        await controller.setPlaybackSpeed(speed);
      if (!controller.value.isPlaying &&
          !_dialogOpen &&
          _foreground &&
          identical(controller, _controller))
        await controller.play();
    } catch (_) {
    } finally {
      _aligning = false;
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
            now.difference(_lastEnterPress!) <
                const Duration(milliseconds: 500)) {
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
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 16,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF16162A).withOpacity(0.95),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: Colors.white.withOpacity(0.08),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.blueAccent.withOpacity(0.15),
                        blurRadius: 30,
                        spreadRadius: 2,
                      ),
                      BoxShadow(
                        color: Colors.black.withOpacity(0.5),
                        blurRadius: 20,
                      ),
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
                                child: const Icon(
                                  Icons.settings,
                                  color: Colors.blueAccent,
                                  size: 16,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Configuración',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [
                                  Colors.blueAccent.withOpacity(0.3),
                                  Colors.purpleAccent.withOpacity(0.3),
                                ],
                              ),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              appVersion,
                              style: TextStyle(
                                color: Colors.lightBlueAccent,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Sucursal: ${_agenciaId ?? "—"}  •  ${_playlist.length} items',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.4),
                          fontSize: 10,
                        ),
                      ),
                      Divider(
                        color: Colors.white.withOpacity(0.08),
                        height: 16,
                      ),

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
                              setState(() {
                                _rotationTurns = tempRotation;
                              });
                              SharedPreferences.getInstance().then(
                                (p) => p.setInt('rotation_turns', tempRotation),
                              );
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
                                setState(() {
                                  _rotationTurns = tempRotation;
                                });
                                SharedPreferences.getInstance().then(
                                  (p) =>
                                      p.setInt('rotation_turns', tempRotation),
                                );
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 150),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 10,
                                ),
                                decoration: BoxDecoration(
                                  color: hasFocus
                                      ? Colors.amberAccent.withOpacity(0.15)
                                      : Colors.white.withOpacity(0.04),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: hasFocus
                                        ? Colors.amberAccent.withOpacity(0.5)
                                        : Colors.transparent,
                                  ),
                                ),
                                child: Row(
                                  children: [
                                    Icon(
                                      Icons.screen_rotation,
                                      color: Colors.amberAccent,
                                      size: 18,
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          const Text(
                                            'Rotación',
                                            style: TextStyle(
                                              color: Colors.white,
                                              fontSize: 13,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                          Text(
                                            _getRotationText(tempRotation),
                                            style: TextStyle(
                                              color: Colors.white.withOpacity(
                                                0.5,
                                              ),
                                              fontSize: 10,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    // Indicador visual de grados
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 3,
                                      ),
                                      decoration: BoxDecoration(
                                        color: Colors.amberAccent.withOpacity(
                                          0.2,
                                        ),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        '${tempRotation * 90}°',
                                        style: const TextStyle(
                                          color: Colors.amberAccent,
                                          fontSize: 12,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
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
                        icon: Icons.store,
                        color: Colors.blueAccent,
                        label: 'Cambiar Sucursal',
                        onTap: () {
                          Navigator.pop(dialogContext);
                          Future.delayed(
                            const Duration(milliseconds: 300),
                            () async {
                              if (!mounted) return;
                              await _selectAgencia();
                              if (mounted) {
                                _requestClock(register: true);
                                if (!(socket?.connected ?? false))
                                  _loadPlaylist();
                              }
                            },
                          );
                        },
                      ),
                      // Reiniciar
                      _CompactMenuItem(
                        icon: Icons.refresh,
                        color: Colors.greenAccent,
                        label: 'Reiniciar App',
                        onTap: () {
                          Navigator.pop(dialogContext);
                          _initApp();
                        },
                      ),
                      // Salir
                      _CompactMenuItem(
                        icon: Icons.power_settings_new,
                        color: Colors.redAccent,
                        label: 'Salir',
                        onTap: () {
                          Navigator.pop(dialogContext);
                          SystemNavigator.pop();
                          exit(0);
                        },
                      ),

                      const SizedBox(height: 6),
                      // Info compacta
                      Text(
                        'API: $apiBaseUrl',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.2),
                          fontSize: 8,
                        ),
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

  Widget _fallbackImage() =>
      Image.asset('image/publi.png', fit: BoxFit.contain, cacheWidth: 1080);

  Widget _buildMedia() {
    final controller = _controller;
    if (!_mediaFailed && controller != null && controller.value.isInitialized) {
      return SizedBox.expand(
        child: FittedBox(
          fit: BoxFit.cover,
          child: SizedBox(
            width: controller.value.size.width,
            height: controller.value.size.height,
            child: VideoPlayer(controller),
          ),
        ),
      );
    }
    if (!_mediaFailed &&
        _localPath != null &&
        _playlist.isNotEmpty &&
        _playlist[_currentIndex]['type'] != 'video') {
      return Image.file(
        File(_localPath!),
        fit: BoxFit.cover,
        cacheWidth: 1080,
        errorBuilder: (_, error, stack) {
          final generation = _playGeneration;
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (generation == _playGeneration) _showFallback();
          });
          return _fallbackImage();
        },
      );
    }
    return _fallbackImage();
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
                    duration: Duration.zero,
                    switchInCurve: Curves.easeInOutCubic,
                    switchOutCurve: Curves.easeInOutCubic,
                    transitionBuilder:
                        (Widget child, Animation<double> animation) {
                          final fadeAnimation = CurvedAnimation(
                            parent: animation,
                            curve: Curves.easeInOutCubic,
                          );
                          final scaleAnimation = Tween<double>(
                            begin: 0.96,
                            end: 1.0,
                          ).animate(fadeAnimation);
                          return FadeTransition(
                            opacity: fadeAnimation,
                            child: ScaleTransition(
                              scale: scaleAnimation,
                              child: child,
                            ),
                          );
                        },
                    child: Container(
                      key: ValueKey<String>(
                        _localPath ?? 'empty_$_currentIndex',
                      ),
                      color: Colors.black,
                      width: double.infinity,
                      height: double.infinity,
                      child: _buildMedia(),
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
                        Text(
                          _status,
                          style: const TextStyle(color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                ),
              if (!_isLoading)
                Positioned(
                  bottom: 10,
                  right: 10,
                  child: Text(
                    _status,
                    style: const TextStyle(color: Colors.white24, fontSize: 10),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    _clockTimer?.cancel();
    _prepareRetry?.cancel();
    _bootstrapRetry?.cancel();
    _clockRetry?.cancel();
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
            color: _isFocused
                ? const Color(0xFF1E88E5).withOpacity(0.3)
                : Colors.white.withOpacity(0.04),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: _isFocused ? Colors.lightBlueAccent : Colors.transparent,
              width: 1,
            ),
          ),
          child: Row(
            children: [
              Icon(
                _isFocused
                    ? Icons.radio_button_checked
                    : Icons.radio_button_unchecked,
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
                    fontWeight: _isFocused
                        ? FontWeight.bold
                        : FontWeight.normal,
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
            color: _isFocused
                ? Colors.white.withOpacity(0.15)
                : Colors.transparent,
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
                    fontWeight: _isFocused
                        ? FontWeight.bold
                        : FontWeight.normal,
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
                border: Border.all(
                  color: hasFocus ? color.withOpacity(0.4) : Colors.transparent,
                ),
              ),
              child: Row(
                children: [
                  Icon(icon, color: color, size: 16),
                  const SizedBox(width: 10),
                  Text(
                    label,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: hasFocus
                          ? FontWeight.w600
                          : FontWeight.normal,
                    ),
                  ),
                  const Spacer(),
                  if (hasFocus)
                    Icon(
                      Icons.chevron_right,
                      color: color.withOpacity(0.6),
                      size: 16,
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
