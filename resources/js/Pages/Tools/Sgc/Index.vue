<template>
    <Container title="SGC" gapped>
        <v-alert
            type="warning"
            variant="tonal"
            icon="mdi-flask-outline"
        >
            Projeto em beta. Recursos ainda estão em desenvolvimento e podem mudar ou apresentar falhas.
        </v-alert>

        <p class="text-body-1">
            Sistema de Gestão Comercial para operação multiempresa: cadastre matriz e filiais,
            equipe com cargos de administrador e vendedor, clientes com endereços e anexos,
            catálogo de produtos com estoque e preços por empresa, e pedidos com formas de pagamento,
            parcelas e controle automático de estoque. Os dados ficam isolados por empresa,
            com acesso por proprietário ou funcionário vinculado.
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
