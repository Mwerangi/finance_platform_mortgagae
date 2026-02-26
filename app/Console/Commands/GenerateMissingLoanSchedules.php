<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class GenerateMissingLoanSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:generate-schedules 
                            {--loan-id= : Generate schedule for a specific loan}
                            {--force : Regenerate schedules even if they exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate repayment schedules for loans that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $loanId = $this->option('loan-id');
        $force = $this->option('force');

        if ($loanId) {
            // Generate for specific loan
            $loan = Loan::find($loanId);
            
            if (!$loan) {
                $this->error("Loan with ID {$loanId} not found");
                return 1;
            }

            $this->generateForLoan($loan, $force);
            return 0;
        }

        // Generate for all active/disbursed loans without schedules
        $query = Loan::query()
            ->whereIn('status', ['active', 'pending_disbursement', 'defaulted'])
            ->whereNotNull('first_installment_date');

        if (!$force) {
            $query->doesntHave('schedules');
        }

        $loans = $query->get();

        if ($loans->isEmpty()) {
            $this->info('No loans found that need schedules generated');
            return 0;
        }

        $this->info("Found {$loans->count()} loan(s) to process");
        
        $progressBar = $this->output->createProgressBar($loans->count());
        $progressBar->start();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($loans as $loan) {
            try {
                if ($force && $loan->schedules()->count() > 0) {
                    // Delete existing schedules if forcing
                    $loan->schedules()->delete();
                }

                if ($this->generateForLoan($loan, $force, false)) {
                    $generated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for loan {$loan->loan_account_number}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Schedule Generation Complete:");
        $this->line("  Generated: {$generated}");
        $this->line("  Skipped: {$skipped}");
        if ($failed > 0) {
            $this->error("  Failed: {$failed}");
        }

        return 0;
    }

    /**
     * Generate schedule for a single loan
     */
    private function generateForLoan(Loan $loan, bool $force, bool $verbose = true): bool
    {
        if (!$force && $loan->schedules()->count() > 0) {
            if ($verbose) {
                $this->warn("Loan {$loan->loan_account_number} already has schedules. Use --force to regenerate.");
            }
            return false;
        }

        if (!$loan->first_installment_date) {
            if ($verbose) {
                $this->error("Loan {$loan->loan_account_number} has no first_installment_date set");
            }
            return false;
        }

        try {
            $loan->generateSchedule();
            
            if ($verbose) {
                $scheduleCount = $loan->schedules()->count();
                $this->info("Generated {$scheduleCount} installments for loan {$loan->loan_account_number}");
            }
            
            return true;
        } catch (\Exception $e) {
            if ($verbose) {
                $this->error("Error generating schedule for loan {$loan->loan_account_number}: {$e->getMessage()}");
            }
            throw $e;
        }
    }
}
