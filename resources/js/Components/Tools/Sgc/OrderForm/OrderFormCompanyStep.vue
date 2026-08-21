<template>
    <v-row>
        <v-col cols="12">
            <CompanyAutocomplete
                auto-select-single
                ref="companyAutocompleteRef"
                :disabled="isEditing"
                v-model="companyId"
            ></CompanyAutocomplete>
        </v-col>

        <v-col cols="12">
            <CustomerAutocomplete
                ref="customerAutocompleteRef"
                clearable
                label="Cliente (opcional)"
                :company-id="companyId"
                :error-messages="errors.customer_id"
                v-model="customerId"
            ></CustomerAutocomplete>
        </v-col>
    </v-row>
</template>

<script setup>
    import { ref } from "vue";

    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import CustomerAutocomplete from "@/Components/Inputs/CustomerAutocomplete.vue";

    defineProps({
        isEditing: Boolean,
        errors: {
            type: Object,
            default: () => ({}),
        },
    });

    const companyId = defineModel("companyId", {
        type: [ Number, null ],
        default: null,
    });

    const customerId = defineModel("customerId", {
        type: [ Number, null ],
        default: null,
    });

    const companyAutocompleteRef = ref(null);
    const customerAutocompleteRef = ref(null);

    defineExpose({
        getSelectedCompany: () => companyAutocompleteRef.value?.getSelectedCompany?.() ?? null,
        getSelectedCustomer: () => customerAutocompleteRef.value?.getSelectedCustomer?.() ?? null,
    });
</script>
