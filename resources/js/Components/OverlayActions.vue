<template>
    <template
        v-for="(action, index) in actions"
        :key="action.key ?? index"
    >
        <v-spacer v-if="action.spacer"></v-spacer>

        <v-btn
            v-else
            :disabled="action.disabled || disabled"
            @click="action.onClick?.()"
            v-bind="action"
        ></v-btn>
    </template>
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
