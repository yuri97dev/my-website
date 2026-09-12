<template>
    <Container title="Licenças" gapped>
        <VirtualRemoteTable
            ref="tableRef"
            items-key="licenses"
            :href="route('api.tools.licenses.index')"
            :headers="headers"
            :row-props="rowProps"
            :prepare-item="prepareItem"
            @loaded="onTableLoaded"
        >
            <template #item.status="{ item }">
                <v-chip
                    size="small"
                    variant="tonal"
                    :color="statusColor(item.status)"
                >
                    {{ statusLabel(item.status) }}
                </v-chip>
            </template>

            <template #item.expires_at="{ item }">
                <span v-if="!!item.expires_at">
                    {{ formatDate(item.expires_at, "dd/MM/yyyy") }}
                </span>
            </template>

            <template #item.fingerprint="{ item }">
                <span
                    v-if="item.fingerprint"
                    class="text-truncate d-inline-block"
                    style="max-width: 20ch;"
                    :title="item.fingerprint"
                >
                    {{ item.fingerprint }}
                </span>
                <span v-else class="text-medium-emphasis">-</span>
            </template>

            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <LicenseForm
            ref="licenseFormRef"
            :max-licenses="maxLicenses"
            :used-licenses="usedLicenses"
            @saved="refresh"
            @form-closed="refresh"
        ></LicenseForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="licenseFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref } from "vue";

    import Container from "@/Components/Container.vue";
    import LicenseForm from "@/Components/Tools/License/LicenseForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const props = defineProps({
        max_licenses: Number,
    });

    const licenseFormRef = ref(null);
    const tableRef = ref(null);

    const maxLicenses = ref(props.max_licenses ?? 0);
    const usedLicenses = ref(0);

    const headers = ref([
        {
            key: "label",
            title: "Nome",
        },
        {
            key: "status",
            title: "Status",
        },
        {
            key: "expires_at",
            title: "Expira em",
        },
        {
            key: "fingerprint",
            title: "Dispositivo",
        },
        {
            key: "created_at",
            title: "Criado em",
        },
    ]);

    const statusLabels = {
        pending: "Pendente",
        active: "Ativa",
        suspended: "Suspensa",
        revoked: "Revogada",
    };

    const statusColors = {
        pending: "warning",
        active: "success",
        suspended: "orange",
        revoked: "error",
    };

    function statusLabel(status) {
        return statusLabels[status] || status;
    }

    function statusColor(status) {
        return statusColors[status] || "default";
    }

    function prepareItem(item) {
        return {
            ...item,
        };
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => licenseFormRef.value.open(item),
        };
    }

    function onTableLoaded(data) {
        usedLicenses.value = data?.used_licenses ?? 0;
        maxLicenses.value = data?.max_licenses ?? props.max_licenses ?? 0;
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
