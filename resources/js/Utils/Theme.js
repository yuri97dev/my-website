export function resolveVuetifyTheme(theme) {
    return theme === "dark" ? "dark" : "light";
}

export function setThemeCookie(value) {
    if( typeof document === "undefined" ) return;
    document.cookie = `theme=${value};path=/;max-age=31536000;SameSite=Lax`;
}
