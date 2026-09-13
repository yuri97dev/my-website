<template>
    <Container title="Sobre">
        <div class="mx-auto w-100" style="max-width: 800px">
            <v-card>
                <v-card-text>
                    <section>
                        <h1 class="text-primary">Sobre o {{ appName }}</h1>

                        <p>
                            Este é um projeto pessoal criado por Yuri Seabra
                            para reunir ferramentas web que facilitam tarefas
                            do dia a dia e disponibilizá-las para quem quiser
                            usar.
                        </p>

                        <p>
                            O projeto também é um espaço para experimentar
                            novas tecnologias e compartilhar conteúdo sobre
                            tecnologia, programação, animes e cultura pop.
                        </p>
                    </section>

                    <v-divider class="my-4"></v-divider>

                    <SocialLinks></SocialLinks>

                    <v-divider class="my-4"></v-divider>

                    <section>
                        <h2 class="text-primary">Informações Legais</h2>

                        <div class="d-flex flex-wrap ga-2">
                            <v-btn
                                variant="tonal"
                                color="primary"
                                prepend-icon="mdi-shield-lock-outline"
                                text="Política de Privacidade"
                                :href="route('privacy_policy')"
                                @click.prevent="goTo(route('privacy_policy'))"
                            ></v-btn>

                            <v-btn
                                variant="tonal"
                                color="primary"
                                prepend-icon="mdi-file-document-outline"
                                text="Termos de Uso"
                                :href="route('terms_of_use')"
                                @click.prevent="goTo(route('terms_of_use'))"
                            ></v-btn>
                        </div>
                    </section>
                </v-card-text>
            </v-card>
        </div>
    </Container>
</template>

<script setup>
    import { computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import Container from "@/Components/Container.vue";
    import SocialLinks from "@/Components/SocialLinks.vue";

    import { goTo } from "@/Utils/Common";

    const appName = computed(() => usePage().props.appName);
</script>
