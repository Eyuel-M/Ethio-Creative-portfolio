/**
 * Browser-side half of image handling. Images are sent exactly as chosen — the server does the one careful,
 * quality-preserving pass. The browser only steps in when a file is too big for the server to accept at all,
 * and then uses very high quality.
 */

export const MAX_BYTES = 20 * 1024 * 1024;
export const MAX_DIMENSION = 8000;
const COMPRESSIBLE = ['image/jpeg', 'image/png', 'image/webp'];

/** Returns the original file unless it would be rejected for size; then a high-quality version that fits. */
export async function compressImage(file) {
    if (!COMPRESSIBLE.includes(file.type) || typeof createImageBitmap !== 'function') return file;
    try {
        const bmp = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const tooBig = file.size > MAX_BYTES || Math.max(bmp.width, bmp.height) > MAX_DIMENSION;
        if (!tooBig) { bmp.close?.(); return file; }
        const scale = Math.min(1, MAX_DIMENSION / Math.max(bmp.width, bmp.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * scale);
        canvas.height = Math.round(bmp.height * scale);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        bmp.close?.();
        const type = file.type === 'image/jpeg' ? 'image/jpeg' : 'image/webp'; // WebP keeps transparency
        for (const q of [0.95, 0.92, 0.88]) {
            const blob = await new Promise((r) => canvas.toBlob(r, type, q));
            if (blob && blob.size <= MAX_BYTES) return new File([blob], file.name.replace(/\.[^.]+$/, '') + (blob.type === 'image/webp' ? '.webp' : '.jpg'), { type: blob.type });
        }
        return file; // let the server explain the limit
    } catch {
        return file;
    }
}

/** Uploads one image; resolves with the server's { path, url, bytes, original_bytes }. */
export function uploadImage(endpoint, file, onProgress = () => {}, field = 'image') {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const form = new FormData();
        form.append(field, file);
        xhr.open('POST', endpoint);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]')?.content || '');
        xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(Math.round((e.loaded / e.total) * 100));
        xhr.onload = () => {
            let body = {};
            try { body = JSON.parse(xhr.responseText); } catch { /* not JSON */ }
            if (xhr.status >= 200 && xhr.status < 300) return resolve(body);
            const msg = body?.errors?.[field]?.[0] || body?.message
                || (xhr.status === 413 ? 'This image is too large for the server.' : xhr.status === 429 ? 'Too many uploads at once — retry in a moment.' : 'Upload failed.');
            reject(new Error(msg));
        };
        xhr.onerror = () => reject(new Error('Connection lost.'));
        xhr.send(form);
    });
}

/** Runs async jobs with at most `limit` in flight. */
export function queue(limit = 3) {
    let active = 0;
    const waiting = [];
    const next = () => {
        if (active >= limit || !waiting.length) return;
        active++;
        const { job, resolve, reject } = waiting.shift();
        job().then(resolve, reject).finally(() => { active--; next(); });
    };
    return (job) => new Promise((resolve, reject) => { waiting.push({ job, resolve, reject }); next(); });
}

export const formatBytes = (b) => (b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');
