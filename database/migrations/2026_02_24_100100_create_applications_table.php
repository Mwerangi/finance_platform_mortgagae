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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_product_id')->constrained('loan_products')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Application Details
            $table->string('application_number')->unique(); // e.g., APP-000001
            $table->string('status')->default('draft'); // draft, submitted, under_review, approved, rejected, disbursed, closed
            $table->decimal('requested_amount', 15, 2);
            $table->integer('requested_tenure_months');
            
            // Property Details (optional for mortgage)
            $table->string('property_type')->nullable();
            $table->decimal('property_value', 15, 2)->nullable();
            $table->text('property_address')->nullable();
            
            // Application Workflow
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('loan_product_id');
            $table->index('status');
            $table->index('application_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
