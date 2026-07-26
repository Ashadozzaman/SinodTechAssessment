<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>();
</script>

<template>
    <div v-if="page.props.settings.logo_url" class="flex aspect-square size-8 items-center justify-center rounded-md">
        <img :src="page.props.settings.logo_url" :alt="page.props.settings.company_name" class="size-8 rounded-md object-contain" />
    </div>
    <div v-else class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-none font-semibold">{{ page.props.settings.company_name }}</span>
    </div>
</template>
