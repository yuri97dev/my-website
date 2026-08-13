<template>
    <EmployeeFormFields
        ref="employeeFormFieldsRef"
        :default-company-id="defaultCompanyId"
        @close="handleFormClosed"
    ></EmployeeFormFields>
</template>

<script setup>
    import { ref } from "vue";

    import EmployeeFormFields from "@/Components/Tools/Sgc/EmployeeFormFields.vue";

    defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "form-closed",
    ]);

    const employeeFormFieldsRef = ref(null);

    function open(data = {}) {
        employeeFormFieldsRef.value?.open(data);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
