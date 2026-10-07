@php
  use App\Services\TiendaWebService as T;
  $titulo = trim($__env->yieldContent('title')) ?: 'Farmacias Santidad Divina · Farmacia 24 horas en Oruro, Bolivia';
  $descripcion = trim($__env->yieldContent('description')) ?: 'Farmacias Santidad Divina en Oruro: medicamentos, dermocosmética, cuidado de mamá y bebé y vitaminas a precio solidario. Atención 24 horas, stock por sucursal y pedidos por WhatsApp.';
  $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
  $ogImage = trim($__env->yieldContent('og_image')) ?: asset('tienda/logo.png');
  $wa = T::WHATSAPP;
  $redes = [
    'facebook'  => 'https://www.facebook.com/farmacia.santidaddivina',
    'instagram' => 'https://www.instagram.com/farmacias_santidad_divina/',
    'tiktok'    => 'https://www.tiktok.com/@santidad_divina',
  ];
  $v = @filemtime(public_path('tienda/tienda.css')) . @filemtime(public_path('tienda/tienda.js'));
@endphp
<!DOCTYPE html>
<html lang="es-BO">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $titulo }}</title>
  <meta name="description" content="{{ Str::limit($descripcion, 300) }}">
  <meta name="robots" content="{{ trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large' }}">
  <meta name="theme-color" content="#1f86e6">
  <link rel="canonical" href="{{ $canonical }}">
  <meta property="og:type" content="{{ trim($__env->yieldContent('og_type')) ?: 'website' }}">
  <meta property="og:locale" content="es_BO">
  <meta property="og:site_name" content="Farmacias Santidad Divina">
  <meta property="og:title" content="{{ $titulo }}">
  <meta property="og:description" content="{{ Str::limit($descripcion, 300) }}">
  <meta property="og:url" content="{{ $canonical }}">
  <meta property="og:image" content="{{ $ogImage }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $titulo }}">
  <meta name="twitter:description" content="{{ Str::limit($descripcion, 200) }}">
  <meta name="twitter:image" content="{{ $ogImage }}">
  <link rel="icon" href="{{ asset('tienda/logo.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,500&display=swap">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/duotone/style.css">
  <link rel="stylesheet" href="{{ asset('tienda/ds.css') }}?v={{ $v }}">
  <link rel="stylesheet" href="{{ asset('tienda/tienda.css') }}?v={{ $v }}">
  <script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
      ['@type' => 'Organization', '@id' => url('/') . '/#org', 'name' => 'Farmacias Santidad Divina', 'url' => url('/') . '/', 'logo' => asset('tienda/logo.png'),
       'email' => 'farmaciasantidaddivinacentral@gmail.com', 'telephone' => '+' . $wa, 'slogan' => 'Precio Solidario',
       'sameAs' => array_values($redes)],
      ['@type' => 'WebSite', '@id' => url('/') . '/#web', 'url' => url('/') . '/', 'name' => 'Farmacias Santidad Divina', 'inLanguage' => 'es-BO',
       'potentialAction' => ['@type' => 'SearchAction', 'target' => route('tienda.buscar') . '?q={q}', 'query-input' => 'required name=q']],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
  @stack('head')
</head>
<body>

<a href="#contenido" class="sr-only">Saltar al contenido</a>

<header class="hdr">
  <div class="hdr-in">
    <a href="{{ route('tienda.home') }}" class="brand" aria-label="Farmacias Santidad Divina, inicio">
      <span class="brand-logo"><img src="{{ asset('tienda/logo.png') }}" alt="" width="40" height="40"></span>
      <span><span class="brand-name">Santidad Divina</span><span class="brand-sub">Precio Solidario</span></span>
    </a>

    <form class="search" role="search" action="{{ route('tienda.buscar') }}" method="get" data-search>
      <label for="sd-q" class="sr-only">Buscar producto</label>
      <div class="search-box">
        <i class="ph-duotone ph-magnifying-glass" aria-hidden="true"></i>
        <input id="sd-q" name="q" class="input" type="search" autocomplete="off" value="{{ request('q') }}"
               placeholder="Busca un producto, principio activo o laboratorio" aria-autocomplete="list" aria-controls="sd-sugg">
      </div>
      <button type="submit" class="btn btn-pink">Buscar</button>
      <div id="sd-sugg" class="sugg" role="listbox" hidden></div>
    </form>

    <nav class="nav-main" aria-label="Principal">
      <a href="{{ route('tienda.home') }}#categorias">Categorías</a>
      <a href="{{ route('tienda.descuentos') }}">Descuentos</a>
      <a href="{{ route('tienda.sucursales') }}">Sucursales</a>
      <button type="button" class="btn btn-white" data-cart-open aria-label="Abrir carrito">
        <i class="ph-duotone ph-shopping-cart" aria-hidden="true" style="font-size:20px"></i>
        <span>Carrito</span>
        <span class="badge-pink" data-cart-count>0</span>
      </button>
    </nav>
  </div>
