/**
 * Browser-side half of image handling: shrink a photo before sending it (saves data on slow
 * connections), then upload it on its own with progress. The server compresses again and is the
 * real safeguard; this step just means less to send.
 */

export const MAX_EDGE = 2560;
const QUALITY = 0.85;
const COMPRESSIBLE = ['image/jpeg', 'image/png', 'image/webp'];

/** Returns a smaller File where possible, otherwise the original. GIFs are left alone (animation). */
export async function compressImage(file) {
    if (!COMPRESSIBLE.includes(file.type) || typeof createImageBitmap !== 'function') return file;
    try {
        const bmp = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_EDGE / Math.max(bmp.width, bmp.height));
        if (scale === 1 && file.size < 400 * 1024) { bmp.close?.(); return file; } // already small
        const w = Math.round(bmp.width * scale);
        const h = Math.round(bmp.height * scale);
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(bmp, 0, 0, w, h);
        bmp.close?.();
        let blob = await new Promise((r) => canvas.toBlob(r, 'image/webp', QUALITY));
        if (!blob || blob.type !== 'image/webp') blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', QUALITY));
        if (!blob || (scale === 1 && blob.size >= file.size)) return file;
        const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + ext, { type: blob.type });
    } catch {
        return file;
    }
}

/** Uploads one image; resolves with the server's { path, url, bytes, original_bytes }. */
export function uploadImage(endpoint, file, onProgress = () => {}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const form = new FormData();
        form.append('image', file);
        xhr.open('POST', endpoint);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]')?.content || '');
        xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(Math.round((e.loaded / e.total) * 100));
        xhr.onload = () => {
            let body = {};
            try { body = JSON.parse(xhr.responseText); } catch { /* not JSON */ }
            if (xhr.status >= 200 && xhr.status < 300) return resolve(body);
            const msg = body?.errors?.image?.[0] || body?.message
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
