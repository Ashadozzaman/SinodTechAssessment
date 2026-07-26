<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import { type BreadcrumbItem } from '@/types';
import { type Category } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Product',
        href: '/products',
    },
];

defineProps<{
    categories: Category[];
}>();

const form = useForm({
    name: '',
    sku: '',
    category_id: '',
    price: '',
    description: '',
    is_active: true,
});
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Link
                :href="route('products.index')"
                class="border-sidebar-border/70 dark:border-sidebar-border primary-button absolute top-4 right-2 cursor-pointer self-end rounded-md border px-4 py-2 hover:bg-purple-200"
            >
                Back
            </Link>
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="relative overflow-x-auto sm:rounded-lg">
                    <form @submit.prevent="form.post(route('products.store'))" class="mx-auto my-10 max-w-md rounded-xl bg-white p-6 shadow-md">
                        <FormField v-model="form.name" label="Name" :error="form.errors.name" />
                        <FormField v-model="form.sku" label="SKU" :error="form.errors.sku" />

                        <FormField v-model="form.category_id" label="Category" type="select" :error="form.errors.category_id">
                            <option value="">None</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </FormField>

                        <FormField v-model="form.price" label="Price" type="number" step="0.01" min="0" :error="form.errors.price" />
                        <FormField v-model="form.description" label="Description" type="textarea" :error="form.errors.description" />
                        <FormField v-model="form.is_active" label="Active" type="checkbox" :error="form.errors.is_active" />

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
