<template>
    <v-dialog
        :max-width="maxWidth"
        @after-leave="handleClose"
        v-bind="attrs"
        v-model="isOpen"
    >
        <v-card :class="cardClasses">
            <v-card-title class="d-flex justify-space-between align-center ga-2">
                <span
                    class="text-primary text-truncate"
                    :title="title"
                >
                    {{ title }}
                </span>

                <v-btn
                    size="small"
                    icon="mdi-close"
                    variant="text"
                    @click="close"
                ></v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text v-if="!!message">
                <div v-html="message"></div>
            </v-card-text>

            <template v-else-if="noBody">
                <slot></slot>
            </template>

            <v-card-item v-else>
                <slot></slot>
            </v-card-item>

            <template v-if="!hideFooter">
                <v-divider></v-divider>

                <v-card-actions class="flex-wrap">
                    <slot name="actions"></slot>

                    <v-btn
                        prepend-icon="mdi-close"
                        text="Fechar"
                        @click="close"
                    ></v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { computed, useAttrs } from "vue";

    const attrs = useAttrs();

    const props = defineProps({
        title: {
            type: String,
            required: true,
        },
        message: {
            type: String,
            default: "",
        },
        maxWidth: {
            type: String,
            default: "var(--center-width)",
        },
        hideFooter: Boolean,
        noBody: Boolean,
    });

    const emit = defineEmits([
        "close",
    ]);

    const isOpen = defineModel();

    const cardClasses = computed(() => {
        if( attrs.fullscreen !== "" ) return null;
        return "rounded-t-0 rounded-b-0";
    });

    function open() {
        isOpen.value = true;
    }

    function close() {
        isOpen.value = false;
    }

    function handleClose() {
        emit("close");
    }

    defineExpose({
        open,
        close,
    });
</script>
