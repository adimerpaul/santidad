import 'dart:async';
import 'dart:io' show File, FileSystemEntity; // Solo para móvil
import 'package:flutter/foundation.dart' show kIsWeb, listEquals;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:socket_io_client/socket_io_client.dart' as IO;
import 'package:permission_handler/permission_handler.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

const String _env = String.fromEnvironment('ENV', defaultValue: 'development');

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load(fileName: _env == 'production' ? '.env.production' : '.env');
  if (!kIsWeb) {
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    WakelockPlus.enable();
  }
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

class _PublicidadPlayerState extends State<PublicidadPlayer> {
  final String apiBaseUrl = dotenv.env['API_BASE_URL']!;
  final String socketUrl = dotenv.env['SOCKET_URL']!;

  VideoPlayerController? _controller;
  List<dynamic> _playlist = [];
  int _currentIndex = 0;
  String? _localPath;
  String? _agenciaId;
  bool _isLoading = true;
  String _status = 'Iniciando...';
  late IO.Socket socket;
  Timer? _imageTimer;

  @override
  void initState() {
    super.initState();
    _initApp();
  }

  Future<void> _initApp() async {
    await _requestPermissions();
    await _loadSavedState(); // Cargar agencia guardada
    if (_agenciaId == null) {
      await _selectAgencia();
    }
    _initSocket();
    _checkPublicidad();
  }

  Future<void> _loadSavedState() async {
    final prefs = await SharedPreferences.getInstance();
    _agenciaId = prefs.getString('agencia_id');
    // No cargamos playlist offline por ahora para simplificar, se cargará al conectar
  }

