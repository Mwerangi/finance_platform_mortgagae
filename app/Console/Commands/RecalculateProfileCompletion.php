<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class RecalculateProfileCompletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:recalculate-profile-completion {--customer_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate profile completion percentage for all customers or a specific customer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->option('customer_id');

        if ($customerId) {
            // Recalculate for specific customer
            $customer = Customer::find($customerId);
            
            if (!$customer) {
                $this->error("Customer with ID {$customerId} not found.");
                return 1;
            }

            $oldPercentage = $customer->profile_completion_percentage;
            $customer->updateProfileCompletion();
            $customer->refresh();
            $newPercentage = $customer->profile_completion_percentage;

            $this->info("Customer #{$customerId} - {$customer->full_name}");
            $this->info("Profile completion: {$oldPercentage}% → {$newPercentage}%");
            
            return 0;
        }

        // Recalculate for all customers
        $this->info('Recalculating profile completion for all customers...');
        
        $customers = Customer::all();
        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        $updated = 0;
        $unchanged = 0;

        foreach ($customers as $customer) {
            $oldPercentage = $customer->profile_completion_percentage;
            $customer->updateProfileCompletion();
            $customer->refresh();
            $newPercentage = $customer->profile_completion_percentage;

            if ($oldPercentage !== $newPercentage) {
                $updated++;
            } else {
                $unchanged++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Recalculation complete!");
        $this->info("Total customers: {$customers->count()}");
        $this->info("Updated: {$updated}");
        $this->info("Unchanged: {$unchanged}");

        return 0;
    }
}
