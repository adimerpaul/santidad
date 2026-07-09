class Sucursal {
  final int id;
  final String nombre;
  final String direccion;
  final String telefono;
  final String whatsapp;
  final String horario;
  final double? latitud;
  final double? longitud;

  Sucursal({
    required this.id,
    required this.nombre,
    required this.direccion,
    required this.telefono,
    required this.whatsapp,
    required this.horario,
    this.latitud,
    this.longitud,
  });

  bool get tieneUbicacion => latitud != null && longitud != null;

  factory Sucursal.fromJson(Map<String, dynamic> json) => Sucursal(
        id: json['id'] ?? 0,
        nombre: json['nombre'] ?? '',
        direccion: json['direccion'] ?? '',
        telefono: json['telefono'] ?? '',
        whatsapp: json['whatsapp'] ?? '',
        horario: json['horario'] ?? json['atencion'] ?? '',
        latitud: double.tryParse('${json['latitud']}'),
        longitud: double.tryParse('${json['longitud']}'),
      );
}
