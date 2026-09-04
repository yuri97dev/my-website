<template>
    <v-chip-group
        v-if="categories.length > 0"
        color="primary"
        class="py-1"
        mandatory
        show-arrows
        center-active
        :model-value="selectedSlug"
        @update:model-value="selectCategory"
    >
        <v-chip
            :value="ALL_VALUE"
            :variant="selectedSlug === ALL_VALUE ? 'flat' : 'outlined'"
            class="my-0"
        >Ver todos</v-chip>

        <v-chip
            v-for="item in categories"
            :key="item.id"
            :value="item.slug"
            :variant="item.slug === selectedSlug ? 'flat' : 'outlined'"
            class="my-0"
        >{{ item.name }} ({{ item.posts_count }})</v-chip>
    </v-chip-group>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";

    import api from "@/api";

    import { goTo } from "@/Utils/Common";

    const ALL_VALUE = "__all__";

    const category = defineModel("category", {
        type: String,
    });

    const emit = defineEmits([
        "loaded",
    ]);

    const categories = ref([]);

    const selectedSlug = computed(() => category.value || ALL_VALUE);

    function selectCategory(value) {
        if( !value || value === ALL_VALUE ) {
            category.value = undefined;
            goTo(route("blog.index"));
            return;
        }

        category.value = value;

        goTo(route("blog.index", { category: value }));
    }

    async function getCategories() {
        try {
            const url = route("api.blog.posts.categories.index");

            const { data } = await api.get(url, {
                params: { posts_count: true },
            });

            categories.value = data.categories.filter(
                item => item.posts_count > 0,
            );

            emit("loaded", categories.value);
        } catch (e) {
            console.log(e);
        }
    }

    onMounted(getCategories);
</script>
