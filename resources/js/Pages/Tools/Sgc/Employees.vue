<template>
    <Container title="Funcionários" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="employees"
            :href="route('api.tools.sgc.employees.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
            :prepare-item="prepareItem"
        >
            <template #item.role="{ item }">
                <v-chip
                    size="small"
                    :color="item.role === 'admin' ? 'primary' : undefined"
                    :variant="item.role === 'admin' ? 'tonal' : 'outlined'"
                >
                    {{ roleLabel(item.role) }}
                </v-chip>
            </template>

            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.updated_at="{ item }">
                <span v-if="!!item.updated_at">
                    {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <EmployeeForm
            ref="employeeFormRef"
            @form-closed="refresh"
        ></EmployeeForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="employeeFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import EmployeeForm from "@/Components/Tools/Sgc/EmployeeForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const employeeFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

    const roleLabels = {
        admin: "Administrador",
        seller: "Vendedor",
    };

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "company_id",
            title: "Empresa",
        },
        {
            key: "name",
            title: "Nome",
        },
        {
            key: "email",
            title: "E-mail",
        },
        {
            key: "role",
            title: "Cargo",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "user_name",
            title: "Nome",
        },
        {
            key: "user_email",
            title: "E-mail",
        },
        {
            key: "role",
            title: "Cargo",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
        {
            key: "updated_at",
            title: "Atualizado Em",
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function roleLabel(role) {
        return roleLabels[role] ?? role;
    }

    function prepareItem(item) {
        return {
            ...item,
            user_name: item.user?.name,
            user_email: item.user?.email,
        };
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => employeeFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
