<?php

namespace App\Services;

use App\Models\LoanKeyword;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * LoanDetectionService
 * 
 * Detects hidden loans in bank statement transactions using:
 * 1. Keyword matching (configurable Tanzania-specific keywords)
 * 2. Recurrence detection (similar debits appearing monthly)
 * 3. Confidence scoring (high/medium/low based on evidence)
 */
class LoanDetectionService
{
    protected $keywords;
    protected $institutionId;

    /**
     * Constructor
     * 
     * @param int|null $institutionId
     */
    public function __construct(?int $institutionId = null)
    {
        $this->institutionId = $institutionId;
        $this->keywords = LoanKeyword::getActiveKeywords($institutionId);
    }

    /**
     * Detect loans from transaction data
     * 
     * @param Collection $transactions - Collection of transaction objects with: date, description, debit, credit
     * @return array [
     *   'detected_loans' => [],
     *   'total_monthly_repayment' => 0,
     *   'loan_count' => 0,
     *   'loan_stacking_detected' => false,
     *   'confidence' => 'high|medium|low',
     *   'loan_inflows' => 0
     * ]
     */
    public function detectLoans(Collection $transactions): array
    {
        // Separate debits and credits
        $debits = $transactions->where('debit', '>', 0);
        $credits = $transactions->where('credit', '>', 0);

        // Detect loan repayments (debits)
        $repaymentKeywords = $this->keywords->where('type', 'repayment');
        $detectedRepayments = $this->detectByKeywordAndRecurrence($debits, $repaymentKeywords);

        // Detect loan disbursements (credits)
        $disbursementKeywords = $this->keywords->where('type', 'disbursement');
        $loanInflows = $this->detectLoanDisbursements($credits, $disbursementKeywords);

        // Calculate total monthly repayment
        $totalMonthlyRepayment = collect($detectedRepayments)->sum('monthly_amount');

        // Determine if loan stacking is detected (3+ active loans)
        $loanStacking = count($detectedRepayments) >= 3;

        // Calculate overall confidence
        $confidence = $this->calculateOverallConfidence($detectedRepayments);

        return [
            'detected_loans' => $detectedRepayments,
            'total_monthly_repayment' => $totalMonthlyRepayment,
            'loan_count' => count($detectedRepayments),
            'loan_stacking_detected' => $loanStacking,
            'confidence' => $confidence,
            'loan_inflows' => $loanInflows['total_amount'],
            'loan_disbursement_details' => $loanInflows['details'],
        ];
    }

    /**
     * Detect loan repayments by keyword matching and recurrence
     * 
     * @param Collection $debits
     * @param Collection $keywords
     * @return array
     */
    protected function detectByKeywordAndRecurrence(Collection $debits, Collection $keywords): array
    {
        $detectedLoans = [];
        $processedGroups = [];

        // Step 0: Filter out non-loan transactions (cash withdrawals, fees, commissions)
        $debits = $this->excludeNonLoanTransactions($debits);

        // Step 1: Group similar transactions
        $groups = $this->groupSimilarTransactions($debits);

        foreach ($groups as $groupKey => $group) {
            if (in_array($groupKey, $processedGroups)) {
                continue;
            }

            $sampleTransaction = $group->first();
            $description = strtoupper($sampleTransaction['description'] ?? '');

            // Check for keyword match
            $matchedKeyword = $this->findMatchingKeyword($description, $keywords);

            // Check for recurrence pattern
            $isRecurring = $this->isRecurringPattern($group);

            // Determine confidence
            if ($matchedKeyword && $isRecurring) {
                $confidence = 'high';
                $reason = 'Keyword match + recurring pattern';
            } elseif ($matchedKeyword) {
                $confidence = 'medium';
                $reason = 'Keyword match only';
            } elseif ($isRecurring && $group->count() >= 3) {
                $confidence = 'medium';
                $reason = 'Recurring pattern (3+ occurrences)';
            } else {
                continue; // Skip if confidence is too low
            }

            // Calculate average monthly amount
            $averageAmount = $group->avg('debit');
            $occurrences = $group->count();

            $detectedLoans[] = [
                'description' => $sampleTransaction['description'],
                'lender_name' => $this->extractLenderName($description, $matchedKeyword),
                'monthly_amount' => round($averageAmount, 2),
                'occurrences' => $occurrences,
                'confidence' => $confidence,
                'reason' => $reason,
                'keyword_matched' => $matchedKeyword ? $matchedKeyword['keyword'] : null,
                'first_seen' => $group->min('date'),
                'last_seen' => $group->max('date'),
                'transactions' => $group->map(fn($t) => [
                    'date' => $t['date'],
                    'amount' => $t['debit'],
                    'description' => $t['description'],
                ])->toArray(),
            ];

            $processedGroups[] = $groupKey;
        }

        return $detectedLoans;
    }

