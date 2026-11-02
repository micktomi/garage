import test from 'node:test';
import assert from 'node:assert/strict';
import { prepareImage } from '../../resources/js/workshop/Pages/RegistrationScan/prepareImage.js';

function browser(t, { width = 4608, height = 3456, bitmapFails = false, emptyBlob = false } = {}) {
    const calls = [];
    const source = { width, height, close: () => calls.push(['close']) };
    const canvas = {
        getContext: () => ({
            save: () => calls.push(['save']),
            restore: () => calls.push(['restore']),
            translate: (...args) => calls.push(['translate', ...args]),
            rotate: (...args) => calls.push(['rotate', ...args]),
            set fillStyle(value) { calls.push(['fillStyle', value]); },
            fillRect: (...args) => calls.push(['fillRect', ...args]),
            drawImage: (...args) => calls.push(['drawImage', ...args]),
        }),
        toBlob: (callback, type, quality) => {
            calls.push(['encode', type, quality]);
            callback(emptyBlob ? null : new Blob(['processed image'], { type }));
        },
    };
    const globals = {
        createImageBitmap: async (file, options) => {
            calls.push(['decode', options]);
            if (bitmapFails) throw new Error('unsupported decoder');
            return source;
        },
        document: { createElement: () => canvas },
        Image: class {
            constructor() { this.width = width; this.height = height; }
            set src(value) { calls.push(['fallback', value]); this.onload(); }
        },
    };
    for (const [key, value] of Object.entries(globals)) {
        const previous = Object.getOwnPropertyDescriptor(globalThis, key);
        Object.defineProperty(globalThis, key, { value, configurable: true });
        t.after(() => previous ? Object.defineProperty(globalThis, key, previous) : delete globalThis[key]);
    }
    t.mock.method(URL, 'createObjectURL', () => 'blob:registration-test');
    t.mock.method(URL, 'revokeObjectURL', (url) => calls.push(['revoke', url]));
    return { calls, canvas };
}

test('camera/gallery images follow the oriented high-detail 4096px JPEG preprocessing', async (t) => {
    const { calls, canvas } = browser(t);
    const original = new File(['camera image'], 'camera.png', { type: 'image/png' });
    const result = await prepareImage(original);
    assert.deepEqual(calls[0], ['decode', { imageOrientation: 'from-image' }]);
    assert.equal(canvas.width, 4096);
    assert.equal(canvas.height, 3072);
    assert.deepEqual(calls.find(([name]) => name === 'fillStyle'), ['fillStyle', '#ffffff']);
    assert.deepEqual(calls.find(([name]) => name === 'encode'), ['encode', 'image/jpeg', 0.94]);
    assert.ok(calls.some(([name]) => name === 'close'));
    assert.equal(result.name, 'registration-scan.jpg');
    assert.equal(result.type, 'image/jpeg');
    assert.equal(await result.text(), 'processed image');
});

test('browser decoder fallback releases the object URL and never upscales a small image', async (t) => {
    const { calls, canvas } = browser(t, { width: 800, height: 1200, bitmapFails: true });
    const result = await prepareImage(new File(['gallery image'], 'gallery.jpg', { type: 'image/jpeg' }));
    assert.equal(canvas.width, 800);
    assert.equal(canvas.height, 1200);
    assert.ok(calls.some(([name]) => name === 'fallback'));
    assert.deepEqual(calls.find(([name]) => name === 'revoke'), ['revoke', 'blob:registration-test']);
    assert.equal(result.type, 'image/jpeg');
});

test('an unavailable JPEG encoder preserves the original image for server validation', async (t) => {
    browser(t, { emptyBlob: true });
    const original = new File(['image'], 'original.webp', { type: 'image/webp' });
    assert.equal(await prepareImage(original), original);
});

test('camera JPEG is recompressed to the actual server upload limit', async (t) => {
    const { calls, canvas } = browser(t);
    canvas.toBlob = (cb, type, quality) => {
        calls.push(['encode', type, quality]);
        cb(new Blob(['x'.repeat(quality > 0.9 ? 2500 : 1500)], { type }));
    };
    const result = await prepareImage(new File(['original'], 'camera.jpg', { type: 'image/jpeg' }), { maxBytes: 1800 });
    assert.ok(result.size <= 1800);
    assert.deepEqual(calls.filter(([name]) => name === 'encode'), [['encode', 'image/jpeg', 0.94], ['encode', 'image/jpeg', 0.9]]);
    assert.equal(canvas.width, 4096);
});

test('noisy camera images are resized further without returning an oversize file', async (t) => {
    const { calls, canvas } = browser(t);
    canvas.toBlob = (cb, type) => cb(new Blob(['x'.repeat(canvas.width > 2500 ? 2500 : 1500)], { type }));
    const result = await prepareImage(new File(['original'], 'camera.jpg', { type: 'image/jpeg' }), { maxBytes: 1800 });
    assert.ok(result.size <= 1800);
    assert.ok(canvas.width < 4096);
    assert.ok(calls.some(([name]) => name === 'close'));
});

test('an image that cannot fit the server limit is rejected and its decoder is released', async (t) => {
    const { calls, canvas } = browser(t);
    canvas.toBlob = (cb, type) => cb(new Blob(['x'.repeat(3000)], { type }));
    await assert.rejects(prepareImage(new File(['original'], 'camera.jpg'), { maxBytes: 1800 }), /size/);
    assert.ok(canvas.width >= 1280);
    assert.ok(calls.some(([name]) => name === 'close'));
});

test('an unavailable encoder never silently returns an oversize original', async (t) => {
    const { calls } = browser(t, { emptyBlob: true });
    await assert.rejects(prepareImage(new File(['x'.repeat(3000)], 'camera.jpg'), { maxBytes: 1800 }), /size/);
    assert.ok(calls.some(([name]) => name === 'close'));
});


test('manual rotation changes the uploaded pixels, swaps dimensions and preserves aspect ratio', async (t) => {
    const { calls, canvas } = browser(t, { width: 1200, height: 1600 });
    const result = await prepareImage(new File(['original'], 'portrait.jpg'), { quarterTurns: 3 });
    assert.equal(canvas.width, 1600);
    assert.equal(canvas.height, 1200);
    assert.deepEqual(calls.find(([name]) => name === 'translate'), ['translate', 800, 600]);
    assert.deepEqual(calls.find(([name]) => name === 'rotate'), ['rotate', 3 * Math.PI / 2]);
    assert.deepEqual(calls.find(([name]) => name === 'drawImage').slice(2), [-600, -800, 1200, 1600]);
    assert.equal(result.type, 'image/jpeg');
    assert.ok(calls.some(([name]) => name === 'restore'));
    assert.ok(calls.some(([name]) => name === 'close'));
});

test('a failed rotation encoder cannot silently upload the unrotated original', async (t) => {
    const { calls } = browser(t, { emptyBlob: true });
    await assert.rejects(prepareImage(new File(['original'], 'portrait.jpg'), { quarterTurns: 1 }), /rotate/);
    assert.ok(calls.some(([name]) => name === 'close'));
});
