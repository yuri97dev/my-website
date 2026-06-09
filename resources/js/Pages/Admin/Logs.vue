<template>
    <Container>
        <v-card
            class="d-flex flex-column w-100"
            :loading="loading"
        >
            <v-card-item>
                <v-row align="center" justify="end">
                    <v-col cols="12" lg="4">
                        <v-text-field
                            type="date"
                            label="Data"
                            append-inner-icon="mdi-refresh"
                            :loading="loading"
                            @click:append-inner="getLogs"
                            v-model="date"
                        ></v-text-field>
                    </v-col>
                </v-row>
            </v-card-item>

            <v-divider></v-divider>

            <pre
                v-if="content"
                class="overflow-y-auto flex-grow-1 pa-4 ma-0 font-monospace text-pre-wrap"
            >{{ content }}</pre>

            <v-card-item v-else>
                <div
                    style="height: var(--center-height);"
                    class="d-flex flex-column align-center justify-center ga-2"
                >
                    <v-icon
                        icon="mdi-file-search-outline"
                        size="56"
                        color="grey"
                    ></v-icon>

                    <span v-if="loading" class="text-grey">
                        Carregando logs...
                    </span>

                    <span v-else class="text-grey">
                        Nenhum log encontrado para esta data.
                    </span>
                </div>
            </v-card-item>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, watch, onMounted } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";

    import { alertError } from "@/Utils/Alert";

    function formatDateInput(value) {
        const parsed = value instanceof Date ? value : new Date(value);
        const year = parsed.getFullYear();
        const month = String(parsed.getMonth() + 1).padStart(2, "0");
        const day = String(parsed.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    const date = ref(formatDateInput(new Date()));
    const content = ref("");
    const loading = ref(false);

    async function getLogs() {
        loading.value = true;

        try {
            const url = route("api.logs.show", date.value);
            const { data } = await api.get(url);

            content.value = data.content;
        } catch (e) {
            content.value = "";
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar logs");
        } finally {
            loading.value = false;
        }
    }

    watch(date, async () => {
        await getLogs();
    });

    onMounted(async () => {
        await getLogs();
    });
</script>
