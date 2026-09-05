<template>
    <AiProviderFormFields
        ref="aiProviderFormFieldsRef"
        :prepend-actions="prependActions"
        @close="handleFormClosed"
    ></AiProviderFormFields>

    <AiPromptManager ref="aiPromptManagerRef"/>
</template>

<script setup>
    import { ref, computed } from "vue";

    import AiProviderFormFields from "@/Components/Admin/AiProviderFormFields.vue";
    import AiPromptManager from "@/Components/Admin/AiPromptManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const aiProviderFormFieldsRef = ref(null);
    const aiPromptManagerRef = ref(null);

    const prependActions = computed(() => [
        {
            key: "prompts",
            prependIcon: "mdi-text-box-multiple-outline",
            text: "Prompts",
            onClick: openPrompts,
        },
    ]);

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
