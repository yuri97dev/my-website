<template>
    <v-card ref="card" :title="title" variant="tonal" style="scroll-margin-top: 128px">
        <v-divider></v-divider>

        <v-card-text>
            <v-alert v-if="error" type="error" variant="tonal">{{ error }}</v-alert>

            <div v-else-if="loading" class="d-flex align-center ga-3">
                <v-progress-circular indeterminate size="24"></v-progress-circular>
                Analisando o texto…
            </div>

            <template v-else-if="answer">
                <p class="text-overline">
                    {{ type == 'choice' ? 'Alternativa escolhida' : type == 'score' ? 'Pontuação na escala' : 'Resposta mais provável' }}
                </p>

                <p class="text-h4 mb-4">{{ headline }}</p>

                <div v-for="item in distribution" :key="item.label" class="mb-4">
                    <div class="d-flex justify-space-between ga-4 mb-1">
                        <span class="text-pre-wrap">{{ item.label }}</span>
                        <span>{{ percent(item.value) }}%</span>
                    </div>

                    <v-progress-linear :model-value="item.value * 100" color="primary" bg-color="on-surface" :bg-opacity="0.12" :buffer-opacity="0" height="8" rounded></v-progress-linear>
                </div>
            </template>

            <p v-else>Envie um texto para ver o resultado da análise.</p>
        </v-card-text>
    </v-card>
</template>

<script setup>
    import { ref, computed, watch } from "vue";
    import { useDisplay } from "vuetify";

    const props = defineProps({
        title: { type: String, default: "Resposta" },
        type: String,
        answer: Object,
        loading: Boolean,
        error: String,
    });

    const card = ref(null);
    const { lgAndUp } = useDisplay();

    watch(() => props.loading, (loading, wasLoading) => {
        if( loading || !wasLoading || lgAndUp.value ) return;

        const element = card.value?.$el;

        if( element?.isConnected ) {
            element.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }, { flush: "post" });

    function percent(value) {
        return (Number(value) * 100).toLocaleString("pt-BR", { maximumFractionDigits: 1 });
    }

    const headline = computed(() => {
        if( props.type == "choice" ) return props.answer.choice;
        if( props.type == "noul" ) return props.answer.noul > 0.5 ? "Sim" : props.answer.noul < 0.5 ? "Não" : "Indefinido";

        const max = Object.keys(props.answer.legend || props.answer.probabilities || {}).length - 1;

        return `${Number(props.answer.score).toLocaleString("pt-BR", { maximumFractionDigits: 2 })} de ${max}`;
    });

    const distribution = computed(() => {
        if( props.type == "noul" ) {
            return [
                { label: "Sim", value: props.answer.noul },
                { label: "Não", value: 1 - props.answer.noul },
            ];
        }

        return Object.entries(props.answer.probabilities || {}).map(([key, value]) => ({
            label: props.type == "score" ? `${key} — ${props.answer.legend?.[key] ?? key}` : key,
            value,
        }));
    });
</script>
