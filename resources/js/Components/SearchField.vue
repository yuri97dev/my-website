<template>
    <v-card hover border>
        <v-autocomplete
            chips
            no-filter
            hide-no-data
            return-object
            clear-on-select
            auto-select-first
            prepend-inner-icon="mdi-magnify"
            placeholder="Pesquisar"
            variant="solo"
            :items="items"
            v-model="modelValue"
            v-model:search="search"
        >
            <template v-slot:item="{ props, item }">
                <v-list-item
                    v-bind="{ ...props, ...item }"
                    color="primary"
                >
                    <template #title>
                        <span>{{ item.rawTitle }}: </span>

                        <span class="text-grey font-italic">
                            "{{ item.search?.trim() || "" }}"
                        </span>
                    </template>
                </v-list-item>
            </template>

            <template #append-inner>
                <div class="d-flex align-center ga-2">
                    <v-btn
                        size="small"
                        icon="mdi-refresh"
                        variant="tonal"
                        color="primary"
                        @click="handleRefresh"
                    ></v-btn>

                    <v-btn
                        size="small"
                        icon="mdi-close"
                        variant="tonal"
                        :disabled="!modelValue"
                        @click="handleClear"
                    ></v-btn>
                </div>
            </template>
        </v-autocomplete>
    </v-card>
</template>

<script setup>
    import { ref, computed } from "vue";

    const props = defineProps({
        fields: {
            type: Array,
            default: () => [],
        },
    });

    const emit = defineEmits([
        "refresh",
    ]);

    const modelValue = defineModel();
    const search = ref("");

    const items = computed(() => {
        const { key } = modelValue.value || {};
        const firstField = props.fields[0] || {};
        const activeField = !!key ? key : firstField.key;

        return props.fields.map(field => ({
            ...field,
            rawTitle: field.title,
            title: `${field.title}: "${search.value.trim()}"`,
            active: activeField === field.key,
            search: search.value,
        }));
    });

    function handleRefresh() {
        emit("refresh");
    }

    function handleClear() {
        search.value = "";
        modelValue.value = null;
    }
</script>
