// ============================================================
//  FiscalPro Service Worker v2 · Edicions L'Escletxa
//  Akra Tech Studio · akratechstudio.es
// ============================================================

const CACHE_VERSION = 'fiscalpro-v2';
const CACHE_STATIC  = 'fiscalpro-static-v2';

const STATIC_ASSETS = [
  '/llibres/',
  '/llibres/index.html',
  '/llibres/manifest.json',
  '/llibres/icon-192.png',
  '/llibres/icon-512.png',
  '/llibres/apple-touch-icon.png',
  'https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap',
];

// ── INSTAL·LACIÓ ──
self.addEventListener('install', event => {
  console.log('[SW] FiscalPro v2 instal·lant...');
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then(cache => Promise.allSettled(
        STATIC_ASSETS.map(url =>
          cache.add(url).catch(e => console.warn('[SW] No cached:', url, e.message))
        )
      ))
      .then(() => self.skipWaiting())
  );
});

// ── ACTIVACIÓ: neteja versions anteriors ──
self.addEventListener('activate', event => {
  console.log('[SW] FiscalPro v2 activant...');
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys
          .filter(k => k !== CACHE_STATIC && k !== CACHE_VERSION)
          .map(k => { console.log('[SW] Eliminant cache antic:', k); return caches.delete(k); })
      ))
      .then(() => self.clients.claim())
  );
});

// ── FETCH ──
self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  // Ignorar no-GET, extensions, API Anthropic
  if (req.method !== 'GET') return;
  if (url.protocol === 'chrome-extension:') return;
  if (url.hostname === 'api.anthropic.com') return;

  // Fonts Google: Cache First
  if (url.hostname.includes('googleapis.com') || url.hostname.includes('gstatic.com')) {
    event.respondWith(
      caches.match(req).then(cached => {
        if (cached) return cached;
        return fetch(req).then(res => {
          if (res && res.status === 200) {
            const clone = res.clone();
            caches.open(CACHE_STATIC).then(c => c.put(req, clone));
          }
          return res;
        }).catch(() => new Response('', { status: 408 }));
      })
    );
    return;
  }

  // App pròpia (akratechstudio.es o localhost): Stale-While-Revalidate
  if (url.hostname === 'akratechstudio.es' || url.hostname === 'localhost' || url.hostname === '127.0.0.1') {
    event.respondWith(
      caches.open(CACHE_STATIC).then(cache =>
        cache.match(req).then(cached => {
          const fetchPromise = fetch(req).then(res => {
            if (res && res.status === 200) {
              cache.put(req, res.clone());
            }
            return res;
          }).catch(() => null);

          return cached || fetchPromise.then(res => res || cache.match('/llibres/index.html'));
        })
      )
    );
    return;
  }

  // Resta: Network First amb fallback a cache
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});

// ── NOTIFICACIONS PUSH (terminis fiscals) ──
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  event.waitUntil(
    self.registration.showNotification(
      data.title || 'FiscalPro · Edicions L\'Escletxa',
      {
        body:    data.body    || 'Tens un termini fiscal proper',
        icon:    '/llibres/icon-192.png',
        badge:   '/llibres/icon-192.png',
        vibrate: [200, 100, 200],
        data:    { url: data.url || '/llibres/' }
      }
    )
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data?.url || '/llibres/'));
});

console.log('[SW] FiscalPro Service Worker v2 carregat');
