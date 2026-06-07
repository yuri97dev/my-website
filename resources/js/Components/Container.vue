<template>
    <v-app>
        <v-app-bar elevation="0" class="border-b">
            <v-container class="d-flex align-center py-0 h-100">
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

                <div v-if="lgAndUp" class="align-center fill-height">
                    <v-btn
                        v-for="(link, index) in links"
                        :key="`desktop-${index}`"
                        class="h-100 rounded-0"
                        v-bind="{ ...link, icon: null, prependIcon: link.icon }"
                    ></v-btn>
                </div>

                <template v-else>
                    <v-btn
                        v-for="(link, index) in links"
                        :key="`mobile-${index}`"
                        v-bind="{ ...link, text: null }"
                    ></v-btn>
                </template>
            </v-container>
        </v-app-bar>

        <v-main>
            <v-container class="h-100">
                <div
                    v-if="centered"
                    class="d-flex flex-column align-center justify-center w-100 h-100"
                >
                    <slot />
                </div>

                <slot v-else />
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
    import { computed, watch } from "vue";

    import { usePage } from "@inertiajs/vue3";
    import { useTheme, useDisplay } from "vuetify";

    import { goTo } from "@/Utils/Common";

    const props = defineProps({
        centered: Boolean,
    });

    const page = usePage();
    const theme = useTheme();
    const { lgAndUp } = useDisplay();

    const appName = computed(() => page.props.appName);

    const authUser = computed(() => page.props?.auth?.user);

    const links = computed(() => [
        {
            icon: "mdi-tools",
            text: "Ferramentas",
            href: route("tools"),
            active: route().current("tools"),
            onClick: (e) => goTo(e),
        },
        {
            icon: "mdi-theme-light-dark",
            text: "Alternar Tema",
            onClick: () => theme.toggle(),
        },
        !!authUser.value ? {
            icon: "mdi-account",
            text: authUser.value.name?.split(" ")[0],
            // href: route("account"),
            // active: route().current("account"),
            onClick: (e) => goTo(e),
        } : {
            icon: "mdi-login",
            text: "Login",
            href: route("login"),
            active: route().current("login"),
            onClick: (e) => goTo(e),
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
