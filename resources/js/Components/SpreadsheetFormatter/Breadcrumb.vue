<template>
    <v-breadcrumbs
        class="my-0 justify-center"
        :items="items"
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
    import { ref } from "vue";

    import { goTo } from "@/Utils/Common";

    const items = ref([
        {
            icon: "mdi-tools",
            title: "Ferramentas",
            href: route("tools"),
            disabled: route().current("tools"),
        },
        {
            icon: "mdi-table-edit",
            title: "Formatador",
            href: route("tools.spreadsheet-formatter"),
            disabled: route().current("tools.spreadsheet-formatter"),
        },
        {
            icon: "mdi-file-multiple",
            title: "Arquivos",
            href: route("tools.spreadsheet-formatter.files"),
            disabled: route().current("tools.spreadsheet-formatter.files"),
        },
    ]);
</script>
