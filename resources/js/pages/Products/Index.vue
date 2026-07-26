<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { type BreadcrumbItem } from '@/types';
import { type Category, type Paginated, type Product } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';

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

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'sku', label: 'SKU' },
    { key: 'category', label: 'Category' },
    { key: 'price', label: 'Price' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Action' },
];

const deleteProduct = async (id: number) => {
    if (await confirmDelete('Are you sure you want to delete this product?')) {
        router.delete(route('products.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('products.index'), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Products" description="Manage your product catalog.">
                <template #actions>
                    <Link
                        v-if="can('products.create')"
                        :href="route('products.create')"
                        class="border-sidebar-border/70 dark:border-sidebar-border primary-button cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        Create
                    </Link>
                </template>
            </PageHeader>

            <DataTable :columns="columns" :data="products" empty-message="No products found.">
                <template #filters>
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
                </template>

                <template #cell-category="{ row }">
                    {{ row.category?.name ?? '—' }}
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="rounded-full px-2 py-1 text-sm font-semibold"
                        :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                    >
                        {{ row.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </template>

                <template #cell-actions="{ row }">
                    <a
                        v-if="can('products.update')"
                        :href="route('products.edit', row.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <button
                        v-if="can('products.delete')"
                        @click="deleteProduct(row.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
