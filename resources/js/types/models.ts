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

export interface Customer {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    address: string | null;
    created_at: string;
    updated_at: string;
}

export interface LostCustomer {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    address: string | null;
    last_purchase_at: string | null;
    created_at: string;
}

export interface EmployeeOption {
    id: number;
    name: string;
}

export interface ProductStockRow {
    branch_id: number;
    branch_name: string;
    quantity: number;
}

export interface CustomerSearchResult {
    id: number;
    name: string;
    phone: string;
    email: string | null;
}

export interface ProductSearchResult {
    id: number;
    name: string;
    sku: string;
    price: string;
    available_stock: number;
}

export interface SaleItem {
    id: number;
    product_id: number;
    product_name: string;
    quantity: number;
    unit_price: string;
    subtotal: string;
}

export interface Sale {
    id: number;
    invoice_number: string;
    customer?: { id: number; name: string };
    branch?: { id: number; name: string };
    cashier?: { id: number; name: string };
    items?: SaleItem[];
    total_amount: string;
    status: 'completed' | 'refunded';
    status_label: string;
    sale_date: string;
    created_at: string;
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
