import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/sucursal.dart';
import '../data/repositories/catalogo_repository.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import '../viewmodels/pedidos_viewmodel.dart';
import 'widgets/ui_widgets.dart';

/// Decisión del vendedor cuando hay productos sin stock en la sucursal.
enum _AccionSinStock { cancelar, editar, eliminarYEnviar }

class NuevoPedidoView extends StatefulWidget {
  final VoidCallback onExplorar;
  final VoidCallback onVerPedidos;

  const NuevoPedidoView({
    super.key,
    required this.onExplorar,
    required this.onVerPedidos,
  });

  @override
  State<NuevoPedidoView> createState() => _NuevoPedidoViewState();
}

class _NuevoPedidoViewState extends State<NuevoPedidoView> {
  final _obsCtl = TextEditingController();
  bool _enviando = false;

  @override
  void dispose() {
    _obsCtl.dispose();
    super.dispose();
  }

  Future<void> _enviarPorWhatsApp() async {
    final carrito = context.read<CarritoViewModel>();
    final catalogo = context.read<CatalogoViewModel>();
    final pedidosVM = context.read<PedidosViewModel>();
    carrito.setObservacion(_obsCtl.text.trim());

    Sucursal? sucursal;
    for (final s in catalogo.sucursales) {
      if (s.id == carrito.agenciaId) sucursal = s;
    }
    if (sucursal == null) {
      showToast(context, 'Selecciona una sucursal', icon: Icons.error_outline);
      return;
    }

    // Verificación de stock en la sucursal contra el servidor,
    // igual que la tienda pública antes de pedir por WhatsApp
    setState(() => _enviando = true);
    List<ProductoSinStock> sinStock;
    try {
      sinStock = await catalogo.repo.verificarStockSucursal(
        sucursalId: sucursal.id,
        cantidades: {for (final i in carrito.items) i.product.id: i.qty},
      );
    } catch (_) {
      if (mounted) {
        setState(() => _enviando = false);
        showToast(context, 'Error verificando disponibilidad',
            icon: Icons.error_outline);
      }
      return;
    }
    if (!mounted) return;
    setState(() => _enviando = false);

    if (sinStock.isNotEmpty) {
      final accion = await _dialogoSinStock(sucursal, sinStock);
      if (!mounted) return;
      if (accion == _AccionSinStock.cancelar || accion == null) {
        showToast(context, 'Pedido cancelado', icon: Icons.info_outline);
        return;
      }
      if (accion == _AccionSinStock.editar) {
        showToast(context, 'Puedes ajustar las cantidades del pedido',
            icon: Icons.edit_outlined);
        return;
      }
      carrito.quitarProductos(sinStock.map((p) => p.productoId));
      if (carrito.vacio) {
        showToast(context, 'Todos los productos fueron removidos por falta de stock',
            icon: Icons.warning_amber_rounded);
        return;
      }
    }

    // El pedido va al WhatsApp de la agencia seleccionada (con prefijo 591);
    // si la agencia no tiene número, se usa el general del .env
    var destino = numeroWhatsApp(sucursal.whatsapp);
    if (destino.isEmpty) {
      destino = numeroWhatsApp(dotenv.env['WHATSAPP'] ?? '');
    }
    if (destino.isEmpty) {
      showToast(context, 'No hay un número de WhatsApp configurado',
          icon: Icons.error_outline);
      return;
    }

    // El pedido se registra en la tabla `orders` del backend y el número
    // que genera el servidor (PEDIDOWEB_Nº…) es el código del pedido:
    // con ese mismo número caja lo recupera en Ventas (/sale) del panel.
    setState(() => _enviando = true);
    var codigo = '';
    try {
      codigo = await catalogo.repo.crearOrden(
        items: carrito.items,
        sucursalId: sucursal.id,
        sucursalNombre: sucursal.nombre,
      );
    } catch (_) {
      // sin conexión igual se envía por WhatsApp con un código local
    }
    if (!mounted) return;
    setState(() => _enviando = false);
    if (codigo.isEmpty) {
      codigo = await pedidosVM.siguienteCodigo();
      if (!mounted) return;
    }

    final pedido = carrito.construirPedido(
      sucursal: sucursal,
      codigo: codigo,
      whatsappDestino: destino,
    );
    final uri = Uri.parse(
      'https://wa.me/$destino?text=${Uri.encodeComponent(mensajeWhatsAppPedido(pedido))}',
    );

    final abierto = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted) return;
    if (!abierto) {
      showToast(context, 'No se pudo abrir WhatsApp',
          icon: Icons.error_outline);
      return;
    }