    /**
     * Detect loan disbursements (bulk credits from lenders)
     * 
     * @param Collection $credits
     * @param Collection $keywords
     * @return array
     */
    protected function detectLoanDisbursements(Collection $credits, Collection $keywords): array
    {
        $disbursements = [];
        $totalAmount = 0;

        foreach ($credits as $transaction) {
            $description = strtoupper($transaction['description'] ?? '');
            $matchedKeyword = $this->findMatchingKeyword($description, $keywords);

            if ($matchedKeyword) {
                $disbursements[] = [
                    'date' => $transaction['date'],
                    'amount' => $transaction['credit'],
                    'description' => $transaction['description'],
                    'keyword_matched' => $matchedKeyword['keyword'],
                    'lender_name' => $this->extractLenderName($description, $matchedKeyword),
                ];

                $totalAmount += $transaction['credit'];
            }
        }

        return [
            'details' => $disbursements,
            'total_amount' => $totalAmount,
            'count' => count($disbursements),
        ];
    }

    /**
     * Group similar transactions based on description and amount similarity
     * 
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupSimilarTransactions(Collection $transactions): Collection
    {
        $groups = [];

        foreach ($transactions as $transaction) {
            $description = $this->normalizeDescription($transaction['description'] ?? '');
            $amount = $transaction['debit'];

            // Create a fuzzy key based on description pattern and amount range
            $amountKey = floor($amount / 10000) * 10000; // Group by 10k increments
            $descriptionKey = Str::limit($description, 30, '');
            $groupKey = md5($descriptionKey . '_' . $amountKey);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = collect();
            }

            $groups[$groupKey]->push($transaction);
        }

        // Filter groups: keep only those with similar amounts (within 10% variance)
        return collect($groups)->map(function ($group) {
            $amounts = $group->pluck('debit');
            $avgAmount = $amounts->avg();
            
            return $group->filter(function ($transaction) use ($avgAmount) {
                $variance = abs($transaction['debit'] - $avgAmount) / $avgAmount;
                return $variance <= 0.10; // 10% tolerance
            });
        })->filter(function ($group) {
            return $group->count() >= 2; // At least 2 occurrences
        });
    }

    /**
     * Check if transactions show a recurring monthly pattern
     * 
     * @param Collection $transactions
     * @return bool
     */
    protected function isRecurringPattern(Collection $transactions): bool
    {
        if ($transactions->count() < 2) {
            return false;
        }

        $dates = $transactions->pluck('date')->sort()->values();
        $intervals = [];

        for ($i = 1; $i < $dates->count(); $i++) {
            $date1 = \Carbon\Carbon::parse($dates[$i - 1]);
            $date2 = \Carbon\Carbon::parse($dates[$i]);
            $daysDiff = $date1->diffInDays($date2);
            $intervals[] = $daysDiff;
        }

        // Check if intervals are approximately monthly (25-35 days)
        $monthlyIntervals = collect($intervals)->filter(function ($days) {
            return $days >= 25 && $days <= 35;
        });

        return $monthlyIntervals->count() >= count($intervals) * 0.7; // 70% of intervals are monthly
    }

    /**
     * Find matching keyword in description
     * 
     * @param string $description
     * @param Collection $keywords
     * @return array|null
     */
    protected function findMatchingKeyword(string $description, Collection $keywords): ?array
    {
        $upperDescription = strtoupper($description);

        // Sort keywords by weight (highest first)
        $sortedKeywords = $keywords->sortByDesc('weight');

        foreach ($sortedKeywords as $keyword) {
            $upperKeyword = strtoupper($keyword->keyword);
            
            if (Str::contains($upperDescription, $upperKeyword)) {
                return [
                    'keyword' => $keyword->keyword,
                    'weight' => $keyword->weight,
                    'type' => $keyword->type,
                    'language' => $keyword->language,
                ];
            }
        }

        return null;
    }

