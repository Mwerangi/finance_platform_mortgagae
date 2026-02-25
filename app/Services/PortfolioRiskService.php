<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Service for portfolio risk calculations and metrics
 */
class PortfolioRiskService
{
    /**
     * Calculate Days Past Due
     * 
     * @param Carbon $dueDate The due date to calculate from
     * @return int Number of days past due (0 if not due yet)
     */
    public function calculateDPD(Carbon $dueDate): int
    {
        $now = Carbon::now();
        
        if ($now->lessThan($dueDate)) {
            return 0; // Not yet due
        }

        return abs($now->diffInDays($dueDate, false));
    }

    /**
     * Get aging bucket for a given DPD
     * 
     * @param int $dpd Days past due
     * @return string Aging bucket name
     */
    public function getAgingBucket(int $dpd): string
    {
        return match(true) {
            $dpd === 0 => 'current',
            $dpd >= 1 && $dpd <= 30 => '1-30',
            $dpd >= 31 && $dpd <= 60 => '31-60',
            $dpd >= 61 && $dpd <= 90 => '61-90',
            $dpd >= 91 && $dpd <= 180 => '91-180',
            $dpd > 180 => '180+',
            default => 'current',
        };
    }

    /**
     * Calculate Portfolio at Risk (PAR)
     * 
     * @param float $overdueAmount Amount that is overdue
     * @param float $totalOutstanding Total outstanding portfolio
     * @return float PAR percentage
     */
    public function calculatePAR(float $overdueAmount, float $totalOutstanding): float
    {
        if ($totalOutstanding <= 0) {
            return 0.0;
        }

        $par = ($overdueAmount / $totalOutstanding) * 100;

        return round($par, 2);
    }

    /**
     * Calculate PAR30 (Portfolio at Risk > 30 days)
     * 
     * @param float $overdue30Plus Amount overdue 30+ days
     * @param float $totalOutstanding Total outstanding portfolio
     * @return float PAR30 percentage
     */
    public function calculatePAR30(float $overdue30Plus, float $totalOutstanding): float
    {
        return $this->calculatePAR($overdue30Plus, $totalOutstanding);
    }

    /**
     * Calculate Non-Performing Loans (NPL) ratio
     * NPL is typically loans overdue by 90+ days
     * 
     * @param float $overdue90Plus Amount overdue 90+ days
     * @param float $totalOutstanding Total outstanding portfolio
     * @return float NPL percentage
     */
    public function calculateNPL(float $overdue90Plus, float $totalOutstanding): float
    {
        return $this->calculatePAR($overdue90Plus, $totalOutstanding);
    }

    /**
     * Calculate collection rate
     * 
     * @param float $collectedAmount Amount collected
     * @param float $expectedAmount Amount expected
     * @return float Collection rate percentage
     */
    public function calculateCollectionRate(float $collectedAmount, float $expectedAmount): float
    {
        if ($expectedAmount <= 0) {
            return 0.0;
        }

        $collectionRate = ($collectedAmount / $expectedAmount) * 100;

        // Cap at 100% (can't collect more than 100% of expected in this context)
        return round(min($collectionRate, 100.0), 2);
    }

    /**
     * Get risk grade based on Days Past Due
     * 
     * @param int $dpd Days past due
     * @return string Risk grade (A, B, C, D, E)
     */
    public function getRiskGradeByDPD(int $dpd): string
    {
        return match(true) {
            $dpd === 0 => 'A',                    // Current
            $dpd >= 1 && $dpd <= 30 => 'B',       // 1-30 days
            $dpd >= 31 && $dpd <= 60 => 'C',      // 31-60 days
            $dpd >= 61 && $dpd <= 90 => 'D',      // 61-90 days
            $dpd > 90 => 'E',                     // 90+ days (NPL)
            default => 'A',
        };
    }

    /**
     * Calculate provision amount based on risk grade
     * 
     * Provision rates:
     * - A (Current): 1%
     * - B (1-30 days): 5%
     * - C (31-60 days): 25%
     * - D (61-90 days): 50%
     * - E (90+ days): 100%
     * 
     * @param float $amount Outstanding amount
     * @param string $riskGrade Risk grade (A-E)
     * @return float Provision amount required
     */
    public function getProvisionAmount(float $amount, string $riskGrade): float
    {
        $provisionRate = match($riskGrade) {
            'A' => 0.01,   // 1%
            'B' => 0.05,   // 5%
            'C' => 0.25,   // 25%
            'D' => 0.50,   // 50%
            'E' => 1.00,   // 100%
            default => 0.01,
        };

        $provision = $amount * $provisionRate;

        return round($provision, 2);
    }
}
