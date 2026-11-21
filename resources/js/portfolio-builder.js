/**
 * Case-study builder: a block canvas with drag-and-drop, insert-anywhere, duplicate,
 * per-block style settings and undo for deletes. All values are submitted through
 * ordinary form inputs (blocks[key][...]); settings travel as one JSON string per block.
 */
import { compressImage, uploadImage, queue, formatBytes } from './image-upload';

export function portfolioBuilder({ blocks, defaults, labels, uploadUrl, cover, coverPath = null }) {
    let n = 0;
    const clone = (o) => JSON.parse(JSON.stringify(o));
    const fresh = (type) => ({
        key: ++n, type, body: '', caption: '', alt: '', embed_url: '',
        existing: null, preview: null, up: null, gallery: [],
        settings: clone(defaults[type] || {}),
    });
    const run = queue(3);

    return {
        blocks: blocks.map((b) => ({
            ...fresh(b.type), ...b, key: ++n, up: null,
            gallery: (b.gallery || []).map((g) => ({ ...g, id: ++n, up: null })),
            settings: { ...clone(defaults[b.type] || {}), ...(b.settings || {}) },
        })),
        cover: { preview: cover, path: coverPath, up: null },
        defaults, labels, formatBytes,
        selected: null,
        tab: 'add',
        picker: null,       // index where the inline "+" picker is open
        drag: null,         // { from: index } or { type }
        dropAt: null,
        trash: null,        // last deleted { block, index }

        get current() { return this.blocks.find((b) => b.key === this.selected) || null; },

        select(b) { this.selected = b.key; this.tab = 'style'; this.picker = null; },
        deselect() { this.selected = null; if (this.tab === 'style') this.tab = 'add'; },

        insert(type, at = null) {
            const b = fresh(type);
            const i = at ?? (this.current ? this.blocks.indexOf(this.current) + 1 : this.blocks.length);
            this.blocks.splice(i, 0, b);
            this.picker = null;
            this.select(b);
            this.$nextTick(() => {
                const el = document.getElementById(`block-${b.key}`);
                el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el?.querySelector('textarea, input[type=url]')?.focus({ preventScroll: true });
            });
        },
        move(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.blocks.length) return;
            this.blocks.splice(j, 0, this.blocks.splice(i, 1)[0]);
        },
        duplicate(i) {
            const src = this.blocks[i];
            const copy = { ...clone({ ...src, up: null, gallery: [] }), key: ++n, up: null };
            if (!copy.existing) copy.preview = null; // an image still uploading can't be copied
            copy.gallery = (src.gallery || []).filter((g) => g.path && !g.up).map((g) => ({ path: g.path, url: g.url, alt: g.alt, id: ++n, up: null }));
            this.blocks.splice(i + 1, 0, copy);
            this.select(copy);
        },
        remove(i) {
            const [b] = this.blocks.splice(i, 1);
            this.trash = { block: b, index: i };
            if (this.selected === b.key) this.deselect();
            clearTimeout(this._t);
            this._t = setTimeout(() => (this.trash = null), 6000);
        },
        undo() {
            if (!this.trash) return;
            this.blocks.splice(this.trash.index, 0, this.trash.block);
            this.select(this.trash.block);
            this.trash = null;
        },

        // ── drag & drop ──
        startDrag(e, payload) {
            this.drag = payload;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', 'block');
        },
        over(e, i) {
            if (!this.drag) return;
            const r = e.currentTarget.getBoundingClientRect();
            this.dropAt = e.clientY - r.top > r.height / 2 ? i + 1 : i;
        },
        drop() {
            if (!this.drag || this.dropAt === null) return this.endDrag();
            if (this.drag.type) {
                this.insert(this.drag.type, this.dropAt);
            } else {
                const from = this.drag.from;
                let to = this.dropAt;
                if (to > from) to--;
                if (to !== from) this.blocks.splice(to, 0, this.blocks.splice(from, 1)[0]);
            }
            this.endDrag();
        },
        endDrag() { this.drag = null; this.dropAt = null; },

        // ── media: every image is compressed, then uploaded on its own as soon as it's picked ──
        get uploading() {
            let count = this.cover.up?.status === 'uploading' ? 1 : 0;
            this.blocks.forEach((b) => {
                if (b.up?.status === 'uploading') count++;
                b.gallery.forEach((g) => g.up?.status === 'uploading' && count++);
            });
            return count;
        },
        get failed() {
            let count = this.cover.up?.status === 'error' ? 1 : 0;
            this.blocks.forEach((b) => {
                if (b.up?.status === 'error') count++;
                b.gallery.forEach((g) => g.up?.status === 'error' && count++);
            });
            return count;
        },
        /** Uploads `file` for `target` (an object with preview/path-ish fields); `done` stores the result. */
        send(target, file, done) {
            target.up = { status: 'uploading', stage: 'Preparing…', progress: 0, file, error: null, note: '' };
            const up = target.up;
            run(async () => {
                const small = await compressImage(file);
                up.stage = 'Uploading';
                const res = await uploadImage(uploadUrl, small, (p) => {
                    up.progress = p;
                    up.stage = p < 100 ? 'Uploading' : 'Optimizing…';
                });
                done(res);
                const saved = file.size - res.bytes;
                up.note = saved > file.size * 0.05 ? `${formatBytes(file.size)} → ${formatBytes(res.bytes)}` : formatBytes(res.bytes);
                up.status = 'done';
                setTimeout(() => { if (target.up === up) target.up = null; }, 2500);
            }).catch((e) => { up.status = 'error'; up.error = e.message; });
        },
        retry(target, done) { if (target.up?.file) this.send(target, target.up.file, done); },

        pickImage(b, e) {
            const f = e.target.files[0];
            e.target.value = '';
            if (!f) return;
            b.preview = URL.createObjectURL(f);
            b.existing = null;
            this.send(b, f, (r) => (b.existing = r.path));
        },
        imageDone(b) { return (r) => (b.existing = r.path); },
        addGallery(b, e) {
            const room = 12 - b.gallery.length;
            [...e.target.files].slice(0, Math.max(0, room)).forEach((f) => {
                const item = { id: ++n, path: null, url: URL.createObjectURL(f), alt: '', up: null };
                b.gallery.push(item);
                const live = b.gallery[b.gallery.length - 1]; // the reactive copy
                this.send(live, f, (r) => (live.path = r.path));
            });
            e.target.value = '';
        },
        galleryDone(g) { return (r) => (g.path = r.path); },
        pickCover(e) {
            const f = e.target.files[0];
            e.target.value = '';
            if (!f) return;
            this.cover.preview = URL.createObjectURL(f);
            this.cover.path = null;
            this.send(this.cover, f, (r) => (this.cover.path = r.path));
        },
        coverDone() { return (r) => (this.cover.path = r.path); },
        beforeSubmit(e) {
            if (this.uploading) { e.preventDefault(); this.notice = `Wait for ${this.uploading} image${this.uploading === 1 ? '' : 's'} to finish uploading.`; return; }
            if (this.failed) { e.preventDefault(); this.notice = `${this.failed} image${this.failed === 1 ? '' : 's'} failed to upload. Retry or remove ${this.failed === 1 ? 'it' : 'them'} first.`; }
        },
        notice: '',

        embedSrc(url) {
            let m = (url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]{11})/);
            if (m) return `https://www.youtube-nocookie.com/embed/${m[1]}`;
            m = (url || '').match(/vimeo\.com\/(\d+)/);
            return m ? `https://player.vimeo.com/video/${m[1]}` : null;
        },

        // ── canvas styling (mirrors portfolio/_block.blade.php) ──
        /** Full-width media get the shared 8px gutter (sides and between stacked images), like the public page. */
        gutter(b) {
            return ['image', 'gallery', 'embed'].includes(b.type) && b.settings.width === 'full' && !(b.type === 'gallery' && b.settings.gap === 'none');
        },
        wrap(b) {
            const s = b.settings;
            return [
                { none: '', light: 'bg-zinc-50', dark: 'bg-ink text-white', accent: 'bg-violet-50' }[s.bg] || '',
                s.pad === 'none' && this.gutter(b) ? 'py-1' : ({ none: 'py-0', s: 'py-5', m: 'py-9', l: 'py-14' }[s.pad] ?? 'py-9'),
            ].join(' ');
        },
        inner(b) {
            const s = b.settings;
            return [
                this.gutter(b) ? 'px-2' : ({ full: '', wide: 'px-6 sm:px-10', narrow: 'mx-auto max-w-2xl px-6' }[s.width] ?? 'px-6 sm:px-10'),
                s.align === 'center' ? 'text-center' : '',
            ].join(' ');
        },
        /** Images in a block that still have no alt text. */
        missingAlt(b) {
            if (['image', 'image_text'].includes(b.type)) return b.preview && !(b.alt || '').trim() ? 1 : 0;
            if (b.type === 'gallery') return b.gallery.filter((g) => !(g.alt || '').trim()).length;
            return 0;
        },
        editAlt(b) {
            this.select(b);
            this.$nextTick(() => document.querySelector('[data-alt-field]')?.focus());
        },
        set(key, value) { if (this.current) this.current.settings[key] = value; },
        has(key) { return this.current && key in (this.defaults[this.current.type] || {}); },
        grow(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; },
    };
}
