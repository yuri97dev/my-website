<template>
    <BottomSheet
        overlay-id="sgc-customer-form"
        max-width="960"
        :title="isEditing ? 'Editar Cliente' : 'Novo Cliente'"
        :actions="sheetActions"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-tabs
            grow
            show-arrows
            align-tabs="center"
            v-model="currentStep"
        >
            <v-tab
                v-for="step in steps"
                :key="step.value"
                :value="step.value"
                :prepend-icon="step.icon"
                :disabled="step.disabled"
                :text="step.title"
            ></v-tab>
        </v-tabs>

        <v-divider class="mb-4"></v-divider>

        <v-tabs-window v-model="currentStep">
            <!-- <v-tabs-window-item value="documents">
                <v-row>
                    <v-col cols="12" lg="6">
                        <v-text-field
                            label="RG"
                            hint="Documento de identidade"
                            persistent-hint
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
                </v-row>
            </v-tabs-window-item> -->

            <v-tabs-window-item value="data">
                <v-alert
                    v-if="matchedCustomer?.id && !isEditing"
                    type="info"
                    variant="tonal"
                    class="mb-4"
                    :text="matchedCustomerAlert"
                ></v-alert>

                <v-row>
                    <v-col cols="12" lg="6">
                        <v-text-field
                            label="RG"
                            hint="Documento de identidade"
                            persistent-hint
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
                        <CompanyAutocomplete
                            auto-select-single
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
            </v-tabs-window-item>

            <v-tabs-window-item value="addresses">
                <CustomerAddressManager
                    v-if="isEditing"
                    ref="addressManagerRef"
                    :customer-id="customer.id"
                    :company-id="form.company_id"
                />
            </v-tabs-window-item>

            <v-tabs-window-item value="attachments">
                <CustomerAttachmentManager
                    v-if="isEditing"
                    ref="attachmentManagerRef"
                    :customer-id="customer.id"
                    :company-id="form.company_id"
                />
            </v-tabs-window-item>
        </v-tabs-window>
    </BottomSheet>

</template>

