<template>
    <PostFormFields
        ref="formFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                v-if="!post?.id"
                prepend-icon="mdi-import"
                text="Importar"
                @click="openScrapedArticleManager"
            ></v-btn>

            <v-btn
                prepend-icon="mdi-image-multiple"
                text="Imagens"
                @click="openImageManager"
            ></v-btn>
        </template>
    </PostFormFields>

    <PostImageManager ref="imageManagerRef"/>

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
    import { ref } from "vue";

    import PostFormFields from "@/Components/Admin/Blog/PostFormFields.vue";
    import PostImageManager from "@/Components/Admin/Blog/PostImageManager.vue";
    import ScrapedArticleManager from "@/Components/Admin/Blog/ScrapedArticleManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const formFieldsRef = ref(null);
    const imageManagerRef = ref(null);
    const scrapedArticleManagerRef = ref(null);

    const post = ref(null);

    function open(data = {}) {
        post.value = data;
        formFieldsRef.value?.open(data);
    }

    function openImageManager() {
        imageManagerRef.value?.open(post.value.id);
    }

    function openScrapedArticleManager() {
        scrapedArticleManagerRef.value?.open();
    }

    function handleImportScrapedArticle() {
        const {
            selectedScrapedArticle,
            close: closeScrapedArticleManager,
        } = scrapedArticleManagerRef.value;

        if( !selectedScrapedArticle ) return;

        closeScrapedArticleManager();

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

        open({
            title,
            content,
            thumbnail_path: firstImage?.src ?
                new URL(firstImage.src).pathname :
                null,
        });
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
