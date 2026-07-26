<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type Branch } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Branch',
        href: '/branches',
    },
];

const props = defineProps<{
    branch: Branch;
}>();

const form = useForm({
    name: props.branch.name || '',
    address: props.branch.address || '',
    phone: props.branch.phone || '',
    is_active: props.branch.is_active,
});
</script>

<template>
    <Head title="Edit Branch" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Link
                :href="route('branches.index')"
                class="border-sidebar-border/70 dark:border-sidebar-border primary-button absolute top-4 right-2 cursor-pointer self-end rounded-md border px-4 py-2 hover:bg-purple-200"
            >
                Back
            </Link>
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="relative overflow-x-auto sm:rounded-lg">
                    <form
                        @submit.prevent="form.put(route('branches.update', props.branch.id))"
                        class="mx-auto my-10 max-w-md rounded-xl bg-white p-6 shadow-md"
                    >
                        <div class="mb-4">
                            <label class="mb-1 block">Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded border p-2" />
                            <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">Address</label>
                            <input v-model="form.address" type="text" class="w-full rounded border p-2" />
                            <div v-if="form.errors.address" class="text-sm text-red-500">{{ form.errors.address }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full rounded border p-2" />
                            <div v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</div>
                        </div>

                        <div class="mb-4 flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="form-checkbox h-4 w-4 rounded border" />
                            <span class="ml-2 text-gray-800">Active</span>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
