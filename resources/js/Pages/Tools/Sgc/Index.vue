<template>
    <Container title="SGC">
        <div class="w-100">
            <v-row>
                <v-col
                    v-for="section in sgcSections"
                    :key="section.key"
                    cols="12"
                    lg="4"
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
