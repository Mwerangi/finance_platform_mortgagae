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
        Schema::create('promise_to_pay', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('collections_action_id')->nullable()->constrained('collections_actions')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Promise Details
            $table->date('promise_date'); // When promise was made
            $table->date('commitment_date'); // When customer committed to pay
            $table->decimal('promised_amount', 15, 2);
            
            // Optional: Breakdown of promised amount
            $table->decimal('principal_amount', 15, 2)->nullable();
            $table->decimal('interest_amount', 15, 2)->nullable();
            $table->decimal('penalty_amount', 15, 2)->nullable();
            $table->decimal('fees_amount', 15, 2)->nullable();
            
            // Status
            $table->enum('status', [
                'open',         // Active promise
                'kept',         // Customer paid as promised
                'partially_kept', // Partial payment received
                'broken',       // Deadline passed, no payment
                'rescheduled',  // PTP rescheduled
                'cancelled'     // PTP cancelled
            ])->default('open');
            
            // Payment Tracking
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->date('actual_payment_date')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('repayments')->onDelete('set null'); // Link to actual payment
            
            // Follow-up
            $table->date('follow_up_date')->nullable(); // When to follow up if not paid
            $table->integer('days_overdue')->default(0); // Days past commitment_date
            $table->boolean('reminder_sent')->default(false);
            
            // Reschedule History
            $table->integer('reschedule_count')->default(0);
            $table->date('original_commitment_date')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->text('customer_reason')->nullable(); // Why customer couldn't pay on time
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            
            // Indexes (composite indexes only)
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'commitment_date']);
            $table->index(['loan_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['commitment_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promise_to_pay');
    }
};
