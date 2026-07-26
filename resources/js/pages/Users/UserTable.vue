<script setup>
import { can } from '@/lib/can';
import { confirmDelete } from '@/lib/confirm';
import { router } from '@inertiajs/vue3';
const props = defineProps(['users']);

const deleteUser = async (id) => {
    if (await confirmDelete('Are you sure you want to delete this user?')) {
        router.delete(route('users.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('users.index'), {
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
                <th scope="col" class="px-6 py-3">Email</th>
                <th scope="col" class="px-6 py-3">Role</th>
                <!-- <th scope="col" class="px-6 py-3">Mobile</th> -->
                <th scope="col" class="px-6 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="user in users"
                :key="user.id"
                class="border-b border-gray-200 odd:bg-white even:bg-gray-50 dark:border-gray-700 odd:dark:bg-gray-900 even:dark:bg-gray-800"
            >
                <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">{{ user.name }}</th>
                <td class="px-6 py-4">{{ user.email }}</td>
                <td class="px-6 py-4">
                    <span
                        v-for="role in user.roles"
                        :key="role.id"
                        class="mr-2 inline-block rounded-full bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                        >{{ role.name }}</span
                    >
                </td>
                <!-- <td class="px-6 py-4">01865432121</td> -->
                <td class="px-6 py-4">
                    <a
                        v-if="can('users.update')"
                        :href="route('users.edit', user.id)"
                        class="p-2 font-medium text-blue-600 hover:underline dark:text-blue-500"
                        >Edit</a
                    >
                    <!-- <Link
                        :href="route('users.destroy', user.id)"
                        method="delete"
                        as="button"
                        @click="confirmDelete"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                        >Delete</Link
                    > -->
                    <button
                        v-if="can('users.delete')"
                        @click="deleteUser(user.id)"
                        class="p-2 font-medium text-red-600 hover:underline dark:text-blue-500"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
