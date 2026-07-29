<template>
    <ArticleSourceFormFields
        ref="articleSourceFormFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                prepend-icon="mdi-robot-outline"
                text="Prompts de IA"
                @click="openAiPrompts"
            ></v-btn>

            <v-btn
                prepend-icon="mdi-file-document-multiple"
                text="Posts Baixados"
                @click="openPostsDownloaded"
            ></v-btn>
        </template>
    </ArticleSourceFormFields>

    <ArticleSourceAiPromptManager
        ref="articleSourceAiPromptManagerRef"
        @update="handleFormClosed"
    />

    <ScrapedArticleManager ref="scrapedArticleManagerRef"/>
</template>

<script setup>
    import { ref } from "vue";

    import ArticleSourceFormFields from "@/Components/Admin/Blog/ArticleSourceFormFields.vue";
    import ArticleSourceAiPromptManager from "@/Components/Admin/Blog/ArticleSourceAiPromptManager.vue";
    import ScrapedArticleManager from "@/Components/Admin/Blog/ScrapedArticleManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const articleSourceFormFieldsRef = ref(null);
    const articleSourceAiPromptManagerRef = ref(null);
    const scrapedArticleManagerRef = ref(null);

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
