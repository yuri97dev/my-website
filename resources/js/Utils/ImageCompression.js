import imageCompression from "browser-image-compression";

const KB = 1024;

/** Arquivos menores que isso raramente valem recompressão. */
const TINY_FILE_BYTES = 40 * KB;

/** Abaixo deste tamanho, só comprime se as dimensões forem grandes. */
const SMALL_FILE_BYTES = 120 * KB;

/** Dimensão máxima (lado maior) para considerar a imagem já adequada. */
const SMALL_DIMENSION_PX = 640;

/** Economia mínima para aceitar o arquivo comprimido (evita reencode desnecessário). */
const MIN_SAVINGS_RATIO = 0.05;

const SKIP_TYPES = new Set([
    "image/gif",
    "image/svg+xml",
]);

const DEFAULT_OPTIONS = {
    maxSizeMB: 1.5,
    maxWidthOrHeight: 1920,
    initialQuality: 0.85,
    useWebWorker: true,
};

export function isCompressibleImage(file) {
    return file instanceof File
        && typeof file.type === "string"
        && file.type.startsWith("image/")
        && !SKIP_TYPES.has(file.type);
}

function readImageDimensions(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);

            resolve({
                width: image.naturalWidth,
                height: image.naturalHeight,
            });
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error("Não foi possível ler as dimensões da imagem"));
        };

        image.src = url;
    });
}

function shouldSkipCompression(file, { width, height }) {
    if( file.size <= TINY_FILE_BYTES ) {
        return true;
    }

    const longestSide = Math.max(width, height);

    if( file.size <= SMALL_FILE_BYTES && longestSide <= SMALL_DIMENSION_PX ) {
        return true;
    }

    return false;
}

function buildCompressionOptions(file, overrides = {}) {
    const options = {
        ...DEFAULT_OPTIONS,
        ...overrides,
    };

    if( file.type === "image/png" || file.type === "image/webp" ) {
        options.initialQuality = Math.max(options.initialQuality, 0.9);
    }

    return options;
}

function toFileWithOriginalName(compressed, original) {
    if( compressed === original ) {
        return original;
    }

    return new File([ compressed ], original.name, {
        type: compressed.type || original.type,
        lastModified: original.lastModified,
    });
}

/**
 * Comprime imagens para upload mais rápido, preservando a original quando
 * a compressão não traz ganho ou pode degradar a qualidade.
 */
export async function compressImageFile(file, options = {}) {
    if( !isCompressibleImage(file) ) {
        return file;
    }

    let dimensions;

    try {
        dimensions = await readImageDimensions(file);
    } catch {
        return file;
    }

    if( shouldSkipCompression(file, dimensions) ) {
        return file;
    }

    try {
        const compressed = await imageCompression(
            file,
            buildCompressionOptions(file, options),
        );

        if( compressed.size >= file.size ) {
            return file;
        }

        const savingsRatio = 1 - (compressed.size / file.size);

        if( savingsRatio < MIN_SAVINGS_RATIO ) {
            return file;
        }

        return toFileWithOriginalName(compressed, file);
    } catch {
        return file;
    }
}

export async function compressImageFiles(files, options = {}) {
    return Promise.all(
        files.map(file => compressImageFile(file, options)),
    );
}
