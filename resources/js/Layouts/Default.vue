<template>
    <v-app>
        <Head :title="pageTitle" />

        <NavigationDrawer v-model="drawer" />

        <slot></slot>
    </v-app>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";

    import { usePage, Head } from "@inertiajs/vue3";
    import { useDisplay } from "vuetify";

    import NavigationDrawer from "@/Components/NavigationDrawer.vue";

    const page = usePage();
    const { lgAndUp } = useDisplay();

    const appName = computed(() => page.props.appName);
    const pageTitle = computed(() => appName.value);

    const drawer = ref(false);

    onMounted(() => {
        if( lgAndUp.value ) {
            drawer.value = true;
        }
    });
</script>
