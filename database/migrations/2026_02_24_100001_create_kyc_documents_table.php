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
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Document Information
            $table->string('document_type'); // 'national_id', 'passport', 'drivers_license', 'utility_bill', 'bank_statement', 'employment_letter', 'business_license', 'tax_certificate', 'other'
            $table->string('document_number')->nullable();
            $table->string('document_name');
            $table->text('description')->nullable();
            
            // File Information
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // mime type
            $table->integer('file_size'); // in bytes
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Verification
            $table->string('verification_status')->default('pending'); // pending, verified, rejected, expired
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            // Rejection
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_notes')->nullable();
            
            // Expiry
            $table->date('expiry_date')->nullable();
            $table->boolean('is_expired')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('document_type');
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
