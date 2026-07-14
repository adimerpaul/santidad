import 'package:flutter/foundation.dart';

import '../data/models/categoria.dart';
import '../data/models/product.dart';
import '../data/models/sucursal.dart';
import '../data/repositories/catalogo_repository.dart';

/// Filtro activo del listado de productos.
class FiltroCatalogo {
  static const todo = 'TODO';
  static const ofertas = 'OFERTAS';
}

class CatalogoViewModel extends ChangeNotifier {
  final CatalogoRepository repo;

  CatalogoViewModel(this.repo);

  // --- Config base ---
  List<Categoria> categorias = [];
  List<Sucursal> sucursales = [];
  int umbralStockBajo = 20;
  List<Product> ofertas = [];
  List<String> carrusel = [];
  bool cargandoInicio = false;
  String? errorInicio;

  // --- Listado de productos ---
  List<Product> productos = [];
  bool cargandoProductos = false;
  bool cargandoMas = false;
  String? errorProductos;
  String busqueda = '';
  String filtro = FiltroCatalogo.todo; // TODO | OFERTAS | id de categoría
  int _pagina = 1;
  int _ultimaPagina = 1;

  bool get hayMasProductos => _pagina < _ultimaPagina;

  int? get categoriaSeleccionada =>
      int.tryParse(filtro); // null si es TODO/OFERTAS

  Future<void> cargarInicio() async {
    cargandoInicio = true;
    errorInicio = null;
    notifyListeners();
    try {
      final config = await repo.config();
      categorias = config.categorias;
      sucursales = config.sucursales;
      umbralStockBajo = config.umbralStockBajo;
      final pagOfertas = await repo.productos(ofertas: true, perPage: 10);
      ofertas = pagOfertas.items;
      try {
        carrusel = await repo.carousels();
      } catch (_) {
        // el carrusel es decorativo: si falla, el inicio sigue funcionando
      }
    } catch (e) {
      errorInicio = e.toString();
    } finally {
      cargandoInicio = false;
      notifyListeners();
    }
  }

  void setFiltro(String nuevo) {
    if (filtro == nuevo) return;
    filtro = nuevo;
    cargarProductos();
  }

  void setBusqueda(String q) {
    busqueda = q;
    cargarProductos();
  }

  Future<void> cargarProductos() async {
    cargandoProductos = true;
    errorProductos = null;
    _pagina = 1;
    notifyListeners();
    try {
      final pag = await _fetch(1);
      productos = pag.items;
      _ultimaPagina = pag.ultimaPagina;
    } catch (e) {
      errorProductos = e.toString();
      productos = [];
    } finally {
      cargandoProductos = false;
      notifyListeners();
    }
  }

  Future<void> cargarMas() async {
    if (cargandoMas || !hayMasProductos) return;
    cargandoMas = true;
    notifyListeners();
    try {
      final pag = await _fetch(_pagina + 1);
      _pagina = pag.paginaActual;
      _ultimaPagina = pag.ultimaPagina;
      productos = [...productos, ...pag.items];
    } catch (_) {
      // se puede reintentar con el siguiente scroll
    } finally {
      cargandoMas = false;
      notifyListeners();
    }
  }

  Future<PaginaProductos> _fetch(int page) => repo.productos(
        search: busqueda.trim(),
        categoryId: categoriaSeleccionada ?? 0,
        ofertas: filtro == FiltroCatalogo.ofertas,
        page: page,
      );

  /// Detalle completo de un producto con sus relacionados.
  /// El backend calcula los relacionados por principio activo (misma dosis
  /// y composición similar), igual que la tienda pública; si el endpoint
  /// devuelve vacío se respeta (no hay productos con composición similar).
  /// Solo si el endpoint falla (backend sin actualizar) se hace una búsqueda
  /// local por composición/nombre como último recurso.
  Future<DetalleProducto> detalleConRelacionados(Product p) async {
    try {
      final det = await repo.detalle(p.id);
      return det;
    } catch (_) {
      // el endpoint puede no existir aún en el servidor
    }

    var relacionados = <Product>[];
    try {
      final pag = await repo.productos(
        search: _terminoRelacionado(p),
        perPage: 12,
      );
      relacionados = pag.items.where((x) => x.id != p.id).toList();
    } catch (_) {}

    return DetalleProducto(producto: p, similares: relacionados);
  }

  /// Primera palabra significativa de la composición (principio activo)
  /// o, en su defecto, del nombre del producto.
  String _terminoRelacionado(Product p) {
    final base = p.composicion.isNotEmpty ? p.composicion : p.nombre;
    final tokens = base
        .toUpperCase()
        .split(RegExp(r'[^A-ZÁÉÍÓÚÜÑ]+'))
        .where((t) => t.length >= 4);
    if (tokens.isNotEmpty) return tokens.first;
    return p.nombre.split(' ').first;
  }

  /// Sugerencias rápidas para el buscador del inicio.
  Future<List<Product>> sugerencias(String q) async {
    if (q.trim().length < 2) return [];
    try {
      final pag = await repo.productos(search: q.trim(), perPage: 5);
      return pag.items;
    } catch (_) {
      return [];
    }
  }
}
