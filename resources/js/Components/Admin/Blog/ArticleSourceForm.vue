<template>
    <ArticleSourceFormFields
        ref="articleSourceFormFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                prepend-icon="mdi-file-document-multiple"
                @click="openPostsDownloaded"
            >Posts Baixados</v-btn>
        </template>
    </ArticleSourceFormFields>

    <ScrapedArticleManager ref="scrapedArticleManagerRef"/>
</template>

<script setup>
    import { ref } from "vue";

    import ArticleSourceFormFields from "@/Components/Admin/Blog/ArticleSourceFormFields.vue";
    import ScrapedArticleManager from "@/Components/Admin/Blog/ScrapedArticleManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const articleSourceFormFieldsRef = ref(null);
    const scrapedArticleManagerRef = ref(null);

    const articleSource = ref(null);

    function open(data = {}) {
        articleSource.value = data;
        articleSourceFormFieldsRef.value?.open(data);
    }

    function openPostsDownloaded() {
        scrapedArticleManagerRef.value?.open(articleSource.value);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
