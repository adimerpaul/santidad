@extends('tienda.layout')

@section('title', 'Quiénes Somos · Farmacias Santidad Divina')
@section('description', 'Farmacia Santidad Divina nace en Oruro el 12 de julio de 2010. Conoce nuestra historia, misión y visión: compromiso con la salud, calidez y servicio.')
@section('canonical', route('tienda.quienes-somos'))

@push('head')
<style>
  .qs-cols { display: grid; grid-template-columns: repeat(auto-fit,minmax(min(100%,300px),1fr)); gap: 20px; align-items: start; }
  .qs-gal { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 0; }
  .qs-gal figure { margin: 0; border-radius: 16px; overflow: hidden; background: var(--color-surface); }
  .qs-gal img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
  .qs-gal figcaption { padding: 8px 10px; font-size: 13px; color: var(--muted); }
  .qs-kpis { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
  .qs-kpis div { padding: 16px; border-radius: 16px; background: var(--color-accent-100); text-align: center; }
  .qs-kpis b { display: block; font-size: 30px; font-weight: 800; color: var(--color-accent-700); }
  .qs-kpis span { font-size: 14px; }
  .qs-cta { display: flex; flex-wrap: wrap; gap: 12px; }
  .qs-cta a { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding-inline: 20px; }
</style>
@endpush

@section('content')
@include('tienda.partials.legal', [
  'titulo' => 'Quiénes Somos',
  'subtitulo' => 'Farmacia Santidad Divina — compromiso con la salud, calidez y servicio.',
  'icono' => 'users-three',
  'chips' => ['Desde 2010', 'Oruro, Bolivia', '6 sucursales'],
  'secciones' => [
    ['id' => 'historia', 'titulo' => 'Nuestra Historia', 'icono' => 'clock-counter-clockwise', 'html' => '
      <div class="qs-cols">
        <div>
          <p>Farmacia Santidad Divina inicia este proyecto el <strong>12 de julio del año 2010</strong>. Nuestra transformación está constantemente vinculada al proceso de modernización, priorizando la expansión de nuestros servicios, siempre pensando en la población orureña y muy pronto, a nivel nacional.</p>
          <p>Farmacia Santidad Divina en su vigésimo cuarto aniversario, ha logrado un arduo trabajo, siempre con la prioridad de la mejora continua y perseverando en alcanzar la excelencia, enfocándose en nuestra clientela y ofreciendo un servicio de calidad. Actualmente contamos con <strong>6 sucursales</strong> en la ciudad de Oruro. Desde esa fecha, Oruro, <strong>05 de agosto de 2025</strong>, pasa a ser <strong>Santidad-Divina S.R.L.</strong></p>
        </div>
        <div class="qs-gal">
          <figure><img src="' . asset('tienda/santidad1.png') . '" alt="Frente antiguo de Farmacia Santidad Divina" loading="lazy"><figcaption>Frente antiguo — primeros años</figcaption></figure>
          <figure><img src="' . asset('tienda/santidad2.png') . '" alt="Frente actual de Farmacia Santidad Divina" loading="lazy"><figcaption>Frente actual — renovación y crecimiento</figcaption></figure>
        </div>
      </div>'],
    ['id' => 'mision', 'titulo' => 'Misión', 'icono' => 'flag', 'html' => '
      <p>Brindar productos farmacéuticos y servicio de calidad, accesibles en todo momento y lugar para el bienestar de la salud de la población Orureña. A través de nuestra red de farmacias, ofrecemos asesoramiento profesional, una experiencia excepcional, soluciones integrales, guiados por valores de honestidad, transparencia, solidaridad, igualdad y ética.</p>'],
    ['id' => 'vision', 'titulo' => 'Visión', 'icono' => 'trophy', 'html' => '
      <p>Ser la cadena de Farmacias líder con mayor participación en el mercado, reconocida por nuestra excelencia en el servicio al cliente, la calidad de nuestros productos y nuestra contribución al cuidado de la salud. Nos esforzamos para ser la primera opción para cubrir necesidades de la salud y bienestar, manteniendo los más altos estándares éticos con responsabilidad social.</p>'],
    ['id' => 'datos', 'titulo' => 'Datos destacados', 'icono' => 'chart-bar', 'html' => '
      <div class="qs-kpis">
        <div><b>2010</b><span>Año de inicio</span></div>
        <div><b>6</b><span>Sucursales en Oruro</span></div>
        <div><b>15+</b><span>Años de servicio</span></div>
      </div>'],
    ['id' => 'contacto', 'titulo' => '¿Quieres saber más?', 'icono' => 'headset', 'html' => '
      <div class="qs-cta">
        <a class="btn btn-primary" href="' . route('tienda.sucursales') . '"><i class="ph-duotone ph-storefront"></i>Ver Sucursales</a>
        <a class="btn btn-white" style="box-shadow:var(--shadow-card)" href="https://wa.me/' . \App\Services\TiendaWebService::WHATSAPP . '" target="_blank" rel="noopener"><i class="ph-duotone ph-whatsapp-logo"></i>Escríbenos por WhatsApp</a>
      </div>'],
  ],
])
@endsection
