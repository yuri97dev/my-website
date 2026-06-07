<template>
    <Container centered>
        <v-card style="width: 100%; max-width: 800px">
            <v-card-title class="text-center text-primary">
                Termos de Uso
            </v-card-title>

            <v-card-text>
                <div class="text-center">
                    <p>Bem-vindo ao {{ appName }}. Ao utilizar os serviços e ferramentas disponíveis na plataforma, você concorda com estes Termos de Uso, em conformidade com a LGPD (Lei nº 13.709/2018).</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">1. Definições</h3>

                    <p>
                        <strong>Controlador:</strong> {{ appName }}, responsável pelas decisões relacionadas ao tratamento de dados.
                    </p>

                    <p>
                        <strong>Usuário:</strong> Pessoa física ou jurídica que utiliza qualquer ferramenta ou serviço da plataforma.
                    </p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">2. Ferramentas</h3>
                    <p>A plataforma oferece diversas ferramentas para atender às necessidades dos usuários. Novas funcionalidades poderão ser adicionadas futuramente.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">3. Responsabilidade pelo Conteúdo</h3>
                    <p>Você é o único responsável pelos dados e conteúdos enviados aos serviços. Não nos responsabilizamos por perdas de dados ou danos causados por ações do usuário, como exclusão de arquivos sem verificação prévia ou alterações indevidas.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">4. Retenção de Arquivos</h3>
                    <p>Arquivos enviados ao servidor têm vida útil limitada. Um processo automático (<em>worker</em>) remove periodicamente arquivos antigos. Recomendamos que salve cópias locais antes de enviar.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">5. Serviços Pagos e Garantias</h3>
                    <p>Podemos oferecer versões pagas dos serviços e ferramentas. Embora nos esforcemos para manter a plataforma estável, não garantimos ausência de bugs ou interrupções. Em caso de falhas, poderemos oferecer reembolso ou negociações amigáveis, a critério da equipe do {{ appName }}.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">6. Alterações dos Termos</h3>
                    <p>Reservamo-nos o direito de alterar estes Termos a qualquer momento. As atualizações serão publicadas nesta página com data de revisão.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">7. Legislação Aplicável</h3>
                    <p>Estes Termos são regidos pela legislação brasileira, incluindo a LGPD. Para controvérsias, fica eleito o foro da comarca de Manaus/AM.</p>
                </div>
            </v-card-text>
        </v-card>
    </Container>
</template>

<script setup>
    import { computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import Container from "../Components/Container.vue";

    const page = usePage();
    
    const appName = computed(() => page.props.appName);
</script>