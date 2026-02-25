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
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['customer_id']);
            
            // Make customer_id nullable
            $table->foreignId('customer_id')->nullable()->change()->constrained('customers')->onDelete('cascade');
            
            // Add bank_name and account_number fields
            $table->string('bank_name')->nullable()->after('file_size');
            $table->string('account_number')->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn(['bank_name', 'account_number']);
            
            // Drop the foreign key and make customer_id not nullable again
            $table->dropForeign(['customer_id']);
            $table->foreignId('customer_id')->nullable(false)->change()->constrained('customers')->onDelete('cascade');
        });
    }
};
