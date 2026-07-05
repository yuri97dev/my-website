<template>
    <Container title="Acesso negado" centered>
        <v-empty-state
            headline="403"
            icon="mdi-shield-lock-outline"
            title="Acesso negado"
            text="Você não tem permissão para acessar esta página."
            action-text="Voltar ao início"
            @click:action="goTo(route('home'))"
        ></v-empty-state>
    </Container>
</template>

<script setup>
    import Container from "@/Components/Container.vue";
    import { goTo } from "@/Utils/Common";
</script>
