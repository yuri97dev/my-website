<template>
    <Container title="Ferramentas">
        <div class="w-100">
            <v-row>
                <v-col
                    v-for="tool in tools"
                    :key="tool.key"
                    cols="12"
                    lg="4"
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
