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

            <v-list-group v-if="isAuthenticated" value="sgc">
                <template v-slot:activator="{ props }">
                    <v-list-item
                        prepend-icon="mdi-storefront-outline"
                        title="SGC"
                        v-bind="props"
                    ></v-list-item>
                </template>

                <v-list-item
                    v-for="link in sgcLinks"
                    :key="link.key"
                    :prepend-icon="link.icon"
                    :title="link.text"
                    :href="link.href"
                    :active="link.active"
                    color="primary"
                    @click.prevent="goTo(link.href)"
                ></v-list-item>
            </v-list-group>
        </v-list>
    </v-navigation-drawer>
</template>

<script setup>
    import { computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import { goTo } from "@/Utils/Common";
    import { tools } from "@/Data/tools";
    import { sgcSections, getSgcLinks } from "@/Data/sgc";

    const drawer = defineModel();
    const page = usePage();

    const isAdmin = computed(() => page.props?.auth?.isSysAdmin);
    const isAuthenticated = computed(() => !!page.props?.auth?.user);

    const sgcLinks = computed(() => getSgcLinks());

    const links = computed(() => [
        {
            key: "blog",
            icon: "mdi-newspaper",
            text: "Blog",
            href: route("blog.index"),
            active: route().current("blog.index"),
        },
        ...tools
            .filter(tool => tool.key !== "sgc")
            .map(tool => ({
                key: tool.key,
                icon: tool.icon,
                text: tool.title,
                href: tool.href,
                active: route().current(`tools.${tool.key}*`),
            })),
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
            key: "blog-article-sources",
            icon: "mdi-web",
            text: "Fontes de Artigos",
            href: route("admin.blog.article-sources"),
            active: route().current("admin.blog.article-sources"),
        },
        {
            key: "ai-providers",
            icon: "mdi-robot",
            text: "Provedores de IA",
            href: route("admin.ai-providers"),
            active: route().current("admin.ai-providers"),
        },
        {
            key: "scrape-http-profiles",
            icon: "mdi-web-box",
            text: "Perfis HTTP",
            href: route("admin.scrape-http-profiles"),
            active: route().current("admin.scrape-http-profiles"),
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
        const groups = [];

        if( isAdmin.value && adminLinks.value.some(link => link.active) ) {
            groups.push("admin");
        }

        if(
            route().current("tools.sgc") ||
            route().current("tools.sgc.*")
        ) {
            groups.push("sgc");
        }

        return groups;
    });
</script>
