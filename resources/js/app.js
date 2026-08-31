import { createInertiaApp } from "@inertiajs/vue3";
import { route, ZiggyVue } from "ziggy-js";

import createVuetify from "./vuetify";

createInertiaApp({
    withApp(app, { page }) {
        const ziggy = {
            ...page.props.ziggy,
            location: new URL(page.props.ziggy.location),
        };

        globalThis.Ziggy = ziggy;
        globalThis.route = route;

        app.use(createVuetify());
        app.use(ZiggyVue, ziggy);
    },
});
