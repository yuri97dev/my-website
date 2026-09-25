import { defineConfig } from "vite";
import inertia from "@inertiajs/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vuetify from "vite-plugin-vuetify";
import { bunny } from "laravel-vite-plugin/fonts";
import { fileURLToPath, URL } from "node:url";
import path from "node:path";

const root = fileURLToPath(new URL(".", import.meta.url));

export default defineConfig({
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            "ziggy-js": path.resolve(root, "vendor/tightenco/ziggy"),
        },
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
            fonts: [
                bunny("Instrument Sans", {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia({
            ssr: false,
        }),
        vue(),
        vuetify({ autoImport: { labs: true } }),
    ],
    server: {
        host: true,
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
