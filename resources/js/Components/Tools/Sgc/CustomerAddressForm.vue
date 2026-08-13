<template>
    <Dialog
        overlay-id="sgc-customer-address-form"
        :title="isEditing ? 'Editar Endereço' : 'Novo Endereço'"
        max-width="700"
        no-body
        scrollable
        @close="resetForm"
        v-model="isOpen"
    >
        <v-card-text>
            <v-row>
                <v-col cols="12" lg="4">
                    <PostalCodeField
                        @address-found="fillAddressFromPostalCode"
                        v-model="form.address_postal_code"
                    ></PostalCodeField>
                </v-col>

                <v-col cols="12" lg="8">
                    <v-text-field
                        label="Logradouro"
                        autofocus
                        v-model="form.address_street"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="Número"
                        v-model="form.address_number"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="Bairro"
                        v-model="form.address_district"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="Complemento"
                        v-model="form.address_complement"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="Cidade"
                        v-model="form.address_city"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="Estado"
                        v-model="form.address_state"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="4">
                    <v-text-field
                        label="País"
                        v-model="form.address_country"
                    ></v-text-field>
                </v-col>
            </v-row>
        </v-card-text>

        <template #actions>
            <v-btn
                v-if="isEditing"
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                text="Excluir"
                @click="isConfirmRemoveDialogOpen = true"
            ></v-btn>

            <v-spacer></v-spacer>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="saving"
                :text="isEditing ? 'Salvar' : 'Adicionar'"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-customer-address-confirm-remove"
        title="Excluir Endereço"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O endereço será removido permanentemente. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                :loading="saving"
                text="Excluir"
                @click="handleRemove"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";
    import PostalCodeField from "@/Components/PostalCodeField.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "saved",
        "removed",
    ]);

    const isOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const saving = ref(false);
    const customerId = ref(null);

    const form = reactive(emptyAddress());

    const isEditing = computed(() => !!form.id);

    function emptyAddress() {
        return {
            id: null,
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

    function buildPayload() {
        return {
            address_street: form.address_street,
            address_number: form.address_number,
            address_complement: form.address_complement,
            address_district: form.address_district,
            address_city: form.address_city,
            address_state: form.address_state,
            address_postal_code: form.address_postal_code,
            address_country: form.address_country,
        };
    }

    function resetForm() {
        Object.assign(form, emptyAddress());
    }

    function fillAddressFromPostalCode(address) {
        form.address_street = address.address_street ?? form.address_street;
        form.address_district = address.address_district ?? form.address_district;
        form.address_city = address.address_city ?? form.address_city;
        form.address_state = address.address_state ?? form.address_state;
        form.address_country = address.address_country ?? form.address_country;
    }

    async function handleSave() {
        if( !customerId.value ) return;

        saving.value = true;

        try {
            if( form.id ) {
                const url = route("api.tools.sgc.customers.addresses.update", {
                    sgc_customer: customerId.value,
                    sgc_customer_address: form.id,
                });

                await api.put(url, buildPayload());

                toastSuccess("Ok", "Endereço atualizado com sucesso");
            } else {
                const url = route("api.tools.sgc.customers.addresses.store", customerId.value);

                await api.post(url, buildPayload());

                toastSuccess("Ok", "Endereço adicionado com sucesso");
            }

            isOpen.value = false;

            resetForm();

            emit("saved");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao salvar endereço");
        } finally {
            saving.value = false;
        }
    }

    async function handleRemove() {
        if( !customerId.value || !form.id ) return;

        saving.value = true;

        try {
            const url = route("api.tools.sgc.customers.addresses.destroy", {
                sgc_customer: customerId.value,
                sgc_customer_address: form.id,
            });

            await api.delete(url);

            toastSuccess("Ok", "Endereço removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isOpen.value = false;

            resetForm();

            emit("removed");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover endereço");
        } finally {
            saving.value = false;
        }
    }

    function open(id, address = null) {
        customerId.value = id;
        Object.assign(form, emptyAddress(), address || {});
        isOpen.value = true;
    }

    function close() {
        isOpen.value = false;
        isConfirmRemoveDialogOpen.value = false;
        customerId.value = null;
        resetForm();
    }

    defineExpose({
        open,
        close,
    });
</script>
