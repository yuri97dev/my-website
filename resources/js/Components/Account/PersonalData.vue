<template>
    <v-form
        class="mb-4"
        @submit.prevent="updatePersonalData"
    >
        <v-card title="Dados Pessoais" :loading="loading.personalData">
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
                    label="Email"
                    class="mb-4"
                    required
                    :error-messages="errors.email"
                    v-model="form.email"
                ></v-text-field>

                <v-checkbox
                    color="primary"
                    v-model="form.accepts_marketing"
                >
                    <template #label>
                        <div class="ml-2">
                            Quero receber novidades, ofertas e conteúdos exclusivos por e-mail. (Opcional)
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
                    prepend-icon="mdi-content-save"
                    text="Salvar"
                    :loading="loading.personalData"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </v-form>

    <v-form
        class="mb-0"
        @submit.prevent="updatePassword"
    >
        <v-card title="Alterar Senha" :loading="loading.password">
            <v-card-item>
                <PasswordField
                    label="Senha Atual"
                    class="mb-4"
                    required
                    :error-messages="errors.current_password"
                    v-model="form.current_password"
                ></PasswordField>

                <PasswordField
                    label="Nova Senha"
                    class="mb-4"
                    required
                    :error-messages="errors.password"
                    v-model="form.password"
                ></PasswordField>

                <PasswordField
                    label="Confirmar Nova Senha"
                    class="mb-4"
                    required
                    :error-messages="errors.password_confirmation"
                    v-model="form.password_confirmation"
                ></PasswordField>
            </v-card-item>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    block
                    type="submit"
                    size="large"
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                    text="Salvar"
                    :loading="loading.password"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </v-form>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";
    import { router, usePage } from "@inertiajs/vue3";

    import api from "@/api";

    import PasswordField from "../PasswordField.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const page = usePage();

    const authUser = computed(() => page.props?.auth?.user);

    const form = reactive({
        name: authUser.value?.name ?? "",
        email: authUser.value?.email ?? "",
        current_password: "",
        password: "",
        password_confirmation: "",
        accepts_marketing: !!authUser.value?.accepts_marketing,
    });

    const errors = ref({});

    const loading = ref({
        personalData: false,
        password: false,
    });

    async function updatePersonalData() {
        errors.value = {};

        loading.value.personalData = true;

        try {
            await api.put("/api/account/personal-data", form);
            router.reload({ preserveScroll: true });
            toastSuccess("Ok", "Dados pessoais atualizados com sucesso");
        } catch (e) {
            const { message, errors: responseErrors } = e.response.data;
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar dados pessoais");
        } finally {
            loading.value.personalData = false;
        }
    }

    async function updatePassword() {
        errors.value = {};

        loading.value.password = true;

        try {
            await api.put("/api/account/password", form);

            form.current_password = "";
            form.password = "";
            form.password_confirmation = "";

            toastSuccess("Ok", "Senha alterada com sucesso");
        } catch (e) {
            const { message, errors: responseErrors } = e.response.data;
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao alterar senha");
        } finally {
            loading.value.password = false;
        }
    }
</script>
