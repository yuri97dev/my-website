import { createVuetify } from "vuetify";

import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

export default createVuetify({
    defaults: {
        VTextField: {
            hideDetails: "auto",
        },
        VSelect: {
            hideDetails: "auto",
        },
        VAutocomplete: {
            hideDetails: "auto",
        },
        VCheckbox: {
            hideDetails: "auto",
        },
        VRadio: {
            hideDetails: "auto",
        },
        VFileUpload: {
            hideDetails: "auto",
        },
    },
});
