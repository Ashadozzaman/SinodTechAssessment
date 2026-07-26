<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { type Branch, type CustomerSearchResult, type ProductSearchResult } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales', href: '/sales' },
    { title: 'New Sale', href: '/sales/create' },
];

const props = defineProps<{
    branches: Branch[];
}>();

interface CartLine {
    product_id: number;
    name: string;
    sku: string;
    unit_price: number;
    quantity: number;
    available_stock: number;
}

const form = useForm<{
    customer_id: number | '';
    branch_id: number | '';
    items: { product_id: number; quantity: number }[];
}>({
    customer_id: '',
    branch_id: '',
    items: [],
});

const selectedCustomer = ref<CustomerSearchResult | null>(null);
const customerSearch = ref('');
const customerResults = ref<CustomerSearchResult[]>([]);

const productSearch = ref('');
const productResults = ref<ProductSearchResult[]>([]);

const cart = ref<CartLine[]>([]);

let customerDebounce: ReturnType<typeof setTimeout> | null = null;
watch(customerSearch, (value) => {
    if (customerDebounce) clearTimeout(customerDebounce);
    if (!value) {
        customerResults.value = [];
        return;
    }
    customerDebounce = setTimeout(async () => {
        const url = new URL(route('sales.search-customers'), window.location.origin);
        url.searchParams.set('search', value);
        const response = await fetch(url.toString());
        customerResults.value = await response.json();
    }, 300);
});

let productDebounce: ReturnType<typeof setTimeout> | null = null;
watch([productSearch, () => form.branch_id], ([value, branchId]) => {
    if (productDebounce) clearTimeout(productDebounce);
    if (!value || !branchId) {
        productResults.value = [];
        return;
    }
    productDebounce = setTimeout(async () => {
        const url = new URL(route('sales.search-products'), window.location.origin);
        url.searchParams.set('search', value);
        url.searchParams.set('branch_id', String(branchId));
        const response = await fetch(url.toString());
        productResults.value = await response.json();
    }, 300);
});

function selectCustomer(customer: CustomerSearchResult) {
    selectedCustomer.value = customer;
    form.customer_id = customer.id;
    customerSearch.value = '';
    customerResults.value = [];
}

function clearCustomer() {
    selectedCustomer.value = null;
    form.customer_id = '';
}

function addProduct(product: ProductSearchResult) {
    const existing = cart.value.find((line) => line.product_id === product.id);
    if (existing) {
        if (existing.quantity < existing.available_stock) {
            existing.quantity += 1;
        }
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: Number(product.price),
            quantity: 1,
            available_stock: product.available_stock,
        });
    }
    productSearch.value = '';
    productResults.value = [];
}

function removeLine(productId: number) {
    cart.value = cart.value.filter((line) => line.product_id !== productId);
}

const total = computed(() => cart.value.reduce((sum, line) => sum + line.unit_price * line.quantity, 0));

function submit() {
    form.items = cart.value.map((line) => ({ product_id: line.product_id, quantity: line.quantity }));
    form.post(route('sales.store'));
}
</script>

<template>
    <Head title="New Sale" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">New Sale</h1>
                <Link
                    :href="route('sales.index')"
                    class="border-sidebar-border/70 dark:border-sidebar-border rounded-md border px-4 py-2 text-sm hover:bg-purple-200"
                >
                    Back
                </Link>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Branch</label>
                    <select v-model="form.branch_id" class="w-full rounded border p-2">
                        <option value="">Select a branch...</option>
                        <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.branch_id" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Customer</label>

                    <div v-if="selectedCustomer" class="flex items-center justify-between rounded border bg-gray-50 p-2 dark:bg-gray-800">
                        <span>{{ selectedCustomer.name }} · {{ selectedCustomer.phone }}</span>
                        <button type="button" class="text-sm text-red-600 hover:underline" @click="clearCustomer">Change</button>
                    </div>

                    <div v-else class="relative">
                        <input
                            v-model="customerSearch"
                            type="text"
                            placeholder="Search by name, phone, or email..."
                            class="w-full rounded border p-2"
                        />
                        <ul
                            v-if="customerResults.length"
                            class="absolute z-10 mt-1 w-full rounded border bg-white shadow-md dark:bg-gray-900"
                        >
                            <li
                                v-for="customer in customerResults"
                                :key="customer.id"
                                class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                                @click="selectCustomer(customer)"
                            >
                                {{ customer.name }} · {{ customer.phone }}
                            </li>
                        </ul>
                    </div>
                    <InputError :message="form.errors.customer_id" />
                </div>
            </div>

            <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                <label class="mb-1 block text-sm font-medium">Add product</label>
                <div class="relative max-w-md">
                    <input
                        v-model="productSearch"
                        type="text"
                        :disabled="!form.branch_id"
                        :placeholder="form.branch_id ? 'Search by name or SKU...' : 'Select a branch first'"
                        class="w-full rounded border p-2 disabled:bg-gray-100 dark:disabled:bg-gray-800"
                    />
                    <ul v-if="productResults.length" class="absolute z-10 mt-1 w-full rounded border bg-white shadow-md dark:bg-gray-900">
                        <li
                            v-for="product in productResults"
                            :key="product.id"
                            class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                            @click="addProduct(product)"
                        >
                            <span>{{ product.name }} ({{ product.sku }})</span>
                            <span class="text-gray-500">{{ product.available_stock }} in stock · ${{ product.price }}</span>
                        </li>
                    </ul>
                </div>
                <InputError :message="form.errors.items" />

                <table class="mt-4 w-full text-left text-sm">
                    <thead class="border-b text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2">Unit Price</th>
                            <th class="py-2">Qty</th>
                            <th class="py-2">Subtotal</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cart.length === 0">
                            <td colspan="5" class="py-6 text-center text-gray-400">No products added yet.</td>
                        </tr>
                        <tr v-for="line in cart" :key="line.product_id" class="border-b">
                            <td class="py-2">{{ line.name }}</td>
                            <td class="py-2">${{ line.unit_price.toFixed(2) }}</td>
                            <td class="py-2">
                                <input
                                    v-model.number="line.quantity"
                                    type="number"
                                    min="1"
                                    :max="line.available_stock"
                                    class="w-20 rounded border p-1"
                                />
                            </td>
                            <td class="py-2">${{ (line.unit_price * line.quantity).toFixed(2) }}</td>
                            <td class="py-2">
                                <button type="button" class="text-red-600 hover:underline" @click="removeLine(line.product_id)">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 flex items-center justify-between border-t pt-4">
                    <span class="text-lg font-semibold">Total: ${{ total.toFixed(2) }}</span>
                    <button
                        type="button"
                        :disabled="form.processing || cart.length === 0 || !form.customer_id || !form.branch_id"
                        class="rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                        @click="submit"
                    >
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
