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
        // Add foreign key constraint for promise_to_pay_id in collections_actions table
        Schema::table('collections_actions', function (Blueprint $table) {
            $table->foreign('promise_to_pay_id')
                ->references('id')
                ->on('promise_to_pay')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections_actions', function (Blueprint $table) {
            $table->dropForeign(['promise_to_pay_id']);
        });
    }
};
