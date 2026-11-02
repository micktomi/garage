// Oriented decode, at most 4096px. Keep the JPEG within the PHP upload limit,
// while retaining fine document text whenever the upload budget permits it.
export async function prepareImage(
    original,
    { maxBytes = 10 * 1024 * 1024, quarterTurns = 0 } = {},
) {
    const turns = ((quarterTurns % 4) + 4) % 4;
    let source, cleanup;
    if (typeof createImageBitmap === "function") {
        try {
            source = await createImageBitmap(original, {
                imageOrientation: "from-image",
            });
            cleanup = () => source.close();
        } catch {
            /* browser decoder fallback */
        }
    }
    if (!source) {
        const url = URL.createObjectURL(original);
        source = await new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error("decode"));
            };
            image.src = url;
        });
        cleanup = () => URL.revokeObjectURL(url);
    }
    try {
        const width = source.naturalWidth || source.width;
        const height = source.naturalHeight || source.height;
        if (!width || !height) throw new Error("decode");
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        if (!context) throw new Error("decode");
        let dimension = Math.min(4096, Math.max(width, height));
        while (true) {
            const scale = dimension / Math.max(width, height);
            const scaledWidth = Math.max(1, Math.round(width * scale));
            const scaledHeight = Math.max(1, Math.round(height * scale));
            canvas.width = turns % 2 ? scaledHeight : scaledWidth;
            canvas.height = turns % 2 ? scaledWidth : scaledHeight;
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, canvas.width, canvas.height);
            if (turns) {
                context.save();
                context.translate(canvas.width / 2, canvas.height / 2);
                context.rotate((turns * Math.PI) / 2);
                context.drawImage(source, -scaledWidth / 2, -scaledHeight / 2, scaledWidth, scaledHeight);
                context.restore();
            } else {
                context.drawImage(source, 0, 0, scaledWidth, scaledHeight);
            }
            for (const quality of [0.94, 0.9, 0.85]) {
                const blob = await new Promise((resolve) =>
                    canvas.toBlob(resolve, "image/jpeg", quality),
                );
                if (!blob) {
                    if (turns) throw new Error("rotate");
                    if (original.size <= maxBytes) return original;
                    throw new Error("size");
                }
                if (blob.size <= maxBytes) {
                    return new File([blob], "registration-scan.jpg", {
                        type: "image/jpeg",
                        lastModified: Date.now(),
                    });
                }
            }
            // Avoid reducing the document to an unreadable thumbnail.
            if (dimension <= 1280) throw new Error("size");
            dimension = Math.max(1280, Math.floor(dimension * 0.8));
        }
    } finally {
        cleanup();
    }
}
