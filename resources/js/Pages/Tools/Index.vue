<template>
    <Container centered>
        <v-card
            height="var(--center-height)"
            style="width: 100%; max-width: var(--center-width);"
        >
            <v-list height="100%">
                <v-list-item
                    v-for="(item, index) in items"
                    :key="index"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :subtitle="item.subtitle"
                    :href="item.href"
                    lines="two"
                    slim
                    @click.prevent="goTo(item.href)"
                ></v-list-item>
            </v-list>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref } from "vue";

    import { goTo } from "@/Utils/Common";

    import Container from "@/Components/Container.vue";

    const items = ref([
        {
            title: "Formatador de Planilhas",
            subtitle: "Automatize a formatação e ganhe tempo",
            icon: "mdi-table-edit",
            href: route("tools.spreadsheet-formatter"),
            image: "/images/spreadsheet-formatter/thumbnail.png",
            image_placeholder: "/images/spreadsheet-formatter/thumbnail-placeholder.png",
        },
    ]);
</script>