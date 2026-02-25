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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Transaction Details
            $table->date('transaction_date');
            $table->string('description');
            $table->string('transaction_hash')->unique(); // for deduplication
            
            // Amounts
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->nullable();
            
            // Classification
            $table->string('transaction_type')->nullable(); // salary, business_income, debt_payment, rent, utility, transfer, etc.
            $table->string('category')->nullable(); // auto-classified category
            $table->boolean('is_income')->default(false);
            $table->boolean('is_expense')->default(false);
            $table->boolean('is_debt_payment')->default(false);
            $table->boolean('is_recurring')->default(false);
            
            // Risk Flags
            $table->json('risk_flags')->nullable(); // gambling, bounce, large_transfer, etc.
            $table->boolean('is_flagged')->default(false);
            
            // Additional Metadata
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('bank_statement_import_id');
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index(['customer_id', 'transaction_date'], 'idx_customer_trans_date');
            $table->index(['bank_statement_import_id', 'transaction_date'], 'idx_import_trans_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
