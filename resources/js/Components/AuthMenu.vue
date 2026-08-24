<template>
    <v-menu v-if="authUser" location="bottom end">
        <template #activator="{ props: menuProps }">
            <v-btn
                icon="mdi-account"
                v-bind="menuProps"
            ></v-btn>
        </template>

        <v-list border slim>
            <v-list-item
                prepend-icon="mdi-account"
                title="Conta"
                :href="route('account')"
                @click.prevent="goTo"
            ></v-list-item>

            <v-list-item
                prepend-icon="mdi-logout"
                title="Logout"
                @click="confirmLogout"
            ></v-list-item>
        </v-list>
    </v-menu>

    <v-btn
        v-else
        icon="mdi-login"
        :href="route('login')"
        :active="route().current('login')"
        @click.prevent="goTo(route('login'))"
    ></v-btn>
</template>

<script setup>
    import { computed, ref } from "vue";

    import { usePage } from "@inertiajs/vue3";

    import api from "@/api";

    import { alertError, confirm } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";

    const page = usePage();

    const loading = ref(false);

    const authUser = computed(() => page.props?.auth?.user);

    async function confirmLogout() {
        const { isConfirmed } = await confirm(
            "Logout",
            "Tem certeza que deseja sair?",
            { confirmButtonText: "Sair" },
        );

        if( !isConfirmed ) return;

        await logout();
    }

    async function logout() {
        loading.value = true;

        try {
            await api.post(route("api.auth.logout"));
            goTo(route("home"));
        } catch (e) {
            alertError("Ooops", "Erro ao sair");
        } finally {
            loading.value = false;
        }
    }
</script>
