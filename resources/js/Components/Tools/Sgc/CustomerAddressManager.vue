<template>
    <div>
        <v-skeleton-loader
            v-if="loading"
            type="list-item-two-line@4"
        ></v-skeleton-loader>

        <v-card
            v-else-if="addresses.length === 0"
            border
            variant="tonal"
            class="text-center pa-8"
        >
            <v-icon
                icon="mdi-map-marker-outline"
                size="48"
                color="primary"
                class="mb-4"
            ></v-icon>

            <div class="text-subtitle-1 font-weight-medium mb-1">
                Nenhum endereço
            </div>

            <div class="text-body-2 text-medium-emphasis">
                Use “Novo Endereço” no rodapé.
            </div>
        </v-card>

        <v-row v-else>
            <v-col
                v-for="address in addresses"
                :key="address.id"
                cols="12"
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

                        <div v-if="address.address_complement">
                            {{ address.address_complement }}
                        </div>

                        <div v-if="address.address_reference_point">
                            Ref.: {{ address.address_reference_point }}
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
    </div>

    <CustomerAddressForm
        ref="addressFormRef"
        @saved="loadAddresses"
        @removed="loadAddresses"
    />
</template>

<script setup>
    import { ref, watch } from "vue";

    import api from "@/api";

    import CustomerAddressForm from "@/Components/Tools/Sgc/CustomerAddressForm.vue";

    import { alertError } from "@/Utils/Alert";

    const props = defineProps({
        customerId: {
            type: [ Number, null ],
            default: null,
        },
        companyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const addressFormRef = ref(null);
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
        if( !props.customerId ) return;

        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.addresses.index", props.customerId);
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

        if( props.companyId == null ) return;

        try {
            const url = route("api.tools.sgc.companies.show", props.companyId);

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
            props.customerId,
            address,
            address?.id ? null : companyAddressDefaults.value,
        );
    }

    function reset() {
        addressFormRef.value?.close();
        companyAddressDefaults.value = null;
        addresses.value = [];
    }

    watch(
        () => [ props.customerId, props.companyId ],
        ([ customerId ]) => {
            if( !customerId ) {
                reset();
                return;
            }

            loadAddresses();
            loadCompanyAddressDefaults();
        },
        { immediate: true },
    );

    defineExpose({
        load: loadAddresses,
        create: () => openAddressForm(),
        reset,
        loading,
    });
</script>
