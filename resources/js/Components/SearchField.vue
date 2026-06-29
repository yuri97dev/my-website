<template>
    <v-card hover>
        <v-autocomplete
            chips
            closable-chips
            no-filter
            hide-no-data
            return-object
            clear-on-select
            auto-select-first
            prepend-inner-icon="mdi-magnify"
            placeholder="Pesquisar"
            variant="solo"
            item-title="rawTitle"
            :multiple="multiple"
            :menu-props="{
                offset: 8,
            }"
            :list-props="{
                border: true,
                rounded: 'lg',
            }"
            :items="items"
            v-model="modelValue"
            v-model:search="search"
        >
            <template v-slot:chip="{ props, item, index }">
                <v-chip
                    v-bind="props"
                    :text="item.title"
                    color="primary"
                    @click="handleChipClick(item, index)"
                ></v-chip>
            </template>

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
        multiple: Boolean,
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
        const activeField = key || "search";

        const mergedFields = [{
            key: "search",
            title: "Busca Geral",
        }, ...props.fields];

        return mergedFields.map(field => ({
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

    function handleChipClick(item, index) {
        const { search: searchValue } = item || {};

        if( props.multiple ) {
            modelValue.value.splice(index, 1);
        } else {
            modelValue.value = null;
        }

        search.value = searchValue;
    }
</script>
