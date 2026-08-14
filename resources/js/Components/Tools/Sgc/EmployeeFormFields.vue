<template>
    <BottomSheet
        overlay-id="sgc-employee-form"
        :title="isEditing ? 'Editar Funcionário' : 'Novo Funcionário'"
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
                    label="E-mail"
                    type="email"
                    :disabled="isEditing"
                    :error-messages="errors.email"
                    v-model="form.email"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" v-if="isEditing">
                <v-text-field
                    label="Nome"
                    :model-value="employee.user?.name"
                    disabled
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-select
                    label="Cargo"
                    :items="roleOptions"
                    item-title="title"
                    item-value="value"
                    :disabled="isOwner"
                    :error-messages="errors.role"
                    v-model="form.role"
                ></v-select>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                v-if="isEditing && !isOwner && canManageCompany(form.company_id)"
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
                :disabled="isOwner"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>

    <Dialog
        overlay-id="sgc-employee-confirm-remove"
        title="Excluir Funcionário"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O funcionário <strong class="text-primary">{{ employee.user?.name ?? employee.user?.email ?? "" }}</strong>
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

    const roleOptions = [
        {
            title: "Administrador",
            value: "admin",
        },
        {
            title: "Vendedor",
            value: "seller",
        },
    ];

    const isDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = ref(false);
    const deleting = ref(false);
    const errors = ref({});

    const employee = ref({});
    const form = ref({});

    const isEditing = computed(() => !!employee.value.id);

    const isOwner = computed(() => {
        if( !employee.value.company || !employee.value.user_id ) {
            return false;
        }

        return employee.value.company.owner_id === employee.value.user_id;
    });

    function buildPayload() {
        return {
            company_id: form.value.company_id,
            email: form.value.email,
            role: form.value.role,
        };
    }

    function resolveCompanyId(employeeData, fallback = null) {
        const id = employeeData?.company_id ?? employeeData?.company?.id ?? fallback;

        return id != null ? Number(id) : null;
    }

    async function loadEmployee(id) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.employees.show", id);
            const { data } = await api.get(url);

            employee.value = data.employee;

            form.value = {
                company_id: resolveCompanyId(data.employee, props.defaultCompanyId),
                email: data.employee.user?.email,
                role: data.employee.role,
            };
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar funcionário");
        } finally {
            loading.value = false;
        }
    }

    async function updateEmployee() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.employees.update", employee.value.id);
            const { data } = await api.put(url, {
                company_id: form.value.company_id,
                role: form.value.role,
            });

            toastSuccess("Ok", "Funcionário atualizado com sucesso");

            employee.value = data.employee;

            form.value = {
                ...form.value,
                role: data.employee.role,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar funcionário");
        } finally {
            loading.value = false;
        }
    }

    async function createEmployee() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.employees.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Funcionário adicionado com sucesso");

            employee.value = data.employee;

            form.value = {
                company_id: resolveCompanyId(data.employee, props.defaultCompanyId),
                email: data.employee.user?.email,
                role: data.employee.role,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao adicionar funcionário");
        } finally {
            loading.value = false;
        }
    }

    async function handleDelete() {
        deleting.value = true;

        try {
            const url = route("api.tools.sgc.employees.destroy", employee.value.id);

            await api.delete(url);

            toastSuccess("Ok", "Funcionário removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover funcionário");
        } finally {
            deleting.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateEmployee();
        } else {
            await createEmployee();
        }
    }

    async function open(data = {}) {
        if( data.id ) {
            isDialogOpen.value = true;

            await loadEmployee(data.id);

            const companyId = data.company_id ?? props.defaultCompanyId;

            if( companyId != null ) {
                form.value.company_id = companyId;
            }

            return;
        }

        employee.value = data;

        form.value = {
            company_id: data.company_id ?? props.defaultCompanyId,
            email: data.email ?? null,
            role: data.role ?? "seller",
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        employee.value = {};

        form.value = {
            company_id: props.defaultCompanyId,
            email: null,
            role: "seller",
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
