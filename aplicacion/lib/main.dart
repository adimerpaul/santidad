import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/app_theme.dart';
import 'data/repositories/catalogo_repository.dart';
import 'viewmodels/carrito_viewmodel.dart';
import 'viewmodels/catalogo_viewmodel.dart';
import 'viewmodels/pedidos_viewmodel.dart';
import 'views/shell_view.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Release (flutter build) usa .env.production; debug/profile usan .env
  await dotenv.load(fileName: kReleaseMode ? '.env.production' : '.env');

  final api = ApiClient();

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => CatalogoViewModel(CatalogoRepository(api)),
        ),
        ChangeNotifierProvider(create: (_) => CarritoViewModel()),
        ChangeNotifierProvider(create: (_) => PedidosViewModel()),
      ],
      child: const SantidadApp(),
    ),
  );
}

class SantidadApp extends StatelessWidget {
  const SantidadApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Farmacias Santidad-Divina S.R.L.',
      debugShowCheckedModeBanner: false,
      theme: buildAppTheme(),
      home: const ShellView(),
    );
  }
}
