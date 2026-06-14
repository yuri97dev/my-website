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

function toastFire(options) {
    const defaults = {
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        theme: localStorage.getItem("theme") || "light",
        didOpen: (toast) => {
            toast.addEventListener("mouseenter", Swal.stopTimer);
            toast.addEventListener("mouseleave", Swal.resumeTimer);
        },
    };

    return Swal.fire({ ...defaults, ...options });
}

export function toastSuccess(title, text) {
    return toastFire({ icon: "success", title, text });
}

export function toastError(title, text) {
    return toastFire({ icon: "error", title, text });
}

export function toastWarning(title, text) {
    return toastFire({ icon: "warning", title, text });
}

export function toastInfo(title, text) {
    return toastFire({ icon: "info", title, text });
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

export { Swal };
