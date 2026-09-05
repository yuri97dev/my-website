<template>
    <BottomSheet
        overlay-id="sgc-order-details"
        height="80dvh"
        max-width="960"
        :title="sheetTitle"
        :loading="loading"
        :hide-footer="!canCancel"
        :actions="sheetActions"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-alert
            v-if="isCancelled"
            type="error"
            variant="tonal"
            class="mb-4"
            text="Este pedido está cancelado. As baixas foram zeradas e não podem ser registradas novamente."
        ></v-alert>

        <v-list lines="two" class="mb-4">
            <v-list-item
                prepend-icon="mdi-domain"
                title="Empresa"
                :subtitle="companyName"
            ></v-list-item>

            <v-list-item
                prepend-icon="mdi-account-outline"
                title="Cliente"
                :subtitle="customerName"
            ></v-list-item>

            <v-list-item
                prepend-icon="mdi-credit-card-outline"
                title="Pagamento"
                :subtitle="paymentName"
            ></v-list-item>

            <v-list-item
                prepend-icon="mdi-account-edit-outline"
                title="Criado por"
                :subtitle="createdByName"
            ></v-list-item>
        </v-list>

        <div class="text-subtitle-1 font-weight-medium mb-2">
            Produtos
        </div>

        <v-list
            v-if="items.length > 0"
            lines="two"
            class="mb-4"
        >
            <v-list-item
                v-for="item in items"
                :key="item.id"
                prepend-icon="mdi-package-variant"
                :title="item.product?.name ?? `Produto #${item.product_id}`"
                :subtitle="itemSummary(item)"
            >
                <template #append>
                    <span>R$ {{ formatMoney(lineTotal(item)) }}</span>
                </template>
            </v-list-item>

            <v-divider class="my-2"></v-divider>

            <v-list-item
                v-if="orderDiscount > 0"
                prepend-icon="mdi-sale-outline"
                title="Desconto"
            >
                <template #append>
                    <span>- R$ {{ formatMoney(orderDiscount) }}</span>
                </template>
            </v-list-item>

            <v-list-item
                prepend-icon="mdi-receipt-text-outline"
                title="Total do pedido"
            >
                <template #append>
                    <span class="text-primary text-h6">
                        R$ {{ formatMoney(order.total) }}
                    </span>
                </template>
            </v-list-item>
        </v-list>

        <v-alert
            v-else
            type="info"
            variant="tonal"
            class="mb-4"
            text="Este pedido não possui produtos."
        ></v-alert>

        <v-textarea
            v-if="order.notes"
            class="mb-4"
            label="Observações"
            rows="2"
            auto-grow
            readonly
            prepend-inner-icon="mdi-note-text-outline"
            :model-value="order.notes"
        ></v-textarea>

        <v-table
            v-if="installments.length > 0"
            class="border rounded-lg"
        >
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                    <th>Pago</th>
                    <th>Pago Em</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="installment in installments"
                    :key="installment.id"
                >
                    <td>
                        <div class="d-flex align-center ga-2">
                            <span>{{ installment.installment_number }}ª</span>

                            <v-chip
                                size="small"
                                variant="tonal"
                                :color="installmentStatusColor(installment)"
                            >
                                {{ installmentStatusLabel(installment) }}
                            </v-chip>
                        </div>
                    </td>

                    <td>
                        {{ formatDueDate(installment.due_date) }}
                    </td>

                    <td>
                        R$ {{ formatMoney(installment.amount) }}
                    </td>

                    <td>
                        R$ {{ formatMoney(installmentPaidAmount(installment)) }}
                    </td>

                    <td>
                        <span
                            v-if="isInstallmentPaid(installment) && installment.paid_at"
                            class="text-caption text-medium-emphasis"
                        >
                            {{ formatDate(installment.paid_at, "dd/MM/yyyy HH:mm") }}
                        </span>
                    </td>

                    <td class="text-end">
                        <v-btn
                            v-if="canPayInstallment(installment)"
                            variant="tonal"
                            color="primary"
                            prepend-icon="mdi-cash-check"
                            text="Baixa"
                            @click="openPayDialog(installment)"
                        ></v-btn>
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr>
                    <th>Total</th>
                    <th></th>
                    <th>R$ {{ formatMoney(installmentsSum) }}</th>
                    <th>R$ {{ formatMoney(installmentsPaidSum) }}</th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </v-table>
    </BottomSheet>

    <Dialog
        overlay-id="sgc-order-confirm-cancel"
        title="Cancelar Pedido"
        max-width="500"
        v-model="isConfirmCancelDialogOpen"
    >
        O pedido <strong class="text-primary">#{{ order.id ?? "" }}</strong>
        será cancelado. As baixas das parcelas serão zeradas, como se o valor
        tivesse sido devolvido ao cliente.

        <v-textarea
            class="mt-4"
            label="Motivo do cancelamento"
            rows="2"
            auto-grow
            :error-messages="cancelErrors.stock_movement_reason"
            v-model="cancelReason"
        ></v-textarea>

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-cancel"
                :loading="cancelling"
                text="Cancelar pedido"
                @click="handleCancel"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-order-pay-installment"
        title="Dar baixa na parcela"
        max-width="500"
        v-model="isPayDialogOpen"
    >
        <v-alert
            v-if="selectedInstallment"
            type="info"
            variant="tonal"
            class="mb-4"
            :text="payDialogSummary"
        ></v-alert>

        <v-text-field
            label="Valor da baixa"
            prefix="R$"
            type="number"
            min="0.01"
            step="0.01"
            persistent-hint
            :hint="payAmountHint"
            :error-messages="payAmountError"
            v-model.number="payAmount"
        ></v-text-field>

        <template #actions>
            <v-btn
                color="primary"
                prepend-icon="mdi-cash-check"
                :loading="paying"
                :disabled="!canConfirmPay"
                text="Confirmar baixa"
                @click="handlePay"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { formatDate, formatNumber, parseDate } from "@/Utils/Common";

    import {
        installmentPaidAmount,
        installmentRemainingAmount,
        isInstallmentPaid,
        sumInstallmentAmounts,
        sumInstallmentPaidAmounts,
    } from "@/Utils/Installments";

    import { formatMoney, lineItemTotal, toMoney } from "@/Utils/Money";
    import { useSgcAuth } from "@/Composables/useSgcAuth";

    const emit = defineEmits([
        "close",
        "updated",
    ]);

    const { canManageCompany } = useSgcAuth();

    const statusLabels = {
        draft: "Rascunho",
        confirmed: "Confirmado",
        cancelled: "Cancelado",
    };

    const statusColors = {
        draft: "grey",
        confirmed: "success",
        cancelled: "error",
    };

    const installmentStatusLabels = {
        cancelled: "Cancelada",
        paid: "Paga",
        partial: "Parcial",
        overdue: "Vencida",
        pending: "Pendente",
    };

    const installmentStatusColors = {
        cancelled: "error",
        paid: "success",
        partial: "warning",
        overdue: "error",
        pending: "grey",
    };

    const isDialogOpen = ref(false);
    const isConfirmCancelDialogOpen = ref(false);
    const isPayDialogOpen = ref(false);
    const loading = ref(false);
    const cancelling = ref(false);
    const paying = ref(false);
    const payAmount = ref(0);
    const payErrors = ref({});
    const cancelReason = ref("");
    const cancelErrors = ref({});
    const order = ref({});
    const selectedInstallment = ref(null);

    const sheetTitle = computed(() => {
        return order.value.id ? `Pedido #${order.value.id}` : "Pedido";
    });

    const isCancelled = computed(() => order.value.status === "cancelled");
    const isConfirmed = computed(() => order.value.status === "confirmed");

    const items = computed(() => order.value.items || []);
    const installments = computed(() => order.value.installments || []);

    const companyName = computed(() => {
        return order.value.company?.trade_name
            || order.value.company?.legal_name
            || "—";
    });

    const customerName = computed(() => order.value.customer?.name || "—");

    const paymentName = computed(() => {
        return order.value.payment_method?.name || "—";
    });

    const createdByName = computed(() => order.value.created_by?.name || "—");

    const orderDiscount = computed(() => toMoney(order.value.discount_amount || 0));

    const installmentsSum = computed(() => sumInstallmentAmounts(installments.value));

    const installmentsPaidSum = computed(() => {
        return sumInstallmentPaidAmounts(installments.value);
    });

    const canCancel = computed(() => {
        return !!order.value.id
            && !isCancelled.value
            && canManageCompany(order.value.company_id);
    });

    const sheetActions = computed(() => {
        if( !canCancel.value ) {
            return [];
        }

        return [
            {
                key: "cancel",
                color: "error",
                variant: "tonal",
                prependIcon: "mdi-cancel",
                loading: cancelling.value,
                text: "Cancelar pedido",
                onClick: openCancelDialog,
            },
        ];
    });

    const canConfirmPay = computed(() => {
        if( !selectedInstallment.value ) {
            return false;
        }

        const amount = toMoney(payAmount.value);
        const remaining = installmentRemainingAmount(selectedInstallment.value);

        return amount > 0 && amount <= remaining;
    });

    const payDialogSummary = computed(() => {
        const installment = selectedInstallment.value;

        if( !installment ) {
            return "";
        }

        const number = installment.installment_number;
        const amount = formatMoney(installment.amount);
        const paid = formatMoney(installmentPaidAmount(installment));
        const remaining = formatMoney(installmentRemainingAmount(installment));

        return `Parcela ${number}ª · Valor R$ ${amount} · Pago R$ ${paid} · Restante R$ ${remaining}`;
    });

    const payAmountHint = computed(() => {
        if( !selectedInstallment.value ) {
            return "";
        }

        return `Restante da parcela: R$ ${formatMoney(installmentRemainingAmount(selectedInstallment.value))}`;
    });

    const payAmountError = computed(() => {
        if( payErrors.value.amount ) {
            return payErrors.value.amount;
        }

        if( !selectedInstallment.value || payAmount.value == null || payAmount.value === "" ) {
            return undefined;
        }

        const amount = toMoney(payAmount.value);
        const remaining = installmentRemainingAmount(selectedInstallment.value);

        if( amount <= 0 ) {
            return "Informe um valor maior que zero.";
        }

        if( amount > remaining ) {
            return "O valor da baixa não pode ser maior que o restante da parcela.";
        }

        return undefined;
    });

    function statusLabel(status) {
        return statusLabels[status] || status;
    }

    function statusColor(status) {
        return statusColors[status] || "default";
    }

    function installmentStatus(installment) {
        if( isCancelled.value ) {
            return "cancelled";
        }

        if( isInstallmentPaid(installment) ) {
            return "paid";
        }

        if( installmentPaidAmount(installment) > 0 ) {
            return "partial";
        }

        if( isOverdue(installment) ) {
            return "overdue";
        }

        return "pending";
    }

    function installmentStatusLabel(installment) {
        return installmentStatusLabels[installmentStatus(installment)];
    }

    function installmentStatusColor(installment) {
        return installmentStatusColors[installmentStatus(installment)];
    }

    function isOverdue(installment) {
        if( isInstallmentPaid(installment) || !installment.due_date ) {
            return false;
        }

        const due = parseDate(installment.due_date);

        if( !due ) {
            return false;
        }

        const today = new Date();

        today.setHours(0, 0, 0, 0);
        due.setHours(0, 0, 0, 0);

        return due < today;
    }

    function formatDueDate(value) {
        return formatDate(value, "dd/MM/yyyy", "—");
    }

    function lineTotal(item) {
        return toMoney(lineItemTotal(
            item.quantity,
            item.unit_price,
            item.discount_amount,
        ));
    }

    function itemSummary(item) {
        const unit = item.unit_of_measure?.abbreviation
            ? ` ${item.unit_of_measure.abbreviation}`
            : "";

        const parts = [
            `${formatNumber(item.quantity, 3)}${unit} × R$ ${formatMoney(item.unit_price)}`,
        ];

        if( Number(item.discount_amount || 0) > 0 ) {
            parts.push(`desconto R$ ${formatMoney(item.discount_amount)}`);
        }

        return parts.join(" · ");
    }

    function canPayInstallment(installment) {
        return isConfirmed.value && installmentRemainingAmount(installment) > 0;
    }

    function openPayDialog(installment) {
        selectedInstallment.value = installment;
        payAmount.value = installmentRemainingAmount(installment);
        payErrors.value = {};
        isPayDialogOpen.value = true;
    }

    async function loadOrder(id) {
        loading.value = true;

        try {
            const url = route("api.tools.sgc.orders.show", id);
            const { data } = await api.get(url);

            order.value = data.order;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar pedido");
            isDialogOpen.value = false;
        } finally {
            loading.value = false;
        }
    }

    async function handlePay() {
        if( !canConfirmPay.value ) return;

        paying.value = true;
        payErrors.value = {};

        try {
            const url = route(
                "api.tools.sgc.orders.installments.pay",
                [order.value.id, selectedInstallment.value.id],
            );

            const { data } = await api.post(url, {
                amount: toMoney(payAmount.value),
            });

            order.value = data.order;
            isPayDialogOpen.value = false;
            selectedInstallment.value = null;

            toastSuccess("Ok", "Baixa registrada com sucesso");
            emit("updated");
        } catch (e) {
            const { message, errors } = e?.response?.data || {};
            payErrors.value = errors || {};
            alertError("Ooops", message || "Erro ao registrar baixa");
        } finally {
            paying.value = false;
        }
    }

    async function handleCancel() {
        if( !String(cancelReason.value || "").trim() ) {
            cancelErrors.value = {
                stock_movement_reason: "Informe o motivo do cancelamento.",
            };

            return;
        }

        cancelling.value = true;
        cancelErrors.value = {};

        try {
            const url = route("api.tools.sgc.orders.cancel", order.value.id);

            const { data } = await api.post(url, {
                stock_movement_reason: cancelReason.value,
            });

            order.value = data.order;
            isConfirmCancelDialogOpen.value = false;
            cancelReason.value = "";

            toastSuccess("Ok", "Pedido cancelado com sucesso");
            emit("updated");
        } catch (e) {
            const { message, errors } = e?.response?.data || {};
            cancelErrors.value = errors || {};
            alertError("Ooops", message || "Erro ao cancelar pedido");
        } finally {
            cancelling.value = false;
        }
    }

    function openCancelDialog() {
        cancelReason.value = "";
        cancelErrors.value = {};
        isConfirmCancelDialogOpen.value = true;
    }

    async function open(data = {}) {
        reset();

        if( !data.id ) {
            return;
        }

        order.value = {
            id: data.id,
            company_id: data.company_id,
            status: data.status,
        };
        isDialogOpen.value = true;
        await loadOrder(data.id);
    }

    function reset() {
        order.value = {};
        selectedInstallment.value = null;
        payAmount.value = 0;
        payErrors.value = {};
        cancelReason.value = "";
        cancelErrors.value = {};
        isConfirmCancelDialogOpen.value = false;
        isPayDialogOpen.value = false;
    }

    function handleClose() {
        reset();
        emit("close");
    }

    defineExpose({
        open,
    });
</script>
