<template>
    <v-menu
        v-if="useMenu"
        offset="8"
        location="bottom start"
    >
        <template #activator="{ props: menuProps }">
            <v-btn
                variant="tonal"
                prepend-icon="mdi-dots-vertical"
                text="Mais Opções"
                :disabled="disabled"
                v-bind="menuProps"
            ></v-btn>
        </template>

        <v-list border slim>
            <v-list-item
                v-for="(action, index) in buttonActions"
                :key="action.key ?? index"
                :prepend-icon="action.prependIcon"
                :append-icon="action.appendIcon"
                :title="action.text"
                :class="action.color ? `text-${action.color}` : undefined"
                :disabled="action.disabled || disabled || action.loading"
                :href="action.href"
                :target="action.target"
                @click="action.onClick?.()"
            ></v-list-item>
        </v-list>
    </v-menu>

    <template v-else>
        <template
            v-for="(action, index) in actions"
            :key="action.key ?? index"
        >
            <v-spacer v-if="action.spacer"></v-spacer>

            <v-btn
                v-else
                v-bind="vBtnProps(action)"
                :disabled="action.disabled || disabled || action.loading"
                @click="action.onClick?.()"
            >{{ action.text }}</v-btn>
        </template>
    </template>
</template>

<script setup>
    import { computed } from "vue";
    import { useDisplay } from "vuetify";

    const props = defineProps({
        actions: {
            type: Array,
            default: () => [],
        },
        disabled: Boolean,
    });

    const { smAndDown } = useDisplay();

    const buttonActions = computed(() =>
        props.actions.filter((action) => !action.spacer)
    );

    const useMenu = computed(() =>
        smAndDown.value && buttonActions.value.length > 2
    );

    function vBtnProps(action) {
        const {
            key,
            text,
            onClick,
            spacer,
            ...props
        } = action;

        return props;
    }
</script>
