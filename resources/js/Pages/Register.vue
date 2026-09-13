<template>
    <Container title="Cadastro" centered>
        <v-form
            style="width: 100%; max-width: var(--center-width);"
            @submit.prevent="register"
        >
            <v-card
                title="Cadastro"
                prepend-icon="mdi-account-plus"
                :loading="loading"
            >
                <v-card-item>
                    <v-text-field
                        label="Nome Completo"
                        class="mb-4"
                        required
                        autofocus
                        :error-messages="errors.name"
                        v-model="form.name"
                    ></v-text-field>

                    <v-text-field
                        type="email"
                        label="Email"
                        class="mb-4"
                        required
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

                    <PasswordField
                        label="Confirmar Senha"
                        class="mb-4"
                        required
                        :error-messages="errors.password_confirmation"
                        v-model="form.password_confirmation"
                    ></PasswordField>

                    <v-checkbox
                        color="primary"
                        class="mb-2"
                        v-model="form.remember_me"
                    >
                        <template #label>
                            <div class="ml-2">
                                Manter conectado
                            </div>
                        </template>
                    </v-checkbox>

                    <v-checkbox
                        color="primary"
                        class="mb-2"
                        v-model="form.accepts_marketing"
                    >
                        <template #label>
                            <div class="ml-2">
                                Quero receber novidades, ofertas e conteúdos exclusivos por e-mail. (Opcional)
                            </div>
                        </template>
                    </v-checkbox>

                    <v-checkbox
                        color="primary"
                        class="mb-2"
                        required
                        :error-messages="errors.accepted_terms"
                        v-model="form.accepted_terms"
                    >
                        <template #label>
                            <div class="ml-2">
                                Li e concordo com os <a :href="route('terms_of_use')" target="_blank" class="text-primary">Termos de Uso</a>
                                e a <a :href="route('privacy_policy')" target="_blank" class="text-primary">Política de Privacidade</a>. (Obrigatório)
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
                    >Cadastrar</v-btn>
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
    import { alertError } from "@/Utils/Alert";

    const form = reactive({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        remember_me: false,
        accepts_marketing: false,
        accepted_terms: false,
    });

    const errors = ref({});
    const loading = ref(false);

    async function register() {
        errors.value = {};

        loading.value = true;

        try {
            const { data } = await api.post("/api/auth/register", form);
            goTo(data.redirect || route("home"));
        } catch (e) {
            const { message, errors: responseErrors } = e.response.data;
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao cadastrar");
        } finally {
            loading.value = false;
        }
    }
</script>
