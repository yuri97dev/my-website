<template>
    <BottomSheet
        :title="isEditing ? 'Editar Fonte' : 'Nova Fonte'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="3" xl="3">
                <v-select
                    label="Driver"
                    :items="DRIVERS"
                    :error-messages="errors.driver"
                    v-model="form.driver"
                ></v-select>
            </v-col>

            <v-col cols="12" lg="3" xl="3">
                <v-text-field
                    label="Locale"
                    :error-messages="errors.locale"
                    v-model="form.locale"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="3" xl="2">
                <v-select
                    label="Método HTTP"
                    :items="HTTP_METHODS"
                    :error-messages="errors.http_method"
                    v-model="form.http_method"
                ></v-select>
            </v-col>

            <v-col cols="12" lg="9" xl="10">
                <v-text-field
                    label="URL da Lista"
                    :error-messages="errors.list_url"
                    v-model="form.list_url"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Selector da Lista"
                    :error-messages="errors.list_item_selector"
                    v-model="form.list_item_selector"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Selector do Título"
                    :error-messages="errors.item_title_selector"
                    v-model="form.item_title_selector"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Selector do Link"
                    :error-messages="errors.item_link_selector"
                    v-model="form.item_link_selector"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Selector do Conteúdo"
                    :error-messages="errors.content_selector"
                    v-model="form.content_selector"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="3" xl="2">
                <v-switch
                    label="Ativo"
                    color="primary"
                    :error-messages="errors.is_enabled"
                    v-model="form.is_enabled"
                ></v-switch>
            </v-col>
        </v-row>

        <template #actions>
            <slot
                v-if="isEditing"
                name="action-buttons"
            ></slot>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { alertError, alertSuccess } from "@/Utils/Alert";

    const DRIVERS = [
        { title: "RSS", value: "rss" },
        { title: "Browser", value: "browser" },
    ];

    const HTTP_METHODS = [ "GET", "POST" ];

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);

    const loading = ref(false);
    const errors = ref({});

    const articleSource = ref({});
    const form = ref({});

    const isEditing = computed(() => !!articleSource.value.id);

    async function updateArticleSource() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.article-sources.update", articleSource.value.id);
            const { data } = await api.put(url, form.value);

            alertSuccess("Ok", "Site atualizado com sucesso");

            articleSource.value = data.article_source;

            form.value = {
                ...form.value,
                ...data.article_source,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar site");
        } finally {
            loading.value = false;
        }
    }

    async function createArticleSource() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.article-sources.store");
            const { data } = await api.post(url, form.value);

            alertSuccess("Ok", "Site criado com sucesso");

            articleSource.value = data.article_source;

            form.value = {
                ...form.value,
                ...data.article_source,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar site");
        } finally {
            loading.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateArticleSource();
        } else {
            await createArticleSource();
        }
    }

    function open(data = {}) {
        document.documentElement.style.overflowY = "hidden";

        articleSource.value = data;

        form.value = {
            ...form.value,
            ...data,
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};

        articleSource.value = {};

        form.value = {
            name: null,
            driver: null,
            locale: null,
            http_method: "GET",
            list_url: null,
            list_item_selector: null,
            item_title_selector: null,
            item_link_selector: null,
            content_selector: null,
            is_enabled: true,
        };
    }

    function handleClose() {
        reset();

        document.documentElement.style.overflowY = "scroll";

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    defineExpose({
        open,
    });
</script>
