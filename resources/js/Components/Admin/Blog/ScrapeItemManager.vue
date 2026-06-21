<template>
    <v-bottom-sheet
        inset
        scrollable
        transition="dialog-bottom-transition"
        v-model="isOpen"
    >
        <v-card
            height="70dvh"
            min-height="400px"
            class="rounded-b-0"
            :loading="loading"
        >
            <v-toolbar>
                <v-toolbar-title>
                    Posts Baixados
                </v-toolbar-title>

                <v-btn
                    icon="mdi-close"
                    variant="tonal"
                    color="primary"
                    @click="isOpen = false"
                ></v-btn>
            </v-toolbar>

            <v-card-text>
                <v-row>
                    <template v-if="loading">
                        <v-col
                            v-for="n in 4"
                            :key="n"
                            cols="12"
                            xl="4" lg="6"
                        >
                            <v-skeleton-loader
                                type="card"
                            ></v-skeleton-loader>
                        </v-col>
                    </template>

                    <v-col
                        v-for="(scrapeItem, index) in scrapeItems"
                        :key="index"
                        cols="12"
                        xl="4" lg="6"
                    >
                        <v-card
                            border
                            hover
                            variant="tonal"
                            @click="handleItemClick(scrapeItem)"
                        >
                            <v-card-title class="text-wrap">
                                <div class="lines-2">
                                    {{ scrapeItem.title }}
                                </div>
                            </v-card-title>

                            <v-card-text class="text-primary">
                                {{ scrapeItem.target.name }}
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>
    </v-bottom-sheet>

    <v-dialog
        scrollable
        max-width="900px"
        v-model="isScrapeItemContentDialogOpen"
    >
        <v-toolbar class="rounded-t-lg">
            <v-toolbar-title>
                {{ selectedScrapeItem.title }}
            </v-toolbar-title>

            <v-btn
                icon="mdi-close"
                variant="tonal"
                color="primary"
                @click="isScrapeItemContentDialogOpen = false"
            ></v-btn>
        </v-toolbar>

        <v-card
            class="rounded-t-0"
            height="70dvh"
        >
            <v-card-text>
                <div v-html="selectedScrapeItem.content"></div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="justify-space-between flex-wrap">
                <v-btn
                    v-if="selectedScrapeItem.is_enabled"
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-block-helper"
                    @click="handleDisableScrapeItem"
                >Desativar</v-btn>

                <v-spacer></v-spacer>

                <slot name="content-action-buttons"></slot>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
        max-width="400px"
        v-model="isDisableScrapeItemDialogOpen"
    >
        <v-card>
            <v-card-text>
                <p>Desativar o item?</p>
            </v-card-text>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-block-helper"
                    @click="disableScrapeItem"
                >Desativar</v-btn>

                <v-btn
                    prepend-icon="mdi-close"
                    @click="isDisableScrapeItemDialogOpen = false"
                >Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, nextTick } from "vue";

    import api from "@/api";

    import {
        alertError,
        alertSuccess,
    } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isScrapeItemContentDialogOpen = ref(false);
    const isDisableScrapeItemDialogOpen = ref(false);

    const scrapeItems = ref([]);
    const scrapeTarget = ref(null);

    const selectedScrapeItem = ref(null);

    const loading = ref(false);

    async function getScrapeItems() {
        loading.value = true;

        try {
            const url = route("api.scrape-items.index");
            const { data } = await api.get(url);

            scrapeItems.value = data.scrape_items;
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar itens");
        } finally {
            loading.value = false;
        }
    }

    async function getScrapeItemById(id) {
        loading.value = true;

        try {
            const url = route("api.scrape-items.show", id);
            const { data } = await api.get(url);

            selectedScrapeItem.value = data.scrape_item;
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar item");
            isScrapeItemContentDialogOpen.value = false;
        } finally {
            loading.value = false;
        }
    }

    async function disableScrapeItem() {
        loading.value = true;

        try {
            const url = route("api.scrape-items.disable", selectedScrapeItem.value.id);
            await api.patch(url);

            alertSuccess("Ok", "Item desativado com sucesso");

            isDisableScrapeItemDialogOpen.value = false;
            isScrapeItemContentDialogOpen.value = false;

            nextTick(async () => {
                await getScrapeItems();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao desativar item");
        } finally {
            loading.value = false;
        }
    }

    function handleItemClick(scrapeItem) {
        selectedScrapeItem.value = {
            content: "",
        };

        isScrapeItemContentDialogOpen.value = true;

        nextTick(async () => {
            await getScrapeItemById(scrapeItem.id);
        });
    }

    function handleDisableScrapeItem() {
        if( !selectedScrapeItem.value.is_enabled ) return;
        isDisableScrapeItemDialogOpen.value = true;
    }

    function open(data = {}) {
        scrapeTarget.value = data;
        isOpen.value = true;

        nextTick(async () => {
            await getScrapeItems();
        });
    }

    function close() {
        isScrapeItemContentDialogOpen.value = false;
        isOpen.value = false;
    }

    defineExpose({
        open,
        close,
        selectedScrapeItem,
    });
</script>
