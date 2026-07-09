import 'package:flutter/foundation.dart';

import '../data/models/cart_item.dart';
import '../data/models/pedido.dart';
import '../data/models/product.dart';
import '../data/models/sucursal.dart';

class CarritoViewModel extends ChangeNotifier {
  final List<CartItem> items = [];
  int? agenciaId;
  String prioridad = 'NORMAL';
  String observacion = '';

  int get totalUnidades => items.fold(0, (t, i) => t + i.qty);
  double get total => items.fold(0.0, (t, i) => t + i.subtotal);
  bool get vacio => items.isEmpty;

  void setAgencia(int? id) {
    agenciaId = id;
    notifyListeners();
  }

  void setPrioridad(String p) {
    prioridad = p;
    notifyListeners();
  }

  void setObservacion(String obs) {
    observacion = obs;
  }

  CartItem? _buscar(int productId) {
    for (final i in items) {
      if (i.product.id == productId) return i;
    }
    return null;
  }

  void agregar(Product p) {
    final existente = _buscar(p.id);
    if (existente != null) {
      existente.qty++;
    } else {
      items.add(CartItem(product: p));
    }
    notifyListeners();
  }

  void cambiarCantidad(int productId, int delta) {
    final item = _buscar(productId);
    if (item == null) return;
    item.qty += delta;
    if (item.qty <= 0) items.remove(item);
    notifyListeners();
  }

  void limpiar() {
    items.clear();
    prioridad = 'NORMAL';
    observacion = '';
    notifyListeners();
  }

  /// Arma el pedido con el contenido actual del carrito.
  Pedido construirPedido({
    required Sucursal sucursal,
    required String codigo,
    required String whatsappDestino,
  }) {
    final ahora = DateTime.now();
    String dos(int n) => n.toString().padLeft(2, '0');
    return Pedido(
      id: ahora.millisecondsSinceEpoch,
      codigo: codigo,
      sucursal: sucursal.nombre,
      fecha:
          '${dos(ahora.day)}/${dos(ahora.month)}/${ahora.year} ${dos(ahora.hour)}:${dos(ahora.minute)}',
      items: totalUnidades,
      total: total,
      estado: 'ENVIADO',
      prioridad: prioridad,
      observacion: observacion.isEmpty ? null : observacion,
      whatsapp: whatsappDestino,
      detalles: items
          .map((i) => PedidoLinea(
                producto: i.product.nombre,
                cantidad: i.qty,
                precio: i.product.precio,
              ))
          .toList(),
    );
  }
}
