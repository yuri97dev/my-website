<template>
    <Container title="Página não encontrada" centered>
        <v-empty-state
            headline="404"
            icon="mdi-file-question-outline"
            title="Página não encontrada"
            text="A página que você procura não existe ou foi removida."
            action-text="Voltar ao início"
            @click:action="goTo(route('home'))"
        ></v-empty-state>
    </Container>
</template>

<script setup>
    import Container from "@/Components/Container.vue";
    import { goTo } from "@/Utils/Common";
</script>
