<template>
    <v-card hover>
        <v-autocomplete
            label="Empresa"
            prepend-inner-icon="mdi-domain"
            variant="solo"
            item-title="label"
            item-value="id"
            hide-no-data
            no-filter
            :items="companies"
            :loading="loading"
            :return-object="false"
            :clearable="clearable"
            :disabled="disabled"
            v-model="modelValue"
        >
            <template #append-inner>
                <div
                    class="d-flex align-center ga-2"
                    @mousedown.stop.prevent
                >
                    <v-btn
                        size="small"
                        icon="mdi-refresh"
                        variant="tonal"
                        color="primary"
                        :loading="loading"
                        @click.stop="getCompanies"
                    ></v-btn>
                </div>
            </template>
        </v-autocomplete>
    </v-card>
</template>

<script setup>
    import { ref, onMounted, watch } from "vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    const modelValue = defineModel({
        type: [ Number, null ],
        default: null,
    });

    defineProps({
        clearable: {
            type: Boolean,
            default: false,
        },
        disabled: Boolean,
    });

    const companies = ref([]);
    const loading = ref(false);

    function companyLabel(company) {
        return company.trade_name || company.legal_name;
    }

    async function ensureSelectedCompany() {
        if( modelValue.value == null ) return;

        const selectedId = Number(modelValue.value);

        if( companies.value.some(company => company.id === selectedId) ) return;

        try {
            const url = route("api.tools.sgc.companies.show", selectedId);
            const { data } = await api.get(url);

            companies.value.push({
                ...data.company,
                label: companyLabel(data.company),
            });
        } catch {
            // ignore: o valor permanece selecionado mesmo sem label
        }
    }

    async function getCompanies() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.companies.index");
            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            companies.value = (data.companies || []).map(company => ({
                ...company,
                label: companyLabel(company),
            }));

            await ensureSelectedCompany();
        } catch (e) {
            companies.value = [];
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar empresas");
        } finally {
            loading.value = false;
        }
    }

    watch(modelValue, async () => {
        await ensureSelectedCompany();
    });

    onMounted(async () => {
        await getCompanies();
    });

    defineExpose({
        getCompanies,
        companies,
    });
</script>
