@extends('tienda.layout')
@php
  $T = \App\Services\TiendaWebService::class;
  $r = $p->raw;
  $specs = collect([
    'Unidad de venta' => $r->unidad, 'Principio activo' => $r->composicion, 'Nombre común' => $r->nombreComun,
    'Laboratorio / marca' => $r->marca, 'Distribuidora' => $r->distribuidora, 'País de origen' => $r->paisOrigen,
    'Reg. sanitario' => $r->registroSanitario, 'Código' => $r->id,
  ])->filter(fn ($v) => filled($v));
  $desc = $p->nombre . ($p->lab ? ' de ' . $p->lab : '') . ' a ' . $T::bs($p->precio)
        . ($p->descuento ? ' (' . $p->pct . '% de descuento, antes ' . $T::bs($p->antes) . ')' : '')
        . '. ' . $p->disponibilidad . ' en Farmacias Santidad Divina, Oruro. Pide por WhatsApp o recoge en sucursal.';
  $waBuy = 'https://wa.me/' . $T::WHATSAPP . '?text=' . rawurlencode('Hola, quiero ' . $p->nombre . ' (' . $T::bs($p->precio) . ' c/u). ' . $p->url);
@endphp

@section('title', $p->nombre . ' · ' . $T::bs($p->precio) . ' | Santidad Divina Oruro')
@section('description', $desc)
@section('canonical', $p->url)
@section('og_image', $p->img)
@section('og_type', 'product')

