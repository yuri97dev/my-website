<template>
    <Container>
        <v-row>
            <v-col
                v-for="(card, index) in cards"
                :key="index"
                cols="12" xl="4" lg="6"
            >
                <Link :href="card.href">
                    <v-card
                        :title="card.title"
                        :subtitle="card.subtitle"
                        class="mx-auto"
                        border
                    >
                        <v-img
                            cover
                            height="300"
                            class="align-end text-white rounded-b"
                            gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                            :src="card.image"
                            :lazy-src="card.image_placeholder"
                        />
                    </v-card>
                </Link>
            </v-col>
        </v-row>
    </Container>
</template>

<script setup>
    import { ref } from "vue";
    import { Link } from "@inertiajs/vue3";

    import Container from "@/Components/Container.vue";

    const cards = ref([
        {
            title: "Formatador de Planilhas",
            subtitle: "Automatize a formatação e ganhe tempo",
            icon: "mdi-table",
            href: route("tools.spreadsheet-formatter"),
            image: "/images/spreadsheet-formatter/thumbnail.png",
            image_placeholder: "/images/spreadsheet-formatter/thumbnail-placeholder.png",
        },
    ]);
</script>