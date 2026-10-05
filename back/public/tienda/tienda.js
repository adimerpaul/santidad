/* Tienda pública Santidad Divina — JS ligero sobre HTML renderizado con Blade.
   Carruseles, sugerencias del buscador (/api/productos), carrito (localStorage → WhatsApp). */
(function () {
  'use strict';
  var SD = window.SD || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var bs = function (n) { return 'Bs ' + (Number(n) || 0).toFixed(2); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var slug = function (s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'producto'; };
  var reduceMotion = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Quitar el service worker y la caché de la tienda anterior (Quasar PWA), si siguen en el navegador
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function (regs) { regs.forEach(function (r) { r.unregister(); }); }).catch(function () {});
  }
  if (window.caches && caches.keys) {
    caches.keys().then(function (keys) { keys.forEach(function (k) { caches.delete(k); }); }).catch(function () {});
  }

  // ---------- Toast ----------
  var toastEl = $('[data-toast]'), toastT;
  function toast(msg) {
    if (!toastEl) return;
    toastEl.querySelector('span').textContent = msg;
    toastEl.hidden = false;
    clearTimeout(toastT);
    toastT = setTimeout(function () { toastEl.hidden = true; }, 2200);
  }

  // ---------- Carruseles de banners ----------
  $$('[data-banner]').forEach(function (el) {
    var track = $('[data-banner-track]', el);
    var slides = track.children.length;
    var dots = $$('[data-banner-dots] button', el);
    var i = 0, paused = false;
    function go(n) {
      i = (n + slides) % slides;
      if (el.hasAttribute('data-fade')) {
        // Hero: las diapositivas se superponen y se desvanecen
        Array.prototype.forEach.call(track.children, function (s, k) { s.classList.toggle('on', k === i); });
      } else {
        track.style.transform = 'translateX(-' + (i * 100) + '%)';
      }
      dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
    }
    dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); }); });
    var prev = $('[data-banner-prev]', el), next = $('[data-banner-next]', el);
    if (prev) prev.addEventListener('click', function () { go(i - 1); });
    if (next) next.addEventListener('click', function () { go(i + 1); });
    el.addEventListener('mouseenter', function () { paused = true; });
    el.addEventListener('mouseleave', function () { paused = false; });
    // Deslizar con el dedo
    var x0 = null;
    el.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; paused = true; }, { passive: true });
    el.addEventListener('touchend', function (e) {
      if (x0 !== null) { var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) go(i + (dx < 0 ? 1 : -1)); }
      x0 = null; paused = false;
    });
    if (slides > 1 && !reduceMotion) setInterval(function () { if (!paused && !document.hidden) go(i + 1); }, 5500);
  });

  // ---------- Carruseles de productos (scroll-snap) ----------
  $$('[data-pcar]').forEach(function (el) {
    var track = $('[data-pcar-track]', el);
    var dotsBox = $('[data-pcar-dots]', el);
    var sec = el.closest('section');
    var ctrl = sec && $('[data-pcar-ctrl]', sec);
    var label = ctrl && $('[data-pcar-label]', ctrl);
    function pages() { return Math.max(1, Math.ceil((track.scrollWidth - 4) / track.clientWidth)); }
    function page() { return Math.min(pages() - 1, Math.round(track.scrollLeft / track.clientWidth)); }
    function go(n) {
      var p = pages(); n = (n + p) % p;
      track.scrollTo({ left: n * track.clientWidth, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    function render() {
      var p = pages(), c = page();
      if (ctrl) ctrl.style.visibility = p > 1 ? 'visible' : 'hidden';
      if (label) label.textContent = (c + 1) + ' / ' + p;
      if (dotsBox.children.length !== p) {
        dotsBox.innerHTML = '';
        if (p > 1) for (var k = 0; k < p; k++) {
          var b = document.createElement('button');
          b.type = 'button'; b.setAttribute('aria-label', 'Página ' + (k + 1));
          b.addEventListener('click', go.bind(null, k));
          dotsBox.appendChild(b);
        }
      }
      Array.prototype.forEach.call(dotsBox.children, function (d, k) { d.classList.toggle('on', k === c); });
    }
    if (ctrl) {
      $('[data-pcar-prev]', ctrl).addEventListener('click', function () { go(page() - 1); });
      $('[data-pcar-next]', ctrl).addEventListener('click', function () { go(page() + 1); });
    }
    var t;
    track.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(render, 80); }, { passive: true });
    window.addEventListener('resize', render);
    render();
  });

  // ---------- Sucursales: mapa Leaflet con tiles de Google ----------
  $$('[data-suc]').forEach(function (box) {
    var mapEl = $('[data-suc-map]', box), dir = $('[data-suc-dir]', box), tel = $('[data-suc-tel]', box), wa = $('[data-suc-wa]', box);
    var map = null, markers = {};
    if (mapEl && window.L) {
      var points = [];
      try { points = JSON.parse(mapEl.getAttribute('data-points') || '[]'); } catch (e) {}
      map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true });
      L.tileLayer('https://mt{s}.google.com/vt/lyrs=r&x={x}&y={y}&z={z}', {
        subdomains: ['0', '1', '2', '3'], maxZoom: 20, attribution: '&copy; Google'
      }).addTo(map);
      var icon = L.divIcon({ className: 'suc-pin', html: '<span><i class="ph-duotone ph-first-aid"></i></span>', iconSize: [38, 46], iconAnchor: [19, 44], popupAnchor: [0, -40] });
      points.forEach(function (p) {
        markers[p.id] = L.marker([p.lat, p.lon], { icon: icon, title: p.name }).addTo(map).bindPopup(
          '<strong>' + esc(p.name) + '</strong><br>' + esc(p.addr) + '<br><em>' + esc(p.estado) + '</em>' +
          (p.dir ? '<br><a href="' + esc(p.dir) + '" target="_blank" rel="noopener">Cómo llegar →</a>' : ''));
      });
      var sel = mapEl.dataset.sel && markers[mapEl.dataset.sel];
      if (sel) { map.setView(sel.getLatLng(), 17); sel.openPopup(); }
      else if (points.length) map.fitBounds(L.latLngBounds(points.map(function (p) { return [p.lat, p.lon]; })), { padding: [30, 30] });
      else map.setView([-17.9667, -67.1167], 14); // Oruro
      // Permitir zoom con la rueda solo después de hacer clic en el mapa
      map.on('click', function () { map.scrollWheelZoom.enable(); });
      mapEl.addEventListener('mouseleave', function () { map.scrollWheelZoom.disable(); });
    }
    $$('[data-suc-item]', box).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('[data-suc-item]', box).forEach(function (b) { b.classList.remove('on'); b.setAttribute('aria-pressed', 'false'); });
        btn.classList.add('on'); btn.setAttribute('aria-pressed', 'true');
        var m = markers[btn.dataset.id];
        if (map && m) { map.flyTo(m.getLatLng(), 17, { duration: reduceMotion ? 0 : 0.8 }); m.openPopup(); }
        if (dir) dir.href = btn.dataset.dir || '#';
        if (tel) { tel.href = btn.dataset.tel || '#'; tel.hidden = !btn.dataset.tel; }
        if (wa) wa.href = 'https://wa.me/' + (btn.dataset.wa || SD.whatsapp);
      });
    });
  });

  // ---------- Laboratorios: ver todos ----------
  var labsMore = $('[data-labs-more]');
  if (labsMore) labsMore.addEventListener('click', function () { $$('[data-lab-extra]').forEach(function (a) { a.hidden = false; }); labsMore.remove(); });

  // ---------- Filtros de búsqueda: enviar al cambiar ----------
  var filters = $('[data-filters]');
  if (filters) filters.addEventListener('change', function () {
    $$('select, input', filters).forEach(function (i) { i.disabled = (i.type === 'checkbox' ? !i.checked : !i.value); });
    filters.submit();
  });

  // ---------- Buscador con sugerencias ----------
  var form = $('[data-search]');
  if (form) {
    var input = $('input[name=q]', form), box = $('#sd-sugg'), timer, ticket = 0, idx = -1, items = [];
    var imgUrl = function (img) { return !img ? SD.imgDefault : /^https?:\/\//i.test(img) ? img : SD.base + '/images/' + encodeURIComponent(img); };
    var close = function () { box.hidden = true; idx = -1; };
    var highlight = function () { $$('.sugg-item', box).forEach(function (a, k) { a.classList.toggle('on', k === idx); }); };
    form.addEventListener('submit', function (e) { if (!input.value.trim()) { e.preventDefault(); input.focus(); } });
    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { close(); return; }
      timer = setTimeout(function () {
        var my = ++ticket;
        box.hidden = false;
        box.innerHTML = '<p class="muted" style="margin:0;padding:12px 8px;font-size:14px">Buscando…</p>';
        fetch(SD.api + '/productos?per_page=6&search=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (my !== ticket) return;
            items = d.data || []; idx = -1;
            if (!items.length) {
              box.innerHTML = '<p style="margin:0;padding:12px 8px;font-size:14px">Sin resultados para “' + esc(q) + '”. <a href="https://wa.me/' + SD.whatsapp + '?text=' + encodeURIComponent('Hola, busco: ' + q) + '" target="_blank" rel="noopener">Escríbenos y lo cotizamos.</a></p>';
              return;
            }
            box.innerHTML = items.map(function (p) {
              var old = Number(p.precio) || 0, price = Number(p.precioVenta) || old;
              var pct = old > price ? Math.round((old - price) / old * 100) : 0;
              return '<a class="sugg-item" role="option" href="' + SD.base + '/producto/' + p.id + '/' + slug(p.nombre) + '">' +
                '<span class="thumb"><img src="' + esc(imgUrl(p.imagen)) + '" alt="" loading="lazy" onerror="this.onerror=null;this.src=\'' + SD.imgDefault + '\'"></span>' +
                '<span style="display:flex;flex-direction:column;gap:2px;min-width:0"><span class="ellipsis" style="font-weight:600;font-size:15px">' + esc(p.nombre) + '</span>' +
                '<span class="muted ellipsis" style="font-size:12.5px">' + esc(p.marca || p.distribuidora || 'Santidad Divina') + '</span></span>' +
                '<span style="display:flex;flex-direction:column;align-items:flex-end;gap:2px"><span class="tnum" style="font-weight:700">' + bs(price) + '</span>' +
                (pct ? '<span style="display:flex;gap:6px;align-items:center;font-size:12px"><s class="muted">' + bs(old) + '</s><span style="background:var(--color-accent-2);color:#fff;padding:0 6px;border-radius:var(--radius-md);font-weight:600">-' + pct + '%</span></span>' : '') +
                '</span></a>';
            }).join('') + '<a href="' + SD.buscar + '?q=' + encodeURIComponent(q) + '" style="display:block;padding:10px 8px 4px;font-size:14px;font-weight:600">Ver los ' + (d.total || items.length) + ' resultados para “' + esc(q) + '” →</a>';
          })
          .catch(function () { if (my === ticket) close(); });
      }, 250);
    });
    input.addEventListener('keydown', function (e) {
      var links = $$('.sugg-item', box);
      if (box.hidden || !links.length) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); idx = (idx + 1) % links.length; highlight(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); idx = (idx - 1 + links.length) % links.length; highlight(); }
      else if (e.key === 'Enter' && idx >= 0) { e.preventDefault(); location.href = links[idx].href; }
      else if (e.key === 'Escape') close();
    });
    input.addEventListener('focus', function () { if (box.innerHTML && input.value.trim().length >= 2) box.hidden = false; });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
  }

  // ---------- Carrito ----------
  var KEY = 'sd_cart_web';
  var cart = {};
  try { cart = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { cart = {}; }
  var delivery = 'recojo';
  var drawer = $('[data-cart]'), backdrop = $('[data-cart-close].dialog-backdrop');
  var pickup = $('[data-pickup]');

  function save() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }
  function list() { return Object.keys(cart).map(function (k) { return cart[k]; }); }
  function openCart() { drawer.hidden = false; backdrop.hidden = false; renderCart(); $('[data-cart-close]', drawer).focus(); }
  function closeCart() { drawer.hidden = true; backdrop.hidden = true; }

  function add(p, n) {
    var cur = cart[p.id];
    var max = p.max || 99;
    cart[p.id] = Object.assign({}, p, { qty: Math.min(max, (cur ? cur.qty : 0) + n) });
    save(); renderCart(); toast('Añadido: ' + p.name);
  }
  function setQty(id, q) {
    if (!cart[id]) return;
    if (q <= 0) delete cart[id]; else cart[id].qty = Math.min(cart[id].max || 99, q);
    save(); renderCart();
  }

  function renderCart() {
    var items = list();
    var count = items.reduce(function (a, x) { return a + x.qty; }, 0);
    var total = items.reduce(function (a, x) { return a + x.qty * x.price; }, 0);
    var saveAmt = items.reduce(function (a, x) { return a + x.qty * Math.max(0, (x.old || x.price) - x.price); }, 0);
    $$('[data-cart-count]').forEach(function (b) { b.textContent = count; });
    if (!drawer) return;
    $('[data-cart-empty]', drawer).hidden = items.length > 0;
    $('[data-cart-foot]', drawer).hidden = items.length === 0;
    $('[data-cart-items]', drawer).innerHTML = items.map(function (x) {
      return '<li class="cart-item" data-id="' + x.id + '">' +
        '<span class="thumb" style="width:56px;height:56px"><img src="' + esc(x.img) + '" alt=""></span>' +
        '<span style="display:flex;flex-direction:column;gap:6px;min-width:0"><a href="' + esc(x.url) + '" style="font-weight:600;font-size:15px;color:var(--color-text);text-decoration:none">' + esc(x.name) + '</a>' +
        '<span style="display:flex;align-items:center;gap:4px">' +
        '<button type="button" class="btn btn-ghost btn-icon" aria-label="Quitar uno" data-q="-1"><i class="ph-duotone ph-minus"></i></button>' +
        '<span class="tnum" style="min-width:28px;text-align:center">' + x.qty + '</span>' +
        '<button type="button" class="btn btn-ghost btn-icon" aria-label="Agregar uno" data-q="1"><i class="ph-duotone ph-plus"></i></button>' +
        '<button type="button" class="btn btn-ghost" data-q="0" style="font-size:13px">Eliminar</button></span></span>' +
        '<span class="tnum" style="font-weight:700">' + bs(x.qty * x.price) + '</span></li>';
    }).join('');
    $('[data-cart-total]', drawer).textContent = bs(total);
    $('[data-cart-save]', drawer).textContent = bs(saveAmt);
    $('[data-cart-save-row]', drawer).hidden = saveAmt <= 0;
    pickup.hidden = delivery !== 'recojo';
    $$('[data-delivery]', drawer).forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.delivery === delivery)); });

    // Indica qué sucursal tiene todo el pedido
    $$('option', pickup).forEach(function (o) {
      var ok = items.every(function (x) { return !x.stock || (x.stock[o.value] || 0) >= x.qty; });
      o.textContent = o.dataset.name + (items.length ? (ok ? ' · tiene todo' : ' · stock parcial') : '');
    });

    var opt = pickup.options[pickup.selectedIndex];
    var lines = items.map(function (x) { return '• ' + x.qty + ' x ' + x.name + ' (' + bs(x.qty * x.price) + ')'; }).join('\n');
    var msg = 'Hola Santidad Divina, quiero hacer un pedido:\n' + lines + '\nTotal: ' + bs(total) + '\n' +
      (delivery === 'recojo' ? 'Recojo en: ' + (opt ? opt.dataset.name : '') : 'Envío a domicilio. Mi dirección es: ');
    var wa = delivery === 'recojo' && opt && opt.dataset.wa ? opt.dataset.wa : SD.whatsapp;
    $('[data-cart-wa]', drawer).href = 'https://wa.me/' + wa + '?text=' + encodeURIComponent(msg);
  }

  if (drawer) {
    $$('[data-cart-open]').forEach(function (b) { b.addEventListener('click', openCart); });
    $$('[data-cart-close]').forEach(function (b) { b.addEventListener('click', closeCart); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !drawer.hidden) closeCart(); });
    $('[data-cart-items]', drawer).addEventListener('click', function (e) {
      var b = e.target.closest('[data-q]'); if (!b) return;
      var id = b.closest('[data-id]').dataset.id, d = +b.dataset.q;
      setQty(id, d === 0 ? 0 : cart[id].qty + d);
    });
    $$('[data-delivery]', drawer).forEach(function (b) { b.addEventListener('click', function () { delivery = b.dataset.delivery; renderCart(); }); });
    pickup.addEventListener('change', renderCart);
  }

  // Botones "Añadir"
  var pdQty = 1;
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-add]'); if (!b || b.disabled) return;
    e.preventDefault();
    try { add(JSON.parse(b.getAttribute('data-add')), b.hasAttribute('data-add-qty') ? pdQty : 1); } catch (err) {}
  });

  // ---------- Detalle de producto: cantidad y compartir ----------
  var pd = $('[data-pd]');
  if (pd) {
    var price = +pd.dataset.price, max = +pd.dataset.max || 1;
    var upd = function () { $('[data-pd-qty]', pd).textContent = pdQty; $('[data-pd-total]', pd).textContent = bs(pdQty * price); };
    $('[data-pd-dec]', pd).addEventListener('click', function () { pdQty = Math.max(1, pdQty - 1); upd(); });
    $('[data-pd-inc]', pd).addEventListener('click', function () { pdQty = Math.min(max, pdQty + 1); upd(); });
  }
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.dataset.url, title = b.dataset.title;
      if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); }
      else if (navigator.clipboard) { navigator.clipboard.writeText(url).then(function () { toast('Enlace copiado'); }, function () { toast(url); }); }
      else toast(url);
    });
  });

  // ---------- Suscripción (solo confirmación visual) ----------
  var news = $('[data-news]');
  if (news) news.addEventListener('submit', function (e) { e.preventDefault(); news.reset(); toast('Listo, te enviaremos nuestras ofertas'); });

  renderCart();
})();
