class StockSucursal {
  final int agenciaId;
  final String agencia;
  final int cantidad;

  StockSucursal({
    required this.agenciaId,
    required this.agencia,
    required this.cantidad,
  });

  factory StockSucursal.fromJson(Map<String, dynamic> json) => StockSucursal(
        agenciaId: json['agencia_id'] ?? 0,
        agencia: json['agencia'] ?? '',
        cantidad: json['cantidad'] ?? 0,
      );
}

class Product {
  final int id;
  final String nombre;
  final String categoria;
  final int? categoryId;
  final String presentacion;
  final String marca;
  final double precio;
  final double precioAntes;
  final bool enOferta;
  final double porcentaje;
  final String? imagen;
  final List<StockSucursal> stocks;
  final int stockTotal;

  Product({
    required this.id,
    required this.nombre,
    required this.categoria,
    this.categoryId,
    required this.presentacion,
    required this.marca,
    required this.precio,
    required this.precioAntes,
    required this.enOferta,
    required this.porcentaje,
    this.imagen,
    required this.stocks,
    required this.stockTotal,
  });

  /// Porcentaje de descuento a mostrar en la etiqueta de oferta.
  int get descuento {
    if (porcentaje > 0) return porcentaje.round();
    if (precioAntes > precio && precioAntes > 0) {
      return (((precioAntes - precio) / precioAntes) * 100).round();
    }
    return 0;
  }

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] ?? 0,
        nombre: json['nombre'] ?? '',
        categoria: json['categoria'] ?? 'General',
        categoryId: json['category_id'],
        presentacion: json['presentacion'] ?? '',
        marca: json['marca'] ?? '',
        precio: double.tryParse('${json['precio']}') ?? 0,
        precioAntes: double.tryParse('${json['precio_antes']}') ?? 0,
        enOferta: json['en_oferta'] == true,
        porcentaje: double.tryParse('${json['porcentaje']}') ?? 0,
        imagen: json['imagen'],
        stocks: (json['stocks'] as List? ?? [])
            .map((s) => StockSucursal.fromJson(Map<String, dynamic>.from(s)))
            .toList(),
        stockTotal: json['stock_total'] ?? 0,
      );
}
