<template>
    <v-bottom-sheet
        inset
        scrollable
        transition="dialog-bottom-transition"
        @after-leave="handleClose"
        v-bind="attrs"
        v-model="isOpen"
    >
        <v-card
            ref="cardRef"
            :height="height"
            :min-height="minHeight"
            :loading="loading"
            class="rounded-b-0"
        >
            <v-card-title class="d-flex justify-space-between align-center ga-2 bg-surface-light">
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

            <v-card-text v-if="!!message">
                <div v-html="message"></div>
            </v-card-text>

            <template v-else-if="noBody">
                <slot></slot>
            </template>

            <v-card-text v-else>
                <slot></slot>
            </v-card-text>

            <template v-if="!hideFooter">
                <v-divider></v-divider>

                <v-card-actions class="flex-wrap">
                    <slot name="actions"></slot>

                    <OverlayActions
                        :actions="actions"
                        :disabled="loading"
                    ></OverlayActions>

                    <v-btn
                        prepend-icon="mdi-close"
                        text="Fechar"
                        :disabled="loading"
                        @click="close"
                    ></v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-bottom-sheet>
</template>

<script setup>
    import { ref, useAttrs } from "vue";

    import OverlayActions from "@/Components/OverlayActions.vue";

    const attrs = useAttrs();

    const props = defineProps({
        overlayId: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        message: {
            type: String,
            default: "",
        },
        height: {
            type: String,
            default: "70dvh",
        },
        minHeight: {
            type: String,
            default: "400px",
        },
        loading: Boolean,
        hideFooter: Boolean,
        noBody: Boolean,
        actions: {
            type: Array,
            default: () => [],
        },
    });

    const emit = defineEmits([
        "close",
    ]);

    const isOpen = defineModel();

    const cardRef = ref(null);

    function open() {
        isOpen.value = true;
    }

    function close() {
        isOpen.value = false;
    }

    function handleClose() {
        emit("close");
    }

    function scrollToTop() {
        cardRef.value?.$el?.querySelector(".v-card-text")?.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    }

    defineExpose({
        open,
        close,
        scrollToTop,
    });
</script>
