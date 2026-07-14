import 'package:flutter_test/flutter_test.dart';
import 'package:san2/data/models/product.dart';

void main() {
  test('backend antiguo: aplica el descuento en la app', () {
    // El backend viejo manda el precio base sin descontar
    final p = Product.fromJson({
      'id': 1,
      'nombre': 'PARACETAMOL 100 MG',
      'precio': 11.96,
      'precio_antes': 0,
      'porcentaje': 15,
      'en_oferta': true,
    });
    expect(p.precioAntes, 11.96);
    expect(p.precio, 10.17);
    expect(p.descuento, 15);
  });

  test('backend nuevo: respeta el precio ya descontado', () {
    final p = Product.fromJson({
      'id': 2,
      'nombre': 'BUCLOREX',
      'precio': 24.5,
      'precio_antes': 32.67,
      'porcentaje': 25,
      'en_oferta': true,
    });
    expect(p.precio, 24.5);
    expect(p.precioAntes, 32.67);
    expect(p.descuento, 25);
  });

  test('sin oferta: no inventa precio tachado', () {
    final p = Product.fromJson({
      'id': 3,
      'nombre': 'ALCOHOL',
      'precio': 8.0,
      'precio_antes': 0,
      'porcentaje': 0,
    });
    expect(p.precio, 8.0);
    expect(p.precioAntes, 0);
    expect(p.descuento, 0);
  });
}
