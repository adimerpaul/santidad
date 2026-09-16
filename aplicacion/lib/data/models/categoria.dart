class Categoria {
  final int id;
  final String name;

  Categoria({required this.id, required this.name});

  factory Categoria.fromJson(Map<String, dynamic> json) =>
      Categoria(id: json['id'] ?? 0, name: json['name'] ?? '');
}
