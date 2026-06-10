<template>
    <v-app>
        <v-app-bar elevation="0" class="border-b">
            <v-app-bar-nav-icon
                variant="text"
                @click.stop="drawer = !drawer"
            ></v-app-bar-nav-icon>

            <v-app-bar-title
                tag="a"
                class="d-flex align-center fill-height text-primary"
                style="flex: unset"
                :href="route('home')"
                @click="goTo"
            >
                <v-icon icon="mdi-brain"></v-icon>
                {{ appName }}
            </v-app-bar-title>

            <v-spacer></v-spacer>

            <v-btn
                v-for="(link, index) in links"
                :key="`app-bar-link-${index}`"
                v-bind="{ ...link, text: null }"
            ></v-btn>

            <AuthMenu />
        </v-app-bar>

        <NavigationDrawer v-model="drawer" />

        <v-main>
            <v-container class="h-100">
                <div
                    v-if="centered"
                    class="d-flex flex-column align-center justify-center w-100 h-100"
                >
                    <slot />
                </div>

                <div
                    v-else-if="gapped"
                    class="d-flex flex-column ga-4 h-100"
                >
                    <slot />
                </div>

                <slot v-else />
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
    import { ref, computed, watch } from "vue";

    import { usePage } from "@inertiajs/vue3";
    import { useTheme, useDisplay } from "vuetify";

    import AuthMenu from "@/Components/AuthMenu.vue";
    import NavigationDrawer from "@/Components/NavigationDrawer.vue";

    import { goTo } from "@/Utils/Common";

    const props = defineProps({
        centered: Boolean,
        gapped: Boolean,
    });

    const page = usePage();
    const theme = useTheme();
    const { lgAndUp } = useDisplay();

    const drawer = ref(lgAndUp.value ? true : false);

    const appName = computed(() => page.props.appName);

    const links = computed(() => [
        {
            icon: "mdi-theme-light-dark",
            text: "Alternar Tema",
            onClick: () => theme.toggle(),
        },
    ]);

    theme.change(localStorage.getItem("theme") || "system");

    watch(theme.name, (newTheme) => {
        localStorage.setItem("theme", newTheme);
    }, {
        deep: true,
        immediate: true,
    });
</script>
