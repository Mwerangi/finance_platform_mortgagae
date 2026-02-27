<?php

namespace Database\Seeders;

use App\Models\LoanKeyword;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanKeywordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds Tanzania loan detection keywords for identifying:
     * - Loan repayments (recurring debits)
     * - Loan disbursements (bulk credits)
     */
    public function run(): void
    {
        // Clear existing keywords
        LoanKeyword::truncate();

        // 1️⃣ GENERIC LOAN KEYWORDS (ENGLISH) - Repayment
        $genericEnglish = [
            ['keyword' => 'LOAN', 'weight' => 10],
            ['keyword' => 'LOAN REPAYMENT', 'weight' => 10],
            ['keyword' => 'LOAN INSTALLMENT', 'weight' => 10],
            ['keyword' => 'LOAN DEDUCTION', 'weight' => 10],
            ['keyword' => 'REPAYMENT', 'weight' => 9],
            ['keyword' => 'EMI', 'weight' => 9],
            ['keyword' => 'CREDIT FACILITY', 'weight' => 8],
            ['keyword' => 'TERM LOAN', 'weight' => 8],
            ['keyword' => 'OVERDRAFT', 'weight' => 7],
            ['keyword' => 'OD REPAYMENT', 'weight' => 8],
            ['keyword' => 'SALARY ADVANCE', 'weight' => 7],
            ['keyword' => 'ADVANCE REPAYMENT', 'weight' => 8],
            ['keyword' => 'FINANCE', 'weight' => 6],
            ['keyword' => 'CREDIT PAYMENT', 'weight' => 7],
        ];

        foreach ($genericEnglish as $kw) {
            LoanKeyword::create([
                'institution_id' => null,
                'keyword' => $kw['keyword'],
                'type' => 'repayment',
                'language' => 'english',
                'weight' => $kw['weight'],
                'is_active' => true,
                'description' => 'Generic English loan repayment keyword',
            ]);
        }

        // 2️⃣ SWAHILI LOAN KEYWORDS - Repayment
        $swahili = [
            ['keyword' => 'MKOPO', 'weight' => 10],
            ['keyword' => 'REJESHO', 'weight' => 10],
            ['keyword' => 'MAREJESHO', 'weight' => 10],
            ['keyword' => 'MAREJESHO YA MKOPO', 'weight' => 10],
            ['keyword' => 'MALIPO YA MKOPO', 'weight' => 10],
            ['keyword' => 'KUREJESHA', 'weight' => 9],
            ['keyword' => 'DENI', 'weight' => 8],
            ['keyword' => 'MALIPO YA DENI', 'weight' => 9],
        ];

        foreach ($swahili as $kw) {
            LoanKeyword::create([
                'institution_id' => null,
                'keyword' => $kw['keyword'],
                'type' => 'repayment',
                'language' => 'swahili',
                'weight' => $kw['weight'],
                'is_active' => true,
                'description' => 'Swahili loan repayment keyword for Tanzania market',
            ]);
        }

        // 3️⃣ TANZANIAN BANKS & LENDERS - Repayment
        $tanzanianBanks = [
            // Traditional Banks
            ['keyword' => 'CRDB', 'weight' => 8],
            ['keyword' => 'NMB', 'weight' => 8],
            ['keyword' => 'NBC', 'weight' => 8],
            ['keyword' => 'ABSA', 'weight' => 8],
            ['keyword' => 'STANBIC', 'weight' => 8],
            ['keyword' => 'EXIM', 'weight' => 8],
            ['keyword' => 'AZANIA', 'weight' => 8],
            ['keyword' => 'ACCESS BANK', 'weight' => 8],
            ['keyword' => 'EQUITY BANK', 'weight' => 8],
            ['keyword' => 'DTB', 'weight' => 8],
            ['keyword' => 'UBA', 'weight' => 8],
            ['keyword' => 'KCB', 'weight' => 8],
            
            // Mobile / Digital Lenders
            ['keyword' => 'M-PESA LOAN', 'weight' => 9],
            ['keyword' => 'TALA', 'weight' => 9],
            ['keyword' => 'BRANCH', 'weight' => 7], // Lower weight - common word
            ['keyword' => 'OKASH', 'weight' => 9],
            ['keyword' => 'FINCA', 'weight' => 8],
            ['keyword' => 'BAYPORT', 'weight' => 9],
            ['keyword' => 'LETSHEGO', 'weight' => 9],
            ['keyword' => 'TUJIJENGE', 'weight' => 9],
            ['keyword' => 'VISIONFUND', 'weight' => 9],
            
            // SACCOS Indicators
            ['keyword' => 'SACCOS', 'weight' => 7],
            ['keyword' => 'COOP', 'weight' => 6], // Lower weight - common abbreviation
            ['keyword' => 'USHIRIKA', 'weight' => 8],
            ['keyword' => 'CHAMA', 'weight' => 6], // Lower weight - can mean group savings
        ];

        foreach ($tanzanianBanks as $kw) {
            LoanKeyword::create([
                'institution_id' => null,
                'keyword' => $kw['keyword'],
                'type' => 'repayment',
                'language' => 'mixed',
                'weight' => $kw['weight'],
                'is_active' => true,
                'description' => 'Tanzanian bank/lender name - indicates loan repayment when recurring',
            ]);
        }

        // 4️⃣ LOAN DISBURSEMENT KEYWORDS (Credit Side)
        $disbursements = [
            ['keyword' => 'LOAN DISBURSEMENT', 'weight' => 10, 'lang' => 'english'],
            ['keyword' => 'LOAN PROCEEDS', 'weight' => 10, 'lang' => 'english'],
            ['keyword' => 'CREDIT FACILITY', 'weight' => 8, 'lang' => 'english'],
            ['keyword' => 'MKOPO', 'weight' => 10, 'lang' => 'swahili'],
            ['keyword' => 'ADVANCE', 'weight' => 7, 'lang' => 'english'],
            
            // Bank-specific disbursement patterns
            ['keyword' => 'CRDB LOAN', 'weight' => 9, 'lang' => 'mixed'],
            ['keyword' => 'NMB LOAN', 'weight' => 9, 'lang' => 'mixed'],
            ['keyword' => 'BAYPORT LOAN', 'weight' => 10, 'lang' => 'mixed'],
            ['keyword' => 'BRANCH LOAN', 'weight' => 9, 'lang' => 'mixed'],
            ['keyword' => 'TALA LOAN', 'weight' => 10, 'lang' => 'mixed'],
            ['keyword' => 'M-PESA LOAN', 'weight' => 10, 'lang' => 'mixed'],
        ];

        foreach ($disbursements as $kw) {
            LoanKeyword::create([
                'institution_id' => null,
                'keyword' => $kw['keyword'],
                'type' => 'disbursement',
                'language' => $kw['lang'],
                'weight' => $kw['weight'],
                'is_active' => true,
                'description' => 'Loan disbursement keyword - detects bulk deposits from loans',
            ]);
        }

        $this->command->info('✅ Seeded ' . LoanKeyword::count() . ' loan detection keywords for Tanzania market');
    }
}