    await pedidosVM.guardar(pedido);
    carrito.limpiar();
    _obsCtl.clear();
    if (!mounted) return;
    showToast(context, 'Pedido $codigo enviado por WhatsApp');
    widget.onVerPedidos();
  }

  /// Diálogo con los productos que no tienen stock suficiente en la
  /// sucursal, con las mismas opciones que la tienda pública.
  Future<_AccionSinStock?> _dialogoSinStock(
    Sucursal sucursal,
    List<ProductoSinStock> sinStock,
  ) {
    return showDialog<_AccionSinStock>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        titlePadding: const EdgeInsets.fromLTRB(20, 18, 20, 0),
        contentPadding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded,
                color: AppColors.warnFg, size: 22),
            SizedBox(width: 8),
            Expanded(
              child: Text(
                'Productos sin stock disponible',
                style: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w800),
              ),
            ),
          ],
        ),
        content: SizedBox(
          width: double.maxFinite,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text.rich(
                  TextSpan(
                    text:
                        'Los siguientes productos no tienen stock suficiente en la sucursal ',
                    children: [
                      TextSpan(
                        text: '${sucursal.nombre}:',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                  style: const TextStyle(fontSize: 12.5, height: 1.4),
                ),
                const SizedBox(height: 10),
                ...sinStock.map(
                  (p) => Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 9),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF5F5),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFFFCDD2)),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                p.nombre,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Stock disponible: ${p.stockDisponible} | Pedido: ${p.cantidadSolicitada}',
                                style: const TextStyle(
                                  fontSize: 11,
                                  color: AppColors.muted2,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.badFg,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            'Faltan: ${p.faltan}',
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  '¿Qué deseas hacer?',
                  style: TextStyle(fontSize: 11.5, color: AppColors.muted),
                ),
              ],
            ),
          ),
        ),
        actionsPadding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, _AccionSinStock.cancelar),
            child: const Text(
              'Cancelar todo',
              style: TextStyle(
                color: AppColors.badFg,
                fontWeight: FontWeight.w700,
                fontSize: 12.5,
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, _AccionSinStock.editar),
            child: const Text(
              'Editar cantidades',
              style: TextStyle(
                color: AppColors.primaryDark,
                fontWeight: FontWeight.w700,
                fontSize: 12.5,
              ),
            ),
          ),
          ElevatedButton(
            onPressed: () =>
                Navigator.pop(ctx, _AccionSinStock.eliminarYEnviar),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.waDark,
              foregroundColor: Colors.white,
              elevation: 0,
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            child: const Text(
              'Eliminar sin stock y enviar',
              style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final carrito = context.watch<CarritoViewModel>();
    final catalogo = context.watch<CatalogoViewModel>();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
      children: [
        const Text(
          'Nuevo pedido',
          style: TextStyle(
            fontSize: 21,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        const SizedBox(height: 4),
        const Text(
          'Arma tu pedido y envíalo por WhatsApp',
          style: TextStyle(fontSize: 12, color: AppColors.muted),
        ),
        const SizedBox(height: 16),

        // Formulario
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.line),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const _Label('SUCURSAL'),
              const SizedBox(height: 6),
              DropdownButtonFormField<int>(
                initialValue: catalogo.sucursales
                        .any((s) => s.id == carrito.agenciaId)
                    ? carrito.agenciaId
                    : null,
                items: catalogo.sucursales
                    .map(
                      (s) => DropdownMenuItem(
                        value: s.id,
                        child: Text(
                          s.nombre,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13.5,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    )
                    .toList(),
                onChanged: (v) {
                  carrito.setAgencia(v);
                  final faltan = carrito.itemsSinStockEnSucursal;
                  if (faltan.isNotEmpty) {
                    showToast(
                      context,
                      faltan.length == 1
                          ? '1 producto sin stock suficiente en esta sucursal'
                          : '${faltan.length} productos sin stock suficiente en esta sucursal',
                      icon: Icons.warning_amber_rounded,
                    );
                  }
                },
                icon: const Icon(Icons.keyboard_arrow_down,
                    color: AppColors.muted2),
                hint: const Text('Selecciona una sucursal'),
              ),
              const SizedBox(height: 14),
              const _Label('PRIORIDAD'),
              const SizedBox(height: 6),
              Row(
                children: [
                  Expanded(
                    child: _PrioBtn(
                      texto: 'Normal',
                      activo: carrito.prioridad == 'NORMAL',
                      colorActivo: AppColors.ink,
                      textoActivo: AppColors.primaryPale,
                      onTap: () => carrito.setPrioridad('NORMAL'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _PrioBtn(
                      texto: 'Urgente',
                      activo: carrito.prioridad == 'URGENTE',
                      colorActivo: AppColors.badFg,
                      textoActivo: Colors.white,
                      onTap: () => carrito.setPrioridad('URGENTE'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              const _Label('OBSERVACIONES'),
              const SizedBox(height: 6),
              TextField(
                controller: _obsCtl,
                decoration: const InputDecoration(
                  hintText: 'Ej. Reposición semanal…',
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 20),
        const Text(
          'Ítems del pedido',
          style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 10),

        if (carrito.vacio)
          Container(
            padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: const Color(0xFFC9D9EE),
                width: 1.5,
              ),
            ),
            child: Column(
              children: [
                const Icon(Icons.shopping_basket_outlined,
                    size: 26, color: Color(0xFFBCD2EC)),
                const SizedBox(height: 10),
                const Text(
                  'Aún no añadiste productos',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: AppColors.muted,
                  ),
                ),
                const SizedBox(height: 12),
                ElevatedButton(
                  onPressed: widget.onExplorar,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.ink,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 18, vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text(
                    'Explorar productos',
                    style:
                        TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ),
          )
        else ...[
          ...carrito.items.map(
            (item) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding:
                  const EdgeInsets.symmetric(horizontal: 13, vertical: 11),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.line),
              ),
              child: Row(
                children: [
                  ProductThumb(
                    categoria: item.product.categoria,
                    imagen: item.product.imagen,
                    size: 40,
                    radius: 11,
                    iconSize: 17,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.product.nombre,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 1),
                        Text(
                          bs(item.subtotal),
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primaryDark,
                          ),
                        ),
                        // Disponibilidad en la sucursal seleccionada
                        if (carrito.stockEnSucursal(item.product)
                            case final int stockSuc) ...[
                          const SizedBox(height: 2),
                          Text(
                            stockSuc >= item.qty
                                ? 'Disponible en la sucursal'
                                : 'Stock disponible: $stockSuc | Pedido: ${item.qty}',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: stockSuc >= item.qty
                                  ? AppColors.okFg
                                  : AppColors.badFg,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  _QtyBtn(
                    icon: Icons.remove,
                    onTap: () =>
                        carrito.cambiarCantidad(item.product.id, -1),
                  ),
                  SizedBox(
                    width: 30,
                    child: Text(
                      '${item.qty}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  _QtyBtn(
                    icon: Icons.add,
                    onTap: () => carrito.cambiarCantidad(item.product.id, 1),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(17),
            decoration: BoxDecoration(
              color: AppColors.ink,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Total estimado',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Colors.white70,
                      ),
                    ),
                    Text(
                      bs(carrito.total),
                      style: const TextStyle(
                        fontSize: 19,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primaryLight,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                GradientButton(
                  texto: 'Pedir por WhatsApp',
                  iconWidget: const WhatsAppIcon(size: 19),
                  colores: const [AppColors.wa, AppColors.waDark],
                  cargando: _enviando,
                  onPressed: _enviarPorWhatsApp,
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}

class _Label extends StatelessWidget {
  final String texto;

  const _Label(this.texto);

  @override
  Widget build(BuildContext context) {
    return Text(
      texto,
      style: const TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        letterSpacing: .8,
        color: AppColors.muted,
      ),
    );
  }
}

class _PrioBtn extends StatelessWidget {
  final String texto;
  final bool activo;
  final Color colorActivo;
  final Color textoActivo;
  final VoidCallback onTap;

  const _PrioBtn({
    required this.texto,
    required this.activo,
    required this.colorActivo,
    required this.textoActivo,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 11),
        decoration: BoxDecoration(
          color: activo ? colorActivo : AppColors.cream,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: activo ? colorActivo : AppColors.line2,
            width: 1.5,
          ),
        ),
        child: Text(
          texto,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: activo ? textoActivo : AppColors.muted2,
          ),
        ),
      ),
    );
  }
}

class _QtyBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _QtyBtn({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(9),
      onTap: onTap,
      child: Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          color: AppColors.cream,
          borderRadius: BorderRadius.circular(9),
          border: Border.all(color: AppColors.line2),
        ),
        child: Icon(icon, size: 14, color: AppColors.ink),
      ),
    );
  }
}
