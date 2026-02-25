<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoInstitution = Institution::where('code', 'DEMO001')->first();
        
        if (!$demoInstitution) {
            $this->command->warn('Demo institution not found. Skipping customer seeding.');
            return;
        }

        $creditManager = User::where('email', 'admin@demo-mfi.co.tz')->first();

        // Customer 1: Salary-based customer with complete KYC
        $customer1 = Customer::create([
            'institution_id' => $demoInstitution->id,
            'customer_type' => CustomerType::SALARY,
            'first_name' => 'Juma',
            'middle_name' => 'Hassan',
            'last_name' => 'Mwamba',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male',
            'marital_status' => 'married',
            'national_id' => '19850515-12345-00001',
            'tin' => '123-456-789',
            'phone_primary' => '+255712345678',
            'phone_secondary' => '+255622345678',
            'email' => 'juma.mwamba@example.com',
            'physical_address' => 'Plot 123, Mikocheni, Kinondoni',
            'city' => 'Dar es Salaam',
            'region' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'employer_name' => 'Tanzania Revenue Authority',
            'occupation' => 'Tax Officer',
            'industry' => 'Government',
            'employment_start_date' => '2010-03-01',
            'next_of_kin_name' => 'Fatuma Mwamba',
            'next_of_kin_relationship' => 'Spouse',
            'next_of_kin_phone' => '+255713456789',
            'next_of_kin_address' => 'Same as customer',
            'status' => 'active',
        ]);

        if ($creditManager) {
            $customer1->verifyKyc($creditManager->id);
        }

        // Customer 2: Business customer with KYC pending
        $customer2 = Customer::create([
            'institution_id' => $demoInstitution->id,
            'customer_type' => CustomerType::BUSINESS,
            'first_name' => 'Amina',
            'last_name' => 'Salim',
            'date_of_birth' => '1978-11-22',
            'gender' => 'female',
            'marital_status' => 'single',
            'national_id' => '19781122-54321-00002',
            'phone_primary' => '+255754567890',
            'email' => 'amina.salim@business.co.tz',
            'physical_address' => 'Kariakoo Market, Ilala',
            'city' => 'Dar es Salaam',
            'region' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'business_name' => 'Amina Textiles Ltd',
            'occupation' => 'Business Owner',
            'industry' => 'Retail',
            'employment_start_date' => '2005-01-10',
            'next_of_kin_name' => 'Salim Ahmed',
            'next_of_kin_relationship' => 'Brother',
            'next_of_kin_phone' => '+255765678901',
            'next_of_kin_address' => 'Temeke, Dar es Salaam',
            'status' => 'active',
        ]);

        // Customer 3: Mixed income customer with complete profile
        $customer3 = Customer::create([
            'institution_id' => $demoInstitution->id,
            'customer_type' => CustomerType::MIXED,
            'first_name' => 'Richard',
            'middle_name' => 'John',
            'last_name' => 'Moshi',
            'date_of_birth' => '1990-07-08',
            'gender' => 'male',
            'marital_status' => 'married',
            'national_id' => '19900708-67890-00003',
            'tin' => '987-654-321',
            'phone_primary' => '+255776789012',
            'email' => 'richard.moshi@gmail.com',
            'physical_address' => 'Mbezi Beach, Kinondoni',
            'city' => 'Dar es Salaam',
            'region' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'employer_name' => 'Vodacom Tanzania',
            'business_name' => 'Moshi Consultancy Services',
            'occupation' => 'IT Consultant',
            'industry' => 'Technology',
            'employment_start_date' => '2015-06-01',
            'next_of_kin_name' => 'Grace Moshi',
            'next_of_kin_relationship' => 'Spouse',
            'next_of_kin_phone' => '+255787890123',
            'next_of_kin_address' => 'Same as customer',
            'status' => 'active',
        ]);

        if ($creditManager) {
            $customer3->verifyKyc($creditManager->id);
        }

        // Customer 4: Salary customer with incomplete profile
        $customer4 = Customer::create([
            'institution_id' => $demoInstitution->id,
            'customer_type' => CustomerType::SALARY,
            'first_name' => 'Sarah',
            'last_name' => 'Kimario',
            'date_of_birth' => '1995-03-20',
            'gender' => 'female',
            'phone_primary' => '+255798901234',
            'email' => 'sarah.kimario@example.com',
            'city' => 'Arusha',
            'region' => 'Arusha',
            'country' => 'Tanzania',
            'employer_name' => 'Kilimanjaro Hotel',
            'occupation' => 'Accountant',
            'status' => 'active',
        ]);

        // Customer 5: Business customer - suspended
        $customer5 = Customer::create([
            'institution_id' => $demoInstitution->id,
            'customer_type' => CustomerType::BUSINESS,
            'first_name' => 'Daniel',
            'last_name' => 'Mtega',
            'date_of_birth' => '1982-09-14',
            'gender' => 'male',
            'national_id' => '19820914-11111-00005',
            'phone_primary' => '+255700112233',
            'physical_address' => 'Mwanza City Center',
            'city' => 'Mwanza',
            'region' => 'Mwanza',
            'country' => 'Tanzania',
            'business_name' => 'Mtega Hardware',
            'occupation' => 'Business Owner',
            'industry' => 'Construction Supplies',
            'status' => 'suspended',
        ]);

        $this->command->info('5 demo customers created successfully.');

        // Add sample KYC documents for customer 1 (verified)
        $customer1->kycDocuments()->create([
            'institution_id' => $demoInstitution->id,
            'document_type' => DocumentType::NATIONAL_ID,
            'document_name' => 'National ID - Juma Mwamba',
            'file_path' => 'dummy/path/national_id_customer1.pdf',
            'file_name' => 'national_id.pdf',
            'file_size' => 524288, // 512KB
            'file_type' => 'application/pdf',
            'document_number' => '19850515-12345-00001',
            'uploaded_by' => $creditManager?->id,
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by' => $creditManager?->id,
            'verified_at' => now(),
        ]);

        $customer1->kycDocuments()->create([
            'institution_id' => $demoInstitution->id,
            'document_type' => DocumentType::BANK_STATEMENT,
            'document_name' => 'Bank Statement - 6 Months',
            'file_path' => 'dummy/path/bank_statement_customer1.pdf',
            'file_name' => 'bank_statement_6months.pdf',
            'file_size' => 1048576, // 1MB
            'file_type' => 'application/pdf',
            'uploaded_by' => $creditManager?->id,
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by' => $creditManager?->id,
            'verified_at' => now(),
        ]);

        // Add sample KYC documents for customer 2 (pending)
        $customer2->kycDocuments()->create([
            'institution_id' => $demoInstitution->id,
            'document_type' => DocumentType::NATIONAL_ID,
            'document_name' => 'National ID - Amina Salim',
            'file_path' => 'dummy/path/national_id_customer2.pdf',
            'file_name' => 'national_id.pdf',
            'file_size' => 612352, // ~600KB
            'file_type' => 'application/pdf',
            'document_number' => '19781122-54321-00002',
            'uploaded_by' => $creditManager?->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $customer2->kycDocuments()->create([
            'institution_id' => $demoInstitution->id,
            'document_type' => DocumentType::BUSINESS_LICENSE,
            'document_name' => 'Business License - Amina Textiles',
            'file_path' => 'dummy/path/business_license_customer2.pdf',
            'file_name' => 'business_license.pdf',
            'file_size' => 409600, // 400KB
            'file_type' => 'application/pdf',
            'document_number' => 'BL-2005-12345',
            'expiry_date' => '2026-12-31',
            'uploaded_by' => $creditManager?->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        // Add sample KYC documents for customer 3 (verified)
        $customer3->kycDocuments()->create([
            'institution_id' => $demoInstitution->id,
            'document_type' => DocumentType::PASSPORT,
            'document_name' => 'Passport - Richard Moshi',
            'file_path' => 'dummy/path/passport_customer3.pdf',
            'file_name' => 'passport.pdf',
            'file_size' => 720896, // ~700KB
            'file_type' => 'application/pdf',
            'document_number' => 'AB1234567',
            'expiry_date' => '2028-06-30',
            'uploaded_by' => $creditManager?->id,
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by' => $creditManager?->id,
            'verified_at' => now(),
        ]);

        $this->command->info('KYC documents created for demo customers.');
    }
}
