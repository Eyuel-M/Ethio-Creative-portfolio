/**
 * Insights post editor. Builds on the case-study builder (drag & drop, insert anywhere, duplicate, undo,
 * per-block styles, one-at-a-time compressed image uploads) and adds what articles need: rich-text
 * formatting, repeatable items (stats, FAQ), tables, lists, buttons, file downloads, page settings,
 * a live search preview, reading time and unsaved-change protection.
 */
import { portfolioBuilder } from './portfolio-builder';
import { uploadImage, formatBytes } from './image-upload';

const extras = {
    // ── page meta (title, slug, SEO) ──
    get slugPreview() {
        return this.meta.slugEdited && this.meta.slug ? this.meta.slug : this.slugify(this.meta.title) || 'your-post';
    },
    slugify(s) {
        return (s || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 90);
    },
    get seoTitle() { return (this.meta.seoTitle || this.meta.title || 'Post title').trim(); },
    get seoDescription() {
        const firstText = this.blocks.find((b) => b.type === 'text' && b.body.trim());
        return (this.meta.seoDescription || this.meta.excerpt || (firstText ? this.plain(firstText.body) : '') || 'A short description of the post appears here in search results.').trim();
    },
    plain(s) { return (s || '').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1').replace(/(\*\*|==|`)/g, '').replace(/\s+/g, ' '); },

    get words() {
        let text = `${this.meta.title} ${this.meta.excerpt}`;
        this.blocks.forEach((b) => {
            text += ` ${b.title || ''} ${b.body || ''} ${b.caption || ''}`;
            (b.items || []).forEach((it) => { text += ` ${it.a || ''} ${it.b || ''}`; });
        });
        return (this.plain(text).match(/\S+/g) || []).length;
    },
    get readingMinutes() {
        const images = this.blocks.reduce((n, b) => n + (b.preview ? 1 : 0) + (b.gallery || []).length, 0);
        return Math.max(1, Math.round(this.words / 220 + images * 0.15));
    },
    get outline() {
        return this.blocks.filter((b) => b.type === 'heading' && b.body.trim()).map((b) => ({ key: b.key, text: b.body, level: b.settings.level }));
    },
    get scheduled() { return !!this.meta.publishAt && new Date(this.meta.publishAt) > new Date(); },
    get publishLabel() {
        if (this.scheduled) return this.meta.isPublished ? 'Update schedule' : 'Schedule';
        return this.meta.isPublished ? 'Update' : 'Publish';
    },

    // ── repeatable items (stats, FAQ) ──
    addItem(b) {
        if ((b.items || []).length >= 16) return;
        b.items.push({ a: '', b: '', id: Date.now() + Math.random() });
        this.$nextTick(() => document.querySelector(`#block-${b.key} [data-item]:last-of-type input, #block-${b.key} [data-item]:last-of-type textarea`)?.focus());
    },
    removeItem(b, i) { b.items.splice(i, 1); },
    moveItem(b, i, d) {
        const j = i + d;
        if (j < 0 || j >= b.items.length) return;
        b.items.splice(j, 0, b.items.splice(i, 1)[0]);
    },
    ensureItems(b, n) { while ((b.items || []).length < n) b.items.push({ a: '', b: '', id: Date.now() + Math.random() }); },

    // ── tables & lists (live preview of what readers will see) ──
    tableRows(b) {
        return (b.body || '').split('\n').map((l) => l.trim()).filter((l) => l && !/^\|?\s*:?-{2,}/.test(l))
            .map((l) => l.replace(/^\|+|\|+$/g, '').split('|').map((c) => this.plain(c.trim())));
    },
    listItems(b) {
        return (b.body || '').split('\n').map((l) => l.replace(/^(\s*[-*•]|\s*\d+[.)])\s+/, '').trim()).filter(Boolean);
    },

    // ── rich text: wraps the selection in the textarea with simple, safe markers ──
    format(b, kind) {
        const el = document.querySelector(`#block-${b.key} textarea[data-rich]`) || document.activeElement;
        if (!el || el.tagName !== 'TEXTAREA') return;
        const [s, e] = [el.selectionStart, el.selectionEnd];
        const value = el.value;
        const sel = value.slice(s, e);
        const wrap = { bold: ['**', '**', 'bold text'], italic: ['*', '*', 'italic text'], mark: ['==', '==', 'highlighted'], code: ['`', '`', 'code'] }[kind];
        let insert;
        let caret;
        if (wrap) {
            const text = sel || wrap[2];
            insert = wrap[0] + text + wrap[1];
            caret = [s + wrap[0].length, s + wrap[0].length + text.length];
        } else if (kind === 'link') {
            const url = window.prompt('Link address (https://… or a page on this site like /jobs)', 'https://');
            if (!url || url === 'https://') return;
            const text = sel || 'link text';
            insert = `[${text}](${url.trim()})`;
            caret = [s + 1, s + 1 + text.length];
        } else if (kind === 'bullet' || kind === 'number') {
            const start = value.lastIndexOf('\n', s - 1) + 1;
            const end = value.indexOf('\n', e) === -1 ? value.length : value.indexOf('\n', e);
            const lines = value.slice(start, end).split('\n');
            const done = lines.map((l, i) => (kind === 'bullet' ? '- ' : `${i + 1}. `) + l.replace(/^(\s*[-*•]|\s*\d+[.)])\s+/, ''));
            insert = done.join('\n');
            b.body = value.slice(0, start) + insert + value.slice(end);
            this.$nextTick(() => { el.focus(); el.setSelectionRange(start, start + insert.length); this.grow(el); });
            this.dirty = true;
            return;
        }
        b.body = value.slice(0, s) + insert + value.slice(e);
        this.dirty = true;
        this.$nextTick(() => { el.focus(); el.setSelectionRange(...caret); this.grow(el); });
    },

    // ── files for download blocks (PDF of a study, data sheets…) ──
    pickFile(b, e) {
        const f = e.target.files[0];
        e.target.value = '';
        if (!f) return;
        b.up = { status: 'uploading', stage: 'Uploading', progress: 0, file: f, error: null, note: '' };
        const up = b.up;
        uploadImage(this.fileUrl, f, (p) => { up.progress = p; }, 'file').then((r) => {
            b.existing = r.path;
            b.caption = r.name;
            b.size = formatBytes(r.bytes);
            if (!b.title) b.title = r.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
            up.status = 'done';
            setTimeout(() => { if (b.up === up) b.up = null; }, 1500);
        }).catch((err) => { up.status = 'error'; up.error = err.message; });
    },

    // ── canvas styling for posts (mirrors insights/_block.blade.php) ──
    wrap(b) {
        const s = b.settings;
        const soft = { blue: 'bg-[#eaf2fc]', violet: 'bg-[#f1ebfc]', green: 'bg-[#e6f4ed]', amber: 'bg-[#fdf3e1]', rose: 'bg-[#fcebf0]', ink: 'bg-zinc-100' }[this.meta.s.accent] || 'bg-zinc-100';
        const bg = { none: '', light: 'bg-zinc-50', dark: 'bg-ink text-white', accent: soft }[s.bg] || '';
        const pad = { none: 'py-0', s: (s.bg && s.bg !== 'none') ? 'py-8' : 'py-3', m: 'py-8', l: 'py-14' }[s.pad] ?? 'py-8';
        return `${bg} ${pad}`;
    },
    inner(b) {
        const s = b.settings;
        return [{ full: 'px-2', wide: 'px-6 sm:px-10', narrow: 'mx-auto max-w-2xl px-6' }[s.width] ?? 'mx-auto max-w-2xl px-6', s.align === 'center' ? 'text-center' : ''].join(' ');
    },
    accentText() { return { blue: 'text-[#2a78d6]', violet: 'text-[#7c4ddc]', green: 'text-[#16855a]', amber: 'text-[#b26a00]', rose: 'text-[#c8325a]', ink: 'text-ink' }[this.meta.s.accent] || 'text-ink'; },
    accentBg() { return { blue: 'bg-[#2a78d6]', violet: 'bg-[#7c4ddc]', green: 'bg-[#16855a]', amber: 'bg-[#d98b06]', rose: 'bg-[#c8325a]', ink: 'bg-ink' }[this.meta.s.accent] || 'bg-ink'; },

    // ── unsaved changes ──
    dirty: false,
    guard(e) { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } },
};

export function postBuilder(opts) {
    const b = portfolioBuilder(opts);
    Object.defineProperties(b, Object.getOwnPropertyDescriptors(extras));
    b.meta = opts.meta;
    b.fileUrl = opts.fileUrl;
    b.accent = opts.accent || 'blue';
    return b;
}
