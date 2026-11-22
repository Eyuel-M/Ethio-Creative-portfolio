/**
 * Keeps panels that scroll on their own fully on screen, so every part of
 * them can be reached without scrolling somewhere else first.
 *
 *   data-fit="scroll"   A sticky side panel with its own scrollbar (filters, editor tools).
 *                       Its height always ends at the bottom of the window — even before it
 *                       sticks — so its scrollbar reaches its last item.
 *   data-fit="release"  A sticky side panel without a scrollbar (apply card, summaries).
 *                       It stays sticky only while it fits on screen; when it's taller than
 *                       the window it scrolls with the page, so its bottom is never cut off.
 *   data-fit="fill"     A box that scrolls inside (Messages). It fills the window from where
 *                       it starts down to the bottom edge, never taller.
 *
 * Optional: data-fit-gap (px below the panel, default 16), data-fit-min (px, default 160).
 */
const GAP = 16;

function stickyTop(el) {
    const cs = getComputedStyle(el);
    return cs.position === 'sticky' ? parseFloat(cs.top) || 0 : null;
}

function fit(el) {
    const mode = el.dataset.fit;
    const gap = Number(el.dataset.fitGap ?? GAP);
    const min = Number(el.dataset.fitMin ?? 160);
    const vh = window.innerHeight;

    if (mode === 'scroll') {
        const top = stickyTop(el);
        if (top === null) { el.style.maxHeight = ''; return; }   // not sticky at this size (phones)
        const from = Math.max(el.getBoundingClientRect().top, top);
        el.style.maxHeight = Math.max(min, vh - from - gap) + 'px';
    } else if (mode === 'release') {
        el.style.position = '';
        const top = stickyTop(el);
        if (top !== null && el.offsetHeight > vh - top - gap) el.style.position = 'static';
    } else if (mode === 'fill') {
        const docTop = el.getBoundingClientRect().top + window.scrollY;
        el.style.height = Math.max(min, vh - docTop - gap) + 'px';
    }
}

export function initViewportFit() {
    const els = () => document.querySelectorAll('[data-fit]');
    let queued = false;
    const run = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => { queued = false; els().forEach(fit); });
    };

    window.addEventListener('scroll', run, { passive: true });
    window.addEventListener('resize', run);
    window.addEventListener('load', run);
    // Panels grow and shrink (filters open, steps change, images load).
    if ('ResizeObserver' in window) {
        const ro = new ResizeObserver(run);
        els().forEach((el) => { ro.observe(el); [...el.children].forEach((c) => ro.observe(c)); });
    }
    run();
}
