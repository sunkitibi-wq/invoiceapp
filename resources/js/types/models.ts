export type Money = {
    cents: number;
    formatted: string;
};

export type InvoiceStatus =
    | 'draft'
    | 'sent'
    | 'partially_paid'
    | 'paid'
    | 'overdue'
    | 'void';

export type DiscountType = 'percent' | 'fixed';

export type PaymentMethodValue =
    | 'cash'
    | 'bank_transfer'
    | 'card'
    | 'cheque'
    | 'other';

export type RecurrenceFrequencyValue =
    | 'weekly'
    | 'fortnightly'
    | 'monthly'
    | 'quarterly'
    | 'yearly';

export type Option = {
    value: string;
    label: string;
};

export type Customer = {
    id: number;
    name: string;
    company: string | null;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
    notes: string | null;
    is_active: boolean;
    billing_name: string;
    address: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    invoices_count?: number;
    outstanding_invoices_count?: number;
};

export type CustomerSummary = {
    id: number;
    name: string;
    company: string | null;
    email: string | null;
};

export type Service = {
    id: number;
    name: string;
    unit: string | null;
    description: string | null;
    unit_price: Money;
    tax_rate: number | null;
    is_active: boolean;
    created_at?: string | null;
    updated_at?: string | null;
};

export type ServiceOption = {
    id: number;
    name: string;
    unit: string | null;
    description: string | null;
    unit_price: string;
    tax_rate_percent: number | null;
};

export type InvoiceItem = {
    id: number;
    invoice_id: number;
    service_id: number | null;
    description: string;
    quantity: number;
    unit_price: Money;
    tax_rate: number | null;
    tax_rate_percent: number | null;
    subtotal?: Money;
    tax_amount?: Money;
    total?: Money;
    position: number;
    created_at?: string | null;
    updated_at?: string | null;
};

export type Payment = {
    id: number;
    invoice_id: number;
    amount: Money;
    method: PaymentMethodValue;
    method_label: string;
    paid_at: string;
    reference: string | null;
    note: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    invoice?: Invoice;
};

export type Invoice = {
    id: number;
    number: string;
    customer_id: number;
    recurring_invoice_id: number | null;
    status: InvoiceStatus;
    status_label: string;
    issue_date: string;
    due_date: string;
    currency: string;
    subtotal: Money;
    discount_type: DiscountType | null;
    discount_value: number | null;
    discount_amount: Money;
    tax_rate: number;
    tax_rate_percent: number;
    tax_total: Money;
    total: Money;
    amount_paid: Money;
    balance: Money;
    is_overdue: boolean;
    is_editable: boolean;
    notes: string | null;
    terms: string | null;
    sent_at: string | null;
    paid_at: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    customer?: Customer;
    items?: InvoiceItem[];
    payments?: Payment[];
};

export type RecurringInvoice = {
    id: number;
    customer_id: number;
    frequency: RecurrenceFrequencyValue;
    frequency_label: string;
    next_invoice_at: string;
    end_at: string | null;
    due_date_days: number;
    tax_rate: number;
    tax_rate_percent: number;
    discount_type: DiscountType | null;
    discount_value: number | null;
    notes: string | null;
    terms: string | null;
    is_active: boolean;
    is_due: boolean;
    last_generated_at: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    customer?: Customer;
    items?: RecurringInvoiceItem[];
};

export type RecurringInvoiceItem = {
    id: number;
    recurring_invoice_id: number;
    service_id: number | null;
    description: string;
    quantity: number;
    unit_price: Money;
    tax_rate: number | null;
    tax_rate_percent: number | null;
    position: number;
    created_at?: string | null;
    updated_at?: string | null;
};

export type Paginated<T> = {
    current_page: number;
    data: T[];
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type RoleOption = { value: number; label: string };

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    roles?: RoleOption[];
};

export type Role = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
};

export type PermissionGroup = {
    group: string;
    permissions: Array<{ value: string; label: string }>;
};

export type PermissionStatus = 'synced' | 'custom' | 'missing';

export type PermissionRecord = {
    id: number | null;
    name: string;
    label: string;
    group: string;
    status: PermissionStatus;
    is_code_defined: boolean;
    roles: Array<{ id: number; name: string }>;
    roles_count: number;
};

export type PermissionSummary = {
    total: number;
    synced: number;
    custom: number;
    missing: number;
};
