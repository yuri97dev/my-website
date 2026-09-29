export function setThemeCookie(value) {
    if( typeof document === "undefined" ) return;

    const hexaColor = value == "dark" ? "#212121" : "#ffffff";

    document.cookie = `theme=${value};path=/;max-age=31536000;SameSite=Lax`;

    document.querySelector("meta[name=theme-color]")?.setAttribute("content", hexaColor)
}
