import '../../core/api_client.dart';
import '../models/categoria.dart';
import '../models/product.dart';
import '../models/sucursal.dart';

class ConfigApp {
  final List<Categoria> categorias;
  final List<Sucursal> sucursales;
  final int umbralStockBajo;

  ConfigApp({
    required this.categorias,
    required this.sucursales,
    required this.umbralStockBajo,
  });
}

class PaginaProductos {
  final List<Product> items;
  final int paginaActual;
  final int ultimaPagina;

  PaginaProductos({
    required this.items,
    required this.paginaActual,
    required this.ultimaPagina,
  });

  bool get hayMas => paginaActual < ultimaPagina;
}

class CatalogoRepository {
  final ApiClient api;

  CatalogoRepository(this.api);

  Future<ConfigApp> config() async {
    final data = await api.get('/app/config');
    return ConfigApp(
      categorias: (data['categorias'] as List? ?? [])
          .map((c) => Categoria.fromJson(Map<String, dynamic>.from(c)))
          .toList(),
      sucursales: (data['sucursales'] as List? ?? [])
          .map((s) => Sucursal.fromJson(Map<String, dynamic>.from(s)))
          .toList(),
      umbralStockBajo: data['umbral_stock_bajo'] ?? 20,
    );
  }

  Future<PaginaProductos> productos({
    String search = '',
    int categoryId = 0,
    bool ofertas = false,
    int page = 1,
    int perPage = 30,
  }) async {
    final data = await api.get('/app/productos', query: {
      if (search.isNotEmpty) 'search': search,
      if (categoryId > 0) 'category_id': '$categoryId',
      if (ofertas) 'ofertas': '1',
      'page': '$page',
      'per_page': '$perPage',
    });

    return PaginaProductos(
      items: (data['data'] as List? ?? [])
          .map((p) => Product.fromJson(Map<String, dynamic>.from(p)))
          .toList(),
      paginaActual: data['current_page'] ?? 1,
      ultimaPagina: data['last_page'] ?? 1,
    );
  }
}
