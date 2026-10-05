@extends('tienda.layout')

@section('title', 'Sucursales en Oruro · Farmacias Santidad Divina')
@section('description', 'Direcciones, teléfonos, horarios y mapa de las ' . $sucursales->count() . ' sucursales de Farmacias Santidad Divina en Oruro, incluida la farmacia de turno 24 horas.')
@section('canonical', route('tienda.sucursales'))

@section('content')
<div class="page" style="padding-bottom:0">
  <nav class="crumb" aria-label="Ruta"><a href="{{ route('tienda.home') }}">Inicio</a><span>/</span><span>Sucursales</span></nav>
</div>
<div class="wrap">
  @include('tienda.partials.sucursales', ['sucursales' => $sucursales, 'titulo' => 'Sucursales de Farmacias Santidad Divina en Oruro', 'tag' => 'h1'])
</div>
@endsection
