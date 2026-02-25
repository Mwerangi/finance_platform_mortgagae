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
        Schema::create('collections_queue', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // Delinquency Details
            $table->integer('days_past_due')->default(0);
            $table->decimal('total_arrears', 15, 2)->default(0);
            $table->decimal('principal_arrears', 15, 2)->default(0);
            $table->decimal('interest_arrears', 15, 2)->default(0);
            $table->decimal('penalty_arrears', 15, 2)->default(0);
            $table->decimal('fees_arrears', 15, 2)->default(0);
            
            // Priority Scoring
            $table->integer('priority_score')->default(0); // Higher = more urgent
            $table->enum('priority_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('delinquency_bucket', ['current', '1-30', '31-60', '61-90', '91-180', '180+'])->default('current');
            
            // Assignment Details
            $table->enum('status', [
                'pending',      // Not yet assigned
                'assigned',     // Assigned to officer
                'in_progress',  // Being worked on
                'contacted',    // Customer contacted
                'ptp_made',     // Promise to pay made
                'resolved',     // Payment received
                'escalated',    // Escalated to legal
                'closed'        // Closed/written off
            ])->default('pending');
            
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->timestamp('next_action_due')->nullable();
            
            // Contact Information
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_address')->nullable();
            
            // Metrics
            $table->integer('contact_attempts')->default(0);
            $table->integer('successful_contacts')->default(0);
            $table->integer('broken_promises')->default(0);
            
            // Flags
            $table->boolean('is_legal_case')->default(false);
            $table->boolean('has_active_ptp')->default(false);
            $table->boolean('customer_reachable')->default(true);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            
            // Indexes (composite indexes only)
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'priority_level']);
            $table->index(['assigned_to', 'status']);
            $table->index(['institution_id', 'days_past_due']);
            $table->index(['next_action_due']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections_queue');
    }
};
