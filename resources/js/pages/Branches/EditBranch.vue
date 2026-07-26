<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import FormField from '@/components/FormField.vue';
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
                        <FormField v-model="form.name" label="Name" :error="form.errors.name" />
                        <FormField v-model="form.address" label="Address" :error="form.errors.address" />
                        <FormField v-model="form.phone" label="Phone" :error="form.errors.phone" />
                        <FormField v-model="form.is_active" label="Active" type="checkbox" :error="form.errors.is_active" />

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
