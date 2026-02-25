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
        Schema::table('eligibility_assessments', function (Blueprint $table) {
            // Drop foreign keys temporarily
            $table->dropForeign(['application_id']);
            $table->dropForeign(['customer_id']);
            
            // Make columns nullable
            $table->foreignId('application_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->change();
            
            // Re-add foreign keys with cascade delete
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eligibility_assessments', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['application_id']);
            $table->dropForeign(['customer_id']);
            
            // Make columns NOT NULL again (restore original)
            $table->foreignId('application_id')->change();
            $table->foreignId('customer_id')->change();
            
            // Re-add foreign keys
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }
};
