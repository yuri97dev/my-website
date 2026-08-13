<template>
    <ProductFormFields
        ref="productFormFieldsRef"
        :default-company-id="defaultCompanyId"
        @close="handleFormClosed"
    ></ProductFormFields>
</template>

<script setup>
    import { ref } from "vue";

    import ProductFormFields from "@/Components/Tools/Sgc/ProductFormFields.vue";

    defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "form-closed",
    ]);

    const productFormFieldsRef = ref(null);

    function open(data = {}) {
        productFormFieldsRef.value?.open(data);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
