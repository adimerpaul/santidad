import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../../core/formatters.dart';

void showToast(
  BuildContext context,
  String msg, {
  IconData icon = Icons.check_circle,
}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AppColors.primaryLight, size: 18),
            const SizedBox(width: 8),
            Flexible(
              child: Text(
                msg,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 12.5,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.ink,
        shape: const StadiumBorder(),
        margin: const EdgeInsets.only(bottom: 24, left: 30, right: 30),
        duration: const Duration(milliseconds: 2400),
      ),
    );
}

/// Miniatura del producto: foto si existe, sino ícono celeste (`.thumb`).
class ProductThumb extends StatelessWidget {
  final String categoria;
  final String? imagen;
  final double size;
  final double radius;
  final double iconSize;

  const ProductThumb({
    super.key,
    required this.categoria,
    this.imagen,
    this.size = 52,
    this.radius = 14,
    this.iconSize = 22,
  });

  @override
  Widget build(BuildContext context) {
    final url = urlImagenProducto(imagen);
    final icono = Icon(
      iconoCategoria(categoria),
      color: AppColors.skyInk,
      size: iconSize,
    );

    return Container(
      width: size,
      height: size,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: AppColors.sky,
        borderRadius: BorderRadius.circular(radius),
      ),
      child: url == null
          ? icono
          : Image.network(
              url,
              fit: BoxFit.cover,
              width: size,
              height: size,
              errorBuilder: (_, _, _) => icono,
              loadingBuilder: (_, child, progreso) =>
                  progreso == null ? child : icono,
            ),
    );
  }
}

/// Chip de estado de stock (En stock / Stock bajo / Agotado).
/// Chip de disponibilidad por sucursal: no muestra cantidades,
/// solo "Disponible" o "Sin stock" (igual que en frontTienda).
class DisponibleChip extends StatelessWidget {
  final bool disponible;

  const DisponibleChip({super.key, required this.disponible});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
      decoration: BoxDecoration(
        color: disponible ? AppColors.okBg : AppColors.cream2,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        disponible ? 'Disponible' : 'Sin stock',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: disponible ? AppColors.okFg : AppColors.muted,
        ),
      ),
    );
  }
}

class StockChip extends StatelessWidget {
  final int total;
  final int umbral;

  const StockChip({super.key, required this.total, required this.umbral});

  @override
  Widget build(BuildContext context) {
    final Color bg;
    final Color fg;
    final String texto;
    if (total == 0) {
      bg = AppColors.badBg;
      fg = AppColors.badFg;
      texto = 'Agotado';
    } else if (total < umbral) {
      bg = AppColors.warnBg;
      fg = AppColors.warnFg;
      texto = 'Stock bajo: $total';
    } else {
      bg = AppColors.okBg;
      fg = AppColors.okFg;
      texto = total > 100 ? 'En stock' : 'En stock: $total';
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        texto,
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: fg),
      ),
    );
  }
}

/// Fila de precios: original tachado, precio con descuento y etiqueta -X%.
class PriceRow extends StatelessWidget {
  final double precio;
  final double precioAntes;
  final int descuento;
  final double fontSize;

  const PriceRow({
    super.key,
    required this.precio,
    this.precioAntes = 0,
    this.descuento = 0,
    this.fontSize = 14,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        if (precioAntes > precio) ...[
          Flexible(
            child: Text(
              bs(precioAntes),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: fontSize - 2.5,
                color: AppColors.muted,
                decoration: TextDecoration.lineThrough,
              ),
            ),
          ),
          const SizedBox(width: 6),
        ],
        Text(
          bs(precio),
          style: TextStyle(
            fontSize: fontSize,
            fontWeight: FontWeight.w800,
            color: AppColors.primaryDark,
          ),
        ),
        if (descuento > 0) ...[
          const SizedBox(width: 6),
          OfferTag(descuento: descuento),
        ],
      ],
    );
  }
}

