export interface Branch {
    id: number;
    name: string;
    address: string;
    phone: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    name: string;
    slug?: string;
}

export interface Product {
    id: number;
    name: string;
    sku: string;
    category_id: number | null;
    category?: Category | null;
    price: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ProductStockRow {
    branch_id: number;
    branch_name: string;
    quantity: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}
