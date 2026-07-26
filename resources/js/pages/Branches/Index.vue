<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { type BreadcrumbItem } from '@/types';
import { type Branch, type Paginated } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Branches',
        href: '/branches',
    },
];

const props = defineProps<{
    branches: Paginated<Branch>;
    filters: {
        search: string | null;
    };
}>();

const { filters, setSearch } = useTableFilters(route('branches.index'), { search: props.filters.search ?? '' }, ['branches']);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'address', label: 'Address' },
    { key: 'phone', label: 'Phone' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Action' },
];

const deleteBranch = async (id: number) => {
    if (await confirmDelete('Are you sure you want to delete this branch?')) {
        router.delete(route('branches.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('branches.index'), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Branches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Branches" description="Manage your store branches.">
                <template #actions>
                    <Link
                        v-if="can('branches.create')"
                        :href="route('branches.create')"
                        class="border-sidebar-border/70 dark:border-sidebar-border primary-button cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        Create
                    </Link>
                </template>
            </PageHeader>

            <DataTable :columns="columns" :data="branches" empty-message="No branches found.">
                <template #filters>
                    <input
                        :value="filters.search"
                        @input="setSearch(($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search by name or address..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
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
                        v-if="can('branches.update')"
                        :href="route('branches.edit', row.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <button
                        v-if="can('branches.delete')"
                        @click="deleteBranch(row.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
