<template>
    <Container title="Ferramentas" centered>
        <div
            class="w-100"
            style="max-width: var(--center-width);"
        >
            <v-row>
                <v-col
                    v-for="tool in tools"
                    :key="tool.key"
                    cols="12"
                >
                    <ToolCard :tool="tool" />
                </v-col>
            </v-row>
        </div>
    </Container>
</template>

<script setup>
    import Container from "@/Components/Container.vue";
    import ToolCard from "@/Components/ToolCard.vue";

    import { tools } from "@/Data/tools";
</script>