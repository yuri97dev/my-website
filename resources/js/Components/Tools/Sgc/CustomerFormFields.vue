<template>
    <BottomSheet
        overlay-id="sgc-customer-form"
        :title="isEditing ? 'Editar Cliente' : 'Novo Cliente'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6">
                <CompanyAutocomplete
                    :disabled="isEditing"
                    v-model="form.company_id"
                ></CompanyAutocomplete>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Apelido"
                    :error-messages="errors.nickname"
                    v-model="form.nickname"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Documento de Identidade"
                    :error-messages="errors.identity_document"
                    v-model="form.identity_document"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="CPF/CNPJ"
                    :error-messages="errors.tax_id"
                    v-model="form.tax_id"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Data de Nascimento"
                    type="date"
                    :error-messages="errors.birth_date"
                    v-model="form.birth_date"
                ></v-text-field>
            </v-col>

            <v-col cols="12">
                <v-textarea
                    label="Observações"
                    rows="2"
                    auto-grow
                    :error-messages="errors.notes"
                    v-model="form.notes"
                ></v-textarea>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                v-if="isEditing && canManageCompany(form.company_id)"
                color="error"
                variant="tonal"
                prepend-icon="mdi-delete"
                :loading="deleting"
                text="Remover"
                @click="isConfirmRemoveDialogOpen = true"
            ></v-btn>

            <v-spacer></v-spacer>

            <slot
                name="action-buttons"
                :can-manage-addresses="isEditing"
            ></slot>

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
        overlay-id="sgc-customer-confirm-remove"
        title="Excluir Cliente"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O cliente <strong class="text-primary">{{ form.name ?? "" }}</strong>
        será removido desta empresa. Essa ação não pode ser desfeita.

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

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { useSgcAuth } from "@/Composables/useSgcAuth";

    const props = defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "close",
    ]);

    const { canManageCompany } = useSgcAuth();

    const isDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = ref(false);
    const deleting = ref(false);
    const errors = ref({});

    const customer = ref({});
    const form = ref({});

    const isEditing = computed(() => !!customer.value.id);

    function buildPayload() {
        return {
            company_id: form.value.company_id,
            name: form.value.name,
            nickname: form.value.nickname,
            identity_document: form.value.identity_document,
            tax_id: form.value.tax_id,
            birth_date: form.value.birth_date || null,
            notes: form.value.notes,
        };
    }

    function resolveCompanyId(customer, fallback = null) {
        const id = customer?.companies?.[0]?.id ?? fallback;

        return id != null ? Number(id) : null;
    }

    function resolveNotes(customer, fallback = null) {
        return customer?.companies?.[0]?.pivot?.notes ?? fallback;
    }

    async function loadCustomer(id) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.show", id);
            const { data } = await api.get(url);

            customer.value = data.customer;

            form.value = {
                company_id: resolveCompanyId(data.customer, props.defaultCompanyId),
                name: data.customer.name,
                nickname: data.customer.nickname,
                identity_document: data.customer.identity_document,
                tax_id: data.customer.tax_id,
                birth_date: data.customer.birth_date,
                notes: resolveNotes(data.customer),
            };
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar cliente");
        } finally {
            loading.value = false;
        }
    }

    async function updateCustomer() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.update", customer.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Ok", "Cliente atualizado com sucesso");

            customer.value = data.customer;

            form.value = {
                ...form.value,
                ...data.customer,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar cliente");
        } finally {
            loading.value = false;
        }
    }

    async function createCustomer() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Cliente criado com sucesso");

            customer.value = data.customer;

            form.value = {
                ...form.value,
                ...data.customer,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar cliente");
        } finally {
            loading.value = false;
        }
    }

    async function handleDelete() {
        deleting.value = true;

        try {
            const url = route("api.tools.sgc.customers.destroy", customer.value.id);

            await api.delete(url, {
                params: {
                    company_id: form.value.company_id,
                },
            });

            toastSuccess("Ok", "Cliente removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover cliente");
        } finally {
            deleting.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateCustomer();
        } else {
            await createCustomer();
        }
    }

    async function open(data = {}) {
        if( data.id ) {
            isDialogOpen.value = true;

            await loadCustomer(data.id);

            const companyId = data.company_id ?? props.defaultCompanyId;

            if( companyId != null ) {
                form.value.company_id = companyId;
            }

            const initialNotes = data.pivot?.notes ?? data.notes;

            if( initialNotes != null ) {
                form.value.notes = initialNotes;
            }

            return;
        }

        customer.value = data;

        form.value = {
            company_id: data.company_id ?? props.defaultCompanyId,
            name: data.name ?? null,
            nickname: data.nickname ?? null,
            identity_document: data.identity_document ?? null,
            tax_id: data.tax_id ?? null,
            birth_date: data.birth_date ?? null,
            notes: data.notes ?? null,
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        customer.value = {};

        form.value = {
            company_id: props.defaultCompanyId,
            name: null,
            nickname: null,
            identity_document: null,
            tax_id: null,
            birth_date: null,
            notes: null,
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
        getCustomer: () => customer.value,
    });
</script>
