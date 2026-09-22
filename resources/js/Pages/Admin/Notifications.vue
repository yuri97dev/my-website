<template>
    <Container title="Notificações" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="notification_deliveries"
            :href="route('api.notification-deliveries.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
        >
            <template #item.status="{ item }">
                <v-chip
                    size="small"
                    variant="tonal"
                    :color="getNotificationDeliveryStatusColor(item.status)"
                >{{ getNotificationDeliveryStatusLabel(item.status) }}</v-chip>
            </template>

            <template #item.sent_at="{ item }">
                <span v-if="!!item.sent_at">
                    {{ formatDate(item.sent_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.failed_at="{ item }">
                <span v-if="!!item.failed_at">
                    {{ formatDate(item.failed_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <NotificationDeliveryDetailSheet
            ref="detailSheetRef"
            @resent="refresh"
        ></NotificationDeliveryDetailSheet>

        <NotificationProviderFormFields
            ref="providerFormRef"
            @close="providerListSheetRef?.refresh()"
        ></NotificationProviderFormFields>

        <NotificationProviderListSheet
            ref="providerListSheetRef"
            @add="providerFormRef?.open()"
            @edit="providerFormRef?.open($event)"
        ></NotificationProviderListSheet>

        <v-fab
            app
            icon="mdi-server-network"
            size="large"
            color="primary"
            title="Provedores"
            @click="providerListSheetRef?.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import NotificationDeliveryDetailSheet from "@/Components/Admin/NotificationDeliveryDetailSheet.vue";
    import NotificationProviderFormFields from "@/Components/Admin/NotificationProviderFormFields.vue";
    import NotificationProviderListSheet from "@/Components/Admin/NotificationProviderListSheet.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import {
        getNotificationDeliveryStatusColor,
        getNotificationDeliveryStatusLabel,
    } from "@/Data/notificationDeliveryStatuses";
    import { formatDate } from "@/Utils/Common";

    const tableRef = ref(null);
    const detailSheetRef = ref(null);
    const providerFormRef = ref(null);
    const providerListSheetRef = ref(null);
    const search = ref(null);

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "subject",
            title: "Assunto",
        },
        {
            key: "status",
            title: "Status",
        },
        {
            key: "channel",
            title: "Canal",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "channel",
            title: "Canal",
        },
        {
            key: "status",
            title: "Status",
        },
        {
            key: "subject",
            title: "Assunto",
        },
        {
            key: "attempts",
            title: "Tentativas",
        },
        {
            key: "sent_at",
            title: "Enviado Em",
        },
        {
            key: "failed_at",
            title: "Falhou Em",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => detailSheetRef.value?.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
