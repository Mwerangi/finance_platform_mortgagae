<?php

namespace Database\Seeders;

use App\Enums\InterestModel;
use App\Enums\LoanProductStatus;
use App\Models\Institution;
use App\Models\LoanProduct;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoInstitution = Institution::where('code', 'DEMO001')->first();

        if (!$demoInstitution) {
            $this->command->warn('Demo institution not found. Skipping loan product seeding.');
            return;
        }

        $products = [
            [
                'institution_id' => $demoInstitution->id,
                'name' => 'Salary Advance Loan - Reducing Balance',
                'code' => 'SAL-RB-001',
                'description' => 'Short-term salary advance loan with reducing balance interest calculation.',
                'interest_model' => InterestModel::REDUCING_BALANCE,
                'annual_interest_rate' => 18.00,
                'rate_type' => 'fixed',
                'min_tenure_months' => 3,
                'max_tenure_months' => 12,
                'min_loan_amount' => 500000.00,
                'max_loan_amount' => 5000000.00,
                'max_ltv_percentage' => null,
                'max_dsr_salary_percentage' => 40.00,
                'max_dti_percentage' => 50.00,
                'business_safety_factor' => null,
                'max_dsr_business_percentage' => null,
                'status' => LoanProductStatus::ACTIVE,
                'activated_at' => now(),
                'fees' => [
                    'processing_fee' => [
                        'type' => 'percentage',
                        'value' => 2.5,
                        'min' => 50000,
                        'max' => 500000,
                    ],
                    'appraisal_fee' => null,
                    'insurance_fee' => [
                        'type' => 'percentage',
                        'value' => 0.5,
                    ],
                    'other_fees' => [],
                ],
                'penalties' => [
                    'late_payment' => [
                        'type' => 'percentage',
                        'value' => 2.0,
                        'per' => 'installment',
                    ],
                    'early_repayment' => [
                        'type' => 'percentage',
                        'value' => 1.0,
                        'of' => 'outstanding',
                    ],
                ],
                'credit_policy' => [
                    'min_volatility_score' => 0.25,
                    'min_income_stability_score' => 0.6,
                    'min_account_age_months' => 6,
                    'max_debt_exposure_ratio' => 0.5,
                    'risk_grade_thresholds' => [
                        'A' => ['min_score' => 80, 'max_ltv' => 90],
                        'B' => ['min_score' => 65, 'max_ltv' => 80],
                        'C' => ['min_score' => 50, 'max_ltv' => 70],
                        'D' => ['min_score' => 0, 'max_ltv' => 60],
                    ],
                ],
            ],
            [
                'institution_id' => $demoInstitution->id,
                'name' => 'Mortgage Loan - Reducing Balance',
                'code' => 'MTG-RB-001',
                'description' => 'Long-term mortgage loan for property purchase with reducing balance calculation.',
                'interest_model' => InterestModel::REDUCING_BALANCE,
                'annual_interest_rate' => 15.00,
                'rate_type' => 'fixed',
                'min_tenure_months' => 12,
                'max_tenure_months' => 240,
                'min_loan_amount' => 5000000.00,
                'max_loan_amount' => 500000000.00,
                'max_ltv_percentage' => 80.00,
                'max_dsr_salary_percentage' => 35.00,
                'max_dti_percentage' => 45.00,
                'business_safety_factor' => null,
                'max_dsr_business_percentage' => null,
                'status' => LoanProductStatus::ACTIVE,
                'activated_at' => now(),
                'fees' => [
                    'processing_fee' => [
                        'type' => 'percentage',
                        'value' => 1.5,
                        'min' => 200000,
                        'max' => 3000000,
                    ],
                    'appraisal_fee' => [
                        'type' => 'fixed',
                        'value' => 500000,
                    ],
                    'insurance_fee' => [
                        'type' => 'percentage',
                        'value' => 0.8,
                    ],
                    'other_fees' => [
                        [
                            'name' => 'Legal Fee',
                            'type' => 'fixed',
                            'value' => 1000000,
                        ],
                        [
                            'name' => 'Valuation Fee',
                            'type' => 'percentage',
                            'value' => 0.3,
                        ],
                    ],
                ],
                'penalties' => [
                    'late_payment' => [
                        'type' => 'percentage',
                        'value' => 3.0,
                        'per' => 'installment',
                    ],
                    'early_repayment' => [
                        'type' => 'percentage',
                        'value' => 2.0,
                        'of' => 'outstanding',
                    ],
                ],
                'credit_policy' => [
                    'min_volatility_score' => 0.2,
                    'min_income_stability_score' => 0.7,
                    'min_account_age_months' => 12,
                    'max_debt_exposure_ratio' => 0.45,
                    'risk_grade_thresholds' => [
                        'A' => ['min_score' => 85, 'max_ltv' => 80],
                        'B' => ['min_score' => 70, 'max_ltv' => 75],
                        'C' => ['min_score' => 55, 'max_ltv' => 70],
                        'D' => ['min_score' => 0, 'max_ltv' => 65],
                    ],
                ],
            ],
            [
                'institution_id' => $demoInstitution->id,
                'name' => 'Business Loan - Flat Rate',
                'code' => 'BUS-FR-001',
                'description' => 'Business expansion loan with flat rate interest for predictable payments.',
                'interest_model' => InterestModel::FLAT_RATE,
                'annual_interest_rate' => 20.00,
                'rate_type' => 'fixed',
                'min_tenure_months' => 6,
                'max_tenure_months' => 24,
                'min_loan_amount' => 1000000.00,
                'max_loan_amount' => 30000000.00,
                'max_ltv_percentage' => null,
                'max_dsr_salary_percentage' => null,
                'max_dti_percentage' => null,
                'business_safety_factor' => 0.60,
                'max_dsr_business_percentage' => 50.00,
                'status' => LoanProductStatus::ACTIVE,
                'activated_at' => now(),
                'fees' => [
                    'processing_fee' => [
                        'type' => 'percentage',
                        'value' => 3.0,
                        'min' => 100000,
                        'max' => 1000000,
                    ],
                    'appraisal_fee' => [
                        'type' => 'fixed',
                        'value' => 200000,
                    ],
                    'insurance_fee' => null,
                    'other_fees' => [],
                ],
                'penalties' => [
                    'late_payment' => [
                        'type' => 'percentage',
                        'value' => 5.0,
                        'per' => 'installment',
                    ],
                    'early_repayment' => null,
                ],
                'credit_policy' => [
                    'min_volatility_score' => 0.3,
                    'min_income_stability_score' => 0.5,
                    'min_account_age_months' => 6,
                    'max_debt_exposure_ratio' => 0.5,
                    'risk_grade_thresholds' => [
                        'A' => ['min_score' => 75, 'max_ltv' => 100],
                        'B' => ['min_score' => 60, 'max_ltv' => 100],
                        'C' => ['min_score' => 45, 'max_ltv' => 100],
                        'D' => ['min_score' => 0, 'max_ltv' => 100],
                    ],
                ],
            ],
            [
                'institution_id' => $demoInstitution->id,
                'name' => 'Emergency Loan - Flat Rate',
                'code' => 'EMG-FR-001',
                'description' => 'Quick emergency loan with simple flat rate calculation for urgent needs.',
                'interest_model' => InterestModel::FLAT_RATE,
                'annual_interest_rate' => 24.00,
                'rate_type' => 'fixed',
                'min_tenure_months' => 1,
                'max_tenure_months' => 6,
                'min_loan_amount' => 100000.00,
                'max_loan_amount' => 2000000.00,
                'max_ltv_percentage' => null,
                'max_dsr_salary_percentage' => 45.00,
                'max_dti_percentage' => 55.00,
                'business_safety_factor' => null,
                'max_dsr_business_percentage' => null,
                'status' => LoanProductStatus::ACTIVE,
                'activated_at' => now(),
                'fees' => [
                    'processing_fee' => [
                        'type' => 'fixed',
                        'value' => 25000,
                    ],
                    'appraisal_fee' => null,
                    'insurance_fee' => null,
                    'other_fees' => [],
                ],
                'penalties' => [
                    'late_payment' => [
                        'type' => 'percentage',
                        'value' => 5.0,
                        'per' => 'installment',
                    ],
                    'early_repayment' => null,
                ],
                'credit_policy' => [
                    'min_volatility_score' => 0.35,
                    'min_income_stability_score' => 0.4,
                    'min_account_age_months' => 3,
                    'max_debt_exposure_ratio' => 0.55,
                    'risk_grade_thresholds' => [
                        'A' => ['min_score' => 70, 'max_ltv' => 100],
                        'B' => ['min_score' => 55, 'max_ltv' => 100],
                        'C' => ['min_score' => 40, 'max_ltv' => 100],
                        'D' => ['min_score' => 0, 'max_ltv' => 100],
                    ],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = LoanProduct::create($productData);
            $this->command->info("Created loan product: {$product->name} ({$product->code})");
        }

        $this->command->info('Loan products seeded successfully!');
    }
}
