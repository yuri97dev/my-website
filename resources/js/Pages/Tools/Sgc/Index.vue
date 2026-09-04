<template>
    <Container title="SGC" gapped>
        <v-alert
            type="warning"
            variant="tonal"
            icon="mdi-flask-outline"
        >
            Projeto em beta. Recursos em desenvolvimento e sujeitos a mudanças ou falhas.
        </v-alert>

        <h1 class="my-0">
            Sistema de Gestão Comercial (SGC)
        </h1>

        <p class="my-0">
            Gerencie matriz e filiais, equipe, clientes, produtos, estoque, preços e pedidos. Controle pagamentos, parcelas e estoque automaticamente, com dados isolados por empresa e acesso seguro para proprietários e funcionários.
        </p>

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

    import { sgcSections, registerSgcTabs } from "@/Data/sgc";

    registerSgcTabs();
</script>
