import 'product.dart';

/// Promoción vigente en la app, armada a partir de los productos en oferta
/// que el backend marca con el nombre de su promoción.
class Promocion {
  final String nombre;
  final int porcentaje;
  final List<Product> productos;

  const Promocion({
    required this.nombre,
    required this.porcentaje,
    required this.productos,
  });

  /// Nombre que usa el backend cuando el descuento es propio del producto
  /// y no de una promoción configurada.
  static const _descuentoPropio = 'Descuento permanente del producto';

  /// Agrupa los productos por promoción, ordenadas por mayor descuento.
  static List<Promocion> agrupar(List<Product> productos) {
    final grupos = <String, List<Product>>{};
    for (final p in productos) {
      final nombre = p.promocion?.trim() ?? '';
      if (nombre.isEmpty || nombre == _descuentoPropio) continue;
      grupos.putIfAbsent(nombre, () => []).add(p);
    }

    return grupos.entries
        .map(
          (e) => Promocion(
            nombre: e.key,
            porcentaje: e.value
                .map((p) => p.descuento)
                .fold(0, (a, b) => a > b ? a : b),
            productos: e.value,
          ),
        )
        .toList()
      ..sort((a, b) => b.porcentaje.compareTo(a.porcentaje));
  }
}
