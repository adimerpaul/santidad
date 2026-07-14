import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import '../viewmodels/pedidos_viewmodel.dart';
import 'home_view.dart';
import 'nuevo_pedido_view.dart';
import 'pedidos_view.dart';
import 'productos_view.dart';
import 'sucursales_view.dart';
import 'widgets/ui_widgets.dart';

/// Contenedor principal: header, pestañas y navegación inferior con FAB.
class ShellView extends StatefulWidget {
  const ShellView({super.key});

  @override
  State<ShellView> createState() => _ShellViewState();
}

class _ShellViewState extends State<ShellView> {
  int _tab = 0;
  final _productosKey = GlobalKey<ProductosViewState>();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final catalogo = context.read<CatalogoViewModel>();
      catalogo.cargarProductos();
      context.read<PedidosViewModel>().cargar();
      await catalogo.cargarInicio();
      if (!mounted) return;
      // Sucursal por defecto para el pedido: la primera activa
      final carrito = context.read<CarritoViewModel>();
      if (carrito.agenciaId == null && catalogo.sucursales.isNotEmpty) {
        carrito.setAgencia(catalogo.sucursales.first.id);
      }
    });
  }

  void _go(int tab) => setState(() => _tab = tab);

  void _explorar(String filtro, {String busqueda = ''}) {
    final catalogo = context.read<CatalogoViewModel>();
    catalogo.filtro = filtro;
    catalogo.busqueda = busqueda;
    catalogo.cargarProductos();
    _go(1);
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => _productosKey.currentState?.sincronizarBusqueda(),
    );
  }

  Future<void> _abrirWhatsAppGeneral() async {
    final numero = numeroWhatsApp(dotenv.env['WHATSAPP'] ?? '');
    if (numero.isEmpty) return;
    await launchUrl(
      Uri.parse('https://wa.me/$numero'),
      mode: LaunchMode.externalApplication,
    );
  }

  @override
  Widget build(BuildContext context) {
    final carrito = context.watch<CarritoViewModel>();

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [AppColors.cream, AppColors.cream2],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          bottom: false,
          child: Column(
            children: [
              const _Header(),
              Expanded(
                child: Stack(
                  children: [
                    IndexedStack(
                      index: _tab,
                      children: [
                        HomeView(
                          onExplorar: _explorar,
                          onNuevoPedido: () => _go(2),
                          onMisPedidos: () => _go(3),
                        ),
                        ProductosView(key: _productosKey),
                        NuevoPedidoView(
                          onExplorar: () => _go(1),
                          onVerPedidos: () => _go(3),
                        ),
                        const PedidosView(),
                        const SucursalesView(),
                      ],
                    ),
                    // Botón flotante de WhatsApp
                    Positioned(
                      right: 14,
                      bottom: 14,
                      child: GestureDetector(
                        onTap: _abrirWhatsAppGeneral,
                        child: Container(
                          width: 52,
                          height: 52,
                          decoration: BoxDecoration(
                            color: AppColors.wa,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.wa.withValues(alpha: .45),
                                blurRadius: 24,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: const Center(child: WhatsAppIcon(size: 27)),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: _BottomNav(
        tab: _tab,
        badge: carrito.totalUnidades,
        onTap: _go,
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 9),
      decoration: BoxDecoration(
        color: AppColors.cream.withValues(alpha: .88),
        border: Border(
          bottom: BorderSide(
            color: AppColors.primaryDark.withValues(alpha: .18),
          ),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primary, AppColors.primaryDark],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primaryDark.withValues(alpha: .35),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: const Icon(Icons.local_pharmacy,
                color: Colors.white, size: 19),
          ),
          const SizedBox(width: 10),
          const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Santidad-Divina',
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  height: 1.1,
                  letterSpacing: -.2,
                ),
              ),
              Text(
                'FARMACIAS S.R.L.',
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 2.5,
                  color: AppColors.primaryDark,
                ),
              ),
            ],
          ),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: AppColors.primarySoft,
              borderRadius: BorderRadius.circular(999),
            ),
            child: const Row(
              children: [
                Icon(Icons.location_on,
                    size: 12, color: AppColors.primaryDeep),
                SizedBox(width: 4),
                Text(
                  'Oruro',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primaryDeep,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _BottomNav extends StatelessWidget {
  final int tab;
  final int badge;
  final void Function(int) onTap;

  const _BottomNav({
    required this.tab,
    required this.badge,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.cream.withValues(alpha: .94),
        border: Border(
          top: BorderSide(
            color: AppColors.primaryDark.withValues(alpha: .18),
          ),
        ),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 62,
          child: Row(
            children: [
              _item(0, Icons.home, 'Inicio'),
              _item(1, Icons.medication, 'Productos'),
              _fab(context),
              _item(3, Icons.assignment, 'Pedidos'),
              _item(4, Icons.store, 'Sucursales'),
            ],
          ),
        ),
      ),
    );
  }

  Widget _item(int i, IconData icon, String label) {
    final activo = tab == i;
    final color = activo ? AppColors.primaryDark : const Color(0xFF8FA0B8);
    return Expanded(
      child: InkWell(
        onTap: () => onTap(i),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 20, color: color),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 9.5,
                fontWeight: FontWeight.w700,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _fab(BuildContext context) {
    final activo = tab == 2;
    return Expanded(
      child: GestureDetector(
        onTap: () => onTap(2),
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Transform.translate(
              offset: const Offset(0, -14),
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppColors.primary, AppColors.primaryDark],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.cream, width: 3),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primaryDark.withValues(alpha: .45),
                          blurRadius: 20,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.shopping_basket,
                        color: Colors.white, size: 19),
                  ),
                  if (badge > 0)
                    Positioned(
                      top: -4,
                      right: -6,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 5, vertical: 1),
                        constraints: const BoxConstraints(minWidth: 19),
                        decoration: BoxDecoration(
                          color: AppColors.badFg,
                          borderRadius: BorderRadius.circular(999),
                          border:
                              Border.all(color: AppColors.cream, width: 2),
                        ),
                        child: Text(
                          '$badge',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            Transform.translate(
              offset: const Offset(0, -12),
              child: Text(
                'Pedido',
                style: TextStyle(
                  fontSize: 9.5,
                  fontWeight: FontWeight.w700,
                  color: activo
                      ? AppColors.primaryDark
                      : const Color(0xFF8FA0B8),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
