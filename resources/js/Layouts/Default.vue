<template>
    <v-app>
        <NavigationDrawer v-model="drawer" />

        <v-app-bar
            elevation="0"
            class="border-b"
            :extended="showAppBarExtension"
        >
            <v-app-bar-nav-icon
                variant="text"
                @click.stop="drawer = !drawer"
            ></v-app-bar-nav-icon>

            <v-app-bar-title
                tag="a"
                class="text-primary"
                style="flex: unset"
                :href="route('home')"
                @click="goTo"
            >
                {{ appName }}
            </v-app-bar-title>

            <v-spacer></v-spacer>

            <v-btn
                v-for="(link, index) in links"
                :key="`app-bar-link-${index}`"
                v-bind="{ ...link, text: null }"
            ></v-btn>

            <AuthMenu />

            <template
                v-if="showAppBarExtension"
                #extension
            >
                <slot name="app-bar-extension">
                    <AppBarTabs />
                </slot>
            </template>
        </v-app-bar>

        <slot></slot>
    </v-app>
</template>

<script setup>
    import { ref, computed, onMounted, useSlots } from "vue";

    import { usePage, Head } from "@inertiajs/vue3";
    import { useDisplay, useTheme } from "vuetify";

    import NavigationDrawer from "@/Components/NavigationDrawer.vue";
    import AppBarTabs from "@/Components/AppBarTabs.vue";
    import AuthMenu from "@/Components/AuthMenu.vue";

    import { useTabs } from "@/Composables/useTabs";
    import { goTo } from "@/Utils/Common";
    import { setThemeCookie } from "@/Utils/Theme";

    const page = usePage();
    const slots = useSlots();
    const theme = useTheme();
    const { list } = useTabs();
    const { lgAndUp } = useDisplay();

    const appName = computed(() => page.props.appName);

    const showAppBarExtension = computed(() => {
        return "app-bar-extension" in slots || list.value.length > 0;
    });

    const links = computed(() => [
        {
            icon: "mdi-theme-light-dark",
            text: "Alternar Tema",
            onClick: () => {
                theme.toggle();
                setThemeCookie(theme.global.current.value.dark ? "dark" : "light");
            },
        },
    ]);

    const drawer = ref(false);

    onMounted(() => {
        if( lgAndUp.value ) {
            drawer.value = true;
        }
    });
</script>
