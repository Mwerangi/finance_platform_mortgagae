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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            
            // Categorization
            $table->string('category'); // policy_risk, interest_rates, assessment, workflow, documents, emails, branding, system, integrations, compliance
            $table->string('key')->unique(); // Unique identifier (e.g., 'max_dti_ratio')
            $table->string('label'); // Human-readable name
            
            // Value Storage
            $table->text('value')->nullable(); // JSON or plain value
            $table->text('default_value')->nullable();
            $table->string('data_type')->default('string'); // string, number, boolean, json, text, email, url, color, file
            
            // Metadata
            $table->text('description')->nullable();
            $table->string('unit')->nullable(); // %, TZS, days, etc.
            $table->integer('display_order')->default(0);
            
            // Access Control
            $table->boolean('is_public')->default(false); // Can be accessed by non-admin
            $table->boolean('is_editable')->default(true); // Can be modified
            $table->boolean('requires_restart')->default(false);
            
            // Validation
            $table->json('validation_rules')->nullable(); // min, max, regex, etc.
            $table->json('options')->nullable(); // For select/dropdown types
            
            // Audit
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('category');
            $table->index(['category', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
