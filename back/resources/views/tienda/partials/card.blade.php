{{-- Tarjeta de producto. Requiere $p (TiendaWebService::presentar) --}}
@php $T = app(\App\Services\TiendaWebService::class); @endphp
<article class="pcard">
  <a href="{{ $p->url }}" class="pcard-img" tabindex="-1" aria-hidden="true">
    <img src="{{ $p->img }}" alt="{{ $p->nombre }}" loading="lazy" width="300" height="300" onerror="this.onerror=null;this.src='{{ asset('images/productDefault.jpg') }}'">
    @if($p->descuento)<span class="pcard-disc">-{{ $p->pct }}%</span>@endif
    @if($p->topVentas)<span class="pcard-top">Top ventas</span>@endif
  </a>
  <div class="pcard-body">
    <span class="pcard-lab ellipsis">{{ $p->lab ?: 'Santidad Divina' }}</span>
    <h3 class="pcard-name"><a href="{{ $p->url }}">{{ $p->nombre }}</a></h3>
    <span class="pcard-avail" style="color:{{ $p->colorDisp }}"><i class="ph-duotone ph-package" aria-hidden="true"></i>{{ $p->disponibilidad }}</span>
    <span class="pcard-price">
      <strong class="tnum">{{ $T::bs($p->precio) }}</strong>
      @if($p->descuento)<s class="tnum">{{ $T::bs($p->antes) }}</s>@endif
    </span>
    <button type="button" class="btn btn-primary pcard-add" data-add='@json($T->datosCarrito($p))' @disabled($p->agotado)>
      <i class="ph-duotone ph-shopping-cart-simple" aria-hidden="true"></i>{{ $p->agotado ? 'Agotado' : 'Añadir' }}
    </button>
  </div>
</article>
