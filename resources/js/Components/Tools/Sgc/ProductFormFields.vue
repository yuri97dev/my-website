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
                <ProductCombobox
                    v-if="!isEditing"
                    :error-messages="errors.name || errors.product_id"
                    v-model="nameInput"
                ></ProductCombobox>

                <v-text-field
                    v-else
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
                    :disabled="hasSerialNumber"
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
                @click="openDeleteDialog"
            ></v-btn>

            <v-spacer></v-spacer>

            <v-btn
                v-if="isEditing"
                variant="tonal"
                prepend-icon="mdi-history"
                text="Movimentações"
                @click="openStockMovements"
            ></v-btn>

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

        <v-textarea
            class="mt-4"
            label="Motivo da exclusão"
            rows="2"
            auto-grow
            :error-messages="errors.stock_movement_reason"
            v-model="deleteReason"
        ></v-textarea>

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

    <Dialog
        overlay-id="sgc-product-stock-reason"
        title="Alteração de estoque"
        max-width="500"
        v-model="isStockReasonDialogOpen"
    >
        A quantidade em estoque será alterada de
        <strong class="text-primary">{{ originalStockQuantity }}</strong>
        para
        <strong class="text-primary">{{ normalizeStockQuantity(form.stock_quantity) }}</strong>.
        Informe o motivo dessa alteração.

        <v-textarea
            class="mt-4"
            label="Motivo da alteração"
            rows="2"
            auto-grow
            :error-messages="errors.stock_movement_reason"
            v-model="stockMovementReason"
        ></v-textarea>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="loading.save"
                text="Confirmar e salvar"
                @click="confirmStockReasonAndSave"
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

    <ProductStockMovementList
        ref="stockMovementListRef"
    />
</template>

