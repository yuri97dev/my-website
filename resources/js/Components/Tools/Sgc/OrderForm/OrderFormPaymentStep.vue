<template>
    <v-row>
        <v-col cols="12" xl="4">
            <v-select
                label="Forma de pagamento"
                item-title="name"
                item-value="id"
                :loading="loading"
                :items="paymentMethods"
                :error-messages="errors.payment_method_id"
                v-model="paymentMethodId"
            ></v-select>
        </v-col>

        <v-col
            v-if="supportsInstallments"
            cols="12"
            xl="4"
        >
            <v-select
                label="Número de parcelas"
                :items="installmentOptions"
                :error-messages="errors.installments_count"
                v-model="installmentsCount"
            ></v-select>
        </v-col>

        <v-col cols="12" xl="4">
            <v-text-field
                label="Desconto do pedido"
                prefix="R$"
                type="number"
                min="0"
                step="0.01"
                :error-messages="errors.discount_amount"
                v-model.number="orderDiscountAmount"
            ></v-text-field>
        </v-col>

        <v-col
            v-if="showInstallmentList"
            cols="12"
        >
            <v-table class="border rounded-lg">
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="installment in installments"
                        :key="installment.installment_number"
                    >
                        <td>
                            {{ installment.installment_number }}ª
                        </td>

                        <td>
                            <v-date-input
                                width="300"
                                prepend-icon=""
                                prepend-inner-icon="mdi-calendar-outline"
                                bg-color="transparent"
                                :allowed-dates="isDueDateAllowed"
                                :error-messages="installmentError(installment.installment_number, 'due_date')"
                                :model-value="parseDueDate(installment.due_date)"
                                @update:model-value="(value) => setDueDate(installment, value)"
                            ></v-date-input>
                        </td>

                        <td>
                            <v-text-field
                                type="number"
                                width="300"
                                prefix="R$"
                                min="0"
                                step="0.01"
                                bg-color="transparent"
                                :error-messages="installmentError(installment.installment_number, 'amount')"
                                @update:model-value="handleInstallmentEdit"
                                v-model.number="installment.amount"
                            ></v-text-field>
                        </td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr>
                        <th>
                            Total
                        </th>

                        <th>
                            <!-- <v-btn
                                size="small"
                                variant="tonal"
                                prepend-icon="mdi-refresh"
                                text="Redistribuir"
                                @click="handleRedistribute"
                            ></v-btn> -->
                        </th>

                        <th class="text-end">
                            R$ {{ formatMoney(installmentsSum) }}
                        </th>
                    </tr>
                </tfoot>
            </v-table>

            <v-alert
                v-if="hasInstallmentMismatch"
                type="warning"
                variant="tonal"
                class="mt-4"
                :text="installmentMismatchMessage"
            ></v-alert>
        </v-col>
    </v-row>
</template>

<script setup>
    import { computed } from "vue";

    import {
        buildDefaultInstallments,
        installmentsTotalMatches,
        sumInstallmentAmounts,
    } from "@/Utils/Installments";

    import { formatDate, parseDate } from "@/Utils/Common";
    import { isNonBusinessDay } from "@/Utils/BusinessDays";
    import { formatMoney } from "@/Utils/Money";

    const props = defineProps({
        paymentMethods: {
            type: Array,
            default: () => [],
        },
        loading: Boolean,
        subtotal: {
            type: Number,
            default: 0,
        },
        orderTotal: {
            type: Number,
            default: 0,
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    });

    const emit = defineEmits([
        "installments-customized",
        "redistribute-installments",
    ]);

    const paymentMethodId = defineModel("paymentMethodId", {
        type: [ Number, null ],
        default: null,
    });

    const installmentsCount = defineModel("installmentsCount", {
        type: Number,
        default: 1,
    });

    const installments = defineModel("installments", {
        type: Array,
        default: () => [],
    });

    const orderDiscountAmount = defineModel("orderDiscountAmount", {
        type: Number,
        default: 0,
    });

    const selectedPaymentMethod = computed(() => {
        return props.paymentMethods.find(
            method => method.id === paymentMethodId.value
        ) ?? null;
    });

    const supportsInstallments = computed(() => {
        return selectedPaymentMethod.value?.supports_installments === true;
    });

    const showInstallmentList = computed(() => {
        return supportsInstallments.value && installmentsCount.value > 1;
    });

    const installmentOptions = computed(() => {
        return Array.from({ length: 12 }, (_, index) => {
            const count = index + 1;

            return {
                title: `${count}x`,
                value: count,
            };
        });
    });

    const installmentsSum = computed(() => {
        return sumInstallmentAmounts(installments.value);
    });

    const hasInstallmentMismatch = computed(() => {
        if( !showInstallmentList.value ) {
            return false;
        }

        return !installmentsTotalMatches(installments.value, props.orderTotal);
    });

    const installmentMismatchMessage = computed(() => {
        return `A soma das parcelas (R$ ${formatMoney(installmentsSum.value)}) deve ser igual ao total do pedido (R$ ${formatMoney(props.orderTotal)}).`;
    });

    function installmentError(installmentNumber, field) {
        const key = `installments.${installmentNumber - 1}.${field}`;

        return props.errors[key];
    }

    function parseDueDate(value) {
        return parseDate(value);
    }

    function setDueDate(installment, value) {
        installment.due_date = value ? formatDate(value, "yyyy-MM-dd") : null;
        handleInstallmentEdit();
    }

    function isDueDateAllowed(date) {
        const parsed = parseDate(date);

        if( !parsed ) {
            return false;
        }

        return !isNonBusinessDay(parsed);
    }

    function handleInstallmentEdit() {
        emit("installments-customized");
    }

    function handleRedistribute() {
        installments.value = buildDefaultInstallments(
            installmentsCount.value,
            props.orderTotal,
        );

        emit("redistribute-installments");
    }
</script>
