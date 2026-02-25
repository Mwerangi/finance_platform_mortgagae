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
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Basic Information
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Interest Rate Configuration
            $table->string('interest_model'); // 'reducing_balance', 'flat_rate'
            $table->decimal('annual_interest_rate', 5, 2); // e.g., 24.50 for 24.5%
            $table->string('rate_type')->default('fixed'); // 'fixed', 'variable' (future)
            
            // Tenure Configuration (in months)
            $table->integer('min_tenure_months');
            $table->integer('max_tenure_months');
            
            // Loan Amount Limits
            $table->decimal('min_loan_amount', 15, 2);
            $table->decimal('max_loan_amount', 15, 2);
            
            // LTV (Loan-to-Value) - for secured loans
            $table->decimal('max_ltv_percentage', 5, 2)->nullable(); // e.g., 80.00 for 80%
            
            // DSR (Debt Service Ratio) - for salary clients
            $table->decimal('max_dsr_salary_percentage', 5, 2)->nullable(); // e.g., 40.00 for 40%
            
            // DTI (Debt-to-Income) - total debt exposure
            $table->decimal('max_dti_percentage', 5, 2)->nullable(); // e.g., 50.00 for 50%
            
            // Business Income Configuration
            $table->decimal('business_safety_factor', 5, 2)->nullable(); // e.g., 0.60 for 60%
            $table->decimal('max_dsr_business_percentage', 5, 2)->nullable(); // e.g., 50.00 for 50%
            
            // Fees Configuration (JSON)
            $table->json('fees')->nullable();
            // Structure: {
            //   "processing_fee": {"type": "percentage", "value": 2.5, "min": 50000, "max": 500000},
            //   "appraisal_fee": {"type": "fixed", "value": 100000},
            //   "insurance_fee": {"type": "percentage", "value": 1.0},
            //   "other_fees": [
            //     {"name": "Legal Fee", "type": "fixed", "value": 200000}
            //   ]
            // }
            
            // Penalties Configuration (JSON)
            $table->json('penalties')->nullable();
            // Structure: {
            //   "late_payment": {"type": "percentage", "value": 2.0, "per": "installment"},
            //   "early_repayment": {"type": "percentage", "value": 3.0, "of": "outstanding"}
            // }
            
            // Credit Policy Rules (JSON)
            $table->json('credit_policy')->nullable();
            // Structure: {
            //   "min_volatility_score": 0.3,
            //   "min_income_stability_score": 0.5,
            //   "min_account_age_months": 6,
            //   "max_debt_exposure_ratio": 0.5,
            //   "risk_grade_thresholds": {
            //     "A": {"min_score": 80, "max_ltv": 90},
            //     "B": {"min_score": 65, "max_ltv": 80},
            //     "C": {"min_score": 50, "max_ltv": 70},
            //     "D": {"min_score": 0, "max_ltv": 60}
            //   }
            // }
            
            // Product Status
            $table->string('status')->default('draft'); // draft, active, inactive, archived
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('institution_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