<script setup>
    import { ref, computed, nextTick, reactive, watch } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";
    import CompanyAutocomplete from "@/Components/Inputs/CompanyAutocomplete.vue";
    import ProductCombobox from "@/Components/Inputs/ProductCombobox.vue";
    import BrandManager from "@/Components/Tools/Sgc/BrandManager.vue";
    import ProductCategoryManager from "@/Components/Tools/Sgc/ProductCategoryManager.vue";
    import ProductStockMovementList from "@/Components/Tools/Sgc/ProductStockMovementList.vue";
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
    const isStockReasonDialogOpen = ref(false);
    const originalStockQuantity = ref(0);
    const stockMovementReason = ref("");
    const deleteReason = ref("");
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
    const nameInput = ref(null);
    const form = ref({});

    const brandManagerRef = ref(null);
    const categoryManagerRef = ref(null);
    const unitOfMeasureManagerRef = ref(null);
    const stockMovementListRef = ref(null);

    const isEditing = computed(() => !!product.value.id);

    const hasSerialNumber = computed(() => {
        const serial = form.value.serial_number;

        return serial != null && String(serial).trim() !== "";
    });

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
            company_product_id: form.value.company_product_id || null,
            product_id: isEditing.value ? null : (form.value.product_id || null),
            brand_id: form.value.brand_id || null,
            product_category_id: form.value.product_category_id || null,
            name: typeof nameInput.value === "object" && nameInput.value?.name
                ? nameInput.value.name
                : form.value.name,
            technical_specifications: form.value.technical_specifications,
            notes: form.value.notes,
            sale_price: normalizeMoneyInput(form.value.sale_price),
            cost_price: normalizeMoneyInput(form.value.cost_price),
            stock_quantity: normalizeStockQuantity(form.value.stock_quantity),
            unit_of_measure_id: form.value.unit_of_measure_id || null,
            serial_number: normalizeSerialNumber(form.value.serial_number),
            currency: "BRL",
            ...(isEditing.value && hasStockQuantityChanged()
                ? { stock_movement_reason: stockMovementReason.value || null }
                : {}),
        };
    }

    function normalizeStockQuantity(value) {
        if( value === null || value === "" ) {
            return 0;
        }

        return Number(value);
    }

    function hasStockQuantityChanged() {
        return Number(normalizeStockQuantity(form.value.stock_quantity)).toFixed(3)
            !== Number(originalStockQuantity.value).toFixed(3);
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
                company_product_id: data.product.companies?.[0]?.pivot?.id ?? null,
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

            originalStockQuantity.value = normalizeStockQuantity(form.value.stock_quantity);
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
            const payload = buildPayload();
            const url = route("api.tools.sgc.products.update", product.value.id);
            const { data } = await api.put(url, payload);

            toastSuccess("Ok", "Produto atualizado com sucesso");

            product.value = data.product;
            form.value = {
                ...form.value,
                ...data.product,
                stock_quantity: payload.stock_quantity,
            };

            originalStockQuantity.value = payload.stock_quantity;
            stockMovementReason.value = "";
            isStockReasonDialogOpen.value = false;
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
                    company_product_id: form.value.company_product_id || undefined,
                    stock_movement_reason: deleteReason.value || undefined,
                },
            });

            toastSuccess("Ok", "Produto removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao remover produto");
        } finally {
            loading.delete = false;
        }
    }

    async function handleSave() {
        if( isEditing.value && hasStockQuantityChanged() ) {
            errors.value = {
                ...errors.value,
                stock_movement_reason: undefined,
            };
            isStockReasonDialogOpen.value = true;
            return;
        }

        await persistProduct();
    }

    async function confirmStockReasonAndSave() {
        if( !String(stockMovementReason.value || "").trim() ) {
            errors.value = {
                ...errors.value,
                stock_movement_reason: "Informe o motivo da alteração de estoque.",
            };
            return;
        }

        await persistProduct();
    }

    async function persistProduct() {
        if( isEditing.value ) {
            await updateProduct();
        } else {
            await createProduct();
        }
    }

    function openDeleteDialog() {
        deleteReason.value = "";
        errors.value = {
            ...errors.value,
            stock_movement_reason: undefined,
        };
        isConfirmRemoveDialogOpen.value = true;
    }

    function openStockMovements() {
        stockMovementListRef.value?.open({
            productId: product.value.id,
            companyId: form.value.company_id,
            companyProductId: form.value.company_product_id,
        });
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

            const companyProductId = data.company_product_id ?? data.pivot?.id;

            if( companyProductId != null ) {
                form.value.company_product_id = companyProductId;
            }

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
                originalStockQuantity.value = normalizeStockQuantity(initialStockQuantity);
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
            company_product_id: null,
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
            product_id: null,
        };

        nameInput.value = data.name ?? null;

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
        originalStockQuantity.value = 0;
        stockMovementReason.value = "";
        deleteReason.value = "";
        isStockReasonDialogOpen.value = false;

        form.value = {
            company_id: props.defaultCompanyId,
            company_product_id: null,
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
            product_id: null,
        };

        nameInput.value = null;
    }

    function handleClose() {
        isConfirmRemoveDialogOpen.value = false;

        reset();

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    watch(nameInput, (value) => {
        if( isEditing.value ) {
            return;
        }

        if( typeof value === "object" && value?.id ) {
            applySelectedProduct(value);
            return;
        }

        form.value.product_id = null;
        form.value.name = typeof value === "string" ? value : null;
    });

    watch(hasSerialNumber, (hasSerial) => {
        if( hasSerial ) {
            form.value.stock_quantity = 1;
        }
    });

    function applySelectedProduct(selected) {
        form.value.product_id = selected.id;
        form.value.name = selected.name;
        form.value.brand_id = selected.brand_id ?? null;
        form.value.product_category_id = selected.product_category_id ?? null;
        form.value.technical_specifications = selected.technical_specifications ?? null;
        form.value.sale_price = selected.sale_price ?? selected.pivot?.sale_price ?? null;
        form.value.cost_price = selected.cost_price ?? selected.pivot?.cost_price ?? null;
        form.value.unit_of_measure_id = selected.unit_of_measure_id ?? selected.pivot?.unit_of_measure_id ?? null;
        form.value.notes = selected.notes ?? selected.pivot?.notes ?? null;
    }

    defineExpose({
        open,
    });
</script>
