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
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete(); // null = applies to all
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            
            // Conditional Requirements
            $table->enum('customer_type', ['salaried', 'self_employed', 'both'])->default('both');
            $table->enum('loan_purpose', [
                'home_purchase',
                'home_refinance',
                'home_completion',
                'home_construction',
                'home_equity_release',
                'all'
            ])->default('all');
            
            // Stage Requirements
            $table->enum('stage', ['interview', 'eligibility', 'underwriting', 'approval'])->default('underwriting');
            $table->boolean('is_required')->default(true); // false = optional
            $table->boolean('can_skip_with_supervisor_approval')->default(false);
            
            // Metadata
            $table->text('instructions')->nullable(); // Instructions for collecting this document
            $table->integer('display_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_type', 'loan_purpose', 'stage']);
            $table->index('institution_id');
            $table->index('is_required');
            
            // Ensure unique combinations
            $table->unique(['institution_id', 'document_type_id', 'customer_type', 'loan_purpose', 'stage'], 'unique_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
