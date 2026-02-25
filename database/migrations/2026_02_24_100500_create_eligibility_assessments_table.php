<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eligibility_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('loan_product_id')->constrained()->onDelete('cascade');
            $table->foreignId('statement_analytics_id')->nullable()->constrained('statement_analytics')->onDelete('set null');
            
            // Assessment metadata
            $table->string('assessment_version', 50)->default('1.0'); // For tracking algorithm changes
            $table->enum('assessment_type', ['initial', 'rerun', 'stress_test'])->default('initial');
            
            // Requested loan details
            $table->decimal('requested_amount', 15, 2);
            $table->integer('requested_tenure_months');
            $table->decimal('property_value', 15, 2)->nullable();
            
            // Income & Debt Analysis
            $table->enum('income_classification', ['salary', 'business', 'mixed', 'irregular', 'unknown']);
            $table->decimal('gross_monthly_income', 15, 2);
            $table->decimal('net_monthly_income', 15, 2);
            $table->decimal('income_stability_score', 5, 2)->default(0); // 0-100
            $table->decimal('total_monthly_debt', 15, 2)->default(0);
            $table->integer('detected_debt_count')->default(0);
            
            // Ratios & Calculations
            $table->decimal('dti_ratio', 5, 2)->nullable(); // Debt to Income (%)
            $table->decimal('dsr_ratio', 5, 2)->nullable(); // Debt Service Ratio (%)
            $table->decimal('ltv_ratio', 5, 2)->nullable(); // Loan to Value (%)
            $table->decimal('proposed_installment', 15, 2);
            $table->decimal('net_disposable_income', 15, 2); // After existing debts
            $table->decimal('net_surplus_after_loan', 15, 2); // After proposed loan
            $table->decimal('business_safety_factor', 5, 2)->nullable(); // For business income (e.g., 0.7 = 70%)
            
            // Maximum Loan Calculations
            $table->decimal('max_installment_from_income', 15, 2);
            $table->decimal('max_loan_from_affordability', 15, 2);
            $table->decimal('max_loan_from_ltv', 15, 2)->nullable();
            $table->decimal('final_max_loan', 15, 2);
            $table->integer('optimal_tenure_months')->nullable();
            
            // Risk Assessment
            $table->enum('risk_grade', ['A', 'B', 'C', 'D', 'E'])->default('C');
            $table->decimal('risk_score', 5, 2)->default(50); // 0-100
            $table->json('risk_factors')->nullable(); // Array of risk flags
            $table->decimal('cash_flow_volatility', 5, 2)->default(0);
            
            // Decision Logic
            $table->enum('system_decision', ['eligible', 'conditional', 'outside_policy', 'declined'])->default('conditional');
            $table->text('decision_reason')->nullable();
            $table->json('policy_breaches')->nullable(); // Array of failed policy checks
            $table->json('conditions')->nullable(); // Array of conditions if approved
            $table->boolean('is_recommendable')->default(false); // Should we recommend approval?
            
            // Stress Test Data (if applicable)
            $table->boolean('is_stress_test')->default(false);
            $table->string('stress_scenario', 100)->nullable(); // e.g., 'income_drop_20', 'rate_increase_3'
            $table->json('stress_test_params')->nullable(); // JSON of stress parameters
            $table->decimal('stressed_installment', 15, 2)->nullable();
            $table->decimal('stressed_net_surplus', 15, 2)->nullable();
            $table->boolean('passes_stress_test')->nullable();
            
            // Amortization Details
            $table->enum('interest_method', ['reducing_balance', 'flat_rate']);
            $table->decimal('interest_rate', 5, 2); // Annual interest rate
            $table->decimal('monthly_interest_rate', 8, 6); // Calculated monthly rate
            $table->decimal('total_interest', 15, 2);
            $table->decimal('total_repayment', 15, 2);
            $table->decimal('effective_apr', 5, 2)->nullable(); // For flat rate comparison
            
            // Processing & Audit
            $table->foreignId('assessed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assessed_at');
            $table->json('calculation_details')->nullable(); // Store intermediate calculations for audit
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('application_id');
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('system_decision');
            $table->index('risk_grade');
            $table->index('assessed_at');
            $table->index(['application_id', 'assessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligibility_assessments');
    }
};
