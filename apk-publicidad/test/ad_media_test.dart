import 'dart:io';
import 'package:crypto/crypto.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:app_movil_publi/ad_media.dart';

void main() {
  // Exercise real localhost streaming, without Flutter widget HTTP overrides.
  HttpOverrides.global = null;
  test(
    'verified cache avoids downloads; corrupt and truncated files never become active',
    () async {
      final root = await Directory.systemTemp.createTemp('tv-media-test-');
      final payload = List<int>.filled(1024 * 1024, 7);
      var requests = 0;
      final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      server.listen((request) async {
        requests++;
        if (request.uri.path == '/broken') {
          request.response.contentLength = 2;
          request.response.add([1, 2]);
          await request.response.close();
        } else {
          request.response.contentLength = payload.length;
          request.response.add(payload);
          await request.response.close();
        }
      });
      final store = AdMediaStore('test', directoryProvider: () async => root);
      final ad = <String, dynamic>{
        'id': 1,
        'file_id': 'a.mp4',
        'media_version': 'v1',
        'url': 'http://127.0.0.1:' + server.port.toString() + '/ok',
        'sha256': sha256.convert(payload).toString(),
        'size_bytes': payload.length,
      };
      try {
        final saved = await store.prepare(ad);
        expect(await File(saved).length(), payload.length);
        await store.prepare(ad);
        expect(requests, 1);
        await File(saved).writeAsBytes([1]);
        await store.prepare(ad);
        expect(requests, 2);
        await expectLater(
          store.prepare({
            ...ad,
            'id': 2,
            'url': 'http://127.0.0.1:' + server.port.toString() + '/broken',
          }),
          throwsA(anything),
        );
        expect(await File(saved).length(), payload.length);
        expect(
          await root.list().where((f) => f.path.endsWith('.part')).length,
          0,
        );
      } finally {
        await server.close(force: true);
        await root.delete(recursive: true);
      }
    },
  );
  test(
    '200 MiB video streams to disk and is reused without another download',
    () async {
      final root = await Directory.systemTemp.createTemp('tv-large-media-');
      final chunk = List<int>.filled(1024 * 1024, 7);
      var requests = 0;
      final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      server.listen((request) async {
        requests++;
        request.response.contentLength = chunk.length * 200;
        for (var i = 0; i < 200; i++) {
          request.response.add(chunk);
          await request.response.flush();
        }
        await request.response.close();
      });
      final store = AdMediaStore('large', directoryProvider: () async => root);
      final ad = <String, dynamic>{
        'id': 200,
        'file_id': 'large.mp4',
        'media_version': 'v1',
        'url': 'http://127.0.0.1:${server.port}/large',
        'size_bytes': chunk.length * 200,
      };
      try {
        final path = await store.prepare(ad);
        expect(await File(path).length(), chunk.length * 200);
        await store.prepare(ad);
        expect(requests, 1);
      } finally {
        await server.close(force: true);
        await root.delete(recursive: true);
      }
    },
    timeout: const Timeout(Duration(minutes: 2)),
  );
}
