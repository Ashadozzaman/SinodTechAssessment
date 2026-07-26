<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { type BreadcrumbItem } from '@/types';
import { type LostCustomer, type Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lost Customers',
        href: '/lost-customers',
    },
];

const props = defineProps<{
    customers: Paginated<LostCustomer>;
    filters: {
        search: string | null;
    };
    lostCustomerDays: number;
}>();

const { filters, setSearch } = useTableFilters(route('lost-customers.index'), { search: props.filters.search ?? '' }, ['customers']);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'last_purchase_at', label: 'Last Purchase' },
];

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString() : 'Never purchased');
</script>

<template>
    <Head title="Lost Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Lost Customers" :description="`Customers with no purchase in the last ${lostCustomerDays}+ days.`" />

            <DataTable :columns="columns" :data="customers" empty-message="No lost customers found.">
                <template #filters>
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by name, phone, or email..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
                </template>

                <template #cell-name="{ row }">
                    <Link :href="route('customers.show', row.id)" class="font-medium text-gray-900 hover:underline dark:text-white">
                        {{ row.name }}
                    </Link>
                </template>

                <template #cell-email="{ row }">
                    {{ row.email ?? '—' }}
                </template>

                <template #cell-last_purchase_at="{ row }">
                    {{ formatDate(row.last_purchase_at) }}
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
