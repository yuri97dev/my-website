<template>
    <Container>
        <h1>
            Soluções simples para tarefas repetitivas
        </h1>

        <v-row>
            <v-col
                v-for="item in items"
                :key="item.title"
                cols="12"
                lg="4"
            >
                <v-card
                    hover
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :subtitle="item.subtitle"
                    @click.prevent="goTo(item.href)"
                >
                    <v-card-text>
                        <div class="d-flex flex-wrap ga-2">
                            <v-chip
                                v-for="feature in item.features"
                                :key="feature"
                                :value="feature"
                                color="primary"
                                variant="outlined"
                            >{{ feature }}</v-chip>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </Container>
</template>

<script setup>
    import { ref } from "vue";

    import { goTo } from "@/Utils/Common";

    import Container from "@/Components/Container.vue";

    const items = ref([
        {
            title: "Formatador de CSV e XLSX",
            subtitle: "Limpe e padronize textos de planilhas em poucos cliques",
            icon: "mdi-table-edit",
            href: route("tools.spreadsheet-formatter"),
            features: [
                "Remove HTML",
                "Padroniza texto",
                "Exporta CSV/XLSX",
            ],
        },
    ]);
</script>