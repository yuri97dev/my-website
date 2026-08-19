<template>
    <v-tabs
        grow
        inset
        class="mb-4"
        v-model="productsTab"
    >
        <v-tab
            value="list"
            prepend-icon="mdi-list-box"
            text="Listagem"
        ></v-tab>

        <v-tab
            value="cart"
            prepend-icon="mdi-cart-outline"
            :text="cartTabLabel"
        ></v-tab>
    </v-tabs>

    <v-tabs-window v-model="productsTab">
        <v-tabs-window-item value="list">
            <OrderFormProductList
                :company-id="companyId"
                :cart="cart"
                @set-quantity="emit('set-quantity', $event)"
            ></OrderFormProductList>
        </v-tabs-window-item>

        <v-tabs-window-item value="cart">
            <v-alert
                v-if="cartItems.length === 0"
                type="info"
                variant="tonal"
                text="Nenhum produto adicionado. Selecione produtos na aba Listagem."
            ></v-alert>

            <template v-else>
                <v-row>
                    <v-col
                        v-for="(item, index) in cartItems"
                        :key="item.product_id"
                        cols="12"
                        lg="6"
                    >
                        <v-card
                            border
                            variant="tonal"
                            class="h-100 d-flex flex-column"
                        >
                            <v-card-title class="text-wrap text-body-1">
                                {{ item.name }}
                            </v-card-title>

                            <v-card-text class="d-flex flex-column ga-3 flex-grow-1">
                                <v-text-field
                                    label="Preço unitário"
                                    prefix="R$"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    hide-details
                                    v-model.number="item.unit_price"
                                ></v-text-field>

                                <v-text-field
                                    label="Desconto do item"
                                    prefix="R$"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    hide-details
                                    :error-messages="itemError(index, 'discount_amount')"
                                    v-model.number="item.discount_amount"
                                ></v-text-field>

                                <v-number-input
                                    label="Quantidade"
                                    hide-details
                                    control-variant="split"
                                    :min="0"
                                    :max="item.stock_quantity"
                                    :suffix="item.unit_of_measure || undefined"
                                    :model-value="item.quantity"
                                    @update:model-value="updateItemQuantity(item, $event)"
                                ></v-number-input>

                                <span class="text-body-2 text-medium-emphasis">
                                    Total: R$ {{ formatMoney(lineTotal(item)) }}
                                </span>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </template>
        </v-tabs-window-item>
    </v-tabs-window>
</template>

<script setup>
    import { ref, computed } from "vue";

    import OrderFormProductList from "@/Components/Tools/Sgc/OrderForm/OrderFormProductList.vue";

    import { formatMoney, lineItemTotal, toMoney } from "@/Utils/Money";

    const props = defineProps({
        companyId: {
            type: [ Number, null ],
            default: null,
        },
        cart: {
            type: Object,
            required: true,
        },
        cartItems: {
            type: Array,
            default: () => [],
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    });

    const emit = defineEmits([
        "set-quantity",
    ]);

    const productsTab = ref("list");

    const cartTabLabel = computed(() => {
        const count = props.cartItems.length;

        return count > 0 ? `Carrinho (${count})` : "Carrinho";
    });

    function lineTotal(item) {
        return toMoney(lineItemTotal(
            item.quantity,
            item.unit_price,
            item.discount_amount,
        ));
    }

    function itemError(index, field) {
        return props.errors[`items.${index}.${field}`];
    }

    function updateItemQuantity(item, quantity) {
        emit("set-quantity", {
            productId: item.product_id,
            quantity,
        });
    }
</script>
