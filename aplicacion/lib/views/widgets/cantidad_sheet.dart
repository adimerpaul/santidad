import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/app_theme.dart';
import '../../core/formatters.dart';
import '../../data/models/product.dart';
import 'ui_widgets.dart';

/// Modal para elegir la cantidad antes de añadir un producto al pedido.
/// Devuelve la cantidad elegida, o null si el usuario cancela.
Future<int?> mostrarSelectorCantidad(BuildContext context, Product producto) {
  return showModalBottomSheet<int>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _CantidadSheet(producto: producto),
  );
}

class _CantidadSheet extends StatefulWidget {
  final Product producto;

  const _CantidadSheet({required this.producto});

  @override
  State<_CantidadSheet> createState() => _CantidadSheetState();
}

class _CantidadSheetState extends State<_CantidadSheet> {
  static const _maxCantidad = 999;

  int _qty = 1;
  late final TextEditingController _ctrl = TextEditingController(text: '1');

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _setQty(int valor) {
    final nuevo = valor.clamp(1, _maxCantidad);
    setState(() => _qty = nuevo);
    _ctrl.value = TextEditingValue(
      text: '$nuevo',
      selection: TextSelection.collapsed(offset: '$nuevo'.length),
    );
  }

  void _onTextChanged(String texto) {
    final valor = int.tryParse(texto);
    if (valor == null) return;
    setState(() => _qty = valor.clamp(1, _maxCantidad));
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.producto;

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        decoration: const BoxDecoration(
          color: AppColors.cream,
          borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 14),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppColors.line2,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
                const SizedBox(height: 14),

                // Producto
                Row(
                  children: [
                    ProductThumb(
                      categoria: p.categoria,
                      imagen: p.imagen,
                      size: 56,
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
                              fontSize: 13.5,
                              fontWeight: FontWeight.w800,
                              color: AppColors.ink,
                            ),
                          ),
                          if (p.presentacion.trim().isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              p.presentacion,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11.5,
                                color: AppColors.muted,
                              ),
                            ),
                          ],
                          const SizedBox(height: 4),
                          PriceRow(
                            precio: p.precio,
                            precioAntes: p.precioAntes,
                            descuento: p.descuento,
                            fontSize: 13.5,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Selector de cantidad
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.line),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Cantidad',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                          color: AppColors.muted2,
                        ),
                      ),
                      Row(
                        children: [
                          _PasoBoton(
                            icon: Icons.remove,
                            onTap: _qty > 1 ? () => _setQty(_qty - 1) : null,
                          ),
                          SizedBox(
                            width: 58,
                            child: TextField(
                              controller: _ctrl,
                              onChanged: _onTextChanged,
                              textAlign: TextAlign.center,
                              keyboardType: TextInputType.number,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly,
                                LengthLimitingTextInputFormatter(3),
                              ],
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: AppColors.ink,
                              ),
                              decoration: const InputDecoration(
                                isDense: true,
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.zero,
                              ),
                            ),
                          ),
                          _PasoBoton(
                            icon: Icons.add,
                            onTap: _qty < _maxCantidad
                                ? () => _setQty(_qty + 1)
                                : null,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 10),

                // Precio unitario y subtotal
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [AppColors.primarySoft, AppColors.cream],
                    ),
                    border: Border.all(color: AppColors.primaryPale),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Precio unitario',
                            style: TextStyle(
                              fontSize: 12,
                              color: AppColors.muted2,
                            ),
                          ),
                          Text(
                            bs(p.precio),
                            style: const TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w700,
                              color: AppColors.inkSoft,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Subtotal ($_qty und)',
                            style: const TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w800,
                              color: AppColors.ink,
                            ),
                          ),
                          Text(
                            bs(p.precio * _qty),
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w900,
                              color: AppColors.primaryDark,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(context),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.muted2,
                          minimumSize: const Size.fromHeight(50),
                          side: const BorderSide(color: AppColors.line2),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: const Text(
                          'Cancelar',
                          style: TextStyle(
                            fontSize: 13.5,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: GradientButton(
                        texto: 'Añadir al pedido',
                        icon: Icons.add_shopping_cart,
                        onPressed: () => Navigator.pop(context, _qty),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Botón redondo de -/+ del selector de cantidad.
class _PasoBoton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;

  const _PasoBoton({required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    final habilitado = onTap != null;
    return Material(
      color: habilitado ? AppColors.primarySoft : AppColors.cream2,
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 36,
          height: 36,
          child: Icon(
            icon,
            size: 18,
            color: habilitado ? AppColors.primaryDark : AppColors.muted,
          ),
        ),
      ),
    );
  }
}
