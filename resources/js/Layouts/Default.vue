<template>
    <v-app>
        <Head :title="pageTitle" />

        <slot></slot>
    </v-app>
</template>

<script setup>
    import { computed } from "vue";

    import { usePage, Head } from "@inertiajs/vue3";

    const page = usePage();

    const appName = computed(() => page.props.appName);
    const pageTitle = computed(() => appName.value);
</script>
