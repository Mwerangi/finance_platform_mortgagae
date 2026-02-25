<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EligibilityService;
use App\Models\Application;
use App\Models\LoanProduct;
use App\Models\Customer;
use App\Models\StatementAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EligibilityService::class);
    }

    /** @test */
    public function it_calculates_dti_correctly()
    {
        // Monthly gross income: 2,000,000 TZS
        // Requested loan payment: 500,000 TZS
        // Existing loan payments: 300,000 TZS
        // Total debt: 800,000
        // DTI = 800,000 / 2,000,000 = 40%

        $dti = $this->service->calculateDTI(
            monthlyGrossIncome: 2000000,
            requestedLoanPayment: 500000,
            existingLoanPayments: 300000
        );

        $this->assertEquals(40.0, $dti);
    }

    /** @test */
    public function it_calculates_dsr_correctly()
    {
        // Monthly net salary: 1,500,000 TZS
        // Requested loan payment: 600,000 TZS
        // Existing loan payments: 200,000 TZS
        // Total debt: 800,000
        // DSR = 800,000 / 1,500,000 = 53.33%

        $dsr = $this->service->calculateDSR(
            monthlyNetSalary: 1500000,
            requestedLoanPayment: 600000,
            existingLoanPayments: 200000
        );

        $this->assertEquals(53.33, round($dsr, 2));
    }

    /** @test */
    public function it_calculates_ltv_correctly()
    {
        // Loan amount: 50,000,000 TZS
        // Collateral value: 75,000,000 TZS
        // LTV = 50,000,000 / 75,000,000 = 66.67%

        $ltv = $this->service->calculateLTV(
            loanAmount: 50000000,
            collateralValue: 75000000
        );

        $this->assertEquals(66.67, round($ltv, 2));
    }

    /** @test */
    public function it_passes_eligibility_when_all_rules_met()
    {
        // Integration test - requires full application setup with statement analytics
        // This test validates the assessEligibility method works with real data
        // Skipped for unit testing - should be in integration test suite
        $this->markTestSkipped('Integration test - requires full statement analytics setup');
    }

    /** @test */
    public function it_fails_eligibility_when_dsr_exceeded()
    {
        // Integration test - requires full application setup with statement analytics
        // This test validates DSR boundary conditions in assessEligibility
        // Skipped for unit testing - should be in integration test suite
        $this->markTestSkipped('Integration test - requires full statement analytics setup');
    }

    /** @test */
    public function it_handles_zero_income_scenarios()
    {
        $dti = $this->service->calculateDTI(
            monthlyGrossIncome: 0,
            requestedLoanPayment: 500000,
            existingLoanPayments: 0
        );

        // With zero income, DTI should be 100% (maximum)
        $this->assertEquals(100.0, $dti);
    }

    /** @test */
    public function it_handles_zero_collateral_ltv()
    {
        $ltv = $this->service->calculateLTV(
            loanAmount: 5000000,
            collateralValue: 0
        );

        // With zero collateral, LTV should be 100% (maximum risk)
        $this->assertEquals(100.0, $ltv);
    }
}
