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

    import { goTo } from "@/Utils/Common";

    const drawer = defineModel();

    const links = computed(() => [
        {
            key: "tools",
            icon: "mdi-tools",
            text: "Ferramentas",
            href: route("tools"),
            active: route().current("tools"),
        },
    ]);
</script>