</header>

<main id="contenido">
  @yield('content')
</main>

<footer class="ftr">
  <div class="ftr-grid">
    <div>
      <p style="margin:0;display:flex;align-items:center;gap:10px;font-family:var(--font-heading);font-weight:700;font-size:22px"><span class="brand-logo"><img src="{{ asset('tienda/logo.png') }}" alt="" width="40" height="40"></span>Farmacia Santidad Divina</p>
      <p style="margin:2px 0 0;font-size:14px;color:var(--color-accent-700)">Precio Solidario</p>
      <p style="margin:16px 0 0;font-size:15px;line-height:1.6">Atendemos con ética y responsabilidad. Encuentra medicamentos, dermocosmética y cuidado personal a precios justos.</p>
      <div style="display:flex;gap:8px;margin-top:16px">
        <a class="btn btn-ghost btn-icon" href="{{ $redes['facebook'] }}" aria-label="Facebook" target="_blank" rel="noopener"><i class="ph-duotone ph-facebook-logo" style="font-size:20px"></i></a>
        <a class="btn btn-ghost btn-icon" href="{{ $redes['instagram'] }}" aria-label="Instagram" target="_blank" rel="noopener"><i class="ph-duotone ph-instagram-logo" style="font-size:20px"></i></a>
        <a class="btn btn-ghost btn-icon" href="{{ $redes['tiktok'] }}" aria-label="TikTok" target="_blank" rel="noopener"><i class="ph-duotone ph-tiktok-logo" style="font-size:20px"></i></a>
        <a class="btn btn-ghost btn-icon" href="https://wa.me/{{ $wa }}" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="ph-duotone ph-whatsapp-logo" style="font-size:20px"></i></a>
      </div>
    </div>
    <nav aria-label="Navegación"><p style="margin:0 0 12px;font-weight:600">Navegación</p>
      <ul>
        <li><a href="{{ route('tienda.home') }}">Inicio</a></li>
        <li><a href="{{ route('tienda.descuentos') }}">Descuentos</a></li>
        <li><a href="{{ route('tienda.sucursales') }}">Sucursales</a></li>
        <li><a href="{{ route('tienda.buscar') }}">Todos los productos</a></li>
        <li><a href="tel:+{{ $wa }}">Llámanos</a></li>
      </ul>
    </nav>
    <nav aria-label="Legales"><p style="margin:0 0 12px;font-weight:600">Legales</p>
      <ul>
        <li><a href="{{ route('tienda.privacidad') }}">Políticas de Privacidad</a></li>
        <li><a href="{{ route('tienda.envio') }}">Política de Envío</a></li>
        <li><a href="{{ route('tienda.terminos') }}">Términos y Condiciones</a></li>
        <li><a href="{{ route('tienda.quienes-somos') }}">Quiénes Somos</a></li>
      </ul>
    </nav>
    <nav aria-label="Categorías"><p style="margin:0 0 12px;font-weight:600">Categorías</p>
      <ul>
        @foreach(app(T::class)->categorias()->take(8) as $c)
          <li><a href="{{ $c->url }}">{{ $c->nombre }}</a></li>
        @endforeach
      </ul>
    </nav>
    <address style="font-style:normal"><p style="margin:0 0 12px;font-weight:600">Contacto</p>
      <ul>
        <li class="ico"><i class="ph-duotone ph-map-pin"></i>Oruro, Bolivia</li>
        <li class="ico"><i class="ph-duotone ph-clock"></i>Lun–Dom: 08:00–22:00 · Sucursal 1: 24 h</li>
        <li class="ico"><i class="ph-duotone ph-phone"></i><a href="tel:+{{ $wa }}" style="white-space:nowrap">+591 72319869</a></li>
        <li class="ico" style="word-break:break-all"><i class="ph-duotone ph-envelope"></i><a href="mailto:farmaciasantidaddivinacentral@gmail.com">farmaciasantidaddivinacentral@gmail.com</a></li>
      </ul>
      <form data-news style="display:flex;gap:8px;margin-top:16px">
        <input class="input" type="email" required placeholder="Tu correo electrónico" aria-label="Correo para ofertas">
        <button type="submit" class="btn btn-primary">Suscribir</button>
      </form>
    </address>
  </div>
  <div style="max-width:1240px;margin:40px auto 0;display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px 20px;font-size:13px">
    <p class="muted" style="margin:0">© {{ date('Y') }} Farmacia Santidad Divina. Todos los derechos reservados.</p>
    <p style="margin:0;display:flex;flex-wrap:wrap;gap:6px 16px">
      <a href="{{ route('tienda.privacidad') }}">Privacidad</a>
      <a href="{{ route('tienda.terminos') }}">Términos</a>
      <a href="{{ route('tienda.envio') }}">Envíos</a>
    </p>
  </div>
