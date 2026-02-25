<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PortfolioRiskService;
use App\Models\Loan;
use App\Models\Repayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PortfolioRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PortfolioRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PortfolioRiskService::class);
    }

    /** @test */
    public function it_calculates_dpd_correctly()
    {
        // Loan with installment due 15 days ago
        $dueDate = Carbon::now()->subDays(15);
        
        $dpd = $this->service->calculateDPD($dueDate);

        $this->assertEquals(15, $dpd);
    }

    /** @test */
    public function it_returns_zero_dpd_for_future_dates()
    {
        $dueDate = Carbon::now()->addDays(5);
        
        $dpd = $this->service->calculateDPD($dueDate);

        $this->assertEquals(0, $dpd);
    }

    /** @test */
    public function it_assigns_correct_aging_bucket()
    {
        $this->assertEquals('current', $this->service->getAgingBucket(0));
        $this->assertEquals('1-30', $this->service->getAgingBucket(15));
        $this->assertEquals('1-30', $this->service->getAgingBucket(30));
        $this->assertEquals('31-60', $this->service->getAgingBucket(45));
        $this->assertEquals('61-90', $this->service->getAgingBucket(75));
        $this->assertEquals('91-180', $this->service->getAgingBucket(120));
        $this->assertEquals('180+', $this->service->getAgingBucket(200));
    }

    /** @test */
    public function it_calculates_par_correctly()
    {
        // Portfolio: 10 loans worth 100M TZS total
        // 2 loans overdue (>1 day) worth 25M TZS
        // PAR = 25M / 100M = 25%

        $totalOutstanding = 100000000;
        $overdueAmount = 25000000;

        $par = $this->service->calculatePAR($overdueAmount, $totalOutstanding);

        $this->assertEquals(25.0, $par);
    }

    /** @test */
    public function it_calculates_par30_correctly()
    {
        // Total outstanding: 100M
        // Overdue >30 days: 15M
        // PAR30 = 15%

        $totalOutstanding = 100000000;
        $overdue30Plus = 15000000;

        $par30 = $this->service->calculatePAR30($overdue30Plus, $totalOutstanding);

        $this->assertEquals(15.0, $par30);
    }

    /** @test */
    public function it_calculates_npl_correctly()
    {
        // Total outstanding: 100M
        // Overdue >90 days (NPL): 10M
        // NPL = 10%

        $totalOutstanding = 100000000;
        $overdue90Plus = 10000000;

        $npl = $this->service->calculateNPL($overdue90Plus, $totalOutstanding);

        $this->assertEquals(10.0, $npl);
    }

    /** @test */
    public function it_handles_zero_portfolio()
    {
        $par = $this->service->calculatePAR(0, 0);
        $this->assertEquals(0.0, $par);

        $npl = $this->service->calculateNPL(0, 0);
        $this->assertEquals(0.0, $npl);
    }

    /** @test */
    public function it_calculates_collection_rate_correctly()
    {
        // Expected: 10M in month
        // Collected: 8M
        // Collection rate = 80%

        $expectedAmount = 10000000;
        $collectedAmount = 8000000;

        $rate = $this->service->calculateCollectionRate($collectedAmount, $expectedAmount);

        $this->assertEquals(80.0, $rate);
    }

    /** @test */
    public function it_assigns_correct_risk_grade_by_dpd()
    {
        // Current = Grade A
        $this->assertEquals('A', $this->service->getRiskGradeByDPD(0));
        
        // 1-30 days = Grade B
        $this->assertEquals('B', $this->service->getRiskGradeByDPD(15));
        
        // 31-60 days = Grade C
        $this->assertEquals('C', $this->service->getRiskGradeByDPD(45));
        
        // 61-90 days = Grade D
        $this->assertEquals('D', $this->service->getRiskGradeByDPD(75));
        
        // 90+ days = Grade E
        $this->assertEquals('E', $this->service->getRiskGradeByDPD(120));
    }

    /** @test */
    public function it_calculates_provision_requirement()
    {
        // Grade A: 1% provision
        $this->assertEquals(10000, $this->service->getProvisionAmount(1000000, 'A'));
        
        // Grade B: 5% provision
        $this->assertEquals(50000, $this->service->getProvisionAmount(1000000, 'B'));
        
        // Grade C: 25% provision
        $this->assertEquals(250000, $this->service->getProvisionAmount(1000000, 'C'));
        
        // Grade D: 50% provision
        $this->assertEquals(500000, $this->service->getProvisionAmount(1000000, 'D'));
        
        // Grade E: 100% provision
        $this->assertEquals(1000000, $this->service->getProvisionAmount(1000000, 'E'));
    }
}
