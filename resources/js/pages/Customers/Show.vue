<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { type BreadcrumbItem } from '@/types';
import { type Customer, type Engagement, type Sale, type Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    customer: Customer;
    sales: Paginated<Sale>;
    engagements: Paginated<Engagement>;
    lastPurchaseAt: string | null;
    purchaseFrequency: number;
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

const saleColumns = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'sale_date', label: 'Date' },
    { key: 'total_amount', label: 'Total' },
    { key: 'status_label', label: 'Status' },
];

const engagementColumns = [
    { key: 'channel_label', label: 'Channel' },
    { key: 'message', label: 'Message' },
    { key: 'status_label', label: 'Status' },
    { key: 'sent_at', label: 'Sent At' },
    { key: 'triggered_by', label: 'Triggered By' },
];

const engagementStatusClass = (status: Engagement['status']) => {
    return {
        sent: 'bg-green-100 text-green-800',
        simulated: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
    }[status];
};
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
                <div class="mx-auto my-6 grid max-w-2xl grid-cols-2 gap-4 rounded-xl bg-white p-6 shadow-md">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Purchase</dt>
                        <dd class="text-gray-900">{{ lastPurchaseAt ? new Date(lastPurchaseAt).toLocaleDateString() : 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Purchase Frequency</dt>
                        <dd class="text-gray-900">{{ purchaseFrequency }} {{ purchaseFrequency === 1 ? 'sale' : 'sales' }}</dd>
                    </div>
                </div>
            </div>

            <DataTable :columns="saleColumns" :data="sales" empty-message="No purchases yet.">
                <template #cell-invoice_number="{ row }">
                    <Link :href="route('sales.show', row.id)" class="text-blue-600 hover:underline">{{ row.invoice_number }}</Link>
                </template>
                <template #cell-sale_date="{ row }">
                    {{ new Date(row.sale_date).toLocaleDateString() }}
                </template>
                <template #cell-total_amount="{ row }"> ${{ row.total_amount }} </template>
            </DataTable>

            <PageHeader title="Engagement History" description="Past re-engagement attempts sent to this customer." />

            <DataTable :columns="engagementColumns" :data="engagements" empty-message="No re-engagement messages sent yet.">
                <template #cell-status_label="{ row }">
                    <span class="rounded px-2 py-1 text-xs font-medium" :class="engagementStatusClass(row.status)">
                        {{ row.status_label }}
                    </span>
                </template>
                <template #cell-sent_at="{ row }"> {{ new Date(row.sent_at).toLocaleString() }} </template>
                <template #cell-triggered_by="{ row }"> {{ row.triggered_by ?? '—' }} </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
