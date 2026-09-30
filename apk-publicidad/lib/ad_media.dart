import 'dart:convert';
import 'dart:io';
import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:flutter/services.dart';
import 'package:path_provider/path_provider.dart';

class AdMediaStore {
  final String scope;
  final Future<Directory> Function()? directoryProvider;
  AdMediaStore(this.scope, {this.directoryProvider});
  static const channel = MethodChannel('com.santidad.app/autostart');
  final Set<String> _verified = {};
  Future<Directory> get directory async {
    if (directoryProvider != null) return directoryProvider!();
    final root = await getApplicationDocumentsDirectory();
    final dir = Directory(
      root.path +
          '/advertising/' +
          sha256.convert(utf8.encode(scope)).toString(),
    );
    return dir.create(recursive: true);
  }

  Future<String> mediaPath(Map<String, dynamic> ad) async {
    final dir = await directory;
    final extension = ad['file_id']
        .toString()
        .split('.')
        .last
        .replaceAll(RegExp('[^a-zA-Z0-9]'), '');
    final key = sha256.convert(
      utf8.encode(ad['id'].toString() + '|' + ad['media_version'].toString()),
    );
    return dir.path + '/' + key.toString() + '.' + extension;
  }

  Future<String> prepare(Map<String, dynamic> ad) async {
    final file = File(await mediaPath(ad));
    final expectedSize = (ad['size_bytes'] as num?)?.toInt();
    final expectedHash = ad['sha256'] as String?;
    Future<bool> valid(File candidate) async {
      if (!await candidate.exists()) return false;
      final stat = await candidate.stat();
      if (stat.size == 0 || (expectedSize != null && stat.size != expectedSize))
        return false;
      final cacheKey =
          candidate.path +
          '|' +
          stat.size.toString() +
          '|' +
          stat.modified.toString() +
          '|' +
          (expectedHash ?? '');
      if (_verified.contains(cacheKey)) return true;
      if (expectedHash != null &&
          (await sha256.bind(candidate.openRead()).first).toString() !=
              expectedHash)
        return false;
      _verified.add(cacheKey);
      return true;
    }

    if (await valid(file)) return file.path;
    if (Platform.isAndroid) {
      final free = await channel.invokeMethod<int>('freeMediaBytes');
      if (free != null &&
          free < (expectedSize ?? 200 * 1024 * 1024) + 64 * 1024 * 1024) {
        throw StateError(
          'Espacio insuficiente; se conserva la publicidad anterior',
        );
      }
    }
    final partial = File(file.path + '.part');
    try {
      final dio = Dio(
        BaseOptions(
          connectTimeout: const Duration(seconds: 20),
          receiveTimeout: const Duration(minutes: 2),
        ),
      );
      final response = await dio.download(ad['url'].toString(), partial.path);
      final length = int.tryParse(
        response.headers.value('content-length') ?? '',
      );
      if (length != null && await partial.length() != length)
        throw StateError('Descarga incompleta');
      if (!await valid(partial))
        throw StateError('Archivo incompleto o checksum incorrecto');
      await partial.rename(file.path);
      return file.path;
    } catch (_) {
      if (await partial.exists()) await partial.delete();
      rethrow;
    }
  }

  Future<int> duration(String path) async {
    final ms = await channel.invokeMethod<int>('mediaDuration', {'path': path});
    if (ms == null || ms <= 0)
      throw StateError('No se pudo leer la duración del video');
    return ms;
  }

  Future<void> cleanup(
    Iterable<String> active, {
    bool Function()? stillCurrent,
  }) async {
    final keep = active.toSet();
    final dir = await directory;
    await for (final entry in dir.list()) {
      if (stillCurrent != null && !stillCurrent()) return;
      if (entry is File && !keep.contains(entry.path)) {
        try {
          await entry.delete();
        } catch (_) {}
      }
    }
  }
}
