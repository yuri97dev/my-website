<template>
    <div class="d-flex flex-column ga-4">
        <v-card v-if="loading && devices.length == 0">
            <v-skeleton-loader
                type="article"
                class="rounded-lg"
            ></v-skeleton-loader>
        </v-card>

        <v-card
            v-for="device in devices"
            :key="device.id"
            :subtitle="device.browser || 'Navegador Desconhecido'"
            :prepend-icon="device.icon"
        >
            <template #title>
                {{ device.os || "Dispositivo Desconhecido" }}

                <v-chip
                    v-if="device.is_current"
                    size="small"
                    color="primary"
                    variant="tonal"
                >
                    Sessão Atual
                </v-chip>
            </template>

            <v-card-text>
                <span>Última Atividade: </span>
                {{ formatDate(device.last_activity, "dd/MM/yyyy HH:mm") }}
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    color="error"
                    prepend-icon="mdi-logout"
                    text="Revogar Sessão"
                    :loading="loading"
                    :disabled="device.is_current"
                    @click="confirmRevokeSession(device)"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script setup>
    import { ref, onMounted } from "vue";

    import api from "@/api";

    import { alertError, confirm, toastSuccess } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const ICONS = {
        "bot": "mdi-robot",
        "desktop": "mdi-desktop-classic",
        "mobile": "mdi-cellphone",
        "tablet": "mdi-tablet",
        "other": "mdi-help-circle",
    };

    const devices = ref([]);
    const loading = ref(false);

    async function confirmRevokeSession(device) {
        const { isConfirmed } = await confirm(
            "Revogar Sessão",
            "Tem certeza que deseja revogar a sessão?",
        );

        if( !isConfirmed ) return;

        await revokeSession(device);
    }

    async function getDevices() {
        loading.value = true;

        try {
            const url = route("api.account.sessions.index");
            const { data } = await api.get(url);

            devices.value = data.sessions.map(session => ({
                ...session,
                icon: ICONS[session.device] || ICONS.other,
            }));
        } catch (e) {
            alertError("Ooops", "Erro ao buscar dispositivos");
        } finally {
            loading.value = false;
        }
    }

    async function revokeSession(device) {
        loading.value = true;

        try {
            const url = route("api.account.sessions.destroy", device.id);
            await api.delete(url);

            toastSuccess("Ok", "Sessão revogada com sucesso");
        } catch (e) {
            alertError("Ooops", "Erro ao revogar sessão");
        }

        await getDevices();
    }

    onMounted(async () => {
        await getDevices();
    });
</script>
