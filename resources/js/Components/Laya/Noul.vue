<template>
    <div>
        <v-row>
            <v-col cols="12" lg="6">
                <v-card title="1. Análise" variant="tonal">
                    <v-divider></v-divider>

                    <v-form @submit.prevent="send">
                        <v-card-text>
                            <v-textarea
                                label="Texto para análise"
                                rows="4"
                                class="mb-4"
                                v-model="state"
                            ></v-textarea>
                            <v-textarea
                                label="Pergunta"
                                placeholder="O texto contém um pedido de reembolso?"
                                rows="2"
                                class="mb-4"
                                v-model="instructions"
                            ></v-textarea>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions>
                            <v-btn
                                type="submit"
                                text="Analisar (Ctrl + Enter)"
                                prepend-icon="mdi-send"
                                variant="tonal"
                                color="primary"
                                :disabled="!valid"
                                :loading="loading"
                            ></v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <Result title="2. Resposta" type="noul" :answer="answer" :error="error" :loading="loading" />
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
    import { useLaya } from "@/Composables/useLaya";

    import Result from "./Result.vue";

    const { state, instructions, answer, error, loading, valid, send } = useLaya("noul");
</script>
