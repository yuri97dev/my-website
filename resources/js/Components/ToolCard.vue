<template>
    <v-card
        hover
        class="h-100"
        @click.prevent="goTo(tool.href)"
    >
        <div
            class="d-flex align-center justify-center bg-grey-lighten-2"
            style="height: var(--thumbnail-height)"
        >
            <v-icon
                :icon="tool.icon"
                size="80"
            ></v-icon>
        </div>

        <v-card-title>
            {{ tool.title }}
        </v-card-title>

        <v-card-subtitle class="text-wrap">
            {{ tool.subtitle }}
        </v-card-subtitle>

        <v-card-text>
            <div class="d-flex flex-wrap ga-2">
                <v-chip
                    v-for="feature in tool.features"
                    :key="feature"
                    :value="feature"
                    color="primary"
                    variant="outlined"
                >{{ feature }}</v-chip>
            </div>
        </v-card-text>
    </v-card>
</template>

<script setup>
    import { goTo } from "@/Utils/Common";

    defineProps({
        tool: {
            type: Object,
            required: true,
        },
    });
</script>
