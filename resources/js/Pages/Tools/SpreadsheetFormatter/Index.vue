<template>
    <Container title="Formatador de CSV e XLSX" centered>
        <div style="width: 100%; max-width: var(--center-width);">
            <v-alert
                v-if="shouldShowFileSizeInfo"
                type="info"
                variant="tonal"
                icon="mdi-information"
                class="mb-4"
            >
                O limite atual é de {{ MAX_FILE_SIZE_MB }} MB por arquivo.
                Estamos trabalhando para oferecer contas gratuitas e opções pagas com limites maiores no futuro.
            </v-alert>

            <v-card>
                <v-progress-linear
                    color="primary"
                    v-model="uploadProgress"
                ></v-progress-linear>

                <Breadcrumb />

                <v-card-item>
                    <v-file-upload
                        scrim="primary"
                        show-size
                        clearable
                        inset-file-list
                        title="Arraste e solte o arquivo aqui"
                        divider-text="ou clique no botão abaixo"
                        browse-text="Selecionar Arquivo"
                        :error-messages="fileErrorMessage"
                        v-model="form.file"
                    ></v-file-upload>

                    <v-expand-transition>
                        <div v-if="shouldShowOptions">
                            <v-divider class="mt-4"></v-divider>

                            <v-checkbox
                                label="Remover HTML"
                                hide-details
                                disabled
                                :model-value="true"
                            ></v-checkbox>

                            <v-checkbox
                                label="Remover duplicatas (EM BREVE)"
                                hide-details
                                disabled
                                :model-value="false"
                            ></v-checkbox>

                            <v-checkbox
                                label="Ajustar espaçamento entre palavras"
                                hide-details
                                v-model="form.adjust_word_spacing"
                            ></v-checkbox>

                            <v-checkbox
                                label="Remover acentos e caracteres especiais"
                                hide-details
                                v-model="form.normalize_text"
                            ></v-checkbox>

                            <v-checkbox
                                label="Remover quebras de linha"
                                hide-details
                                v-model="form.remove_line_breaks"
                            ></v-checkbox>

                            <span class="subheading d-block mt-2">
                                Formato de Texto:
                            </span>

                            <v-chip-group
                                column
                                color="primary"
                                v-model="form.text_format"
                            >
                                <v-chip
                                    v-for="format in textFormats"
                                    :key="format.value"
                                    :value="format.value"
                                >{{ format.title }}</v-chip>
                            </v-chip-group>

                            <span class="subheading d-block mt-2">
                                Formato de Exportação:
                            </span>

                            <v-chip-group
                                color="primary"
                                v-model="form.export_format"
                            >
                                <v-chip
                                    v-for="format in exportFormats"
                                    :key="format.value"
                                    :value="format.value"
                                >{{ format.title }}</v-chip>
                            </v-chip-group>
                        </div>
                    </v-expand-transition>
                </v-card-item>

                <template v-if="shouldShowOptions">
                    <v-divider></v-divider>

                    <v-card-actions>
                        <v-btn
                            block
                            size="large"
                            variant="tonal"
                            color="primary"
                            prepend-icon="mdi-table-edit"
                            :disabled="!form.file"
                            :loading="isUploading"
                            @click="uploadFile"
                        >Formatar Planilha</v-btn>
                    </v-card-actions>
                </template>
            </v-card>
        </div>
    </Container>
</template>

<script setup>
    import { ref, computed, watch, onMounted } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import Breadcrumb from "@/Components/SpreadsheetFormatter/Breadcrumb.vue";

    import { alertError } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";

    const props = defineProps({
        max_upload_size: Number,
    });

    const MAX_FILE_SIZE_MB = props.max_upload_size / 1024;
    const BYTES_PER_MB = 1024 * 1024;

    const form = ref({
        adjust_word_spacing: true,
        normalize_text: true,
        remove_line_breaks: false,
        text_format: "uppercase",
        export_format: "xlsx",
        file: null,
    });

    const textFormats = ref([
        { title: "Original", value: null },
        { title: "MAIÚSCULO", value: "uppercase" },
        { title: "minúsculo", value: "lowercase" },
        { title: "Iniciais Maiúsculas", value: "ucfirst" },
    ]);

    const exportFormats = ref([
        { title: "CSV", value: "csv" },
        { title: "XLSX", value: "xlsx" },
    ]);

    const isUploading = ref(false);
    const uploadProgress = ref(0);

    const isFileSizeValid = computed(() => {
        const { file } = form.value;
        const fileSize = !file ? 0 : (file.size / BYTES_PER_MB);

        return fileSize > 0 && fileSize <= MAX_FILE_SIZE_MB;
    });

    const isFileExtensionValid = computed(() => {
        const { file } = form.value;
        const fileExtension = !file ? null : file.name.split(".").pop().toLowerCase();

        return [ "csv", "xlsx" ].includes(fileExtension);
    });

    const fileErrorMessage = computed(() => {
        const { file } = form.value;

        if( !file ) return null;

        if( !isFileExtensionValid.value ) return "Formato inválido. Use arquivos CSV ou XLSX";
        if( !isFileSizeValid.value ) return `O arquivo deve ter no máximo ${MAX_FILE_SIZE_MB} MB`;

        return null;
    });

    const shouldShowFileSizeInfo = computed(() => {
        const { file } = form.value;
        return file && isFileExtensionValid.value && !isFileSizeValid.value;
    });

    const shouldShowOptions = computed(() => {
        return isFileSizeValid.value && isFileExtensionValid.value;
    });

    async function uploadFile() {
        isUploading.value = true;
        uploadProgress.value = 0;

        const formData = new FormData;

        Object.keys(form.value)
            .filter(field => field != "file")
            .forEach(field => {
                const valueAsNumber = Number(form.value[field]);
                formData.append(field, isNaN(valueAsNumber) ? form.value[field] : valueAsNumber);
            });

        formData.append("spreadsheet", form.value.file);

        try {
            const url = route("api.tools.spreadsheet-formatter.upload");

            const { data } = await api.post(url, formData, {
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                },
            });

            goTo(route("tools.spreadsheet-formatter.files"));
        } catch (e) {
            isUploading.value = false;
            const message = e.response?.data?.message || "Erro ao enviar arquivo";
            alertError("Ooops", message);
        }
    }

    watch(() => form.value, (newValue) => {
        localStorage.spreadsheetFormatter = JSON.stringify({ ...newValue, file: null });
    }, { deep: true });

    onMounted(() => {
        const userPreferences = JSON.parse(localStorage.spreadsheetFormatter || "{}");
        form.value = { ...form.value, ...userPreferences };
    });
</script>