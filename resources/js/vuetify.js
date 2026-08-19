import { createVuetify } from "vuetify";
import { blueGrey } from "vuetify/util/colors";

import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

export default createVuetify({
    theme: {
        defaultTheme: "system",
        themes: {
            light: {
                colors: {
                    primary: blueGrey.darken2,
                },
            },
            dark: {
                colors: {
                    primary: blueGrey.lighten3,
                },
            },
        },
    },
    defaults: {
        VTextField: {
            hideDetails: "auto",
        },
        VTextarea: {
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
        VSwitch: {
            inset: "material",
            hideDetails: "auto",
        },
        VRadio: {
            hideDetails: "auto",
        },
        VFileInput: {
            hideDetails: "auto",
        },
        VFileUpload: {
            hideDetails: "auto",
        },
        VDateInput: {
            hideDetails: "auto",
            inputFormat: "dd/MM/yyyy",
        },
        VNumberInput: {
            hideDetails: "auto",
        },
        VCard: {
            rounded: "lg",
        },
        VTabsWindow: {
            crossfade: true,
            class: "overflow-visible",
        },
        VDataTable: {
            noDataText: "Nenhum resultado encontrado",
            loadingText: "Carregando",
        },
        VDataTableVirtual: {
            noDataText: "Nenhum resultado encontrado",
            loadingText: "Carregando",
        },
        VDataTableServer: {
            itemsPerPageOptions: [10, 25, 50, 100, 200],
            noDataText: "Nenhum resultado encontrado",
            itemsPerPageText: "Itens por página",
            loadingText: "Carregando",
        },
        Dialog: {
            closeOnBack: false,
        },
        BottomSheet: {
            closeOnBack: false,
        },
    },
});
