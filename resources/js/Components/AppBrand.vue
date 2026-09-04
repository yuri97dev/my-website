<template>
    <div
        class="d-flex align-center justify-center flex-column ga-2 bg-surface-light"
        style="height: 144px"
    >
        <v-avatar size="64">
            <v-img
                src="/images/logo.webp"
                alt="Logo do site"
            ></v-img>
        </v-avatar>

        {{ appName }}
    </div>
</template>

<script setup>
    import { computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    const appName = computed(() => usePage().props.appName);
</script>
