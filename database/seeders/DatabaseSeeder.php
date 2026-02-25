<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed institutions first
        $this->call([
            InstitutionSeeder::class,
        ]);

        // Seed RBAC system
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        // Seed loan products
        $this->call([
            LoanProductSeeder::class,
        ]);

        // Seed document types and requirements
        $this->call([
            DocumentTypeSeeder::class,
            DocumentRequirementSeeder::class,
        ]);

        // Create default test user
        $providerInstitution = \App\Models\Institution::where('code', 'PROV001')->first();
        
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'status' => 'active',
            'institution_id' => $providerInstitution->id,
        ]);

        // Assign Provider Super Admin role
        $superAdminRole = \App\Models\Role::where('slug', 'provider-super-admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
            $this->command->info("Super Admin user created: admin@example.com (Institution: {$providerInstitution->name})");
        }

        // Create a demo institution admin
        $demoInstitution = \App\Models\Institution::where('code', 'DEMO001')->first();
        
        $demoAdmin = User::factory()->create([
            'name' => 'Demo Institution Admin',
            'email' => 'admin@demo-mfi.co.tz',
            'status' => 'active',
            'institution_id' => $demoInstitution->id,
        ]);

        $institutionAdminRole = \App\Models\Role::where('slug', 'institution-admin')->first();
        if ($institutionAdminRole) {
            $demoAdmin->assignRole($institutionAdminRole);
            $this->command->info("Demo Admin user created: admin@demo-mfi.co.tz (Institution: {$demoInstitution->name})");
        }

        // Seed customers and KYC documents
        $this->call([
            CustomerSeeder::class,
        ]);
    }
}
