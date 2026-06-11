<template>
    <Container title="Usuários">
        <v-card hover border class="mb-4">
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getUsers"
                v-model="search"
            ></v-text-field>
        </v-card>

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
                    {{ format(item.created_at, "dd/MM/yyyy HH:mm") }}
                </template>

                <template #item.updated_at="{ item }">
                    {{ format(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </template>
            </v-data-table-server>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, reactive, onMounted } from "vue";
    import { format } from "date-fns";

    import Container from "@/Components/Container.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    const search = ref("");
    const users = ref([]);
    const loading = ref(false);

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
                search: search.value,
                page: pagination.page,
                per_page: pagination.perPage,
            };

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

    async function handleSearch() {
        pagination.page = 1;
        await getUsers();
    }
</script>
