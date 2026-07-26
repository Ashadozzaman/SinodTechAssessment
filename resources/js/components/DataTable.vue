<script setup lang="ts" generic="T extends { id: number | string }">
import { type Paginated } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{
    columns: { key: string; label: string; class?: string }[];
    data: Paginated<T>;
    emptyMessage?: string;
}>();
</script>

<template>
    <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2 p-4">
            <slot name="filters" />
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-left text-sm text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="bg-gray-50 text-xs text-gray-700 uppercase dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th v-for="column in columns" :key="column.key" scope="col" class="px-6 py-3" :class="column.class">
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="data.data.length === 0">
                        <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-400">
                            {{ emptyMessage ?? 'No records found.' }}
                        </td>
                    </tr>
                    <tr
                        v-for="row in data.data"
                        :key="row.id"
                        class="border-b border-gray-200 odd:bg-white even:bg-gray-50 dark:border-gray-700 odd:dark:bg-gray-900 even:dark:bg-gray-800"
                    >
                        <td v-for="column in columns" :key="column.key" class="px-6 py-4" :class="column.class">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ (row as Record<string, unknown>)[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="data.meta" class="flex flex-wrap items-center gap-1 p-4">
            <template v-for="link in data.meta.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    class="rounded px-3 py-1 text-sm"
                    :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'"
                />
                <span v-else v-html="link.label" class="rounded px-3 py-1 text-sm text-gray-400" />
            </template>
        </div>
    </div>
</template>
