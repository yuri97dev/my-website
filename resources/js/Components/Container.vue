<template>
    <v-app>
        <Head :title="pageTitle" />

        <NavigationDrawer v-model="drawer" />

        <v-app-bar
            elevation="0"
            class="border-b"
            :extended="extended"
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

            <template #extension>
                <slot name="app-bar-extension">
                    <AppBarTabs />
                </slot>
            </template>
        </v-app-bar>

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
    import { ref, computed, watch, onMounted, nextTick, useSlots } from "vue";

    import { usePage, Head } from "@inertiajs/vue3";
    import { useTheme, useDisplay } from "vuetify";

    import AppBarTabs from "@/Components/AppBarTabs.vue";
    import AuthMenu from "@/Components/AuthMenu.vue";
    import NavigationDrawer from "@/Components/NavigationDrawer.vue";

    import { useTabs } from "@/Composables/useTabs";
    import { goTo } from "@/Utils/Common";

    const { list } = useTabs();
    const slots = useSlots();

    const showAppBarExtension = computed(() => {
        return "app-bar-extension" in slots || list.value.length > 0;
    });

    const extended = ref(showAppBarExtension.value);

    const props = defineProps({
        centered: Boolean,
        gapped: Boolean,
        title: String,
    });

    const page = usePage();
    const theme = useTheme();
    const { lgAndUp } = useDisplay();

    const pageTitle = computed(() => {
        if( !props.title ) return appName.value;
        return `${appName.value} - ${props.title}`;
    });

    const drawer = ref(false);

    const appName = computed(() => page.props.appName);

    const links = computed(() => [
        {
            icon: "mdi-theme-light-dark",
            text: "Alternar Tema",
            onClick: () => theme.toggle(),
        },
    ]);

    watch(theme.name, (newTheme) => {
        if( typeof document === "undefined" ) return;
        document.cookie = `theme=${newTheme};path=/;max-age=31536000;SameSite=Lax`;
    });

    watch(showAppBarExtension, (value) => {
        if( !value ) {
            extended.value = false;
            return;
        }

        if( !extended.value ) {
            nextTick(() => {
                extended.value = true;
            });
        }
    });

    onMounted(() => {
        if( lgAndUp.value ) {
            drawer.value = true;
        }

        if( !extended.value && showAppBarExtension.value ) {
            nextTick(() => {
                extended.value = true;
            });
        }
    });
</script>
