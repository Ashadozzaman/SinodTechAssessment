<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { type BreadcrumbItem } from '@/types';
import { type EmployeeKpi, type Paginated } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'KPI Leaderboard',
        href: '/kpi-leaderboard',
    },
];

const props = defineProps<{
    employees: Paginated<EmployeeKpi>;
    filters: {
        search: string | null;
    };
}>();

const { filters, setSearch } = useTableFilters(route('kpi-leaderboard.index'), { search: props.filters.search ?? '' }, ['employees']);

const columns = [
    { key: 'rank', label: '#' },
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'kpi_score', label: 'KPI Score' },
];

const rankOf = (id: number) => props.employees.data.findIndex((employee) => employee.id === id) + props.employees.meta.per_page * (props.employees.meta.current_page - 1) + 1;
</script>

<template>
    <Head title="KPI Leaderboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Employee KPI Leaderboard" description="Employees ranked by KPI points earned from assigned-customer sales." />

            <DataTable :columns="columns" :data="employees" empty-message="No employees found.">
                <template #filters>
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
                </template>

                <template #cell-rank="{ row }">
                    {{ rankOf(row.id) }}
                </template>

                <template #cell-email="{ row }">
                    {{ row.email ?? '—' }}
                </template>

                <template #cell-kpi_score="{ row }">
                    <span class="font-semibold">{{ row.kpi_score }}</span>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
