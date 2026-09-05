<template>
    <Dialog
        overlay-id="blog-tag-list"
        title="Tags"
        max-width="600"
        no-body
        @close="handleClose"
        v-model="isDialogListOpen"
    >
        <v-list
            lines="two"
            style="height: 400px; overflow-y: auto;"
        >
            <v-skeleton-loader
                v-if="loading"
                type="list-item-three-line@3"
            ></v-skeleton-loader>

            <v-list-item
                v-for="tag in tags"
                :key="tag.id"
                :title="tag.name"
                :subtitle="tag.slug"
                prepend-icon="mdi-tag"
                slim
            >
                <template #append>
                    <v-menu offset="8" location="bottom end">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                size="small"
                                icon="mdi-dots-vertical"
                                variant="tonal"
                                color="primary"
                                class="rounded"
                                v-bind="props"
                            ></v-btn>
                        </template>

                        <v-list border slim>
                            <v-list-item
                                prepend-icon="mdi-pencil"
                                title="Editar"
                                @click="editTag(tag)"
                            ></v-list-item>

                            <v-list-item
                                prepend-icon="mdi-delete"
                                title="Excluir"
                                @click="confirmRemoveTag(tag)"
                            ></v-list-item>
                        </v-list>
                    </v-menu>
                </template>
            </v-list-item>
        </v-list>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                text="Criar Tag"
                @click="openCreate"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="blog-tag-form"
        title="Criar Tag"
        max-width="500"
        @close="resetForm"
        v-model="isDialogFormOpen"
    >
        <v-text-field
            label="Nome"
            class="mb-4"
            autofocus
            required
            v-model="form.name"
        ></v-text-field>

        <v-text-field
            label="Slug"
            v-model="form.slug"
        ></v-text-field>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                :text="form.id ? 'Salvar' : 'Criar'"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>

</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";

    const emits = defineEmits([
        "update",
    ]);

    const form = reactive({
        id: null,
        name: "",
        slug: "",
    });

    const isDialogListOpen = ref(false);
    const isDialogFormOpen = ref(false);
    const hasChanges = ref(false);

    const tags = ref([]);

    const loading = ref(false);

    async function getTags() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.tags.index");
            const { data } = await api.get(url);

            tags.value = data.tags;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar tags");
        } finally {
            loading.value = false;
        }
    }

    async function createTag() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.tags.store");
            await api.post(url, form);

            toastSuccess("Tag criada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao criar tag");
        } finally {
            loading.value = false;
        }

        await getTags();
    }

    async function updateTag() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.tags.update", form.id);
            await api.put(url, form);

            toastSuccess("Tag atualizada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao atualizar tag");
        } finally {
            loading.value = false;
        }

        await getTags();
    }

    async function handleSave() {
        if( form.id ) {
            await updateTag();
        } else {
            await createTag();
        }
    }

    async function removeTag(tag) {
        loading.value = true;

        try {
            const url = route("api.blog.posts.tags.destroy", tag.id);
            await api.delete(url);

            toastSuccess("Tag removida com sucesso");

            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover tag");
        } finally {
            loading.value = false;
        }

        await getTags();
    }

    async function confirmRemoveTag(tag) {
        const { isConfirmed } = await confirmDelete(
            "Excluir Tag",
            `A tag "${tag.name}" será removida permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        await removeTag(tag);
    }

    function openList() {
        hasChanges.value = false;
        isDialogListOpen.value = true;

        nextTick(async () => {
            await getTags();
        });
    }

    function openCreate() {
        isDialogFormOpen.value = true;
    }

    function editTag(tag) {
        form.id = tag.id;
        form.name = tag.name;
        form.slug = tag.slug;

        isDialogFormOpen.value = true;
    }

    function resetForm() {
        form.id = null;
        form.name = "";
        form.slug = "";
    }

    function handleClose() {
        if( !hasChanges.value ) return;
        emits("update");
    }

    defineExpose({
        openList,
    });
</script>
