<template>
    <v-btn
        v-for="(action, index) in actions"
        :key="action.key ?? index"
        :disabled="action.disabled || disabled"
        @click="action.onClick?.()"
        v-bind="action"
    ></v-btn>
</template>

<script setup>
    defineProps({
        actions: {
            type: Array,
            default: () => [],
        },
        disabled: Boolean,
    });
</script>
