<template>
    <Container title="Laya" gapped>
        <KeepAlive>
            <component :is="screens[active]" />
        </KeepAlive>
    </Container>
</template>

<script setup>
    import { ref, provide, onBeforeUnmount } from "vue";

    import { LayaRequestKey } from "@/Composables/useLaya";
    import { useTabs } from "@/Composables/useTabs";

    import Container from "@/Components/Container.vue";
    import Choice from "@/Components/Laya/Choice.vue";
    import Score from "@/Components/Laya/Score.vue";
    import Noul from "@/Components/Laya/Noul.vue";
    import Raw from "@/Components/Laya/Raw.vue";

    provide(LayaRequestKey, ref(null));

    const { active, sync, clear } = useTabs();

    const screens = { choice: Choice, score: Score, noul: Noul, raw: Raw };

    sync([
        { id: "choice", title: "Choice", icon: "mdi-format-list-bulleted" },
        { id: "score", title: "Score", icon: "mdi-chart-bar" },
        { id: "noul", title: "Noul", icon: "mdi-checkbox-marked-circle-outline" },
        { id: "raw", title: "Raw", icon: "mdi-code-json" },
    ], "choice");

    onBeforeUnmount(clear);
</script>