  Future<void> _selectAgencia() async {
    setState(() => _status = 'Cargando sucursales...');
    try {
      final dio = Dio();
      final response = await dio.get('$apiBaseUrl/sucursales');
      if (response.statusCode == 200) {
        final List agencias = response.data;
        final selected = await showDialog<String>(
          context: context,
          barrierDismissible: false,
          builder: (context) => AlertDialog(
            title: const Text('Seleccionar Sucursal'),
            content: SizedBox(
              width: double.maxFinite,
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: agencias.length,
                itemBuilder: (context, index) {
                  return ListTile(
                    title: Text(agencias[index]['nombre']),
                    onTap: () => Navigator.pop(context, agencias[index]['id'].toString()),
                  );
                },
              ),
            ),
          ),
        );

        if (selected != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('agencia_id', selected);
          setState(() => _agenciaId = selected);
        }
      }
    } catch (e) {
      print('Error loading agencias: $e');
      setState(() => _status = 'Error al cargar sucursales');
      await Future.delayed(const Duration(seconds: 5));
      return _selectAgencia();
    }
  }

  Future<void> _requestPermissions() async {
    await [
      Permission.storage,
      Permission.manageExternalStorage,
    ].request();
  }

  void _initSocket() {
    socket = IO.io(socketUrl, IO.OptionBuilder()
      .setTransports(['websocket'])
      .disableAutoConnect()
      .build());

    socket.connect();

    socket.onConnect((_) => print('Conectado a Socket Server'));
    
    socket.on('new_publicidad', (data) {
      print('Nueva publicidad recibida por socket: $data');
      if (data['agencia_id'] == null || data['agencia_id'].toString() == _agenciaId) {
        _checkPublicidad();
      }
    });
  }

  Future<void> _checkPublicidad() async {
    setState(() => _status = 'Comprobando actualizaciones...');
    try {
      final dio = Dio();
      final response = await dio.get(
        '$apiBaseUrl/publicidad-actual',
        queryParameters: {'agencia_id': _agenciaId},
      );
      
      if (response.statusCode == 200) {
        final data = response.data;
        
        if (data is Map && data['message'] != null) {
          setState(() {
            _isLoading = false;
            _status = data['message'];
            _playlist = [];
            _localPath = null;
          });
          _controller?.pause();
          return;
        }

        if (data is List) {
          _updatePlaylist(data);
        }
      }
    } catch (e) {
      print('Error checking publicidad: $e');
      setState(() => _status = 'Esperando publicidad...');
      Future.delayed(const Duration(seconds: 30), _checkPublicidad);
    }
  }

  void _updatePlaylist(List<dynamic> newAds) async {
    List<String> currentIds = _playlist.map((e) => e['file_id'].toString()).toList();
    List<String> newIds = newAds.map((e) => e['file_id'].toString()).toList();
    
    if (listEquals(currentIds, newIds) && _playlist.isNotEmpty) {
      setState(() {
        _isLoading = false;
        _status = 'Playlist al día';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _status = 'Actualizando playlist...';
    });

    List<Map<String, dynamic>> updatedPlaylist = [];
    final directory = await getApplicationDocumentsDirectory();

    // 1. Descargar nuevos archivos y construir nueva playlist
    for (var ad in newAds) {
      final String fileId = ad['file_id'];
      final String type = ad['type'];
      final String url = ad['url'];
      final String extension = type == 'video' ? 'mp4' : 'jpg';
      final String filePath = kIsWeb ? url : '${directory.path}/publicidad_$fileId.$extension';

      if (!kIsWeb && !await File(filePath).exists()) {
        setState(() => _status = 'Descargando ${ad['name']}...');
        try {
          await Dio().download(url, filePath);
        } catch (e) {
          print('Error descargando ad: $e');
          continue; 
        }
      }
      
      updatedPlaylist.add({
        'file_id': fileId,
        'path': filePath,
        'type': type,
        'name': ad['name']
      });
    }

    // 2. Limpieza de archivos locales que ya no están en la playlist
    if (!kIsWeb) {
      try {
        final List<FileSystemEntity> files = directory.listSync();
        final Set<String> activeFileNames = updatedPlaylist
            .map((e) => 'publicidad_${e['file_id']}.${e['type'] == 'video' ? 'mp4' : 'jpg'}')
            .toSet();

        for (var file in files) {
          final String fileName = file.path.split('/').last;
          if (fileName.startsWith('publicidad_') && !activeFileNames.contains(fileName)) {
            print('Eliminando archivo antiguo: $fileName');
            await file.delete();
          }
        }
      } catch (e) {
        print('Error limpiando archivos: $e');
      }
    }

    // 3. Actualizar estado y reiniciar si es necesario
    if (updatedPlaylist.isNotEmpty) {
      // Verificar si el video actual sigue existiendo en la nueva playlist
      bool currentStillExists = false;
      if (_playlist.isNotEmpty) {
        final currentFileId = _playlist[_currentIndex]['file_id'];
        for (int i = 0; i < updatedPlaylist.length; i++) {
          if (updatedPlaylist[i]['file_id'] == currentFileId) {
            _currentIndex = i; // Actualizar índice para mantener posición
            currentStillExists = true;
            break;
          }
        }
      }

      setState(() {
        _playlist = updatedPlaylist;
        _isLoading = false;
      });

      if (!currentStillExists) {
        _currentIndex = 0;
        _playCurrent();
      } else {
        setState(() => _status = 'Playlist actualizada');
      }
    } else {
      setState(() {
        _isLoading = false;
        _status = 'Sin contenido válido';
      });
    }
  }

  void _playCurrent() {
    if (_playlist.isEmpty) return;
    final currentAd = _playlist[_currentIndex];
    
    _imageTimer?.cancel();
    
    // Si el siguiente NO es video, liberamos el controlador actual para que no tape la imagen
    if (currentAd['type'] != 'video' && _controller != null) {
      _controller!.dispose();
      _controller = null;
    }
    
    setState(() {
      _localPath = currentAd['path'];
      _status = 'Reproduciendo: ${currentAd['name']}';
    });

    if (currentAd['type'] == 'video') {
      _playVideo(currentAd['path']);
    } else {
      _displayImage(currentAd['path']);
    }
  }

  void _nextItem() {
    if (_playlist.isEmpty) return;
    if (!mounted) return;
    setState(() {
      _currentIndex = (_currentIndex + 1) % _playlist.length;
    });
    _playCurrent();
  }

  void _playVideo(String path) {
    if (_controller != null) {
      _controller!.dispose();
      _controller = null;
    }

    if (kIsWeb) {
      _controller = VideoPlayerController.networkUrl(Uri.parse(path));
    } else {
      _controller = VideoPlayerController.file(File(path));
    }

    _controller!.initialize().then((_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
      });
      _controller!.play();
      _controller!.addListener(() {
        final bool isFinished = _controller!.value.position >= _controller!.value.duration;
        if (isFinished && _controller!.value.isPlaying == false) {
           // Solo pasamos al siguiente si ya no está reproduciendo y llegó al final
           _controller!.removeListener(() {});
           _nextItem();
        }
      });
    });
  }

  void _displayImage(String path) {
    setState(() {
      _isLoading = false;
    });
    // Mostrar imagen por 10 segundos y pasar al siguiente
    _imageTimer = Timer(const Duration(seconds: 10), _nextItem);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          Center(
            child: _controller != null && _controller!.value.isInitialized
                ? AspectRatio(
                    aspectRatio: _controller!.value.aspectRatio,
                    child: VideoPlayer(_controller!),
                  )
                : _localPath != null
                    ? (kIsWeb 
                        ? Image.network(_localPath!, fit: BoxFit.contain)
                        : Image.file(File(_localPath!), fit: BoxFit.contain))
                    : const SizedBox.shrink(),
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
    );
  }

  @override
  void dispose() {
    socket.dispose();
    _controller?.dispose();
    _imageTimer?.cancel();
    super.dispose();
  }
}
