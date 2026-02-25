<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Temporarily expand enum to include both old and new values
        DB::statement("ALTER TABLE prospects MODIFY COLUMN customer_type ENUM('salaried', 'self_employed', 'salary', 'business', 'mixed') NOT NULL");
        
        // Step 2: Update existing data to match new enum values
        DB::table('prospects')
            ->where('customer_type', 'salaried')
            ->update(['customer_type' => 'salary']);
        
        DB::table('prospects')
            ->where('customer_type', 'self_employed')
            ->update(['customer_type' => 'business']);
        
        // Step 3: Remove old enum values
        DB::statement("ALTER TABLE prospects MODIFY COLUMN customer_type ENUM('salary', 'business', 'mixed') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Temporarily expand enum
        DB::statement("ALTER TABLE prospects MODIFY COLUMN customer_type ENUM('salary', 'business', 'mixed', 'salaried', 'self_employed') NOT NULL");
        
        // Step 2: Revert data
        DB::table('prospects')
            ->where('customer_type', 'salary')
            ->update(['customer_type' => 'salaried']);
        
        DB::table('prospects')
            ->where('customer_type', 'business')
            ->update(['customer_type' => 'self_employed']);
        
        // Step 3: Remove new enum values
        DB::statement("ALTER TABLE prospects MODIFY COLUMN customer_type ENUM('salaried', 'self_employed') NOT NULL");
    }
};
