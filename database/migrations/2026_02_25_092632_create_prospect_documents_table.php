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
        Schema::create('prospect_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            
            // File Information
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // pdf, jpg, png, etc.
            $table->integer('file_size')->nullable(); // in bytes
            
            // Verification Status
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            // Document Details
            $table->text('notes')->nullable();
            $table->date('expiry_date')->nullable(); // for documents like IDs, insurance
            
            // Upload Tracking
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index(['prospect_id', 'status']);
            $table->index('document_type_id');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_documents');
    }
};
