<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type Sale } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    sale: Sale;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales', href: '/sales' },
    { title: 'Invoice', href: '#' },
];
</script>

<template>
    <Head :title="`Sale ${sale.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Invoice {{ sale.invoice_number }}</h1>
                <Link
                    :href="route('sales.index')"
                    class="border-sidebar-border/70 dark:border-sidebar-border rounded-md border px-4 py-2 text-sm hover:bg-purple-200"
                >
                    Back
                </Link>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border grid gap-4 rounded-xl border p-6 md:grid-cols-3">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Customer</div>
                    <div class="font-medium">{{ sale.customer?.name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Branch</div>
                    <div class="font-medium">{{ sale.branch?.name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Cashier</div>
                    <div class="font-medium">{{ sale.cashier?.name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Status</div>
                    <div class="font-medium">{{ sale.status_label }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Date</div>
                    <div class="font-medium">{{ new Date(sale.sale_date).toLocaleString() }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Total</div>
                    <div class="font-medium">${{ sale.total_amount }}</div>
                </div>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2">Unit Price</th>
                            <th class="py-2">Qty</th>
                            <th class="py-2">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sale.items" :key="item.id" class="border-b">
                            <td class="py-2">{{ item.product_name }}</td>
                            <td class="py-2">${{ item.unit_price }}</td>
                            <td class="py-2">{{ item.quantity }}</td>
                            <td class="py-2">${{ item.subtotal }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
