<template>
    <Dialog
        overlay-id="sgc-product-category-list"
        title="Categorias de Produto"
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
                v-for="category in categories"
                :key="category.id"
                :title="category.name"
                :subtitle="category.description"
                prepend-icon="mdi-shape-outline"
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

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                text="Criar Categoria"
                @click="openCreate"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-product-category-form"
        :title="form.id ? 'Editar Categoria' : 'Criar Categoria'"
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

        <v-textarea
            label="Descrição"
            rows="2"
            auto-grow
            v-model="form.description"
        ></v-textarea>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :text="form.id ? 'Salvar' : 'Criar'"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-product-category-confirm-remove"
        title="Excluir Categoria"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        A categoria <strong class="text-primary">{{ selectedCategory?.name ?? "" }}</strong>
        será removida permanentemente. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                text="Excluir"
                @click="removeCategory(selectedCategory)"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emits = defineEmits([
        "update",
    ]);

    const form = reactive({
        id: null,
        name: "",
        description: "",
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
            const url = route("api.tools.sgc.product-categories.index");
            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            categories.value = data.product_categories;
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
            const url = route("api.tools.sgc.product-categories.store");
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
            const url = route("api.tools.sgc.product-categories.update", form.id);
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
            const url = route("api.tools.sgc.product-categories.destroy", category.id);
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
        form.description = category.description || "";

        isDialogFormOpen.value = true;
    }

    function resetForm() {
        form.id = null;
        form.name = "";
        form.description = "";
    }

    function handleClose() {
        if( !hasChanges.value ) return;
        emits("update");
    }

    defineExpose({
        openList,
    });
</script>
