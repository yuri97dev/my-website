<template>
    <BottomSheet
        overlay-id="article-source-form"
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
                    label="Base URL do Link"
                    hint="Opcional. Prefixo concatenado ao link obtido. Ex.: https://meusite.com/news/ ou https://meusite.com/news?slug="
                    persistent-hint
                    :error-messages="errors.item_link_base_url"
                    v-model="form.item_link_base_url"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="6">
                <v-text-field
                    label="Selector do Conteúdo"
                    :error-messages="errors.content_selector"
                    v-model="form.content_selector"
                ></v-text-field>
            </v-col>

            <template v-if="isBrowserDriver">
                <v-col cols="12">
                    <v-text-field
                        label="User-Agent"
                        hint="Opcional. Substitui o user-agent padrão do Chrome headless."
                        persistent-hint
                        :error-messages="errors.user_agent"
                        v-model="form.user_agent"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="6" xl="6">
                    <v-text-field
                        label="Proxy"
                        hint="Opcional. Ex.: http://proxy:8080"
                        persistent-hint
                        :error-messages="errors.proxy_server"
                        v-model="form.proxy_server"
                    ></v-text-field>
                </v-col>

                <v-col cols="12" lg="6" xl="6">
                    <v-text-field
                        label="Accept-Language"
                        hint="Opcional. Ex.: pt-BR,pt;q=0.9,en;q=0.8"
                        persistent-hint
                        :error-messages="errors.accept_language"
                        v-model="form.accept_language"
                    ></v-text-field>
                </v-col>
            </template>

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
                color="secondary"
                prepend-icon="mdi-play-circle-outline"
                text="Testar"
                :loading="testing"
                :disabled="loading"
                @click="handleTest"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                text="Salvar"
                :disabled="testing"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>

    <Dialog
        overlay-id="article-source-test-result"
        :title="testResult?.title ?? 'Resultado do Teste'"
        no-body
        scrollable
        max-width="900px"
        v-model="isTestDialogOpen"
    >
        <v-card-text>
            <div class="mb-4">
                <a
                    :href="testResult?.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-primary"
                >
                    {{ testResult?.url }}
                </a>
            </div>

            <div
                class="post-container"
                v-html="testResult?.content ?? ''"
            ></div>
        </v-card-text>
    </Dialog>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const DRIVERS = [
        { title: "RSS", value: "rss" },
        { title: "Browser", value: "browser" },
        { title: "JSON", value: "json" },
    ];

    const HTTP_METHODS = [ "GET", "POST" ];

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);

    const loading = ref(false);
    const testing = ref(false);
    const errors = ref({});

    const testResult = ref(null);
    const isTestDialogOpen = ref(false);

    const articleSource = ref({});
    const form = ref({});

    const isEditing = computed(() => !!articleSource.value.id);
    const isBrowserDriver = computed(() => form.value.driver === "browser");

    async function updateArticleSource() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.article-sources.update", articleSource.value.id);
            const { data } = await api.put(url, form.value);

            toastSuccess("Ok", "Site atualizado com sucesso");

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

            toastSuccess("Ok", "Site criado com sucesso");

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

    async function handleTest() {
        errors.value = {};
        testing.value = true;
        testResult.value = null;

        try {
            const url = route("api.article-sources.test");

            const payload = {
                driver: form.value.driver,
                http_method: form.value.http_method,
                list_url: form.value.list_url,
                list_item_selector: form.value.list_item_selector,
                item_title_selector: form.value.item_title_selector,
                item_link_selector: form.value.item_link_selector,
                item_link_base_url: form.value.item_link_base_url,
                content_selector: form.value.content_selector,
                user_agent: form.value.user_agent,
                proxy_server: form.value.proxy_server,
                accept_language: form.value.accept_language,
            };

            const { data } = await api.post(url, payload);

            testResult.value = data;
            isTestDialogOpen.value = true;
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao testar fonte");
        } finally {
            testing.value = false;
        }
    }

    function open(data = {}) {
        articleSource.value = data;

        form.value = {
            ...form.value,
            ...data,
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};

        testResult.value = null;
        isTestDialogOpen.value = false;

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
            item_link_base_url: null,
            content_selector: null,
            user_agent: null,
            proxy_server: null,
            accept_language: null,
            is_enabled: true,
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    defineExpose({
        open,
        getArticleSource: () => articleSource.value,
    });
</script>
