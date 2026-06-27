<template>
    <Container title="Conta">
        <v-alert
            v-if="emailNotVerified"
            type="warning"
            variant="tonal"
            icon="mdi-alert"
            class="mb-4"
        >
            Seu e-mail não foi verificado.
            Clique <strong
                class="cursor-pointer text-primary"
                @click="resendEmailVerification"
            >aqui</strong>
            para reenviar o e-mail de verificação.
        </v-alert>

        <v-tabs
            align-tabs="center"
            color="primary"
            class="mb-4"
            :grow="!lgAndUp"
            v-model="tab"
        >
            <v-tab prepend-icon="mdi-account" text="Dados Pessoais" value="personal-data"></v-tab>
            <v-tab prepend-icon="mdi-devices" text="Dispositivos" value="devices"/>
            <v-tab prepend-icon="mdi-account-remove" text="Excluir Conta" value="delete-account"/>
        </v-tabs>

        <div
            class="mx-auto"
            style="width: 100%; max-width: var(--center-width);"
        >
            <v-tabs-window v-model="tab">
                <v-tabs-window-item value="personal-data">
                    <PersonalData />
                </v-tabs-window-item>

                <v-tabs-window-item value="devices">
                    <Devices />
                </v-tabs-window-item>

                <v-tabs-window-item value="delete-account">
                    <DeleteAccount />
                </v-tabs-window-item>
            </v-tabs-window>
        </div>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";
    import { usePage } from "@inertiajs/vue3";
    import { useDisplay } from "vuetify";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PersonalData from "@/Components/Account/PersonalData.vue";
    import Devices from "@/Components/Account/Devices.vue";
    import DeleteAccount from "@/Components/Account/DeleteAccount.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const page = usePage();
    const { lgAndUp } = useDisplay();

    const tab = ref("personal-data");

    const emailNotVerified = computed(() => {
        return !page.props?.auth?.user?.email_verified_at;
    });

    const tabDirection = computed(() =>
        lgAndUp.value ? "vertical" : "horizontal"
    );

    async function resendEmailVerification() {
        try {
            await api.post("/api/account/email/verification-notification");
            toastSuccess("Ok", "Link de verificação enviado para o seu e-mail.");
        } catch (err) {
            const { message } = err?.response?.data || {};
            alertError("Ooops", message || "Falha ao enviar e-mail de verificação");
        }
    }

    onMounted(() => {
        const { success, error } = page.props?.flash || {};

        if( success ) {
            toastSuccess("Ok", success);
        } else if( error ) {
            alertError("Ooops", error);
        }
    });
</script>
