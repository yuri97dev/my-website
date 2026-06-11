<template>
    <Container title="Redefinir Senha" centered>
        <v-form
            style="width: 100%; max-width: var(--center-width);"
            @submit.prevent="handleSubmit"
        >
            <v-alert
                v-if="!hasValidLink"
                type="error"
                variant="tonal"
                icon="mdi-alert"
                class="mb-4"
            >
                Link de redefinição inválido ou incompleto.
            </v-alert>

            <v-alert
                v-if="mainError"
                type="error"
                variant="tonal"
                icon="mdi-alert"
                class="mb-4"
            >{{ mainError }}</v-alert>

            <v-alert
                v-if="reset"
                type="success"
                variant="tonal"
                icon="mdi-check"
                class="mb-4"
            >
                Sua senha foi redefinida com sucesso.
            </v-alert>

            <v-card
                title="Redefinir Senha"
                prepend-icon="mdi-lock-reset"
                :loading="loading"
            >
                <v-card-item>
                    <v-text-field
                        type="email"
                        label="E-mail"
                        class="mb-4"
                        readonly
                        :error-messages="errors.email"
                        v-model="form.email"
                    ></v-text-field>

                    <PasswordField
                        label="Nova Senha"
                        class="mb-4"
                        required
                        autofocus
                        :error-messages="errors.password"
                        v-model="form.password"
                    ></PasswordField>

                    <PasswordField
                        label="Confirmar Senha"
                        class="mb-4"
                        required
                        :error-messages="errors.password_confirmation"
                        v-model="form.password_confirmation"
                    ></PasswordField>
                </v-card-item>

                <v-divider></v-divider>

                <v-card-actions class="flex-column">
                    <v-btn
                        block
                        type="submit"
                        size="large"
                        variant="tonal"
                        color="primary"
                        :loading="loading"
                        :disabled="!canSubmit"
                    >Redefinir Senha</v-btn>

                    <v-btn
                        v-if="reset"
                        block
                        size="large"
                        variant="text"
                        :href="route('login')"
                        @click.prevent="goTo(route('login'))"
                    >Ir para o login</v-btn>

                    <v-btn
                        v-else-if="!hasValidLink"
                        block
                        size="large"
                        variant="text"
                        :href="route('forgot_password')"
                        @click.prevent="goTo(route('forgot_password'))"
                    >Solicitar novo link</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </Container>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PasswordField from "@/Components/PasswordField.vue";

    import { goTo, validEmail } from "@/Utils/Common";

    const params = new URLSearchParams(window.location.search);

    const form = reactive({
        token: params.get("token") ?? "",
        email: params.get("email") ?? "",
        password: "",
        password_confirmation: "",
    });

    const errors = ref({});
    const mainError = ref("");
    const loading = ref(false);
    const reset = ref(false);

    const hasValidLink = computed(() => {
        return !!form.token && validEmail(form.email);
    });

    const canSubmit = computed(() => {
        if( loading.value || reset.value || !hasValidLink.value ) return false;

        return form.password.length >= 6
            && form.password_confirmation.length >= 6
            && form.password === form.password_confirmation;
    });

    async function handleSubmit() {
        mainError.value = "";
        errors.value = {};

        loading.value = true;

        try {
            await api.post("/api/account/reset-password", form);
            reset.value = true;
        } catch (e) {
            const { message, errors: responseErrors } = e.response?.data || {};
            errors.value = responseErrors || {};
            mainError.value = message || "Falha ao redefinir senha";
        } finally {
            loading.value = false;
        }
    }
</script>
