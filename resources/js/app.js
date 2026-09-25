import { createInertiaApp, usePage } from "@inertiajs/vue3";
import { route, ZiggyVue } from "ziggy-js";

import { createTabsStore, TabsKey } from "@/Composables/useTabs";

import createVuetify from "./vuetify";

import DefaultLayout from "@/Layouts/Default.vue";

createInertiaApp({
    layout: () => DefaultLayout,
    withApp(app, { page: initialPage }) {
        const page = usePage();
        const ziggyProps = () => page.props?.ziggy ?? initialPage.props.ziggy;

        const ziggy = {
            get url() { return ziggyProps().url; },
            get port() { return ziggyProps().port; },
            get defaults() { return ziggyProps().defaults; },
            get routes() { return ziggyProps().routes; },
            get location() { return new URL(ziggyProps().location); },
        };

        globalThis.Ziggy = ziggy;
        globalThis.route = route;

        app.provide(TabsKey, createTabsStore());

        app.use(createVuetify(initialPage.props.theme ?? "light"));
        app.use(ZiggyVue, ziggy);
    },
}).then(() => {
    const loadingContainer = document.querySelector("#loading-container");

    if( loadingContainer ) {
        loadingContainer.classList.remove("opened");
    }
});
