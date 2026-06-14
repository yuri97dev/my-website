<template>
    <v-dialog
        max-width="600"
        @after-leave="handleClose"
        v-model="isDialogListOpen"
    >
        <v-card>
            <v-card-title>Categorias</v-card-title>

            <v-list
                lines="two"
                style="height: 400px; overflow-y: auto;"
            >
                <v-skeleton-loader
                    v-if="loading"
                    type="list-item-three-line@3"
                ></v-skeleton-loader>

                <v-list-item
                    v-for="category in categories"
                    :key="category.id"
                    :title="category.name"
                    :subtitle="category.slug"
                    prepend-icon="mdi-folder-outline"
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
                                    @click="editCategory(category)"
                                ></v-list-item>

                                <v-list-item
                                    prepend-icon="mdi-delete"
                                    title="Excluir"
                                    @click="confirmRemoveCategory(category)"
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
                    Criar Categoria
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
            <v-card-title>Criar Categoria</v-card-title>

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
            <v-card-title>Excluir Categoria</v-card-title>

            <v-card-text>
                A categoria <strong class="text-primary">{{ selectedCategory?.name ?? "" }}</strong>
                será removida permanentemente. Essa ação não pode ser desfeita.
            </v-card-text>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-delete"
                    @click="removeCategory(selectedCategory)"
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

    const categories = ref([]);
    const selectedCategory = ref(null);

    const loading = ref(false);

    async function getCategories() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.categories.index");
            const { data } = await api.get(url);

            categories.value = data.categories;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar categorias");
        } finally {
            loading.value = false;
        }
    }

    async function createCategory() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.categories.store");
            await api.post(url, form);

            toastSuccess("Ok", "Categoria criada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao criar categoria");
        } finally {
            loading.value = false;
        }

        await getCategories();
    }

    async function updateCategory() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.categories.update", form.id);
            await api.put(url, form);

            toastSuccess("Ok", "Categoria atualizada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao atualizar categoria");
        } finally {
            loading.value = false;
        }

        await getCategories();
    }

    async function handleSave() {
        if( form.id ) {
            await updateCategory();
        } else {
            await createCategory();
        }
    }

    async function removeCategory(category) {
        loading.value = true;

        try {
            const url = route("api.blog.posts.categories.destroy", category.id);
            await api.delete(url);

            toastSuccess("Ok", "Categoria removida com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            hasChanges.value = true;

            selectedCategory.value = null;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover categoria");
        } finally {
            loading.value = false;
        }

        await getCategories();
    }

    function confirmRemoveCategory(category) {
        selectedCategory.value = category;
        isConfirmRemoveDialogOpen.value = true;
    }

    function openList() {
        hasChanges.value = false;
        isDialogListOpen.value = true;

        nextTick(async () => {
            await getCategories();
        });
    }

    function openCreate() {
        isDialogFormOpen.value = true;
    }

    function editCategory(category) {
        form.id = category.id;
        form.name = category.name;
        form.slug = category.slug;

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
