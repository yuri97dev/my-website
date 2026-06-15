<template>
    <v-breadcrumbs
        :class="classes"
        :items="mappedItems"
    >
        <template #item="{ item, key }">
            <v-breadcrumbs-item
                :href="item.href"
                :disabled="item.disabled"
                @click.prevent="goTo(item.href)"
            >
                <v-icon v-if="!!item.icon" :icon="item.icon"></v-icon>
                {{ item.title }}
            </v-breadcrumbs-item>
        </template>
    </v-breadcrumbs>

    <v-divider></v-divider>
</template>

<script setup>
    import { computed } from "vue";

    import { goTo } from "@/Utils/Common";

    const props = defineProps({
        items: Array,
        centered: Boolean,
    });

    const mappedItems = computed(() => props.items.map(item => ({
        ...item,
        href: route(item.key, item.value || undefined),
        disabled: route().current(item.key, item.value || undefined),
    })));

    const classes = computed(() => {
        const defaultClasses = "my-0 px-0";
        if( !props.centered ) return defaultClasses;
        return `${defaultClasses} justify-center text-center`;
    });
</script>
