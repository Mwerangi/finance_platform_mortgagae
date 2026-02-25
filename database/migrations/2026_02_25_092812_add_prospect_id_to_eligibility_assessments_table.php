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
            $table->foreignId('prospect_id')->nullable()->after('application_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eligibility_assessments', function (Blueprint $table) {
            $table->dropForeign(['prospect_id']);
            $table->dropColumn('prospect_id');
        });
    }
};
