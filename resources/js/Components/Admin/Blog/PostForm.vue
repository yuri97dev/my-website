<template>
    <PostFormFields
        ref="formFieldsRef"
        @close="handleFormClosed"
    >
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

    const emit = defineEmits([
        "form-closed",
    ]);

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

    function handleFormClosed() {
        emit("form-closed");
    }

    defineExpose({
        open,
    });
</script>
