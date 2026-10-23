import { ref, computed, inject, watch, onBeforeUnmount, onActivated, onDeactivated } from "vue";

import api from "@/api";

export const LayaRequestKey = Symbol("layaRequest");

export function useLaya(type) {
    const lastRequest = inject(LayaRequestKey);
    const state = ref("");
    const instructions = ref("");
    const criteria = ref(["", ""]);
    const answer = ref(null);
    const error = ref("");
    const loading = ref(false);

    let controller = null;

    const valid = computed(() => state.value.trim() && instructions.value.trim()
        && (type == "noul" || (criteria.value.length >= 2 && criteria.value.every(item => item.trim()))));

    const requestBody = computed(() => {
        const question = { type, instructions: instructions.value.trim() };

        if( type != "noul" ) {
            question.criteria = criteria.value.map(item => item.trim());
        }

        return {
            state: state.value,
            questions: { result: question },
        };
    });

    watch(requestBody, body => {
        lastRequest.value = body;
    });

    async function send() {
        if( !valid.value || loading.value ) return;

        controller = new AbortController();

        loading.value = true;
        answer.value = null;
        error.value = "";

        try {
            const { data } = await api.post("/api/laya", requestBody.value, { signal: controller.signal });

            answer.value = data?.answers?.result ?? null;

            if( !answer.value ) error.value = "A resposta não contém um resultado para esta análise.";
        } catch(e) {
            if( e?.code == "ERR_CANCELED" ) return;

            const message = e?.response?.data?.error || e?.response?.data?.detail;
            error.value = typeof message == "string" ? message : "Falha ao processar sua solicitação.";
        } finally {
            loading.value = false;
        }
    }

    function handleCtrlEnter(e) {
        if( e.ctrlKey && !e.shiftKey && !e.altKey && e.key == "Enter" ) {
            e.preventDefault();
            send();
        }
    }

    onActivated(() => window.addEventListener("keydown", handleCtrlEnter));
    onDeactivated(() => window.removeEventListener("keydown", handleCtrlEnter));

    onBeforeUnmount(() => {
        window.removeEventListener("keydown", handleCtrlEnter);
        controller?.abort();
    });

    return { state, instructions, criteria, answer, error, loading, valid, send };
}
