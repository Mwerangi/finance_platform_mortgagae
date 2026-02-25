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
        Schema::create('collections_actions', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('queue_id')->nullable()->constrained('collections_queue')->onDelete('set null');
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            
            // Action Details
            $table->enum('action_type', [
                'phone_call',
                'sms',
                'email',
                'field_visit',
                'office_visit',
                'letter',
                'legal_notice',
                'other'
            ]);
            
            $table->timestamp('action_date');
            $table->string('contact_method')->nullable(); // Phone number, email used, etc.
            
            // Outcome
            $table->enum('outcome', [
                'successful',           // Contact made
                'no_answer',            // No answer
                'wrong_number',         // Wrong/invalid number
                'call_back_requested',  // Customer requested callback
                'payment_promised',     // PTP made
                'payment_received',     // Payment made
                'dispute_raised',       // Customer disputed amount
                'refused_to_pay',       // Customer refused
                'partial_payment',      // Partial payment
                'other'
            ])->nullable();
            
            // Promise to Pay Link (if PTP was created from this action)
            // Note: Foreign key will be added after promise_to_pay table is created
            $table->unsignedBigInteger('promise_to_pay_id')->nullable();
            
            // Action Details
            $table->text('notes')->nullable();
            $table->text('customer_response')->nullable();
            $table->decimal('amount_committed', 15, 2)->nullable(); // Amount customer committed to pay
            $table->date('commitment_date')->nullable(); // When customer committed to pay
            
            // Next Action Planning
            $table->date('next_action_date')->nullable();
            $table->string('next_action_type')->nullable();
            
            // Geolocation (for field visits)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Metadata
            $table->integer('duration_minutes')->nullable(); // Duration of call/visit
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            
            // Indexes (composite indexes only)
            $table->index(['institution_id', 'action_date']);
            $table->index(['loan_id', 'action_date']);
            $table->index(['performed_by', 'action_date']);
            $table->index(['institution_id', 'action_type']);
            $table->index(['institution_id', 'outcome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections_actions');
    }
};
