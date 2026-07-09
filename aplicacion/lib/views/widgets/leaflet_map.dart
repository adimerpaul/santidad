import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../data/models/sucursal.dart';

/// Permite centrar el mapa desde fuera (botón "Ver en mapa").
class LeafletMapController {
  WebViewController? _web;

  void centrarEn(int index) {
    _web?.runJavaScript('centrarEn($index);');
  }
}

/// Mapa Leaflet real (1.9.4) dentro de un WebView, con tiles de
/// OpenStreetMap y marcadores tipo circleMarker como el diseño de referencia.
class LeafletMap extends StatefulWidget {
  final List<Sucursal> sucursales;
  final LeafletMapController controller;

  const LeafletMap({
    super.key,
    required this.sucursales,
    required this.controller,
  });

  @override
  State<LeafletMap> createState() => _LeafletMapState();
}

class _LeafletMapState extends State<LeafletMap> {
  late final WebViewController _web;

  // Centro por defecto: Oruro, Bolivia.
  static const _latOruro = -17.9605;
  static const _lngOruro = -67.1075;

  @override
  void initState() {
    super.initState();
    _web = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFFEFF4FA))
      ..loadHtmlString(_html());
    widget.controller._web = _web;
  }

  String _html() {
    final conUbicacion =
        widget.sucursales.where((s) => s.tieneUbicacion).toList();
    final datos = jsonEncode(conUbicacion
        .map((s) => {
              'nombre': s.nombre,
              'dir': s.direccion,
              'lat': s.latitud,
              'lng': s.longitud,
            })
        .toList());
    final lat = conUbicacion.isNotEmpty ? conUbicacion.first.latitud : _latOruro;
    final lng =
        conUbicacion.isNotEmpty ? conUbicacion.first.longitud : _lngOruro;

    return '''
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  html,body,#map{margin:0;padding:0;height:100%;width:100%;background:#EFF4FA}
  .leaflet-popup-content{font-family:sans-serif;font-size:12px}
</style>
</head>
<body>
<div id="map"></div>
<script>
  var SUCS = $datos;
  var map = L.map('map', { scrollWheelZoom: false }).setView([$lat, $lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  var markers = [];
  SUCS.forEach(function (s) {
    var m = L.circleMarker([s.lat, s.lng], {
      radius: 9, color: '#1E5AA8', weight: 2.5,
      fillColor: '#2F6FC1', fillOpacity: .85
    }).addTo(map);
    m.bindPopup('<b>' + s.nombre + '</b><br>' + s.dir);
    markers.push(m);
  });
  function centrarEn(i) {
    var s = SUCS[i];
    if (!s) return;
    map.setView([s.lat, s.lng], 16);
    markers[i].openPopup();
  }
</script>
</body>
</html>
''';
  }

  @override
  Widget build(BuildContext context) {
    return WebViewWidget(controller: _web);
  }
}
