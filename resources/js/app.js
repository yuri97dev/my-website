import { createInertiaApp } from "@inertiajs/vue3";
import { ZiggyVue } from "ziggy-js";

import vuetify from "./vuetify";

createInertiaApp({
    withApp(app) {
        app.use(vuetify);
        app.use(ZiggyVue);
    },
});
