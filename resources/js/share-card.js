/**
 * Post card studio: draws a LinkedIn / Instagram announcement image from a freelancer's own portfolio covers,
 * entirely in the browser (canvas), using the site's two fonts. Images are same-origin, so the canvas can be exported.
 */
const FORMATS = {
    linkedin: { w: 1200, h: 627, label: 'LinkedIn post', max: 4, layout: 'side' },
    linkedin_square: { w: 1200, h: 1200, label: 'LinkedIn square', max: 4, layout: 'stack' },
    instagram: { w: 1080, h: 1350, label: 'Instagram post', max: 4, layout: 'stack' },
    story: { w: 1080, h: 1920, label: 'Instagram story', max: 5, layout: 'stack' },
};
const THEMES = {
    light: { bg: '#fbfbfd', ink: '#0c0c10', muted: '#71717a', pill: '#ffffff', pillInk: '#0c0c10', line: '#e4e4e7', accent: '#2f6bff', mark: '#0c0c10', markInk: '#ffffff' },
    dark: { bg: '#0c0c10', ink: '#ffffff', muted: '#a1a1aa', pill: '#1f1f25', pillInk: '#ffffff', line: '#27272a', accent: '#34c759', mark: '#ffffff', markInk: '#0c0c10' },
    brand: { bg: '#2f6bff', ink: '#ffffff', muted: 'rgba(255,255,255,.78)', pill: '#ffffff', pillInk: '#0c0c10', line: 'rgba(255,255,255,.25)', accent: '#ffffff', mark: '#ffffff', markInk: '#2f6bff' },
};
const SANS = "'Inter Variable', system-ui, sans-serif";
const DISPLAY = "'Inter Tight Variable', 'Inter Variable', system-ui, sans-serif";

