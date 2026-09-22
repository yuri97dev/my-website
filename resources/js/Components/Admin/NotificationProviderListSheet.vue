<template>
    <BottomSheet
        overlay-id="notification-provider-list"
        title="Provedores de Notificação"
        :actions="sheetActions"
        :loading="loading"
        height="80dvh"
        v-model="isOpen"
    >
        <v-row v-if="loading">
            <v-col
                v-for="index in 3"
                :key="index"
                cols="12"
                sm="6"
                lg="4"
            >
                <v-skeleton-loader type="list-item-three-line"></v-skeleton-loader>
            </v-col>
        </v-row>

        <v-empty-state
            v-else-if="providers.length === 0"
            icon="mdi-server-off"
            title="Nenhum provedor cadastrado"
            text="Adicione um provedor para começar a enviar notificações."
        ></v-empty-state>

        <v-row v-else>
            <v-col
                v-for="provider in providers"
                :key="provider.id"
                cols="12"
                lg="6"
                xl="4"
            >
                <v-card
                    border
                    hover
                    class="d-flex flex-column h-100"
                    variant="tonal"
                    :color="provider.is_enabled ? null : 'error'"
                    @click="emit('edit', provider)"
                >
                    <v-card-item>
                        <template #prepend>
                            <v-avatar
                                color="primary"
                                variant="tonal"
                                icon="mdi-server"
                            ></v-avatar>
                        </template>

                        <v-card-title class="text-wrap">
                            {{ provider.name }}
                        </v-card-title>

                        <v-card-subtitle class="text-wrap">
                            {{ providerSubtitle(provider) }}
                        </v-card-subtitle>

                        <template #append>
                            <v-chip
                                size="small"
                                variant="tonal"
                                :color="provider.is_enabled ? 'success' : 'error'"
                            >{{ provider.is_enabled ? "Ativo" : "Inativo" }}</v-chip>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { getNotificationProviderChannelLabel } from "@/Data/notificationProviderChannels";
    import { alertError } from "@/Utils/Alert";

    const emit = defineEmits([
        "add",
        "edit",
    ]);

    const sheetActions = computed(() => [
        {
            key: "refresh",
            prependIcon: "mdi-refresh",
            text: "Atualizar",
            disabled: loading.value,
            onClick: fetchProviders,
        },
        {
            key: "add",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-plus",
            text: "Adicionar",
            onClick: () => emit("add"),
        },
    ]);

    const isOpen = ref(false);
    const loading = ref(false);
    const providers = ref([]);

    function providerSubtitle(provider) {
        const channel = getNotificationProviderChannelLabel(provider.channel);
        return `${channel} · ${provider.key}`;
    }

    async function fetchProviders() {
        loading.value = true;

        try {
            const url = route("api.notification-providers.index");

            const { data } = await api.get(url);

            providers.value = data.notification_providers || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao carregar provedores");
            providers.value = [];
        } finally {
            loading.value = false;
        }
    }

    function open() {
        isOpen.value = true;
        fetchProviders();
    }

    function refresh() {
        if( isOpen.value ) {
            fetchProviders();
        }
    }

    defineExpose({
        open,
        refresh,
    });
</script>
