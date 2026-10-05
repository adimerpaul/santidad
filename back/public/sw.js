/*
 * Service worker "autodestructivo".
 * La tienda anterior (frontTienda, Quasar PWA) registró /sw.js y guardó en caché su index.html.
 * Este archivo lo reemplaza: borra todas las cachés, se da de baja y recarga las pestañas abiertas
 * para que los visitantes vean la tienda nueva (Blade) sin tener que hacer Ctrl+F5.
 * No eliminar mientras puedan quedar navegadores con el service worker antiguo.
 */
self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil((async function () {
    try {
      var keys = await caches.keys();
      await Promise.all(keys.map(function (k) { return caches.delete(k); }));
    } catch (e) {}
    try { await self.registration.unregister(); } catch (e) {}
    var clients = await self.clients.matchAll({ type: 'window' });
    clients.forEach(function (client) {
      try { client.navigate(client.url); } catch (e) {}
    });
  })());
});

// Mientras siga activo, todo va directo a la red (nunca responde desde caché)
self.addEventListener('fetch', function () {});
