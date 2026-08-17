// ═══════════════════════════════════════════════
// SERVICE WORKER — Descobreix Alacant PWA
// Estratègia: Cache-first per recursos estàtics,
//             Network-first per dades dinàmiques
// ═══════════════════════════════════════════════

const CACHE_NAME   = 'descobreix-alacant-v2';
const CACHE_STATIC = 'da-static-v2';
const CACHE_MAP    = 'da-map-tiles-v1';

// Recursos que es cachegen en instal·lar
const PRECACHE = [
  './index.html',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Sans+3:wght@300;400;600&display=swap',
];

// ── Install ──
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then(cache => cache.addAll(PRECACHE).catch(() => {})) // silencia errors de xarxa
      .then(() => self.skipWaiting())
  );
});

// ── Activate: neteja caches vells ──
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== CACHE_STATIC && k !== CACHE_MAP)
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: estratègia intel·ligent per tipus de recurs ──
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Tiles del mapa OpenStreetMap → cache-first (saves dades mòbil)
  if (url.hostname.includes('tile.openstreetmap.org') ||
      url.hostname.includes('openstreetmap')) {
    event.respondWith(mapTileStrategy(event.request));
    return;
  }

  // API de clima → network-first, fallback a cache
  if (url.hostname.includes('open-meteo.com')) {
    event.respondWith(networkFirstWithCache(event.request, 'da-api-v1', 3600));
    return;
  }

  // API Anthropic → sempre network (no cachear)
  if (url.hostname.includes('anthropic.com')) {
    event.respondWith(fetch(event.request).catch(() =>
      new Response('{"error":"offline"}', { headers: { 'Content-Type': 'application/json' } })
    ));
    return;
  }

  // Fonts de Google → cache-first
  if (url.hostname.includes('fonts.googleapis.com') ||
      url.hostname.includes('fonts.gstatic.com')) {
    event.respondWith(cacheFirst(event.request, CACHE_STATIC));
    return;
  }

  // Leaflet CSS/JS → cache-first
  if (url.hostname.includes('unpkg.com') || url.hostname.includes('cdnjs.cloudflare.com')) {
    event.respondWith(cacheFirst(event.request, CACHE_STATIC));
    return;
  }

  // HTML principal → network-first, fallback a cache (per tenir sempre la versió més nova)
  if (event.request.mode === 'navigate' ||
      url.pathname.endsWith('.html')) {
    event.respondWith(networkFirstWithCache(event.request, CACHE_STATIC));
    return;
  }

  // Resta → cache-first amb fallback network
  event.respondWith(cacheFirst(event.request, CACHE_STATIC));
});

// ── Estratègies ──

async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('Sense connexió', { status: 503 });
  }
}

async function networkFirstWithCache(request, cacheName, maxAgeSeconds = 0) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    // Pàgina offline de fallback
    if (request.mode === 'navigate') {
      return caches.match('./index.html');
    }
    return new Response('Sense connexió', { status: 503 });
  }
}

async function mapTileStrategy(request) {
  const cache = await caches.open(CACHE_MAP);
  const cached = await cache.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    return response;
  } catch {
    return new Response('', { status: 503 });
  }
}

// ── Push notifications (base per a futures notificacions) ──
self.addEventListener('push', event => {
  if (!event.data) return;
  const data = event.data.json();
  event.waitUntil(
    self.registration.showNotification(data.title || 'Descobreix Alacant', {
      body: data.body || '',
      icon: './icon-192.png',
      badge: './icon-72.png',
      data: { url: data.url || './' },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url || './')
  );
});
