<template>
    <Dialog
        overlay-id="license-help"
        title="Integração com Aplicações Desktop"
        max-width="720"
        hide-footer
        no-body
        scrollable
        v-model="isOpen"
    >
        <v-card-text>
            <div>
                <h3 class="text-primary mt-0">Como funciona</h3>

                <div class="text-body-2">
                    <p>
                        A integração usa o mesmo endpoint para ativar e validar a licença. O que muda
                        é o token enviado no header <code>Authorization</code>.
                    </p>

                    <v-alert type="warning" variant="tonal">
                        <strong>O token de ativação é descartável.</strong> Use-o somente na primeira
                        requisição, para registrar o fingerprint. Depois, substitua-o pelo
                        <code>token</code> retornado pela API.
                    </v-alert>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">1. Primeira requisição: ativação</h3>

                <div class="text-body-2">
                    <p>
                        Copie o token de ativação no painel e envie-o com um fingerprint estável do
                        dispositivo. Ele expira em 1 dia e serve apenas para esta etapa.
                    </p>

                    <div class="text-overline text-medium-emphasis mb-1">Endpoint</div>

                    <div class="d-flex align-start ga-2 mb-4">
                        <code class="d-block flex-grow-1 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ refreshUrl }}</code>

                        <v-btn
                            size="small"
                            variant="tonal"
                            icon="mdi-content-copy"
                            @click="copy(refreshUrl)"
                        ></v-btn>
                    </div>

                    <div class="text-overline text-medium-emphasis mb-1">Ativar com cURL</div>

                    <div class="d-flex align-start ga-2">
                        <pre class="flex-grow-1 my-0 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ activationExample }}</pre>

                        <v-btn
                            size="small"
                            variant="tonal"
                            icon="mdi-content-copy"
                            @click="copy(activationExample)"
                        ></v-btn>
                    </div>

                    <p class="mt-4 mb-0">
                        Se a ativação for aceita, a licença fica vinculada ao fingerprint e a API
                        retorna um novo token. <strong>Salve esse novo token na máquina.</strong>
                    </p>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">2. Próximas requisições: validação</h3>

                <div class="text-body-2">
                    <p>
                        Nas chamadas seguintes, envie o <strong>último token retornado pela
                        API</strong>, mantendo o mesmo fingerprint. Cada resposta bem-sucedida traz
                        outro token: substitua o token salvo por ele.
                    </p>

                    <div class="text-overline text-medium-emphasis mb-1">Validar com cURL</div>

                    <div class="d-flex align-start ga-2 mb-4">
                        <pre class="flex-grow-1 my-0 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ refreshExample }}</pre>

                        <v-btn
                            size="small"
                            variant="tonal"
                            icon="mdi-content-copy"
                            @click="copy(refreshExample)"
                        ></v-btn>
                    </div>

                    <v-alert type="info" variant="tonal">
                        Não reutilize o token de ativação. Depois que o fingerprint for registrado,
                        ele será recusado com a mensagem: <code>Token não assinado</code>.
                    </v-alert>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">3. O que salvar no dispositivo</h3>

                <div class="text-body-2">
                    <ul class="mb-0">
                        <li><strong>Token:</strong> obrigatório para a próxima requisição; atualize-o após cada resposta 200.</li>
                        <li><strong>Fingerprint:</strong> deve permanecer igual ao informado na ativação.</li>
                        <li><strong>Chave pública:</strong> opcional; só é necessária se a aplicação quiser validar o JWT localmente.</li>
                    </ul>

                    <p class="mt-4 mb-0">
                        Para consumir este endpoint, basta guardar e reenviar o token. A assinatura e
                        a validação da licença são feitas pela API.
                    </p>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">4. Quando consultar</h3>

                <div class="text-body-2">
                    <p>
                        Consulte a cada 1 ou 2 minutos enquanto a aplicação estiver em uso. Assim,
                        suspensões, revogações e expirações são detectadas rapidamente.
                    </p>

                    <ul class="mb-0">
                        <li><strong>200:</strong> licença válida; salve o novo <code>token</code>.</li>
                        <li><strong>401:</strong> bloqueie o acesso; a licença ou o fingerprint foi recusado.</li>
                        <li><strong>400:</strong> token ausente, inválido, expirado ou incorreto para esta etapa.</li>
                    </ul>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">5. Fingerprint e troca de máquina</h3>

                <div class="text-body-2">
                    <ul class="mb-0">
                        <li>Gere um valor estável, por exemplo um SHA-256 do ID da máquina combinado com identificadores de hardware.</li>
                        <li>Não use IP, hostname ou MAC isoladamente, pois podem mudar.</li>
                        <li>Uma licença ativa só aceita o fingerprint registrado na ativação.</li>
                        <li>Para trocar de máquina, volte a licença para <code>pending</code> no painel e gere um novo token de ativação.</li>
                    </ul>
                </div>
            </div>
        </v-card-text>
    </Dialog>
</template>

<script setup>
    import Dialog from "@/Components/Dialog.vue";

    import { toastSuccess, alertError } from "@/Utils/Alert";
    import { copyText } from "@/Utils/Common";

    const isOpen = defineModel({
        default: false,
    });

    const refreshUrl = route("api.tools.licenses.token.refresh");

    const activationExample = `curl -X POST "${refreshUrl}" \\
  -H "Authorization: Bearer TOKEN_DE_ATIVACAO" \\
  -H "Content-Type: application/json" \\
  -d '{"fingerprint":"FINGERPRINT_DO_DISPOSITIVO"}'

// Resposta:
// { "token": "NOVO_TOKEN_ASSINADO", "public_key": "..." }`;

    const refreshExample = `curl -X POST "${refreshUrl}" \\
  -H "Authorization: Bearer ULTIMO_TOKEN_RETORNADO" \\
  -H "Content-Type: application/json" \\
  -d '{"fingerprint":"O_MESMO_FINGERPRINT"}'

// Salve o novo "token" da resposta para a próxima consulta.`;

    async function copy(text) {
        const { success, error } = await copyText(text);

        if( success ) {
            toastSuccess("Copiado");
        } else {
            alertError("Ooops", error || "Erro ao copiar");
        }
    }
</script>
