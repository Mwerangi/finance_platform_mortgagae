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
        Schema::create('statement_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            
            // Analysis Period
            $table->integer('analysis_months'); // number of months analyzed
            $table->date('analysis_start_date');
            $table->date('analysis_end_date');
            
            // Monthly Aggregations (JSON arrays)
            $table->json('monthly_inflows'); // [{month: '2026-01', inflow: 5000000, ...}, ...]
            $table->json('monthly_outflows');
            $table->json('monthly_net_surplus');
            
            // Summary Metrics
            $table->decimal('avg_monthly_inflow', 15, 2);
            $table->decimal('avg_monthly_outflow', 15, 2);
            $table->decimal('avg_net_surplus', 15, 2);
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable();
            
            // Income Analysis
            $table->string('income_classification'); // salary, business, mixed, irregular
            $table->decimal('estimated_net_income', 15, 2)->nullable();
            $table->decimal('income_stability_score', 5, 2)->default(0); // 0-100
            $table->boolean('has_regular_salary')->default(false);
            $table->boolean('has_business_income')->default(false);
            $table->json('income_sources')->nullable(); // detected income sources
            
            // Expense & Debt Analysis
            $table->decimal('total_debt_obligations', 15, 2)->default(0);
            $table->decimal('estimated_monthly_debt', 15, 2)->default(0);
            $table->integer('debt_payment_count')->default(0);
            $table->json('detected_debts')->nullable(); // array of detected debt payments
            
            // Risk Metrics
            $table->decimal('cash_flow_volatility_score', 5, 2)->default(0); // 0-100, higher = more volatile
            $table->integer('negative_balance_days')->default(0);
            $table->integer('bounce_count')->default(0);
            $table->integer('gambling_transaction_count')->default(0);
            $table->decimal('large_unexplained_outflows', 15, 2)->default(0);
            $table->json('risk_flags')->nullable(); // array of flagged risks
            $table->string('overall_risk_assessment')->nullable(); // low, medium, high
            
            // Calculated Ratios (for eligibility)
            $table->decimal('debt_to_income_ratio', 5, 2)->nullable(); // percentage
            $table->decimal('disposable_income_ratio', 5, 2)->nullable(); // percentage
            
            // Processing
            $table->timestamp('computed_at')->nullable();
            $table->foreignId('computed_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('bank_statement_import_id');
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('application_id');
            $table->index('income_classification');
            $table->index('overall_risk_assessment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statement_analytics');
    }
};
