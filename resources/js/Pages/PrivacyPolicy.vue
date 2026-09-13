<template>
    <Container title="Política de Privacidade" centered>
        <v-card class="w-100" style="max-width: 800px">
            <v-card-text>
                <h1 class="text-primary">Política de Privacidade</h1>

                <div>
                    <p>Esta Política de Privacidade descreve como coletamos, usamos, armazenamos e protegemos seus dados pessoais, em conformidade com a LGPD (Lei nº 13.709/2018).</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">1. Dados Coletados</h3>

                    <p>
                        <strong>Dados fornecidos:</strong> nome, e-mail e quaisquer arquivos enviados;
                    </p>

                    <p>
                        <strong>Dados de uso:</strong> IP, tipo de navegador, data e hora de acesso, coletados via cookies e logs;
                    </p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">2. Finalidade do Tratamento</h3>

                    <p v-for="(purpose, idx) in purposes" :key="idx">
                        {{ purpose }};
                    </p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">3. Base Legal</h3>

                    <p>
                        <strong>Consentimento:</strong> para comunicações de marketing;
                    </p>

                    <p>
                        <strong>Execução de contrato:</strong> para prestação de serviços;
                    </p>

                    <p>
                        <strong>Legítimo interesse:</strong> para segurança e melhoria da plataforma;
                    </p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">4. Compartilhamento de Dados</h3>
                    <p>Não compartilhamos seus dados pessoais com terceiros, exceto quando exigido por lei, ordem judicial ou para garantir a segurança da plataforma.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">5. Direitos dos Titulares</h3>

                    <p v-for="(right, i) in rights" :key="i">
                        {{ right }};
                    </p>

                    <p class="mt-3">
                        Para exercer seus direitos, entre em contato pelo e-mail:
                        <a href="mailto:contato@yuri97.com" class="text-primary">contato@yuri97.com</a>.
                    </p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">6. Segurança</h3>
                    <p>Adotamos medidas técnicas e administrativas para proteger seus dados contra acesso não autorizado e vazamentos.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">7. Retenção de Arquivos</h3>
                    <p>Arquivos enviados são armazenados apenas pelo período necessário para prestação dos serviços. Um <em>worker</em> remove arquivos antigos automaticamente.</p>
                </div>

                <v-divider class="my-4"/>

                <div>
                    <h3 class="text-primary mb-2">8. Alterações nesta Política</h3>
                    <p>Esta política pode ser atualizada. As mudanças serão publicadas nesta página com data de revisão.</p>
                </div>
            </v-card-text>
        </v-card>
    </Container>
</template>

<script setup>
    import Container from "../Components/Container.vue";

    const purposes = [
        "Prestar e aprimorar nossos serviços e ferramentas oferecidos pela plataforma",
        "Comunicar novidades, promoções e conteúdos exclusivos (com seu consentimento prévio)",
        "Garantir segurança e monitorar o uso da plataforma",
    ];

    const rights = [
        "Acesso, correção, anonimização, bloqueio ou eliminação de seus dados",
        "Portabilidade",
        "Revogação do consentimento",
    ];
</script>