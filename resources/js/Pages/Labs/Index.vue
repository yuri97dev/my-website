<template>
    <Container title="Labs">
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
    import { ref } from "vue";

    import Container from "@/Components/Container.vue";
    import ToolCard from "@/Components/ToolCard.vue";

    const tools = ref([
        {
            key: "laya",
            title: "Laya",
            subtitle: "Motor de inteligência artificial para classificação de textos e tomada de decisões automatizadas.",
            icon: "mdi-robot",
            get href() {
                return route("labs.laya");
            },
        },
    ]);
</script>
