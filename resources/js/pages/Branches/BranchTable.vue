<script setup lang="ts">
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { type Branch, type Paginated } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';

defineProps<{
    branches: Paginated<Branch>;
}>();

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
    <table class="w-full text-left text-sm text-gray-500 rtl:text-right dark:text-gray-400">
        <thead class="bg-gray-50 text-xs text-gray-700 uppercase dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">Name</th>
                <th scope="col" class="px-6 py-3">Address</th>
                <th scope="col" class="px-6 py-3">Phone</th>
                <th scope="col" class="px-6 py-3">Status</th>
                <th scope="col" class="px-6 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="branch in branches.data"
                :key="branch.id"
                class="border-b border-gray-200 odd:bg-white even:bg-gray-50 dark:border-gray-700 odd:dark:bg-gray-900 even:dark:bg-gray-800"
            >
                <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">{{ branch.name }}</th>
                <td class="px-6 py-4">{{ branch.address }}</td>
                <td class="px-6 py-4">{{ branch.phone }}</td>
                <td class="px-6 py-4">
                    <span
                        class="rounded-full px-2 py-1 text-sm font-semibold"
                        :class="branch.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                    >
                        {{ branch.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a
                        v-if="can('branches.update')"
                        :href="route('branches.edit', branch.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <button
                        v-if="can('branches.delete')"
                        @click="deleteBranch(branch.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <div v-if="branches.meta" class="flex flex-wrap items-center gap-1 p-4">
        <template v-for="link in branches.meta.links" :key="link.label">
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
</template>