    /**
     * Extract lender name from description
     * 
     * @param string $description
     * @param array|null $matchedKeyword
     * @return string
     */
    protected function extractLenderName(string $description, ?array $matchedKeyword): string
    {
        if (!$matchedKeyword) {
            return 'Unknown Lender';
        }

        $keyword = $matchedKeyword['keyword'];

        // If keyword is a bank/lender name, return it
        $lenderKeywords = ['CRDB', 'NMB', 'NBC', 'ABSA', 'STANBIC', 'TALA', 'BRANCH', 'BAYPORT', 'M-PESA'];
        foreach ($lenderKeywords as $lender) {
            if (Str::contains(strtoupper($description), $lender)) {
                return $lender;
            }
        }

        // Extract first word that looks like a lender name (capitalized, 3+ chars)
        $words = explode(' ', $description);
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && ctype_upper($word[0])) {
                return $word;
            }
        }

        return $keyword;
    }

    /**
     * Normalize description for comparison
     * 
     * @param string $description
     * @return string
     */
    protected function normalizeDescription(string $description): string
    {
        // Remove common noise words and special characters
        $description = strtoupper($description);
        $description = preg_replace('/[^A-Z0-9\s]/', ' ', $description);
        $description = preg_replace('/\s+/', ' ', $description);
        
        // Remove dates and transaction IDs
        $description = preg_replace('/\d{4}[-\/]\d{2}[-\/]\d{2}/', '', $description);
        $description = preg_replace('/\b\d{6,}\b/', '', $description);
        
        return trim($description);
    }

    /**
     * Exclude non-loan transactions (cash withdrawals, fees, commissions)
     * 
     * @param Collection $transactions
     * @return Collection
     */
    protected function excludeNonLoanTransactions(Collection $transactions): Collection
    {
        // Patterns that should NOT be classified as loan repayments
        $exclusionPatterns = [
            'CASH WITHDRAWAL',
            'ATM WITHDRAWAL',
            'ATM CASH WDL',
            'ATM CHARGES',
            'ATM FEE',
            'WITHDRAW',
            'MAINTENANCE FEE',
            'MONTHLY FEE',
            'SERVICE FEE',
            'ANNUAL FEE',
            'SMS FEE',
            'COMMISSION',
            'BANK CHARGES',
            'STANDING ORDER FEE',
            'LEDGER FEE',
            'STATEMENT FEE',
            'ACCOUNT FEE',
            'TRANSFER FEE',
            'TRANSACTION FEE',
            'PROCESSING FEE',
            'HANDLING FEE',
            'EXCISE DUTY',
            'VAT',
            'TAX WITHHOLDING',
            'GOVERNMENT LEVY',
            'GOVERNMENT CHARGES',
            'GOVERNMENT TAX',
            'GOVERNMENT FEE',
            'E-COM PURCHASE',
            'E-COMMERCE',
            'ECOMMERCE',
            'ONLINE PURCHASE',
            'ONLINE SHOPPING',
            'CREDIT CARD PAYMENT', // Unless it explicitly says LOAN
            'UTILITY',
            'ELECTRICITY',
            'WATER BILL',
            'RENT PAYMENT',
        ];

        return $transactions->filter(function ($transaction) use ($exclusionPatterns) {
            $description = strtoupper($transaction['description'] ?? '');

            // Exclude if matches any exclusion pattern
            foreach ($exclusionPatterns as $pattern) {
                if (Str::contains($description, $pattern)) {
                    // However, if description explicitly contains LOAN keywords, keep it
                    $loanKeywords = ['LOAN', 'MKOPO', 'CREDIT LINE', 'ADVANCE', 'LENDING'];
                    $hasLoanKeyword = false;
                    
                    foreach ($loanKeywords as $keyword) {
                        if (Str::contains($description, $keyword)) {
                            $hasLoanKeyword = true;
                            break;
                        }
                    }

                    if (!$hasLoanKeyword) {
                        return false; // Exclude this transaction
                    }
                }
            }

            return true; // Keep this transaction
        });
    }

    /**
     * Calculate overall confidence level
     * 
     * @param array $detectedLoans
     * @return string
     */
    protected function calculateOverallConfidence(array $detectedLoans): string
    {
        if (empty($detectedLoans)) {
            return 'none';
        }

        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;

        foreach ($detectedLoans as $loan) {
            switch ($loan['confidence']) {
                case 'high':
                    $highCount++;
                    break;
                case 'medium':
                    $mediumCount++;
                    break;
                case 'low':
                    $lowCount++;
                    break;
            }
        }

        // If majority are high confidence, return high
        if ($highCount >= count($detectedLoans) * 0.6) {
            return 'high';
        }

        // If mostly high or medium, return medium
        if (($highCount + $mediumCount) >= count($detectedLoans) * 0.7) {
            return 'medium';
        }

        return 'low';
    }
}
