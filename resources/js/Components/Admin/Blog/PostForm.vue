<template>
    <PostFormFields
        ref="formFieldsRef"
        @close="handleFormClosed"
    >
        <template #action-buttons>
            <v-btn
                v-if="!post?.id"
                prepend-icon="mdi-import"
                @click="openScrapeItemManager"
            >Importar</v-btn>

            <v-btn
                prepend-icon="mdi-image-multiple"
                @click="openImageManager"
            >Imagens</v-btn>
        </template>
    </PostFormFields>

    <PostImageManager ref="imageManagerRef"/>

    <ScrapeItemManager ref="scrapeItemManagerRef">
        <template #content-action-buttons>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-import"
                @click="handleImportScrapeItem"
            >Importar</v-btn>
        </template>
    </ScrapeItemManager>
</template>

<script setup>
    import { ref } from "vue";

    import PostFormFields from "@/Components/Admin/Blog/PostFormFields.vue";
    import PostImageManager from "@/Components/Admin/Blog/PostImageManager.vue";
    import ScrapeItemManager from "@/Components/Admin/Blog/ScrapeItemManager.vue";

    const emit = defineEmits([
        "form-closed",
    ]);

    const formFieldsRef = ref(null);
    const imageManagerRef = ref(null);
    const scrapeItemManagerRef = ref(null);

    const post = ref(null);

    function open(data = {}) {
        post.value = data;
        formFieldsRef.value?.open(data);
    }

    function openImageManager() {
        imageManagerRef.value?.open(post.value.id);
    }

    function openScrapeItemManager() {
        scrapeItemManagerRef.value?.open();
    }

    function handleImportScrapeItem() {
        const {
            selectedScrapeItem,
            close: closeScrapeItemManager,
        } = scrapeItemManagerRef.value;

        if( !selectedScrapeItem ) return;

        closeScrapeItemManager();

        open({
            title: selectedScrapeItem.title,
            content: selectedScrapeItem.content,
        });
    }

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
