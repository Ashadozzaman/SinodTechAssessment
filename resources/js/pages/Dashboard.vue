<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type LowStockProduct, type RecentSale, type SalesChart, type SalesSummary, type TopProduct } from '@/types/models';
import { Head, usePage } from '@inertiajs/vue3';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const props = defineProps<{
    totalUsers: number;
    salesSummary: SalesSummary | null;
    salesChart: SalesChart | null;
    topProducts: TopProduct[] | null;
    lowStockProducts: LowStockProduct[] | null;
    recentSales: RecentSale[] | null;
}>();

const page = usePage<SharedData>();
const formatMoney = (value: number) => `${page.props.settings.currency_symbol}${value.toFixed(2)}`;

type Period = 'today' | 'weekly' | 'monthly';
const periods: { key: Period; label: string }[] = [
    { key: 'today', label: 'Today' },
    { key: 'weekly', label: 'Weekly' },
    { key: 'monthly', label: 'Monthly' },
];
const activePeriod = ref<Period>('weekly');

const activeSeries = computed(() => props.salesChart?.[activePeriod.value]);

const chartData = computed(() => ({
    labels: activeSeries.value?.labels ?? [],
    datasets: [
        {
            label: 'Revenue',
            data: activeSeries.value?.data ?? [],
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124, 58, 237, 0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 2,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <PageHeader title="Dashboard" description="Sales overview and store health at a glance." />

            <div class="grid gap-4 md:grid-cols-4">
                <Card v-if="salesSummary">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Sales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ formatMoney(salesSummary.today.total) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ salesSummary.today.count }} sale(s)</div>
                    </CardContent>
                </Card>
                <Card v-if="salesSummary">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-500 dark:text-gray-400">This Week</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ formatMoney(salesSummary.weekly.total) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ salesSummary.weekly.count }} sale(s)</div>
                    </CardContent>
                </Card>
                <Card v-if="salesSummary">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ formatMoney(salesSummary.monthly.total) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ salesSummary.monthly.count }} sale(s)</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ totalUsers }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="salesChart">
                <CardHeader>
                    <CardTitle>Sales Trend</CardTitle>
                    <CardAction>
                        <div class="border-sidebar-border/70 dark:border-sidebar-border flex gap-1 rounded-md border p-1">
                            <button
                                v-for="period in periods"
                                :key="period.key"
                                type="button"
                                :class="[
                                    'rounded px-3 py-1 text-sm transition-colors',
                                    activePeriod === period.key
                                        ? 'bg-purple-600 text-white'
                                        : 'text-gray-500 hover:bg-purple-100 dark:text-gray-400 dark:hover:bg-purple-900/30',
                                ]"
                                @click="activePeriod = period.key"
                            >
                                {{ period.label }}
                            </button>
                        </div>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <div class="h-72">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>

            <div v-if="topProducts || lowStockProducts" class="grid gap-4 md:grid-cols-2">
                <Card v-if="topProducts">
                    <CardHeader>
                        <CardTitle>Top Selling Products (This Month)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topProducts.length === 0" class="text-sm text-gray-500 dark:text-gray-400">No sales yet this month.</div>
                        <ul v-else class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y">
                            <li v-for="product in topProducts" :key="product.id" class="flex items-center justify-between py-2">
                                <div>
                                    <div class="font-medium">{{ product.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ product.quantity_sold }} sold</div>
                                </div>
                                <div class="font-semibold">{{ formatMoney(product.revenue) }}</div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card v-if="lowStockProducts">
                    <CardHeader>
                        <CardTitle>Low Stock Alerts</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="lowStockProducts.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                            All products are well stocked.
                        </div>
                        <ul v-else class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y">
                            <li v-for="product in lowStockProducts" :key="product.id" class="flex items-center justify-between py-2">
                                <div>
                                    <div class="font-medium">{{ product.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ product.sku }}</div>
                                </div>
                                <div class="font-semibold text-red-600 dark:text-red-400">{{ product.quantity }} left</div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="recentSales">
                <CardHeader>
                    <CardTitle>Recent Sales</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentSales.length === 0" class="text-sm text-gray-500 dark:text-gray-400">No sales recorded yet.</div>
                    <table v-else class="w-full text-left text-sm">
                        <thead class="text-xs text-gray-500 uppercase dark:text-gray-400">
                            <tr>
                                <th class="py-2">Invoice #</th>
                                <th class="py-2">Customer</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Date</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y">
                            <tr v-for="sale in recentSales" :key="sale.id">
                                <td class="py-2">{{ sale.invoice_number }}</td>
                                <td class="py-2">{{ sale.customer_name ?? '—' }}</td>
                                <td class="py-2">{{ sale.status_label }}</td>
                                <td class="py-2">{{ sale.sale_date }}</td>
                                <td class="py-2 text-right">{{ formatMoney(sale.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
