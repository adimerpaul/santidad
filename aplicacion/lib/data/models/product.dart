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
  final String descripcion;
  final String registroSanitario;
  final String origen;
  final String composicion;
  final String distribuidora;
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
    this.descripcion = '',
    this.registroSanitario = '',
    this.origen = '',
    this.composicion = '',
    this.distribuidora = '',
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

  factory Product.fromJson(Map<String, dynamic> json) {
    var precio = double.tryParse('${json['precio']}') ?? 0;
    var precioAntes = double.tryParse('${json['precio_antes']}') ?? 0;
    final porcentaje = double.tryParse('${json['porcentaje']}') ?? 0;

    // Si el backend aún no aplicó el descuento (precio_antes no viene mayor
    // al precio), se calcula aquí para mostrar siempre el monto original,
    // el monto con descuento y el porcentaje.
    if (porcentaje > 0 && precioAntes <= precio) {
      precioAntes = precio;
      precio = double.parse(
        (precio - (precio * porcentaje / 100)).toStringAsFixed(2),
      );
    }

    return Product(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      categoria: json['categoria'] ?? 'General',
      categoryId: json['category_id'],
      presentacion: json['presentacion'] ?? '',
      marca: json['marca'] ?? '',
      precio: precio,
      precioAntes: precioAntes,
      enOferta: json['en_oferta'] == true,
      porcentaje: porcentaje,
      imagen: json['imagen'],
      descripcion: json['descripcion'] ?? '',
      registroSanitario: json['registro_sanitario'] ?? '',
      origen: json['origen'] ?? '',
      composicion: json['composicion'] ?? '',
      distribuidora: json['distribuidora'] ?? '',
      stocks: (json['stocks'] as List? ?? [])
          .map((s) => StockSucursal.fromJson(Map<String, dynamic>.from(s)))
          .toList(),
      stockTotal: json['stock_total'] ?? 0,
    );
  }
}
