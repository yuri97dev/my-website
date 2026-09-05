<template>
    <div :class="rootClasses">
        <div class="d-flex align-center ga-2 pa-2 bg-surface rounded-lg">
            <v-btn
                icon="mdi-chevron-left"
                variant="tonal"
                :disabled="disabled || !canGoPrev"
                :aria-label="previousLabel"
                @click="goToPage(modelValue - 1)"
            ></v-btn>

            <span class="user-select-none text-no-wrap">
                {{ modelValue }} / {{ length }}
            </span>

            <v-btn
                icon="mdi-chevron-right"
                variant="tonal"
                :disabled="disabled || !canGoNext"
                :aria-label="nextLabel"
                @click="goToPage(modelValue + 1)"
            ></v-btn>
        </div>
    </div>
</template>

<script setup>
    import { computed } from "vue";

    const props = defineProps({
        length: {
            type: Number,
            required: true,
        },
        start: {
            type: Number,
            default: 1,
        },
        disabled: Boolean,
        sticky: Boolean,
        previousLabel: {
            type: String,
            default: "Página anterior",
        },
        nextLabel: {
            type: String,
            default: "Próxima página",
        },
    });

    const modelValue = defineModel({
        type: Number,
        required: true,
    });

    const canGoPrev = computed(() => modelValue.value > props.start);
    const canGoNext = computed(() => modelValue.value < props.start + props.length - 1);

    const rootClasses = computed(() => [
        "d-flex justify-center",
        props.sticky && "simple-pagination--sticky",
    ]);

    function goToPage(page) {
        if( props.disabled ) return;
        if( page < props.start || page > props.start + props.length - 1 ) return;

        modelValue.value = page;
    }
</script>

<style scoped>
    .simple-pagination--sticky {
        position: sticky;
        left: 0;
        bottom: 0;
        width: 100%;
        z-index: 1;
    }
</style>
