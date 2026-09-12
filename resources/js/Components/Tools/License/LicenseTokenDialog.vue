<template>
    <Dialog
        overlay-id="license-token"
        title="Token de Ativação"
        max-width="600"
        :loading="loading"
        v-model="isOpen"
        @close="reset"
    >
        <v-skeleton-loader
            v-if="loading"
            type="text@4"
        ></v-skeleton-loader>

        <v-textarea
            v-else
            readonly
            rows="6"
            v-model="token"
        ></v-textarea>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-copy"
                :disabled="loading || !token"
                @click="copyToken"
            >Copiar Token</v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { copyText } from "@/Utils/Common";

    const isOpen = ref(false);
    const loading = ref(false);
    const token = ref("");

    async function open({ licenseId, successMessage = null }) {
        isOpen.value = true;
        loading.value = true;

        token.value = "";

        try {
            const url = route("api.tools.licenses.token", licenseId);

            const { data } = await api.post(url);

            token.value = data.token;

            if( successMessage ) {
                toastSuccess(successMessage);
            }
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao obter token");
            isOpen.value = false;
        } finally {
            loading.value = false;
        }
    }

    function reset() {
        token.value = "";
        loading.value = false;
    }

    async function copyToken() {
        const { success, error } = await copyText(token.value);

        if( success ) {
            toastSuccess("Token copiado");
        } else {
            alertError("Ooops", error || "Erro ao copiar token");
        }
    }

    defineExpose({
        open,
    });
</script>
