import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

import '../data/models/pedido.dart';

/// Formato de moneda boliviana.
String bs(num n) => 'Bs ${n.toStringAsFixed(2)}';

/// URL completa de la imagen de un producto.
/// Deriva la base del API_URL del .env: http://host:8000/api → http://host:8000/images/…
String? urlImagenProducto(String? imagen) {
  final img = (imagen ?? '').trim();
  if (img.isEmpty) return null;
  if (img.startsWith('http://') || img.startsWith('https://')) return img;
  final api = dotenv.env['API_URL'] ?? '';
  if (api.isEmpty) return null;
  final base = api.replaceFirst(RegExp(r'/api/?$'), '');
  // Codifica espacios y caracteres especiales del nombre de archivo
  final nombre = img.contains('%') ? img : Uri.encodeComponent(img);
  return '$base/images/$nombre';
}

/// Texto del pedido que se envía por WhatsApp.
String mensajeWhatsAppPedido(Pedido pd) {
  final b = StringBuffer()
    ..writeln('🧾 *Pedido ${pd.codigo} — Farmacia Santidad*')
    ..writeln('📍 Sucursal: ${pd.sucursal}');
  if (pd.urgente) b.writeln('⚡ Prioridad: *URGENTE*');
  if (pd.observacion != null && pd.observacion!.isNotEmpty) {
    b.writeln('📝 ${pd.observacion}');
  }
  b.writeln('──────────────');
  for (final d in pd.detalles) {
    b.writeln('• ${d.cantidad}× ${d.producto} — ${bs(d.subtotal)}');
  }
  b
    ..writeln('──────────────')
    ..writeln('*Total: ${bs(pd.total)}*');
  return b.toString().trimRight();
}

String saludoDelDia() {
  final h = DateTime.now().hour;
  if (h < 12) return 'Buenos días';
  if (h < 19) return 'Buenas tardes';
  return 'Buenas noches';
}

/// Ícono representativo según el nombre de la categoría.
IconData iconoCategoria(String nombre) {
  final n = nombre.toLowerCase();
  if (n.contains('analg') || n.contains('dolor')) return Icons.healing;
  if (n.contains('antibi')) return Icons.biotech;
  if (n.contains('vitami') || n.contains('suplement')) {
    return Icons.energy_savings_leaf;
  }
  if (n.contains('alerg') || n.contains('alérg')) return Icons.shield;
  if (n.contains('gastro') || n.contains('digest')) {
    return Icons.medication_liquid;
  }
  if (n.contains('respir') || n.contains('tos')) return Icons.air;
  if (n.contains('pediá') || n.contains('pedia') ||
      n.contains('niñ') || n.contains('beb')) {
    return Icons.child_care;
  }
  if (n.contains('cuidado') || n.contains('higien') ||
      n.contains('personal')) {
    return Icons.clean_hands;
  }
  if (n.contains('dermo') || n.contains('piel') || n.contains('solar')) {
    return Icons.wb_sunny;
  }
  if (n.contains('cardio') || n.contains('coraz')) return Icons.favorite;
  return Icons.medication;
}
