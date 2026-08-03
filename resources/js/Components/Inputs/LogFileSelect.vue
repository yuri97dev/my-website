<template>
    <v-card hover>
        <v-autocomplete
            label="Arquivo de log"
            prepend-inner-icon="mdi-file-document-outline"
            variant="solo"
            item-title="name"
            item-value="name"
            return-object
            clearable
            hide-no-data
            no-filter
            :items="files"
            :loading="loading"
            v-model="modelValue"
        >
            <template #item="{ props, item }">
                <v-list-item
                    v-bind="props"
                    color="primary"
                >
                    <template #subtitle>
                        {{ formatBytes(item.size) }}
                        ·
                        {{ formatDate(item.modified_at, "dd/MM/yyyy HH:mm") }}
                    </template>
                </v-list-item>
            </template>

            <template #append-inner>
                <div
                    class="d-flex align-center ga-2"
                    @mousedown.stop.prevent
                >
                    <v-btn
                        size="small"
                        icon="mdi-refresh"
                        variant="tonal"
                        color="primary"
                        :loading="loading"
                        @click.stop="fetchFiles"
                    ></v-btn>
                </div>
            </template>
        </v-autocomplete>
    </v-card>
</template>

<script setup>
    import { ref, onMounted } from "vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { formatBytes, formatDate } from "@/Utils/Common";

    const modelValue = defineModel({
        default: null,
    });

    const files = ref([]);
    const loading = ref(false);

    async function fetchFiles() {
        loading.value = true;

        try {
            const url = route("api.logs.files.index");
            const { data } = await api.get(url);

            files.value = data.files || [];
        } catch (e) {
            files.value = [];
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar arquivos de log");
        } finally {
            loading.value = false;
        }
    }

    onMounted(async () => {
        await fetchFiles();
    });
</script>
