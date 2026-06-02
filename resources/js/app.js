import { createInertiaApp } from "@inertiajs/vue3";
import { createVuetify } from "vuetify";
import { ZiggyVue } from "ziggy-js";

import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

const vuetify = createVuetify();

createInertiaApp({
    withApp(app) {
        app.use(vuetify);
        app.use(ZiggyVue);
    },
});
