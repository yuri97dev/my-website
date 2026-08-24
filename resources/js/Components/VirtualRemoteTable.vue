<template>
    <v-card class="virtual-remote-table">
        <v-data-table-virtual
            ref="tableRef"
            class="virtual-remote-table__data text-no-wrap"
            hover
            fixed-header
            :loading="loading"
            :items="items"
            :headers="mappedHeaders"
            :show-select="showSelect"
            :item-value="itemValue"
            :row-props="rowProps"
            :no-data-text="emptyListText"
            v-model="selected"
        >
            <template
                v-for="header in filterableHeaders"
                :key="header.key"
                #[`header.${header.key}`]
            >
                <div class="d-flex align-center ga-4">
                    <span>{{ header.title }}</span>

                    <v-menu
                        offset="8"
                        :close-on-content-click="false"
                    >
                        <template #activator="{ props: menuProps }">
                            <v-btn
                                v-bind="menuProps"
                                size="x-small"
                                variant="tonal"
                                class="rounded"
                                :color="isColumnFiltered(header) ? 'primary' : undefined"
                                :icon="isColumnFiltered(header) ? 'mdi-filter' : 'mdi-filter-outline'"
                                @click="initColumnFilterDraft(header)"
                            ></v-btn>
                        </template>

                        <v-card border min-width="280">
                            <v-card-text class="d-flex flex-column ga-2">
                                <v-text-field
                                    v-for="(filterField, i) in getFilterFields(header.key, header.field)"
                                    :key="filterField.key"
                                    :autofocus="i === 0"
                                    :label="filterField.label"
                                    :placeholder="header.title"
                                    :type="header.field.type"
                                    v-model="columnFilterDraft[filterField.key]"
                                    @keyup.enter="applyColumnFilter(header)"
                                ></v-text-field>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions>
                                <v-spacer></v-spacer>

                                <v-btn
                                    color="primary"
                                    variant="tonal"
                                    @click="applyColumnFilter(header)"
                                >Filtrar</v-btn>

                                <v-btn
                                    variant="text"
                                    @click="applyColumnFilter(header, true)"
                                >Limpar</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-menu>
                </div>
            </template>

            <template
                v-for="(_, slotName) in $slots"
                :key="slotName"
                #[slotName]="scope"
            >
                <slot
                    v-if="slotName.startsWith('item.')"
                    :name="slotName"
                    v-bind="scope"
                ></slot>
            </template>

            <template #body.append>
                <tr
                    v-show="!loading"
                    ref="sentinelRef"
                    class="sentinel bg-transparent border-0"
                >
                    <td class="border-0" :colspan="columnCount"></td>
                </tr>
            </template>
        </v-data-table-virtual>

        <v-divider></v-divider>

        <v-card-actions class="justify-space-between flex-wrap">
            <div
                class="text-no-wrap flex-grow-1 flex-shrink-1 text-start"
                style="flex-basis: 0;"
            >
                <slot name="prepend-actions"></slot>
            </div>

            <div class="text-no-wrap text-center">
                {{ formatNumber(items.length, 0) }} de {{ formatNumber(pagination.total, 0) }} linha(s)
            </div>

            <div
                class="text-no-wrap flex-grow-1 flex-shrink-1 text-end"
                style="flex-basis: 0;"
            >
                <slot name="append-actions"></slot>
            </div>
        </v-card-actions>
    </v-card>
</template>

