import test from 'node:test';
import assert from 'node:assert/strict';
import { prepareImage } from '../../resources/js/workshop/Pages/RegistrationScan/prepareImage.js';

function browser(t, { width = 4608, height = 3456, bitmapFails = false, emptyBlob = false } = {}) {
    const calls = [];
    const source = { width, height, close: () => calls.push(['close']) };
    const canvas = {
        getContext: () => ({
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

test('camera/gallery images follow the existing oriented 3072px JPEG preprocessing', async (t) => {
    const { calls, canvas } = browser(t);
    const original = new File(['camera image'], 'camera.png', { type: 'image/png' });
    const result = await prepareImage(original);
    assert.deepEqual(calls[0], ['decode', { imageOrientation: 'from-image' }]);
    assert.equal(canvas.width, 3072);
    assert.equal(canvas.height, 2304);
    assert.deepEqual(calls.find(([name]) => name === 'fillStyle'), ['fillStyle', '#ffffff']);
    assert.deepEqual(calls.find(([name]) => name === 'encode'), ['encode', 'image/jpeg', 0.9]);
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
