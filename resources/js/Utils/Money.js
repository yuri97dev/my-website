import Decimal from "decimal.js";

const MONEY_DECIMAL_PLACES = 2;
const ROUNDING = Decimal.ROUND_HALF_UP;

Decimal.set({
    precision: 20,
    rounding: ROUNDING,
});

export function money(value) {
    if( value instanceof Decimal ) {
        return value;
    }

    if( value == null || value === "" ) {
        return new Decimal(0);
    }

    return new Decimal(value);
}

export function toMoney(value) {
    return money(value).toDecimalPlaces(MONEY_DECIMAL_PLACES, ROUNDING).toNumber();
}

export function normalizeMoneyInput(value) {
    if( value === null || value === "" ) {
        return null;
    }

    return toMoney(value);
}

export function formatMoney(value) {
    return toMoney(value).toLocaleString("pt-BR", {
        minimumFractionDigits: MONEY_DECIMAL_PLACES,
        maximumFractionDigits: MONEY_DECIMAL_PLACES,
    });
}

export function addMoney(...values) {
    return values.reduce(
        (total, value) => total.plus(money(value)),
        money(0),
    );
}

export function subtractMoney(minuend, subtrahend) {
    return money(minuend).minus(money(subtrahend));
}

export function multiplyMoney(a, b) {
    return money(a).times(money(b));
}

export function maxMoney(...values) {
    return Decimal.max(...values.map(money));
}

export function lineItemTotal(quantity, unitPrice, discount = 0) {
    return multiplyMoney(quantity, unitPrice).minus(money(discount));
}

export function sumOrderLines(items) {
    return items.reduce((total, item) => {
        return total.plus(lineItemTotal(
            item.quantity,
            item.unit_price,
            item.discount_amount,
        ));
    }, money(0));
}

export function orderTotalAfterDiscount(subtotal, discount = 0) {
    return maxMoney(0, subtractMoney(subtotal, discount));
}
