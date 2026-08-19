<template>
    <v-list density="compact" class="pa-0">
        <v-list-item title="Empresa" :subtitle="companyName"></v-list-item>
        <v-list-item title="Cliente" :subtitle="customerName"></v-list-item>
        <v-list-item title="Pagamento" :subtitle="paymentName"></v-list-item>

        <template v-if="showInstallments">
            <v-list-item
                title="Parcelas"
                :subtitle="`${installmentsCount}x`"
            ></v-list-item>

            <v-list-item
                v-for="installment in installments"
                :key="`summary-installment-${installment.installment_number}`"
                :title="`${installment.installment_number}ª parcela`"
                :subtitle="formatDueDate(installment.due_date)"
            >
                <template #append>
                    <span>R$ {{ formatMoney(installment.amount) }}</span>
                </template>
            </v-list-item>
        </template>

        <v-divider class="my-2"></v-divider>

        <v-list-item
            v-for="item in cartItems"
            :key="`summary-${item.product_id}`"
            :title="item.name"
            :subtitle="itemSummary(item)"
        >
            <template #append>
                <span>R$ {{ formatMoney(lineTotal(item)) }}</span>
            </template>
        </v-list-item>

        <v-divider class="my-2"></v-divider>

        <v-list-item title="Total dos itens">
            <template #append>
                <span>R$ {{ formatMoney(subtotal) }}</span>
            </template>
        </v-list-item>

        <v-list-item
            v-if="orderDiscountAmount > 0"
            title="Desconto do pedido"
        >
            <template #append>
                <span>- R$ {{ formatMoney(orderDiscountAmount) }}</span>
            </template>
        </v-list-item>

        <v-list-item title="Total do pedido" class="font-weight-bold">
            <template #append>
                <span class="text-primary text-h6">
                    R$ {{ formatMoney(orderTotal) }}
                </span>
            </template>
        </v-list-item>

        <v-col cols="12" class="px-0 pt-2">
            <v-textarea
                label="Observações"
                rows="2"
                auto-grow
                :error-messages="errors.notes"
                v-model="notes"
            ></v-textarea>
        </v-col>
    </v-list>
</template>

<script setup>
    import { formatDate, formatNumber } from "@/Utils/Common";
    import { formatMoney, lineItemTotal, toMoney } from "@/Utils/Money";

    defineProps({
        companyName: {
            type: String,
            default: "—",
        },
        customerName: {
            type: String,
            default: "—",
        },
        paymentName: {
            type: String,
            default: "—",
        },
        cartItems: {
            type: Array,
            default: () => [],
        },
        subtotal: {
            type: Number,
            default: 0,
        },
        orderTotal: {
            type: Number,
            default: 0,
        },
        orderDiscountAmount: {
            type: Number,
            default: 0,
        },
        installments: {
            type: Array,
            default: () => [],
        },
        installmentsCount: {
            type: Number,
            default: 1,
        },
        showInstallments: Boolean,
        errors: {
            type: Object,
            default: () => ({}),
        },
    });

    const notes = defineModel("notes", {
        type: [ String, null ],
        default: null,
    });

    function lineTotal(item) {
        return toMoney(lineItemTotal(
            item.quantity,
            item.unit_price,
            item.discount_amount,
        ));
    }

    function itemSummary(item) {
        const parts = [
            `${formatNumber(item.quantity, 3)} × R$ ${formatMoney(item.unit_price)}`,
        ];

        if( Number(item.discount_amount || 0) > 0 ) {
            parts.push(`desconto R$ ${formatMoney(item.discount_amount)}`);
        }

        return parts.join(" · ");
    }

    function formatDueDate(value) {
        if( !value ) return "Sem vencimento";

        return formatDate(value, "dd/MM/yyyy");
    }
</script>
