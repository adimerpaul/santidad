{{-- Mapa + lista de sucursales. Requiere $sucursales; opcional $titulo, $tag --}}
@php $sel = $sucursales->firstWhere('id', (int) request('id')) ?? $sucursales->first(); $tag = $tag ?? 'h2';
  $puntos = $sucursales->filter(fn ($s) => $s->lat !== null)->map(fn ($s) => [
    'id' => $s->id, 'lat' => $s->lat, 'lon' => $s->lon, 'name' => $s->nombre,
    'addr' => $s->direccion, 'estado' => $s->estado, 'dir' => $s->comoLlegar,
  ])->values();
@endphp
<section id="sucursales" class="suc" aria-labelledby="h-suc">
  <{{ $tag }} id="h-suc" class="h2" style="margin-bottom:8px">{{ $titulo ?? 'Nuestras sucursales' }}</{{ $tag }}>
  <p style="margin:0 0 28px;font-size:16px;max-width:60ch">{{ $sucursales->count() }} farmacias en Oruro. Elige una sucursal para verla en el mapa, llamar o pedir indicaciones.</p>
  <div class="suc-grid" data-suc>
    <div class="suc-map">
      {{-- Mapa Leaflet con tiles de Google (mt*.google.com/vt/lyrs=r) --}}
      <div class="suc-mapdiv" role="region" aria-label="Mapa de sucursales" data-suc-map
           data-sel="{{ request('id') ? $sel?->id : '' }}"
           data-points='@json($puntos)'></div>
    </div>
    <div>
      <ul class="suc-list">
        @foreach($sucursales as $s)
          <li itemscope itemtype="https://schema.org/Pharmacy">
            <button type="button" class="suc-btn {{ $sel && $s->id === $sel->id ? 'on' : '' }}" aria-pressed="{{ $sel && $s->id === $sel->id ? 'true' : 'false' }}"
                    data-suc-item data-id="{{ $s->id }}" data-dir="{{ $s->comoLlegar }}" data-tel="{{ $s->telHref }}" data-wa="{{ $s->whatsapp }}" data-name="{{ $s->nombre }}">
              <span style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline">
                <span class="suc-name" itemprop="name">{{ $s->nombre }}</span>
                <span class="tag {{ $s->abierto ? 'tag-accent' : 'tag-neutral' }}">{{ $s->estado }}</span>
              </span>
              <span style="font-size:14.5px" itemprop="address">{{ $s->direccion }}, Oruro</span>
              <span class="muted" style="font-size:13.5px">{{ $s->horario }}@if($s->telefono) · Tel. <span itemprop="telephone">{{ $s->telefono }}</span>@endif</span>
            </button>
          </li>
        @endforeach
      </ul>
      @if($sel)
        <div style="display:flex;gap:10px;flex-wrap:wrap;padding:14px 16px 0">
          <a class="btn btn-primary" href="{{ $sel->comoLlegar ?: '#' }}" target="_blank" rel="noopener" data-suc-dir style="text-decoration:none;display:flex;gap:8px;align-items:center"><i class="ph-duotone ph-navigation-arrow"></i>Cómo llegar</a>
          <a class="btn btn-secondary" href="{{ $sel->telHref ?: '#' }}" data-suc-tel style="text-decoration:none;display:flex;gap:8px;align-items:center"><i class="ph-duotone ph-phone"></i>Llamar</a>
          <a class="btn btn-secondary" href="https://wa.me/{{ $sel->whatsapp ?: \App\Services\TiendaWebService::WHATSAPP }}" target="_blank" rel="noopener" data-suc-wa style="text-decoration:none;display:flex;gap:8px;align-items:center"><i class="ph-duotone ph-whatsapp-logo"></i>WhatsApp</a>
        </div>
      @endif
    </div>
  </div>
</section>
@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script>
<script type="application/ld+json">{!! json_encode([
  '@context' => 'https://schema.org',
  '@graph' => $sucursales->map(fn ($s) => array_filter([
    '@type' => 'Pharmacy',
    'name' => $s->nombre,
    'parentOrganization' => ['@id' => url('/') . '/#org'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s->direccion, 'addressLocality' => 'Oruro', 'addressCountry' => 'BO'],
    'telephone' => $s->telefono ? '+591 2 ' . preg_replace('/\D/', '', $s->telefono) : null,
    'geo' => $s->lat ? ['@type' => 'GeoCoordinates', 'latitude' => $s->lat, 'longitude' => $s->lon] : null,
    'openingHours' => $s->h24 ? 'Mo-Su 00:00-23:59' : null,
    'url' => route('tienda.sucursales'),
  ]))->values(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
