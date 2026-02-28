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
            $table->json('final_recommendation')->nullable()->after('risk_explanation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eligibility_assessments', function (Blueprint $table) {
            $table->dropColumn('final_recommendation');
        });
    }
};
