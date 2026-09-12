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
                <h3 class="text-primary mt-0">Visão Geral</h3>

                <div class="text-body-2">
                    <p class="mb-0">
                        Cada licença possui um par de chaves RSA exclusivo. O token JWT é assinado
                        com a chave privada da licença (RS256) e pode ser validado localmente com a
                        <code>public_key</code> retornada na primeira renovação bem-sucedida.
                    </p>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">1. Ativar a Licença</h3>

                <div class="text-body-2">
                    <ol class="pl-4 mb-0">
                        <li>Crie a licença no painel e copie o <strong>token de ativação</strong> (validade de 1 dia).</li>
                        <li>Na aplicação desktop, envie uma requisição POST para o endpoint de renovação.</li>
                        <li>Informe o token no header <code>Authorization: Bearer</code> e o <code>fingerprint</code> do dispositivo no corpo.</li>
                        <li>Na primeira chamada bem-sucedida, a licença passa de <code>pending</code> para <code>active</code> e fica vinculada ao fingerprint informado.</li>
                    </ol>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">2. Consultar e Renovar o Token</h3>

                <div class="text-body-2">
                    <p>
                        Use o mesmo endpoint para consultar a situação da licença na nuvem e renovar
                        o token. A resposta indica se ela continua válida (ativa, não expirada, não
                        suspensa/revogada).
                    </p>

                    <p>
                        <strong>Consulte a cada 1 ou 2 minutos</strong> enquanto a aplicação estiver
                        em uso. Não há notificação em tempo real (WebSocket); o polling curto garante
                        que, se a licença for cancelada ou suspensa no painel, a aplicação fique
                        inutilizável em no máximo ~1 minuto.
                    </p>

                    <div class="text-overline text-medium-emphasis mb-1">Endpoint</div>

                    <div class="d-flex align-center ga-2 mb-3">
                        <code class="d-block flex-grow-1 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ refreshUrl }}</code>

                        <v-btn
                            size="small"
                            variant="tonal"
                            icon="mdi-content-copy"
                            @click="copy(refreshUrl)"
                        ></v-btn>
                    </div>

                    <div class="text-overline text-medium-emphasis mb-1">Exemplo com cURL</div>

                    <div class="d-flex align-start ga-2">
                        <pre class="flex-grow-1 my-0 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ curlExample }}</pre>

                        <v-btn
                            size="small"
                            variant="tonal"
                            icon="mdi-content-copy"
                            @click="copy(curlExample)"
                        ></v-btn>
                    </div>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">3. Respostas da API</h3>

                <div class="text-body-2">
                    <ul class="mb-0">
                        <li><strong>200</strong> — <code>{ "token": "...", "public_key": "..." }</code></li>
                        <li><strong>401</strong> — licença suspensa, revogada, expirada ou fingerprint divergente</li>
                        <li><strong>400</strong> — token Bearer inválido, expirado ou malformado</li>
                    </ul>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">4. Fingerprint do Dispositivo</h3>

                <div class="text-body-2">
                    <p>
                        O <code>fingerprint</code> identifica a máquina onde a licença está ativa.
                        Uma licença ativa só aceita renovações com o mesmo valor. Um dispositivo só
                        pode ter uma licença ativa por vez.
                    </p>

                    <p class="mb-2"><strong>Recomendações:</strong></p>

                    <ul class="mb-0">
                        <li>Combine identificadores estáveis: MAC da interface principal, ID da máquina, serial do disco ou CPU.</li>
                        <li>Não use IP sozinho — endereços mudam com frequência.</li>
                        <li>Normalize os valores (lowercase, trim) antes de concatenar.</li>
                        <li>Gere um hash fixo (ex.: SHA-256) da string resultante e envie o digest como fingerprint.</li>
                        <li>Documente quais campos entram no hash para reproduzir o mesmo valor após reinstalações parciais.</li>
                    </ul>

                    <div class="text-overline text-medium-emphasis mt-4 mb-1">Exemplo de Geração</div>

                    <pre class="my-0 font-monospace text-body-2 pa-4 rounded bg-surface-light text-pre-wrap overflow-x-auto">{{ fingerprintExample }}</pre>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">5. Validação Local do JWT</h3>

                <div class="text-body-2">
                    <p class="mb-2">
                        A validação local complementa a consulta na nuvem, mas <strong>não substitui
                        o polling</strong>: um JWT ainda dentro do <code>exp</code> pode continuar
                        válido localmente mesmo após revogação no painel. A revogação só é detectada
                        na próxima consulta bem-sucedida (ou falha 401) ao endpoint de refresh.
                    </p>

                    <ul class="mb-0">
                        <li>Verifique a assinatura RS256 com a <code>public_key</code> PEM retornada pela API.</li>
                        <li>Confira o claim <code>sub</code> (UUID da licença) e <code>fingerprint</code> (deve coincidir com o local).</li>
                        <li>O token renovado tem validade até a data de expiração da licença + 15 dias — use esse prazo como margem offline, não como intervalo de consulta.</li>
                    </ul>
                </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <div>
                <h3 class="text-primary">6. Boas Práticas</h3>

                <div class="text-body-2">
                    <ul class="mb-0">
                        <li>Execute um timer em background (1–2 min) que chame o endpoint de refresh enquanto a app estiver aberta.</li>
                        <li>Ante qualquer resposta 401, bloqueie imediatamente o acesso — não espere o JWT local expirar.</li>
                        <li>Armazene a <code>public_key</code> localmente após a primeira renovação bem-sucedida.</li>
                        <li>O endpoint aceita até 30 requisições por minuto por IP — intervalo de 1–2 min por cliente fica bem abaixo desse limite.</li>
                        <li>Para trocar de máquina, altere o status da licença para <code>pending</code> no painel — isso libera o fingerprint.</li>
                        <li>Licenças revogadas não contam no limite do plano, mas permanecem no histórico.</li>
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

    const curlExample = `curl -X POST "${refreshUrl}" \\
  -H "Authorization: Bearer SEU_TOKEN_JWT" \\
  -H "Content-Type: application/json" \\
  -d '{"fingerprint":"SHA256_DO_DISPOSITIVO"}'`;

    const fingerprintExample = `// Exemplo: hash estável a partir de identificadores locais
const parts = [
    macAddress,      // ex.: "aa:bb:cc:dd:ee:ff"
    machineId,       // ex.: UUID do SO ou serial do hardware
    hostname,        // opcional, se estável no seu cenário
].map(v => v.trim().toLowerCase()).filter(Boolean);

const fingerprint = sha256(parts.join("|"));`;

    async function copy(text) {
        const { success, error } = await copyText(text);

        if( success ) {
            toastSuccess("Copiado");
        } else {
            alertError("Ooops", error || "Erro ao copiar");
        }
    }
</script>
