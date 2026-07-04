<template>
    <v-card
        hover
        border
        class="h-100"
        :href="post.href"
        @click.prevent="goTo(post.href)"
    >
        <v-img
            :src="post.thumbnail_path"
            height="240px"
            class="bg-surface-light"
            cover
        ></v-img>

        <v-card-title class="text-primary">
            <div class="lines-2">
                {{ post.title }}
            </div>
        </v-card-title>

        <v-card-text :title="post.excerpt">
            <div class="lines-2">
                {{ post.excerpt || "Sem resumo disponível" }}
            </div>

            <div class="d-flex align-center justify-space-between text-grey mt-2">
                <v-chip color="primary" variant="outlined">
                    {{ post.category.name }}
                </v-chip>

                <div class="d-flex align-center ga-2 text-grey">
                    <v-icon size="small" icon="mdi-clock"></v-icon>
                    {{ formatDate(post.updated_at, "dd/MM/yyyy HH:mm") }}
                </div>
            </div>
        </v-card-text>
    </v-card>
</template>

<script setup>
    import { formatDate, goTo } from "@/Utils/Common";

    defineProps({
        post: {
            type: Object,
            required: true,
        },
    });
</script>
