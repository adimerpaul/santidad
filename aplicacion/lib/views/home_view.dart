import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/product.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import 'producto_detalle_view.dart';
import 'widgets/hero_carousel.dart';
import 'widgets/ui_widgets.dart';

class HomeView extends StatefulWidget {
  final void Function(String filtro, {String busqueda}) onExplorar;
  final VoidCallback onNuevoPedido;
  final VoidCallback onMisPedidos;

  const HomeView({
    super.key,
    required this.onExplorar,
    required this.onNuevoPedido,
    required this.onMisPedidos,
  });

  @override
  State<HomeView> createState() => _HomeViewState();
}

class _HomeViewState extends State<HomeView> {
  final _searchCtl = TextEditingController();
  Timer? _debounce;
  List<Product> _sugerencias = [];

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtl.dispose();
    super.dispose();
  }

  void _onBuscar(String q) {
    _debounce?.cancel();
    if (q.trim().length < 2) {
      setState(() => _sugerencias = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 350), () async {
      final vm = context.read<CatalogoViewModel>();
      final res = await vm.sugerencias(q);
      if (mounted) setState(() => _sugerencias = res);
    });
  }

  void _irASugerencia(Product p) {
    _searchCtl.clear();
    setState(() => _sugerencias = []);
    _verDetalle(p);
  }

  void _verDetalle(Product p) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => ProductoDetalleView(product: p)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final catalogo = context.watch<CatalogoViewModel>();
    final carrito = context.read<CarritoViewModel>();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
      children: [
        Text(
          saludoDelDia(),
          style: const TextStyle(fontSize: 12, color: AppColors.muted),
        ),
        const SizedBox(height: 4),
        const Text(
          '¿Qué necesitas pedir hoy?',
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        const SizedBox(height: 12),

        // Buscador con sugerencias
        TextField(
          controller: _searchCtl,
          onChanged: _onBuscar,
          onSubmitted: (q) =>
              widget.onExplorar(FiltroCatalogo.todo, busqueda: q.trim()),
          decoration: const InputDecoration(
            hintText: 'Buscar producto, categoría…',
            prefixIcon: Icon(
              Icons.search,
              size: 19,
              color: AppColors.primaryDark,
            ),
          ),
        ),
        if (_sugerencias.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(top: 6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.line),
              boxShadow: [
                BoxShadow(
                  color: AppColors.ink.withValues(alpha: .12),
                  blurRadius: 30,
                  offset: const Offset(0, 14),
                ),
              ],
            ),
            child: Column(
              children: _sugerencias
                  .map(
                    (p) => InkWell(
                      onTap: () => _irASugerencia(p),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 9,
                        ),
                        child: Row(
                          children: [
                            ProductThumb(
                              categoria: p.categoria,
                              imagen: p.imagen,
                              size: 38,
                              radius: 10,
                              iconSize: 16,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    p.nombre,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  Text(
                                    p.categoria,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      color: AppColors.muted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            PriceRow(
                              precio: p.precio,
                              precioAntes: p.precioAntes,
                              descuento: p.descuento,
                              fontSize: 12.5,
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),

        const SizedBox(height: 14),

        // Carrusel principal (mismas imágenes que la tienda pública)
        if (catalogo.carrusel.isNotEmpty) ...[
          HeroCarousel(imagenes: catalogo.carrusel),
          const SizedBox(height: 14),
        ],

        // Banner de ofertas
        InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => widget.onExplorar(FiltroCatalogo.ofertas),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.ink, AppColors.inkSoft, Color(0xFF3D557F)],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: AppColors.ink.withValues(alpha: .28),
                  blurRadius: 26,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: .18),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: AppColors.primary.withValues(alpha: .45),
                    ),
                  ),
                  child: const Icon(
                    Icons.sell,
                    color: AppColors.primaryLight,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 14),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Sección de ofertas',
                        style: TextStyle(
                          fontSize: 15.5,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primaryPale,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Descuentos especiales esta semana',
                        style: TextStyle(fontSize: 11.5, color: Colors.white70),
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.chevron_right,
                  color: AppColors.primaryLight,
                  size: 20,
                ),
              ],
            ),
          ),
        ),

        // Categorías
        SectionHeader(
          titulo: 'Categorías',
          accion: 'Ver todo',
          onAccion: () => widget.onExplorar(FiltroCatalogo.todo),
        ),
        SizedBox(
          height: 88,
          child: catalogo.cargandoInicio && catalogo.categorias.isEmpty
              ? const Center(child: CircularProgressIndicator(strokeWidth: 2))
              : ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: catalogo.categorias.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 10),
                  itemBuilder: (_, i) {
                    final cat = catalogo.categorias[i];
                    return GestureDetector(
                      onTap: () => widget.onExplorar('${cat.id}'),
                      child: SizedBox(
                        width: 66,
                        child: Column(
                          children: [
                            Container(
                              width: 52,
                              height: 52,
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.line),
                              ),
                              child: Icon(
                                iconoCategoria(cat.name),
                                color: AppColors.skyInk,
                                size: 22,
                              ),
                            ),
                            const SizedBox(height: 7),
                            Text(
                              cat.name,
                              maxLines: 2,
                              textAlign: TextAlign.center,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: AppColors.muted2,
                                height: 1.2,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ),

        // Ofertas de la semana
        if (catalogo.ofertas.isNotEmpty) ...[
          const SectionHeader(titulo: 'Ofertas de la semana'),
          SizedBox(
            height: 196,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: catalogo.ofertas.length,
              separatorBuilder: (_, _) => const SizedBox(width: 12),
              itemBuilder: (_, i) {
                final p = catalogo.ofertas[i];
                return GestureDetector(
                  onTap: () => _verDetalle(p),
                  child: Container(
                    width: 156,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
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
                            ),
                            if (p.descuento > 0)
                              OfferTag(descuento: p.descuento),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Expanded(
                          child: Text(
                            p.nombre,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              height: 1.25,
                            ),
                          ),
                        ),
                        PriceRow(
                          precio: p.precio,
                          precioAntes: p.precioAntes,
                          fontSize: 13.5,
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          width: double.infinity,
                          height: 34,
                          child: TextButton.icon(
                            onPressed: () {
                              carrito.agregar(p);
                              showToast(context, 'Añadido al pedido');
                            },
                            style: TextButton.styleFrom(
                              backgroundColor: AppColors.primarySoft,
                              foregroundColor: AppColors.primaryDeep,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                            icon: const Icon(Icons.add, size: 14),
                            label: const Text(
                              'Añadir',
                              style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],

        const SizedBox(height: 14),

        // Accesos rápidos
        Row(
          children: [
            Expanded(
              child: _QuickButton(
                gradiente: true,
                icon: Icons.note_add,
                titulo: 'Nuevo pedido',
                subtitulo: 'Arma tu pedido y envíalo por WhatsApp',
                onTap: widget.onNuevoPedido,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _QuickButton(
                gradiente: false,
                icon: Icons.history,
                titulo: 'Mis pedidos',
                subtitulo: 'Revisa tu historial de pedidos',
                onTap: widget.onMisPedidos,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _QuickButton extends StatelessWidget {
  final bool gradiente;
  final IconData icon;
  final String titulo;
  final String subtitulo;
  final VoidCallback onTap;

  const _QuickButton({
    required this.gradiente,
    required this.icon,
    required this.titulo,
    required this.subtitulo,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(13),
        height: 110,
        decoration: BoxDecoration(
          gradient: gradiente
              ? const LinearGradient(
                  colors: [AppColors.primary, AppColors.primaryDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                )
              : null,
          color: gradiente ? null : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: gradiente ? null : Border.all(color: AppColors.line),
          boxShadow: gradiente
              ? [
                  BoxShadow(
                    color: AppColors.primaryDark.withValues(alpha: .32),
                    blurRadius: 24,
                    offset: const Offset(0, 10),
                  ),
                ]
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              icon,
              size: 20,
              color: gradiente ? Colors.white : AppColors.skyInk,
            ),
            const Spacer(),
            Text(
              titulo,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: gradiente ? Colors.white : AppColors.ink,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              subtitulo,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 10.5,
                color: gradiente ? Colors.white70 : AppColors.muted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
