import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_theme.dart';
import '../core/formatters.dart';
import '../data/models/sucursal.dart';
import '../viewmodels/catalogo_viewmodel.dart';
import 'widgets/leaflet_map.dart';
import 'widgets/ui_widgets.dart';

class SucursalesView extends StatefulWidget {
  const SucursalesView({super.key});

  @override
  State<SucursalesView> createState() => _SucursalesViewState();
}

class _SucursalesViewState extends State<SucursalesView> {
  final _mapCtl = LeafletMapController();

  Future<void> _abrirWhatsApp(String numero) async {
    final limpio = numeroWhatsApp(numero);
    if (limpio.isEmpty) {
      showToast(context, 'Esta sucursal no tiene WhatsApp registrado',
          icon: Icons.error_outline);
      return;
    }
    final uri = Uri.parse('https://wa.me/$limpio');
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        showToast(context, 'No se pudo abrir WhatsApp',
            icon: Icons.error_outline);
      }
    }
  }

  void _verEnMapa(Sucursal s, List<Sucursal> sucursales) {
    if (!s.tieneUbicacion) {
      showToast(context, 'Esta sucursal no tiene ubicación registrada',
          icon: Icons.error_outline);
      return;
    }
    // Índice dentro de la lista que dibuja el mapa (solo con ubicación)
    final conUbicacion =
        sucursales.where((x) => x.tieneUbicacion).toList();
    final index = conUbicacion.indexWhere((x) => x.id == s.id);
    if (index >= 0) _mapCtl.centrarEn(index);
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<CatalogoViewModel>();
    final sucursales = vm.sucursales;

    if (vm.cargandoInicio && sucursales.isEmpty) {
      return const Center(child: CircularProgressIndicator(strokeWidth: 2.5));
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
      children: [
        const Text(
          'Sucursales',
          style: TextStyle(
            fontSize: 21,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          '${sucursales.length} ${sucursales.length == 1 ? 'punto' : 'puntos'} de atención en Oruro',
          style: const TextStyle(fontSize: 12, color: AppColors.muted),
        ),
        const SizedBox(height: 16),

        // Mapa Leaflet (WebView + OpenStreetMap)
        if (sucursales.isNotEmpty)
          Container(
            height: 230,
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.line),
            ),
            child: LeafletMap(
              sucursales: sucursales,
              controller: _mapCtl,
            ),
          ),
        const SizedBox(height: 12),

        // Tarjetas de sucursales
        ...sucursales.map(
          (s) => Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.line),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: AppColors.sky,
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: const Icon(Icons.home_work,
                          color: AppColors.skyInk, size: 19),
                    ),
                    const SizedBox(width: 11),
                    Expanded(
                      child: Text(
                        s.nombre,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 9, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.okBg,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: const Text(
                        'ACTIVO',
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                          color: AppColors.okFg,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                if (s.direccion.isNotEmpty)
                  _InfoLinea(icon: Icons.location_on, texto: s.direccion),
                if (s.telefono.isNotEmpty)
                  _InfoLinea(icon: Icons.phone, texto: s.telefono),
                if (s.horario.isNotEmpty)
                  _InfoLinea(icon: Icons.schedule, texto: s.horario),
                const SizedBox(height: 11),
                Row(
                  children: [
                    Expanded(
                      child: _AccionBtn(
                        icon: Icons.chat,
                        texto: 'WhatsApp',
                        bg: AppColors.wa,
                        fg: Colors.white,
                        onTap: () => _abrirWhatsApp(s.whatsapp),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _AccionBtn(
                        icon: Icons.map,
                        texto: 'Ver en mapa',
                        bg: AppColors.primarySoft,
                        fg: AppColors.primaryDeep,
                        onTap: () => _verEnMapa(s, sucursales),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _InfoLinea extends StatelessWidget {
  final IconData icon;
  final String texto;

  const _InfoLinea({required this.icon, required this.texto});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 18,
            child: Icon(icon, size: 13, color: AppColors.primaryDark),
          ),
          const SizedBox(width: 4),
          Expanded(
            child: Text(
              texto,
              style: const TextStyle(fontSize: 12, color: AppColors.muted2),
            ),
          ),
        ],
      ),
    );
  }
}

class _AccionBtn extends StatelessWidget {
  final IconData icon;
  final String texto;
  final Color bg;
  final Color fg;
  final VoidCallback onTap;

  const _AccionBtn({
    required this.icon,
    required this.texto,
    required this.bg,
    required this.fg,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: TextButton.icon(
        onPressed: onTap,
        style: TextButton.styleFrom(
          backgroundColor: bg,
          foregroundColor: fg,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        icon: Icon(icon, size: 15),
        label: Text(
          texto,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
        ),
      ),
    );
  }
}
