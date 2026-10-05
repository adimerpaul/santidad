@extends('tienda.layout')
@php
  $T = \App\Services\TiendaWebService::class;
  $principal = $sucursales->firstWhere('h24', true) ?? $sucursales->first();
  $abiertas = $sucursales->where('abierto', true)->count();
  $maxDesc = $descuentos->max('pct') ?: 0;
@endphp

@section('title', 'Farmacias Santidad Divina · Farmacia 24 horas en Oruro, Bolivia')
@section('description', 'Farmacia 24 horas en Oruro con ' . $sucursales->count() . ' sucursales. Medicamentos, dermocosmética, cuidado de mamá y bebé y vitaminas a precio solidario. Revisa el stock por sucursal y pide por WhatsApp.')
@section('canonical', route('tienda.home') . '/')

@push('head')
  @if($banners->isNotEmpty())<link rel="preload" as="image" href="{{ $banners->first()->img }}">@endif
@endpush

@section('content')
<div class="wrap">

  {{-- ===== Hero: el carrusel principal (Carousel tipo Normal) va de fondo y el texto encima ===== --}}
  <section class="hero hero-bg {{ $banners->isEmpty() ? 'sin-fondo' : '' }}" @if($banners->isNotEmpty()) aria-roledescription="carrusel" data-banner data-fade @endif>
    @if($banners->isNotEmpty())
      <div class="hero-slides" data-banner-track>
        @foreach($banners as $i => $b)
          <div class="hero-slide {{ $i === 0 ? 'on' : '' }}" aria-roledescription="diapositiva" aria-label="{{ $i + 1 }} de {{ $banners->count() }}">
            <picture>
              @if($b->movil)<source media="(max-width: 640px)" srcset="{{ $b->movil }}">@endif
              <img src="{{ $b->img }}" alt="Promoción {{ $i + 1 }} — Farmacias Santidad Divina" width="1221" height="560" @if($i > 0) loading="lazy" @else fetchpriority="high" @endif>
            </picture>
            @if($b->url)<a href="{{ $b->url }}" class="hero-slide-link" aria-label="Ver promoción {{ $i + 1 }}"></a>@endif
          </div>
        @endforeach
      </div>
    @endif
    <span class="hero-overlay" aria-hidden="true"></span>
    <span class="hero-glow2" aria-hidden="true"></span>

    <div class="hero-content">
      <h1><span>Tu farmacia en Oruro.</span><span style="color:#bff1ff">Abierta las 24 horas.</span></h1>
      <span class="hero-pill"><i class="ph-duotone ph-clock"></i>{{ $abiertas }} de {{ $sucursales->count() }} sucursales abiertas ahora</span>
      <p>Medicamentos, dermocosmética y cuidado personal a precio solidario. Busca tu producto, revisa en qué sucursal hay stock y recógelo o recíbelo en casa.</p>
      <div class="hero-cta">
        <a class="btn btn-pink" href="{{ route('tienda.descuentos') }}" style="padding-inline:24px;box-shadow:0 8px 20px rgba(214,0,108,.35)">Ver descuentos</a>
        <a class="btn btn-white" href="#sucursales" style="min-height:48px;padding-inline:22px"><i class="ph-duotone ph-map-pin" aria-hidden="true"></i>Encontrar sucursal</a>
      </div>
    </div>

    @if($banners->count() > 1)
      <button type="button" class="banner-arrow l" aria-label="Anterior" data-banner-prev><i class="ph-duotone ph-caret-left"></i></button>
      <button type="button" class="banner-arrow r" aria-label="Siguiente" data-banner-next><i class="ph-duotone ph-caret-right"></i></button>
      <div class="banner-dots" data-banner-dots>
        @foreach($banners as $i => $b)
          <button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Ir a la promoción {{ $i + 1 }}"></button>
        @endforeach
      </div>
    @endif
  </section>

  {{-- ===== Promos ===== --}}
  <section class="promos" aria-label="Promociones">
    <a href="{{ route('tienda.descuentos') }}" class="promo sun">
      <span class="promo-chip">Ofertas de la semana</span>
      <span class="promo-big">{{ $maxDesc ? 'Hasta ' . $maxDesc . '% OFF' : 'Precio solidario' }}</span>
      <span style="font-size:17px;font-weight:700;position:relative">{{ $descuentos->count() ? $descuentos->count() . ' productos con descuento' : 'Precios bajos todos los días' }}</span>
      <span style="font-size:14px;font-weight:700;color:#d6006c;margin-top:auto;position:relative">Ver productos →</span>
      <span class="promo-ball" aria-hidden="true"></span>
    </a>
    <a href="https://wa.me/{{ $T::WHATSAPP }}?text={{ rawurlencode('Hola, quiero cotizar estos productos / esta receta:') }}" target="_blank" rel="noopener" class="promo white">
      <span class="icon-sq"><i class="ph-duotone ph-file-text"></i></span>
      <span style="font-size:20px;font-weight:800;letter-spacing:-.01em">Envía tu receta</span>
      <span class="muted" style="font-size:14.5px;line-height:1.5">Mándanos una foto por WhatsApp y te decimos qué tenemos y cuánto cuesta.</span>
      <span class="btn-pink" style="align-self:flex-start;margin-top:auto;min-height:0;padding:8px 16px;font-size:14px">Cotizar ahora</span>
    </a>
    @if($principal)
      <a href="{{ $principal->telHref }}" class="promo blue">
        <span style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#bff1ff">Atención {{ $principal->h24 ? '24 horas' : 'todos los días' }}</span>
        <span style="font-size:40px;font-weight:800;line-height:1;letter-spacing:-.02em">{{ $principal->telefono }}</span>
        <span style="font-size:15px;color:#e3f4ff">Lunes a domingo, incluye feriados · {{ $principal->direccion }}</span>
        <span style="font-size:14px;font-weight:700;margin-top:auto">Llamar ahora →</span>
      </a>
    @endif
  </section>

  {{-- ===== Servicios ===== --}}
  <section class="services" aria-label="Servicios">
    <div class="service"><h2><i class="ph-duotone ph-moped" aria-hidden="true"></i>Envíos a domicilio</h2><p>Pide desde donde estés y te lo llevamos.</p></div>
    <div class="service"><h2><i class="ph-duotone ph-receipt" aria-hidden="true"></i>Cotiza con nosotros</h2><p>Solicita una cotización rápida y sin compromiso.</p></div>
    <div class="service"><h2><i class="ph-duotone ph-tag" aria-hidden="true"></i>Descuentos por volumen</h2><p>Aprovecha grandes descuentos en compras en grandes cantidades.</p></div>
    <div class="service"><h2><i class="ph-duotone ph-device-mobile" aria-hidden="true"></i>Aplicación móvil</h2><p>Compra desde donde estés con nuestra app móvil. <a href="#app">Descargar</a></p></div>
  </section>

  {{-- ===== Descuentos ===== --}}
  @if($descuentos->isNotEmpty())
    <section id="descuentos" class="sec" aria-labelledby="h-desc">
      <div class="sec-head">
        <div><span class="kicker">Ofertas de la semana</span><h2 id="h-desc" class="h2">Explora nuestros descuentos</h2></div>
        @include('tienda.partials.pcar-ctrl', ['id' => 'desc'])
      </div>
      @include('tienda.partials.carrusel-productos', ['productos' => $descuentos])
      <p style="text-align:center;margin:20px 0 0"><a class="btn btn-secondary" href="{{ route('tienda.descuentos') }}" style="text-decoration:none;display:inline-flex">Ver todos los descuentos →</a></p>
    </section>
  @endif

  {{-- ===== Categorías ===== --}}
  <section id="categorias" class="cats" aria-labelledby="h-cat">
    <h2 id="h-cat" class="h2" style="margin-bottom:28px">Explorá nuestras categorías</h2>
    <div class="cats-grid">
      @foreach($categorias as $c)
        <a href="{{ $c->url }}" class="cat">
          <span class="cat-ico"><i class="{{ $c->icono }}" aria-hidden="true"></i></span>
          <span class="cat-name">{{ $c->nombre }}</span>
          <span class="muted" style="font-size:12.5px">Ver productos</span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- ===== Más vendidos ===== --}}
  @if($masVendidos->isNotEmpty())
    <section class="sec" aria-labelledby="h-top">
      <div class="sec-head">
        <div><span class="kicker">Lo que más llevan nuestros clientes</span><h2 id="h-top" class="h2">Productos más vendidos</h2></div>
        @include('tienda.partials.pcar-ctrl', ['id' => 'top'])
      </div>
      @include('tienda.partials.carrusel-productos', ['productos' => $masVendidos])
    </section>
  @endif

  {{-- ===== Carrusel medio (Carousel tipo Medio) ===== --}}
  @if($medios->isNotEmpty())
    <section class="banner medio" aria-roledescription="carrusel" aria-label="Novedades" data-banner>
      <div class="banner-track" data-banner-track>
        @foreach($medios as $i => $b)
          @php $tagB = $b->url ? 'a' : 'div'; @endphp
          <{{ $tagB }} class="banner-slide" @if($b->url) href="{{ $b->url }}" @endif>
            <img src="{{ $b->img }}" alt="Novedad {{ $i + 1 }} — Farmacias Santidad Divina" loading="lazy" width="1221" height="400">
          </{{ $tagB }}>
        @endforeach
      </div>
      @if($medios->count() > 1)
        <div class="banner-dots" data-banner-dots>
          @foreach($medios as $i => $b)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Ir a la novedad {{ $i + 1 }}"></button>@endforeach
        </div>
      @endif
    </section>
  @endif

  {{-- ===== Sucursales ===== --}}
  @include('tienda.partials.sucursales', ['sucursales' => $sucursales])

  <section class="sec" aria-labelledby="h-mejor">
    <h2 id="h-mejor" class="h2" style="margin-bottom:28px">Te ofrecemos lo mejor</h2>
    <div class="best">
      <div><h3>Innovación constante</h3><p>Innovación constante para servirte mejor.</p></div>
      <div><h3>Atención profesional</h3><p>Profesionales farmacéuticos a tu servicio.</p></div>
      <div><h3>Precios solidarios</h3><p>Ahora todos los días de la semana.</p></div>
    </div>
  </section>

  <section id="app" class="app" aria-labelledby="h-app">
    <div>
      <span class="kicker" style="color:#bff1ff;font-weight:700">Aplicación móvil</span>
      <h2 id="h-app" class="h2">Santidad Divina en tu bolsillo</h2>
      <ul>
        <li><i class="ph-duotone ph-magnifying-glass"></i>Busca tu producto</li>
        <li><i class="ph-duotone ph-package"></i>Ver disponibilidad</li>
        <li><i class="ph-duotone ph-shopping-cart"></i>Carrito de compras</li>
      </ul>
      <a class="btn btn-white" href="https://play.google.com/store" target="_blank" rel="noopener" style="margin-top:24px;min-height:48px;padding-inline:22px"><i class="ph-duotone ph-google-play-logo"></i>Descargar en Google Play</a>
    </div>
    <div style="display:flex;gap:24px;align-items:flex-end;flex-wrap:wrap">
      <div style="width:180px;height:180px;padding:10px;background:#fff;border-radius:22px;box-sizing:border-box">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=320x320&amp;data={{ rawurlencode('https://play.google.com/store') }}" alt="Código QR para descargar la app" width="160" height="160" loading="lazy" style="width:100%;height:100%">
      </div>
      <p style="margin:0;font-size:14px;max-width:22ch;color:#e3f4ff">Escanea el código con la cámara de tu teléfono.</p>
    </div>
  </section>

  {{-- ===== Carrusel inferior de marcas (Carousel tipo Mini) ===== --}}
  @if($marcas->isNotEmpty())
    <section class="sec" aria-labelledby="h-marcas" style="padding-bottom:0">
      <h2 id="h-marcas" class="h2" style="font-size:clamp(24px,2.6vw,32px);font-weight:700;margin-bottom:8px">Marcas que confían en nosotros</h2>
      <div class="marquee">
        <div class="marquee-track">
          @foreach([$marcas, $marcas] as $k => $lista)
            @foreach($lista as $m)
              @php $tagM = $m->url ? 'a' : 'span'; @endphp
              <{{ $tagM }} @if($m->url) href="{{ $m->url }}" @endif @if($k) aria-hidden="true" tabindex="-1" @endif>
                <img src="{{ $m->img }}" alt="{{ $k ? '' : 'Marca aliada' }}" loading="lazy" height="66">
              </{{ $tagM }}>
            @endforeach
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ===== Laboratorios / distribuidoras ===== --}}
  @if(count($labs))
    <section class="sec" aria-labelledby="h-labs">
      <h2 id="h-labs" class="h2" style="font-size:clamp(24px,2.6vw,32px);font-weight:700;margin-bottom:20px">Laboratorios y distribuidoras</h2>
      <div class="labs" data-labs>
        @foreach($labs as $i => $l)
          <a href="{{ route('tienda.buscar', ['q' => $l]) }}" class="lab" @if($i >= 24) hidden data-lab-extra @endif>{{ $l }}</a>
        @endforeach
        @if(count($labs) > 24)
          <button type="button" class="lab" data-labs-more style="border:0;cursor:pointer;font:inherit;font-weight:700;font-size:14px">Ver todas ({{ count($labs) }}) →</button>
        @endif
      </div>
    </section>
  @endif
</div>
@endsection
