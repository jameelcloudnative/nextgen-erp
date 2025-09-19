// User-related TypeScript interfaces for the ERP system

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    companies?: UserCompany[];
    roles?: Role[];
    pivot?: UserCompanyPivot;
}

export interface Company {
    id: number;
    name: string;
    code: string;
    description: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    postal_code: string | null;
    currency: string;
    timezone: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    users?: UserCompany[];
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

export interface UserCompany {
    id: number;
    name: string;
    code: string;
    pivot: UserCompanyPivot;
}

export interface UserCompanyPivot {
    user_id: number;
    company_id: number;
    role_id: number;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    last_activity_at?: string;
}

export interface UserCompanyAssignment {
    company_id: number;
    company_name: string;
    company_code: string;
    role_id: number;
    role_name: string;
    is_default: boolean;
    assigned_at: string;
    updated_at: string;
    team_count: number;
}

export interface UserListData {
    users: PaginatedData<User>;
    company: Company;
    roles: Role[];
    filters: UserFilters;
    stats: UserStats;
}

export interface UserFilters {
    search?: string;
    role?: number;
    status?: 'active' | 'inactive';
}

export interface UserStats {
    total_users: number;
    active_users: number;
    admin_users: number;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface CreateUserFormData {
    action: 'create_new' | 'assign_existing';
    role_id: number;
    is_default: boolean;

    // For creating new user
    name?: string;
    email?: string;
    password?: string;
    password_confirmation?: string;

    // For assigning existing user
    user_id?: number;
}

export interface UpdateUserFormData {
    name: string;
    email: string;
    role_id: number;
    is_default: boolean;
}

export interface AssignUserFormData {
    company_id: number;
    role_id: number;
    is_default: boolean;
}

export interface UserRolesData {
    user: User;
    company_assignments: UserCompanyAssignment[];
    available_roles: Role[];
    statistics: {
        total_companies: number;
        default_company: UserCompanyAssignment | null;
        admin_companies: number;
    };
}

export interface ActivityLogEntry {
    id: number;
    log_name: string;
    description: string;
    subject_type: string;
    subject_id: number;
    causer_type: string;
    causer_id: number;
    properties: Record<string, unknown>;
    created_at: string;
    updated_at: string;
}

// Form validation errors
export interface ValidationErrors {
    [key: string]: string[];
}

// API Response types
export interface ApiResponse<T = Record<string, unknown>> {
    message: string;
    data?: T;
    errors?: ValidationErrors;
}

// Table column definition for user table
export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    searchable?: boolean;
    className?: string;
}

// User table action types
export type UserAction = 'view' | 'edit' | 'roles' | 'remove';

// Company switching
export interface CompanySwitchData {
    company_id: number;
}

// Flash messages
export interface FlashMessage {
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
}
