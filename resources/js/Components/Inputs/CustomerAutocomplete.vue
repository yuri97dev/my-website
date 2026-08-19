<template>
    <RemoteAutocomplete
        ref="autocompleteRef"
        item-title="label"
        item-value="id"
        :label="label"
        prepend-inner-icon="mdi-account"
        :href="customersUrl"
        items-key="customers"
        :filters="filters"
        :map-item="mapCustomer"
        :clearable="clearable"
        :disabled="disabled || !companyId"
        error-message="Erro ao buscar clientes"
        v-bind="$attrs"
        v-model="selectedCustomer"
    ></RemoteAutocomplete>
</template>

<script setup>
    import { ref, computed, watch, nextTick } from "vue";

    import api from "@/api";

    import RemoteAutocomplete from "@/Components/Inputs/RemoteAutocomplete.vue";

    defineOptions({ inheritAttrs: false });

    const modelValue = defineModel({
        type: [ Number, null ],
        default: null,
    });

    const props = defineProps({
        companyId: {
            type: [ Number, null ],
            default: null,
        },
        clearable: {
            type: Boolean,
            default: false,
        },
        disabled: Boolean,
        label: {
            type: String,
            default: "Cliente",
        },
    });

    const autocompleteRef = ref(null);
    const selectedCustomer = ref(null);

    const customersUrl = route("api.tools.sgc.customers.index");

    const filters = computed(() => {
        if( props.companyId == null ) return {};

        return {
            company_id: props.companyId,
        };
    });

    function mapCustomer(customer) {
        return {
            ...customer,
            label: customer.nickname
                ? `${customer.name} (${customer.nickname})`
                : customer.name,
        };
    }

    async function syncSelectedFromId(id) {
        if( id == null ) {
            selectedCustomer.value = null;
            return;
        }

        const numericId = Number(id);

        if( selectedCustomer.value?.id === numericId ) return;

        const items = autocompleteRef.value?.items ?? [];
        const found = items.find(customer => customer.id === numericId);

        if( found ) {
            selectedCustomer.value = found;
            return;
        }

        try {
            const url = route("api.tools.sgc.customers.show", numericId);

            const { data } = await api.get(url);
            const customer = mapCustomer(data.customer);

            autocompleteRef.value?.ensureItem(customer);
            selectedCustomer.value = customer;
        } catch {
            // ignore: o valor permanece selecionado mesmo sem label
        }
    }

    watch(() => props.companyId, () => {
        if( props.companyId == null ) {
            modelValue.value = null;
            selectedCustomer.value = null;
        }
    });

    watch(modelValue, async (id) => {
        await nextTick();
        await syncSelectedFromId(id);
    }, { immediate: true });

    watch(selectedCustomer, (customer) => {
        const id = customer?.id ?? null;

        if( modelValue.value !== id ) {
            modelValue.value = id;
        }
    });

    defineExpose({
        autocompleteRef,
        getSelectedCustomer: () => selectedCustomer.value,
    });
</script>
