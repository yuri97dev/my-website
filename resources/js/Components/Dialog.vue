<template>
    <v-dialog
        :max-width="maxWidth"
        @after-leave="handleClose"
        v-model="isOpen"
    >
        <v-card>
            <v-card-title class="d-flex justify-space-between align-center ga-2">
                <span class="text-primary">
                    {{ title }}
                </span>

                <v-btn
                    size="small"
                    icon="mdi-close"
                    variant="text"
                    class="rounded"
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

                <v-card-actions>
                    <slot name="actions"></slot>

                    <v-btn
                        prepend-icon="mdi-close"
                        @click="close"
                    >Fechar</v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<script setup>
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
