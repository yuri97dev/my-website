<template>
    <v-navigation-drawer v-model="drawer">
        <v-list nav>
            <v-list-item
                v-for="link in links"
                :key="link.key"
                :prepend-icon="link.icon"
                :title="link.text"
                :href="link.href"
                :active="link.active"
                color="primary"
                @click.prevent="goTo(link.href)"
            ></v-list-item>
        </v-list>
    </v-navigation-drawer>
</template>

<script setup>
    import { computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import { goTo } from "@/Utils/Common";

    const ADMIN_LINKS = [
        {
            key: "sessions",
            icon: "mdi-monitor-account",
            text: "Sessões",
            href: route("admin.sessions"),
            active: route().current("admin.sessions"),
        },
        {
            key: "users",
            icon: "mdi-account-group",
            text: "Usuários",
            href: route("admin.users"),
            active: route().current("admin.users"),
        },
        {
            key: "jobs",
            icon: "mdi-progress-clock",
            text: "Jobs",
            href: route("admin.jobs"),
            active: route().current("admin.jobs"),
        },
        {
            key: "logs",
            icon: "mdi-file-document",
            text: "Logs",
            href: route("admin.logs"),
            active: route().current("admin.logs"),
        },
    ];

    const drawer = defineModel();
    const page = usePage();

    const isAdmin = computed(() => page.props?.auth?.isSysAdmin);

    const links = computed(() => [
        {
            key: "tools",
            icon: "mdi-tools",
            text: "Ferramentas",
            href: route("tools"),
            active: route().current("tools"),
        },
        ...(isAdmin.value ? ADMIN_LINKS : []),
    ]);
</script>
