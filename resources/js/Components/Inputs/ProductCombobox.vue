<template>
    <RemoteCombobox
        item-title="name"
        item-value="id"
        :label="label"
        prepend-inner-icon="mdi-package-variant"
        :href="productsUrl"
        items-key="products"
        :clearable="clearable"
        :disabled="disabled"
        error-message="Erro ao buscar produtos"
        hint="Busque um produto existente ou digite um nome novo"
        persistent-hint
        v-bind="$attrs"
        v-model="model"
    ></RemoteCombobox>
</template>

<script setup>
    import RemoteCombobox from "@/Components/Inputs/RemoteCombobox.vue";

    defineOptions({ inheritAttrs: false });

    const model = defineModel();

    defineProps({
        clearable: {
            type: Boolean,
            default: false,
        },
        disabled: Boolean,
        label: {
            type: String,
            default: "Nome",
        },
    });

    const productsUrl = route("api.tools.sgc.products.index");
</script>
