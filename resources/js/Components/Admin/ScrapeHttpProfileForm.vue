<template>
    <BottomSheet
        overlay-id="scrape-http-profile-form"
        :title="isEditing ? 'Editar Perfil HTTP' : 'Novo Perfil HTTP'"
        :actions="sheetActions"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="9" xl="10">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="3" xl="2" class="d-flex align-center justify-lg-end">
                <v-switch
                    label="Ativo"
                    color="primary"
                    :error-messages="errors.is_enabled"
                    v-model="form.is_enabled"
                ></v-switch>
            </v-col>

            <v-col cols="12" class="d-flex flex-column ga-4">
                <div
                    v-if="errors.headers"
                    class="text-error text-caption mb-2"
                >
                    {{ Array.isArray(errors.headers) ? errors.headers[0] : errors.headers }}
                </div>

                <v-card
                    v-for="(header, index) in headers"
                    :key="index"
                    variant="tonal"
                >
                    <v-card-item>
                        <v-row>
                            <v-col cols="12" lg="4" xl="3">
                                <v-text-field
                                    label="Nome"
                                    hide-details="auto"
                                    v-model="header.key"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" lg="8" xl="9">
                                <v-text-field
                                    label="Valor"
                                    hide-details="auto"
                                    v-model="header.value"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-card-item>

                    <v-divider></v-divider>

                    <v-card-actions class="justify-end">
                        <v-btn
                            variant="tonal"
                            color="error"
                            prepend-icon="mdi-delete"
                            text="Remover"
                            :disabled="headers.length <= 1"
                            @click="removeHeader(index)"
                        ></v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "form-closed",
    ]);

    const isDialogOpen = ref(false);

    const loading = ref(false);
    const errors = ref({});

    const scrapeHttpProfile = ref({});
    const form = ref({});
    const headers = ref([]);

    const isEditing = computed(() => !!scrapeHttpProfile.value.id);

    const sheetActions = computed(() => {
        const actions = [];

        if( isEditing.value ) {
            actions.push({
                key: "delete",
                variant: "tonal",
                color: "error",
                prependIcon: "mdi-delete",
                text: "Excluir",
                onClick: removeScrapeHttpProfile,
            });
        }

        actions.push({ key: "spacer", spacer: true });

        actions.push({
            key: "add-header",
            prependIcon: "mdi-plus",
            text: "Adicionar",
            onClick: addHeader,
        });

        actions.push({
            key: "save",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-content-save",
            loading: loading.value,
            text: "Salvar",
            onClick: handleSave,
        });

        return actions;
    });

    function buildPayload() {
        return {
            name: form.value.name,
            is_enabled: form.value.is_enabled,
            headers: headers.value.reduce((acc, header) => {
                const key = header.key?.trim();

                if( !key ) return acc;

                acc[key] = header.value ?? "";

                return acc;
            }, {}),
        };
    }

    function setHeaders(headersData = {}) {
        const entries = Object.entries(headersData || {});

        if( !entries.length ) {
            headers.value = [{ key: "", value: "" }];
            return;
        }

        headers.value = entries.map(([key, value]) => ({
            key,
            value,
        }));
    }

    async function createScrapeHttpProfile() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.scrape-http-profiles.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Perfil HTTP criado com sucesso");

            scrapeHttpProfile.value = data.scrape_http_profile;

            form.value = {
                ...form.value,
                ...data.scrape_http_profile,
            };

            setHeaders(data.scrape_http_profile.headers);
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar perfil HTTP");
        } finally {
            loading.value = false;
        }
    }

    async function updateScrapeHttpProfile() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.scrape-http-profiles.update", scrapeHttpProfile.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Perfil HTTP atualizado com sucesso");

            scrapeHttpProfile.value = data.scrape_http_profile;

            form.value = {
                ...form.value,
                ...data.scrape_http_profile,
            };

            setHeaders(data.scrape_http_profile.headers);
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar perfil HTTP");
        } finally {
            loading.value = false;
        }
    }

    async function removeScrapeHttpProfile() {
        const { isConfirmed } = await confirmDelete(
            "Excluir Perfil HTTP",
            `O perfil "${scrapeHttpProfile.value?.name ?? ""}" será removido permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        loading.value = true;

        try {
            const url = route("api.scrape-http-profiles.destroy", scrapeHttpProfile.value.id);
            await api.delete(url);

            toastSuccess("Perfil HTTP removido com sucesso");

            isDialogOpen.value = false;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover perfil HTTP");
        } finally {
            loading.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateScrapeHttpProfile();
        } else {
            await createScrapeHttpProfile();
        }
    }

    function addHeader() {
        headers.value.unshift({ key: "", value: "" });
    }

    function removeHeader(index) {
        if( headers.value.length <= 1 ) return;
        headers.value.splice(index, 1);
    }
    function open(data = {}) {
        scrapeHttpProfile.value = data;

        form.value = {
            ...form.value,
            ...data,
        };

        setHeaders(data.headers);

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};

        scrapeHttpProfile.value = {};

        form.value = {
            name: null,
            is_enabled: true,
        };

        headers.value = [{ key: "", value: "" }];
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("form-closed");
        });
    }

    reset();

    defineExpose({
        open,
    });
</script>