</footer>

{{-- Carrito: se llena con JS desde localStorage; el pedido se envía por WhatsApp --}}
<div class="dialog-backdrop" data-cart-close hidden style="position:fixed;inset:0;z-index:60"></div>
<aside class="cart" role="dialog" aria-modal="true" aria-label="Carrito de compras" data-cart hidden>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px">
    <h2 style="font-family:var(--font-heading);font-weight:700;font-size:26px;margin:0">Tu carrito</h2>
    <button type="button" class="btn btn-ghost btn-icon" aria-label="Cerrar carrito" data-cart-close><i class="ph-duotone ph-x" style="font-size:20px"></i></button>
  </div>
  <div style="flex:1;overflow:auto;padding:0 24px">
    <p data-cart-empty style="font-size:16px;margin:24px 0">Aún no agregaste productos. <a href="{{ route('tienda.descuentos') }}">Ver descuentos</a></p>
    <ul data-cart-items style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:20px"></ul>
  </div>
  <div data-cart-foot hidden style="padding:20px 24px;box-shadow:0 -1px 0 var(--color-divider);display:flex;flex-direction:column;gap:14px">
    <div class="seg" role="radiogroup" aria-label="Entrega" style="display:flex">
      <button type="button" class="seg-opt" data-delivery="recojo" aria-pressed="true" style="flex:1">Recojo en sucursal</button>
      <button type="button" class="seg-opt" data-delivery="envio" aria-pressed="false" style="flex:1">Envío a domicilio</button>
    </div>
    <select class="input" aria-label="Sucursal de recojo" data-pickup>
      @foreach(app(T::class)->sucursales() as $s)
        <option value="{{ $s->id }}" data-wa="{{ $s->whatsapp }}" data-name="{{ $s->nombre }}">{{ $s->nombre }}</option>
      @endforeach
    </select>
    <div data-cart-save-row style="display:flex;justify-content:space-between;font-size:14px;color:var(--color-accent-2-700)"><span>Ahorras</span><span class="tnum" data-cart-save></span></div>
    <div style="display:flex;justify-content:space-between;align-items:baseline"><span style="font-size:16px">Total</span><span class="tnum" style="font-size:26px;font-weight:800" data-cart-total></span></div>
    <a class="btn btn-primary btn-block" data-cart-wa href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" style="text-decoration:none;display:flex;gap:8px;align-items:center;justify-content:center;min-height:48px"><i class="ph-duotone ph-whatsapp-logo" style="font-size:20px"></i>Enviar pedido por WhatsApp</a>
    <p class="muted" style="margin:0;font-size:12.5px">Un farmacéutico confirma stock y te responde. Medicamentos con receta requieren la receta al recoger.</p>
  </div>
</aside>

<div class="toast" role="status" data-toast hidden><i class="ph-duotone ph-check-circle"></i><span></span></div>

<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="wa-float" aria-label="Escríbenos por WhatsApp"><i class="ph-duotone ph-whatsapp-logo" style="font-size:28px"></i></a>

<script>
  window.SD = {!! json_encode([
    'api' => url('/api'),
    'base' => url('/'),
    'buscar' => route('tienda.buscar'),
    'whatsapp' => $wa,
    'imgDefault' => asset('images/productDefault.jpg'),
  ], JSON_UNESCAPED_SLASHES) !!};
</script>
<script src="{{ asset('tienda/tienda.js') }}?v={{ $v }}" defer></script>
@stack('scripts')
</body>
</html>
