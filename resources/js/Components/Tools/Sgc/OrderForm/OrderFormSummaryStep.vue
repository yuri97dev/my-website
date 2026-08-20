<template>
    <v-list lines="two">
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
            v-if="showInstallments"
            prepend-icon="mdi-calendar-multiple"
            title="Parcelas"
            :subtitle="`${installmentsCount}x`"
        ></v-list-item>

        <v-divider class="my-2"></v-divider>

        <v-list-item
            v-for="item in cartItems"
            :key="`summary-${item.company_product_id}`"
            prepend-icon="mdi-package-variant"
            :title="item.name"
            :subtitle="itemSummary(item)"
        >
            <template #append>
                <span>R$ {{ formatMoney(lineTotal(item)) }}</span>
            </template>
        </v-list-item>

        <v-divider class="my-2"></v-divider>

        <v-list-item
            v-if="orderDiscountAmount > 0"
            prepend-icon="mdi-sale-outline"
            title="Desconto"
        >
            <template #append>
                <span>- R$ {{ formatMoney(orderDiscountAmount) }}</span>
            </template>
        </v-list-item>

        <v-list-item
            prepend-icon="mdi-receipt-text-outline"
            title="Total do pedido"
            class="font-weight-bold"
        >
            <template #append>
                <span class="text-primary text-h6">
                    R$ {{ formatMoney(orderTotal) }}
                </span>
            </template>
        </v-list-item>
    </v-list>

    <v-textarea
        class="mt-2"
        label="Observações"
        rows="2"
        auto-grow
        prepend-inner-icon="mdi-note-text-outline"
        :error-messages="errors.notes"
        v-model="notes"
    ></v-textarea>
</template>

<script setup>
    import { formatNumber } from "@/Utils/Common";
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
        orderTotal: {
            type: Number,
            default: 0,
        },
        orderDiscountAmount: {
            type: Number,
            default: 0,
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
        const parts = [];

        if( item.serial_number ) {
            parts.push(item.serial_number);
        }

        parts.push(`${formatNumber(item.quantity, 3)} × R$ ${formatMoney(item.unit_price)}`);

        if( Number(item.discount_amount || 0) > 0 ) {
            parts.push(`desconto R$ ${formatMoney(item.discount_amount)}`);
        }

        return parts.join(" · ");
    }
</script>