export function shareCard(cfg) {
    const images = {};
    const load = (src) => images[src] ??= new Promise((resolve) => {
        const img = new Image();
        img.decoding = 'async';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });

    return {
        formats: FORMATS,
        format: 'linkedin',
        theme: 'light',
        projects: cfg.projects,
        picked: cfg.preselect.length ? cfg.preselect.slice(0, 5) : cfg.projects.slice(0, 4).map((p) => p.id),
        headlines: cfg.headlines,
        headline: 0,
        custom: '',
        showLink: true,
        busy: false,
        note: '',
        canShareFiles: false,

        init() {
            try { this.canShareFiles = !!(navigator.canShare && navigator.canShare({ files: [new File([''], 'x.png', { type: 'image/png' })] })); } catch { this.canShareFiles = false; }
            this.$watch('format', () => this.render());
            this.$watch('theme', () => this.render());
            this.$watch('picked', () => this.render());
            this.$watch('headline', () => this.render());
            this.$watch('custom', () => this.render());
            this.$watch('showLink', () => this.render());
            Promise.all([
                document.fonts.load(`700 40px ${DISPLAY}`), document.fonts.load(`500 20px ${SANS}`), document.fonts.load(`600 20px ${SANS}`),
            ]).finally(() => this.render());
        },

        get text() { return (this.custom.trim() || this.headlines[this.headline] || '').trim(); },
        get max() { return FORMATS[this.format].max; },
        toggle(id) {
            const i = this.picked.indexOf(id);
            if (i >= 0) this.picked.splice(i, 1);
            else if (this.picked.length < this.max) this.picked.push(id);
            else this.note = `Up to ${this.max} projects for this format.`;
        },
        order(id) { const i = this.picked.indexOf(id); return i >= 0 ? i + 1 : null; },

        async render() {
            const f = FORMATS[this.format];
            const t = THEMES[this.theme];
            const c = this.$refs.canvas;
            c.width = f.w;
            c.height = f.h;
            const ctx = c.getContext('2d');
            const chosen = this.picked.map((id) => this.projects.find((p) => p.id === id)).filter((p) => p && p.cover);
            const imgs = (await Promise.all(chosen.map((p) => load(p.cover)))).filter(Boolean);

            ctx.fillStyle = t.bg;
            ctx.fillRect(0, 0, f.w, f.h);

            const s = f.w / 1080; // scale unit
            const gap = Math.round(10 * s);
            const pad = Math.round(f.layout === 'side' ? 40 : 44 * s);
            if (f.layout === 'side') {
                const textW = Math.round(f.w * 0.43);
                this.collage(ctx, imgs, textW + gap, gap, f.w - textW - gap * 2, f.h - gap * 2, gap, Math.round(16 * s));
                this.copy(ctx, t, pad, pad, textW - pad * 1.4, f.h - pad * 2, s, 'side');
            } else {
                const textH = Math.round(f.h * (this.format === 'story' ? 0.3 : 0.34));
                this.collage(ctx, imgs, gap, gap, f.w - gap * 2, f.h - textH - gap, gap, Math.round(18 * s));
                this.copy(ctx, t, pad, f.h - textH + pad * 0.6, f.w - pad * 2, textH - pad * 1.4, s, 'stack');
            }
        },

        /** Even-gap collage, matching the portfolio pages. */
        collage(ctx, imgs, x, y, w, h, g, r) {
            const cells = [];
            const n = Math.min(imgs.length, 5);
            const wide = w > h;
            if (n === 0) {
                ctx.save();
                ctx.fillStyle = 'rgba(127,127,127,.12)';
                this.round(ctx, x, y, w, h, r);
                ctx.fill();
                ctx.restore();
                return;
            }
            if (n === 1) cells.push([x, y, w, h]);
            else if (n === 2) wide ? cells.push([x, y, (w - g) / 2, h], [x + (w + g) / 2, y, (w - g) / 2, h]) : cells.push([x, y, w, (h - g) / 2], [x, y + (h + g) / 2, w, (h - g) / 2]);
            else if (n === 3) {
                if (wide) { const big = (w - g) * 0.6; cells.push([x, y, big, h], [x + big + g, y, w - big - g, (h - g) / 2], [x + big + g, y + (h + g) / 2, w - big - g, (h - g) / 2]); }
                else { const big = (h - g) * 0.6; cells.push([x, y, w, big], [x, y + big + g, (w - g) / 2, h - big - g], [x + (w + g) / 2, y + big + g, (w - g) / 2, h - big - g]); }
            } else if (n === 4) {
                const cw = (w - g) / 2; const ch = (h - g) / 2;
                cells.push([x, y, cw, ch], [x + cw + g, y, cw, ch], [x, y + ch + g, cw, ch], [x + cw + g, y + ch + g, cw, ch]);
            } else {
                const top = (h - g) * 0.55; const bh = h - top - g;
                cells.push([x, y, (w - g) / 2, top], [x + (w + g) / 2, y, (w - g) / 2, top]);
                const bw = (w - 2 * g) / 3;
                for (let i = 0; i < 3; i++) cells.push([x + i * (bw + g), y + top + g, bw, bh]);
            }
            cells.forEach(([cx, cy, cw, ch], i) => {
                const img = imgs[i];
                ctx.save();
                this.round(ctx, cx, cy, cw, ch, r);
                ctx.clip();
                const scale = Math.max(cw / img.width, ch / img.height);
                const sw = cw / scale; const sh = ch / scale;
                ctx.drawImage(img, (img.width - sw) / 2, (img.height - sh) / 2, sw, sh, cx, cy, cw, ch);
                ctx.restore();
            });
        },

        copy(ctx, t, x, y, w, h, s, mode) {
            const k = mode === 'side' ? 1.12 : s;
            // Brand mark
            const m = Math.round(44 * k);
            ctx.fillStyle = t.mark;
            this.round(ctx, x, y, m, m, Math.round(12 * k));
            ctx.fill();
            ctx.strokeStyle = t.markInk;
            ctx.lineWidth = Math.max(2, 3.2 * k);
            ctx.lineCap = 'round';
            [[0.3, 0.3, 0.7], [0.3, 0.5, 0.58], [0.3, 0.7, 0.7]].forEach(([a, yy, b]) => { ctx.beginPath(); ctx.moveTo(x + m * a, y + m * yy); ctx.lineTo(x + m * b, y + m * yy); ctx.stroke(); });
            ctx.fillStyle = '#34c759';
            ctx.beginPath(); ctx.arc(x + m * 0.76, y + m * 0.5, m * 0.07, 0, Math.PI * 2); ctx.fill();
            ctx.textBaseline = 'middle';
            ctx.font = `700 ${Math.round(24 * k)}px ${DISPLAY}`;
            ctx.fillStyle = t.ink;
            ctx.fillText(cfg.brand, x + m + 14 * k, y + m / 2);

            // Headline, wrapped to fit
            ctx.textBaseline = 'alphabetic';
            const bottomBlock = Math.round((this.showLink ? 150 : 96) * k);
            let size = Math.round((mode === 'side' ? 50 : 66) * k);
            let lines;
            for (; size > 22; size -= 2) {
                ctx.font = `700 ${size}px ${DISPLAY}`;
                lines = this.wrap(ctx, this.text, w);
                if (lines.length * size * 1.08 <= h - m - bottomBlock - 30 * k && lines.length <= (mode === 'side' ? 5 : 3)) break;
            }
            ctx.fillStyle = t.ink;
            let ty = y + m + 30 * k + size;
            lines.forEach((ln) => { ctx.fillText(ln, x, ty); ty += size * 1.08; });

            // Name + title
            const by = y + h - (this.showLink ? 66 * k : 0);
            ctx.font = `600 ${Math.round(26 * k)}px ${SANS}`;
            ctx.fillStyle = t.ink;
            ctx.fillText(this.fit(ctx, cfg.name, w), x, by - 34 * k);
            ctx.font = `500 ${Math.round(21 * k)}px ${SANS}`;
            ctx.fillStyle = t.muted;
            ctx.fillText(this.fit(ctx, cfg.subtitle, w), x, by - 4 * k);

            if (this.showLink) {
                ctx.font = `600 ${Math.round(20 * k)}px ${SANS}`;
                const label = this.fit(ctx, cfg.displayUrl, w - 40 * k);
                const pw = ctx.measureText(label).width + 40 * k; const ph = 46 * k;
                ctx.fillStyle = t.pill;
                this.round(ctx, x, y + h - ph, pw, ph, ph / 2);
                ctx.fill();
                if (this.theme === 'light') { ctx.strokeStyle = t.line; ctx.lineWidth = 2; ctx.stroke(); }
                ctx.fillStyle = t.pillInk;
                ctx.textBaseline = 'middle';
                ctx.fillText(label, x + 20 * k, y + h - ph / 2);
                ctx.textBaseline = 'alphabetic';
            }
        },

        wrap(ctx, text, w) {
            const out = []; let line = '';
            for (const word of text.split(/\s+/)) {
                const test = line ? line + ' ' + word : word;
                if (ctx.measureText(test).width > w && line) { out.push(line); line = word; } else line = test;
            }
            if (line) out.push(line);
            return out;
        },
        fit(ctx, text, w) {
            if (ctx.measureText(text).width <= w) return text;
            while (text.length > 1 && ctx.measureText(text + '…').width > w) text = text.slice(0, -1);
            return text + '…';
        },
        round(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        },

        blob() { return new Promise((res) => this.$refs.canvas.toBlob(res, 'image/png')); },
        filename() { return `${cfg.slug}-${this.format.replace('_', '-')}.png`; },
        async download() {
            this.busy = true;
            const b = await this.blob();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(b);
            a.download = this.filename();
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 4000);
            this.busy = false;
            this.track('share_card_download');
        },
        async share() {
            this.busy = true;
            try {
                const file = new File([await this.blob()], this.filename(), { type: 'image/png' });
                await navigator.share({ files: [file], text: this.caption(), title: cfg.name });
                this.track('share_card_share');
            } catch { /* cancelled */ }
            this.busy = false;
        },
        caption() {
            const tags = cfg.hashtags.join(' ');
            return `${this.text}\n\n${cfg.captionBody}\n\n👉 ${cfg.url}\n\n${tags}`;
        },
        async copyCaption() {
            try { await navigator.clipboard.writeText(this.caption()); this.note = 'Caption copied — paste it into your post.'; }
            catch { this.note = 'Select the caption below and copy it.'; }
            this.track('copy_link');
        },
        linkedIn() {
            this.track('share_click', 'LinkedIn card');
            window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(cfg.url), '_blank', 'noopener');
        },
        track(name, label = this.format) {
            const pv = document.querySelector('meta[name=ec-pv]')?.content;
            if (!navigator.sendBeacon) return;
            const body = new FormData();
            body.append('name', name);
            if (pv) body.append('pv', pv);
            body.append('label', label);
            navigator.sendBeacon('/a/event', body);
        },
    };
}
