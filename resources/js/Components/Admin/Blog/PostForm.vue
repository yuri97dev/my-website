<template>
    <PostFormFields ref="formFieldsRef">
        <template #action-buttons>
            <v-btn
                prepend-icon="mdi-image-multiple"
                @click="openImageManager"
            >Imagens</v-btn>
        </template>
    </PostFormFields>

    <PostImageManager ref="imageManagerRef"/>
</template>

<script setup>
    import { ref } from "vue";

    import PostFormFields from "@/Components/Admin/Blog/PostFormFields.vue";
    import PostImageManager from "@/Components/Admin/Blog/PostImageManager.vue";

    const formFieldsRef = ref(null);
    const imageManagerRef = ref(null);

    const post = ref(null);

    function open(data = {}) {
        post.value = data;
        formFieldsRef.value?.open(data);
    }

    function openImageManager() {
        imageManagerRef.value?.open(post.value.id);
    }

    defineExpose({
        open,
    });
</script>
