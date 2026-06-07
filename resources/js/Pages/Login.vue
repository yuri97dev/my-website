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

                    <v-checkbox
                        color="primary"
                        v-model="form.remember_me"
                    >
                        <template #label>
                            <div class="ml-2">
                                Manter conectado
                            </div>
                        </template>
                    </v-checkbox>
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
                    >Entrar</v-btn>
                </v-card-actions>
            </v-card>

            <v-card class="mt-4">
                <v-card-item class="text-center">
                    Novo por aqui?
                    <a
                        :href="route('register')"
                        class="text-green"
                        @click.prevent="goTo(route('register'))"
                    >Cadastre-se</a>
                </v-card-item>
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
