import Decimal from "decimal.js";
import { addMonths, format } from "date-fns";

import { adjustInstallmentDueDate } from "@/Utils/BusinessDays";
import { money, toMoney } from "@/Utils/Money";

export function buildDefaultInstallments(count, total) {
    const quantity = Math.max(1, Number(count) || 1);
    const totalAmount = money(total);

    const baseAmount = totalAmount.dividedBy(quantity).toDecimalPlaces(2, Decimal.ROUND_DOWN);

    let remaining = totalAmount;

    const installments = [];

    for( let i = 1; i <= quantity; i++ ) {
        const amount = i === quantity
            ? toMoney(remaining)
            : toMoney(baseAmount);

        remaining = remaining.minus(money(amount));

        installments.push({
            installment_number: i,
            due_date: format(
                adjustInstallmentDueDate(addMonths(new Date(), i)),
                "yyyy-MM-dd",
            ),
            amount,
        });
    }

    return installments;
}

export function sumInstallmentAmounts(installments) {
    return toMoney(
        (installments || []).reduce(
            (total, installment) => total.plus(money(installment.amount)),
            money(0),
        ),
    );
}

export function installmentsTotalMatches(installments, expectedTotal) {
    return sumInstallmentAmounts(installments) === toMoney(expectedTotal);
}

export function installmentPaidAmount(installment) {
    return toMoney(installment?.paid_amount || 0);
}

export function installmentRemainingAmount(installment) {
    const remaining = money(installment?.amount).minus(money(installment?.paid_amount || 0));
    return toMoney(Decimal.max(remaining, 0));
}

export function isInstallmentPaid(installment) {
    return installmentRemainingAmount(installment) <= 0;
}

export function sumInstallmentPaidAmounts(installments) {
    return toMoney(
        (installments || []).reduce(
            (total, installment) => total.plus(money(installmentPaidAmount(installment))),
            money(0),
        ),
    );
}
