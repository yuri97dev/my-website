import { createVuetify } from "vuetify";
import { blueGrey } from "vuetify/util/colors";

import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

export default function createVuetifyInstance(defaultTheme = "light") {
    return createVuetify({
        ssr: true,
        theme: {
            defaultTheme: defaultTheme === "dark" ? "dark" : "light",
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
                noDataText: "Nenhum resultado encontrado",
            },
            VAutocomplete: {
                hideDetails: "auto",
                noDataText: "Nenhum resultado encontrado",
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
            VNumberInput: {
                hideDetails: "auto",
            },
            VCard: {
                rounded: "lg",
            },
            VProgressLinear: {
                bgOpacity: 1,
                bufferOpacity: 1,
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
}