@push('head')
  <meta property="product:price:amount" content="{{ number_format($p->precio, 2, '.', '') }}">
  <meta property="product:price:currency" content="BOB">
  <script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
      array_filter([
        '@type' => 'Product',
        'name' => $p->nombre,
        'image' => [$p->img],
        'sku' => (string) $p->id,
        'description' => $r->descripcion ?: $desc,
        'brand' => $p->lab ? ['@type' => 'Brand', 'name' => $p->lab] : null,
        'category' => $categoria?->nombre,
        'offers' => [
          '@type' => 'Offer',
          'url' => $p->url,
          'priceCurrency' => 'BOB',
          'price' => number_format($p->precio, 2, '.', ''),
          'availability' => $p->agotado ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
          'itemCondition' => 'https://schema.org/NewCondition',
          'seller' => ['@id' => url('/') . '/#org'],
        ],
      ]),
      ['@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('tienda.home') . '/'],
        $categoria ? ['@type' => 'ListItem', 'position' => 2, 'name' => $categoria->nombre, 'item' => $categoria->url] : null,
        ['@type' => 'ListItem', 'position' => $categoria ? 3 : 2, 'name' => $p->nombre, 'item' => $p->url],
      ]))],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<article class="page">
  <nav class="crumb" aria-label="Ruta">
    <a href="{{ route('tienda.home') }}">Inicio</a><span>/</span>
    @if($categoria)<a href="{{ $categoria->url }}">{{ $categoria->nombre }}</a><span>/</span>@endif
    <span>{{ $p->nombre }}</span>
  </nav>

  <div class="pd-grid">
    <div class="pd-img">
      <img src="{{ $p->img }}" alt="{{ $p->nombre }}" width="600" height="600" fetchpriority="high" onerror="this.onerror=null;this.src='{{ asset('images/productDefault.jpg') }}'">
      @if($p->descuento)<span class="pcard-disc" style="font-size:18px;top:16px;left:16px">-{{ $p->pct }}%</span>@endif
      <span class="tag tag-accent" style="position:absolute;top:16px;right:16px;display:flex;gap:6px;align-items:center"><i class="ph-duotone ph-seal-check"></i>Original</span>
    </div>
    <div>
      @if($p->lab)<span class="muted" style="display:block;font-size:13px;letter-spacing:.08em;text-transform:uppercase">{{ $p->lab }}</span>@endif
      <h1 style="font-size:clamp(28px,3.4vw,42px);line-height:1.12;letter-spacing:-.01em;margin:10px 0 0">{{ $p->nombre }}</h1>
      <div style="display:flex;flex-wrap:wrap;gap:10px 14px;align-items:baseline;margin-top:20px">
        <span class="tnum" style="font-size:36px;font-weight:800">{{ $T::bs($p->precio) }}</span>
        @if($p->descuento)
          <s class="muted" style="font-size:18px">Antes {{ $T::bs($p->antes) }}</s>
          <span class="tag tag-accent-2" style="white-space:nowrap">Ahorras {{ $T::bs($p->antes - $p->precio) }}</span>
        @endif
      </div>
      @if($p->promocion)<p style="margin:8px 0 0;font-size:14px;font-weight:700;color:var(--color-accent-2-700)"><i class="ph-duotone ph-tag"></i> {{ $p->promocion }}</p>@endif
      <p style="margin:10px 0 0;font-size:15px;display:flex;gap:8px;align-items:center;color:{{ $p->colorDisp }}"><i class="ph-duotone ph-package"></i><span>{{ $p->stockTxt }} · {{ $p->disponibilidad }}</span></p>

      @if($specs->isNotEmpty())
        <dl class="pd-specs">
          @foreach($specs as $k => $v)<dt>{{ $k }}</dt><dd>{{ $v }}</dd>@endforeach
        </dl>
      @endif
      @if(filled($r->descripcion))
        <p style="margin:20px 0 0;font-size:15px;line-height:1.6">{{ $r->descripcion }}</p>
      @endif

      <div data-pd data-price="{{ $p->precio }}" data-max="{{ max(1, $p->total) }}">
        <div style="display:flex;flex-wrap:wrap;gap:16px 28px;align-items:center;margin-top:32px">
          <div style="display:flex;align-items:center;gap:4px" role="group" aria-label="Cantidad">
            <button type="button" class="btn btn-secondary btn-icon" aria-label="Quitar uno" data-pd-dec><i class="ph-duotone ph-minus"></i></button>
            <span class="tnum" style="min-width:48px;text-align:center;font-size:20px;font-weight:600" aria-live="polite" data-pd-qty>1</span>
            <button type="button" class="btn btn-secondary btn-icon" aria-label="Agregar uno" data-pd-inc><i class="ph-duotone ph-plus"></i></button>
            <span class="muted" style="margin-left:8px;font-size:14px">{{ $p->unidad }}</span>
          </div>
          <span style="font-size:15px">Total <strong class="tnum" style="font-size:22px" data-pd-total>{{ $T::bs($p->precio) }}</strong></span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px">
          <button type="button" class="btn btn-primary" data-add='@json(app($T)->datosCarrito($p))' data-add-qty @disabled($p->agotado) style="display:flex;gap:8px;align-items:center;min-height:46px;padding-inline:22px"><i class="ph-duotone ph-shopping-cart-simple"></i>Añadir al carrito</button>
          <a class="btn btn-secondary" href="{{ $waBuy }}" target="_blank" rel="noopener" style="text-decoration:none;display:flex;gap:8px;align-items:center;min-height:46px"><i class="ph-duotone ph-whatsapp-logo"></i>Pedir por WhatsApp</a>
          <button type="button" class="btn btn-ghost" data-share data-title="{{ $p->nombre }}" data-url="{{ $p->url }}" style="display:flex;gap:8px;align-items:center;min-height:46px"><i class="ph-duotone ph-share-network"></i>Compartir</button>
        </div>
      </div>
      <p style="display:flex;gap:16px;flex-wrap:wrap;margin:12px 0 0;font-size:14px">
        <a href="https://wa.me/?text={{ rawurlencode($p->nombre . ' a ' . $T::bs($p->precio) . ' en Santidad Divina: ' . $p->url) }}" target="_blank" rel="noopener">Compartir en WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($p->url) }}" target="_blank" rel="noopener">Compartir en Facebook</a>
      </p>
    </div>
  </div>

  <section aria-labelledby="h-disp" style="padding-top:clamp(40px,6vw,72px)">
    <h2 id="h-disp" class="h2" style="font-size:clamp(24px,2.6vw,32px);font-weight:700;margin-bottom:20px">¿En qué sucursal está disponible?</h2>
    <ul class="avail-list">
      @foreach($sucursales as $s)
        @php $n = $p->stock[$s->id] ?? 0; @endphp
        <li style="opacity:{{ $n ? 1 : .55 }}">
          <span style="display:flex;justify-content:space-between;gap:12px;align-items:baseline">
            <span class="suc-name">{{ $s->nombre }}</span>
            <span class="tnum" style="font-weight:700;color:{{ $n ? 'var(--color-accent-700)' : 'var(--muted)' }}">{{ $n ? $n . ' disp.' : 'Sin stock' }}</span>
          </span>
          <span style="font-size:14.5px">{{ $s->direccion }}</span>
          <span class="muted" style="font-size:13.5px">{{ $s->estado }} · <a href="{{ route('tienda.sucursales', ['id' => $s->id]) }}">Ver en mapa</a></span>
        </li>
      @endforeach
    </ul>
  </section>

  @if($relacionados->isNotEmpty())
    <section aria-labelledby="h-rel" style="padding-top:clamp(40px,6vw,72px)">
      <div class="sec-head">
        <h2 id="h-rel" class="h2" style="font-size:clamp(24px,2.6vw,32px);font-weight:700">Productos relacionados</h2>
        @include('tienda.partials.pcar-ctrl', ['id' => 'rel'])
      </div>
      @include('tienda.partials.carrusel-productos', ['productos' => $relacionados])
    </section>
  @endif
</article>
@endsection
