<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type Branch, type Paginated, type Sale } from '@/types/models';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales',
        href: '/sales',
    },
];

const props = defineProps<{
    sales: Paginated<Sale>;
    branches: Branch[];
    filters: {
        search: string | null;
        branch_id: string | null;
        status: string | null;
    };
}>();

const { filters, setSearch, setFilter } = useTableFilters(
    route('sales.index'),
    {
        search: props.filters.search ?? '',
        branch_id: props.filters.branch_id ?? '',
        status: props.filters.status ?? '',
    },
    ['sales'],
);

const exportUrl = () => {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.branch_id) params.set('branch_id', filters.branch_id);
    if (filters.status) params.set('status', filters.status);

    const query = params.toString();
    return route('sales.export') + (query ? `?${query}` : '');
};

const columns = [
    { key: 'invoice_number', label: 'Invoice #' },
    { key: 'customer', label: 'Customer' },
    { key: 'branch', label: 'Branch' },
    { key: 'total_amount', label: 'Total' },
    { key: 'status', label: 'Status' },
    { key: 'sale_date', label: 'Date' },
];
</script>

<template>
    <Head title="Sales" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Sales" description="Point-of-sale transaction history.">
                <template #actions>
                    <a
                        :href="exportUrl()"
                        class="border-sidebar-border/70 dark:border-sidebar-border cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        Export Excel
                    </a>
                    <Link
                        v-if="can('sales.create')"
                        :href="route('sales.create')"
                        class="border-sidebar-border/70 dark:border-sidebar-border primary-button cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        New Sale
                    </Link>
                </template>
            </PageHeader>

            <DataTable :columns="columns" :data="sales" empty-message="No sales found.">
                <template #filters>
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by invoice # or customer..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
                    <select
                        :value="filters.branch_id"
                        @change="setFilter('branch_id', ($event.target as HTMLSelectElement).value)"
                        class="rounded border p-2 text-sm"
                    >
                        <option value="">All branches</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </option>
                    </select>
                    <select
                        :value="filters.status"
                        @change="setFilter('status', ($event.target as HTMLSelectElement).value)"
                        class="rounded border p-2 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="completed">Completed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </template>

                <template #cell-invoice_number="{ row }">
                    <Link :href="route('sales.show', row.id)" class="font-medium text-gray-900 hover:underline dark:text-white">
                        {{ row.invoice_number }}
                    </Link>
                </template>

                <template #cell-customer="{ row }">
                    {{ row.customer?.name ?? '—' }}
                </template>

                <template #cell-branch="{ row }">
                    {{ row.branch?.name ?? '—' }}
                </template>

                <template #cell-total_amount="{ row }"> {{ page.props.settings.currency_symbol }}{{ row.total_amount }} </template>

                <template #cell-status="{ row }">
                    <span
                        class="rounded-full px-2 py-1 text-sm font-semibold"
                        :class="row.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                    >
                        {{ row.status_label }}
                    </span>
                </template>

                <template #cell-sale_date="{ row }">
                    {{ new Date(row.sale_date).toLocaleDateString() }}
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
