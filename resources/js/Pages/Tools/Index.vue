<template>
    <Container title="Ferramentas" centered>
        <div
            class="d-flex flex-column ga-4"
            style="width: 100%; max-width: var(--center-width);"
        >
            <v-card
                v-for="(item, index) in items"
                :key="index"
                hover
                @click.prevent="goTo(item.href)"
            >
                <div
                    class="d-flex align-center justify-center bg-grey-lighten-2"
                    style="height: 180px"
                >
                    <v-icon
                        :icon="item.icon"
                        size="48"
                    ></v-icon>
                </div>

                <v-card-title>
                    {{ item.title }}
                </v-card-title>

                <v-card-subtitle class="text-wrap">
                    {{ item.subtitle }}
                </v-card-subtitle>

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
        </div>
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