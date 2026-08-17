/* AKRA Tech Studio v2 — main.js */
'use strict';

// ─── NAVBAR SCROLL ──────────────────────────────────────────────────────────
const navbar = document.getElementById('navbar');
if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

// ─── HAMBURGER MENU ─────────────────────────────────────────────────────────
const hamburger = document.getElementById('nav-hamburger');
const navLinks  = document.getElementById('nav-links');
if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
        const open = hamburger.classList.toggle('open');
        navLinks.classList.toggle('open', open);
        hamburger.setAttribute('aria-expanded', open);
        document.body.style.overflow = open ? 'hidden' : '';
    });
    // Tanca en fer clic fora
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
            hamburger.classList.remove('open');
            navLinks.classList.remove('open');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    });
}

// ─── LANGUAGE DROPDOWN ──────────────────────────────────────────────────────
const langToggle   = document.getElementById('lang-toggle');
const langDropdown = document.getElementById('lang-dropdown');
if (langToggle && langDropdown) {
    langToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = langDropdown.classList.toggle('show');
        langToggle.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', () => {
        langDropdown.classList.remove('show');
        langToggle?.setAttribute('aria-expanded', 'false');
    });
}

// ─── COUNTER ANIMATION ──────────────────────────────────────────────────────
function animateCounter(el, target, duration = 1800) {
    const start = performance.now();
    const update = (time) => {
        const p = Math.min((time - start) / duration, 1);
        const ease = 1 - Math.pow(1 - p, 3); // ease-out cubic
        el.textContent = Math.round(ease * target) + (el.dataset.suffix || '+');
        if (p < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}
// Inicia counters quan entren al viewport
const counters = document.querySelectorAll('[data-count]');
if (counters.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                animateCounter(el, parseInt(el.dataset.count));
                io.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => io.observe(c));
}

// ─── SCROLL REVEAL ──────────────────────────────────────────────────────────
if ('IntersectionObserver' in window) {
    const style = document.createElement('style');
    style.textContent = `
        .reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .reveal.visible { opacity: 1; transform: none; }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
    `;
    document.head.appendChild(style);

    // Afegir la classe als elements que volem revelar
    const revealTargets = [
        '.service-card', '.diff-item', '.process-step',
        '.testimonial-card', '.project-card', '.local-point'
    ];
    document.querySelectorAll(revealTargets.join(',')).forEach((el, i) => {
        el.classList.add('reveal');
        const delay = i % 3;
        if (delay) el.classList.add(`reveal-delay-${delay}`);
    });

    const revealIO = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revealIO.unobserve(e.target); } });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach(el => revealIO.observe(el));
}

// ─── COOKIE BANNER ──────────────────────────────────────────────────────────
const cookieBanner = document.getElementById('cookie-banner');
const COOKIE_KEY = 'akra_cookies_consent';
const COOKIE_CONSENT_DAYS = cookieBanner ? parseInt(cookieBanner.dataset.consentDays, 10) || 365 : 365;

function getStoredConsent() {
    try {
        const raw = localStorage.getItem(COOKIE_KEY);
        if (!raw) return null;
        const data = JSON.parse(raw);
        const ageMs = Date.now() - (data.ts || 0);
        const maxMs = COOKIE_CONSENT_DAYS * 24 * 60 * 60 * 1000;
        if (ageMs > maxMs) return null; // ha caducat — es torna a demanar
        return data.value || null;
    } catch (e) {
        // Compatibilitat amb el format antic (valor pla sense data) — el tractem com a caducat
        return null;
    }
}

if (cookieBanner && !getStoredConsent()) {
    setTimeout(() => {
        cookieBanner.classList.add('show');
        cookieBanner.setAttribute('aria-hidden', 'false');
    }, 1200);
}
function dismissCookie(consent) {
    localStorage.setItem(COOKIE_KEY, JSON.stringify({ value: consent, ts: Date.now() }));
    cookieBanner?.classList.remove('show');
    setTimeout(() => cookieBanner?.setAttribute('aria-hidden', 'true'), 400);
}
document.getElementById('cookie-accept')?.addEventListener('click', () => dismissCookie('accepted'));
document.getElementById('cookie-reject')?.addEventListener('click', () => dismissCookie('rejected'));

// ─── SMOOTH ANCHOR SCROLL ───────────────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', (e) => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = navbar ? navbar.offsetHeight + 16 : 80;
            window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
        }
    });
});
