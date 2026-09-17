<template>
    <v-app-bar
        elevation="0"
        class="border-b"
        :extended="showAppBarExtension"
    >
        <!-- <v-app-bar-nav-icon
            variant="text"
            @click.stop="drawer = !drawer"
        ></v-app-bar-nav-icon> -->

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

    <v-main>
        <v-container :class="{ 'h-100': centered || gapped }">
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
</template>

<script setup>
    import { ref, computed, onMounted, useSlots } from "vue";

    import { usePage } from "@inertiajs/vue3";
    import { useTheme } from "vuetify";

    import AppBarTabs from "@/Components/AppBarTabs.vue";
    import AuthMenu from "@/Components/AuthMenu.vue";

    import { useTabs } from "@/Composables/useTabs";
    import { goTo } from "@/Utils/Common";
    import { setThemeCookie } from "@/Utils/Theme";

    const { list } = useTabs();
    const slots = useSlots();

    const showAppBarExtension = computed(() => {
        return "app-bar-extension" in slots || list.value.length > 0;
    });

    const props = defineProps({
        centered: Boolean,
        gapped: Boolean,
        title: String,
    });

    const page = usePage();
    const theme = useTheme();

    const appName = computed(() => page.props.appName);

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
</script>
