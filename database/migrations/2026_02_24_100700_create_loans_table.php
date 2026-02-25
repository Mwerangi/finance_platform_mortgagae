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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_product_id')->constrained('loan_products')->onDelete('cascade');
            $table->foreignId('underwriting_decision_id')->nullable()->constrained('underwriting_decisions')->onDelete('set null');
            
            // Loan Identification
            $table->string('loan_account_number')->unique();
            $table->string('external_reference_number')->nullable()->index(); // Bank reference
            
            // Loan Status
            $table->enum('status', [
                'pending_disbursement',
                'active',
                'fully_paid',
                'closed',
                'defaulted',
                'written_off',
                'restructured'
            ])->default('pending_disbursement')->index();
            
            // Approved Loan Terms
            $table->decimal('approved_amount', 15, 2);
            $table->integer('approved_tenure_months');
            $table->decimal('approved_interest_rate', 5, 2); // Annual percentage
            $table->enum('interest_method', ['reducing_balance', 'flat_rate'])->default('reducing_balance');
            $table->decimal('monthly_installment', 15, 2);
            $table->decimal('total_interest', 15, 2);
            $table->decimal('total_repayment', 15, 2);
            
            // Disbursement Details
            $table->decimal('disbursed_amount', 15, 2)->nullable();
            $table->date('disbursement_date')->nullable()->index();
            $table->enum('disbursement_method', ['bank_transfer', 'cheque', 'cash', 'mobile_money'])->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->text('disbursement_notes')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('disbursement_approved_at')->nullable();
            $table->foreignId('disbursement_approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Loan Dates
            $table->date('activation_date')->nullable()->index(); // When loan becomes active
            $table->date('first_installment_date')->nullable(); // First payment due date
            $table->date('maturity_date')->nullable()->index(); // Expected final payment date
            $table->date('closure_date')->nullable(); // When loan was closed
            $table->timestamp('closed_at')->nullable();
            
            // Current Balances
            $table->decimal('principal_outstanding', 15, 2)->default(0);
            $table->decimal('interest_outstanding', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->decimal('penalties_outstanding', 15, 2)->default(0);
            $table->decimal('fees_outstanding', 15, 2)->default(0);
            
            // Payment Summary
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('penalties_paid', 15, 2)->default(0);
            $table->decimal('fees_paid', 15, 2)->default(0);
            $table->integer('installments_paid')->default(0);
            $table->integer('installments_remaining')->nullable();
            
            // Arrears & DPD (Days Past Due)
            $table->integer('days_past_due')->default(0)->index();
            $table->decimal('arrears_amount', 15, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 15, 2)->nullable();
            $table->date('next_payment_due_date')->nullable()->index();
            $table->decimal('next_payment_amount', 15, 2)->nullable();
            
            // Aging Bucket
            $table->enum('aging_bucket', [
                'current',      // 0-30 days
                'bucket_30',    // 31-60 days
                'bucket_60',    // 61-90 days
                'bucket_90',    // 91-180 days
                'bucket_180',   // 180+ days
                'npl'           // Non-performing (90+ days)
            ])->default('current')->index();
            
            // Property/Collateral Details
            $table->string('property_type')->nullable();
            $table->decimal('property_value', 15, 2)->nullable();
            $table->text('property_address')->nullable();
            $table->string('property_title_number')->nullable();
            $table->decimal('ltv_ratio', 5, 2)->nullable(); // Loan-to-Value ratio
            $table->text('collateral_description')->nullable();
            $table->json('collateral_documents')->nullable(); // File references
            
            // Insurance Details
            $table->boolean('insurance_required')->default(false);
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->decimal('insurance_premium', 15, 2)->nullable();
            $table->date('insurance_expiry_date')->nullable();
            
            // Early Settlement
            $table->boolean('allows_early_settlement')->default(true);
            $table->decimal('early_settlement_penalty_rate', 5, 2)->nullable();
            $table->date('early_settlement_date')->nullable();
            $table->decimal('early_settlement_amount', 15, 2)->nullable();
            
            // Restructuring
            $table->boolean('is_restructured')->default(false);
            $table->foreignId('original_loan_id')->nullable()->constrained('loans')->onDelete('set null');
            $table->date('restructured_date')->nullable();
            $table->text('restructure_reason')->nullable();
            
            // Write-off
            $table->date('written_off_date')->nullable();
            $table->decimal('written_off_amount', 15, 2)->nullable();
            $table->text('writeoff_reason')->nullable();
            $table->foreignId('written_off_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Risk Classification
            $table->enum('risk_classification', ['performing', 'watch_list', 'substandard', 'doubtful', 'loss'])->default('performing');
            $table->decimal('provision_amount', 15, 2)->default(0);
            $table->decimal('provision_rate', 5, 2)->default(0);
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for common queries
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('loan_product_id');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'aging_bucket']);
            $table->index(['status', 'disbursement_date']);
            $table->index(['status', 'next_payment_due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
