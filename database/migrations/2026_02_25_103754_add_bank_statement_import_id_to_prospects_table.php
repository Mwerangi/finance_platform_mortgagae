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
        Schema::table('prospects', function (Blueprint $table) {
            $table->foreignId('bank_statement_import_id')->nullable()->after('eligibility_assessment_id')
                ->constrained('bank_statement_imports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropForeign(['bank_statement_import_id']);
            $table->dropColumn('bank_statement_import_id');
        });
    }
};