/// Etiqueta pequeña de descuento (-15%).
class OfferTag extends StatelessWidget {
  final int descuento;

  const OfferTag({super.key, required this.descuento});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
      decoration: BoxDecoration(
        color: AppColors.badFg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        '-$descuento%',
        style: const TextStyle(
          fontSize: 9.5,
          fontWeight: FontWeight.w800,
          color: Colors.white,
        ),
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  final IconData icon;
  final String mensaje;

  const EmptyState({super.key, required this.icon, required this.mensaje});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 44, horizontal: 20),
      child: Column(
        children: [
          Icon(icon, size: 30, color: AppColors.muted),
          const SizedBox(height: 10),
          Text(
            mensaje,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppColors.muted,
            ),
          ),
        ],
      ),
    );
  }
}

/// Logo de WhatsApp dibujado a mano (Material Icons no incluye marcas):
/// globo de chat con cola y auricular punzonado (transparente).
class WhatsAppIcon extends StatelessWidget {
  final double size;
  final Color color;

  const WhatsAppIcon({super.key, this.size = 18, this.color = Colors.white});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size.square(size),
      painter: _WhatsAppPainter(color),
    );
  }
}

class _WhatsAppPainter extends CustomPainter {
  final Color color;

  _WhatsAppPainter(this.color);

  @override
  void paint(Canvas canvas, Size size) {
    final w = size.width;
    final h = size.height;
    canvas.saveLayer(Offset.zero & size, Paint());

    final globo = Path()
      ..addOval(
        Rect.fromCircle(center: Offset(w * .53, h * .47), radius: w * .43),
      )
      ..moveTo(w * .20, h * .62)
      ..lineTo(w * .05, h * .97)
      ..lineTo(w * .45, h * .88)
      ..close();
    canvas.drawPath(globo, Paint()..color = color);

    final auricular = Path()
      ..moveTo(w * .40, h * .28)
      ..quadraticBezierTo(w * .40, h * .58, w * .70, h * .62);
    canvas.drawPath(
      auricular,
      Paint()
        ..blendMode = BlendMode.clear
        ..style = PaintingStyle.stroke
        ..strokeWidth = w * .14
        ..strokeCap = StrokeCap.round,
    );

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _WhatsAppPainter old) => old.color != color;
}

/// Botón principal con degradado azul (`.btn-gold` del diseño).
/// Admite otro degradado e icono personalizado (p. ej. verde + WhatsApp).
class GradientButton extends StatelessWidget {
  final String texto;
  final IconData? icon;
  final Widget? iconWidget;
  final List<Color>? colores;
  final VoidCallback? onPressed;
  final bool cargando;

  const GradientButton({
    super.key,
    required this.texto,
    this.icon,
    this.iconWidget,
    this.colores,
    this.onPressed,
    this.cargando = false,
  });

  @override
  Widget build(BuildContext context) {
    final gradiente = colores ?? const [AppColors.primary, AppColors.primaryDark];
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: gradiente,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: gradiente.last.withValues(alpha: .4),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ElevatedButton(
        onPressed: cargando ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          disabledBackgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(50),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
        ),
        child: cargando
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  color: Colors.white,
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (iconWidget != null) ...[
                    iconWidget!,
                    const SizedBox(width: 8),
                  ] else if (icon != null) ...[
                    Icon(icon, size: 17),
                    const SizedBox(width: 8),
                  ],
                  Text(
                    texto,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      letterSpacing: .3,
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

/// Encabezado de sección con acción opcional a la derecha.
class SectionHeader extends StatelessWidget {
  final String titulo;
  final String? accion;
  final VoidCallback? onAccion;

  const SectionHeader({
    super.key,
    required this.titulo,
    this.accion,
    this.onAccion,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            titulo,
            style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
          ),
          if (accion != null)
            GestureDetector(
              onTap: onAccion,
              child: Text(
                accion!,
                style: const TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                  color: AppColors.primaryDark,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
