@extends('tienda.layout')
@php $T = \App\Services\TiendaWebService::class; @endphp

@section('title', $titulo . ($pagina > 1 ? ' · página ' . $pagina : '') . ' | Farmacias Santidad Divina Oruro')
@section('description', $categoria && !$filtros['q']
  ? $categoria->nombre . ' en Farmacias Santidad Divina, Oruro: ' . $total . ' productos a precio solidario con stock por sucursal. Pide por WhatsApp.'
  : $titulo . ' en Farmacias Santidad Divina, Oruro. ' . $total . ' productos con precio, descuento y disponibilidad por sucursal.')
@section('canonical', $canonical)
@if($noindex)
  @section('robots', 'noindex, follow')
@endif

@push('head')
  @if($pagina > 1)<link rel="prev" href="{{ $pageUrl($pagina - 1) }}">@endif
  @if($pagina < $ultima)<link rel="next" href="{{ $pageUrl($pagina + 1) }}">@endif
  <script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('tienda.home') . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $titulo, 'item' => $canonical],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<div class="page">
  <nav class="crumb" aria-label="Ruta"><a href="{{ route('tienda.home') }}">Inicio</a><span>/</span><span>{{ $categoria ? $categoria->nombre : ($filtros['descuento'] ? 'Descuentos' : 'Búsqueda') }}</span></nav>
  <h1>{{ $titulo }}</h1>
  <p class="muted" style="margin:8px 0 0;font-size:15px">{{ $total }} {{ $total === 1 ? 'producto' : 'productos' }}@if($ultima > 1) · página {{ $pagina }} de {{ $ultima }}@endif</p>

  <form class="filters" method="get" action="{{ route('tienda.buscar') }}" data-filters>
    @if($filtros['q'] !== '')<input type="hidden" name="q" value="{{ $filtros['q'] }}">@endif
    <div class="field"><label for="f-cat">Categoría</label>
      <select id="f-cat" name="categoria" class="input">
        <option value="">Todas</option>
        @foreach($categorias as $c)
          <option value="{{ $c->id }}" @selected($filtros['categoria'] === $c->id)>{{ $c->nombre }}</option>
        @endforeach
      </select></div>
    <div class="field"><label for="f-suc">Disponible en</label>
      <select id="f-suc" name="sucursal" class="input">
        <option value="">Cualquier sucursal</option>
        @foreach($sucursales as $s)
          <option value="{{ $s->id }}" @selected($filtros['sucursal'] === $s->id)>{{ $s->nombre }}</option>
        @endforeach
      </select></div>
    <div class="field" style="flex-basis:180px"><label for="f-ord">Ordenar por</label>
      <select id="f-ord" name="orden" class="input">
        <option value="">Relevancia</option>
        <option value="menor" @selected($filtros['orden'] === 'menor')>Precio: menor a mayor</option>
        <option value="mayor" @selected($filtros['orden'] === 'mayor')>Precio: mayor a menor</option>
        <option value="descuento" @selected($filtros['orden'] === 'descuento')>Mayor descuento</option>
      </select></div>
    <label style="display:flex;gap:8px;align-items:center;font-size:15px;min-height:40px;cursor:pointer"><input type="checkbox" name="descuento" value="1" @checked($filtros['descuento']) style="accent-color:var(--color-accent);width:18px;height:18px">Solo con descuento</label>
    <noscript><button type="submit" class="btn btn-primary">Filtrar</button></noscript>
  </form>

  @if($productos->isEmpty())
    <div style="padding:48px 0">
      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:24px;margin:0">No encontramos ese producto</h2>
      <p style="margin:10px 0 20px;font-size:16px;max-width:52ch">Puede que lo tengamos con otro nombre o lo podamos conseguir. Envíanos la receta o el nombre y te cotizamos.</p>
      <a class="btn btn-primary" href="https://wa.me/{{ $T::WHATSAPP }}?text={{ rawurlencode('Hola, busco: ' . $filtros['q']) }}" target="_blank" rel="noopener" style="text-decoration:none;display:inline-flex;gap:8px;align-items:center"><i class="ph-duotone ph-whatsapp-logo"></i>Cotizar por WhatsApp</a>
    </div>
  @else
    <div class="grid-products">
      @foreach($productos as $p)
        @include('tienda.partials.card', ['p' => $p])
      @endforeach
    </div>
  @endif

  @if($ultima > 1)
    <nav class="pager" aria-label="Paginación">
      @if($pagina > 1)<a href="{{ $pageUrl($pagina - 1) }}" rel="prev" aria-label="Página anterior"><i class="ph-duotone ph-caret-left"></i></a>@endif
      @php $prev = 0; @endphp
      @for($i = 1; $i <= $ultima; $i++)
        @if($i === 1 || $i === $ultima || abs($i - $pagina) <= 2)
          @if($prev && $i - $prev > 1)<span>…</span>@endif
          <a href="{{ $pageUrl($i) }}" class="tnum {{ $i === $pagina ? 'on' : '' }}" @if($i === $pagina) aria-current="page" @endif>{{ $i }}</a>
          @php $prev = $i; @endphp
        @endif
      @endfor
      @if($pagina < $ultima)<a href="{{ $pageUrl($pagina + 1) }}" rel="next" aria-label="Página siguiente"><i class="ph-duotone ph-caret-right"></i></a>@endif
    </nav>
  @endif
</div>
@endsection
