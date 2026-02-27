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
        Schema::table('statement_analytics', function (Blueprint $table) {
            // ==== 1. TRANSACTION SUMMARY ====
            $table->decimal('total_credits', 15, 2)->default(0)->after('closing_balance');
            $table->decimal('total_debits', 15, 2)->default(0)->after('total_credits');
            $table->integer('total_credit_count')->default(0)->after('total_debits');
            $table->integer('total_debit_count')->default(0)->after('total_credit_count');
            $table->decimal('avg_credit_amount', 15, 2)->default(0)->after('total_debit_count');
            $table->decimal('avg_debit_amount', 15, 2)->default(0)->after('avg_credit_amount');
            
            // ==== 2. INCOME SOURCE COMPOSITION ====
            $table->decimal('salary_income', 15, 2)->default(0)->after('income_sources');
            $table->decimal('business_income', 15, 2)->default(0)->after('salary_income');
            $table->decimal('loan_inflows', 15, 2)->default(0)->after('business_income');
            $table->decimal('bulk_deposits', 15, 2)->default(0)->after('loan_inflows');
            $table->decimal('transfer_inflows', 15, 2)->default(0)->after('bulk_deposits');
            $table->decimal('other_income', 15, 2)->default(0)->after('transfer_inflows');
            $table->json('income_composition_breakdown')->nullable()->after('other_income');
            
            // ==== 3. LOAN DETECTION ====
            $table->integer('detected_loan_count')->default(0)->after('detected_debts');
            $table->decimal('detected_monthly_loan_repayment', 15, 2)->default(0)->after('detected_loan_count');
            $table->json('detected_loans')->nullable()->after('detected_monthly_loan_repayment');
            $table->boolean('loan_stacking_detected')->default(false)->after('detected_loans');
            $table->string('loan_detection_confidence')->nullable()->after('loan_stacking_detected'); // high, medium, low
            
            // ==== 4. BULK DEPOSIT ANALYSIS ====
            $table->integer('bulk_deposit_count')->default(0)->after('loan_detection_confidence');
            $table->decimal('largest_single_deposit', 15, 2)->default(0)->after('bulk_deposit_count');
            $table->json('bulk_deposit_details')->nullable()->after('largest_single_deposit');
            $table->boolean('suspicious_deposits_flagged')->default(false)->after('bulk_deposit_details');
            
            // ==== 5. BEHAVIORAL ANALYSIS ====
            $table->decimal('transaction_frequency_score', 5, 2)->default(0)->after('cash_flow_volatility_score');
            $table->decimal('cash_withdrawal_ratio', 5, 2)->default(0)->after('transaction_frequency_score'); // % of outflows as cash
            $table->decimal('income_volatility_coefficient', 5, 2)->default(0)->after('cash_withdrawal_ratio');
            $table->string('transaction_pattern')->nullable()->after('income_volatility_coefficient'); // regular, irregular, sporadic
            $table->string('behavioral_risk_level')->nullable()->after('transaction_pattern'); // low, medium, high
            $table->json('behavioral_flags')->nullable()->after('behavioral_risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statement_analytics', function (Blueprint $table) {
            // Transaction Summary
            $table->dropColumn([
                'total_credits',
                'total_debits',
                'total_credit_count',
                'total_debit_count',
                'avg_credit_amount',
                'avg_debit_amount',
            ]);
            
            // Income Composition
            $table->dropColumn([
                'salary_income',
                'business_income',
                'loan_inflows',
                'bulk_deposits',
                'transfer_inflows',
                'other_income',
                'income_composition_breakdown',
            ]);
            
            // Loan Detection
            $table->dropColumn([
                'detected_loan_count',
                'detected_monthly_loan_repayment',
                'detected_loans',
                'loan_stacking_detected',
                'loan_detection_confidence',
            ]);
            
            // Bulk Deposit Analysis
            $table->dropColumn([
                'bulk_deposit_count',
                'largest_single_deposit',
                'bulk_deposit_details',
                'suspicious_deposits_flagged',
            ]);
            
            // Behavioral Analysis
            $table->dropColumn([
                'transaction_frequency_score',
                'cash_withdrawal_ratio',
                'income_volatility_coefficient',
                'transaction_pattern',
                'behavioral_risk_level',
                'behavioral_flags',
            ]);
        });
    }
};
