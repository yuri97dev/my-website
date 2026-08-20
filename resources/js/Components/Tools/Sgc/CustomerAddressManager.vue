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
                    @click="openAddressForm(address)"
                >
                    <v-card-title class="text-wrap">
                        <div>
                            {{ formatAddressTitle(address) }}
                        </div>
                    </v-card-title>

                    <v-card-text class="d-flex flex-column justify-end flex-grow-1 text-primary">
                        <div>{{ formatAddressSubtitle(address) }}</div>

                        <div>
                            {{ address.address_complement }}
                        </div>

                        <div
                            v-if="address.address_postal_code"
                            class="text-end"
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
                @click="openAddressForm()"
            ></v-btn>
        </template>
    </BottomSheet>

    <CustomerAddressForm
        ref="addressFormRef"
        @saved="loadAddresses"
        @removed="loadAddresses"
    />
</template>

<script setup>
    import { ref } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import CustomerAddressForm from "@/Components/Tools/Sgc/CustomerAddressForm.vue";

    import { alertError } from "@/Utils/Alert";

    const isOpen = ref(false);
    const addressFormRef = ref(null);

    const customerId = ref(null);
    const companyId = ref(null);
    const companyAddressDefaults = ref(null);
    const addresses = ref([]);
    const loading = ref(false);

    function formatAddressTitle(address) {
        const street = [
            address.address_street || "Logradouro não informado",
            address.address_number,
        ].filter(Boolean).join(", ");

        return street;
    }

    function formatAddressSubtitle(address) {
        const parts = [
            address.address_district,
            [address.address_city, address.address_state].filter(Boolean).join(" - "),
            address.address_country,
        ].filter(Boolean);

        return parts.join(" · ") || "Sem cidade informada";
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

    async function loadCompanyAddressDefaults() {
        companyAddressDefaults.value = null;

        if( companyId.value == null ) return;

        try {
            const url = route("api.tools.sgc.companies.show", companyId.value);

            const { data } = await api.get(url);

            const company = data.company || {};

            companyAddressDefaults.value = {
                address_city: company.address_city ?? null,
                address_state: company.address_state ?? null,
                address_country: company.address_country ?? null,
            };
        } catch {
            companyAddressDefaults.value = null;
        }
    }

    function openAddressForm(address = null) {
        addressFormRef.value?.open(
            customerId.value,
            address,
            address?.id ? null : companyAddressDefaults.value,
        );
    }

    function open(id, idCompany = null) {
        customerId.value = id;
        companyId.value = idCompany;
        isOpen.value = true;

        loadAddresses();
        loadCompanyAddressDefaults();
    }

    function close() {
        addressFormRef.value?.close();
        isOpen.value = false;
        customerId.value = null;
        companyId.value = null;
        companyAddressDefaults.value = null;
        addresses.value = [];
    }

    defineExpose({
        open,
        close,
    });
</script>
