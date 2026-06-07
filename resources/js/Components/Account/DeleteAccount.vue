<template>
    <v-card
        title="Excluir Conta"
        color="error"
        variant="tonal"
        style="width: 100%; max-width: var(--center-width);"
        :loading="loading"
    >
        <v-card-text>
            Esta ação é permanente. Todos os seus dados serão removidos e não poderão ser recuperados.
        </v-card-text>

        <v-card-item>
            <PasswordField
                label="Senha"
                class="mb-4"
                required
                @keyup.enter="deleteAccount"
                v-model="currentPassword"
            ></PasswordField>
        </v-card-item>

        <v-divider></v-divider>

        <v-card-actions>
            <v-btn
                block
                size="large"
                variant="tonal"
                color="error"
                prepend-icon="mdi-account-remove"
                text="Excluir Conta"
                :loading="loading"
                @click="deleteAccount"
            ></v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup>
    import { ref } from "vue";

    import api from "@/api";

    import PasswordField from "@/Components/PasswordField.vue";

    import { alertError } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";

    const currentPassword = ref("");

    const loading = ref(false);

    async function deleteAccount() {
        loading.value = true;

        try {
            await api.post("/api/account/destroy", {
                current_password: currentPassword.value,
            });

            goTo(route("home"));
        } catch (e) {
            const { message } = e.response.data;
            alertError("Ooops", message || "Erro ao excluir conta");
        } finally {
            loading.value = false;
        }
    }
</script>
