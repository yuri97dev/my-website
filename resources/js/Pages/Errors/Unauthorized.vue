<template>
    <Container title="Não autenticado" centered>
        <v-empty-state
            headline="401"
            icon="mdi-lock-outline"
            title="Não autenticado"
            text="Você precisa estar logado para acessar esta página."
            action-text="Fazer login"
            @click:action="goTo(route('login'))"
        ></v-empty-state>
    </Container>
</template>

<script setup>
    import Container from "@/Components/Container.vue";
    import { goTo } from "@/Utils/Common";
</script>
