import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_theme.dart';
import '../data/models/product.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import 'producto_detalle_view.dart';
import 'widgets/ui_widgets.dart';

class ProductosView extends StatefulWidget {
  const ProductosView({super.key});

  @override
  State<ProductosView> createState() => ProductosViewState();
}

class ProductosViewState extends State<ProductosView> {
  final searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();
  Timer? _debounce;
  final Set<int> _stockAbierto = {};

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(() {
      if (_scrollCtl.position.pixels >
          _scrollCtl.position.maxScrollExtent - 300) {
        context.read<CatalogoViewModel>().cargarMas();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    searchCtl.dispose();
    _scrollCtl.dispose();
    super.dispose();
  }

  void _onBuscar(String q) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (mounted) context.read<CatalogoViewModel>().setBusqueda(q);
    });
  }

  /// Sincroniza el campo de búsqueda cuando otra pantalla fija la búsqueda.
  void sincronizarBusqueda() {
    final vm = context.read<CatalogoViewModel>();
    if (searchCtl.text != vm.busqueda) {
      searchCtl.text = vm.busqueda;
    }
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<CatalogoViewModel>();
    final carrito = context.read<CarritoViewModel>();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Productos',
                style: TextStyle(
                  fontSize: 21,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -.4,
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: searchCtl,
                onChanged: _onBuscar,
                decoration: InputDecoration(
                  hintText: 'Buscar en el inventario…',
                  prefixIcon: const Icon(
                    Icons.search,
                    size: 19,
                    color: AppColors.primaryDark,
                  ),
                  suffixIcon: searchCtl.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(
                            Icons.close,
                            size: 17,
                            color: AppColors.muted,
                          ),
                          onPressed: () {
                            searchCtl.clear();
                            vm.setBusqueda('');
                          },
                        )
                      : null,
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),

        // Chips de categorías
        SizedBox(
          height: 36,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            children: [
              _chip('Todo', FiltroCatalogo.todo, vm),
              _chip('Ofertas', FiltroCatalogo.ofertas, vm),
              ...vm.categorias.map((c) => _chip(c.name, '${c.id}', vm)),
            ],
          ),
        ),
        const SizedBox(height: 12),

        Expanded(
          child: vm.cargandoProductos
              ? const Center(child: CircularProgressIndicator(strokeWidth: 2.5))
              : vm.productos.isEmpty
              ? const Center(
                  child: EmptyState(
                    icon: Icons.sentiment_dissatisfied_outlined,
                    mensaje: 'Sin resultados para tu búsqueda',
                  ),
                )
              : ListView.separated(
                  controller: _scrollCtl,
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 110),
                  itemCount: vm.productos.length + (vm.hayMasProductos ? 1 : 0),
                  separatorBuilder: (_, _) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    if (i >= vm.productos.length) {
                      return const Center(
                        child: Padding(
                          padding: EdgeInsets.all(14),
                          child: SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        ),
                      );
                    }
                    final p = vm.productos[i];
                    return _ProductCard(
                      product: p,
                      umbral: vm.umbralStockBajo,
                      abierto: _stockAbierto.contains(p.id),
                      onToggleStock: () => setState(() {
                        _stockAbierto.contains(p.id)
                            ? _stockAbierto.remove(p.id)
                            : _stockAbierto.add(p.id);
                      }),
                      onAgregar: () {
                        carrito.agregar(p);
                        showToast(context, 'Añadido al pedido');
                      },
                      onVerDetalle: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ProductoDetalleView(product: p),
                        ),
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  Widget _chip(String texto, String filtro, CatalogoViewModel vm) {
    final activo = vm.filtro == filtro;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: GestureDetector(
        onTap: () => vm.setFiltro(filtro),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
          decoration: BoxDecoration(
            color: activo ? AppColors.ink : Colors.white,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: activo ? AppColors.ink : AppColors.line2),
          ),
          child: Text(
            texto,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: activo ? AppColors.primaryPale : AppColors.muted2,
            ),
          ),
        ),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  final Product product;
  final int umbral;
  final bool abierto;
  final VoidCallback onToggleStock;
  final VoidCallback onAgregar;
  final VoidCallback onVerDetalle;

  const _ProductCard({
    required this.product,
    required this.umbral,
    required this.abierto,
    required this.onToggleStock,
    required this.onAgregar,
    required this.onVerDetalle,
  });

  @override
  Widget build(BuildContext context) {
    final p = product;
    return GestureDetector(
      onTap: onVerDetalle,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ProductThumb(categoria: p.categoria, imagen: p.imagen),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              p.nombre,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          if (p.enOferta && p.descuento > 0) ...[
                            const SizedBox(width: 6),
                            OfferTag(descuento: p.descuento),
                          ],
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(
                        [
                          p.categoria,
                          p.presentacion,
                        ].where((s) => s.isNotEmpty).join(' · '),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppColors.muted,
                        ),
                      ),
                      const SizedBox(height: 5),
                      Row(
                        children: [
                          Flexible(
                            child: PriceRow(
                              precio: p.precio,
                              precioAntes: p.precioAntes,
                            ),
                          ),
                          const SizedBox(width: 8),
                          StockChip(total: p.stockTotal, umbral: umbral),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                SizedBox(
                  width: 40,
                  height: 40,
                  child: IconButton(
                    onPressed: onAgregar,
                    style: IconButton.styleFrom(
                      backgroundColor: AppColors.primarySoft,
                      foregroundColor: AppColors.primaryDeep,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(13),
                      ),
                    ),
                    icon: const Icon(Icons.add, size: 18),
                  ),
                ),
              ],
            ),
            if (p.stocks.isNotEmpty) ...[
              const SizedBox(height: 8),
              GestureDetector(
                onTap: onToggleStock,
                child: Row(
                  children: [
                    const Icon(Icons.store, size: 12, color: AppColors.skyInk),
                    const SizedBox(width: 6),
                    Text(
                      '${abierto ? 'Ocultar' : 'Ver'} stock por sucursal',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.skyInk,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Icon(
                      abierto
                          ? Icons.keyboard_arrow_up
                          : Icons.keyboard_arrow_down,
                      size: 14,
                      color: AppColors.skyInk,
                    ),
                  ],
                ),
              ),
              if (abierto)
                Container(
                  margin: const EdgeInsets.only(top: 8),
                  padding: const EdgeInsets.only(top: 8),
                  decoration: const BoxDecoration(
                    border: Border(top: BorderSide(color: AppColors.line)),
                  ),
                  child: Column(
                    children: p.stocks.map((s) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 6),
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
          ],
        ),
      ),
    );
  }
}
