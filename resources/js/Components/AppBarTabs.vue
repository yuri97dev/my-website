<template>
    <v-tabs
        v-if="list.length"
        align-tabs="center"
        color="primary"
        class="w-100"
        show-arrows
        :grow="mdAndDown && list.length > 1"
        v-model="active"
    >
        <v-tab
            v-for="tab in list"
            :key="tab.id"
            :value="tab.id"
            :text="tab.title"
            :prepend-icon="tab.icon"
            :href="tab.href"
            @click.prevent="selectTab(tab)"
        ></v-tab>
    </v-tabs>
</template>

<script setup>
    import { useDisplay } from "vuetify";

    import { useTabs } from "@/Composables/useTabs";
    import { goTo } from "@/Utils/Common";

    const { list, active } = useTabs();
    const { mdAndDown } = useDisplay();

    function selectTab(tab) {
        if( tab.href ) {
            goTo(tab.href);
            return;
        }

        active.value = tab.id;
    }
</script>
