// Keep the existing OCR upload preprocessing: oriented decode, 3072px, JPEG 0.9.
export async function prepareImage(original) {
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
    const maxDimension = 3072;
    const scale = Math.min(
        1,
        maxDimension / Math.max(source.width, source.height),
    );
    const canvas = document.createElement("canvas");
    canvas.width = Math.max(1, Math.round(source.width * scale));
    canvas.height = Math.max(1, Math.round(source.height * scale));
    const context = canvas.getContext("2d");
    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(source, 0, 0, canvas.width, canvas.height);
    cleanup();
    const blob = await new Promise((resolve) =>
        canvas.toBlob(resolve, "image/jpeg", 0.9),
    );
    if (!blob) return original;
    return new File([blob], "registration-scan.jpg", {
        type: "image/jpeg",
        lastModified: Date.now(),
    });
}
