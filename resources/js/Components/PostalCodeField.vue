<template>
    <v-text-field
        :label="label"
        prepend-inner-icon="mdi-map-marker-outline"
        :loading="loading"
        :error-messages="combinedErrorMessages"
        v-bind="inputAttrs"
        v-model="displayValue"
    ></v-text-field>
</template>

<script setup>
    import { ref, computed, watch, useAttrs } from "vue";

    import api from "@/api";

    defineOptions({ inheritAttrs: false });

    const attrs = useAttrs();

    const model = defineModel({
        type: [ String, null ],
        default: null,
    });

    const props = defineProps({
        label: {
            type: String,
            default: "CEP",
        },
    });

    const emit = defineEmits([
        "address-found",
    ]);

    const loading = ref(false);
    const lookupError = ref(null);
    const lastLookup = ref(null);

    let abortController = null;
    let lookupTimer = null;

    const inputAttrs = computed(() => {
        const { "error-messages": _errorMessages, errorMessages: _errorMessagesCamel, ...rest } = attrs;

        return rest;
    });

    const combinedErrorMessages = computed(() => {
        if( lookupError.value ) {
            return lookupError.value;
        }

        return attrs["error-messages"] ?? attrs.errorMessages;
    });

    function formatPostalCode(value) {
        const digits = String(value || "").replace(/\D/g, "").slice(0, 8);

        if( digits.length <= 5 ) {
            return digits;
        }

        return `${digits.slice(0, 5)}-${digits.slice(5)}`;
    }

    function normalizePostalCode(value) {
        const digits = String(value || "").replace(/\D/g, "");
        return digits.length === 8 ? digits : null;
    }

    const displayValue = computed({
        get() {
            return formatPostalCode(model.value);
        },
        set(value) {
            const formatted = formatPostalCode(value);

            model.value = formatted || null;
            lookupError.value = null;

            const normalized = normalizePostalCode(formatted);

            clearTimeout(lookupTimer);

            if( !normalized ) {
                lastLookup.value = null;
                return;
            }

            if( normalized === lastLookup.value ) {
                return;
            }

            lookupTimer = setTimeout(() => {
                lookup(normalized);
            }, 300);
        },
    });

    async function lookup(postalCode) {
        if( postalCode === lastLookup.value ) {
            return;
        }

        abortController?.abort();
        abortController = new AbortController();

        loading.value = true;
        lookupError.value = null;

        try {
            const url = route("api.postal-code.show", postalCode);

            const { data } = await api.get(url, {
                signal: abortController.signal,
            });

            lastLookup.value = postalCode;

            emit("address-found", data.postal_code_address);
        } catch (e) {
            if( e?.code === "ERR_CANCELED" ) return;

            const { message } = e?.response?.data || {};

            lookupError.value = message || "Erro ao buscar CEP";
        } finally {
            loading.value = false;
        }
    }

    watch(model, (value) => {
        if( !normalizePostalCode(value) ) {
            lastLookup.value = null;
            lookupError.value = null;
        }
    });
</script>
