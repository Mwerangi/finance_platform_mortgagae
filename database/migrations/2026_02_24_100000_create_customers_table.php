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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Customer ID
            $table->string('customer_code')->unique(); // e.g., CUS-001234
            
            // Customer Type
            $table->string('customer_type'); // 'salary', 'business', 'mixed'
            
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender')->nullable(); // 'male', 'female', 'other'
            $table->string('marital_status')->nullable(); // 'single', 'married', 'divorced', 'widowed'
            
            // Identification
            $table->string('national_id')->nullable(); // NIDA number
            $table->string('tin')->nullable(); // Tax Identification Number
            $table->string('passport_number')->nullable();
            
            // Contact Information
            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            
            // Address
            $table->text('physical_address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Tanzania');
            
            // Employment/Business Information
            $table->string('employer_name')->nullable(); // For salary clients
            $table->string('business_name')->nullable(); // For business clients
            $table->string('occupation')->nullable();
            $table->string('industry')->nullable();
            $table->date('employment_start_date')->nullable();
            
            // Next of Kin
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->text('next_of_kin_address')->nullable();
            
            // Profile Completion
            $table->integer('profile_completion_percentage')->default(0);
            $table->boolean('kyc_verified')->default(false);
            $table->timestamp('kyc_verified_at')->nullable();
            $table->foreignId('kyc_verified_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Status
            $table->string('status')->default('active'); // active, inactive, suspended, blacklisted
            $table->timestamp('deactivated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('institution_id');
            $table->index('customer_code');
            $table->index('customer_type');
            $table->index('status');
            $table->index('national_id');
            $table->index('phone_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
