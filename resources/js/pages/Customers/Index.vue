<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { type BreadcrumbItem } from '@/types';
import { type Customer, type Paginated } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
];

const props = defineProps<{
    customers: Paginated<Customer>;
    filters: {
        search: string | null;
    };
}>();

const { filters, setSearch } = useTableFilters(route('customers.index'), { search: props.filters.search ?? '' }, ['customers']);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'address', label: 'Address' },
    { key: 'actions', label: 'Action' },
];

const deleteCustomer = async (id: number) => {
    if (await confirmDelete('Are you sure you want to delete this customer?')) {
        router.delete(route('customers.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('customers.index'), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Customers" description="Manage your customer records.">
                <template #actions>
                    <Link
                        v-if="can('customers.create')"
                        :href="route('customers.create')"
                        class="border-sidebar-border/70 dark:border-sidebar-border primary-button cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        Create
                    </Link>
                </template>
            </PageHeader>

            <DataTable :columns="columns" :data="customers" empty-message="No customers found.">
                <template #filters>
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by name or phone..."
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

                <template #cell-address="{ row }">
                    {{ row.address ?? '—' }}
                </template>

                <template #cell-actions="{ row }">
                    <a
                        v-if="can('customers.update')"
                        :href="route('customers.edit', row.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <button
                        v-if="can('customers.delete')"
                        @click="deleteCustomer(row.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
