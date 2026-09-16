import 'dart:convert';

import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:http/http.dart' as http;

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, [this.statusCode]);

  @override
  String toString() => message;
}

/// Cliente HTTP único de la app. La URL base viene del archivo .env (API_URL).
class ApiClient {
  String? _token;

  String get baseUrl =>
      dotenv.env['API_URL'] ?? 'http://10.0.2.2:8000/api';

  bool get hasToken => _token != null;

  void setToken(String? token) => _token = token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<dynamic> get(String path, {Map<String, String>? query}) =>
      _send('GET', path, query: query);

  Future<dynamic> post(String path, {Map<String, dynamic>? body}) =>
      _send('POST', path, body: body);

  Future<dynamic> _send(
    String method,
    String path, {
    Map<String, String>? query,
    Map<String, dynamic>? body,
  }) async {
    var uri = Uri.parse('$baseUrl$path');
    if (query != null && query.isNotEmpty) {
      uri = uri.replace(queryParameters: {...uri.queryParameters, ...query});
    }

    http.Response res;
    try {
      if (method == 'GET') {
        res = await http
            .get(uri, headers: _headers)
            .timeout(const Duration(seconds: 20));
      } else {
        res = await http
            .post(uri, headers: _headers, body: jsonEncode(body ?? {}))
            .timeout(const Duration(seconds: 20));
      }
    } catch (_) {
      throw ApiException('No se pudo conectar con el servidor');
    }

    dynamic data;
    if (res.bodyBytes.isNotEmpty) {
      try {
        data = jsonDecode(utf8.decode(res.bodyBytes));
      } catch (_) {
        data = null;
      }
    }

    if (res.statusCode >= 200 && res.statusCode < 300) return data;

    final msg = (data is Map && data['message'] != null)
        ? data['message'].toString()
        : 'Error ${res.statusCode}';
    throw ApiException(msg, res.statusCode);
  }
}
