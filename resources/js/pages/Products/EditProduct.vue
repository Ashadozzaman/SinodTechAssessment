<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import { can } from '@/lib/can';
import { type BreadcrumbItem } from '@/types';
import { type Category, type Product, type ProductStockRow } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Product',
        href: '/products',
    },
];

const props = defineProps<{
    product: Product;
    categories: Category[];
    stocks: ProductStockRow[];
}>();

const form = useForm({
    name: props.product.name || '',
    sku: props.product.sku || '',
    category_id: props.product.category_id || '',
    price: props.product.price || '',
    description: props.product.description || '',
    is_active: props.product.is_active,
});

const stockForm = useForm({
    branch_id: 0,
    delta: 0,
});

const deltas = reactive<Record<number, number | null>>({});

function adjustStock(branchId: number) {
    const delta = deltas[branchId];

    if (!delta) {
        return;
    }

    stockForm.branch_id = branchId;
    stockForm.delta = delta;
    stockForm.put(route('inventory.adjust', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            deltas[branchId] = null;
        },
    });
}
</script>

<template>
    <Head title="Edit Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Link
                :href="route('products.index')"
                class="border-sidebar-border/70 dark:border-sidebar-border primary-button absolute top-4 right-2 cursor-pointer self-end rounded-md border px-4 py-2 hover:bg-purple-200"
            >
                Back
            </Link>
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="relative overflow-x-auto sm:rounded-lg">
                    <form
                        @submit.prevent="form.put(route('products.update', props.product.id))"
                        class="mx-auto my-10 max-w-md rounded-xl bg-white p-6 shadow-md"
                    >
                        <FormField v-model="form.name" label="Name" :error="form.errors.name" />
                        <FormField v-model="form.sku" label="SKU" :error="form.errors.sku" />

                        <FormField v-model="form.category_id" label="Category" type="select" :error="form.errors.category_id">
                            <option value="">None</option>
                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </FormField>

                        <FormField v-model="form.price" label="Price" type="number" step="0.01" min="0" :error="form.errors.price" />
                        <FormField v-model="form.description" label="Description" type="textarea" :error="form.errors.description" />
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

            <div class="border-sidebar-border/70 dark:border-sidebar-border relative rounded-xl border md:min-h-min">
                <div class="mx-auto my-10 max-w-2xl rounded-xl bg-white p-6 shadow-md">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Stock by Branch</h2>

                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">Branch</th>
                                <th class="py-2">Quantity</th>
                                <th v-if="can('inventory.adjust')" class="py-2">Adjust</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="stock in props.stocks" :key="stock.branch_id" class="border-b last:border-0">
                                <td class="py-2">{{ stock.branch_name }}</td>
                                <td class="py-2">{{ stock.quantity }}</td>
                                <td v-if="can('inventory.adjust')" class="py-2">
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model.number="deltas[stock.branch_id]"
                                            type="number"
                                            placeholder="+/- qty"
                                            class="w-24 rounded border p-1"
                                        />
                                        <button
                                            type="button"
                                            :disabled="stockForm.processing"
                                            @click="adjustStock(stock.branch_id)"
                                            class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            Adjust
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="stockForm.errors.delta" class="mt-2 text-sm text-red-500">{{ stockForm.errors.delta }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
