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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
            $table->string('source')->default('direct')->after('status'); // direct, from_prospect, referral, walk_in
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['prospect_id']);
            $table->dropColumn(['prospect_id', 'source']);
        });
    }
};
