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
                    @click="selectDevice(device)"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </div>

    <Dialog
        title="Revogar Sessão"
        message="Tem certeza que deseja revogar a sessão?"
        max-width="500"
        v-model="dialog"
    >
        <template #actions>
            <v-btn
                color="primary"
                text="Confirmar"
                @click="revokeSession"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, onMounted } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, alertSuccess } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const ICONS = {
        "bot": "mdi-robot",
        "desktop": "mdi-desktop-classic",
        "mobile": "mdi-cellphone",
        "tablet": "mdi-tablet",
        "other": "mdi-help-circle",
    };

    const devices = ref([]);
    const selectedDevice = ref(null);
    const dialog = ref(false);
    const loading = ref(false);

    function selectDevice(device) {
        selectedDevice.value = device;
        dialog.value = true;
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

    async function revokeSession() {
        dialog.value = false;
        loading.value = true;

        const { id: session_id } = selectedDevice.value;

        try {
            const url = route("api.account.sessions.destroy", session_id);
            await api.delete(url);

            alertSuccess("Ok", "Sessão revogada com sucesso");
        } catch (e) {
            alertError("Ooops", "Erro ao revogar sessão");
        }

        await getDevices();
    }

    onMounted(async () => {
        await getDevices();
    });
</script>
