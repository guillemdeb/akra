// Service Worker · Sara Creixement PWA
// Versió de la cache — incrementa per forçar actualització
const CACHE_VERSION = 'sara-v1.2';
const CACHE_STATIC = `${CACHE_VERSION}-static`;

// Recursos a cachear en la instal·lació
const STATIC_ASSETS = [
  './sara_creixement.html',
  './manifest.json',
  'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
  'https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap',
];

// ─── INSTAL·LACIÓ ────────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then(cache => {
      // Caché els recursos crítics; continua encara que alguns fallin
      return Promise.allSettled(
        STATIC_ASSETS.map(url =>
          cache.add(url).catch(err => console.warn('[SW] No s\'ha pogut cachear:', url, err))
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// ─── ACTIVACIÓ (neteja caches velles) ────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k.startsWith('sara-') && k !== CACHE_STATIC)
          .map(k => {
            console.log('[SW] Eliminant cache antiga:', k);
            return caches.delete(k);
          })
      )
    ).then(() => self.clients.claim())
  );
});

// ─── FETCH: Cache-first per estàtics, network-first per la resta ─
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignora peticions no-GET i chrome-extension
  if (request.method !== 'GET') return;
  if (url.protocol === 'chrome-extension:') return;

  // Fonts de Google: stale-while-revalidate
  if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }

  // Chart.js CDN: cache-first (no canvia)
  if (url.hostname === 'cdnjs.cloudflare.com') {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Fitxers locals (HTML, manifest): network-first amb fallback a cache
  event.respondWith(networkFirstWithFallback(request));
});

// ─── ESTRATÈGIES ─────────────────────────────────────────────────

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_STATIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('Recurs no disponible offline', { status: 503 });
  }
}

async function networkFirstWithFallback(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_STATIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    // Fallback a l'HTML principal si és una navegació
    if (request.mode === 'navigate') {
      const html = await caches.match('./sara_creixement.html');
      if (html) return html;
    }
    return new Response('Sense connexió i sense cache disponible', { status: 503 });
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_STATIC);
  const cached = await cache.match(request);
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => null);
  return cached || fetchPromise;
}

// ─── MISSATGE: forçar actualització des de l'app ─────────────────
self.addEventListener('message', event => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
