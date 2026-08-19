<template>
    <OrderFormFields
        ref="orderFormFieldsRef"
        @close="handleFormClosed"
    ></OrderFormFields>
</template>

<script setup>
    import { ref } from "vue";

    import OrderFormFields from "@/Components/Tools/Sgc/OrderFormFields.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const orderFormFieldsRef = ref(null);

    function open(data = {}) {
        orderFormFieldsRef.value?.open(data);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
