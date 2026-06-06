<template>
    <Container centered>
        <div style="width: 100%; max-width: var(--center-width);">
            <v-alert
                type="info"
                variant="tonal"
                icon="mdi-information"
                class="mb-4"
            >
                <span v-if="files.length === 0">
                    Nenhum arquivo encontrado.
                </span>

                <span v-else>
                    Os arquivos abaixo serão processados e excluídos
                    automaticamente em aproximadamente 30 minutos.
                </span>
            </v-alert>

            <v-card height="var(--center-height)">
                <v-list>
                    <v-list-item
                        v-for="(file, index) in files"
                        :key="index"
                        prepend-icon="mdi-file-outline"
                        lines="two"
                    >
                        <v-list-item-title
                            :title="file.original_name"
                            class="text-wrap"
                        >
                            {{ file.original_name }}
                        </v-list-item-title>

                        <template #append>
                            <v-btn
                                icon
                                variant="tonal"
                                color="primary"
                                class="rounded"
                                loading
                            ></v-btn>
                        </template>
                    </v-list-item>
                </v-list>
            </v-card>
        </div>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";

    const props = defineProps({
        files: Array,
    });
</script>
