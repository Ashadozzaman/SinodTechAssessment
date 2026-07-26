<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
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
                        <div class="mb-4">
                            <label class="mb-1 block">Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded border p-2" />
                            <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">SKU</label>
                            <input v-model="form.sku" type="text" class="w-full rounded border p-2" />
                            <div v-if="form.errors.sku" class="text-sm text-red-500">{{ form.errors.sku }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">Category</label>
                            <select v-model="form.category_id" class="w-full rounded border p-2">
                                <option value="">None</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.category_id" class="text-sm text-red-500">{{ form.errors.category_id }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">Price</label>
                            <input v-model="form.price" type="number" step="0.01" min="0" class="w-full rounded border p-2" />
                            <div v-if="form.errors.price" class="text-sm text-red-500">{{ form.errors.price }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">Description</label>
                            <textarea v-model="form.description" class="w-full rounded border p-2"></textarea>
                            <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                        </div>

                        <div class="mb-4 flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="form-checkbox h-4 w-4 rounded border" />
                            <span class="ml-2 text-gray-800">Active</span>
                        </div>

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
