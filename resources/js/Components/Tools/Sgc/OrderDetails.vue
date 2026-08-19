<template>
    <OrderDetailsFields
        ref="orderDetailsFieldsRef"
        @close="handleClosed"
        @updated="handleUpdated"
    ></OrderDetailsFields>
</template>

<script setup>
    import { ref } from "vue";

    import OrderDetailsFields from "@/Components/Tools/Sgc/OrderDetailsFields.vue";

    const emit = defineEmits([
        "form-closed",
        "updated",
    ]);

    const orderDetailsFieldsRef = ref(null);

    function open(data = {}) {
        orderDetailsFieldsRef.value?.open(data);
    }

    function handleClosed() {
        emit("form-closed");
    }

    function handleUpdated() {
        emit("updated");
    }

    defineExpose({
        open,
    });
</script>
