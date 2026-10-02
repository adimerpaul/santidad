import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../../data/models/product.dart';
import '../../data/models/promocion.dart';
import 'ui_widgets.dart';

/// Sección "Promociones" del inicio: una tarjeta por promoción vigente con
/// su descuento y los primeros productos; al tocarla se ve el listado.
class PromocionesSection extends StatelessWidget {
  final List<Promocion> promociones;
  final void Function(Product p) onVerProducto;
  final void Function(Product p) onAgregar;

  const PromocionesSection({
    super.key,
    required this.promociones,
    required this.onVerProducto,
    required this.onAgregar,
  });

  void _abrir(BuildContext context, Promocion promo) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _PromocionSheet(
        promocion: promo,
        onVerProducto: (p) {
          Navigator.pop(context);
          onVerProducto(p);
        },
        onAgregar: onAgregar,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SectionHeader(titulo: 'Promociones'),
        SizedBox(
          height: 168,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: promociones.length,
            separatorBuilder: (_, _) => const SizedBox(width: 12),
            itemBuilder: (_, i) => _PromocionCard(
              promocion: promociones[i],
              onTap: () => _abrir(context, promociones[i]),
            ),
          ),
        ),
      ],
    );
  }
}

class _PromocionCard extends StatelessWidget {
  final Promocion promocion;
  final VoidCallback onTap;

  const _PromocionCard({required this.promocion, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final muestra = promocion.productos.take(3).toList();

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: onTap,
      child: Container(
        width: 250,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [AppColors.primary, AppColors.primaryDeep],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: AppColors.primaryDark.withValues(alpha: .25),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.sell, size: 16, color: AppColors.primaryPale),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    promocion.nombre,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (promocion.porcentaje > 0)
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: AppColors.badBg,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${promocion.porcentaje}% menos',
                  style: const TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w800,
                    color: AppColors.badFg,
                  ),
                ),
              ),
            const Spacer(),
            Row(
              children: [
                for (final p in muestra)
                  Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: ProductThumb(
                        categoria: p.categoria,
                        imagen: p.imagen,
                        size: 32,
                        radius: 9,
                        iconSize: 15,
                      ),
                    ),
                  ),
                Expanded(
                  child: Text(
                    promocion.productos.length == 1
                        ? '1 producto'
                        : '${promocion.productos.length} productos',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.right,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primaryPale,
                    ),
                  ),
                ),
                const Icon(
                  Icons.chevron_right,
                  size: 18,
                  color: AppColors.primaryPale,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _PromocionSheet extends StatelessWidget {
  final Promocion promocion;
  final void Function(Product p) onVerProducto;
  final void Function(Product p) onAgregar;

  const _PromocionSheet({
    required this.promocion,
    required this.onVerProducto,
    required this.onAgregar,
  });

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: .6,
      minChildSize: .4,
      maxChildSize: .92,
      builder: (_, scrollCtl) => Column(
        children: [
          const SizedBox(height: 10),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: AppColors.line2,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 14, 18, 8),
            child: Row(
              children: [
                const Icon(Icons.sell, color: AppColors.primaryDark, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    promocion.nombre,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                if (promocion.porcentaje > 0)
                  OfferTag(descuento: promocion.porcentaje),
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.line),
          Expanded(
            child: ListView.separated(
              controller: scrollCtl,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              itemCount: promocion.productos.length,
              separatorBuilder: (_, _) =>
                  const Divider(height: 1, color: AppColors.line),
              itemBuilder: (_, i) {
                final p = promocion.productos[i];
                return InkWell(
                  onTap: () => onVerProducto(p),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(
                      children: [
                        ProductThumb(
                          categoria: p.categoria,
                          imagen: p.imagen,
                          size: 44,
                          radius: 12,
                          iconSize: 18,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                p.nombre,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 3),
                              PriceRow(
                                precio: p.precio,
                                precioAntes: p.precioAntes,
                                fontSize: 12.5,
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            onAgregar(p);
                            showToast(context, 'Añadido al pedido');
                          },
                          style: IconButton.styleFrom(
                            backgroundColor: AppColors.primarySoft,
                            foregroundColor: AppColors.primaryDeep,
                          ),
                          icon: const Icon(Icons.add, size: 18),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
