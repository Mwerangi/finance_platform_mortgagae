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
        Schema::create('repayment_import_batches', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            
            // Import Details
            $table->string('batch_number')->unique(); // AUTO-000001
            $table->string('original_filename');
            $table->string('file_path');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'partially_completed'
            ])->default('pending')->index();
            
            // Import Statistics
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('matched_amount', 15, 2)->default(0);
            $table->decimal('unmatched_amount', 15, 2)->default(0);
            
            // Processing Timeline
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_duration_seconds')->nullable(); // in seconds
            
            // Error Tracking
            $table->json('errors')->nullable(); // Array of errors
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('institution_id');
            $table->index('uploaded_by');
            $table->index(['institution_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('loan_schedule_id')->nullable()->constrained('loan_schedules')->onDelete('set null');
            $table->foreignId('import_batch_id')->nullable()->constrained('repayment_import_batches')->onDelete('set null');
            
            // Payment Identification
            $table->string('transaction_reference')->nullable()->index(); // From bank statement
            $table->string('receipt_number')->nullable()->unique(); // Internal receipt
            
            // Payment Details
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', [
                'bank_transfer',
                'cheque',
                'cash',
                'mobile_money',
                'direct_debit',
                'standing_order',
                'other'
            ])->nullable();
            $table->string('payment_channel')->nullable(); // Bank name, mobile provider, etc.
            
            // Allocation Breakdown
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('penalties_amount', 15, 2)->default(0);
            $table->decimal('fees_amount', 15, 2)->default(0);
            $table->decimal('unallocated_amount', 15, 2)->default(0); // Overpayment or advance
            
            // Processing Status
            $table->enum('status', [
                'pending',
                'allocated',
                'reversed',
                'disputed'
            ])->default('pending')->index();
            
            $table->boolean('is_partial_payment')->default(false);
            $table->boolean('is_advance_payment')->default(false); // Payment before due date
            $table->boolean('is_overpayment')->default(false);
            
            // Loan State at Payment Time
            $table->integer('installment_number')->nullable(); // Which installment was paid
            $table->integer('days_past_due_at_payment')->nullable(); // DPD when payment was made
            $table->decimal('outstanding_before_payment', 15, 2)->nullable();
            $table->decimal('outstanding_after_payment', 15, 2)->nullable();
            
            // Reversal Support
            $table->boolean('is_reversed')->default(false);
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('reversal_reason')->nullable();
            
            // Audit Trail
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            
            $table->timestamps();
            
            // Indexes (single column indexes already created inline above)
            $table->index(['institution_id', 'payment_date']);
            $table->index(['loan_id', 'payment_date']);
            $table->index(['institution_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
        Schema::dropIfExists('repayment_import_batches');
    }
};
