<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class InitialSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the initial system setup:
     * - Default company
     * - Super admin user
     * - Assigns super admin to default company
     */
    public function run(): void
    {
        // Create default company
        $company = Company::firstOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Default Company',
                'description' => 'Default company created during system setup',
                'email' => 'admin@company.com',
                'phone' => '+1-555-0123',
                'address' => '123 Business St, Suite 100, Business City, BC',
                'city' => 'Business City',
                'state' => 'BC',
                'country' => 'United States',
                'postal_code' => '12345',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'is_active' => true,
            ]
        );

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'Super Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // Change this in production!
            ]
        );

        // Get the Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        // Assign Super Admin role to user
        $superAdmin->assignRole($superAdminRole);

        // Attach user to company with Super Admin role (via pivot table)
        $superAdmin->companies()->attach($company->id, [
            'role_id' => $superAdminRole->id,
            'is_default' => true,
        ]);

        $this->command->info('✅ Initial setup completed:');
        $this->command->info("   📍 Company: {$company->name} ({$company->code})");
        $this->command->info("   👤 Super Admin: {$superAdmin->email} (password: password)");
        $this->command->info('   🔐 Super Admin role assigned');
        $this->command->info('   🏢 User assigned to default company');
        $this->command->warn('⚠️  Remember to change the default password in production!');
    }
}
