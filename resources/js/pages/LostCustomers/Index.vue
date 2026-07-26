<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { type BreadcrumbItem } from '@/types';
import { type EmployeeOption, type LostCustomer, type Paginated } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

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
    employees: EmployeeOption[];
}>();

const { filters, setSearch } = useTableFilters(route('lost-customers.index'), { search: props.filters.search ?? '' }, ['customers']);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'last_purchase_at', label: 'Last Purchase' },
    { key: 'actions', label: '' },
];

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString() : 'Never purchased');

const selectedEmployee = reactive<Record<number, string>>({});
const assignErrors = reactive<Record<number, string>>({});

function assign(customerId: number) {
    const employeeId = selectedEmployee[customerId];
    if (!employeeId) return;

    assignErrors[customerId] = '';

    router.post(
        route('customers.assign', customerId),
        { employee_id: employeeId },
        {
            preserveScroll: true,
            onError: (errors) => {
                assignErrors[customerId] = errors.employee_id ?? 'Assignment failed.';
            },
        },
    );
}
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

                <template #cell-actions="{ row }">
                    <div v-if="can('customers.assign')" class="flex items-center gap-2">
                        <select v-model="selectedEmployee[row.id]" class="rounded border p-1 text-sm">
                            <option value="" disabled>Assign to…</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }}
                            </option>
                        </select>
                        <button
                            @click="assign(row.id)"
                            type="button"
                            class="rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700"
                        >
                            Assign
                        </button>
                        <div v-if="assignErrors[row.id]" class="text-xs text-red-500">{{ assignErrors[row.id] }}</div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
