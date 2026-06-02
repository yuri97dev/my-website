import { createInertiaApp } from "@inertiajs/vue3";
import { createVuetify } from "vuetify";

import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

const vuetify = createVuetify();

createInertiaApp({
    withApp(app) {
        app.use(vuetify);
    },
});
