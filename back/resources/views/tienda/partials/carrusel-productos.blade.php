{{-- Carrusel horizontal de productos (scroll-snap + flechas/puntos con JS). Requiere $productos --}}
<div class="pcar" data-pcar>
  <div class="pcar-track" data-pcar-track>
    @foreach($productos as $p)
      <div class="pcar-item">@include('tienda.partials.card', ['p' => $p])</div>
    @endforeach
  </div>
  <div class="dots" data-pcar-dots></div>
</div>
