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

export function formatDate(date, { includeTime = false, includeSeconds = false } = {}) {
    const parsed = date instanceof Date ? date : new Date(date);

    if( Number.isNaN(parsed.getTime()) ) {
        return "";
    }

    const options = {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    };

    if( includeTime ) {
        const timeOptions = {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
        };

        if( includeSeconds ) {
            timeOptions.second = "2-digit";
        }

        const datePart = new Intl.DateTimeFormat("pt-BR", options).format(parsed);
        const timePart = new Intl.DateTimeFormat("pt-BR", timeOptions).format(parsed);

        return `${datePart} ${timePart}`;
    }

    return new Intl.DateTimeFormat("pt-BR", options).format(parsed);
}
