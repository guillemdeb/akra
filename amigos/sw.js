/**
 * REDAMIGOS - Service Worker v2.0
 * Estratègia: Cache-First per assets, Network-First per PHP
 * Base: /amigos/
 */

const SW_VERSION    = 'ra-v2.1';
const CACHE_STATIC  = `${SW_VERSION}-static`;
const CACHE_DYNAMIC = `${SW_VERSION}-dynamic`;
const BASE_URL      = '/amigos';

// ── Assets que sempre cal tenir en caché (instal·lació) ──
const STATIC_ASSETS = [
  `${BASE_URL}/assets/css/styles.css`,
  `${BASE_URL}/assets/img/icon-192x192.png`,
  `${BASE_URL}/assets/img/icon-512x512.png`,
  `${BASE_URL}/assets/img/splash-750x1334.png`,
  `${BASE_URL}/uploads/default.png`,
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap',
];

// ── Pàgina offline ──
const OFFLINE_PAGE = `${BASE_URL}/offline.php`;

// ════════════════════════════════════════
//  INSTALL
// ════════════════════════════════════════
self.addEventListener('install', event => {
  console.log(`[SW] Instal·lant ${SW_VERSION}...`);
  event.waitUntil(
    caches.open(CACHE_STATIC).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('[SW] Alguns assets no s\'han pogut guardar en caché:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// ════════════════════════════════════════
//  ACTIVATE - Netejar caché vella
// ════════════════════════════════════════
self.addEventListener('activate', event => {
  console.log(`[SW] Activant ${SW_VERSION}...`);
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== CACHE_STATIC && k !== CACHE_DYNAMIC)
          .map(k => {
            console.log(`[SW] Eliminant caché vella: ${k}`);
            return caches.delete(k);
          })
      )
    ).then(() => self.clients.claim())
  );
});

// ════════════════════════════════════════
//  FETCH - Estratègia per tipus de recurs
// ════════════════════════════════════════
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar peticions no-GET i externes als dominis de confiança
  if (request.method !== 'GET') return;
  if (url.protocol === 'chrome-extension:') return;

  // ── APIs i endpoints dinàmics → Network-only ──
  if (
    url.pathname.startsWith(`${BASE_URL}/api_`) ||
    url.pathname.startsWith(`${BASE_URL}/toggle_like`) ||
    url.pathname.startsWith(`${BASE_URL}/enviar_solicitud`) ||
    url.pathname.startsWith(`${BASE_URL}/logout`)
  ) {
    return; // No intercedir, anar directament a la xarxa
  }

  // ── Assets estàtics (CSS, JS, imatges, fonts) → Cache-First ──
  if (
    request.destination === 'style' ||
    request.destination === 'script' ||
    request.destination === 'image' ||
    request.destination === 'font' ||
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|webp|svg|woff|woff2|ttf)$/)
  ) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // ── Pàgines PHP → Network-First amb fallback offline ──
  if (url.pathname.startsWith(BASE_URL) && url.pathname.endsWith('.php')) {
    event.respondWith(networkFirstWithOffline(request));
    return;
  }

  // Per defecte: intentar xarxa, caché com a backup
  event.respondWith(networkFirst(request));
});

// ════════════════════════════════════════
//  Estratègies
// ════════════════════════════════════════

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_DYNAMIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408 });
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_DYNAMIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || new Response('Sin conexión', { status: 503 });
  }
}

async function networkFirstWithOffline(request) {
  try {
    const response = await fetch(request);
    // Guardar en caché pàgines que no siguin POST/redirects
    if (response.ok && response.type !== 'opaqueredirect') {
      const cache = await caches.open(CACHE_DYNAMIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    // Intentar caché
    const cached = await caches.match(request);
    if (cached) return cached;
    // Pàgina offline
    const offlinePage = await caches.match(OFFLINE_PAGE);
    if (offlinePage) return offlinePage;
    return new Response(
      `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sense connexió</title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <style>body{font-family:Arial,sans-serif;background:#4A90E2;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;}
      .box{background:white;border-radius:16px;padding:40px;text-align:center;max-width:360px;}
      .icon{font-size:3rem;margin-bottom:16px;}h1{color:#333;margin-bottom:8px;}p{color:#666;}</style></head>
      <body><div class="box">
        <div class="icon">📡</div>
        <h1>Sense connexió</h1>
        <p>Comprova la teva connexió a internet i torna-ho a intentar.</p>
        <button onclick="location.reload()" style="margin-top:20px;background:#4A90E2;color:white;border:none;padding:12px 28px;border-radius:8px;font-size:1rem;cursor:pointer;">
          Reintentar
        </button>
      </div></body></html>`,
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
}

// ════════════════════════════════════════
//  PUSH NOTIFICATIONS (preparació futura)
// ════════════════════════════════════════
self.addEventListener('push', event => {
  const data = event.data?.json() || {};
  const options = {
    body: data.body || 'Tens una nova notificació',
    icon: `${BASE_URL}/assets/img/icon-192x192.png`,
    badge: `${BASE_URL}/assets/img/icon-72x72.png`,
    vibrate: [100, 50, 100],
    data: { url: data.url || `${BASE_URL}/notificaciones.php` },
    actions: [
      { action: 'open', title: 'Veure' },
      { action: 'close', title: 'Tancar' }
    ]
  };
  event.waitUntil(
    self.registration.showNotification(data.title || 'RedAmigos', options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'close') return;
  const url = event.notification.data?.url || `${BASE_URL}/timeline.php`;
  event.waitUntil(clients.openWindow(url));
});
