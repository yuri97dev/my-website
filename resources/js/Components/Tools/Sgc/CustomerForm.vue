<template>
    <CustomerFormFields
        ref="customerFormFieldsRef"
        :default-company-id="defaultCompanyId"
        @close="handleFormClosed"
    />
</template>

<script setup>
    import { ref } from "vue";

    import CustomerFormFields from "@/Components/Tools/Sgc/CustomerFormFields.vue";

    defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "form-closed",
    ]);

    const customerFormFieldsRef = ref(null);

    function open(data = {}) {
        customerFormFieldsRef.value?.open(data);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
