class PedidoLinea {
  final String producto;
  final int cantidad;
  final double precio;

  PedidoLinea({
    required this.producto,
    required this.cantidad,
    required this.precio,
  });

  double get subtotal => precio * cantidad;

  factory PedidoLinea.fromJson(Map<String, dynamic> json) => PedidoLinea(
        producto: json['producto'] ?? '',
        cantidad: json['cantidad'] ?? 0,
        precio: double.tryParse('${json['precio']}') ?? 0,
      );

  Map<String, dynamic> toJson() => {
        'producto': producto,
        'cantidad': cantidad,
        'precio': precio,
      };
}

/// Pedido armado en la app y enviado por WhatsApp (se guarda localmente).
class Pedido {
  final int id;
  final String codigo;
  final String sucursal;
  final String fecha;
  final int items;
  final double total;
  final String estado;
  final String prioridad;
  final String? observacion;
  final String whatsapp; // número al que se envió
  final List<PedidoLinea> detalles;

  Pedido({
    required this.id,
    required this.codigo,
    required this.sucursal,
    required this.fecha,
    required this.items,
    required this.total,
    required this.estado,
    required this.prioridad,
    this.observacion,
    this.whatsapp = '',
    required this.detalles,
  });

  bool get urgente => prioridad == 'URGENTE';

  factory Pedido.fromJson(Map<String, dynamic> json) => Pedido(
        id: json['id'] ?? 0,
        codigo: json['codigo'] ?? '',
        sucursal: json['sucursal'] ?? '',
        fecha: json['fecha'] ?? '',
        items: json['items'] ?? 0,
        total: double.tryParse('${json['total']}') ?? 0,
        estado: json['estado'] ?? 'ENVIADO',
        prioridad: json['prioridad'] ?? 'NORMAL',
        observacion: json['observacion'],
        whatsapp: json['whatsapp'] ?? '',
        detalles: (json['detalles'] as List? ?? [])
            .map((d) => PedidoLinea.fromJson(Map<String, dynamic>.from(d)))
            .toList(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'codigo': codigo,
        'sucursal': sucursal,
        'fecha': fecha,
        'items': items,
        'total': total,
        'estado': estado,
        'prioridad': prioridad,
        'observacion': observacion,
        'whatsapp': whatsapp,
        'detalles': detalles.map((d) => d.toJson()).toList(),
      };
}
