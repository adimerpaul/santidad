import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../data/models/pedido.dart';

/// Historial local de pedidos enviados por WhatsApp.
class PedidosViewModel extends ChangeNotifier {
  List<Pedido> pedidos = [];
  bool cargando = false;

  static const _key = 'pedidos_local';
  static const _seqKey = 'pedido_seq';

  Future<void> cargar() async {
    cargando = true;
    notifyListeners();
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_key);
      if (raw != null) {
        pedidos = (jsonDecode(raw) as List)
            .map((p) => Pedido.fromJson(Map<String, dynamic>.from(p)))
            .toList();
      }
    } catch (_) {
      pedidos = [];
    } finally {
      cargando = false;
      notifyListeners();
    }
  }

  /// Correlativo local: PED-0001, PED-0002, …
  Future<String> siguienteCodigo() async {
    final prefs = await SharedPreferences.getInstance();
    final n = (prefs.getInt(_seqKey) ?? 0) + 1;
    await prefs.setInt(_seqKey, n);
    return 'PED-${n.toString().padLeft(4, '0')}';
  }

  Future<void> guardar(Pedido pedido) async {
    pedidos = [pedido, ...pedidos];
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _key,
      jsonEncode(pedidos.map((p) => p.toJson()).toList()),
    );
  }
}
