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
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Schedule Details
            $table->integer('installment_number'); // 1, 2, 3, etc.
            $table->date('due_date')->index();
            $table->enum('status', [
                'pending',
                'partially_paid',
                'fully_paid',
                'overdue',
                'waived'
            ])->default('pending')->index();
            
            // Amounts Due
            $table->decimal('principal_due', 15, 2);
            $table->decimal('interest_due', 15, 2);
            $table->decimal('total_due', 15, 2);
            $table->decimal('penalties_due', 15, 2)->default(0);
            $table->decimal('fees_due', 15, 2)->default(0);
            
            // Balance After Payment (for reducing balance)
            $table->decimal('opening_balance', 15, 2);
            $table->decimal('closing_balance', 15, 2);
            
            // Payment Tracking
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('penalties_paid', 15, 2)->default(0);
            $table->decimal('fees_paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('balance_remaining', 15, 2)->default(0);
            
            // Payment Dates
            $table->date('paid_date')->nullable();
            $table->date('last_payment_date')->nullable();
            
            // Days Past Due
            $table->integer('days_past_due')->default(0);
            $table->date('overdue_since')->nullable();
            
            // Metadata
            $table->json('payment_history')->nullable(); // Array of payment transactions
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('loan_id');
            $table->index('institution_id');
            $table->index(['loan_id', 'installment_number']);
            $table->index(['loan_id', 'due_date']);
            $table->index(['loan_id', 'status']);
            $table->index(['institution_id', 'due_date']);
            $table->index(['institution_id', 'status']);
            $table->index(['status', 'due_date']);
            
            // Unique constraint
            $table->unique(['loan_id', 'installment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
