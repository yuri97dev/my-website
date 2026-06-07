<template>
    <Container centered>
        <v-form
            style="width: 100%; max-width: var(--center-width);"
            @submit.prevent="login"
        >
            <v-alert
                v-if="mainError"
                type="error"
                variant="tonal"
                icon="mdi-alert"
                class="mb-4"
            >{{ mainError }}</v-alert>

            <v-card
                title="Login"
                prepend-icon="mdi-login"
            >
                <v-card-item>
                    <v-text-field
                        type="email"
                        label="Email"
                        class="mb-4"
                        required
                        autofocus
                        :error-messages="errors.email"
                        v-model="form.email"
                    ></v-text-field>

                    <PasswordField
                        label="Senha"
                        class="mb-4"
                        required
                        :error-messages="errors.password"
                        v-model="form.password"
                    ></PasswordField>

                    <v-switch
                        label="Manter Conectado"
                        color="primary"
                        v-model="form.remember_me"
                    ></v-switch>
                </v-card-item>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-btn
                        block
                        type="submit"
                        size="large"
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-login"
                        :loading="loading"
                    >Entrar</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </Container>
</template>

<script setup>
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PasswordField from "@/Components/PasswordField.vue";

    import { goTo } from "@/Utils/Common";

    const form = reactive({
        email: "",
        password: "",
        remember_me: false,
    });

    const errors = ref({});
    const mainError = ref("");
    const loading = ref(false);

    async function login() {
        mainError.value = "";
        errors.value = {};

        loading.value = true;

        try {
            await api.post("/api/auth/login", form);
            goTo(route("home"));
        } catch (e) {
            const { message, errors: responseErrors } = e.response.data;
            errors.value = responseErrors || {};
            mainError.value = message;
        } finally {
            loading.value = false;
        }
    }
</script>
