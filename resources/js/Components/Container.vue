<template>
    <Head :title="pageTitle" />

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
    import { computed } from "vue";
    import { usePage, Head } from "@inertiajs/vue3";

    const props = defineProps({
        centered: Boolean,
        gapped: Boolean,
        title: String,
    });

    const page = usePage();

    const appName = computed(() => page.props.appName);

    const pageTitle = computed(() => {
        if( !props.title ) return appName.value;
        return `${appName.value} - ${props.title}`;
    });
</script>
