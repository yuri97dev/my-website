<template>
    <v-tabs
        v-if="list.length"
        align-tabs="center"
        color="primary"
        class="w-100"
        show-arrows
        :grow="grow"
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
    import { ref, computed, onMounted } from "vue";
    import { useDisplay } from "vuetify";

    import { useTabs } from "@/Composables/useTabs";
    import { goTo } from "@/Utils/Common";

    const { list, active } = useTabs();
    const { mdAndDown } = useDisplay();

    const isMounted = ref(false);

    const grow = computed(() => {
        return isMounted.value && mdAndDown.value && list.value.length > 1;
    });

    function selectTab(tab) {
        if( tab.href ) {
            goTo(tab.href);
            return;
        }

        active.value = tab.id;
    }

    onMounted(() => {
        isMounted.value = true;
    });
</script>
