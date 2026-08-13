<template>
    <BottomSheet
        overlay-id="sgc-customer-address-list"
        title="Endereços"
        height="90dvh"
        :loading="loading"
        v-model="isOpen"
    >
        <div
            v-if="addresses.length === 0 && !loading"
            class="d-flex align-center justify-center py-4 h-100"
        >
            <v-empty-state
                icon="mdi-map-marker-outline"
                title="Nenhum endereço cadastrado"
                text="Adicione um novo endereço para este cliente."
            ></v-empty-state>
        </div>

        <v-row v-else>
            <v-col
                v-for="address in addresses"
                :key="address.id"
                cols="12"
                xl="4"
                lg="6"
            >
                <v-card
                    border
                    hover
                    class="d-flex flex-column h-100"
                    variant="tonal"
                    @click="openAddressDialog(address)"
                >
                    <v-card-title class="text-wrap">
                        <div class="lines-2">
                            {{ formatAddressTitle(address) }}
                        </div>
                    </v-card-title>

                    <v-card-text class="d-flex flex-column justify-end flex-grow-1 text-primary">
                        <div>{{ formatAddressSubtitle(address) }}</div>

                        <div
                            v-if="address.address_postal_code"
                            class="text-end mt-2"
                        >
                            CEP {{ address.address_postal_code }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading"
                @click="loadAddresses"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                text="Novo Endereço"
                @click="openAddressDialog()"
            ></v-btn>
        </template>
    </BottomSheet>

    <Dialog
        overlay-id="sgc-customer-address-form"
        :title="isEditingAddress ? 'Editar Endereço' : 'Novo Endereço'"
        max-width="700"
        @close="resetAddressForm"
        v-model="isAddressDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="8">
                <v-text-field
                    label="Logradouro"
                    autofocus
                    v-model="addressForm.address_street"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Número"
                    v-model="addressForm.address_number"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Complemento"
                    v-model="addressForm.address_complement"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Bairro"
                    v-model="addressForm.address_district"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="CEP"
                    v-model="addressForm.address_postal_code"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Cidade"
                    v-model="addressForm.address_city"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="Estado"
                    v-model="addressForm.address_state"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="4">
                <v-text-field
                    label="País"
                    v-model="addressForm.address_country"
                ></v-text-field>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                v-if="isEditingAddress"
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
                :text="isEditingAddress ? 'Salvar' : 'Adicionar'"
                @click="handleSaveAddress"
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
                @click="handleRemoveAddress"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isAddressDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);

    const customerId = ref(null);
    const addresses = ref([]);
    const loading = ref(false);
    const saving = ref(false);

    const addressForm = reactive(emptyAddress());

    const isEditingAddress = computed(() => !!addressForm.id);

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

    function formatAddressTitle(address) {
        const street = [
            address.address_street,
            address.address_number,
        ].filter(Boolean).join(", ");

        return street || "Endereço sem logradouro";
    }

    function formatAddressSubtitle(address) {
        const parts = [
            address.address_district,
            [address.address_city, address.address_state].filter(Boolean).join(" - "),
            address.address_country,
        ].filter(Boolean);

        return parts.join(" · ") || "Sem cidade informada";
    }

    function buildAddressPayload() {
        return {
            address_street: addressForm.address_street,
            address_number: addressForm.address_number,
            address_complement: addressForm.address_complement,
            address_district: addressForm.address_district,
            address_city: addressForm.address_city,
            address_state: addressForm.address_state,
            address_postal_code: addressForm.address_postal_code,
            address_country: addressForm.address_country,
        };
    }

    async function loadAddresses() {
        if( !customerId.value ) return;

        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.addresses.index", customerId.value);
            const { data } = await api.get(url);

            addresses.value = data.addresses || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar endereços");
        } finally {
            loading.value = false;
        }
    }

    function openAddressDialog(address = null) {
        Object.assign(addressForm, emptyAddress(), address || {});
        isAddressDialogOpen.value = true;
    }

    function resetAddressForm() {
        Object.assign(addressForm, emptyAddress());
    }

    async function handleSaveAddress() {
        if( !customerId.value ) return;

        saving.value = true;

        try {
            if( addressForm.id ) {
                const url = route("api.tools.sgc.customers.addresses.update", {
                    sgc_customer: customerId.value,
                    sgc_customer_address: addressForm.id,
                });

                await api.put(url, buildAddressPayload());

                toastSuccess("Ok", "Endereço atualizado com sucesso");
            } else {
                const url = route("api.tools.sgc.customers.addresses.store", customerId.value);

                await api.post(url, buildAddressPayload());

                toastSuccess("Ok", "Endereço adicionado com sucesso");
            }

            isAddressDialogOpen.value = false;
            resetAddressForm();

            await loadAddresses();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao salvar endereço");
        } finally {
            saving.value = false;
        }
    }

    async function handleRemoveAddress() {
        if( !customerId.value || !addressForm.id ) return;

        saving.value = true;

        try {
            const url = route("api.tools.sgc.customers.addresses.destroy", {
                sgc_customer: customerId.value,
                sgc_customer_address: addressForm.id,
            });

            await api.delete(url);

            toastSuccess("Ok", "Endereço removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isAddressDialogOpen.value = false;

            resetAddressForm();

            await loadAddresses();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover endereço");
        } finally {
            saving.value = false;
        }
    }

    function open(id) {
        customerId.value = id;
        isOpen.value = true;

        loadAddresses();
    }

    function close() {
        isAddressDialogOpen.value = false;
        isOpen.value = false;
        customerId.value = null;
        addresses.value = [];
    }

    defineExpose({
        open,
        close,
    });
</script>
