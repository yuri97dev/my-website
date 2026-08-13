<template>
    <v-autocomplete
        no-filter
        clearable
        return-object
        :label="label"
        :prepend-inner-icon="prependInnerIcon"
        :item-title="itemTitle"
        :item-value="itemValue"
        :items="items"
        :loading="loading"
        :menu-props="{
            maxWidth: 0,
        }"
        v-bind="$attrs"
        v-model="model"
        v-model:search="search"
    >
        <template #item="{ props: itemProps, item }">
            <v-list-item
                v-bind="itemProps"
                :title="null"
            >
                <span class="text-wrap text-break">{{ itemProps.title }}</span>
            </v-list-item>
        </template>
    </v-autocomplete>
</template>

<script setup>
    import { ref, watch, onMounted, useAttrs } from "vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    defineOptions({ inheritAttrs: false });

    const attrs = useAttrs();

    const props = defineProps({
        href: {
            type: String,
            required: true,
        },
        itemsKey: {
            type: String,
            required: true,
        },
        label: String,
        prependInnerIcon: String,
        itemTitle: {
            type: String,
            default: "name",
        },
        itemValue: String,
        filters: {
            type: Object,
            default: () => ({}),
        },
        errorMessage: {
            type: String,
            default: "Failed to fetch items",
        },
        mapItem: {
            type: Function,
            default: (item) => item,
        },
    });

    const model = defineModel();

    const items = ref([]);
    const search = ref("");
    const loading = ref(false);

    let abortController = null;
    let timer = null;

    function selectedItems() {
        if( attrs.multiple ) {
            return Array.isArray(model.value) ? model.value : [];
        }

        return model.value ? [ model.value ] : [];
    }

    function mergeItems(result) {
        if( !props.itemValue ) return result;

        const selected = selectedItems();

        if( selected.length === 0 ) return result;

        const map = {};

        for(const item of result) {
            map[item[props.itemValue]] = item;
        }

        for(const item of selected) {
            if( !map[item[props.itemValue]] ) {
                map[item[props.itemValue]] = item;
            }
        }

        return Object.values(map);
    }

    async function fetch(term = "") {
        abortController?.abort();
        abortController = new AbortController();

        loading.value = true;

        try {
            const { data } = await api.get(props.href, {
                signal: abortController.signal,
                params: {
                    ...props.filters,
                    page: 1,
                    per_page: 50,
                    search: term,
                },
            });

            const result = (data?.[props.itemsKey] || []).map(props.mapItem);

            items.value = mergeItems(result);
        } catch(e) {
            if( e?.code === "ERR_CANCELED" ) return;

            const { message } = e?.response?.data || {};
            alertError("Ooops", message || props.errorMessage);
        } finally {
            loading.value = false;
        }
    }

    function ensureItem(item) {
        if( !item || !props.itemValue ) return;

        const mapped = props.mapItem(item);
        const map = {};

        for(const existing of items.value) {
            map[existing[props.itemValue]] = existing;
        }

        if( !map[mapped[props.itemValue]] ) {
            items.value = [ mapped, ...items.value ];
        }
    }

    watch(search, (value) => {
        clearTimeout(timer);

        loading.value = true;

        timer = setTimeout(() => {
            fetch(value?.trim() || "");
        }, 300);
    });

    onMounted(async () => {
        await fetch();
    });

    defineExpose({
        ensureItem,
        items,
    });
</script>
