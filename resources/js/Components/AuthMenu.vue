<template>
    <v-menu v-if="authUser" location="bottom end">
        <template #activator="{ props: menuProps }">
            <v-btn
                v-bind="{ ...menuBtnProps, ...menuProps }"
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
                @click="logoutDialog = true"
            ></v-list-item>
        </v-list>
    </v-menu>

    <v-btn
        v-else
        v-bind="loginBtnProps"
    ></v-btn>

    <v-dialog
        max-width="500"
        v-model="logoutDialog"
    >
        <v-card>
            <v-card-title>
                Logout
            </v-card-title>

            <v-card-text>
                Tem certeza que deseja sair?
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    color="primary"
                    text="Confirmar"
                    :loading="loading"
                    @click="logout"
                ></v-btn>

                <v-btn
                    text="Cancelar"
                    :disabled="loading"
                    @click="logoutDialog = false"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { computed, ref } from "vue";

    import { usePage } from "@inertiajs/vue3";
    import { useDisplay } from "vuetify";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";

    const page = usePage();
    const { lgAndUp } = useDisplay();

    const logoutDialog = ref(false);
    const loading = ref(false);

    const authUser = computed(() => page.props?.auth?.user);

    const menuBtnProps = computed(() => {
        if( lgAndUp.value ) return {
            class: "h-100 rounded-0",
            prependIcon: "mdi-account",
            text: authUser.value?.name?.split(" ")[0],
        };

        return {
            icon: "mdi-account",
        };
    });

    const loginBtnProps = computed(() => {
        const shared = {
            class: "h-100 rounded-0",
            href: route("login"),
            active: route().current("login"),
            onClick: goTo,
        };

        if( lgAndUp.value ) return {
            ...shared,
            prependIcon: "mdi-login",
            text: "Login",
        };

        return {
            ...shared,
            icon: "mdi-login",
        };
    });

    async function logout() {
        loading.value = true;

        try {
            await api.post(route("api.auth.logout"));
            logoutDialog.value = false;
            goTo(route("home"));
        } catch (e) {
            alertError("Ooops", "Erro ao sair");
        } finally {
            loading.value = false;
        }
    }
</script>
