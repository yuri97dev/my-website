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
                <CompanyAutocomplete
                    label="Empresa Matriz"
                    clearable
                    :exclude-id="company.id"
                    :error-messages="errors.parent_id"
                    v-model="form.parent_id"
                ></CompanyAutocomplete>
            </v-col>

            <v-col cols="12">
                <v-divider class="mb-4"></v-divider>
                <div class="text-subtitle-2 text-medium-emphasis">Endereço</div>
            </v-col>

            <v-col cols="12" lg="4">
                <PostalCodeField
                    :error-messages="errors.address_postal_code"
                    v-model="form.address_postal_code"
                    @address-found="fillAddressFromPostalCode"
                ></PostalCodeField>
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
                v-if="isEditing && isCompanyOwner(company.id)"
                color="error"
                variant="tonal"
                prepend-icon="mdi-delete"
                :loading="deleting"
                text="Remover"
                @click="isConfirmRemoveDialogOpen = true"
            ></v-btn>

            <v-spacer></v-spacer>

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

    <Dialog
        overlay-id="sgc-company-confirm-remove"
        title="Excluir Empresa"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        A empresa <strong class="text-primary">{{ form.trade_name || form.legal_name || "" }}</strong>
        será removida permanentemente. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                :loading="deleting"
                text="Excluir"
                @click="handleDelete"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";
    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import PostalCodeField from "@/Components/PostalCodeField.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { useSgcAuth } from "@/Composables/useSgcAuth";

    const emit = defineEmits([
        "close",
    ]);

    const { isCompanyOwner } = useSgcAuth();

    const isDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = ref(false);
    const deleting = ref(false);
    const errors = ref({});

    const company = ref({});
    const form = ref({});

    const isEditing = computed(() => !!company.value.id);

    function fillAddressFromPostalCode(address) {
        form.value.address_street = address.address_street ?? form.value.address_street;
        form.value.address_district = address.address_district ?? form.value.address_district;
        form.value.address_city = address.address_city ?? form.value.address_city;
        form.value.address_state = address.address_state ?? form.value.address_state;
        form.value.address_country = address.address_country ?? form.value.address_country;
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

    async function handleDelete() {
        deleting.value = true;

        try {
            const url = route("api.tools.sgc.companies.destroy", company.value.id);

            await api.delete(url);

            toastSuccess("Ok", "Empresa removida com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover empresa");
        } finally {
            deleting.value = false;
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
        isConfirmRemoveDialogOpen.value = false;

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
