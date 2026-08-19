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

            <v-col cols="12" lg="6" xl="4">
                <v-text-field
                    label="Preço de Venda"
                    prefix="R$"
                    type="number"
                    min="0"
                    step="0.01"
                    :error-messages="errors.sale_price"
                    v-model="form.sale_price"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="4">
                <v-text-field
                    label="Preço de Custo"
                    prefix="R$"
                    type="number"
                    min="0"
                    step="0.01"
                    :error-messages="errors.cost_price"
                    v-model="form.cost_price"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="4">
                <v-text-field
                    label="Quantidade em Estoque"
                    type="number"
                    min="0"
                    step="0.001"
                    :error-messages="errors.stock_quantity"
                    v-model="form.stock_quantity"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-autocomplete
                    label="Unidade de Medida"
                    item-title="name"
                    item-value="id"
                    clearable
                    :loading="loading.unitsOfMeasure"
                    :items="unitsOfMeasure"
                    :return-object="false"
                    :error-messages="errors.unit_of_measure_id"
                    v-model="form.unit_of_measure_id"
                >
                    <template #append-inner>
                        <v-btn
                            size="small"
                            icon="mdi-playlist-edit"
                            variant="tonal"
                            color="primary"
                            class="rounded"
                            @click.prevent="openUnitOfMeasureManager"
                        ></v-btn>
                    </template>
                </v-autocomplete>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Número de Série"
                    hint="Ex.: IMEI do celular"
                    persistent-hint
                    :error-messages="errors.serial_number"
                    v-model="form.serial_number"
                ></v-text-field>
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
                v-if="isEditing && canManageCompany(form.company_id)"
                color="error"
                variant="tonal"
                prepend-icon="mdi-delete"
                :loading="loading.delete"
                text="Remover"
                @click="isConfirmRemoveDialogOpen = true"
            ></v-btn>

            <v-spacer></v-spacer>

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

    <Dialog
        overlay-id="sgc-product-confirm-remove"
        title="Excluir Produto"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O produto <strong class="text-primary">{{ form.name ?? "" }}</strong>
        será removido desta empresa. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                :loading="loading.delete"
                text="Excluir"
                @click="handleDelete"
            ></v-btn>
        </template>
    </Dialog>

    <BrandManager
        ref="brandManagerRef"
        @update="getBrands"
    />

    <ProductCategoryManager
        ref="categoryManagerRef"
        @update="getCategories"
    />

    <UnitOfMeasureManager
        ref="unitOfMeasureManagerRef"
        @update="getUnitsOfMeasure"
    />
</template>

