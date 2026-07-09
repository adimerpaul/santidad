import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/sucursal.dart';
import '../viewmodels/carrito_viewmodel.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import '../viewmodels/pedidos_viewmodel.dart';
import 'widgets/ui_widgets.dart';

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

    // Número de la sucursal; si no tiene, el WhatsApp general del .env
    var destino = sucursal.whatsapp.replaceAll(RegExp(r'\D'), '');
    if (destino.isEmpty) {
      destino = (dotenv.env['WHATSAPP'] ?? '').replaceAll(RegExp(r'\D'), '');
    }
    if (destino.isEmpty) {
      showToast(context, 'No hay un número de WhatsApp configurado',
          icon: Icons.error_outline);
      return;
    }

    final codigo = await pedidosVM.siguienteCodigo();
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
                onChanged: (v) => carrito.setAgencia(v),
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
                  texto: 'Enviar pedido por WhatsApp',
                  icon: Icons.send,
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
