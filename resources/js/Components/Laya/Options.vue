<template>
    <div>
        <p class="mb-4">{{ ordered ? 'Organize os níveis do menor para o maior. O primeiro vale 0.' : 'Adicione as alternativas entre as quais o Laya deve escolher.' }}</p>

        <div :class="{ 'overflow-y-auto pr-2': scrollable }" :style="scrollable ? { maxHeight: '30dvh' } : null">
            <div v-for="(item, index) in model" :key="index" class="d-flex align-start ga-2 mb-4">
                <v-textarea
                    :ref="el => fields[index] = el"
                    :label="ordered ? `Nível ${index}` : `Opção ${index + 1}`"
                    rows="2"
                    hide-details
                    @keydown.shift.enter.exact.prevent.stop="add"
                    @keydown.shift.delete.exact.prevent.stop="remove(index)"
                    v-model="model[index]"
                ></v-textarea>

                <v-btn
                    icon="mdi-delete-outline"
                    variant="text"
                    :aria-label="`Remover ${ordered ? 'nível' : 'opção'} ${index + 1}`"
                    :disabled="model.length <= 2"
                    @click="remove(index)"
                ></v-btn>
            </div>
        </div>

        <p class="text-caption mt-2">
            Shift + Enter adiciona um campo. Shift + Delete remove o campo em foco. Mínimo de duas entradas.
        </p>
    </div>
</template>

<script setup>
    import { ref, nextTick } from "vue";

    defineProps({ ordered: Boolean, scrollable: Boolean });

    const model = defineModel({ type: Array, required: true });
    const fields = ref([]);

    defineExpose({ add });

    async function remove(index) {
        if( model.value.length <= 2 ) return;

        model.value.splice(index, 1);

        await nextTick();

        fields.value[Math.min(index, model.value.length - 1)]?.focus();
    }

    async function add() {
        model.value.push("");
        await nextTick();
        fields.value[model.value.length - 1]?.focus();
    }
</script>