<script setup>
    import { ref, computed, nextTick, watch } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import CustomerAddressManager from "@/Components/Tools/Sgc/CustomerAddressManager.vue";
    import CustomerAttachmentManager from "@/Components/Tools/Sgc/CustomerAttachmentManager.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";
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
    const addressManagerRef = ref(null);
    const attachmentManagerRef = ref(null);
    const loading = ref(false);
    const lookingUp = ref(false);
    const deleting = ref(false);
    const errors = ref({});
    const currentStep = ref("documents");
    const dataStepUnlocked = ref(false);

    const customer = ref({});
    const matchedCustomer = ref(null);
    const form = ref({});

    const isEditing = computed(() => !!customer.value.id);

    const steps = computed(() => [
        // { value: "documents", title: "Documentos", icon: "mdi-card-account-details-outline" },
        { value: "data", title: "Dados", icon: "mdi-account-details" },
        { value: "addresses", title: "Endereços", icon: "mdi-map-marker-outline", disabled: !isEditing.value },
        { value: "attachments", title: "Anexos", icon: "mdi-paperclip", disabled: !isEditing.value },
    ]);

    const isAlreadyLinked = computed(() => {
        if( !matchedCustomer.value?.id || form.value.company_id == null ) {
            return false;
        }

        return (matchedCustomer.value.companies || []).some(
            company => Number(company.id) === Number(form.value.company_id)
        );
    });

    const matchedCustomerAlert = computed(() => {
        if( isAlreadyLinked.value ) {
            return "Este cliente já está associado a esta empresa. Ao salvar, os dados serão atualizados.";
        }

        return "Cliente encontrado. Ao salvar, ele será associado a empresa selecionada.";
    });

    const currentStepIndex = computed(() => {
        return steps.value.findIndex(step => step.value === currentStep.value);
    });

    const sheetActions = computed(() => {
        const actions = [];

        if( currentStep.value === "data" && isEditing.value && canManageCompany(form.value.company_id) ) {
            actions.push({
                key: "delete",
                color: "error",
                variant: "tonal",
                prependIcon: "mdi-delete",
                loading: deleting.value,
                text: "Excluir Cliente",
                onClick: handleDelete,
            });

            actions.push({ key: "spacer", spacer: true });
        }

        if( currentStep.value === "addresses" ) {
            actions.push({
                key: "refresh-addresses",
                prependIcon: "mdi-refresh",
                text: "Atualizar",
                disabled: addressManagerRef.value?.loading,
                onClick: () => addressManagerRef.value?.load(),
            });
        }

        if( currentStep.value === "attachments" ) {
            actions.push({
                key: "refresh-attachments",
                prependIcon: "mdi-refresh",
                text: "Atualizar",
                disabled: attachmentManagerRef.value?.loading || attachmentManagerRef.value?.saving,
                onClick: () => attachmentManagerRef.value?.load(),
            });
        }

        if( currentStep.value === "documents" || currentStep.value === "data" ) {
            actions.push({
                key: "save",
                variant: "tonal",
                color: "primary",
                prependIcon: "mdi-content-save",
                loading: loading.value,
                text: "Salvar",
                onClick: handleSave,
            });
        }

        if( currentStep.value === "addresses" ) {
            actions.push({
                key: "new-address",
                variant: "tonal",
                color: "primary",
                prependIcon: "mdi-plus",
                text: "Novo Endereço",
                onClick: () => addressManagerRef.value?.create(),
            });
        }

        if( currentStep.value === "attachments" ) {
            actions.push({
                key: "add-attachment",
                variant: "tonal",
                color: "primary",
                prependIcon: "mdi-plus",
                loading: attachmentManagerRef.value?.saving,
                disabled: attachmentManagerRef.value?.loading,
                text: "Adicionar Arquivo",
                onClick: () => attachmentManagerRef.value?.pickFile(),
            });
        }

        return actions;
    });

    function formatBirthDate(value) {
        if( !value ) {
            return null;
        }

        return String(value).slice(0, 10);
    }

    function buildPayload() {
        return {
            company_id: form.value.company_id,
            customer_id: customer.value.id || matchedCustomer.value?.id || null,
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

    function applyMatchedCustomer(found) {
        matchedCustomer.value = found;

        form.value.name = found.name ?? null;
        form.value.nickname = found.nickname ?? null;
        form.value.identity_document = found.identity_document || form.value.identity_document;
        form.value.tax_id = found.tax_id || form.value.tax_id;
        form.value.birth_date = formatBirthDate(found.birth_date);

        const companyNotes = found.companies?.find(
            company => Number(company.id) === Number(form.value.company_id)
        )?.pivot?.notes;

        if( companyNotes != null ) {
            form.value.notes = companyNotes;
        }
    }

    function clearMatchedCustomerData() {
        matchedCustomer.value = null;
    }

    async function lookupCustomer() {
        errors.value = {};

        const identityDocument = form.value.identity_document?.trim?.() || form.value.identity_document;
        const taxId = form.value.tax_id?.trim?.() || form.value.tax_id;

        if( !identityDocument && !taxId ) {
            clearMatchedCustomerData();
            return true;
        }

        lookingUp.value = true;

        try {
            const url = route("api.tools.sgc.customers.lookup");
            const { data } = await api.get(url, {
                params: {
                    identity_document: identityDocument || undefined,
                    tax_id: taxId || undefined,
                },
            });

            if( data.customer ) {
                applyMatchedCustomer(data.customer);
                toastSuccess("Ok", "Cliente encontrado. Os dados foram preenchidos.");
            } else if( matchedCustomer.value ) {
                form.value.name = null;
                form.value.nickname = null;
                form.value.birth_date = null;
                clearMatchedCustomerData();
            } else {
                clearMatchedCustomerData();
            }

            return true;
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao buscar cliente");

            return false;
        } finally {
            lookingUp.value = false;
        }
    }

    async function goToNextStep() {
        if( !isEditing.value ) {
            const found = await lookupCustomer();

            if( !found ) {
                return;
            }
        }

        dataStepUnlocked.value = true;
        currentStep.value = "data";
    }

    function goToPreviousStep() {
        const previousStep = steps[currentStepIndex.value - 1];

        if( previousStep ) {
            currentStep.value = previousStep.value;
            dataStepUnlocked.value = isEditing.value;
        }
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
                birth_date: formatBirthDate(data.customer.birth_date),
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
                birth_date: formatBirthDate(data.customer.birth_date),
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

        const associating = !!matchedCustomer.value?.id;

        try {
            const url = route("api.tools.sgc.customers.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess(
                "Ok",
                associating
                    ? "Cliente associado com sucesso"
                    : "Cliente criado com sucesso"
            );

            customer.value = data.customer;
            matchedCustomer.value = data.customer;

            form.value = {
                ...form.value,
                ...data.customer,
                birth_date: formatBirthDate(data.customer.birth_date),
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
        const { isConfirmed } = await confirmDelete(
            "Excluir Cliente",
            `O cliente "${form.value.name ?? ""}" será removido desta empresa. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        deleting.value = true;

        try {
            const url = route("api.tools.sgc.customers.destroy", customer.value.id);

            await api.delete(url, {
                params: {
                    company_id: form.value.company_id,
                },
            });

            toastSuccess("Ok", "Cliente removido com sucesso");

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
        reset();

        if( data.id ) {
            isDialogOpen.value = true;
            dataStepUnlocked.value = true;
            currentStep.value = "data";

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

        customer.value = {};

        form.value = {
            company_id: data.company_id ?? props.defaultCompanyId,
            name: data.name ?? null,
            nickname: data.nickname ?? null,
            identity_document: data.identity_document ?? null,
            tax_id: data.tax_id ?? null,
            birth_date: formatBirthDate(data.birth_date),
            notes: data.notes ?? null,
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        customer.value = {};
        matchedCustomer.value = null;
        currentStep.value = "documents";
        dataStepUnlocked.value = false;

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
        addressManagerRef.value?.reset();
        attachmentManagerRef.value?.reset();

        reset();

        nextTick(() => {
            emit("close");
        });
    }

    watch(currentStep, (step) => {
        if( step === "documents" && !isEditing.value ) {
            dataStepUnlocked.value = false;
        }
    });

    reset();

    defineExpose({
        open,
        getCustomer: () => customer.value,
        getCompanyId: () => form.value.company_id,
    });
</script>
