import api from "@/api";

export async function refreshCsrfCookie() {
    await api.get("/sanctum/csrf-cookie");
}
