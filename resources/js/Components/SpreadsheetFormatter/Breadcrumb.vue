<template>
    <Breadcrumb
        centered
        :items="items"
    ></Breadcrumb>
</template>

<script setup>
    import { ref } from "vue";

    import Breadcrumb from "@/Components/Breadcrumb.vue";

    const items = ref([
        {
            key: "tools",
            icon: "mdi-tools",
            title: "Ferramentas",
        },
        {
            key: "tools.spreadsheet-formatter",
            icon: "mdi-table-edit",
            title: "Formatador",
        },
        {
            key: "tools.spreadsheet-formatter.files",
            icon: "mdi-file-multiple",
            title: "Arquivos",
        },
    ]);
</script>
