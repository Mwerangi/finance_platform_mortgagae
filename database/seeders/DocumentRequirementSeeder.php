<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Based on "First Housing Finance Mortgage Onboarding Checklist"
     */
    public function run(): void
    {
        // Get all document types for reference
        $docTypes = DocumentType::pluck('id', 'code')->toArray();

        // Define requirements based on checklist
        $requirements = [];

        // === STAGE 1: INTERVIEW SHEET STAGE ===
        // Required at interview stage (both customer types, all loan purposes)
        $interviewDocs = ['interview_sheet'];
        foreach ($interviewDocs as $docCode) {
            if (isset($docTypes[$docCode])) {
                $requirements[] = [
                    'document_type_id' => $docTypes[$docCode],
                    'customer_type' => 'both',
                    'loan_purpose' => 'all',
                    'stage' => 'interview',
                    'is_required' => true,
                ];
            }
        }

        // === STAGE 2: ELIGIBILITY STAGE ===
        // Bank Statement - Required for eligibility check
        if (isset($docTypes['bank_statement'])) {
            $requirements[] = [
                'document_type_id' => $docTypes['bank_statement'],
                'customer_type' => 'both',
                'loan_purpose' => 'all',
                'stage' => 'eligibility',
                'is_required' => true,
                'instructions' => 'Salaried: Last 12 months. Self-employed: Last 24 months.',
            ];
        }

        // === STAGE 3: UNDERWRITING STAGE ===
        
        // Common Documents (Both Customer Types, All Loan Purposes)
        $commonDocs = [
            'national_id',
            'passport_photos',
            'title_deed',
            'loan_application_form',
            'cash_margin',
            'valuation_report',
            'property_insurance',
            'life_assurance',
            'statutory_taxes',
        ];

        foreach ($commonDocs as $docCode) {
            if (isset($docTypes[$docCode])) {
                $requirements[] = [
                    'document_type_id' => $docTypes[$docCode],
                    'customer_type' => 'both',
                    'loan_purpose' => 'all',
                    'stage' => 'underwriting',
                    'is_required' => true,
                ];
            }
        }

        // Marriage Certificate (Conditional - if married)
        if (isset($docTypes['marriage_certificate'])) {
            $requirements[] = [
                'document_type_id' => $docTypes['marriage_certificate'],
                'customer_type' => 'both',
                'loan_purpose' => 'all',
                'stage' => 'underwriting',
                'is_required' => false,
                'instructions' => 'Required only if applicant is married.',
            ];
        }

        // === SALARIED CUSTOMER SPECIFIC DOCUMENTS ===
        $salariedDocs = [
            'employer_intro_letter',
            'salary_slips',
            'employment_contract',
            'call_report',
            'referees',
        ];

        foreach ($salariedDocs as $docCode) {
            if (isset($docTypes[$docCode])) {
                $requirements[] = [
                    'document_type_id' => $docTypes[$docCode],
                    'customer_type' => 'salaried',
                    'loan_purpose' => 'all',
                    'stage' => 'underwriting',
                    'is_required' => true,
                ];
            }
        }

        // === SELF-EMPLOYED CUSTOMER SPECIFIC DOCUMENTS ===
        $selfEmployedDocs = [
            'audited_accounts',
            'business_registration',
            'tin_certificate',
            'tax_clearance',
            'business_license',
            'memarts',
        ];

        foreach ($selfEmployedDocs as $docCode) {
            if (isset($docTypes[$docCode])) {
                $requirements[] = [
                    'document_type_id' => $docTypes[$docCode],
                    'customer_type' => 'self_employed',
                    'loan_purpose' => 'all',
                    'stage' => 'underwriting',
                    'is_required' => true,
                ];
            }
        }

        // === LOAN PURPOSE SPECIFIC DOCUMENTS ===

        // Construction & Completion Specific
        $constructionDocs = ['building_permit', 'approved_drawings', 'boq'];
        $constructionPurposes = ['home_construction', 'home_completion'];

        foreach ($constructionDocs as $docCode) {
            if (isset($docTypes[$docCode])) {
                foreach ($constructionPurposes as $purpose) {
                    $requirements[] = [
                        'document_type_id' => $docTypes[$docCode],
                        'customer_type' => 'both',
                        'loan_purpose' => $purpose,
                        'stage' => 'underwriting',
                        'is_required' => true,
                    ];
                }
            }
        }

        // Refinance & Equity Release Specific
        if (isset($docTypes['offer_letter'])) {
            foreach (['home_refinance', 'home_equity_release'] as $purpose) {
                $requirements[] = [
                    'document_type_id' => $docTypes['offer_letter'],
                    'customer_type' => 'both',
                    'loan_purpose' => $purpose,
                    'stage' => 'underwriting',
                    'is_required' => true,
                    'instructions' => 'Offer letter from previous/current lender.',
                ];
            }
        }

        // Insert all requirements
        foreach ($requirements as $requirement) {
            try {
                DocumentRequirement::updateOrCreate(
                    [
                        'institution_id' => $requirement['institution_id'] ?? null,
                        'document_type_id' => $requirement['document_type_id'],
                        'customer_type' => $requirement['customer_type'],
                        'loan_purpose' => $requirement['loan_purpose'],
                        'stage' => $requirement['stage'],
                    ],
                    $requirement
                );
            } catch (\Exception $e) {
                $this->command->error("Failed to create requirement: " . $e->getMessage());
            }
        }

        $this->command->info('Document requirements seeded successfully!');
        $this->command->info('Total requirements created: ' . count($requirements));
    }
}
