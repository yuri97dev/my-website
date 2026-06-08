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

                <v-card-actions class="flex-column">
                    <v-btn
                        block
                        type="submit"
                        size="large"
                        variant="tonal"
                        color="primary"
                        :loading="loading"
                    >Entrar</v-btn>

                    <v-btn
                        block
                        size="large"
                        variant="text"
                        :disabled="loading"
                        :href="route('forgot_password')"
                        @click.prevent="goTo(route('forgot_password'))"
                    >Esqueceu a senha?</v-btn>

                    <v-divider class="w-100"></v-divider>

                    <v-btn
                        block
                        size="large"
                        color="primary"
                        variant="text"
                        :disabled="loading"
                        :href="route('register')"
                        @click.prevent="goTo(route('register'))"
                    >Criar nova conta</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </Container>
</template>

<script setup>
    import { ref, reactive, onMounted } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PasswordField from "@/Components/PasswordField.vue";

    import { goTo } from "@/Utils/Common";

    const page = usePage();

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

    onMounted(() => {
        const { error } = page.props?.flash || {};

        if( error ) {
            mainError.value = error;
        }
    });
</script>
