<template>
    <v-app>
        <v-app-bar elevation="0" class="border-b">
            <v-container class="d-flex align-center py-0 h-100">
                <v-app-bar-title>
                    <v-icon icon="mdi-brain"></v-icon>
                    {{ appName }}
                </v-app-bar-title>

                <v-spacer></v-spacer>

                <v-btn
                    text="Alternar tema"
                    prepend-icon="mdi-theme-light-dark"
                    class="rounded-0 h-100"
                    @click="theme.toggle()"
                ></v-btn>
            </v-container>
        </v-app-bar>

        <v-main>
            <v-container>
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
    import { computed } from "vue";

    import { usePage } from "@inertiajs/vue3";
    import { useTheme } from "vuetify";

    const page = usePage();
    const theme = useTheme();

    const appName = computed(() => page.props.appName);
</script>
