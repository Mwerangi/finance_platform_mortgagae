<?php

namespace App\Console\Commands;

use App\Models\LoanSchedule;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateOverdueInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:update-overdue-installments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark installments as overdue when their due date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        // Find all pending or partially paid installments that are past due
        $overdueInstallments = LoanSchedule::query()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->where('due_date', '<', $today)
            ->get();

        if ($overdueInstallments->isEmpty()) {
            $this->info('No overdue installments found');
            return 0;
        }

        $this->info("Found {$overdueInstallments->count()} overdue installment(s)");
        
        $progressBar = $this->output->createProgressBar($overdueInstallments->count());
        $progressBar->start();

        $updated = 0;

        foreach ($overdueInstallments as $installment) {
            $daysPastDue = $today->diffInDays($installment->due_date);
            
            $installment->update([
                'status' => 'overdue',
                'days_past_due' => $daysPastDue,
                'overdue_since' => $installment->overdue_since ?? $installment->due_date,
            ]);
            
            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Updated {$updated} installment(s) to overdue status");
        
        return 0;
    }
}
