<?php

namespace App\Services;

use App\Models\BankStatementImport;
use App\Models\EligibilityAssessment;
use App\Models\LoanProduct;
use App\Models\Prospect;
use App\Models\StatementAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProspectService
{
    public function __construct(
        protected EligibilityService $eligibilityService
    ) {}

    /**
     * Create a new prospect from pre-qualification form
     */
    public function createProspect(array $data): Prospect
    {
        return Prospect::create([
            'institution_id' => $data['institution_id'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'id_number' => $data['id_number'],
            'customer_type' => $data['customer_type'],
            'loan_purpose' => $data['loan_purpose'],
            'requested_amount' => $data['requested_amount'],
            'requested_tenure' => $data['requested_tenure'],
            'loan_product_id' => $data['loan_product_id'] ?? null,
            'property_location' => $data['property_location'] ?? null,
            'property_value' => $data['property_value'] ?? null,
            'status' => 'pending',
            'source' => $data['source'] ?? 'web_prequalification',
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Run eligibility assessment for a prospect
     * This is adapted from the Application-based eligibility assessment
     */
    public function runEligibilityAssessment(Prospect $prospect, StatementAnalytics $statementAnalytics): EligibilityAssessment
    {
        DB::beginTransaction();
        try {
            // Get loan product or use default
            $loanProduct = $prospect->loanProduct ?? LoanProduct::active()->first();

            if (!$loanProduct) {
                throw new \Exception('No active loan product available for eligibility assessment.');
            }

            // Create a temporary data structure similar to Application for EligibilityService
            $assessmentData = $this->buildAssessmentData($prospect, $loanProduct, $statementAnalytics);

            // Run the eligibility calculation
            $result = $this->eligibilityService->assessEligibility($assessmentData);

            // Save eligibility assessment
            $assessment = EligibilityAssessment::create([
                'application_id' => null, // No application yet for prospects
                'customer_id' => null, // No customer yet for prospects
                'prospect_id' => $prospect->id,
                'institution_id' => $prospect->institution_id,
                'loan_product_id' => $loanProduct->id,
                'statement_analytics_id' => $statementAnalytics->id,
                'assessment_version' => '1.0',
                'assessment_type' => 'initial', // Use 'initial' instead of 'pre_qualification'
                
                // Request details (from result, not prospect, as they are in the result)
                'requested_amount' => $result['requested_amount'],
 'requested_tenure_months' => $result['requested_tenure_months'],
                'property_value' => $result['property_value'],
                
                // Income analysis
                'income_classification' => $result['income_classification'],
                'gross_monthly_income' => $result['gross_monthly_income'],
                'net_monthly_income' => $result['net_monthly_income'],
                'income_stability_score' => $result['income_stability_score'],
                
                // Debt analysis
                'total_monthly_debt' => $result['total_monthly_debt'],
                'detected_debt_count' => $result['detected_debt_count'],
                
                // Ratios
                'dti_ratio' => $result['dti_ratio'],
                'dsr_ratio' => $result['dsr_ratio'],
                'ltv_ratio' => $result['ltv_ratio'],
                
                // Calculations
                'proposed_installment' => $result['proposed_installment'],
                'net_disposable_income' => $result['net_disposable_income'],
                'net_surplus_after_loan' => $result['net_surplus_after_loan'],
                'business_safety_factor' => $result['business_safety_factor'],
                
                // Max loan calculations
                'max_installment_from_income' => $result['max_installment_from_income'],
                'max_loan_from_affordability' => $result['max_loan_from_affordability'],
                'max_loan_from_ltv' => $result['max_loan_from_ltv'],
                'final_max_loan' => $result['final_max_loan'],
                'optimal_tenure_months' => $result['optimal_tenure_months'],
                
                // Risk assessment
                'risk_grade' => $result['risk_grade'],
                'risk_score' => $result['risk_score'],
                'risk_factors' => $result['risk_factors'],
                'cash_flow_volatility' => $result['cash_flow_volatility'],
                
                // Decision
                'system_decision' => $result['system_decision'],
                'decision_reason' => $result['decision_reason'],
                'policy_breaches' => $result['policy_breaches'],
                'conditions' => $result['conditions'],
                'is_recommendable' => $result['is_recommendable'],
                
                // Interest calculations (from eligibility result)
                'interest_method' => $result['interest_method'] ?? 'reducing_balance',
                'interest_rate' => $result['interest_rate'] ?? 18.0,
                'monthly_interest_rate' => $result['monthly_interest_rate'] ?? 0,
                'total_interest' => $result['total_interest'] ?? 0,
                'total_repayment' => $result['total_repayment'] ?? 0,
                'effective_apr' => $result['effective_apr'] ?? 18.0,
                
                // Metadata
                'assessed_by' => auth()->id(),
                'assessed_at' => now(),
                'calculation_details' => $result,
            ]);

            // Update prospect status based on eligibility result
            $newStatus = match($assessment->system_decision) {
                'eligible' => 'eligibility_passed',
                'conditional' => 'eligibility_passed', // Conditional still passes
                default => 'eligibility_failed',
            };

            $prospect->update([
                'status' => $newStatus,
                'eligibility_assessment_id' => $assessment->id,
            ]);

            DB::commit();

            return $assessment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Prospect eligibility assessment failed', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Build assessment data structure compatible with EligibilityService
     */
    protected function buildAssessmentData(Prospect $prospect, LoanProduct $loanProduct, StatementAnalytics $statementAnalytics): \App\Models\Application
    {
        // Create an unsaved Application model instance for eligibility assessment
        $application = new \App\Models\Application();
        
        // Basic info
        $application->institution_id = $prospect->institution_id;
        $application->loan_product_id = $loanProduct->id;
        $application->requested_amount = $prospect->requested_amount;
        $application->requested_tenure = $prospect->requested_tenure;
        $application->loan_purpose = $prospect->loan_purpose;
        $application->property_value = $prospect->property_value;
        
        // Set relationships without saving
        $application->setRelation('loanProduct', $loanProduct);
        $application->setRelation('statementAnalytics', $statementAnalytics);
        
        // Create a temporary customer object for the eligibility check
        $customer = new \App\Models\Customer();
        $customer->institution_id = $prospect->institution_id;
        
        // Map prospect customer_type to Customer enum values
        $customerType = $this->mapCustomerType($prospect->getRawOriginal('customer_type'));
        $customer->customer_type = $customerType;
        $customer->first_name = $prospect->first_name;
        $customer->middle_name = $prospect->middle_name;
        $customer->last_name = $prospect->last_name;
        
        $application->setRelation('customer', $customer);
        
        return $application;
    }

    /**
     * Convert eligible prospect to customer and create application
     */
    public function convertToCustomer(Prospect $prospect): \App\Models\Customer
    {
        if (!$prospect->canConvertToCustomer()) {
            throw new \Exception('Prospect is not eligible for conversion to customer.');
        }

        DB::beginTransaction();
        try {
            // Create customer from prospect data
            $customer = \App\Models\Customer::create([
                'institution_id' => $prospect->institution_id,
                'prospect_id' => $prospect->id,
                'customer_code' => $this->generateCustomerCode($prospect->institution_id),
                'customer_type' => $prospect->customer_type,
                'first_name' => $prospect->first_name,
                'middle_name' => $prospect->middle_name,
                'last_name' => $prospect->last_name,
                'date_of_birth' => $this->extractDateOfBirthFromNationalId($prospect->id_number),
                'phone_primary' => $prospect->phone,
                'email' => $prospect->email,
                'national_id' => $prospect->id_number,
                'status' => 'pending_kyc',
                'source' => 'from_prospect',
                'notes' => "Converted from prospect (ID: {$prospect->id}) on " . now()->format('Y-m-d H:i:s'),
            ]);

            // Create application from prospect
            // Get loan product ID from prospect or eligibility assessment
            $loanProductId = $prospect->loan_product_id 
                ?? $prospect->eligibilityAssessment?->loan_product_id 
                ?? null;
            
            if (!$loanProductId) {
                throw new \Exception('Cannot create application: No loan product specified.');
            }
            
            $application = \App\Models\Application::create([
                'customer_id' => $customer->id,
                'institution_id' => $prospect->institution_id,
                'loan_product_id' => $loanProductId,
                'created_by' => auth()->id(),
                'status' => 'draft',
                'requested_amount' => $prospect->requested_amount,
                'requested_tenure_months' => $prospect->requested_tenure,
                'property_type' => 'residential', // Default, can be updated later
                'property_value' => $prospect->property_value,
                'property_address' => $prospect->property_location,
                'notes' => "Created from prospect (ID: {$prospect->id}). Pre-qualification completed.",
            ]);

            // Update prospect status
            $prospect->update([
                'status' => 'converted_to_customer',
                'converted_to_customer_id' => $customer->id,
                'converted_at' => now(),
            ]);

            // Link eligibility assessment to customer and application
            if ($prospect->eligibility_assessment_id) {
                $prospect->eligibilityAssessment->update([
                    'customer_id' => $customer->id,
                    'application_id' => $application->id,
                ]);
            }

            // Link bank statement analytics to customer and application
            if ($prospect->statementImport && $prospect->statementImport->analytics) {
                $prospect->statementImport->analytics->update([
                    'customer_id' => $customer->id,
                    'application_id' => $application->id,
                ]);
            }

            Log::info('Prospect converted successfully', [
                'prospect_id' => $prospect->id,
                'customer_id' => $customer->id,
                'application_id' => $application->id,
            ]);

            DB::commit();

            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Prospect to customer conversion failed', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Map prospect customer_type to Customer enum
     */
    protected function mapCustomerType(string $prospectType): \App\Enums\CustomerType
    {
        return match($prospectType) {
            'salaried' => \App\Enums\CustomerType::SALARY,
            'self_employed' => \App\Enums\CustomerType::BUSINESS,
            'salary' => \App\Enums\CustomerType::SALARY,
            'business' => \App\Enums\CustomerType::BUSINESS,
            'mixed' => \App\Enums\CustomerType::MIXED,
            default => \App\Enums\CustomerType::SALARY,
        };
    }

    /**
     * Extract date of birth from Tanzanian national ID
     * Tanzanian National ID format: YYYYMMDD + other digits
     * Example: 19920330151110004 -> 1992-03-30
     */
    protected function extractDateOfBirthFromNationalId(?string $nationalId): ?string
    {
        if (!$nationalId || strlen($nationalId) < 8) {
            return null;
        }

        try {
            // Extract first 8 digits (YYYYMMDD)
            $dateString = substr($nationalId, 0, 8);
            
            // Parse as date
            $year = substr($dateString, 0, 4);
            $month = substr($dateString, 4, 2);
            $day = substr($dateString, 6, 2);
            
            // Validate date
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return sprintf('%s-%s-%s', $year, $month, $day);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract date of birth from national ID', [
                'national_id' => $nationalId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generate customer code
     */
    protected function generateCustomerCode(int $institutionId): string
    {
        $prefix = 'CUST';
        $year = now()->format('Y');
        
        // Get the last customer code for this institution
        $lastCustomer = \App\Models\Customer::where('institution_id', $institutionId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCustomer && preg_match('/(\d+)$/', $lastCustomer->customer_code, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $number);
    }
}
