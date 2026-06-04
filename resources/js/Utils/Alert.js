import Swal from "sweetalert2";

import "sweetalert2/dist/sweetalert2.min.css";

function fire(options) {
    const buttonClasses = `v-btn v-btn--density-default v-btn--size-large`;

    const defaults = {
        confirmButtonText: "OK",
        cancelButtonText: "Cancelar",
        allowOutsideClick: false,
        buttonsStyling: false,
        theme: localStorage.getItem("theme") || "light",
        customClass: {
            closeButton: buttonClasses,
            confirmButton: `${buttonClasses} bg-primary`,
            cancelButton: buttonClasses,
            denyButton: buttonClasses,
        },
    };

    return Swal.fire({ ...defaults, ...options });
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
        icon: "question",
        title,
        text,
        showCancelButton: true,
        ...options,
    });
}

export { Swal };
