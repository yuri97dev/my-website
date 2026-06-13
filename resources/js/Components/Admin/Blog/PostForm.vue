<template>
    <PostFormFields
        ref="formFieldsRef"
        @close="handleClose"
        @open-images="openImageManager"
        v-model="isDialogOpen"
    ></PostFormFields>

    <PostImageManager
        ref="imageManagerRef"
    ></PostImageManager>
</template>

<script setup>
    import { ref } from "vue";

    import PostFormFields from "@/Components/Admin/Blog/PostFormFields.vue";
    import PostImageManager from "@/Components/Admin/Blog/PostImageManager.vue";

    const isDialogOpen = defineModel();

    const formFieldsRef = ref(null);
    const imageManagerRef = ref(null);

    function openImageManager(postId) {
        imageManagerRef.value?.open(postId);
    }

    function open(data = {}) {
        formFieldsRef.value?.open(data);
    }

    function reset() {
        formFieldsRef.value?.reset();
    }

    function handleClose() {
        reset();
        document.documentElement.style.overflowY = "scroll";
    }

    defineExpose({
        open,
        reset,
    });
</script>
