<template>
    <BottomSheet
        overlay-id="sgc-product-form"
        :title="isEditing ? 'Editar Produto' : 'Novo Produto'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6">
                <CompanyAutocomplete
                    :disabled="isEditing"
                    v-model="form.company_id"
                ></CompanyAutocomplete>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-autocomplete
                    label="Marca"
                    item-title="name"
                    item-value="id"
                    clearable
                    :loading="loading.brands"
                    :items="brands"
                    :return-object="false"
                    :error-messages="errors.brand_id"
                    v-model="form.brand_id"
                >
                    <template #append-inner>
                        <v-btn
                            size="small"
                            icon="mdi-playlist-edit"
                            variant="tonal"
                            color="primary"
                            class="rounded"
                            @click.prevent="openBrandManager"
                        ></v-btn>
                    </template>
                </v-autocomplete>
            </v-col>

            <v-col cols="12" lg="6">
                <v-autocomplete
                    label="Categoria"
                    item-title="name"
                    item-value="id"
                    clearable
                    :loading="loading.categories"
                    :items="categories"
                    :return-object="false"
                    :error-messages="errors.product_category_id"
                    v-model="form.product_category_id"
                >
                    <template #append-inner>
                        <v-btn
                            size="small"
                            icon="mdi-playlist-edit"
                            variant="tonal"
                            color="primary"
                            class="rounded"
                            @click.prevent="openCategoryManager"
                        ></v-btn>
                    </template>
                </v-autocomplete>
            </v-col>

            <v-col cols="12">
                <v-textarea
                    label="Especificações Técnicas"
                    rows="2"
                    auto-grow
                    :error-messages="errors.technical_specifications"
                    v-model="form.technical_specifications"
                ></v-textarea>
            </v-col>

            <v-col cols="12">
                <v-textarea
                    label="Observações"
                    rows="2"
                    auto-grow
                    :error-messages="errors.notes"
                    v-model="form.notes"
                ></v-textarea>
            </v-col>
        </v-row>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="loading.save"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>

    <BrandManager
        ref="brandManagerRef"
        @update="getBrands"
    />

    <ProductCategoryManager
        ref="categoryManagerRef"
        @update="getCategories"
    />
</template>

<script setup>
    import { ref, computed, nextTick, reactive } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import BrandManager from "@/Components/Tools/Sgc/BrandManager.vue";
    import ProductCategoryManager from "@/Components/Tools/Sgc/ProductCategoryManager.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const props = defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);
    const loading = reactive({
        save: false,
        brands: false,
        categories: false,
    });
    const errors = ref({});

    const product = ref({});
    const brands = ref([]);
    const categories = ref([]);
    const form = ref({});

    const brandManagerRef = ref(null);
    const categoryManagerRef = ref(null);

    const isEditing = computed(() => !!product.value.id);

    async function getBrands() {
        loading.brands = true;

        try {
            const url = route("api.tools.sgc.brands.index");
            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            brands.value = data.brands;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar marcas");
        } finally {
            loading.brands = false;
        }
    }

    async function getCategories() {
        loading.categories = true;

        try {
            const url = route("api.tools.sgc.product-categories.index");

            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            categories.value = data.product_categories;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar categorias");
        } finally {
            loading.categories = false;
        }
    }

    function buildPayload() {
        return {
            company_id: form.value.company_id,
            brand_id: form.value.brand_id || null,
            product_category_id: form.value.product_category_id || null,
            name: form.value.name,
            technical_specifications: form.value.technical_specifications,
            notes: form.value.notes,
        };
    }

    function resolveCompanyId(product, fallback = null) {
        const id = product?.companies?.[0]?.id ?? fallback;

        return id != null ? Number(id) : null;
    }

    function resolveNotes(product, fallback = null) {
        return product?.companies?.[0]?.pivot?.notes ?? fallback;
    }

    async function loadProduct(id) {
        loading.save = true;

        try {
            const url = route("api.tools.sgc.products.show", id);
            const { data } = await api.get(url);

            product.value = data.product;

            form.value = {
                company_id: resolveCompanyId(data.product, props.defaultCompanyId),
                brand_id: data.product.brand_id,
                product_category_id: data.product.product_category_id,
                name: data.product.name,
                technical_specifications: data.product.technical_specifications,
                notes: resolveNotes(data.product),
            };
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar produto");
        } finally {
            loading.save = false;
        }
    }

    async function updateProduct() {
        errors.value = {};
        loading.save = true;

        try {
            const url = route("api.tools.sgc.products.update", product.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Ok", "Produto atualizado com sucesso");

            product.value = data.product;
            form.value = {
                ...form.value,
                ...data.product,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar produto");
        } finally {
            loading.save = false;
        }
    }

    async function createProduct() {
        errors.value = {};
        loading.save = true;

        try {
            const url = route("api.tools.sgc.products.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Produto criado com sucesso");

            product.value = data.product;

            form.value = {
                ...form.value,
                ...data.product,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar produto");
        } finally {
            loading.save = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateProduct();
        } else {
            await createProduct();
        }
    }

    function openBrandManager() {
        brandManagerRef.value?.openList();
    }

    function openCategoryManager() {
        categoryManagerRef.value?.openList();
    }

    async function open(data = {}) {
        if( data.id ) {
            isDialogOpen.value = true;

            await Promise.all([
                getBrands(),
                getCategories(),
                loadProduct(data.id),
            ]);

            const companyId = data.company_id ?? props.defaultCompanyId;

            if( companyId != null ) {
                form.value.company_id = companyId;
            }

            const initialNotes = data.pivot?.notes ?? data.notes;

            if( initialNotes != null ) {
                form.value.notes = initialNotes;
            }

            return;
        }

        product.value = data;

        form.value = {
            company_id: data.company_id ?? props.defaultCompanyId,
            brand_id: data.brand_id ?? null,
            product_category_id: data.product_category_id ?? null,
            name: data.name ?? null,
            technical_specifications: data.technical_specifications ?? null,
            notes: data.notes ?? null,
        };

        isDialogOpen.value = true;

        await nextTick();

        await Promise.all([
            getBrands(),
            getCategories(),
        ]);
    }

    function reset() {
        errors.value = {};
        product.value = {};

        form.value = {
            company_id: props.defaultCompanyId,
            brand_id: null,
            product_category_id: null,
            name: null,
            technical_specifications: null,
            notes: null,
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    defineExpose({
        open,
    });
</script>
