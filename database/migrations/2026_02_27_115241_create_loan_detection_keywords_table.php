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
        Schema::create('loan_detection_keywords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->comment('For multi-tenant support');
            $table->string('keyword', 100)->comment('The keyword or phrase to detect');
            $table->enum('type', ['repayment', 'disbursement'])->default('repayment')->comment('Loan repayment or disbursement');
            $table->enum('language', ['english', 'swahili', 'mixed'])->default('english')->comment('Keyword language');
            $table->integer('weight')->default(1)->comment('Detection confidence weight (1-10)');
            $table->boolean('is_active')->default(true)->comment('Enable/disable keyword');
            $table->text('description')->nullable()->comment('Keyword usage notes');
            $table->timestamps();
            
            // Indexes
            $table->index(['institution_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_detection_keywords');
    }
};
