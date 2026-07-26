<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useTableFilters } from '@/composables/useTableFilters';
import { can } from '@/lib/can';
import { type BreadcrumbItem } from '@/types';
import { type Branch, type Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import BranchTable from './BranchTable.vue';

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
</script>

<template>
    <Head title="Branches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Link
                v-if="can('branches.create')"
                :href="route('branches.create')"
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
                        placeholder="Search by name or address..."
                        class="w-full max-w-xs rounded border p-2 text-sm"
                    />
                </div>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <BranchTable :branches="branches" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
