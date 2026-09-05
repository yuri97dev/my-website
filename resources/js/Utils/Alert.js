import Swal from "sweetalert2";

import "sweetalert2/dist/sweetalert2.min.css";

function getTheme() {
    return document.cookie.match(/(?:^|; )theme=(dark|light)/)?.[1] || "light";
}

function fire(options) {
    const buttonClasses = `v-btn v-btn--density-default v-btn--size-large`;

    const defaults = {
        confirmButtonText: "OK",
        cancelButtonText: "Cancelar",
        allowOutsideClick: false,
        buttonsStyling: false,
        theme: getTheme(),
        customClass: {
            closeButton: buttonClasses,
            confirmButton: `${buttonClasses} bg-primary`,
            cancelButton: buttonClasses,
            denyButton: buttonClasses,
        },
    };

    return Swal.fire({ ...defaults, ...options });
}

function toastFire(options) {
    const defaults = {
        toast: true,
        position: "bottom",
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3000,
        timerProgressBar: true,
        theme: getTheme(),
        didOpen: (toast) => {
            toast.addEventListener("mouseenter", Swal.stopTimer);
            toast.addEventListener("mouseleave", Swal.resumeTimer);
        },
    };

    return Swal.fire({ ...defaults, ...options });
}

export function toastSuccess(text) {
    return toastFire({ icon: "success", title: text });
}

export function toastError(text) {
    return toastFire({ icon: "error", title: text });
}

export function toastWarning(text) {
    return toastFire({ icon: "warning", title: text });
}

export function toastInfo(text) {
    return toastFire({ icon: "info", title: text });
}

export function alertSuccess(title, text) {
    return fire({ icon: "success", title, text });
}

export function alertError(title, text) {
    return fire({ icon: "error", title, text });
}

export function alertWarning(title, text) {
    return fire({ icon: "warning", title, text });
}

export function alertInfo(title, text) {
    return fire({ icon: "info", title, text });
}

export function confirm(title, text, options = {}) {
    return fire({
        icon: "warning",
        title,
        text,
        showCancelButton: true,
        confirmButtonText: "Confirmar",
        ...options,
    });
}

export function confirmDelete(title, text, options = {}) {
    const buttonClasses = `v-btn v-btn--density-default v-btn--size-large`;

    return fire({
        icon: "warning",
        title,
        text,
        showCancelButton: true,
        confirmButtonText: "Excluir",
        customClass: {
            closeButton: buttonClasses,
            confirmButton: `${buttonClasses} bg-error`,
            cancelButton: buttonClasses,
            denyButton: buttonClasses,
        },
        ...options,
    });
}

export { Swal };
