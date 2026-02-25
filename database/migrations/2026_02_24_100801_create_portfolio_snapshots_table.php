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
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            
            // Snapshot Details
            $table->date('snapshot_date')->index();
            $table->enum('snapshot_type', ['daily', 'monthly', 'quarterly', 'annual'])->default('daily');
            
            // Portfolio Size
            $table->integer('total_loans')->default(0);
            $table->integer('active_loans')->default(0);
            $table->integer('closed_loans')->default(0);
            $table->integer('written_off_loans')->default(0);
            
            // Portfolio Value
            $table->decimal('total_disbursed', 15, 2)->default(0);
            $table->decimal('principal_outstanding', 15, 2)->default(0);
            $table->decimal('interest_outstanding', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->decimal('penalties_outstanding', 15, 2)->default(0);
            $table->decimal('fees_outstanding', 15, 2)->default(0);
            
            // Collections
            $table->decimal('total_collected', 15, 2)->default(0);
            $table->decimal('principal_collected', 15, 2)->default(0);
            $table->decimal('interest_collected', 15, 2)->default(0);
            $table->decimal('penalties_collected', 15, 2)->default(0);
            $table->decimal('fees_collected', 15, 2)->default(0);
            
            // Arrears & Aging
            $table->decimal('total_arrears', 15, 2)->default(0);
            $table->integer('loans_in_arrears')->default(0);
            
            // Aging Buckets
            $table->integer('current_count')->default(0); // 0-30 days
            $table->decimal('current_amount', 15, 2)->default(0);
            
            $table->integer('bucket_30_count')->default(0); // 31-60 days
            $table->decimal('bucket_30_amount', 15, 2)->default(0);
            
            $table->integer('bucket_60_count')->default(0); // 61-90 days
            $table->decimal('bucket_60_amount', 15, 2)->default(0);
            
            $table->integer('bucket_90_count')->default(0); // 91-180 days
            $table->decimal('bucket_90_amount', 15, 2)->default(0);
            
            $table->integer('bucket_180_count')->default(0); // 180+ days
            $table->decimal('bucket_180_amount', 15, 2)->default(0);
            
            $table->integer('npl_count')->default(0); // Non-performing (90+ days)
            $table->decimal('npl_amount', 15, 2)->default(0);
            
            // PAR (Portfolio at Risk)
            $table->integer('par_30_count')->default(0);
            $table->decimal('par_30_amount', 15, 2)->default(0);
            $table->decimal('par_30_ratio', 5, 2)->default(0); // Percentage
            
            $table->integer('par_60_count')->default(0);
            $table->decimal('par_60_amount', 15, 2)->default(0);
            $table->decimal('par_60_ratio', 5, 2)->default(0);
            
            $table->integer('par_90_count')->default(0);
            $table->decimal('par_90_amount', 15, 2)->default(0);
            $table->decimal('par_90_ratio', 5, 2)->default(0);
            
            // NPL Metrics
            $table->decimal('npl_ratio', 5, 2)->default(0); // Percentage
            
            // Collection Rate
            $table->decimal('expected_collections', 15, 2)->default(0); // Due in period
            $table->decimal('actual_collections', 15, 2)->default(0); // Received in period
            $table->decimal('collection_rate', 5, 2)->default(0); // Percentage
            
            // Write-offs
            $table->integer('writeoff_count')->default(0);
            $table->decimal('writeoff_amount', 15, 2)->default(0);
            $table->decimal('writeoff_ratio', 5, 2)->default(0); // Percentage
            
            // Provision
            $table->decimal('total_provision', 15, 2)->default(0);
            $table->decimal('provision_coverage_ratio', 5, 2)->default(0); // Provision / NPL
            
            // Portfolio Growth
            $table->decimal('new_loans_disbursed', 15, 2)->default(0); // In period
            $table->integer('new_loans_count')->default(0);
            $table->decimal('portfolio_growth_rate', 5, 2)->default(0); // Percentage
            
            // Average Metrics
            $table->decimal('average_loan_size', 15, 2)->default(0);
            $table->decimal('average_outstanding', 15, 2)->default(0);
            $table->integer('average_tenure_months')->default(0);
            $table->decimal('average_interest_rate', 5, 2)->default(0);
            
            // Risk Classification
            $table->integer('performing_count')->default(0);
            $table->integer('watch_list_count')->default(0);
            $table->integer('substandard_count')->default(0);
            $table->integer('doubtful_count')->default(0);
            $table->integer('loss_count')->default(0);
            
            // Metadata
            $table->json('additional_metrics')->nullable(); // Flexible storage
            $table->timestamp('computed_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes (foreignId and ->index() already create single-column indexes)
            $table->index(['institution_id', 'snapshot_date']);
            $table->index(['institution_id', 'snapshot_type']);
            
            // Unique constraint - one snapshot per institution per date per type
            $table->unique(['institution_id', 'snapshot_date', 'snapshot_type'], 'portfolio_snapshot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
