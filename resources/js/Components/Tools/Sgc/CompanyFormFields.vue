<template>
    <BottomSheet
        overlay-id="sgc-company-form"
        :title="isEditing ? 'Editar Empresa' : 'Nova Empresa'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome Legal"
                    :error-messages="errors.legal_name"
                    v-model="form.legal_name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome Fantasia"
                    :error-messages="errors.trade_name"
                    v-model="form.trade_name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Documento"
                    :error-messages="errors.document"
                    v-model="form.document"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-autocomplete
                    label="Empresa Matriz"
                    item-title="label"
                    item-value="id"
                    clearable
                    :loading="loadingCompanies"
                    :items="companies"
                    :return-object="false"
                    :error-messages="errors.parent_id"
                    v-model="form.parent_id"
                ></v-autocomplete>
            </v-col>

            <v-col cols="12">
                <v-divider class="mb-4"></v-divider>
                <div class="text-subtitle-2 text-medium-emphasis mb-4">Endereço</div>
            </v-col>

            <v-col cols="12" lg="8">
                <v-text-field
                    label="Logradouro"
                    :error-messages="errors.address_street"
                    v-model="form.address_street"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Número"
                    :error-messages="errors.address_number"
                    v-model="form.address_number"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Complemento"
                    :error-messages="errors.address_complement"
                    v-model="form.address_complement"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Bairro"
                    :error-messages="errors.address_district"
                    v-model="form.address_district"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="CEP"
                    :error-messages="errors.address_postal_code"
                    v-model="form.address_postal_code"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Cidade"
                    :error-messages="errors.address_city"
                    v-model="form.address_city"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Estado"
                    :error-messages="errors.address_state"
                    v-model="form.address_state"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="País"
                    :error-messages="errors.address_country"
                    v-model="form.address_country"
                ></v-text-field>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="loading"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);
    const loading = ref(false);
    const loadingCompanies = ref(false);
    const errors = ref({});

    const company = ref({});
    const companies = ref([]);
    const form = ref({});

    const isEditing = computed(() => !!company.value.id);

    function companyLabel(item) {
        return item.trade_name || item.legal_name;
    }

    async function getCompanies() {
        loadingCompanies.value = true;

        try {
            const url = route("api.tools.sgc.companies.index");

            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            companies.value = (data.companies || [])
                .filter(item => item.id !== company.value.id)
                .map(item => ({
                    ...item,
                    label: companyLabel(item),
                }));
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar empresas");
        } finally {
            loadingCompanies.value = false;
        }
    }

    function buildPayload() {
        return {
            parent_id: form.value.parent_id || null,
            legal_name: form.value.legal_name,
            trade_name: form.value.trade_name,
            document: form.value.document,
            address_street: form.value.address_street,
            address_number: form.value.address_number,
            address_complement: form.value.address_complement,
            address_district: form.value.address_district,
            address_city: form.value.address_city,
            address_state: form.value.address_state,
            address_postal_code: form.value.address_postal_code,
            address_country: form.value.address_country,
        };
    }

    async function updateCompany() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.companies.update", company.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Ok", "Empresa atualizada com sucesso");

            company.value = data.company;

            form.value = {
                ...form.value,
                ...data.company,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar empresa");
        } finally {
            loading.value = false;
        }
    }

    async function createCompany() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.companies.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Empresa criada com sucesso");

            company.value = data.company;

            form.value = {
                ...form.value,
                ...data.company,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar empresa");
        } finally {
            loading.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateCompany();
        } else {
            await createCompany();
        }
    }

    async function open(data = {}) {
        company.value = data;

        form.value = {
            ...form.value,
            ...data,
        };

        isDialogOpen.value = true;

        await nextTick();
        await getCompanies();
    }

    function reset() {
        errors.value = {};
        company.value = {};

        form.value = {
            parent_id: null,
            legal_name: null,
            trade_name: null,
            document: null,
            address_street: null,
            address_number: null,
            address_complement: null,
            address_district: null,
            address_city: null,
            address_state: null,
            address_postal_code: null,
            address_country: null,
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    defineExpose({
        open,
    });
</script>
