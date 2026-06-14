<template>
    <v-dialog
        max-width="600"
        @after-leave="handleClose"
        v-model="isDialogListOpen"
    >
        <v-card>
            <v-card-title>Tags</v-card-title>

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

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openCreate"
                >
                    Criar Tag
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
        max-width="500"
        @after-leave="resetForm"
        v-model="isDialogFormOpen"
    >
        <v-card>
            <v-card-title>Criar Tag</v-card-title>

            <v-card-item>
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
            </v-card-item>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="handleSave"
                >
                    {{ form.id ? "Salvar" : "Criar" }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        <v-card>
            <v-card-title>Excluir Tag</v-card-title>

            <v-card-text>
                A tag <strong class="text-primary">{{ selectedTag?.name ?? "" }}</strong>
                será removida permanentemente. Essa ação não pode ser desfeita.
            </v-card-text>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-delete"
                    @click="removeTag(selectedTag)"
                >
                    Excluir
                </v-btn>

                <v-btn
                    prepend-icon="mdi-close"
                    @click="isConfirmRemoveDialogOpen = false"
                >
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import { alertError, toastSuccess } from "@/Utils/Alert";

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
    const isConfirmRemoveDialogOpen = ref(false);

    const hasChanges = ref(false);

    const tags = ref([]);
    const selectedTag = ref(null);

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

            toastSuccess("Ok", "Tag criada com sucesso");

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

            toastSuccess("Ok", "Tag atualizada com sucesso");

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

            toastSuccess("Ok", "Tag removida com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            hasChanges.value = true;

            selectedTag.value = null;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover tag");
        } finally {
            loading.value = false;
        }

        await getTags();
    }

    function confirmRemoveTag(tag) {
        selectedTag.value = tag;
        isConfirmRemoveDialogOpen.value = true;
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
