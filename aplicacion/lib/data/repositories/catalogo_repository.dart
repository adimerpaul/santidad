import '../../core/api_client.dart';
import '../models/cart_item.dart';
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

  /// Total de productos que coinciden con la búsqueda (todas las páginas).
  final int total;

  PaginaProductos({
    required this.items,
    required this.paginaActual,
    required this.ultimaPagina,
    required this.total,
  });

  bool get hayMas => paginaActual < ultimaPagina;
}

class DetalleProducto {
  final Product producto;
  final List<Product> similares;

  DetalleProducto({required this.producto, required this.similares});
}

class OrdenCreada {
  final String numero;
  final Map<int, double> precios;

  const OrdenCreada({required this.numero, required this.precios});
}

/// Producto del carrito sin stock suficiente en la sucursal elegida.
class ProductoSinStock {
  final int productoId;
  final String nombre;
  final int cantidadSolicitada;
  final int stockDisponible;

  ProductoSinStock({
    required this.productoId,
    required this.nombre,
    required this.cantidadSolicitada,
    required this.stockDisponible,
  });

  int get faltan => cantidadSolicitada - stockDisponible;

  factory ProductoSinStock.fromJson(Map<String, dynamic> json) =>
      ProductoSinStock(
        productoId: json['producto_id'] ?? 0,
        nombre: json['nombre'] ?? '',
        cantidadSolicitada: json['cantidad_solicitada'] ?? 0,
        stockDisponible: json['stock_disponible'] ?? 0,
      );
}

class CatalogoRepository {
  final ApiClient api;

  CatalogoRepository(this.api);

  /// Imágenes del carrusel de inicio (solo las de tipo "Aplicacion").
  Future<List<String>> carousels() async {
    final data = await api.get('/carouselsAplicacion');
    return (data as List? ?? [])
        .map((c) => '${c['image'] ?? ''}')
        .where((img) => img.isNotEmpty)
        .toList();
  }

  Future<DetalleProducto> detalle(int id, {int? agenciaId}) async {
    final data = await api.get(
      '/app/productos/$id',
      query: {if (agenciaId != null) 'agencia_id': '$agenciaId'},
    );
    return DetalleProducto(
      producto: Product.fromJson(
        Map<String, dynamic>.from(data['producto'] ?? {}),
      ),
      similares: (data['similares'] as List? ?? [])
          .map((p) => Product.fromJson(Map<String, dynamic>.from(p)))
          .toList(),
    );
  }

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
    int? agenciaId,
  }) async {
    final data = await api.get(
      '/app/productos',
      query: {
        if (search.isNotEmpty) 'search': search,
        if (categoryId > 0) 'category_id': '$categoryId',
        if (ofertas) 'ofertas': '1',
        if (agenciaId != null) 'agencia_id': '$agenciaId',
        'page': '$page',
        'per_page': '$perPage',
      },
    );

    final items = (data['data'] as List? ?? [])
        .map((p) => Product.fromJson(Map<String, dynamic>.from(p)))
        .toList();

    return PaginaProductos(
      items: items,
      paginaActual: data['current_page'] ?? 1,
      ultimaPagina: data['last_page'] ?? 1,
      total: data['total'] ?? items.length,
    );
  }

  /// Verifica en el servidor el stock del carrito en una sucursal.
  /// Mismo endpoint que usa la tienda pública antes de pedir por WhatsApp.
  /// [cantidades] mapea product_id -> cantidad pedida.
  Future<List<ProductoSinStock>> verificarStockSucursal({
    required int sucursalId,
    required Map<int, int> cantidades,
  }) async {
    final data = await api.post(
      '/stock/verificar-sucursal',
      body: {
        'sucursal_id': sucursalId,
        'productos': cantidades.entries
            .map((e) => {'producto_id': e.key, 'cantidad': e.value})
            .toList(),
      },
    );

    return ((data as Map?)?['productos_sin_stock'] as List? ?? [])
        .map((p) => ProductoSinStock.fromJson(Map<String, dynamic>.from(p)))
        .toList();
  }

  /// Registra el pedido en la tabla `orders` del backend (la misma que usa
  /// la tienda pública) y devuelve el número generado por el servidor
  /// (p. ej. PEDIDOWEB_Nº7), con el que caja lo recupera en /sale.
  Future<OrdenCreada> crearOrden({
    required List<CartItem> items,
    required int sucursalId,
    required String sucursalNombre,
  }) async {
    final data = await api.post(
      '/orders',
      body: {
        'items': items
            .map(
              (i) => {
                'product_id': i.product.id,
                'nombre': i.product.nombre,
                'precio': i.product.precio,
                'cantidad': i.qty,
                'imagen': i.product.imagen,
              },
            )
            .toList(),
        'source': 'app',
        'sucursal_id': sucursalId,
        'sucursal_nombre': sucursalNombre,
      },
    );

    final response = Map<String, dynamic>.from(data as Map? ?? {});
    final precios = <int, double>{};
    for (final raw in response['items'] as List? ?? const []) {
      final item = Map<String, dynamic>.from(raw as Map);
      final id = int.tryParse('${item['product_id'] ?? ''}');
      final price = double.tryParse('${item['price'] ?? ''}');
      if (id != null && price != null) precios[id] = price;
    }

    return OrdenCreada(
      numero: '${response['order_number'] ?? ''}',
      precios: precios,
    );
  }
}
