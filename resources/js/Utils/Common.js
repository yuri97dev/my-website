import { router } from "@inertiajs/vue3";

export function goTo(p) {
    if( !p ) {
        throw new Error("No event or href provided");
    }

    if( typeof p == "string" ) {
        router.visit(p);
        return;
    }

    if( p instanceof Event ) {
        p.preventDefault();

        const href = p.currentTarget?.getAttribute("href");

        if( href ) {
            router.visit(href);
        }

        return;
    }

    throw new Error("Invalid parameter provided");
}

const KB = 1024;
const MB = KB * 1024;

function formatValue(value) {
    if( value >= 10 || Number.isInteger(value) ) {
        return Math.round(value).toString();
    }

    return value.toFixed(1).replace(/\.0$/, "");
}

export function formatBytes(bytes) {
    if( !bytes || bytes < 0 ) {
        return "0 KB";
    }

    if( bytes >= MB ) {
        return `${formatValue(bytes / MB)} MB`;
    }

    return `${formatValue(bytes / KB)} KB`;
}

export function validEmail(email) {
    if( !email || typeof email !== "string" ) {
        return false;
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}
