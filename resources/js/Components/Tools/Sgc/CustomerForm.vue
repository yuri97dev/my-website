<template>
    <CustomerFormFields
        ref="customerFormFieldsRef"
        :default-company-id="defaultCompanyId"
        @close="handleFormClosed"
    >
        <template #action-buttons="{ canManageAddresses }">
            <v-btn
                prepend-icon="mdi-map-marker-outline"
                text="Endereços"
                :disabled="!canManageAddresses"
                @click="openAddressManager"
            ></v-btn>
        </template>
    </CustomerFormFields>

    <CustomerAddressManager ref="addressManagerRef" />
</template>

<script setup>
    import { ref } from "vue";

    import CustomerAddressManager from "@/Components/Tools/Sgc/CustomerAddressManager.vue";
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
    const addressManagerRef = ref(null);

    function open(data = {}) {
        customerFormFieldsRef.value?.open(data);
    }

    function openAddressManager() {
        addressManagerRef.value?.open({
            getAddresses: () => customerFormFieldsRef.value?.getAddresses() || [],
            setAddresses: (addresses) => customerFormFieldsRef.value?.setAddresses(addresses),
            reloadAddresses: () => customerFormFieldsRef.value?.reloadAddresses(),
        });
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
