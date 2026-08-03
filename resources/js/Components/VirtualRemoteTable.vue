<template>
    <v-card class="virtual-remote-table">
        <v-data-table-virtual
            ref="tableRef"
            class="virtual-remote-table__data text-no-wrap"
            hover
            fixed-header
            :loading="loading"
            :items="itens"
            :headers="cabecalhosMapeados"
            :show-select="showSelect"
            :item-value="itemValue"
            :row-props="rowProps"
            :no-data-text="fraseListaVazia"
            v-model="selected"
        >
            <template
                v-for="cf in cabecalhosFiltraveis"
                :key="cf.key"
                #[`header.${cf.key}`]="{ column }"
            >
                <div class="d-flex align-center ga-4">
                    <span>{{ cf.title }}</span>

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
                                :color="colunaFiltrada(cf) ? 'primary' : undefined"
                                :icon="colunaFiltrada(cf) ? 'mdi-filter' : 'mdi-filter-outline'"
                                @click="iniciarRascunho(cf)"
                            ></v-btn>
                        </template>

                        <v-card border min-width="280">
                            <v-card-text class="d-flex flex-column ga-2">
                                <v-text-field
                                    v-for="(campo, i) in camposFiltro(cf.key, cf.field)"
                                    :key="campo.key"
                                    :autofocus="i === 0"
                                    :label="campo.label"
                                    :placeholder="cf.title"
                                    :type="cf.field.type"
                                    v-model="rascunhoFiltrosColuna[campo.key]"
                                    @keyup.enter="filtrarColuna(cf)"
                                ></v-text-field>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions>
                                <v-spacer></v-spacer>

                                <v-btn
                                    color="primary"
                                    variant="tonal"
                                    @click="filtrarColuna(cf)"
                                >Filtrar</v-btn>

                                <v-btn
                                    variant="text"
                                    @click="filtrarColuna(cf, true)"
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
                    class="sentinel bg-primary"
                >
                    <td :colspan="numColunas"></td>
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
                {{ formatarNumero(itens.length, 0) }} de {{ formatarNumero(paginacao.TotalItens, 0) }} linha(s)
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
    import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from "vue";

    import api from "@/api";

    import { alertError } from "@/utils/Alert";
    import { formatarNumero } from "@/utils/Common";

    const selected = defineModel({
        default: () => [],
    });

    const props = defineProps({
        href: {
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
        showSelect: Boolean,
        itemValue: String,
        prepareItem: {
            type: Function,
            default: null,
        },
    });

    const tableRef = ref(null);
    const sentinelRef = ref(null);

    const loading = ref(false);
    const numConsultas = ref(0);
    const numErros = ref(0);

    const itens = ref([]);

    const paginacao = reactive({
        ItensPorPagina: 200,
        PaginaAtual: 1,
        TotalItens: 0,
    });

    const filtrosColunaPadrao = computed(() => {
        const padrao = {};

        for(const { key, field } of props.headers) {
            const valor = field?.value;

            if( !valor ) continue;

            if( typeof valor === "object" ) {
                for(const [s, v] of Object.entries(valor)) {
                    if( v ) padrao[`${key}${s}`] = v;
                }
            } else {
                padrao[key] = valor;
            }
        }

        return padrao;
    });

    const filtrosColuna = reactive({ ...filtrosColunaPadrao.value });
    const rascunhoFiltrosColuna = reactive({});

    const metodoHttp = computed(() => props.method.toLowerCase());

    const cabecalhosMapeados = computed(() => {
        if( props.headers.length > 0 ) return props.headers.map(({ field, ...h }) => ({
            ...h,
            sortable: false,
        }));

        const [ primeiroItem ] = itens.value || [];

        return Object.keys(primeiroItem || {}).map(key => ({
            title: key,
            key,
            sortable: false,
        }));
    });

    const cabecalhosFiltraveis = computed(() => props.headers.filter(h => h.field));

    const numColunas = computed(() => {
        return cabecalhosMapeados.value.length + (props.showSelect ? 1 : 0);
    });

    const fraseListaVazia = computed(() => {
        if( loading.value || itens.value.length > 0 ) return " ";

        if( numErros.value > 0 ) return "Erro ao buscar itens";
        if( !props.autoLoad && numConsultas.value == 0 ) return "Informe os filtros e busque";

        if( numConsultas.value > 0 ) {
            const temFiltros = (
                Object.keys(filtrosColuna).length > 0 ||
                Object.keys(props.filters).length > 0
            );

            return temFiltros ? "Nenhum resultado encontrado" : "Não há registros para exibir";
        }

        return "Não há registros para exibir";
    });

    function camposFiltro(key, field) {
        if( field?.type === "date" ) return [
            { key: `${key}Ini`, label: "Início" },
            { key: `${key}Fim`, label: "Fim" },
        ];

        return [{ key }];
    }

    function colunaFiltrada(cf) {
        return camposFiltro(cf.key, cf.field).some(c => filtrosColuna[c.key]);
    }

    function iniciarRascunho(cf) {
        for(const { key } of camposFiltro(cf.key, cf.field)) {
            rascunhoFiltrosColuna[key] = filtrosColuna[key] ?? "";
        }
    }

    async function filtrarColuna(cf, limpar = false) {
        for(const { key } of camposFiltro(cf.key, cf.field)) {
            if( limpar ) {
                delete rascunhoFiltrosColuna[key];
                delete filtrosColuna[key];
                continue;
            }

            const valor = rascunhoFiltrosColuna[key]?.trim();

            if( valor ) {
                filtrosColuna[key] = valor;
            } else {
                delete filtrosColuna[key];
            }
        }

        await buscarItens(1);
    }

    const podeCarregarMaisItens = computed(() => {
        if( loading.value ) return false;
        if( numErros.value > 0 ) return false;
        if( numConsultas.value == 0 ) return true;
        return itens.value.length < paginacao.TotalItens;
    });

    let observer = null;
    let abortController = null;

    async function buscarItens(PaginaAtual = 1) {
        abortController?.abort();
        abortController = new AbortController();

        loading.value = true;

        if( PaginaAtual === 1 ) {
            itens.value = [];
        }

        try {
            const baseParams = {
                ...props.filters,
                ...filtrosColuna,
                ...paginacao,
                PaginaAtual,
            };

            const filters = metodoHttp.value === "get" ? {
                signal: abortController.signal,
                params: baseParams,
            } : {
                ...baseParams,
                signal: abortController.signal,
            };

            const { data } = await api[metodoHttp.value](props.href, filters);

            numConsultas.value++;

            if( PaginaAtual === 1 ) {
                itens.value = mapearItens(data?.Itens || []);
            } else {
                itens.value.push(...mapearItens(data?.Itens || []));
            }

            paginacao.ItensPorPagina = data?.ItensPorPagina || 300;
            paginacao.PaginaAtual = data?.PaginaAtual || 1;
            paginacao.TotalItens = data?.TotalItens || 0;

            loading.value = false;
            numErros.value = 0;
        } catch(e) {
            if( e?.code === "ERR_CANCELED" ) return;

            const { Message } = e?.response?.data || {};
            alertError("Ooops", Message || "Erro ao buscar itens");

            loading.value = false;
            numErros.value++;
        }
    }

    watch(() => props.filters, async () => {
        await buscarItens(1);
    }, {
        deep: true,
    });

    onMounted(() => {
        const wrapper = tableRef.value?.$el?.querySelector(".v-table__wrapper");

        observer = new IntersectionObserver(async ([ entry ]) => {
            if( !entry.isIntersecting || !podeCarregarMaisItens.value ) return;

            const pertoFim = (wrapper.scrollHeight - wrapper.scrollTop - wrapper.clientHeight) < 100;

            if( !pertoFim ) return;

            if( itens.value.length == 0 ) {
                if( props.autoLoad ) {
                    await buscarItens(1);
                }
            } else {
                await buscarItens(paginacao.PaginaAtual + 1);
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

    function mapearItens(lista) {
        if( !props.prepareItem ) return lista;
        return lista.map(props.prepareItem);
    }

    defineExpose({
        buscarItens,
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
