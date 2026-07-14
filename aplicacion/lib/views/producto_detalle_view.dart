import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/product.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import 'widgets/ui_widgets.dart';

/// Detalle de un producto: ficha completa, descripción y productos similares.
class ProductoDetalleView extends StatefulWidget {
  final Product product;

  const ProductoDetalleView({super.key, required this.product});

  @override
  State<ProductoDetalleView> createState() => _ProductoDetalleViewState();
}

class _ProductoDetalleViewState extends State<ProductoDetalleView> {
  late Product _producto;
  List<Product> _similares = [];
  bool _cargandoSimilares = true;

  @override
  void initState() {
    super.initState();
    _producto = widget.product;
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    final det = await context
        .read<CatalogoViewModel>()
        .detalleConRelacionados(_producto);
    if (!mounted) return;
    setState(() {
      _producto = det.producto;
      _similares = det.similares;
      _cargandoSimilares = false;
    });
  }

  void _abrirSimilar(Product p) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => ProductoDetalleView(product: p)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = _producto;
    final vm = context.watch<CatalogoViewModel>();
    final carrito = context.read<CarritoViewModel>();
    final descripcion = textoPlano(p.descripcion);

    return Scaffold(
      backgroundColor: AppColors.cream,
      appBar: AppBar(
        backgroundColor: AppColors.cream,
        elevation: 0,
        foregroundColor: AppColors.ink,
        titleSpacing: 0,
        title: const Text(
          'Detalle del producto',
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w800,
            letterSpacing: -.2,
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
        children: [
          // Imagen grande
          Stack(
            children: [
              Container(
                height: 210,
                width: double.infinity,
                clipBehavior: Clip.antiAlias,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.line),
                ),
                child: urlImagenProducto(p.imagen) == null
                    ? Icon(
                        iconoCategoria(p.categoria),
                        color: AppColors.skyInk,
                        size: 54,
                      )
                    : Image.network(
                        urlImagenProducto(p.imagen)!,
                        fit: BoxFit.contain,
                        errorBuilder: (_, _, _) => Icon(
                          iconoCategoria(p.categoria),
                          color: AppColors.skyInk,
                          size: 54,
                        ),
                      ),
              ),
              if (p.descuento > 0)
                Positioned(
                  top: 10,
                  right: 10,
                  child: OfferTag(descuento: p.descuento),
                ),
            ],
          ),
          const SizedBox(height: 14),

          // Nombre y categoría
          Text(
            p.nombre,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              letterSpacing: -.3,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            [
              p.categoria,
              p.presentacion,
            ].where((s) => s.isNotEmpty).join(' · '),
            style: const TextStyle(fontSize: 12, color: AppColors.muted),
          ),
          const SizedBox(height: 10),

          // Precios y stock
          Row(
            children: [
              Expanded(
                child: PriceRow(
                  precio: p.precio,
                  precioAntes: p.precioAntes,
                  descuento: p.descuento,
                  fontSize: 19,
                ),
              ),
              StockChip(total: p.stockTotal, umbral: vm.umbralStockBajo),
            ],
          ),
          const SizedBox(height: 14),

          // Ficha del producto
          _FichaCard(
            rows: [
              (
                'Cantidad disponible',
                p.stockTotal == 0
                    ? 'Sin stock'
                    : (p.stockTotal > 100 ? 'En stock' : '${p.stockTotal} und'),
              ),
              ('Origen', p.origen),
              ('Registro sanitario', p.registroSanitario),
              ('Acción terapéutica', p.categoria),
              ('Principio activo', p.composicion),
              ('Laboratorio', p.marca),
              ('Distribuidora', p.distribuidora),
            ],
          ),

          // Stock por sucursal
          if (p.stocks.isNotEmpty) ...[
            const SectionHeader(titulo: 'Stock por sucursal'),
            Container(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 4),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.line),
              ),
              child: Column(
                children: p.stocks.map((s) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          s.agencia,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: AppColors.muted2,
                          ),
                        ),
                        DisponibleChip(disponible: s.cantidad > 0),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          ],

          // Descripción
          if (descripcion.isNotEmpty) ...[
            const SectionHeader(titulo: 'Descripción'),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.line),
              ),
              child: Text(
                descripcion,
                style: const TextStyle(
                  fontSize: 12.5,
                  height: 1.5,
                  color: AppColors.muted2,
                ),
              ),
            ),
          ],

          // Productos similares
          const SectionHeader(titulo: 'Productos relacionados'),
          if (_cargandoSimilares)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else if (_similares.isEmpty)
            const EmptyState(
              icon: Icons.medication_outlined,
              mensaje: 'No se encontraron productos con composición similar',
            )
          else
            SizedBox(
              height: 205,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: _similares.length,
                separatorBuilder: (_, _) => const SizedBox(width: 12),
                itemBuilder: (_, i) {
                  final s = _similares[i];
                  return _SimilarCard(
                    product: s,
                    onTap: () => _abrirSimilar(s),
                    onAgregar: () {
                      carrito.agregar(s);
                      showToast(context, 'Añadido al pedido');
                    },
                  );
                },
              ),
            ),
        ],
      ),

      // Añadir al pedido
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: GradientButton(
            texto: 'Añadir al pedido',
            icon: Icons.add_shopping_cart,
            onPressed: () {
              carrito.agregar(p);
              showToast(context, 'Añadido al pedido');
            },
          ),
        ),
      ),
    );
  }
}

/// Tarjeta con las filas de la ficha técnica (omite los datos vacíos).
class _FichaCard extends StatelessWidget {
  final List<(String, String)> rows;

  const _FichaCard({required this.rows});

  @override
  Widget build(BuildContext context) {
    final visibles = rows.where((r) => r.$2.trim().isNotEmpty).toList();
    if (visibles.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        children: visibles
            .map(
              (r) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(
                      width: 132,
                      child: Text(
                        r.$1,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: AppColors.muted2,
                        ),
                      ),
                    ),
                    Expanded(
                      child: Text(
                        r.$2,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.ink,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _SimilarCard extends StatelessWidget {
  final Product product;
  final VoidCallback onTap;
  final VoidCallback onAgregar;

  const _SimilarCard({
    required this.product,
    required this.onTap,
    required this.onAgregar,
  });

  @override
  Widget build(BuildContext context) {
    final p = product;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 156,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                ProductThumb(
                  categoria: p.categoria,
                  imagen: p.imagen,
                  size: 46,
                  radius: 12,
                  iconSize: 18,
                ),
                if (p.descuento > 0) OfferTag(descuento: p.descuento),
              ],
            ),
            const SizedBox(height: 8),
            Expanded(
              child: Text(
                p.nombre,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  height: 1.25,
                ),
              ),
            ),
            PriceRow(
              precio: p.precio,
              precioAntes: p.precioAntes,
              fontSize: 12.5,
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              height: 30,
              child: TextButton.icon(
                onPressed: onAgregar,
                style: TextButton.styleFrom(
                  backgroundColor: AppColors.primarySoft,
                  foregroundColor: AppColors.primaryDeep,
                  padding: EdgeInsets.zero,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                icon: const Icon(Icons.add, size: 13),
                label: const Text(
                  'Añadir',
                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
