import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

type FilterValue = string | number | boolean | null | undefined;
type Filters = Record<string, FilterValue>;

const SEARCH_DEBOUNCE_MS = 300;

/**
 * Shared search/filter composable for DataTable-style Index pages.
 * Wraps Inertia's router.get() with debounced search, immediate discrete
 * filters, and a partial reload scoped to `only` (per CLAUDE.md §4b).
 */
export function useTableFilters<T extends Filters>(baseUrl: string, initialFilters: T, only: string[]) {
    const filters = reactive({ ...initialFilters }) as T;
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    function apply() {
        router.get(baseUrl, { ...filters }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only,
        });
    }

    function setSearch(value: string) {
        filters.search = value as T[Extract<keyof T, string>];

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(apply, SEARCH_DEBOUNCE_MS);
    }

    function setFilter(key: keyof T, value: FilterValue) {
        filters[key] = value as T[keyof T];
        apply();
    }

    return { filters, setSearch, setFilter };
}
