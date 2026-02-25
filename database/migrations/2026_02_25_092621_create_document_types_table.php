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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Interview Sheet", "ID Copy", "Bank Statement"
            $table->string('code')->unique(); // e.g., "interview_sheet", "id_copy"
            $table->text('description')->nullable();
            $table->enum('category', ['basic', 'financial', 'employment', 'business', 'property', 'legal', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
