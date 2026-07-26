<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import FormField from '@/components/FormField.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { type GeneralSetting } from '@/types/models';

const props = defineProps<{
    setting: GeneralSetting;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'General settings',
        href: '/settings/general',
    },
];

const form = useForm({
    company_name: props.setting.company_name,
    currency_symbol: props.setting.currency_symbol,
    logo: null as File | null,
});

const logoPreview = ref<string | null>(props.setting.logo_url);

const onLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : props.setting.logo_url;
};

const submit = () => {
    form.post(route('general.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.logo = null;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="General settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="General settings" description="Manage your company name, currency symbol, and logo" />

                <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                    <FormField v-model="form.company_name" label="Company name" :error="form.errors.company_name" />
                    <FormField v-model="form.currency_symbol" label="Currency symbol" :error="form.errors.currency_symbol" placeholder="$" />

                    <div class="grid gap-2">
                        <Label for="logo">Company logo</Label>
                        <img v-if="logoPreview" :src="logoPreview" alt="Company logo" class="h-16 w-16 rounded object-contain" />
                        <input id="logo" type="file" accept="image/*" class="text-sm" @change="onLogoChange" />
                        <p v-if="form.errors.logo" class="text-sm text-red-500">{{ form.errors.logo }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
