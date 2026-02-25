<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LoanScheduleService;
use App\Models\LoanProduct;
use App\Enums\InterestCalculationMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoanScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoanScheduleService();
    }

    /** @test */
    public function it_calculates_reducing_balance_schedule_correctly()
    {
        $principal = 1000000; // 1,000,000 TZS
        $term = 12; // 12 months

        $schedule = $this->service->generateSchedule(
            loanId: 1,
            principal: $principal,
            annualRate: 24.0,
            termMonths: $term,
            calculationMethod: InterestCalculationMethod::REDUCING_BALANCE->value,
            startDate: now()
        );

        // Assertions
        $this->assertCount($term, $schedule);
        
        // Monthly rate: 24% / 12 = 2%
        $monthlyRate = 0.02;
        
        // Calculate expected monthly payment using PMT formula
        // PMT = P * r * (1+r)^n / ((1+r)^n - 1)
        $numerator = $principal * $monthlyRate * pow(1 + $monthlyRate, $term);
        $denominator = pow(1 + $monthlyRate, $term) - 1;
        $expectedPayment = $numerator / $denominator;
        
        // First installment
        $firstInstallment = $schedule[0];
        $this->assertEquals(1, $firstInstallment['installment_number']);
        $this->assertEquals(round($expectedPayment, 2), round($firstInstallment['total_due'], 2));
        
        // First month interest should be 2% of principal
        $expectedFirstInterest = $principal * $monthlyRate;
        $this->assertEquals(round($expectedFirstInterest, 2), round($firstInstallment['interest_due'], 2));
        
        // Principal for first month
        $expectedFirstPrincipal = $expectedPayment - $expectedFirstInterest;
        $this->assertEquals(round($expectedFirstPrincipal, 2), round($firstInstallment['principal_due'], 2));
        
        // Check that balance reduces over time
        $this->assertGreaterThan(
            $schedule[$term - 1]['balance_after_payment'],
            $schedule[0]['balance_after_payment']
        );
        
        // Final balance should be close to zero (within rounding error)
        $this->assertLessThan(1, abs($schedule[$term - 1]['balance_after_payment']));
        
        // Total payments should equal principal + total interest
        $totalPayments = array_sum(array_column($schedule, 'total_due'));
        $totalInterest = array_sum(array_column($schedule, 'interest_due'));
        $totalPrincipal = array_sum(array_column($schedule, 'principal_due'));
        
        $this->assertEquals(round($principal, 2), round($totalPrincipal, 2));
        // Allow for 0.05 rounding tolerance (individual rounding accumulation)
        $this->assertEqualsWithDelta($totalPayments, $totalPrincipal + $totalInterest, 0.05);
    }

    /** @test */
    public function it_calculates_flat_rate_schedule_correctly()
    {
        $principal = 1000000; // 1,000,000 TZS
        $term = 12; // 12 months
        $annualRate = 24.0; // 24% flat

        $schedule = $this->service->generateSchedule(
            loanId: 1,
            principal: $principal,
            annualRate: $annualRate,
            termMonths: $term,
            calculationMethod: InterestCalculationMethod::FLAT_RATE->value,
            startDate: now()
        );

        // Assertions
        $this->assertCount($term, $schedule);
        
        // Flat rate: interest calculated on full principal for entire term
        $totalInterest = $principal * ($annualRate / 100) * ($term / 12);
        $monthlyInterest = $totalInterest / $term;
        $monthlyPrincipal = $principal / $term;
        $monthlyPayment = $monthlyPrincipal + $monthlyInterest;
        
        // All installments should have same payment amount
        foreach ($schedule as $installment) {
            $this->assertEquals(round($monthlyPayment, 2), round($installment['total_due'], 2));
            $this->assertEquals(round($monthlyInterest, 2), round($installment['interest_due'], 2));
            $this->assertEquals(round($monthlyPrincipal, 2), round($installment['principal_due'], 2));
        }
        
        // Final balance should be zero
        $this->assertLessThan(1, abs($schedule[$term - 1]['balance_after_payment']));
        
        // Total interest should match flat calculation
        $totalInterestPaid = array_sum(array_column($schedule, 'interest_due'));
        $this->assertEquals(round($totalInterest, 2), round($totalInterestPaid, 2));
    }

    /** @test */
    public function it_generates_correct_due_dates()
    {
        $startDate = now()->startOfMonth();
        
        $schedule = $this->service->generateSchedule(
            loanId: 1,
            principal: 1000000,
            annualRate: 24.0,
            termMonths: 6,
            calculationMethod: InterestCalculationMethod::FLAT_RATE->value,
            startDate: $startDate
        );

        // Check that due dates are sequential months
        for ($i = 0; $i < count($schedule); $i++) {
            $expectedDate = $startDate->copy()->addMonths($i + 1);
            $actualDate = \Carbon\Carbon::parse($schedule[$i]['due_date']);
            
            $this->assertEquals($expectedDate->format('Y-m-d'), $actualDate->format('Y-m-d'));
        }
    }

    /** @test */
    public function it_handles_zero_interest_rate()
    {
        $principal = 1000000;
        $term = 12;

        $schedule = $this->service->generateSchedule(
            loanId: 1,
            principal: $principal,
            annualRate: 0,
            termMonths: $term,
            calculationMethod: InterestCalculationMethod::FLAT_RATE->value,
            startDate: now()
        );

        $monthlyPrincipal = $principal / $term;

        foreach ($schedule as $installment) {
            $this->assertEquals(0, $installment['interest_due']);
            $this->assertEquals(round($monthlyPrincipal, 2), round($installment['principal_due'], 2));
            $this->assertEquals(round($monthlyPrincipal, 2), round($installment['total_due'], 2));
        }
    }
}
