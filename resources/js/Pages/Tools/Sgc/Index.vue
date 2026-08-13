<template>
    <Container title="SGC" centered>
        <div
            class="w-100"
            style="max-width: var(--center-width);"
        >
            <p class="text-medium-emphasis mb-6">
                Sistema de gestão comercial
            </p>

            <v-row>
                <v-col
                    v-for="section in sgcSections"
                    :key="section.key"
                    cols="12"
                >
                    <ToolCard :tool="section" />
                </v-col>
            </v-row>
        </div>
    </Container>
</template>

<script setup>
    import Container from "@/Components/Container.vue";
    import ToolCard from "@/Components/ToolCard.vue";

    import { sgcSections } from "@/Data/sgc";
</script>
