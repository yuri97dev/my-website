<template>
    <AiProviderFormFields
        ref="aiProviderFormFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                prepend-icon="mdi-text-box-multiple-outline"
                text="Prompts"
                @click="openPrompts"
            ></v-btn>
        </template>
    </AiProviderFormFields>

    <AiPromptManager ref="aiPromptManagerRef"/>
</template>

<script setup>
    import { ref } from "vue";

    import AiProviderFormFields from "@/Components/Admin/AiProviderFormFields.vue";
    import AiPromptManager from "@/Components/Admin/AiPromptManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const aiProviderFormFieldsRef = ref(null);
    const aiPromptManagerRef = ref(null);

    function open(data = {}) {
        aiProviderFormFieldsRef.value?.open(data);
    }

    function openPrompts() {
        const aiProvider = aiProviderFormFieldsRef.value?.getAiProvider();
        aiPromptManagerRef.value?.openList(aiProvider);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
