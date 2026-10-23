<template>
    <div>
        <v-row>
            <v-col cols="12" lg="6">
                <v-card title="1. Análise" variant="tonal" class="mb-4">
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
                                placeholder="Qual categoria melhor descreve o texto?"
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

                <v-card title="2. Alternativas" variant="tonal">
                    <v-divider></v-divider>

                    <v-card-text>
                        <Options ref="options" v-model="criteria" scrollable />
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="flex-wrap ga-2">
                        <v-btn
                            text="Analisar (Ctrl + Enter)"
                            prepend-icon="mdi-send"
                            variant="tonal"
                            color="primary"
                            :disabled="!valid"
                            :loading="loading"
                            @click="send"
                        ></v-btn>

                        <v-spacer></v-spacer>

                        <v-btn
                            prepend-icon="mdi-plus"
                            variant="tonal"
                            text="Adicionar opção"
                            @click="options.add()"
                        ></v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <Result title="3. Resposta" type="choice" :answer="answer" :error="error" :loading="loading" />
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
    import { ref } from "vue";
    import { useLaya } from "@/Composables/useLaya";

    import Result from "./Result.vue";
    import Options from "./Options.vue";

    const options = ref(null);

    const { state, instructions, criteria, answer, error, loading, valid, send } = useLaya("choice");
</script>
