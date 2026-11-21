/**
 * Page-leave beacon for first-party analytics: how long the page was actually visible and how far it was
 * scrolled. Plus `data-track="event_name"` on any element sends a click event. No third parties, no fingerprinting.
 */
export function initAnalytics() {
    const pv = document.querySelector('meta[name=ec-pv]')?.content;
    if (!pv || !navigator.sendBeacon) return;

    let visibleMs = 0;
    let since = document.visibilityState === 'visible' ? performance.now() : null;
    let maxScroll = 0;

    const scrollPct = () => {
        const h = document.documentElement.scrollHeight - innerHeight;
        return h <= 0 ? 100 : Math.min(100, Math.round((scrollY / h) * 100));
    };
    const onScroll = () => { maxScroll = Math.max(maxScroll, scrollPct()); };
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const post = (url, data) => {
        const body = new FormData();
        Object.entries(data).forEach(([k, v]) => v !== undefined && v !== null && body.append(k, v));
        navigator.sendBeacon(url, body);
    };
    const flush = () => {
        if (since !== null) { visibleMs += performance.now() - since; since = null; }
        post('/a/beacon', { pv, t: Math.round(visibleMs / 1000), s: maxScroll });
    };
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush();
        else since = performance.now();
    });
    addEventListener('pagehide', flush);

    document.addEventListener('click', (e) => {
        const el = e.target.closest('[data-track]');
        if (el) {
            post('/a/event', { name: el.dataset.track, pv, label: el.dataset.trackLabel || el.textContent.trim().slice(0, 120), target: el.getAttribute('href') || undefined });
            return;
        }
        const a = e.target.closest('a[href^="http"]');
        if (a && a.host !== location.host) post('/a/event', { name: 'outbound_click', pv, target: a.href.slice(0, 300) });
    });
}
