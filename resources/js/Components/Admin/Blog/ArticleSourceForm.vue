<template>
    <ArticleSourceFormFields
        ref="articleSourceFormFieldsRef"
        :prepend-actions="prependActions"
        @close="handleFormClosed"
    ></ArticleSourceFormFields>

    <ArticleSourceAiPromptManager
        ref="articleSourceAiPromptManagerRef"
        @update="handleFormClosed"
    />

    <ScrapedArticleManager ref="scrapedArticleManagerRef"/>
</template>

<script setup>
    import { ref, computed } from "vue";

    import ArticleSourceFormFields from "@/Components/Admin/Blog/ArticleSourceFormFields.vue";
    import ArticleSourceAiPromptManager from "@/Components/Admin/Blog/ArticleSourceAiPromptManager.vue";
    import ScrapedArticleManager from "@/Components/Admin/Blog/ScrapedArticleManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const articleSourceFormFieldsRef = ref(null);
    const articleSourceAiPromptManagerRef = ref(null);
    const scrapedArticleManagerRef = ref(null);

    const prependActions = computed(() => [
        {
            key: "ai-prompts",
            prependIcon: "mdi-robot-outline",
            text: "Prompts de IA",
            onClick: openAiPrompts,
        },
        {
            key: "scraped-articles",
            prependIcon: "mdi-file-document-multiple",
            text: "Posts Baixados",
            onClick: openPostsDownloaded,
        },
    ]);

    function open(data = {}) {
        articleSourceFormFieldsRef.value?.open(data);
    }

    function openAiPrompts() {
        const articleSource = articleSourceFormFieldsRef.value?.getArticleSource();
        articleSourceAiPromptManagerRef.value?.open(articleSource);
    }

    function openPostsDownloaded() {
        const articleSource = articleSourceFormFieldsRef.value?.getArticleSource();
        scrapedArticleManagerRef.value?.open(articleSource);
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
