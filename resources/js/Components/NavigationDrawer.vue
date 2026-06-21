<template>
    <v-navigation-drawer v-model="drawer">
        <v-list nav :opened="opened">
            <v-list-group v-if="isAdmin" value="admin">
                <template v-slot:activator="{ props }">
                    <v-list-item
                        prepend-icon="mdi-shield-account"
                        title="Admin"
                        v-bind="props"
                    ></v-list-item>
                </template>

                <v-list-item
                    v-for="link in adminLinks"
                    :key="link.key"
                    :prepend-icon="link.icon"
                    :title="link.text"
                    :href="link.href"
                    :active="link.active"
                    color="primary"
                    @click.prevent="goTo(link.href)"
                ></v-list-item>
            </v-list-group>

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

    const drawer = defineModel();
    const page = usePage();

    const isAdmin = computed(() => page.props?.auth?.isSysAdmin);

    const links = computed(() => [
        {
            key: "blog",
            icon: "mdi-newspaper",
            text: "Blog",
            href: route("blog.index"),
            active: route().current("blog.index"),
        },
        {
            key: "tools",
            icon: "mdi-tools",
            text: "Ferramentas",
            href: route("tools"),
            active: route().current("tools"),
        },
    ]);

    const adminLinks = computed(() => [
        {
            key: "blog-posts",
            icon: "mdi-file-document",
            text: "Posts",
            href: route("admin.blog.posts"),
            active: route().current("admin.blog.posts"),
        },
        {
            key: "blog-scrape-targets",
            icon: "mdi-web",
            text: "Sites",
            href: route("admin.blog.scrape-targets"),
            active: route().current("admin.blog.scrape-targets"),
        },
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
    ]);

    const opened = computed(() => {
        if( !isAdmin.value ) return [];
        const isAdminGroupOpen = adminLinks.value.some(link => link.active);
        return isAdminGroupOpen ? [ "admin" ] : [];
    });
</script>
