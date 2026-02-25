<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            // Basic Documents
            [
                'name' => 'Interview Sheet',
                'code' => 'interview_sheet',
                'description' => 'Initial interview sheet with basic customer information and loan request details',
                'category' => 'basic',
                'display_order' => 1,
            ],
            [
                'name' => 'National ID Copy',
                'code' => 'national_id',
                'description' => 'Copy of national identification card or passport',
                'category' => 'basic',
                'display_order' => 2,
            ],
            [
                'name' => 'Passport Photos',
                'code' => 'passport_photos',
                'description' => 'Recent passport-size photographs',
                'category' => 'basic',
                'display_order' => 3,
            ],
            [
                'name' => 'Marriage Certificate',
                'code' => 'marriage_certificate',
                'description' => 'Official marriage certificate (if applicable)',
                'category' => 'legal',
                'display_order' => 4,
            ],

            // Financial Documents
            [
                'name' => 'Bank Statement',
                'code' => 'bank_statement',
                'description' => 'Bank statements for income analysis (12 months for salaried, 24 months for self-employed)',
                'category' => 'financial',
                'display_order' => 10,
            ],
            [
                'name' => 'Cash Margin Evidence',
                'code' => 'cash_margin',
                'description' => 'Proof of cash contribution/down payment',
                'category' => 'financial',
                'display_order' => 11,
            ],
            [
                'name' => 'Salary Slips',
                'code' => 'salary_slips',
                'description' => 'Recent salary slips (last 3 months)',
                'category' => 'financial',
                'display_order' => 12,
            ],

            // Employment Documents (Salaried)
            [
                'name' => 'Employer Introduction Letter',
                'code' => 'employer_intro_letter',
                'description' => 'Letter of introduction from employer confirming employment',
                'category' => 'employment',
                'display_order' => 20,
            ],
            [
                'name' => 'Employment Contract',
                'code' => 'employment_contract',
                'description' => 'Current employment contract or appointment letter',
                'category' => 'employment',
                'display_order' => 21,
            ],
            [
                'name' => 'Call Report',
                'code' => 'call_report',
                'description' => 'Verification call report from employer',
                'category' => 'employment',
                'display_order' => 22,
            ],

            // Business Documents (Self-Employed)
            [
                'name' => 'Audited Accounts',
                'code' => 'audited_accounts',
                'description' => 'Audited financial statements (last 3 years)',
                'category' => 'business',
                'display_order' => 30,
            ],
            [
                'name' => 'Business Registration Certificate',
                'code' => 'business_registration',
                'description' => 'Certificate of business registration',
                'category' => 'business',
                'display_order' => 31,
            ],
            [
                'name' => 'TIN Certificate',
                'code' => 'tin_certificate',
                'description' => 'Tax Identification Number certificate',
                'category' => 'business',
                'display_order' => 32,
            ],
            [
                'name' => 'Tax Clearance Certificate',
                'code' => 'tax_clearance',
                'description' => 'Current tax clearance certificate',
                'category' => 'business',
                'display_order' => 33,
            ],
            [
                'name' => 'Business License',
                'code' => 'business_license',
                'description' => 'Valid business operating license',
                'category' => 'business',
                'display_order' => 34,
            ],
            [
                'name' => 'MEMARTS',
                'code' => 'memarts',
                'description' => 'Memorandum and Articles of Association',
                'category' => 'business',
                'display_order' => 35,
            ],

            // Property Documents
            [
                'name' => 'Title Deed',
                'code' => 'title_deed',
                'description' => 'Original title deed or certificate of occupancy',
                'category' => 'property',
                'display_order' => 40,
            ],
            [
                'name' => 'Property Valuation Report',
                'code' => 'valuation_report',
                'description' => 'Professional property valuation report',
                'category' => 'property',
                'display_order' => 41,
            ],
            [
                'name' => 'Building Permit',
                'code' => 'building_permit',
                'description' => 'Approved building permit (for construction/completion)',
                'category' => 'property',
                'display_order' => 42,
            ],
            [
                'name' => 'Approved Drawings',
                'code' => 'approved_drawings',
                'description' => 'Approved architectural/engineering drawings',
                'category' => 'property',
                'display_order' => 43,
            ],
            [
                'name' => 'Bills of Quantities',
                'code' => 'boq',
                'description' => 'Detailed bills of quantities from quantity surveyor',
                'category' => 'property',
                'display_order' => 44,
            ],

            // Legal & Insurance Documents
            [
                'name' => 'Loan Application Form',
                'code' => 'loan_application_form',
                'description' => 'Completed and signed loan application form',
                'category' => 'legal',
                'display_order' => 50,
            ],
            [
                'name' => 'Property Insurance',
                'code' => 'property_insurance',
                'description' => 'Property insurance policy document',
                'category' => 'legal',
                'display_order' => 51,
            ],
            [
                'name' => 'Life Assurance Policy',
                'code' => 'life_assurance',
                'description' => 'Life insurance/assurance policy',
                'category' => 'legal',
                'display_order' => 52,
            ],
            [
                'name' => 'Statutory Taxes Evidence',
                'code' => 'statutory_taxes',
                'description' => 'Proof of payment of statutory taxes and levies',
                'category' => 'legal',
                'display_order' => 53,
            ],

            // Additional Documents
            [
                'name' => 'Offer Letter (Previous Loan)',
                'code' => 'offer_letter',
                'description' => 'Offer letter from previous lender (for refinance/equity release)',
                'category' => 'other',
                'display_order' => 60,
            ],
            [
                'name' => 'Two Referees',
                'code' => 'referees',
                'description' => 'Contact details of two personal/professional referees',
                'category' => 'other',
                'display_order' => 61,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['code' => $documentType['code']],
                $documentType
            );
        }

        $this->command->info('Document types seeded successfully!');
    }
}
