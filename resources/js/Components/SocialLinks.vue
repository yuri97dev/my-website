<template>
    <section>
        <h2 class="text-primary d-flex align-center ga-2">
            Conecte-se
        </h2>

        <p class="text-grey">
            Acompanhe meu conteúdo nas redes sociais ou entre em contato por e-mail.
        </p>

        <div class="d-flex flex-wrap ga-2 mb-4">
            <v-btn
                v-for="item in socialItems"
                :key="item.platform"
                variant="tonal"
                color="primary"
                :prepend-icon="item.icon"
                :text="item.label"
                :href="getSocialWebUrl(item.platform)"
                target="_blank"
                rel="noopener noreferrer"
                @click="handleSocialClick($event, item.platform)"
            ></v-btn>
        </div>

        <div class="d-flex align-center ga-2 text-grey">
            <v-icon icon="mdi-email-outline"></v-icon>

            <a
                href="mailto:contato@yuri97.com"
                class="text-primary"
            >contato@yuri97.com</a>
        </div>
    </section>
</template>

<script setup>
    import { SOCIAL_ITEMS, getSocialWebUrl, handleSocialClick } from "@/Utils/OpenSocialLink";

    const socialItems = SOCIAL_ITEMS;
</script>
