<template>
    <v-slide-group
        v-if="categories.length > 0"
        :model-value="selectedSlug"
        center-active
        show-arrows
    >
        <v-slide-group-item :value="ALL_VALUE">
            <v-chip
                :color="!category ? 'primary' : undefined"
                :variant="!category ? 'flat' : 'outlined'"
                class="ma-1"
                @click="goToCategory()"
            >
                Ver todos
            </v-chip>
        </v-slide-group-item>

        <v-slide-group-item
            v-for="item in categories"
            :key="item.id"
            :value="item.slug"
        >
            <v-chip
                :color="item.slug === category ? 'primary' : undefined"
                :variant="item.slug === category ? 'flat' : 'outlined'"
                class="ma-1"
                @click="goToCategory(item)"
            >
                {{ item.name }} ({{ item.posts_count }})
            </v-chip>
        </v-slide-group-item>
    </v-slide-group>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";

    import api from "@/api";

    import { goTo } from "@/Utils/Common";

    const ALL_VALUE = "__all__";

    const props = defineProps({
        category: {
            type: String,
            required: false,
        },
    });

    const emit = defineEmits(["loaded"]);

    const categories = ref([]);

    const selectedSlug = computed(() => props.category || ALL_VALUE);

    async function getCategories() {
        try {
            const url = route("api.blog.posts.categories.index");

            const { data } = await api.get(url, {
                params: { posts_count: true },
            });

            categories.value = data.categories.filter(
                category => category.posts_count > 0,
            );

            emit("loaded", categories.value);
        } catch (e) {
            console.log(e);
        }
    }

    function goToCategory(item = null) {
        if( !item ) {
            goTo(route("blog.index"));
            return;
        }

        goTo(route("blog.index", { category: item.slug }));
    }

    onMounted(getCategories);
</script>
