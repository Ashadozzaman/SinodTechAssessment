<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        modelValue: string | number | boolean | null;
        error?: string;
        type?: 'text' | 'number' | 'email' | 'tel' | 'textarea' | 'checkbox' | 'select';
        step?: string;
        min?: string;
        placeholder?: string;
    }>(),
    { type: 'text' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | boolean];
}>();
</script>

<template>
    <div class="mb-4" :class="type === 'checkbox' ? 'flex items-center' : ''">
        <label class="mb-1 block" :class="type === 'checkbox' ? 'order-2 ml-2' : ''">{{ label }}</label>

        <input
            v-if="type === 'checkbox'"
            :checked="Boolean(modelValue)"
            @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            type="checkbox"
            class="form-checkbox h-4 w-4 rounded border"
        />

        <textarea
            v-else-if="type === 'textarea'"
            :value="modelValue as string"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
            :placeholder="placeholder"
            class="w-full rounded border p-2"
        />

        <select
            v-else-if="type === 'select'"
            :value="modelValue"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
            class="w-full rounded border p-2"
        >
            <slot />
        </select>

        <input
            v-else
            :value="modelValue as string"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            :type="type"
            :step="step"
            :min="min"
            :placeholder="placeholder"
            class="w-full rounded border p-2"
        />

        <div v-if="error" class="text-sm text-red-500">{{ error }}</div>
    </div>
</template>
