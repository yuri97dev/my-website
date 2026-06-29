<template>
    <Container title="Usuários" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="getUsers"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="users"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getUsers"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
            >
                <template #item.roles="{ item }">
                    <v-chip
                        v-for="role in item.roles"
                        :key="role.id"
                        color="primary"
                        variant="outlined"
                    >{{ role.name }}</v-chip>
                </template>

                <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </template>

                <template #item.updated_at="{ item }">
                    {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </template>
            </v-data-table-server>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const search = ref(null);
    const users = ref([]);
    const loading = ref(false);

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "name",
            title: "Nome",
        },
        {
            key: "email",
            title: "Email",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "name",
            title: "Nome",
        },
        {
            key: "email",
            title: "Email",
        },
        {
            key: "roles",
            title: "Funções",
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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getUsers() {
        loading.value = true;

        try {
            const url = route("api.users.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            if( search.value ) {
                const { key, search: searchValue } = search.value || {};
                params[key] = searchValue;
            }

            const { data } = await api.get(url, { params });

            users.value = data.users;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            alertError("Ooops", "Erro ao buscar usuários");
        }

        loading.value = false;
    }

    function handleSearch() {
        pagination.page = 1;

        nextTick(async () => {
            await getUsers();
        });
    }
</script>
