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
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            
            // File Information
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->integer('file_size'); // bytes
            
            // Import Status
            $table->string('import_status')->default('pending'); // pending, processing, completed, failed
            $table->integer('rows_total')->default(0);
            $table->integer('rows_processed')->default(0);
            $table->integer('rows_failed')->default(0);
            
            // Date Range from Statement
            $table->date('statement_start_date')->nullable();
            $table->date('statement_end_date')->nullable();
            $table->integer('statement_months')->nullable(); // calculated months coverage
            
            // Processing Timestamps
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            
            // Error Tracking
            $table->json('error_log')->nullable(); // array of error messages
            $table->text('processing_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('customer_id');
            $table->index('institution_id');
            $table->index('application_id');
            $table->index('import_status');
            $table->index(['customer_id', 'import_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_imports');
    }
};
