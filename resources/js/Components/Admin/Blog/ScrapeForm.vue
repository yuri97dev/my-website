<template>
    <ScrapeFormFields
        ref="scrapeFormFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                prepend-icon="mdi-file-document-multiple"
                @click="openPostsDownloaded"
            >Posts Baixados</v-btn>
        </template>
    </ScrapeFormFields>

    <ScrapeItemManager ref="scrapeItemManagerRef"/>
</template>

<script setup>
    import { ref } from "vue";

    import ScrapeFormFields from "@/Components/Admin/Blog/ScrapeFormFields.vue";
    import ScrapeItemManager from "@/Components/Admin/Blog/ScrapeItemManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const scrapeFormFieldsRef = ref(null);
    const scrapeItemManagerRef = ref(null);

    const scrapeTarget = ref(null);

    function open(data = {}) {
        scrapeTarget.value = data;
        scrapeFormFieldsRef.value?.open(data);
    }

    function openPostsDownloaded() {
        scrapeItemManagerRef.value?.open(scrapeTarget.value);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
