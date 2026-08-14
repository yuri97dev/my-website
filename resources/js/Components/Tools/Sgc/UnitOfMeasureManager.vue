<template>
    <Dialog
        overlay-id="sgc-unit-of-measure-list"
        title="Unidades de Medida"
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
                v-for="unit in unitsOfMeasure"
                :key="unit.id"
                :title="unit.name"
                :subtitle="unit.abbreviation"
                prepend-icon="mdi-scale-balance"
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
                                @click="editUnit(unit)"
                            ></v-list-item>

                            <v-list-item
                                prepend-icon="mdi-delete"
                                title="Excluir"
                                @click="confirmRemoveUnit(unit)"
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
                text="Criar Unidade"
                @click="openCreate"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-unit-of-measure-form"
        :title="form.id ? 'Editar Unidade' : 'Criar Unidade'"
        max-width="500"
        @close="resetForm"
        v-model="isDialogFormOpen"
    >
        <v-text-field
            label="Abreviação"
            class="mb-4"
            autofocus
            required
            maxlength="8"
            v-model="form.abbreviation"
        ></v-text-field>

        <v-text-field
            label="Nome"
            required
            v-model="form.name"
        ></v-text-field>

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
        overlay-id="sgc-unit-of-measure-confirm-remove"
        title="Excluir Unidade"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        A unidade <strong class="text-primary">{{ selectedUnit?.name ?? "" }}</strong>
        será removida permanentemente. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                text="Excluir"
                @click="removeUnit(selectedUnit)"
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
        abbreviation: "",
        name: "",
    });

    const isDialogListOpen = ref(false);
    const isDialogFormOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);

    const hasChanges = ref(false);

    const unitsOfMeasure = ref([]);
    const selectedUnit = ref(null);

    const loading = ref(false);

    async function getUnitsOfMeasure() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.units-of-measure.index");

            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            unitsOfMeasure.value = data.units_of_measure;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar unidades de medida");
        } finally {
            loading.value = false;
        }
    }

    async function createUnit() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.units-of-measure.store");

            await api.post(url, form);

            toastSuccess("Ok", "Unidade criada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao criar unidade");
        } finally {
            loading.value = false;
        }

        await getUnitsOfMeasure();
    }

    async function updateUnit() {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.units-of-measure.update", form.id);

            await api.put(url, form);

            toastSuccess("Ok", "Unidade atualizada com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao atualizar unidade");
        } finally {
            loading.value = false;
        }

        await getUnitsOfMeasure();
    }

    async function handleSave() {
        if( form.id ) {
            await updateUnit();
        } else {
            await createUnit();
        }
    }

    async function removeUnit(unit) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.units-of-measure.destroy", unit.id);

            await api.delete(url);

            toastSuccess("Ok", "Unidade removida com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            hasChanges.value = true;

            selectedUnit.value = null;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover unidade");
        } finally {
            loading.value = false;
        }

        await getUnitsOfMeasure();
    }

    function confirmRemoveUnit(unit) {
        selectedUnit.value = unit;
        isConfirmRemoveDialogOpen.value = true;
    }

    function openList() {
        hasChanges.value = false;
        isDialogListOpen.value = true;

        nextTick(async () => {
            await getUnitsOfMeasure();
        });
    }

    function openCreate() {
        isDialogFormOpen.value = true;
    }

    function editUnit(unit) {
        form.id = unit.id;
        form.abbreviation = unit.abbreviation || "";
        form.name = unit.name || "";

        isDialogFormOpen.value = true;
    }

    function resetForm() {
        form.id = null;
        form.abbreviation = "";
        form.name = "";
    }

    function handleClose() {
        if( !hasChanges.value ) return;
        emits("update");
    }

    defineExpose({
        openList,
    });
</script>
