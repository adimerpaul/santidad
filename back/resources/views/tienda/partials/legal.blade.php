{{-- Página legal: portada + índice lateral + secciones. Recibe $titulo, $subtitulo, $icono, $chips y $secciones [id, titulo, icono, html]. --}}
@push('head')
<style>
  .lg-hero { margin-top: 8px; padding: clamp(28px,5vw,56px); border-radius: 32px; background: var(--grad-hero); color: #fff; box-shadow: 0 24px 60px rgba(31,110,200,.30); }
  .lg-hero i.lg-ico { font-size: 48px; }
  .lg-hero h1 { color: #fff; margin-top: 12px !important; }
  .lg-hero p { margin: 12px 0 0; font-size: 17px; max-width: 720px; opacity: .92; }
  .lg-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
  .lg-chips span { padding: 6px 14px; border-radius: 999px; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3); font-size: 13px; font-weight: 700; }
  .lg-grid { display: grid; grid-template-columns: 280px minmax(0,1fr); gap: 28px; margin-top: 32px; align-items: start; }
  .lg-toc { position: sticky; top: 110px; background: #fff; border-radius: 22px; box-shadow: var(--shadow-card); padding: 20px; }
  .lg-toc p { margin: 0 0 12px; font-weight: 700; display: flex; gap: 8px; align-items: center; }
  .lg-toc ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
  .lg-toc a { display: flex; gap: 8px; align-items: center; padding: 8px 10px; border-radius: 12px; text-decoration: none; color: var(--color-text); font-size: 14.5px; line-height: 1.3; }
  .lg-toc a:hover { background: var(--color-accent-100); color: var(--color-accent-700); }
  .lg-toc a i { color: var(--color-accent); font-size: 18px; flex: 0 0 auto; }
  .lg-body { display: flex; flex-direction: column; gap: 18px; }
  .lg-sec { background: #fff; border-radius: 22px; box-shadow: var(--shadow-card); padding: clamp(20px,3vw,28px); scroll-margin-top: 110px; }
  .lg-sec h2 { display: flex; gap: 12px; align-items: center; margin: 0 0 12px; font-family: var(--font-heading); font-weight: 700; font-size: clamp(19px,2vw,23px); line-height: 1.25; }
  .lg-sec h2 i { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: var(--grad-brand); color: #fff; font-size: 21px; flex: 0 0 auto; }
  .lg-sec p, .lg-sec li { font-size: 16px; line-height: 1.65; }
  .lg-sec p { margin: 0 0 10px; }
  .lg-sec p:last-child, .lg-sec ul:last-child { margin-bottom: 0; }
  .lg-sec ul { margin: 0 0 12px; padding-left: 22px; }
  .lg-sec .lg-sub { font-weight: 700; margin-top: 14px; }
  @media (max-width: 900px) { .lg-grid { grid-template-columns: 1fr; } .lg-toc { position: static; } }
</style>
@endpush

<div class="page">
  <nav class="crumb" aria-label="Ruta"><a href="{{ route('tienda.home') }}">Inicio</a><span>/</span><span>{{ $titulo }}</span></nav>

  <section class="lg-hero">
    <i class="ph-duotone ph-{{ $icono }} lg-ico" aria-hidden="true"></i>
    <h1>{{ $titulo }}</h1>
    <p>{{ $subtitulo }}</p>
    @if(!empty($chips))
      <div class="lg-chips">@foreach($chips as $chip)<span>{{ $chip }}</span>@endforeach</div>
    @endif
  </section>

  <div class="lg-grid">
    <nav class="lg-toc" aria-label="Contenido">
      <p><i class="ph-duotone ph-book-open" aria-hidden="true"></i>Contenido</p>
      <ul>
        @foreach($secciones as $s)
          <li><a href="#{{ $s['id'] }}"><i class="ph-duotone ph-{{ $s['icono'] }}" aria-hidden="true"></i>{{ $s['titulo'] }}</a></li>
        @endforeach
      </ul>
    </nav>

    <article class="lg-body">
      @foreach($secciones as $s)
        <section class="lg-sec" id="{{ $s['id'] }}">
          <h2><i class="ph-duotone ph-{{ $s['icono'] }}" aria-hidden="true"></i>{{ $s['titulo'] }}</h2>
          {!! $s['html'] !!}
        </section>
      @endforeach
    </article>
  </div>
</div>
