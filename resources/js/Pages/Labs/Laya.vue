<template>
    <Container title="Laya" centered>
        <Splitpanes
            :horizontal="!lgAndUp"
            style="max-height: 70dvh"
            class="default-theme overflow-hidden min-h-0 ga-2"
        >
            <Pane
                v-for="({ title }, n) in items"
                :key="n"
                min-size="30"
                size="50"
                style="background: transparent"
            >
                <v-card
                    :title="title"
                    variant="tonal"
                    class="d-flex flex-column h-100"
                >
                    <MonacoEditor
                        v-if="showMonaco"
                        language="json"
                        class="rounded-lg"
                        :options="monacoOptions"
                        v-model="items[n].value"
                    />

                    <v-divider></v-divider>

                    <v-card-actions v-if="n == 0">
                        <v-btn
                            color="primary"
                            variant="tonal"
                            text="Enviar (Ctrl + Enter)"
                            prepend-icon="mdi-send"
                            :loading="loading"
                            @click="sendPrompt"
                        ></v-btn>
                    </v-card-actions>
                </v-card>
            </Pane>
        </Splitpanes>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted, onBeforeUnmount } from "vue";
    import { useDisplay } from "vuetify";

    import Container from "@/Components/Container.vue";
    import MonacoEditor from "@/Components/MonacoEditor.vue";

    import { Splitpanes, Pane } from "splitpanes";
    import "splitpanes/dist/splitpanes.css";

    import api from "@/api";

    const { lgAndUp } = useDisplay();

    const loading = ref(false);
    const showMonaco = ref(false);

    const monacoOptions = ref({
        validate: true,
        allowComments: false,
        trailingCommas: "error",
    });

    const items = ref([
        { title: "Requisição", value: "{}" },
        { title: "Resposta", value: "{}" },
    ]);

    const validRequestBody = computed(() => {
        try {
            return JSON.parse(items.value[0].value);
        } catch(e) {
            return null;
        }
    });

    let abortController = null

    async function sendPrompt() {
        abortController?.abort();

        items.value[1].value = "{}";

        if( !validRequestBody.value ) return;

        abortController = new AbortController();

        loading.value = true;

        try {
            const { data } = await api.post(`/api/laya`, {
                ...validRequestBody.value,
            }, {
                signal: abortController.signal,
            });

            items.value[1].value = JSON.stringify(data, null, 4);

            loading.value = false;
        } catch(e) {
            if( e?.code === "ERR_CANCELED" ) return;

            const respErr = e?.response?.data || {
                error: "Falha ao processar sua solicitação",
            };

            items.value[1].value = JSON.stringify(respErr, null, 4);

            loading.value = false;
        }
    }

    async function handleCtrlEnter(e) {
        if( loading.value ) return;

        if( e.ctrlKey && e.key == "Enter" ) {
            e.preventDefault();
            e.stopImmediatePropagation();

            await sendPrompt();
        }
    }

    onMounted(() => {
        showMonaco.value = true;
        window.addEventListener("keydown", handleCtrlEnter, true);
    });

    onBeforeUnmount(() => {
        window.removeEventListener("keydown", handleCtrlEnter, true);
    });
</script>
