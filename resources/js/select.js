/**
 * Site-wide dropdown: progressively enhances every native <select> into a designed listbox.
 * The native select stays in the DOM (hidden) and remains the source of truth, so form submission,
 * `form="…"` attributes, Alpine x-model, onchange handlers and auto-submit filters keep working.
 * Opt out with <select data-native>.
 */
const CHEVRON = '<svg viewBox="0 0 24 24" class="ec-select-chevron" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
const CHECK = '<svg viewBox="0 0 24 24" class="ec-select-check" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>';
let uid = 0;
let openInstance = null;

function enhance(select) {
    if (select.dataset.enhanced || select.multiple || select.hasAttribute('data-native') || select.size > 1) return;
    select.dataset.enhanced = '1';
    const id = `ec-select-${++uid}`;
    const isInput = select.classList.contains('input');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = `${select.className} ec-select-button${isInput ? ' ec-select-input' : ''}`;
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', `${id}-list`);
    const label = select.id && document.querySelector(`label[for="${CSS.escape(select.id)}"]`);
    if (label) { label.id ||= `${id}-label`; button.setAttribute('aria-labelledby', `${label.id} ${id}-value`); label.addEventListener('click', e => { e.preventDefault(); button.focus(); }); }
    else if (select.getAttribute('aria-label')) button.setAttribute('aria-label', select.getAttribute('aria-label'));
    button.innerHTML = `<span class="ec-select-value" id="${id}-value"></span>${isInput ? CHEVRON : ''}`;

    select.after(button);
    select.classList.add('ec-select-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const panel = document.createElement('ul');
    panel.className = 'ec-select-panel';
    panel.id = `${id}-list`;
    panel.setAttribute('role', 'listbox');
    panel.hidden = true;
    document.body.appendChild(panel);

    let active = -1;
    let typed = '', typedAt = 0;
    const options = () => [...select.options].filter(o => !o.hidden);

    const sync = () => {
        const o = select.options[select.selectedIndex];
        button.querySelector('.ec-select-value').textContent = o ? o.text : '';
        button.classList.toggle('ec-select-placeholder', !!o && o.value === '' && isInput);
        button.disabled = select.disabled;
    };

    const render = () => {
        panel.innerHTML = '';
        options().forEach((o, i) => {
            const li = document.createElement('li');
            li.id = `${id}-opt-${i}`;
            li.setAttribute('role', 'option');
            li.className = 'ec-select-option';
            li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
            if (o.disabled) li.setAttribute('aria-disabled', 'true');
            li.innerHTML = `<span class="ec-select-text"></span>${CHECK}`;
            li.querySelector('.ec-select-text').textContent = o.text;
            li.addEventListener('mousedown', e => e.preventDefault());
            li.addEventListener('click', () => choose(i));
            li.addEventListener('mousemove', () => highlight(i, false));
            panel.appendChild(li);
        });
    };

    const highlight = (i, scroll = true) => {
        const items = panel.children;
        if (!items.length) return;
        active = Math.max(0, Math.min(items.length - 1, i));
        [...items].forEach((el, k) => el.classList.toggle('is-active', k === active));
        button.setAttribute('aria-activedescendant', items[active].id);
        if (scroll) items[active].scrollIntoView({ block: 'nearest' });
    };

    const place = () => {
        const r = button.getBoundingClientRect();
        panel.style.minWidth = `${Math.max(r.width, 180)}px`;
        panel.style.left = `${Math.min(r.left, window.innerWidth - Math.max(r.width, 180) - 8)}px`;
        const below = window.innerHeight - r.bottom, h = Math.min(panel.scrollHeight, 320);
        if (below < h + 12 && r.top > below) { panel.style.top = ''; panel.style.bottom = `${window.innerHeight - r.top + 6}px`; panel.dataset.side = 'top'; }
        else { panel.style.bottom = ''; panel.style.top = `${r.bottom + 6}px`; panel.dataset.side = 'bottom'; }
    };

    const open = () => {
        if (select.disabled) return;
        if (openInstance && openInstance !== api) openInstance.close();
        render(); panel.hidden = false; place();
        button.setAttribute('aria-expanded', 'true');
        highlight(Math.max(0, options().findIndex(o => o.selected)));
        openInstance = api;
    };
    const close = (focus = false) => {
        if (panel.hidden) return;
        panel.hidden = true; button.setAttribute('aria-expanded', 'false'); button.removeAttribute('aria-activedescendant');
        if (openInstance === api) openInstance = null;
        if (focus) button.focus();
    };
    const choose = (i) => {
        const o = options()[i];
        if (!o || o.disabled) return;
        const changed = !o.selected;
        select.value = o.value; o.selected = true; sync(); close(true);
        if (changed) { select.dispatchEvent(new Event('input', { bubbles: true })); select.dispatchEvent(new Event('change', { bubbles: true })); }
    };

    button.addEventListener('click', () => (panel.hidden ? open() : close()));
    button.addEventListener('keydown', e => {
        const opened = !panel.hidden, n = options().length;
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key) && !opened) { e.preventDefault(); open(); return; }
        if (!opened) {
            if (e.key.length === 1) { e.preventDefault(); open(); }
            else return;
        }
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
        else if (e.key === 'Home') { e.preventDefault(); highlight(0); }
        else if (e.key === 'End') { e.preventDefault(); highlight(n - 1); }
        else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(active); }
        else if (e.key === 'Escape') { e.preventDefault(); close(true); }
        else if (e.key === 'Tab') close();
        else if (e.key.length === 1) {
            const now = Date.now(); typed = (now - typedAt > 600 ? '' : typed) + e.key.toLowerCase(); typedAt = now;
            const k = options().findIndex(o => o.text.toLowerCase().startsWith(typed));
            if (k >= 0) highlight(k);
        }
    });
    button.addEventListener('blur', () => setTimeout(() => { if (document.activeElement !== button) close(); }, 120));
    select.addEventListener('change', sync);
    select.addEventListener('ec:sync', sync);
    select.addEventListener('focus', () => button.focus());
    new MutationObserver(sync).observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });

    const api = { close, place, panel };
    sync();
    // Alpine x-model sets .value without events; re-sync once Alpine has initialised.
    requestAnimationFrame(sync);
}

function enhanceAll(root = document) {
    root.querySelectorAll?.('select').forEach(enhance);
}

document.addEventListener('pointerdown', e => {
    if (openInstance && !openInstance.panel.contains(e.target) && !e.target.closest?.('.ec-select-button')) openInstance.close();
});
window.addEventListener('resize', () => openInstance?.place());
window.addEventListener('scroll', e => { if (openInstance && !openInstance.panel.contains(e.target)) openInstance.place(); }, true);
document.addEventListener('alpine:initialized', () => document.querySelectorAll('select[data-enhanced]').forEach(s => s.dispatchEvent(new Event('ec:sync'))));

export function initSelects() {
    enhanceAll();
    new MutationObserver(muts => muts.forEach(m => m.addedNodes.forEach(n => {
        if (n.nodeType !== 1) return;
        if (n.tagName === 'SELECT') enhance(n); else enhanceAll(n);
    }))).observe(document.body, { childList: true, subtree: true });
}
