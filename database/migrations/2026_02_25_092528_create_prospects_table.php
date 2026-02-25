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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            
            // Basic Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('id_number')->unique();
            
            // Loan Request Details
            $table->enum('customer_type', ['salaried', 'self_employed']);
            $table->enum('loan_purpose', ['home_purchase', 'home_refinance', 'home_completion', 'home_construction', 'home_equity_release']);
            $table->decimal('requested_amount', 15, 2);
            $table->integer('requested_tenure'); // in months
            $table->foreignId('loan_product_id')->nullable()->constrained('loan_products')->nullOnDelete();
            
            // Property Information (optional at this stage)
            $table->string('property_location')->nullable();
            $table->decimal('property_value', 15, 2)->nullable();
            
            // Eligibility Status
            $table->enum('status', ['pending', 'statement_uploaded', 'eligibility_passed', 'eligibility_failed', 'converted_to_customer'])->default('pending');
            $table->foreignId('eligibility_assessment_id')->nullable()->constrained('eligibility_assessments')->nullOnDelete();
            
            // Conversion Tracking
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->string('source')->default('web_prequalification'); // web_prequalification, referral, walk_in, etc.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('institution_id');
            $table->index('phone');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