<script setup>
    import { ref, computed, nextTick, reactive } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";
    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import BrandManager from "@/Components/Tools/Sgc/BrandManager.vue";
    import ProductCategoryManager from "@/Components/Tools/Sgc/ProductCategoryManager.vue";
    import UnitOfMeasureManager from "@/Components/Tools/Sgc/UnitOfMeasureManager.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { normalizeMoneyInput } from "@/Utils/Money";
    import { useSgcAuth } from "@/Composables/useSgcAuth";

    const props = defineProps({
        defaultCompanyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const emit = defineEmits([
        "close",
    ]);

    const { canManageCompany } = useSgcAuth();

    const isDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = reactive({
        save: false,
        delete: false,
        brands: false,
        categories: false,
        unitsOfMeasure: false,
    });
    const errors = ref({});

    const product = ref({});
    const brands = ref([]);
    const categories = ref([]);
    const unitsOfMeasure = ref([]);
    const form = ref({});

    const brandManagerRef = ref(null);
    const categoryManagerRef = ref(null);
    const unitOfMeasureManagerRef = ref(null);

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

    async function getUnitsOfMeasure() {
        loading.unitsOfMeasure = true;

        try {
            const url = route("api.tools.sgc.units-of-measure.index");

            const { data } = await api.get(url, {
                params: {
                    per_page: 100,
                },
            });

            unitsOfMeasure.value = data.units_of_measure;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar unidades de medida");
        } finally {
            loading.unitsOfMeasure = false;
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
            sale_price: normalizeMoneyInput(form.value.sale_price),
            cost_price: normalizeMoneyInput(form.value.cost_price),
            stock_quantity: normalizeStockQuantity(form.value.stock_quantity),
            unit_of_measure_id: form.value.unit_of_measure_id || null,
            serial_number: normalizeSerialNumber(form.value.serial_number),
            currency: "BRL",
        };
    }

    function normalizeStockQuantity(value) {
        if( value === null || value === "" ) {
            return 0;
        }

        return Number(value);
    }

    function normalizeSerialNumber(value) {
        if( value === null || value === "" ) {
            return null;
        }

        const serial = String(value).trim();

        return serial === "" ? null : serial;
    }

    function resolveCompanyId(product, fallback = null) {
        const id = product?.companies?.[0]?.id ?? fallback;

        return id != null ? Number(id) : null;
    }

    function resolvePivotField(product, field, fallback = null) {
        return product?.companies?.[0]?.pivot?.[field] ?? fallback;
    }

    function resolveNotes(product, fallback = null) {
        return resolvePivotField(product, "notes", fallback);
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
                sale_price: resolvePivotField(data.product, "sale_price"),
                cost_price: resolvePivotField(data.product, "cost_price"),
                stock_quantity: resolvePivotField(data.product, "stock_quantity"),
                unit_of_measure_id: resolvePivotField(data.product, "unit_of_measure_id"),
                serial_number: resolvePivotField(data.product, "serial_number"),
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

    async function handleDelete() {
        loading.delete = true;

        try {
            const url = route("api.tools.sgc.products.destroy", product.value.id);

            await api.delete(url, {
                params: {
                    company_id: form.value.company_id,
                },
            });

            toastSuccess("Ok", "Produto removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover produto");
        } finally {
            loading.delete = false;
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

    function openUnitOfMeasureManager() {
        unitOfMeasureManagerRef.value?.openList();
    }

    async function open(data = {}) {
        if( data.id ) {
            isDialogOpen.value = true;

            await Promise.all([
                getBrands(),
                getCategories(),
                getUnitsOfMeasure(),
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

            const initialSalePrice = data.pivot?.sale_price ?? data.sale_price;

            if( initialSalePrice != null ) {
                form.value.sale_price = initialSalePrice;
            }

            const initialCostPrice = data.pivot?.cost_price ?? data.cost_price;

            if( initialCostPrice != null ) {
                form.value.cost_price = initialCostPrice;
            }

            const initialStockQuantity = data.pivot?.stock_quantity ?? data.stock_quantity;

            if( initialStockQuantity != null ) {
                form.value.stock_quantity = initialStockQuantity;
            }

            const initialUnitOfMeasureId = data.pivot?.unit_of_measure_id ?? data.unit_of_measure_id;

            if( initialUnitOfMeasureId != null ) {
                form.value.unit_of_measure_id = initialUnitOfMeasureId;
            }

            const initialSerialNumber = data.pivot?.serial_number ?? data.serial_number;

            if( initialSerialNumber != null ) {
                form.value.serial_number = initialSerialNumber;
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
            sale_price: data.pivot?.sale_price ?? data.sale_price ?? null,
            cost_price: data.pivot?.cost_price ?? data.cost_price ?? null,
            stock_quantity: data.pivot?.stock_quantity ?? data.stock_quantity ?? 0,
            unit_of_measure_id: data.pivot?.unit_of_measure_id ?? data.unit_of_measure_id ?? null,
            serial_number: data.pivot?.serial_number ?? data.serial_number ?? null,
        };

        isDialogOpen.value = true;

        await nextTick();

        await Promise.all([
            getBrands(),
            getCategories(),
            getUnitsOfMeasure(),
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
            sale_price: null,
            cost_price: null,
            stock_quantity: 0,
            unit_of_measure_id: null,
            serial_number: null,
        };
    }

    function handleClose() {
        isConfirmRemoveDialogOpen.value = false;

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
