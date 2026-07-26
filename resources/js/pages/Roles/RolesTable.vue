<script setup>
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { router } from '@inertiajs/vue3';
const props = defineProps(['roles']);

const actionOrder = ['view', 'create', 'update', 'delete'];

const groupPermissions = (permissions) => {
    const groups = {};
    for (const permission of permissions) {
        const [module, ...rest] = permission.name.split('.');
        const action = rest.join('.') || permission.name;
        (groups[module] ??= []).push(action);
    }

    return Object.entries(groups).map(([module, actions]) => ({
        module,
        actions: actions.sort((a, b) => {
            const indexA = actionOrder.indexOf(a);
            const indexB = actionOrder.indexOf(b);
            if (indexA === -1 && indexB === -1) return a.localeCompare(b);
            if (indexA === -1) return 1;
            if (indexB === -1) return -1;
            return indexA - indexB;
        }),
    }));
};

const deleterole = async (id) => {
    if (await confirmDelete('Are you sure you want to delete this role?')) {
        router.delete(route('roles.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('roles.index'), {
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
                <th scope="col" class="px-6 py-3">Permission</th>
                <th scope="col" class="px-6 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="role in roles"
                :key="role.id"
                class="border-b border-gray-200 odd:bg-white even:bg-gray-50 dark:border-gray-700 odd:dark:bg-gray-900 even:dark:bg-gray-800"
            >
                <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">{{ role.name }}</th>
                <th scope="row" class="px-6 py-4 align-top font-medium text-gray-900 dark:text-white">
                    <div class="flex max-w-md flex-wrap gap-2">
                        <div
                            v-for="group in groupPermissions(role.permissions)"
                            :key="group.module"
                            class="rounded-md border border-gray-200 bg-gray-100 px-2 py-1 text-xs whitespace-nowrap dark:border-gray-700 dark:bg-gray-700"
                        >
                            <span class="font-semibold text-gray-700 capitalize dark:text-gray-300">{{ group.module }}:</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ group.actions.join(', ') }}</span>
                        </div>
                    </div>
                </th>

                <td class="px-6 py-4">
                    <a
                        v-if="can('roles.update')"
                        :href="route('roles.edit', role.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <button
                        v-if="can('roles.delete')"
                        @click="deleterole(role.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
