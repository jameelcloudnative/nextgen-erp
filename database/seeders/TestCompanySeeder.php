<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;

class TestCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test companies
        $company1 = Company::create([
            'name' => 'Acme Corporation',
            'code' => 'ACME',
            'description' => 'Test company for development',
            'email' => 'admin@acme.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $company2 = Company::create([
            'name' => 'Beta Industries',
            'code' => 'BETA',
            'description' => 'Second test company',
            'email' => 'admin@beta.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        // Create or get a test role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // Create a test user if none exists
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Attach user to companies with roles
        $user->companies()->attach($company1->id, [
            'role_id' => $adminRole->id,
            'is_default' => true, // Make this the default company
        ]);

        $user->companies()->attach($company2->id, [
            'role_id' => $userRole->id,
            'is_default' => false,
        ]);

        $this->command->info('Test companies and relationships created successfully!');
        $this->command->info("Company 1: {$company1->name} (ID: {$company1->id})");
        $this->command->info("Company 2: {$company2->name} (ID: {$company2->id})");
        $this->command->info("User: {$user->name} ({$user->email})");
    }
}
