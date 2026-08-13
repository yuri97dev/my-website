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
                v-for="(address, index) in addresses"
                :key="address.id ?? `address-${index}`"
                cols="12"
                xl="4"
                lg="6"
            >
                <v-card
                    border
                    hover
                    class="d-flex flex-column h-100"
                    variant="tonal"
                    @click="openAddressDialog(address, index)"
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
                @click="handleRefresh"
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
        O endereço será removido. Essa ação não pode ser desfeita até salvar o cliente.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                text="Excluir"
                @click="handleRemoveAddress"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { alertError } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isAddressDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);

    const addresses = ref([]);
    const loading = ref(false);
    const selectedAddressIndex = ref(null);

    const handlers = reactive({
        getAddresses: null,
        setAddresses: null,
        reloadAddresses: null,
    });

    const addressForm = reactive(emptyAddress());

    const isEditingAddress = computed(() => selectedAddressIndex.value !== null);

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

    function syncAddresses() {
        handlers.setAddresses?.(addresses.value.map(address => ({ ...address })));
    }

    function loadAddressesFromForm() {
        addresses.value = (handlers.getAddresses?.() || []).map(address => ({ ...address }));
    }

    async function handleRefresh() {
        if( !handlers.reloadAddresses ) {
            loadAddressesFromForm();
            return;
        }

        loading.value = true;

        try {
            const refreshed = await handlers.reloadAddresses();
            addresses.value = (refreshed || []).map(address => ({ ...address }));
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao atualizar endereços");
        } finally {
            loading.value = false;
        }
    }

    function openAddressDialog(address = null, index = null) {
        selectedAddressIndex.value = index;

        Object.assign(addressForm, emptyAddress(), address || {});

        isAddressDialogOpen.value = true;
    }

    function resetAddressForm() {
        selectedAddressIndex.value = null;
        Object.assign(addressForm, emptyAddress());
    }

    function handleSaveAddress() {
        const payload = { ...addressForm };

        if( selectedAddressIndex.value !== null ) {
            addresses.value.splice(selectedAddressIndex.value, 1, payload);
        } else {
            addresses.value.push(payload);
        }

        syncAddresses();
        isAddressDialogOpen.value = false;
        resetAddressForm();
    }

    function handleRemoveAddress() {
        if( selectedAddressIndex.value === null ) return;

        addresses.value.splice(selectedAddressIndex.value, 1);
        syncAddresses();

        isConfirmRemoveDialogOpen.value = false;
        isAddressDialogOpen.value = false;
        resetAddressForm();
    }

    function open(options = {}) {
        handlers.getAddresses = options.getAddresses ?? null;
        handlers.setAddresses = options.setAddresses ?? null;
        handlers.reloadAddresses = options.reloadAddresses ?? null;

        loadAddressesFromForm();

        isOpen.value = true;
    }

    function close() {
        isAddressDialogOpen.value = false;
        isOpen.value = false;
    }

    defineExpose({
        open,
        close,
    });
</script>
