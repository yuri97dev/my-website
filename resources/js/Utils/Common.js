import { router } from "@inertiajs/vue3";
import { format as formatDateFns, isValid } from "date-fns";

const KB = 1024;
const MB = KB * 1024;

function formatValue(value) {
    if( value >= 10 || Number.isInteger(value) ) {
        return Math.round(value).toString();
    }

    return value.toFixed(1).replace(/\.0$/, "");
}

function normalizeDateString(value) {
    if( value.includes("T") || value.includes("Z") ) {
        return value;
    }

    if( /^\d{4}-\d{2}-\d{2}$/.test(value) ) {
        return `${value}T00:00:00`;
    }

    return value.replace(" ", "T");
}

export function parseDate(value) {
    if( value == null || value === "" ) {
        return null;
    }

    if( value instanceof Date ) {
        return isValid(value) ? value : null;
    }

    if(
        typeof value === "number"
        || (typeof value === "string" && /^\d+$/.test(value.trim()))
    ) {
        const timestamp = Number(value);
        const milliseconds = timestamp < 1e11 ? timestamp * 1000 : timestamp;
        const date = new Date(milliseconds);

        return isValid(date) ? date : null;
    }

    if( typeof value === "string" ) {
        const date = new Date(normalizeDateString(value.trim()));

        return isValid(date) ? date : null;
    }

    return null;
}

export function formatDate(value, pattern, fallback = "") {
    const date = parseDate(value);

    if( !date ) {
        return fallback;
    }

    return formatDateFns(date, pattern);
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

export function formatText(text) {
    if( !text || typeof text !== "string" ) {
        return text;
    }

    return text
        .toUpperCase()
        .split(" ")
        .filter(palavra => !!palavra)
        .join(" ")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

export function validEmail(email) {
    if( !email || typeof email !== "string" ) {
        return false;
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

export async function copyText(text) {
    try {
        if( navigator.clipboard ) {
            await navigator.clipboard.writeText(text);
            return { success: true };
        }

        const textarea = document.createElement("textarea");

        textarea.value = text;
        textarea.style.position = "absolute";
        textarea.style.left = "-9999px";

        document.body.appendChild(textarea);

        textarea.select();

        document.execCommand("copy");

        document.body.removeChild(textarea);

        return { success: true };
    } catch(e) {
        const error = e?.message || "Falha ao copiar";
        return { success: false, error };
    }
}

export function formatNumber(value, fractionDigits = 0) {
    return Number(value || 0).toLocaleString("pt-BR", {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
}

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
