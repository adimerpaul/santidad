import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../../core/formatters.dart';

/// Carrusel de imágenes como el de la tienda pública (Swiper):
/// autoplay cada 5 s, bucle infinito y puntos indicadores.
class HeroCarousel extends StatefulWidget {
  /// Nombres de archivo dentro de public/images del backend.
  final List<String> imagenes;

  const HeroCarousel({super.key, required this.imagenes});

  @override
  State<HeroCarousel> createState() => _HeroCarouselState();
}

class _HeroCarouselState extends State<HeroCarousel> {
  late final PageController _ctl;
  Timer? _timer;
  int _pagina = 0;

  @override
  void initState() {
    super.initState();
    // Arranca lejos del 0 para poder retroceder también en el bucle
    _pagina = widget.imagenes.length * 100;
    _ctl = PageController(initialPage: _pagina);
    _programarAutoplay();
  }

  void _programarAutoplay() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted || widget.imagenes.length < 2 || !_ctl.hasClients) return;
      _ctl.animateToPage(
        _pagina + 1,
        duration: const Duration(milliseconds: 700),
        curve: Curves.easeOutCubic,
      );
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _ctl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final imgs = widget.imagenes;
    if (imgs.isEmpty) return const SizedBox.shrink();
    final activa = _pagina % imgs.length;

    return Column(
      children: [
        AspectRatio(
          aspectRatio: 16 / 8,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: GestureDetector(
              // Reinicia el autoplay cuando el usuario desliza manualmente
              onPanDown: (_) => _programarAutoplay(),
              child: PageView.builder(
                controller: _ctl,
                onPageChanged: (i) => setState(() => _pagina = i),
                itemBuilder: (_, i) {
                  final url = urlImagenProducto(imgs[i % imgs.length]);
                  return Container(
                    color: AppColors.sky,
                    child: url == null
                        ? const Icon(
                            Icons.local_pharmacy,
                            color: AppColors.skyInk,
                            size: 34,
                          )
                        : Image.network(
                            url,
                            fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => const Icon(
                              Icons.local_pharmacy,
                              color: AppColors.skyInk,
                              size: 34,
                            ),
                          ),
                  );
                },
              ),
            ),
          ),
        ),
        if (imgs.length > 1) ...[
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(imgs.length, (i) {
              final activo = i == activa;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: activo ? 18 : 7,
                height: 7,
                decoration: BoxDecoration(
                  color: activo
                      ? AppColors.primaryDark
                      : AppColors.primaryDark.withValues(alpha: .25),
                  borderRadius: BorderRadius.circular(999),
                ),
              );
            }),
          ),
        ],
      ],
    );
  }
}
