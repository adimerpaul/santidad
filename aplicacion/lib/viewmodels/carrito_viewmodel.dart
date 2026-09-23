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

  void agregar(Product p, {int cantidad = 1}) {
    final qty = cantidad < 1 ? 1 : cantidad;
    final existente = _buscar(p.id);
    if (existente != null) {
      existente.qty += qty;
    } else {
      items.add(CartItem(product: p, qty: qty));
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

  /// Quita del carrito los productos indicados (p. ej. los que quedaron
  /// sin stock en la sucursal elegida).
  void quitarProductos(Iterable<int> productIds) {
    final ids = productIds.toSet();
    items.removeWhere((i) => ids.contains(i.product.id));
    notifyListeners();
  }

  /// Stock del producto en la sucursal seleccionada según los datos que
  /// trae el catálogo. Null si aún no se eligió sucursal.
  int? stockEnSucursal(Product p) {
    final id = agenciaId;
    if (id == null) return null;
    for (final s in p.stocks) {
      if (s.agenciaId == id) return s.cantidad;
    }
    return 0;
  }

  /// Ítems cuya cantidad pedida supera el stock de la sucursal seleccionada.
  List<CartItem> get itemsSinStockEnSucursal {
    if (agenciaId == null) return const [];
    return items
        .where((i) => (stockEnSucursal(i.product) ?? 0) < i.qty)
        .toList();
  }

  /// Arma el pedido con el contenido actual del carrito.
  Pedido construirPedido({
    required Sucursal sucursal,
    required String codigo,
    required String whatsappDestino,
    Map<int, double> preciosConfirmados = const {},
  }) {
    final ahora = DateTime.now();
    String dos(int n) => n.toString().padLeft(2, '0');
    final detallesPedido = items
        .map(
          (i) => PedidoLinea(
            producto: i.product.nombre,
            cantidad: i.qty,
            precio: preciosConfirmados[i.product.id] ?? i.product.precio,
          ),
        )
        .toList();
    return Pedido(
      id: ahora.millisecondsSinceEpoch,
      codigo: codigo,
      sucursal: sucursal.nombre,
      fecha:
          '${dos(ahora.day)}/${dos(ahora.month)}/${ahora.year} ${dos(ahora.hour)}:${dos(ahora.minute)}',
      items: totalUnidades,
      total: detallesPedido.fold<double>(
        0,
        (suma, item) => suma + item.subtotal,
      ),
      estado: 'ENVIADO',
      prioridad: prioridad,
      observacion: observacion.isEmpty ? null : observacion,
      whatsapp: whatsappDestino,
      detalles: detallesPedido,
    );
  }
}
