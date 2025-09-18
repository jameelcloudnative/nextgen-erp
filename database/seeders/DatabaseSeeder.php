<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First create all roles and permissions
        $this->call(RoleSeeder::class);

        // Then create initial setup (super admin + default company)
        $this->call(InitialSetupSeeder::class);
    }
}
