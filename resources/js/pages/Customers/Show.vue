<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem } from '@/types';
import { type Customer } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    customer: Customer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
    {
        title: props.customer.name,
        href: `/customers/${props.customer.id}`,
    },
];
</script>

<template>
    <Head :title="customer.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader :title="customer.name" description="Customer details.">
                <template #actions>
                    <Link
                        :href="route('customers.index')"
                        class="border-sidebar-border/70 dark:border-sidebar-border primary-button cursor-pointer rounded-md border px-4 py-2 hover:bg-purple-200"
                    >
                        Back
                    </Link>
                </template>
            </PageHeader>

            <div class="border-sidebar-border/70 dark:border-sidebar-border relative rounded-xl border md:min-h-min">
                <div class="mx-auto my-10 max-w-2xl rounded-xl bg-white p-6 shadow-md">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="text-gray-900">{{ customer.phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-gray-900">{{ customer.email ?? '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Address</dt>
                            <dd class="text-gray-900">{{ customer.address ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border relative rounded-xl border md:min-h-min">
                <div class="mx-auto my-10 max-w-2xl rounded-xl bg-white p-6 text-center text-gray-500 shadow-md">
                    Purchase history coming soon.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
