import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/pedido.dart';
import '../viewmodels/pedidos_viewmodel.dart';
import 'widgets/ui_widgets.dart';

class PedidosView extends StatelessWidget {
  const PedidosView({super.key});

  Future<void> _reenviar(BuildContext context, Pedido pd) async {
    var destino = pd.whatsapp.replaceAll(RegExp(r'\D'), '');
    if (destino.isEmpty) {
      destino = (dotenv.env['WHATSAPP'] ?? '').replaceAll(RegExp(r'\D'), '');
    }
    if (destino.isEmpty) {
      showToast(context, 'No hay un número de WhatsApp configurado',
          icon: Icons.error_outline);
      return;
    }
    final uri = Uri.parse(
      'https://wa.me/$destino?text=${Uri.encodeComponent(mensajeWhatsAppPedido(pd))}',
    );
    final abierto = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!abierto && context.mounted) {
      showToast(context, 'No se pudo abrir WhatsApp',
          icon: Icons.error_outline);
    }
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<PedidosViewModel>();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 14, 16, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Mis pedidos',
                style: TextStyle(
                  fontSize: 21,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -.4,
                ),
              ),
              SizedBox(height: 4),
              Text(
                'Historial de pedidos enviados por WhatsApp',
                style: TextStyle(fontSize: 12, color: AppColors.muted),
              ),
              SizedBox(height: 12),
            ],
          ),
        ),
        Expanded(
          child: RefreshIndicator(
            color: AppColors.primary,
            onRefresh: () => vm.cargar(),
            child: vm.cargando && vm.pedidos.isEmpty
                ? const Center(
                    child: CircularProgressIndicator(strokeWidth: 2.5))
                : vm.pedidos.isEmpty
                    ? ListView(
                        children: const [
                          SizedBox(height: 60),
                          EmptyState(
                            icon: Icons.assignment_outlined,
                            mensaje: 'Todavía no enviaste pedidos',
                          ),
                        ],
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 110),
                        itemCount: vm.pedidos.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 8),
                        itemBuilder: (context, i) {
                          final pd = vm.pedidos[i];
                          return Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 12),
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
                                    Text(
                                      pd.codigo,
                                      style: const TextStyle(
                                        fontSize: 13.5,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    const _EstadoChip(
                                      texto: 'ENVIADO',
                                      bg: AppColors.infoBg,
                                      fg: AppColors.infoFg,
                                    ),
                                    if (pd.urgente) ...[
                                      const SizedBox(width: 6),
                                      const _EstadoChip(
                                        texto: 'URGENTE',
                                        bg: AppColors.badBg,
                                        fg: AppColors.badFg,
                                      ),
                                    ],
                                    const Spacer(),
                                    Text(
                                      pd.fecha,
                                      style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
                                        color: AppColors.muted,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    const Icon(Icons.store,
                                        size: 11, color: AppColors.skyInk),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        pd.sucursal,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.muted2,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                ...pd.detalles.map(
                                  (d) => Padding(
                                    padding: const EdgeInsets.only(bottom: 3),
                                    child: Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            '${d.cantidad}× ${d.producto}',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(
                                              fontSize: 11.5,
                                              color: AppColors.muted2,
                                            ),
                                          ),
                                        ),
                                        Text(
                                          bs(d.subtotal),
                                          style: const TextStyle(
                                            fontSize: 11.5,
                                            fontWeight: FontWeight.w700,
                                            color: AppColors.muted2,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                Container(
                                  margin: const EdgeInsets.only(top: 7),
                                  padding: const EdgeInsets.only(top: 10),
                                  decoration: const BoxDecoration(
                                    border: Border(
                                      top: BorderSide(color: AppColors.line),
                                    ),
                                  ),
                                  child: Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        '${pd.items} ${pd.items == 1 ? 'ítem' : 'ítems'}',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.muted,
                                        ),
                                      ),
                                      Text(
                                        bs(pd.total),
                                        style: const TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w800,
                                          color: AppColors.primaryDark,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 12),
                                SizedBox(
                                  width: double.infinity,
                                  height: 40,
                                  child: TextButton.icon(
                                    onPressed: () => _reenviar(context, pd),
                                    style: TextButton.styleFrom(
                                      backgroundColor: AppColors.wa,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(13),
                                      ),
                                    ),
                                    icon: const Icon(Icons.chat, size: 15),
                                    label: const Text(
                                      'Reenviar por WhatsApp',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ),
      ],
    );
  }
}

class _EstadoChip extends StatelessWidget {
  final String texto;
  final Color bg;
  final Color fg;

  const _EstadoChip({required this.texto, required this.bg, required this.fg});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        texto,
        style: TextStyle(
          fontSize: 9.5,
          fontWeight: FontWeight.w800,
          color: fg,
        ),
      ),
    );
  }
}
