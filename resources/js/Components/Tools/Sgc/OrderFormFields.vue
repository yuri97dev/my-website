<template>
    <BottomSheet
        overlay-id="sgc-order-form"
        height="80dvh"
        max-width="960"
        :title="isEditing ? `Pedido #${order.id}` : 'Novo Pedido'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-tabs
            grow
            align-tabs="center"
            v-model="currentStep"
        >
            <v-tab
                v-for="step in steps"
                :key="step.value"
                :value="step.value"
                :prepend-icon="step.icon"
                :disabled="!canAccessStep(step.value)"
                :text="step.title"
            ></v-tab>
        </v-tabs>

        <v-divider class="mb-4"></v-divider>

        <v-tabs-window v-model="currentStep">
            <v-tabs-window-item value="company">
                <OrderFormCompanyStep
                    ref="companyStepRef"
                    :is-editing="isEditing"
                    :errors="errors"
                    v-model:company-id="form.company_id"
                    v-model:customer-id="form.customer_id"
                ></OrderFormCompanyStep>
            </v-tabs-window-item>

            <v-tabs-window-item value="products">
                <OrderFormProductsStep
                    :company-id="form.company_id"
                    :cart="cart"
                    :cart-items="cartItems"
                    :errors="errors"
                    @set-quantity="setCartQuantity"
                ></OrderFormProductsStep>
            </v-tabs-window-item>

            <v-tabs-window-item value="payment">
                <OrderFormPaymentStep
                    :payment-methods="paymentMethods"
                    :loading="loadingPaymentMethods"
                    :subtotal="subtotal"
                    :order-total="orderTotal"
                    :errors="errors"
                    v-model:payment-method-id="form.payment_method_id"
                    v-model:installments-count="form.installments_count"
                    v-model:installments="installments"
                    v-model:order-discount-amount="form.discount_amount"
                    @installments-customized="installmentsCustomized = true"
                    @redistribute-installments="installmentsCustomized = false"
                ></OrderFormPaymentStep>
            </v-tabs-window-item>

            <v-tabs-window-item value="summary">
                <OrderFormSummaryStep
                    :company-name="summaryCompanyName"
                    :customer-name="summaryCustomerName"
                    :payment-name="summaryPaymentName"
                    :cart-items="cartItems"
                    :subtotal="subtotal"
                    :order-total="orderTotal"
                    :installments="installments"
                    :installments-count="form.installments_count"
                    :show-installments="showInstallmentSummary"
                    :errors="errors"
                    :order-discount-amount="form.discount_amount"
                    v-model:notes="form.notes"
                ></OrderFormSummaryStep>
            </v-tabs-window-item>
        </v-tabs-window>

        <template #actions>
            <div class="d-flex align-center w-100 ga-2">
                <div class="d-flex align-center ga-2">
                    <v-btn
                        v-if="isEditing && canManageCompany(form.company_id)"
                        color="error"
                        variant="tonal"
                        prepend-icon="mdi-delete"
                        :loading="deleting"
                        text="Remover"
                        @click="isConfirmRemoveDialogOpen = true"
                    ></v-btn>

                    <v-btn
                        v-if="currentStepIndex > 0"
                        variant="text"
                        prepend-icon="mdi-chevron-left"
                        text="Anterior"
                        @click="goToPreviousStep"
                    ></v-btn>
                </div>

                <div class="flex-grow-1"></div>

                <div class="d-flex align-center ga-2">
                    <v-btn
                        v-if="currentStep !== 'summary'"
                        color="primary"
                        variant="tonal"
                        append-icon="mdi-chevron-right"
                        text="Próximo"
                        :disabled="!canGoNext"
                        @click="goToNextStep"
                    ></v-btn>

                    <v-btn
                        v-if="currentStep === 'summary'"
                        color="primary"
                        prepend-icon="mdi-check"
                        :loading="loading"
                        :disabled="!canFinalize"
                        text="Finalizar"
                        @click="handleFinalize"
                    ></v-btn>
                </div>
            </div>
        </template>
    </BottomSheet>

    <Dialog
        overlay-id="sgc-order-confirm-remove"
        title="Excluir Pedido"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O pedido <strong class="text-primary">#{{ order.id ?? "" }}</strong>
        será removido. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                :loading="deleting"
                text="Excluir"
                @click="handleDelete"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed, watch } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";
    import OrderFormCompanyStep from "@/Components/Tools/Sgc/OrderForm/OrderFormCompanyStep.vue";
    import OrderFormPaymentStep from "@/Components/Tools/Sgc/OrderForm/OrderFormPaymentStep.vue";
    import OrderFormProductsStep from "@/Components/Tools/Sgc/OrderForm/OrderFormProductsStep.vue";
    import OrderFormSummaryStep from "@/Components/Tools/Sgc/OrderForm/OrderFormSummaryStep.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import {
        buildDefaultInstallments,
        installmentsTotalMatches,
    } from "@/Utils/Installments";
    import {
        orderTotalAfterDiscount,
        sumOrderLines,
        toMoney,
    } from "@/Utils/Money";
    import { useSgcAuth } from "@/Composables/useSgcAuth";

    const emit = defineEmits([
        "close",
    ]);

    const { canManageCompany } = useSgcAuth();

    const steps = [
        { value: "company", title: "Empresa", icon: "mdi-domain" },
        { value: "products", title: "Produtos", icon: "mdi-cart-outline" },
        { value: "payment", title: "Pagamento", icon: "mdi-credit-card-outline" },
        { value: "summary", title: "Resumo", icon: "mdi-clipboard-check-outline" },
    ];

    const isDialogOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = ref(false);
    const deleting = ref(false);
    const loadingPaymentMethods = ref(false);
    const errors = ref({});

    const currentStep = ref("company");
    const order = ref({});
    const form = ref({});
    const cart = ref({});
    const paymentMethods = ref([]);
    const installments = ref([]);
    const installmentsCustomized = ref(false);

    const companyStepRef = ref(null);

    const isEditing = computed(() => !!order.value.id);

    const currentStepIndex = computed(() => {
        return steps.findIndex(step => step.value === currentStep.value);
    });

    const selectedPaymentMethod = computed(() => {
        return paymentMethods.value.find(
            method => method.id === form.value.payment_method_id
        ) ?? null;
    });

    const supportsInstallments = computed(() => {
        return selectedPaymentMethod.value?.supports_installments === true;
    });

    const showInstallmentSummary = computed(() => {
        return supportsInstallments.value && form.value.installments_count > 1;
    });

    const hasInstallmentMismatch = computed(() => {
        if( !showInstallmentSummary.value ) {
            return false;
        }

        return !installmentsTotalMatches(installments.value, orderTotal.value);
    });

    const cartItems = computed(() => Object.values(cart.value));

    const subtotal = computed(() => {
        return toMoney(sumOrderLines(cartItems.value));
    });

    const orderTotal = computed(() => {
        return toMoney(orderTotalAfterDiscount(
            subtotal.value,
            form.value.discount_amount,
        ));
    });

    const summaryCompanyName = computed(() => {
        return companyStepRef.value?.getSelectedCompany?.()?.label
            || order.value.company?.trade_name
            || order.value.company?.legal_name
            || "—";
    });

    const summaryCustomerName = computed(() => {
        return companyStepRef.value?.getSelectedCustomer?.()?.name
            || order.value.customer?.name
            || "—";
    });

    const summaryPaymentName = computed(() => {
        return selectedPaymentMethod.value?.name
            || order.value.payment_method?.name
            || "—";
    });

    const canGoNext = computed(() => {
        if( currentStep.value === "company" ) {
            return !!form.value.company_id && !!form.value.customer_id;
        }

        if( currentStep.value === "products" ) {
            return cartItems.value.length > 0;
        }

        if( currentStep.value === "payment" ) {
            return !!form.value.payment_method_id && !hasInstallmentMismatch.value;
        }

        return true;
    });

    const canFinalize = computed(() => {
        return !hasInstallmentMismatch.value;
    });

    function canAccessStep(stepValue) {
        const stepIndex = steps.findIndex(step => step.value === stepValue);

        if( stepIndex <= currentStepIndex.value ) {
            return true;
        }

        if( stepValue === "products" ) {
            return !!form.value.company_id && !!form.value.customer_id;
        }

        if( stepValue === "payment" ) {
            return cartItems.value.length > 0;
        }

        if( stepValue === "summary" ) {
            return !!form.value.payment_method_id && !hasInstallmentMismatch.value;
        }

        return false;
    }

    function syncInstallmentsFromTotal() {
        if( !supportsInstallments.value || form.value.installments_count <= 1 ) {
            installments.value = [];
            return;
        }

        if( installmentsCustomized.value ) {
            return;
        }

        installments.value = buildDefaultInstallments(
            form.value.installments_count,
            orderTotal.value,
        );
    }

    function goToNextStep() {
        if( !canGoNext.value ) return;

        const nextStep = steps[currentStepIndex.value + 1];

        if( nextStep ) {
            if( nextStep.value === "payment" ) {
                installmentsCustomized.value = false;
                syncInstallmentsFromTotal();
            }

            currentStep.value = nextStep.value;
        }
    }

    function goToPreviousStep() {
        const previousStep = steps[currentStepIndex.value - 1];

        if( previousStep ) {
            currentStep.value = previousStep.value;
        }
    }

    function setCartQuantity({ product, productId, quantity }) {
        const id = productId ?? product?.id;

        if( !id ) return;

        const stockQuantity = Number(
            cart.value[id]?.stock_quantity ?? product?.stock_quantity ?? 0
        );
        const rawQty = Number(quantity);

        if( !Number.isFinite(rawQty) || rawQty <= 0 ) {
            delete cart.value[id];
            return;
        }

        const finalQty = Math.min(Math.floor(rawQty), stockQuantity);

        if( finalQty <= 0 ) {
            delete cart.value[id];
            return;
        }

        const item = cart.value[id];

        if( item ) {
            item.quantity = finalQty;
            return;
        }

        if( !product ) return;

        cart.value[id] = {
            product_id: id,
            name: product.name,
            quantity: finalQty,
            unit_price: toMoney(product.sale_price || 0),
            unit_of_measure_id: product.unit_of_measure_id ?? null,
            unit_of_measure: product.unit_of_measure ?? null,
            stock_quantity: stockQuantity,
            discount_amount: 0,
        };
    }

    function buildInstallmentsPayload() {
        if( !showInstallmentSummary.value ) {
            return undefined;
        }

        return installments.value.map(installment => ({
            installment_number: installment.installment_number,
            due_date: installment.due_date || null,
            amount: toMoney(installment.amount),
        }));
    }

    function buildItemsPayload() {
        return cartItems.value.map(item => ({
            product_id: item.product_id,
            unit_of_measure_id: item.unit_of_measure_id,
            quantity: item.quantity,
            unit_price: toMoney(item.unit_price),
            discount_amount: toMoney(item.discount_amount || 0),
        }));
    }

    function buildPayload() {
        return {
            company_id: form.value.company_id,
            customer_id: form.value.customer_id,
            payment_method_id: form.value.payment_method_id,
            installments_count: supportsInstallments.value
                ? form.value.installments_count
                : 1,
            installments: buildInstallmentsPayload(),
            status: "confirmed",
            discount_amount: toMoney(form.value.discount_amount || 0),
            currency: "BRL",
            notes: form.value.notes || null,
            items: buildItemsPayload(),
        };
    }

    function validateBeforeSubmit() {
        if( showInstallmentSummary.value
            && !installmentsTotalMatches(installments.value, orderTotal.value)
        ) {
            alertError(
                "Ooops",
                "A soma das parcelas deve ser igual ao total do pedido.",
            );

            return false;
        }

        return true;
    }

    async function loadPaymentMethods() {
        loadingPaymentMethods.value = true;

        try {
            const url = route("api.tools.sgc.payment-methods.index");
            const { data } = await api.get(url);

            paymentMethods.value = data.payment_methods || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar formas de pagamento");
        } finally {
            loadingPaymentMethods.value = false;
        }
    }

    async function loadOrder(id) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.orders.show", id);
            const { data } = await api.get(url);

            order.value = data.order;

            form.value = {
                company_id: data.order.company_id,
                customer_id: data.order.customer_id,
                payment_method_id: data.order.payment_method_id,
                installments_count: data.order.installments?.length || 1,
                discount_amount: toMoney(data.order.discount_amount || 0),
                notes: data.order.notes,
            };

            installments.value = (data.order.installments || []).map(installment => ({
                installment_number: installment.installment_number,
                due_date: installment.due_date?.slice?.(0, 10) ?? installment.due_date,
                amount: toMoney(installment.amount),
            }));

            installmentsCustomized.value = installments.value.length > 1;

            cart.value = {};

            for( const item of data.order.items || [] ) {
                cart.value[item.product_id] = {
                    product_id: item.product_id,
                    name: item.product?.name ?? `Produto #${item.product_id}`,
                    quantity: Number(item.quantity),
                    unit_price: toMoney(item.unit_price),
                    unit_of_measure_id: item.unit_of_measure_id,
                    unit_of_measure: item.unit_of_measure?.abbreviation ?? null,
                    stock_quantity: Number(item.quantity),
                    discount_amount: toMoney(item.discount_amount || 0),
                };
            }
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar pedido");
        } finally {
            loading.value = false;
        }
    }

    async function createOrder() {
        if( !validateBeforeSubmit() ) return;

        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.orders.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Pedido criado com sucesso");

            order.value = data.order;
            isDialogOpen.value = false;
            handleClose();
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar pedido");
        } finally {
            loading.value = false;
        }
    }

    async function updateOrder() {
        if( !validateBeforeSubmit() ) return;

        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.sgc.orders.update", order.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Ok", "Pedido atualizado com sucesso");

            order.value = data.order;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar pedido");
        } finally {
            loading.value = false;
        }
    }

    async function handleFinalize() {
        syncInstallmentsFromTotal();

        if( isEditing.value ) {
            await updateOrder();
        } else {
            await createOrder();
        }
    }

    async function handleDelete() {
        deleting.value = true;

        try {
            const url = route("api.tools.sgc.orders.destroy", order.value.id);

            await api.delete(url);

            toastSuccess("Ok", "Pedido removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover pedido");
        } finally {
            deleting.value = false;
        }
    }

    async function open(data = {}) {
        await loadPaymentMethods();

        currentStep.value = "company";

        if( data.id ) {
            isDialogOpen.value = true;
            await loadOrder(data.id);
            return;
        }

        reset();

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        order.value = {};
        cart.value = {};
        installments.value = [];
        installmentsCustomized.value = false;
        currentStep.value = "company";

        form.value = {
            company_id: null,
            customer_id: null,
            payment_method_id: null,
            installments_count: 1,
            discount_amount: 0,
            notes: null,
        };
    }

    function handleClose() {
        isConfirmRemoveDialogOpen.value = false;

        reset();
        emit("close");
    }

    watch(() => form.value.company_id, (companyId, previousId) => {
        if( previousId != null && companyId !== previousId ) {
            form.value.customer_id = null;
            cart.value = {};
            installments.value = [];
            installmentsCustomized.value = false;
        }
    });

    watch(cart, () => {
        installmentsCustomized.value = false;
        syncInstallmentsFromTotal();
    }, { deep: true });

    watch(supportsInstallments, (supports) => {
        if( !supports ) {
            form.value.installments_count = 1;
            installments.value = [];
            installmentsCustomized.value = false;
        }
    });

    watch(() => form.value.installments_count, (count, previousCount) => {
        if( count === previousCount ) return;

        installmentsCustomized.value = false;

        syncInstallmentsFromTotal();
    });

    watch(orderTotal, () => {
        if( !installmentsCustomized.value ) {
            syncInstallmentsFromTotal();
        }
    });

    reset();

    defineExpose({
        open,
    });
</script>
