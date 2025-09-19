<?php

if (!function_exists('currentCompany')) {
    /**
     * Get the current active company from context
     */
    function currentCompany(): ?\App\Models\Company
    {
        if (app()->has('current_company')) {
            return app('current_company');
        }

        return null;
    }
}

if (!function_exists('currentCompanyId')) {
    /**
     * Get the current active company ID from context
     */
    function currentCompanyId(): ?int
    {
        if (app()->has('current_company_id')) {
            return app('current_company_id');
        }

        return null;
    }
}

if (!function_exists('hasCompanyPermission')) {
    /**
     * Check if current user has permission in current company context
     */
    function hasCompanyPermission(string $permission): bool
    {
        $user = auth()->user();
        $companyId = currentCompanyId();

        if (!$user || !$companyId) {
            return false;
        }

        return $user->hasPermissionInCompany($permission, $companyId);
    }
}

if (!function_exists('scopeToCompany')) {
    /**
     * Scope a query to the current company context
     * Usage: User::where(scopeToCompany('company_id'))->get()
     */
    function scopeToCompany(string $column = 'company_id'): array
    {
        $companyId = currentCompanyId();
        return $companyId ? [$column, $companyId] : [$column, -1]; // -1 ensures no results if no company
    }
}

if (!function_exists('canAccessCompany')) {
    /**
     * Check if current user can access a specific company
     */
    function canAccessCompany(int $companyId): bool
    {
        $user = auth()->user();
        return $user ? $user->hasAccessToCompany($companyId) : false;
    }
}
