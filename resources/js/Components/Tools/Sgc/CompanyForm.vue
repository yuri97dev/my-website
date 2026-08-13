<template>
    <CompanyFormFields
        ref="companyFormFieldsRef"
        @close="handleFormClosed"
    ></CompanyFormFields>
</template>

<script setup>
    import { ref } from "vue";

    import CompanyFormFields from "@/Components/Tools/Sgc/CompanyFormFields.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const companyFormFieldsRef = ref(null);

    function open(data = {}) {
        companyFormFieldsRef.value?.open(data);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
