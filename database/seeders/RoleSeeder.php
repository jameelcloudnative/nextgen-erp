<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all system permissions following ERPNext modules
        $permissions = [
            // Company Management
            'view-companies',
            'create-companies',
            'edit-companies',
            'delete-companies',

            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Role & Permission Management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Accounting Module
            'view-accounts',
            'create-accounts',
            'edit-accounts',
            'delete-accounts',
            'view-journal-entries',
            'create-journal-entries',
            'edit-journal-entries',
            'delete-journal-entries',
            'view-financial-reports',

            // Sales Module
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
            'view-sales-orders',
            'create-sales-orders',
            'edit-sales-orders',
            'delete-sales-orders',
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',

            // Purchase Module
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',
            'view-purchase-orders',
            'create-purchase-orders',
            'edit-purchase-orders',
            'delete-purchase-orders',
            'view-purchase-invoices',
            'create-purchase-invoices',
            'edit-purchase-invoices',
            'delete-purchase-invoices',

            // Inventory Module
            'view-items',
            'create-items',
            'edit-items',
            'delete-items',
            'view-stock',
            'create-stock-entries',
            'edit-stock-entries',
            'delete-stock-entries',

            // HR Module
            'view-employees',
            'create-employees',
            'edit-employees',
            'delete-employees',
            'view-payroll',
            'create-payroll',
            'edit-payroll',
            'delete-payroll',

            // Project Management
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'view-tasks',
            'create-tasks',
            'edit-tasks',
            'delete-tasks',

            // Reports & Analytics
            'view-reports',
            'create-reports',
            'edit-reports',
            'delete-reports',
            'view-analytics',

            // System Settings
            'view-settings',
            'edit-settings',
            'view-activity-logs',
            'backup-system',
            'restore-system',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Define roles with their respective permissions
        $rolesData = [
            'Super Admin' => [
                'description' => 'Full system access across all companies',
                'permissions' => $permissions, // All permissions
            ],

            'Company Admin' => [
                'description' => 'Full access within assigned company',
                'permissions' => array_filter($permissions, function($permission) {
                    return !in_array($permission, ['view-companies', 'create-companies', 'edit-companies', 'delete-companies']);
                }),
            ],

            'Accountant' => [
                'description' => 'Accounting and financial management',
                'permissions' => [
                    'view-accounts', 'create-accounts', 'edit-accounts',
                    'view-journal-entries', 'create-journal-entries', 'edit-journal-entries',
                    'view-financial-reports', 'view-invoices', 'create-invoices', 'edit-invoices',
                    'view-purchase-invoices', 'create-purchase-invoices', 'edit-purchase-invoices',
                    'view-customers', 'view-suppliers', 'view-reports',
                ],
            ],

            'Sales Manager' => [
                'description' => 'Sales and customer management',
                'permissions' => [
                    'view-customers', 'create-customers', 'edit-customers',
                    'view-sales-orders', 'create-sales-orders', 'edit-sales-orders',
                    'view-invoices', 'create-invoices', 'edit-invoices',
                    'view-items', 'view-stock', 'view-reports',
                ],
            ],

            'Purchase Manager' => [
                'description' => 'Purchasing and supplier management',
                'permissions' => [
                    'view-suppliers', 'create-suppliers', 'edit-suppliers',
                    'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders',
                    'view-purchase-invoices', 'create-purchase-invoices', 'edit-purchase-invoices',
                    'view-items', 'view-stock', 'view-reports',
                ],
            ],

            'Inventory Manager' => [
                'description' => 'Inventory and stock management',
                'permissions' => [
                    'view-items', 'create-items', 'edit-items',
                    'view-stock', 'create-stock-entries', 'edit-stock-entries',
                    'view-customers', 'view-suppliers', 'view-reports',
                ],
            ],

            'HR Manager' => [
                'description' => 'Human resources management',
                'permissions' => [
                    'view-employees', 'create-employees', 'edit-employees',
                    'view-payroll', 'create-payroll', 'edit-payroll',
                    'view-users', 'create-users', 'edit-users',
                    'view-reports',
                ],
            ],

            'Project Manager' => [
                'description' => 'Project and task management',
                'permissions' => [
                    'view-projects', 'create-projects', 'edit-projects',
                    'view-tasks', 'create-tasks', 'edit-tasks',
                    'view-employees', 'view-reports',
                ],
            ],

            'Sales Executive' => [
                'description' => 'Sales operations and customer interaction',
                'permissions' => [
                    'view-customers', 'create-customers', 'edit-customers',
                    'view-sales-orders', 'create-sales-orders', 'edit-sales-orders',
                    'view-invoices', 'create-invoices',
                    'view-items', 'view-stock',
                ],
            ],

            'Accountant Assistant' => [
                'description' => 'Limited accounting operations',
                'permissions' => [
                    'view-accounts', 'view-journal-entries', 'create-journal-entries',
                    'view-invoices', 'view-purchase-invoices',
                    'view-customers', 'view-suppliers',
                ],
            ],

            'Employee' => [
                'description' => 'Basic employee access',
                'permissions' => [
                    'view-tasks', 'edit-tasks',
                    'view-projects',
                ],
            ],
        ];

        // Create roles and assign permissions
        foreach ($rolesData as $roleName => $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // Assign permissions to role
            $role->syncPermissions($roleData['permissions']);
        }
    }
}
