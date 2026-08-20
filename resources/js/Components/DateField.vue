<template>
    <v-text-field
        type="date"
        prepend-inner-icon="mdi-calendar-outline"
        v-bind="$attrs"
        :error="hasError"
        :hint="fieldHint"
        :persistent-hint="hasPersistentHint"
        v-model="inputValue"
    ></v-text-field>
</template>

<script setup>
    import { computed, useAttrs } from "vue";

    import { formatDate, parseDate } from "@/Utils/Common";
    import { isNonBusinessDay } from "@/Utils/BusinessDays";

    defineOptions({ inheritAttrs: false });

    const attrs = useAttrs();

    const props = defineProps({
        warnNonBusinessDay: {
            type: Boolean,
            default: true,
        },
    });

    const model = defineModel({
        default: null,
    });

    const inputValue = computed({
        get() {
            if( !model.value ) {
                return "";
            }

            if(
                typeof model.value === "string"
                && /^\d{4}-\d{2}-\d{2}$/.test(model.value)
            ) {
                return model.value;
            }

            return formatDate(model.value, "yyyy-MM-dd");
        },
        set(value) {
            model.value = value || null;
        },
    });

    const showNonBusinessDayWarning = computed(() => {
        if( !props.warnNonBusinessDay ) {
            return false;
        }

        const date = parseDate(inputValue.value);

        if( !date ) {
            return false;
        }

        return isNonBusinessDay(date);
    });

    const hasError = computed(() => {
        return showNonBusinessDayWarning.value || !!attrs.error;
    });

    const fieldHint = computed(() => {
        if( showNonBusinessDayWarning.value ) {
            return "A data selecionada não é dia útil.";
        }

        return attrs.hint;
    });

    const hasPersistentHint = computed(() => {
        return showNonBusinessDayWarning.value
            || !!attrs.persistentHint
            || !!attrs["persistent-hint"];
    });
</script>
