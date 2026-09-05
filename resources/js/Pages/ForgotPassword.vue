<template>
    <Container title="Recuperar Conta" centered>
        <v-form
            style="width: 100%; max-width: var(--center-width);"
            @submit.prevent="handleSubmit"
        >
            <v-card title="Recuperar Conta" :loading="loading">
                <v-card-item>
                    <v-text-field
                        type="email"
                        label="E-mail"
                        class="mb-2"
                        autocomplete="off"
                        autofocus
                        required
                        v-model="email"
                    ></v-text-field>
                </v-card-item>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-btn
                        block
                        type="submit"
                        size="large"
                        variant="tonal"
                        color="primary"
                        :loading="loading"
                        :disabled="!canSubmit"
                    >Enviar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import api from "@/api";

    import Container from "../Components/Container.vue";

    import { validEmail } from "../Utils/Common";
    import { alertError, toastSuccess } from "../Utils/Alert";

    const email = ref("");
    const loading = ref(false);
    const sent = ref(false);

    const canSubmit = computed(() => {
        if( loading.value || sent.value ) return false;
        return validEmail(email.value);
    });

    async function handleSubmit() {
        loading.value = true;

        try {
            await api.post("/api/account/forgot-password", {
                email: email.value,
            });

            sent.value = true;
            toastSuccess("Um link para redefinir sua senha foi enviado para o seu e-mail.");
        } catch (err) {
            const { message } = err?.response?.data || {};
            alertError("Ooops", message || "Falha ao enviar e-mail de recuperação");
        }

        loading.value = false;
    }
</script>
