<template>
    <Container>
        <div class="d-flex flex-column flex-lg-row ga-4">
            <v-tabs
                color="primary"
                :grow="!lgAndUp"
                :direction="tabDirection"
                v-model="tab"
            >
                <v-tab prepend-icon="mdi-account" text="Dados Pessoais" value="personal-data"></v-tab>
                <v-tab prepend-icon="mdi-devices" text="Dispositivos" value="devices"/>
                <v-tab prepend-icon="mdi-account-remove" text="Excluir Conta" value="delete-account"/>
            </v-tabs>

            <v-tabs-window class="w-100" v-model="tab">
                <v-tabs-window-item value="personal-data">
                    <PersonalData />
                </v-tabs-window-item>

                <v-tabs-window-item value="devices">
                    <Devices />
                </v-tabs-window-item>

                <v-tabs-window-item value="delete-account">
                    <DeleteAccount />
                </v-tabs-window-item>
            </v-tabs-window>
        </div>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";
    import { useDisplay } from "vuetify";

    import Container from "@/Components/Container.vue";
    import PersonalData from "@/Components/Account/PersonalData.vue";
    import Devices from "@/Components/Account/Devices.vue";
    import DeleteAccount from "@/Components/Account/DeleteAccount.vue";

    const { lgAndUp } = useDisplay();

    const tab = ref("personal-data");

    const tabDirection = computed(() =>
        lgAndUp.value ? "vertical" : "horizontal"
    );
</script>
