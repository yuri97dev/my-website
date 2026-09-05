<template>
    <Dialog
        overlay-id="sgc-brand-list"
        title="Marcas"
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
                v-for="brand in brands"
                :key="brand.id"
                :title="brand.name"
                :subtitle="brand.description"
                prepend-icon="mdi-tag-outline"
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
                                @click="editBrand(brand)"
                            ></v-list-item>

                            <v-list-item
                                prepend-icon="mdi-delete"
                                title="Excluir"
                                @click="confirmRemoveBrand(brand)"
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
                text="Criar Marca"
                @click="openCreate"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-brand-form"
        :title="form.id ? 'Editar Marca' : 'Criar Marca'"
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
        description: "",
    });

    const isDialogListOpen = ref(false);
    const isDialogFormOpen = ref(false);
    const hasChanges = ref(false);

    const brands = ref([]);

    const loading = ref(false);

    async function getBrands() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.brands.index");
            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            brands.value = data.brands;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar marcas");
        } finally {
            loading.value = false;
        }
    }

    async function createBrand() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.brands.store");
            await api.post(url, form);

            toastSuccess("Marca criada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao criar marca");
        } finally {
            loading.value = false;
        }

        await getBrands();
    }

    async function updateBrand() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.brands.update", form.id);
            await api.put(url, form);

            toastSuccess("Marca atualizada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao atualizar marca");
        } finally {
            loading.value = false;
        }

        await getBrands();
    }

    async function handleSave() {
        if( form.id ) {
            await updateBrand();
        } else {
            await createBrand();
        }
    }

    async function removeBrand(brand) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.brands.destroy", brand.id);
            await api.delete(url);

            toastSuccess("Marca removida com sucesso");

            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover marca");
        } finally {
            loading.value = false;
        }

        await getBrands();
    }

    async function confirmRemoveBrand(brand) {
        const { isConfirmed } = await confirmDelete(
            "Excluir Marca",
            `A marca "${brand.name}" será removida permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        await removeBrand(brand);
    }

    function openList() {
        hasChanges.value = false;
        isDialogListOpen.value = true;

        nextTick(async () => {
            await getBrands();
        });
    }

    function openCreate() {
        isDialogFormOpen.value = true;
    }

    function editBrand(brand) {
        form.id = brand.id;
        form.name = brand.name;
        form.description = brand.description || "";

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
