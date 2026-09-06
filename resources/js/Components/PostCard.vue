<template>
    <v-card
        hover
        border
        class="h-100"
        :variant="isUnpublished ? 'tonal' : undefined"
        :color="isUnpublished ? 'warning' : undefined"
        :href="post.href"
        @click.prevent="goTo(post.href)"
    >
        <v-img
            v-if="showThumbnail"
            :src="post.thumbnail_path"
            height="var(--thumbnail-height)"
            class="bg-surface-light"
            cover
            @error="thumbnailFailed = true"
        ></v-img>

        <div
            v-else
            class="d-flex align-center justify-center bg-surface-light"
            style="height: var(--thumbnail-height)"
        >
            <v-icon
                icon="mdi-image-outline"
                size="64"
                color="grey"
            ></v-icon>
        </div>

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
    import { ref, computed } from "vue";

    import { formatDate, goTo } from "@/Utils/Common";

    const props = defineProps({
        post: {
            type: Object,
            required: true,
        },
    });

    const thumbnailFailed = ref(false);

    const showThumbnail = computed(() =>
        !!props.post.thumbnail_path && !thumbnailFailed.value
    );

    const isUnpublished = computed(() => props.post.status !== "published");
</script>
