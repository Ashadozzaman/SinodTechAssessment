<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { type BreadcrumbItem } from '@/types';
import { type Category, type Paginated, type Product } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import ProductTable from './ProductTable.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

const props = defineProps<{
    products: Paginated<Product>;
    categories: Category[];
    filters: {
        search: string | null;
        category_id: string | null;
    };
}>();

const { filters, setSearch, setFilter } = useTableFilters(
    route('products.index'),
    { search: props.filters.search ?? '', category_id: props.filters.category_id ?? '' },
    ['products'],
);
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Link
                v-if="can('products.create')"
                :href="route('products.create')"
                class="border-sidebar-border/70 dark:border-sidebar-border primary-button absolute top-4 right-2 cursor-pointer self-end rounded-md border px-4 py-2 hover:bg-purple-200"
            >
                Create
            </Link>
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="flex flex-wrap items-center gap-2 p-4">
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by name or SKU..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
                    <select
                        :value="filters.category_id"
                        @change="setFilter('category_id', ($event.target as HTMLSelectElement).value)"
                        class="rounded border p-2 text-sm"
                    >
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <ProductTable :products="products" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
