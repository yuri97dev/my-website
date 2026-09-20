<template>
    <PostFormFields
        ref="formFieldsRef"
        :actions="formActions"
        @close="handleFormClosed"
    />

    <PostImageManager ref="imageManagerRef"/>

    <PostViewManager ref="viewManagerRef"/>

    <ScrapedArticleManager ref="scrapedArticleManagerRef">
        <template #content-action-buttons>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-import"
                text="Importar"
                @click="handleImportScrapedArticle"
            ></v-btn>
        </template>
    </ScrapedArticleManager>
</template>

<script setup>
    import { ref, computed } from "vue";

    import PostFormFields from "@/Components/Admin/Blog/PostFormFields.vue";
    import PostImageManager from "@/Components/Admin/Blog/PostImageManager.vue";
    import PostViewManager from "@/Components/Admin/Blog/PostViewManager.vue";
    import ScrapedArticleManager from "@/Components/Admin/Blog/ScrapedArticleManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const formFieldsRef = ref(null);
    const imageManagerRef = ref(null);
    const viewManagerRef = ref(null);
    const scrapedArticleManagerRef = ref(null);

    const post = ref(null);

    const formActions = computed(() => {
        const actions = [];

        if( !post.value?.id ) {
            actions.push({
                key: "import",
                variant: "tonal",
                prependIcon: "mdi-import",
                text: "Importar",
                onClick: openScrapedArticleManager,
            });
        }

        if( post.value?.id ) {
            actions.push({
                key: "views",
                variant: "tonal",
                prependIcon: "mdi-chart-bar",
                text: "Visualizações",
                onClick: openViewManager,
            });
        }

        actions.push({
            key: "images",
            variant: "tonal",
            prependIcon: "mdi-image-multiple",
            text: "Imagens",
            onClick: openImageManager,
        });

        return actions;
    });

    function open(data = {}) {
        post.value = data;
        formFieldsRef.value?.open(data);
    }

    function openImageManager() {
        imageManagerRef.value?.open(post.value.id);
    }

    function openViewManager() {
        viewManagerRef.value?.open(post.value.id);
    }

    function openScrapedArticleManager() {
        scrapedArticleManagerRef.value?.open();
    }
    function formatExcerpt(text) {
        if( !text ) return null;

        return String(text)
            .trim()
            .split("\n")
            .map(line => line.trim())
            .join(" ")
            .split(" ")
            .map(word => word.trim())
            .join(" ");
    }

    function handleImportScrapedArticle() {
        const manager = scrapedArticleManagerRef.value;

        if( !manager?.selectedScrapedArticle ) return;

        const selectedScrapedArticle = manager.selectedScrapedArticle;

        manager.close();

        const title = (
            selectedScrapedArticle.translated_title ||
            selectedScrapedArticle.title ||
            ""
        );

        const content = (
            selectedScrapedArticle.translated_content ||
            selectedScrapedArticle.content ||
            ""
        );

        const div = document.createElement("div");

        div.innerHTML = content;

        const firstImage = div.querySelector("img");
        const firstParagraph = div.querySelector("p");

        open({
            title,
            content,
            thumbnail_path: firstImage?.src ?
                new URL(firstImage.src).pathname :
                null,
            scraped_article_id: selectedScrapedArticle.id,
            excerpt: formatExcerpt(firstParagraph?.textContent),
        });
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