<script setup>
    import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { formatNumber } from "@/Utils/Common";

    const selected = defineModel({
        default: () => [],
    });

    const props = defineProps({
        href: {
            type: String,
            required: true,
        },
        itemsKey: {
            type: String,
            required: true,
        },
        method: {
            type: String,
            default: "GET",
        },
        headers: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        rowProps: {
            type: Function,
            default: null,
        },
        autoLoad: {
            type: Boolean,
            default: true,
        },
        perPage: {
            type: Number,
            default: 50,
        },
        showSelect: Boolean,
        itemValue: String,
        prepareItem: {
            type: Function,
            default: null,
        },
    });

    const emit = defineEmits([
        "loaded",
    ]);

    const tableRef = ref(null);
    const sentinelRef = ref(null);

    const loading = ref(false);
    const requestCount = ref(0);
    const errorCount = ref(0);

    const items = ref([]);

    const pagination = reactive({
        page: 1,
        perPage: props.perPage,
        total: 0,
    });

    const defaultColumnFilters = computed(() => {
        const defaults = {};

        for(const { key, field } of props.headers) {
            const value = field?.value;

            if( !value ) continue;

            if( typeof value === "object" ) {
                for(const [suffix, fieldValue] of Object.entries(value)) {
                    if( fieldValue ) defaults[`${key}${suffix}`] = fieldValue;
                }
            } else {
                defaults[key] = value;
            }
        }

        return defaults;
    });

    const columnFilters = reactive({ ...defaultColumnFilters.value });
    const columnFilterDraft = reactive({});

    const httpMethod = computed(() => props.method.toLowerCase());

    const mappedHeaders = computed(() => {
        if( props.headers.length > 0 ) return props.headers.map(({ field, ...header }) => ({
            ...header,
            sortable: false,
        }));

        const [ firstItem ] = items.value || [];

        return Object.keys(firstItem || {}).map(key => ({
            title: key,
            key,
            sortable: false,
        }));
    });

    const filterableHeaders = computed(() => props.headers.filter(header => header.field));

    const columnCount = computed(() => {
        return mappedHeaders.value.length + (props.showSelect ? 1 : 0);
    });

    const emptyListText = computed(() => {
        if( loading.value || items.value.length > 0 ) return " ";

        if( errorCount.value > 0 ) return "Erro ao buscar itens";
        if( !props.autoLoad && requestCount.value == 0 ) return "Informe os filtros e busque";

        if( requestCount.value > 0 ) {
            const hasFilters = (
                Object.keys(columnFilters).length > 0 ||
                Object.keys(props.filters).length > 0
            );

            return hasFilters ? "Nenhum resultado encontrado" : "Não há registros para exibir";
        }

        return "Não há registros para exibir";
    });

    function getFilterFields(key, field) {
        if( field?.type === "date" ) return [
            { key: `${key}Ini`, label: "Início" },
            { key: `${key}Fim`, label: "Fim" },
        ];

        return [{ key }];
    }

    function isColumnFiltered(header) {
        return getFilterFields(header.key, header.field).some(field => columnFilters[field.key]);
    }

    function initColumnFilterDraft(header) {
        for(const { key } of getFilterFields(header.key, header.field)) {
            columnFilterDraft[key] = columnFilters[key] ?? "";
        }
    }

    async function applyColumnFilter(header, clear = false) {
        for(const { key } of getFilterFields(header.key, header.field)) {
            if( clear ) {
                delete columnFilterDraft[key];
                delete columnFilters[key];
                continue;
            }

            const value = columnFilterDraft[key]?.trim();

            if( value ) {
                columnFilters[key] = value;
            } else {
                delete columnFilters[key];
            }
        }

        await fetchItems(1);
    }

    const canLoadMoreItems = computed(() => {
        if( loading.value ) return false;
        if( errorCount.value > 0 ) return false;
        if( requestCount.value == 0 ) return true;
        return items.value.length < pagination.total;
    });

    let observer = null;
    let abortController = null;
    let columnWidths = [];

    function extractItems(data) {
        return data?.[props.itemsKey] || [];
    }

    async function waitForTableLayout() {
        await nextTick();
        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    }

    async function syncColumnWidths(measure) {
        await waitForTableLayout();

        if( !items.value.length ) {
            if( measure ) columnWidths = [];
            return;
        }

        const table = tableRef.value?.$el?.querySelector("table");

        if( !table ) return;

        const ths = [ ...table.querySelectorAll("thead th") ];

        if( !ths.length ) return;

        if( measure ) {
            ths.forEach(th => th.style.width = "");
            await waitForTableLayout();
            columnWidths = ths.map(th => `${th.offsetWidth}px`);
        }

        if( !columnWidths.length ) return;

        ths.forEach((th, i) => {
            if( columnWidths[i] ) th.style.width = columnWidths[i];
        });
    }

    async function fetchItems(page = 1) {
        abortController?.abort();
        abortController = new AbortController();

        loading.value = true;

        if( page === 1 ) {
            items.value = [];
        }

        try {
            const baseParams = {
                ...props.filters,
                ...columnFilters,
                page,
                per_page: pagination.perPage,
            };

            const requestConfig = httpMethod.value === "get" ? {
                signal: abortController.signal,
                params: baseParams,
            } : {
                ...baseParams,
                signal: abortController.signal,
            };

            const { data } = await api[httpMethod.value](props.href, requestConfig);

            requestCount.value++;

            const newItems = mapItems(extractItems(data));

            if( page === 1 ) {
                items.value = newItems;
            } else {
                items.value.push(...newItems);
            }

            pagination.page = data?.current_page || page;
            pagination.perPage = data?.per_page || pagination.perPage;
            pagination.total = data?.total || 0;

            loading.value = false;
            errorCount.value = 0;

            emit("loaded", data);

            await syncColumnWidths(page === 1);
        } catch(e) {
            if( e?.code === "ERR_CANCELED" ) return;

            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar itens");

            loading.value = false;
            errorCount.value++;
        }
    }

    watch(() => props.filters, async () => {
        await fetchItems(1);
    }, {
        deep: true,
    });

    watch(() => props.href, async () => {
        await fetchItems(1);
    });

    onMounted(() => {
        const wrapper = tableRef.value?.$el?.querySelector(".v-table__wrapper");

        observer = new IntersectionObserver(async ([ entry ]) => {
            if( !entry.isIntersecting || !canLoadMoreItems.value ) return;

            const nearBottom = (wrapper.scrollHeight - wrapper.scrollTop - wrapper.clientHeight) < 100;

            if( !nearBottom ) return;

            if( items.value.length == 0 ) {
                if( props.autoLoad ) {
                    await fetchItems(1);
                }
            } else {
                await fetchItems(pagination.page + 1);
            }
        });

        observer.observe(sentinelRef.value);
    });

    onBeforeUnmount(() => {
        abortController?.abort();
        observer?.disconnect();

        abortController = null;
        observer = null;
    });

    function mapItems(list) {
        if( !props.prepareItem ) return list;
        return list.map(props.prepareItem);
    }

    defineExpose({
        fetchItems,
    });
</script>

<style scoped>
    .virtual-remote-table {
        display: flex;
        flex-direction: column;
        flex: 1 1 0;
        min-height: 0;
        overflow: hidden;
    }

    .virtual-remote-table__data {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    .virtual-remote-table__data :deep(.v-table) {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
    }

    .virtual-remote-table__data :deep(.v-table__wrapper) {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .virtual-remote-table__data tr.sentinel,
    .virtual-remote-table__data tr.sentinel td {
        height: 1px;
        margin: 0px;
    }
</style>
