<template>
    <v-text-field
        :type="showPassword ? 'text' : 'password'"
        :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
        v-bind="$attrs"
        v-model="model"
        @click:append-inner="showPassword = !showPassword"
    ></v-text-field>
</template>

<script setup>
    import { ref } from "vue";

    defineOptions({ inheritAttrs: false });

    const model = defineModel({ type: String, default: "" });

    const showPassword = ref(false);
</script>
