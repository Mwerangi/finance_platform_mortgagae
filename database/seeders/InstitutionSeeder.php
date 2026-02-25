<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default provider institution for system administration
        $provider = Institution::create([
            'name' => 'White-Label Platform Provider',
            'slug' => 'platform-provider',
            'code' => 'PROV001',
            'description' => 'System provider institution for platform administration',
            'email' => 'provider@mortgage-platform.com',
            'phone' => '+255 700 000 000',
            'address' => 'Dar es Salaam, Tanzania',
            'city' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency' => 'TZS',
            'date_format' => 'Y-m-d',
            'status' => 'active',
            'activated_at' => now(),
            'branding' => [
                'primary_color' => '#1E40AF',
                'secondary_color' => '#64748B',
                'accent_color' => '#10B981',
                'email_from_name' => 'White-Label Platform Provider',
                'email_from_address' => 'noreply@mortgage-platform.com',
            ],
            'settings' => [
                'features' => ['analytics', 'collections', 'reports', 'audit'],
                'loan_account_prefix' => 'LN',
                'customer_id_prefix' => 'CUS',
                'max_file_size_mb' => 50,
                'allowed_file_types' => ['xlsx', 'csv', 'xls'],
                'require_kyc_verification' => true,
                'auto_run_analytics' => true,
            ],
        ]);

        // Create a demo institution for testing
        $demo = Institution::create([
            'name' => 'Demo Microfinance Institution',
            'slug' => 'demo-mfi',
            'code' => 'DEMO001',
            'description' => 'Demo institution for testing and demonstration purposes',
            'email' => 'contact@demo-mfi.co.tz',
            'phone' => '+255 700 123 456',
            'address' => 'Samora Avenue, Dar es Salaam',
            'city' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency' => 'TZS',
            'date_format' => 'd/m/Y',
            'status' => 'active',
            'activated_at' => now(),
            'branding' => [
                'primary_color' => '#059669',
                'secondary_color' => '#6366F1',
                'accent_color' => '#F59E0B',
                'email_from_name' => 'Demo MFI',
                'email_from_address' => 'noreply@demo-mfi.co.tz',
            ],
            'settings' => [
                'features' => ['analytics', 'collections'],
                'loan_account_prefix' => 'DEMO',
                'customer_id_prefix' => 'CUST',
                'max_file_size_mb' => 20,
                'allowed_file_types' => ['xlsx'],
                'require_kyc_verification' => true,
                'auto_run_analytics' => false,
            ],
        ]);

        $this->command->info("Created institutions:");
        $this->command->info("- {$provider->name} ({$provider->code})");
        $this->command->info("- {$demo->name} ({$demo->code})");
    }
}
