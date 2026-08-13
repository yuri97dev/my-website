<template>
    <div>
        <RemoteAutocomplete
            ref="autocompleteRef"
            item-title="label"
            item-value="id"
            :label="label"
            prepend-inner-icon="mdi-domain"
            :href="companiesUrl"
            items-key="companies"
            :filters="filters"
            :map-item="mapCompany"
            :clearable="clearable"
            :disabled="disabled"
            error-message="Erro ao buscar empresas"
            v-bind="$attrs"
            v-model="selectedCompany"
        ></RemoteAutocomplete>
    </div>
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
        clearable: {
            type: Boolean,
            default: false,
        },
        disabled: Boolean,
        excludeId: {
            type: [ Number, null ],
            default: null,
        },
        label: {
            type: String,
            default: "Empresa",
        },
    });

    const autocompleteRef = ref(null);
    const selectedCompany = ref(null);

    const companiesUrl = route("api.tools.sgc.companies.index");

    const filters = computed(() => {
        if( props.excludeId == null ) return {};

        return {
            exclude_id: props.excludeId,
        };
    });

    function mapCompany(company) {
        return {
            ...company,
            label: company.trade_name || company.legal_name,
        };
    }

    async function syncSelectedFromId(id) {
        if( id == null ) {
            selectedCompany.value = null;
            return;
        }

        const numericId = Number(id);

        if( selectedCompany.value?.id === numericId ) return;

        const items = autocompleteRef.value?.items ?? [];
        const found = items.find(company => company.id === numericId);

        if( found ) {
            selectedCompany.value = found;
            return;
        }

        try {
            const url = route("api.tools.sgc.companies.show", numericId);

            const { data } = await api.get(url);
            const company = mapCompany(data.company);

            autocompleteRef.value?.ensureItem(company);
            selectedCompany.value = company;
        } catch {
            // ignore: o valor permanece selecionado mesmo sem label
        }
    }

    watch(modelValue, async (id) => {
        await nextTick();
        await syncSelectedFromId(id);
    }, { immediate: true });

    watch(selectedCompany, (company) => {
        const id = company?.id ?? null;

        if( modelValue.value !== id ) {
            modelValue.value = id;
        }
    });

    defineExpose({
        autocompleteRef,
    });
</script>
