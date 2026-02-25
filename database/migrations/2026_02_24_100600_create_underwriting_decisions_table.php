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
        Schema::create('underwriting_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('eligibility_assessment_id')->nullable()->constrained('eligibility_assessments')->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('loan_product_id')->constrained()->onDelete('cascade');
            
            // Decision metadata
            $table->string('decision_number', 50)->unique(); // e.g., DEC-000001
            $table->enum('decision_status', [
                'draft',
                'pending_review',
                'under_review',
                'pending_approval',
                'approved',
                'declined',
                'cancelled'
            ])->default('draft');
            
            // Underwriter information
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // Requested vs Approved amounts
            $table->decimal('requested_amount', 15, 2);
            $table->integer('requested_tenure_months');
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->integer('approved_tenure_months')->nullable();
            $table->decimal('approved_interest_rate', 5, 2)->nullable();
            $table->string('approved_interest_method', 20)->nullable(); // reducing_balance, flat_rate
            
            // Decision details
            $table->enum('final_decision', ['approved', 'declined', 'deferred'])->nullable();
            $table->text('decision_reason')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->text('approver_notes')->nullable();
            $table->json('attached_conditions')->nullable(); // Array of conditions imposed
            $table->json('waived_conditions')->nullable(); // Conditions from eligibility that were waived
            
            // Override management
            $table->boolean('requires_override')->default(false);
            $table->boolean('override_requested')->default(false);
            $table->boolean('override_approved')->default(false);
            $table->text('override_justification')->nullable();
            $table->json('override_policy_breaches')->nullable(); // Which policies are being overridden
            $table->foreignId('override_requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('override_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('override_requested_at')->nullable();
            $table->timestamp('override_approved_at')->nullable();
            $table->timestamp('override_declined_at')->nullable();
            $table->text('override_decline_reason')->nullable();
            
            // Risk assessment override (if underwriter disagrees with system)
            $table->string('manual_risk_grade', 5)->nullable(); // Override system risk grade
            $table->text('risk_grade_justification')->nullable();
            
            // Maker-Checker (optional)
            $table->boolean('maker_checker_required')->default(false);
            $table->foreignId('maker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('checker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('maker_submitted_at')->nullable();
            $table->timestamp('checker_reviewed_at')->nullable();
            
            // Calculation details (saved for audit)
            $table->decimal('final_monthly_installment', 15, 2)->nullable();
            $table->decimal('final_total_interest', 15, 2)->nullable();
            $table->decimal('final_total_repayment', 15, 2)->nullable();
            $table->decimal('final_dti_ratio', 5, 2)->nullable();
            $table->decimal('final_dsr_ratio', 5, 2)->nullable();
            $table->decimal('final_ltv_ratio', 5, 2)->nullable();
            
            // Workflow tracking
            $table->string('workflow_stage', 50)->nullable(); // credit_officer, supervisor, manager
            $table->integer('approval_level')->default(0); // 0 = none, 1 = first level, 2 = second level
            $table->json('approval_history')->nullable(); // Track approval chain
            
            // Additional flags
            $table->boolean('is_high_value')->default(false); // Requires special approval
            $table->boolean('is_exception_case')->default(false); // Exception to normal policies
            $table->boolean('is_expedited')->default(false); // Fast-track processing
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('application_id');
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('decision_status');
            $table->index('final_decision');
            $table->index('reviewed_by');
            $table->index('approved_by');
            $table->index('workflow_stage');
            $table->index(['institution_id', 'decision_status']);
            $table->index(['reviewed_by', 'decision_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('underwriting_decisions');
    }
};
