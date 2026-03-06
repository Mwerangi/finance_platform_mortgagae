<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mortgage Pre-Qualification Report - {{ $prospect->first_name }} {{ $prospect->last_name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2937;
            background: #f4f6f8;
            font-size: 11px;
            line-height: 1.4;
        }

        .page {
            width: 100%;
            padding: 8px;
            background: #f4f6f8;
        }

        .report-container {
            background: #ffffff;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: #ffffff;
            padding: 12px 16px 2px;
        }

        .header-top {
            display: table;
            width: 100%;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-right {
            text-align: right;
            width: 260px;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px;
            letter-spacing: 0.3px;
        }

        .report-subtitle {
            font-size: 11px;
            opacity: 0.9;
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-conditional {
            background: #fef3c7;
            color: #92400e;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .header-meta {
            margin-top: 4px;
            display: table;
            width: 100%;
            border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 4px;
            margin-bottom: 0;
        }

        .meta-item {
            display: table-cell;
            width: 25%;
            padding-right: 12px;
        }

        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            opacity: 0.75;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: bold;
        }

        .content {
            padding: 2px 14px 14px;
        }

        .section {
            margin-bottom: 4px;
        }

        .section:first-child {
            margin-top: 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e40af;
            border-left: 3px solid #2563eb;
            padding-left: 8px;
            margin: 0 0 2px;
            letter-spacing: 0.3px;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            padding: 6px 8px;
        }

        .summary-box p {
            margin: 0 0 2px;
            line-height: 1.4;
        }

        .summary-box p:last-child {
            margin-bottom: 0;
        }

        .confidence-banner {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            padding: 3px 6px;
            margin-bottom: 3px;
            border-radius: 4px;
            display: table;
            width: 100%;
        }

        .confidence-left {
            display: table-cell;
            width: 70%;
            vertical-align: middle;
        }

        .confidence-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .confidence-badge {
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }

        .drivers-grid,
        .metrics-grid,
        .two-col,
        .four-col {
            width: 100%;
            display: table;
            table-layout: fixed;
            border-spacing: 0;
        }

        .driver-card,
        .metric-card,
        .col,
        .mini-card {
            display: table-cell;
            vertical-align: top;
        }

        .driver-card {
            width: 33.33%;
            padding: 0 6px;
        }

        .driver-card-inner {
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            padding: 10px;
            background: #fff;
        }

        .driver-positive {
            border-top: 4px solid #10b981;
        }

        .driver-risk {
            border-top: 4px solid #ef4444;
        }

        .driver-outcome {
            border-top: 4px solid #f59e0b;
        }

        .driver-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #0f172a;
        }

        .driver-list {
            margin: 0;
            padding-left: 14px;
            font-size: 10px;
        }

        .driver-list li {
            margin-bottom: 3px;
            line-height: 1.4;
        }

        .metrics-grid {
            margin-top: 3px;
        }

        .metric-card {
            width: 20%;
            padding: 0 6px;
        }

        .metric-card-inner {
            background: #f8fafc;
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            padding: 6px 4px;
            text-align: center;
        }

        .metric-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .metric-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .metric-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 1px;
        }

        .col {
            width: 50%;
            padding: 0 8px;
        }

        .card {
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            padding: 8px 10px;
            background: #ffffff;
        }

        .card-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #1e40af;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table tr td {
            padding: 3px 0;
            border-bottom: 1px dashed #e5e7eb;
            vertical-align: top;
            font-size: 10px;
        }

        .detail-table tr:last-child td {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
            width: 56%;
        }

        .detail-value {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .narrative {
            margin-top: 4px;
            color: #374151;
            font-size: 9.5px;
            line-height: 1.3;
        }

        .highlight-panel {
            background: #f9fbfd;
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            padding: 8px;
        }

        .highlight-grid {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .mini-card {
            width: 25%;
            padding: 0 6px;
        }

        .mini-card-inner {
            border: 1px solid #dbe7f0;
            border-radius: 6px;
            background: #fff;
            padding: 6px;
            text-align: center;
        }

        .mini-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .mini-value {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .risk-box {
            border: 1px solid #f1d5d5;
            background: #fff7f7;
            border-radius: 6px;
            padding: 8px 10px;
        }

        .risk-grade {
            font-size: 16px;
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 2px;
        }

        .risk-score {
            color: #7f1d1d;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .conditions-box {
            border: 1px solid #fde7c7;
            background: #fffaf0;
            border-radius: 6px;
            padding: 8px 10px;
        }

        .conditions-box ol {
            margin: 4px 0 0 16px;
            padding: 0;
            font-size: 10px;
        }

        .conditions-box li {
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .recommendation-box {
            border: 2px solid #2563eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .recommendation-head {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 11px;
        }

        .recommendation-body {
            padding: 8px 10px;
            background: #ffffff;
        }

        .recommendation-status {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .recommendation-grid {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .recommendation-grid .mini-card {
            width: 50%;
        }

        .disclaimer {
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            margin-top: 6px;
            line-height: 1.3;
        }

        .footer {
            padding: 6px 12px 8px;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }

        .text-success { color: #065f46; }
        .text-warning { color: #92400e; }
        .text-danger { color: #991b1b; }
        .text-error { color: #dc2626; }

        @page {
            margin: 8mm 8mm;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            h2.section-title { page-break-after: avoid; }
            .recommendation-box { page-break-before: always; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="report-container">

        @php
            $decisionClass = 'status-conditional';
            $decisionText = 'CONDITIONALLY PRE-QUALIFIED';
            
            if (strtolower($assessment->system_decision) === 'approved' || strtolower($assessment->system_decision) === 'eligible') {
                $decisionClass = 'status-approved';
                $decisionText = '✓ PRE-QUALIFIED FOR MORTGAGE';
            } elseif (strtolower($assessment->system_decision) === 'rejected' || strtolower($assessment->system_decision) === 'not_recommended') {
                $decisionClass = 'status-rejected';
                $decisionText = '✗ NOT PRE-QUALIFIED';
            }

            // Calculate confidence score
            $confidenceScore = 0;
            $transactionCount = $data_quality['transaction_count'];
            if ($transactionCount >= 2000) {
                $confidenceScore += 30;
            } elseif ($transactionCount >= 1000) {
                $confidenceScore += 25;
            } elseif ($transactionCount >= 500) {
                $confidenceScore += 20;
            } elseif ($transactionCount >= 200) {
                $confidenceScore += 15;
            } elseif ($transactionCount >= 100) {
                $confidenceScore += 10;
            } else {
                $confidenceScore += 5;
            }
            
            $monthsCount = $data_quality['months_count'];
            if ($monthsCount >= 12) {
                $confidenceScore += 25;
            } elseif ($monthsCount >= 9) {
                $confidenceScore += 20;
            } elseif ($monthsCount >= 6) {
                $confidenceScore += 15;
            } elseif ($monthsCount >= 3) {
                $confidenceScore += 10;
            } else {
                $confidenceScore += 5;
            }
            
            $incomeStability = $analytics ? ($analytics->income_stability_score ?? 0) : 0;
            if ($incomeStability >= 80) {
                $confidenceScore += 25;
            } elseif ($incomeStability >= 70) {
                $confidenceScore += 20;
            } elseif ($incomeStability >= 50) {
                $confidenceScore += 15;
            } elseif ($incomeStability >= 30) {
                $confidenceScore += 10;
            } else {
                $confidenceScore += 5;
            }
            
            $volatility = $analytics ? ($analytics->cash_flow_volatility_score ?? 100) : 100;
            if ($volatility < 10) {
                $confidenceScore += 20;
            } elseif ($volatility < 20) {
                $confidenceScore += 15;
            } elseif ($volatility < 30) {
                $confidenceScore += 12;
            } elseif ($volatility < 40) {
                $confidenceScore += 8;
            } else {
                $confidenceScore += 5;
            }
            
            if ($confidenceScore >= 80) {
                $confidenceLevel = 'High';
                $confidenceLevelColor = '#047857';
                $confidenceLevelBg = '#10b981';
            } elseif ($confidenceScore >= 60) {
                $confidenceLevel = 'Moderate';
                $confidenceLevelColor = '#d97706';
                $confidenceLevelBg = '#f59e0b';
            } else {
                $confidenceLevel = 'Low';
                $confidenceLevelColor = '#dc2626';
                $confidenceLevelBg = '#ef4444';
            }
        @endphp

        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    <h1 class="report-title">Mortgage Pre-Qualification Report</h1>
                    <p class="report-subtitle">Automated Financial Assessment & Recommendation</p>
                </div>
                <div class="header-right">
                    <div class="status-badge {{ $decisionClass }}">
                        {{ $decisionText }}
                    </div>
                </div>
            </div>

            <div class="header-meta">
                <div class="meta-item">
                    <div class="meta-label">Applicant</div>
                    <div class="meta-value">{{ $prospect->first_name }} {{ $prospect->last_name }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Report ID</div>
                    <div class="meta-value">#{{ str_pad($prospect->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Generated</div>
                    <div class="meta-value">{{ now()->format('d M Y, H:i') }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Analysis Period</div>
                    <div class="meta-value">{{ $monthsCount ?? 0 }} Months</div>
                </div>
            </div>
        </div>

        <div class="content">

            <!-- EXECUTIVE SUMMARY -->
            <div class="section">
                <h2 class="section-title">Executive Summary</h2>
                
                <!-- Decision Confidence -->
                <div class="confidence-banner">
                    <div class="confidence-left">
                        <span style="font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px;">Decision Confidence:</span>
                        <span style="font-size: 12px; font-weight: 600; color: {{ $confidenceLevelColor }}; margin-left: 5px;">{{ $confidenceScore }}%</span>
                    </div>
                    <div class="confidence-right">
                        <div class="confidence-badge" style="background: {{ $confidenceLevelBg }};">
                            {{ strtoupper($confidenceLevel) }} CONFIDENCE
                        </div>
                    </div>
                </div>

                <div class="summary-box">
                    @php
                        $maxInstallment = $assessment->max_installment_from_income ?? 0;
                        $disposableIncome = $assessment->net_monthly_income - $assessment->total_monthly_debt;
                    @endphp
                    <p>
                        This automated assessment analyzed <strong>{{ number_format($data_quality['transaction_count']) }} bank transactions</strong> over a <strong>{{ $data_quality['months_count'] }}-month period</strong> to evaluate the applicant's financial capacity for mortgage financing. The applicant demonstrates {{ $assessment->net_monthly_income > 5000000 ? 'strong' : 'moderate' }} repayment capacity, with an estimated average monthly income of <strong>TZS {{ number_format($assessment->net_monthly_income, 0) }}</strong> and disposable income of approximately <strong>TZS {{ number_format($disposableIncome, 0) }}</strong> after accounting for living expenses and existing obligations.
                    </p>
                    @if($analytics && $analytics->income_stability_score < 50)
                    <p>
                        However, the analysis identified {{ $analytics->cash_flow_volatility_score > 30 ? 'high income volatility' : 'moderate income fluctuation' }} and {{ $analytics->income_stability_score < 30 ? 'low' : 'moderate' }} income consistency, indicating that income deposits do not follow a stable monthly pattern. While the applicant shows adequate financial capacity to support mortgage repayments, the irregular income pattern introduces additional lending risk.
                    </p>
                    @endif
                    <p>
                        Based on the available financial data, the system <strong>{{ strtolower($decisionText) }}</strong> the applicant for a mortgage loan of up to <strong>TZS {{ number_format($assessment->final_max_loan ?? 0, 0) }}</strong>, with an estimated monthly installment of approximately <strong>TZS {{ number_format($maxInstallment, 0) }}</strong>{{ isset($conditions) && count($conditions) > 0 ? ', subject to verification of income stability and additional underwriting review' : '' }}.
                    </p>
                </div>
            </div>

            <!-- KEY DECISION DRIVERS -->
            <div class="section">
                <h2 class="section-title">Key Decision Drivers</h2>
                <div class="drivers-grid">
                    @php
                        // Build positive factors
                        $positiveFactors = [];
                        
                        if ($assessment->net_monthly_income >= 3000000) {
                            $positiveFactors[] = 'Average monthly income of <strong>TZS ' . number_format($assessment->net_monthly_income, 0) . '</strong>';
                        }
                        
                        $disposableIncome = $assessment->net_monthly_income - ($assessment->net_monthly_income * 0.35) - $assessment->total_monthly_debt;
                        if ($disposableIncome >= 1000000) {
                            $positiveFactors[] = 'Disposable income of <strong>TZS ' . number_format($disposableIncome, 0) . '</strong>';
                        }
                        
                        if ($assessment->dti_ratio < 40) {
                            $positiveFactors[] = 'Debt-to-Income ratio of <strong>' . number_format($assessment->dti_ratio, 1) . '%</strong>';
                        }
                        
                        if ($analytics && $analytics->negative_balance_days <= 5) {
                            $positiveFactors[] = 'Minimal negative balance days: <strong>' . $analytics->negative_balance_days . '</strong>';
                        }
                        
                        if ($analytics && $analytics->income_stability_score >= 50) {
                            $positiveFactors[] = 'Income stability score of <strong>' . $analytics->income_stability_score . '/100</strong>';
                        }
                        
                        if ($assessment->dsr_ratio < 50) {
                            $positiveFactors[] = 'Debt Service Ratio of <strong>' . number_format($assessment->dsr_ratio, 1) . '%</strong>';
                        }
                        
                        if (empty($positiveFactors)) {
                            $positiveFactors[] = 'Financial profile reviewed for mortgage eligibility';
                        }

                        // Build risk factors
                        $riskFactors = [];
                        
                        if ($analytics && $analytics->cash_flow_volatility_score > 30) {
                            $riskFactors[] = 'Income volatility of <strong>' . number_format($analytics->cash_flow_volatility_score, 1) . '%</strong>';
                        }
                        
                        if ($analytics && $analytics->income_stability_score < 50) {
                            $riskFactors[] = 'Income consistency score of <strong>' . $analytics->income_stability_score . '/100</strong>';
                        }
                        
                        if ($assessment->dti_ratio >= 40) {
                            $riskFactors[] = 'High DTI ratio of <strong>' . number_format($assessment->dti_ratio, 1) . '%</strong>';
                        }
                        
                        if ($analytics && $analytics->negative_balance_days > 5) {
                            $riskFactors[] = 'Negative balance days: <strong>' . $analytics->negative_balance_days . '</strong>';
                        }
                        
                        if ($analytics && $analytics->bounce_count > 0) {
                            $riskFactors[] = 'Payment bounces detected: <strong>' . $analytics->bounce_count . '</strong>';
                        }
                        
                        if ($data_quality['months_count'] < 6) {
                            $riskFactors[] = 'Limited analysis period: <strong>' . $data_quality['months_count'] . ' months</strong>';
                        }
                        
                        if (empty($riskFactors)) {
                            $riskFactors[] = 'No significant risk factors identified';
                        }

                        // Decision outcome
                        $outcomeText = '';
                        $outcomeColor = '#1e40af';
                        $outcomeBg = '#eff6ff';
                        
                        if (strtolower($assessment->system_decision) === 'approved' || strtolower($assessment->system_decision) === 'eligible') {
                            if (count($riskFactors) <= 1 || (count($positiveFactors) >= count($riskFactors) + 2)) {
                                $outcomeText = 'The applicant is PRE-QUALIFIED for mortgage financing. The positive factors significantly outweigh the identified risks, and the applicant demonstrates strong financial capacity to support the requested loan amount with comfortable repayment margins.';
                                $outcomeColor = '#047857';
                                $outcomeBg = '#d1fae5';
                            }
                        } elseif (strtolower($assessment->system_decision) === 'conditional') {
                            if (isset($conditions) && count($conditions) > 0) {
                                $outcomeText = 'The applicant is CONDITIONALLY PRE-QUALIFIED subject to addressing specific requirements. While the financial capacity is adequate, certain risk factors require mitigation through additional verification, documentation, or loan structure adjustments before final approval.';
                                $outcomeColor = '#d97706';
                                $outcomeBg = '#fef3c7';
                            }
                        } elseif (strtolower($assessment->system_decision) === 'rejected' || strtolower($assessment->system_decision) === 'not_recommended') {
                            if (count($riskFactors) > count($positiveFactors) || $assessment->dti_ratio > 50) {
                                $outcomeText = 'The applicant is NOT PRE-QUALIFIED at this time. The identified risk factors significantly outweigh the positive factors, and the current financial profile does not meet minimum lending standards. The applicant should address the concerns and reapply after financial improvements.';
                                $outcomeColor = '#dc2626';
                                $outcomeBg = '#fee2e2';
                            }
                        }
                        
                        if (empty($outcomeText)) {
                            $outcomeText = 'Assessment completed. Review detailed factors above for comprehensive understanding of the decision rationale.';
                        }
                    @endphp

                    <div class="driver-card">
                        <div class="driver-card-inner driver-positive">
                            <div class="driver-title">Positive Factors</div>
                            <ul class="driver-list">
                                @foreach($positiveFactors as $factor)
                                    <li>{!! $factor !!}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="driver-card">
                        <div class="driver-card-inner driver-risk">
                            <div class="driver-title">Risk Factors</div>
                            <ul class="driver-list">
                                @foreach($riskFactors as $risk)
                                    <li>{!! $risk !!}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="driver-card">
                        <div class="driver-card-inner driver-outcome">
                            <div class="driver-title">Decision Outcome</div>
                            <p style="margin: 0; font-size: 11px; line-height: 1.6; color: {{ $outcomeColor }};">
                                {{ $outcomeText }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATA QUALITY -->
            <div class="section">
                <h2 class="section-title">Data Quality Status</h2>
                <div class="highlight-panel">
                    <div class="highlight-grid">
                        <div class="mini-card">
                            <div class="mini-card-inner">
                                <div class="mini-label">Quality Status</div>
                                <div class="mini-value">
                                    @if($data_quality['is_sufficient'])
                                        <span style="color: #047857;">Excellent</span>
                                    @elseif($data_quality['transaction_count'] >= 100)
                                        <span style="color: #d97706;">Adequate</span>
                                    @else
                                        <span style="color: #dc2626;">Limited</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-card-inner">
                                <div class="mini-label">Transactions</div>
                                <div class="mini-value">{{ number_format($data_quality['transaction_count']) }}</div>
                            </div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-card-inner">
                                <div class="mini-label">Credits / Debits</div>
                                <div class="mini-value" style="font-size: 13px;">
                                    {{ number_format($analytics ? $analytics->total_credit_count : 0) }}
                                    /
                                    {{ number_format($analytics ? $analytics->total_debit_count : 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-card-inner">
                                <div class="mini-label">Coverage</div>
                                <div class="mini-value">{{ $data_quality['months_count'] }} Months</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KEY FINANCIAL INDICATORS -->
            <div class="section">
                <h2 class="section-title">Key Financial Indicators</h2>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Avg Monthly Income</div>
                            <div class="metric-value" style="font-size: 13px;">TZS {{ number_format($assessment->net_monthly_income ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Disposable Income</div>
                            <div class="metric-value" style="font-size: 13px;">TZS {{ number_format($disposableIncome ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">DTI Ratio</div>
                            <div class="metric-value">{{ number_format($assessment->dti_ratio ?? 0, 1) }}%</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Income Volatility</div>
                            <div class="metric-value">{{ number_format($analytics ? $analytics->cash_flow_volatility_score : 0, 1) }}%</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Risk Grade</div>
                            <div class="metric-value">
                                @php
                                    $riskScore = $assessment->risk_score ?? 0;
                                    $riskGrade = 'C';
                                    if ($riskScore >= 80) $riskGrade = 'A';
                                    elseif ($riskScore >= 65) $riskGrade = 'B';
                                    elseif ($riskScore < 50) $riskGrade = 'D';
                                @endphp
                                {{ $riskGrade }}
                            </div>
                            <div class="metric-sub">Score: {{ $riskScore }}/100</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- APPLICANT + INCOME -->
            <div class="section">
                <div class="two-col">
                    <div class="col">
                        <div class="card">
                            <div class="card-title">Applicant Summary</div>
                            <table class="detail-table">
                                <tr>
                                    <td class="detail-label">Full Name</td>
                                    <td class="detail-value">{{ $prospect->first_name }} {{ $prospect->last_name }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Customer Type</td>
                                    <td class="detail-value">{{ $prospect->customer_type ? ucfirst($prospect->customer_type->value) : 'Individual' }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Phone / Email</td>
                                    <td class="detail-value">
                                        {{ $prospect->phone ?? 'N/A' }}<br>
                                        <span style="font-weight: normal; font-size: 10px;">{{ $prospect->email ?? '' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Bank Statement Source</td>
                                    <td class="detail-value">{{ $prospect->statementImport?->bank_name ?? 'Not Identified' }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Analysis Period</td>
                                    <td class="detail-value">
                                        {{ $analytics ? $narrativeService->formatTenure($analytics->analysis_months) : 'N/A' }}
                                    </td>
                                </tr>
                            </table>
                            <div class="narrative">
                                The system reviewed the applicant's transaction history to assess income reliability,
                                affordability, debt exposure, and financial behavior relevant to mortgage eligibility.
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-title">Income Analysis</div>
                            <table class="detail-table">
                                <tr>
                                    <td class="detail-label">Average Monthly Income</td>
                                    <td class="detail-value">TZS {{ number_format($assessment->net_monthly_income ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Consistency Score</td>
                                    <td class="detail-value">{{ $analytics ? $analytics->income_stability_score : 0 }}/100</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Income Volatility</td>
                                    <td class="detail-value">{{ number_format($analytics ? $analytics->cash_flow_volatility_score : 0, 1) }}%</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Primary Income Source</td>
                                    <td class="detail-value">{{ $analytics && $analytics->income_classification ? ucfirst(str_replace('_', ' ', $analytics->income_classification->value)) : 'Mixed Sources' }}</td>
                                </tr>
                            </table>
                            <div class="narrative">
                                {{ $narrativeService->explainIncomeVolatility($analytics ? $analytics->cash_flow_volatility_score : 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AFFORDABILITY + LOAN CAPACITY -->
            <div class="section">
                <div class="two-col">
                    <div class="col">
                        <div class="card">
                            <div class="card-title">Affordability Analysis</div>
                            <table class="detail-table">
                                @php
                                    $avgIncome = $assessment->net_monthly_income ?? 0;
                                    $estimatedExpenses = $avgIncome * 0.35;
                                    $existingDebt = $assessment->total_monthly_debt ?? 0;
                                    $disposableIncome = $avgIncome - $estimatedExpenses - $existingDebt;
                                @endphp
                                <tr>
                                    <td class="detail-label">Average Monthly Income</td>
                                    <td class="detail-value">TZS {{ number_format($avgIncome) }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Estimated Living Expenses</td>
                                    <td class="detail-value">TZS {{ number_format($estimatedExpenses) }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Existing Debt Obligations</td>
                                    <td class="detail-value">TZS {{ number_format($existingDebt) }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Disposable Income</td>
                                    <td class="detail-value">TZS {{ number_format($disposableIncome) }}</td>
                                </tr>
                            </table>
                            <div class="narrative">
                                {{ $narrativeService->explainAffordability($avgIncome, $estimatedExpenses, $disposableIncome) }}
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-title">Loan Capacity & Debt Ratios</div>
                            <table class="detail-table">
                                @php
                                    $maxInstallment = $assessment->max_installment_from_income ?? 0;
                                    $hasCalculationError = $maxInstallment <= 0 && ($assessment->final_max_loan ?? 0) > 0;
                                @endphp
                                <tr>
                                    <td class="detail-label">Debt-to-Income Ratio (DTI)</td>
                                    <td class="detail-value">{{ number_format($assessment->dti_ratio ?? 0, 1) }}%</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Debt Service Ratio (DSR)</td>
                                    <td class="detail-value">{{ number_format($assessment->dsr_ratio ?? 0, 1) }}%</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Recommended Installment</td>
                                    <td class="detail-value" style="{{ $hasCalculationError ? 'color: #dc2626;' : '' }}">
                                        @if($hasCalculationError)
                                            NOT AVAILABLE
                                        @else
                                            TZS {{ number_format($maxInstallment) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Maximum Loan Amount</td>
                                    <td class="detail-value">
                                        TZS {{ number_format($assessment->final_max_loan ?? 0) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Loan Tenure</td>
                                    <td class="detail-value">{{ $narrativeService->formatTenure($assessment->loan_tenure_months ?? 240) }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Interest Rate</td>
                                    <td class="detail-value">{{ number_format($assessment->assumed_interest_rate ?? 18, 2) }}% p.a.</td>
                                </tr>
                            </table>
                            <div class="narrative">
                                {{ $narrativeService->explainLoanCapacity($maxInstallment, $assessment->final_max_loan ?? 0, $assessment->loan_tenure_months ?? 240, $assessment->assumed_interest_rate ?? 18) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INCOME SOURCE COMPOSITION -->
            @if($analytics && ($analytics->salary_income > 0 || $analytics->business_income > 0 || $analytics->bulk_deposits > 0))
            <div class="section">
                <h2 class="section-title">Income Source Composition</h2>
                <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                    @if($analytics->salary_income > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner" style="border-color: #10b981;">
                            <div class="metric-label">Salary Income</div>
                            <div class="metric-value" style="font-size: 11px; color: #059669;">TZS {{ number_format($analytics->salary_income ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($analytics->business_income > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner" style="border-color: #06b6d4;">
                            <div class="metric-label">Business Income</div>
                            <div class="metric-value" style="font-size: 11px; color: #0891b2;">TZS {{ number_format($analytics->business_income ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($analytics->transfer_inflows > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Transfer Inflows</div>
                            <div class="metric-value" style="font-size: 11px;">TZS {{ number_format($analytics->transfer_inflows ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($analytics->loan_inflows > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner" style="border-color: #f59e0b;">
                            <div class="metric-label">Loan Inflows</div>
                            <div class="metric-value" style="font-size: 11px; color: #f59e0b;">TZS {{ number_format($analytics->loan_inflows ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($analytics->bulk_deposits > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner" style="border-color: #8b5cf6;">
                            <div class="metric-label">Bulk Deposits</div>
                            <div class="metric-value" style="font-size: 11px; color: #8b5cf6;">TZS {{ number_format($analytics->bulk_deposits ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($analytics->other_income > 0)
                    <div class="metric-card">
                        <div class="metric-card-inner">
                            <div class="metric-label">Other Income</div>
                            <div class="metric-value" style="font-size: 11px;">TZS {{ number_format($analytics->other_income ?? 0) }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($analytics->suspicious_deposits_flagged && $analytics->bulk_deposit_details && count($analytics->bulk_deposit_details) > 0)
                <div class="card" style="border: 2px solid #dc2626; background: #fef2f2; margin-top: 8px;">
                    <div class="card-title" style="color: #991b1b; display: flex; align-items: center;">
                        <span style="margin-right: 4px; font-size: 11px;">⚠️</span>
                        Suspicious Deposits Detected
                        <span style="margin-left: 6px; background: #dc2626; color: white; padding: 1px 4px; border-radius: 3px; font-size: 8px;">
                            {{ $analytics->bulk_deposit_count ?? 0 }} Found
                        </span>
                    </div>
                    <div style="font-size: 8px; color: #7f1d1d; margin-bottom: 4px; line-height: 1.3;">
                        Large unexplained deposits require manual verification. Review transaction details below:
                    </div>
                    <table class="detail-table" style="margin-top: 4px;">
                        <thead>
                            <tr style="background: #fee2e2; border-bottom: 2px solid #dc2626;">
                                <td style="padding: 3px 4px; font-weight: 600; color: #991b1b; font-size: 8px;">Date</td>
                                <td style="padding: 3px 4px; text-align: right; font-weight: 600; color: #991b1b; font-size: 8px;">Amount</td>
                                <td style="padding: 3px 4px; font-weight: 600; color: #991b1b; font-size: 8px;">Description</td>
                                <td style="padding: 3px 4px; font-weight: 600; color: #991b1b; font-size: 8px;">Source</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analytics->bulk_deposit_details as $deposit)
                                @if($deposit['suspicious'] ?? false)
                                <tr>
                                    <td style="padding: 3px 4px; font-size: 8px; color: #7f1d1d;">{{ $deposit['date'] ?? 'N/A' }}</td>
                                    <td style="padding: 3px 4px; text-align: right; font-size: 8px; color: #7f1d1d; font-weight: 600;">
                                        {{ number_format($deposit['amount'] ?? 0, 0) }}
                                    </td>
                                    <td style="padding: 3px 4px; font-size: 7.5px; color: #7f1d1d;">
                                        {{ Str::limit($deposit['description'] ?? 'No description', 35) }}
                                    </td>
                                    <td style="padding: 3px 4px; font-size: 8px; color: #7f1d1d;">
                                        <span style="background: #fecaca; padding: 1px 4px; border-radius: 2px; font-size: 7px;">
                                            {{ ucfirst($deposit['source'] ?? 'Unknown') }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    <div class="narrative" style="margin-top: 4px; color: #7f1d1d; font-style: italic;">
                        Note: Verify these transactions with supporting documentation before final approval decision.
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- BEHAVIOR + RISK -->
            <div class="section">
                <div class="two-col">
                    <div class="col">
                        <div class="card">
                            <div class="card-title">Behavioral Financial Analysis</div>
                            <table class="detail-table">
                                <tr>
                                    <td class="detail-label">Average Account Balance</td>
                                    <td class="detail-value">
                                        TZS {{ number_format($analytics ? (($analytics->opening_balance + $analytics->closing_balance) / 2) : 0) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Negative Balance Days</td>
                                    <td class="detail-value">{{ $analytics ? $analytics->negative_balance_days : 0 }} days</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Payment Bounces</td>
                                    <td class="detail-value">{{ $analytics ? $analytics->bounce_count : 0 }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Gambling Transactions</td>
                                    <td class="detail-value">{{ $analytics ? $analytics->gambling_transaction_count : 0 }}</td>
                                </tr>
                            </table>
                            <div class="narrative">
                                @if($analytics)
                                    {{ $narrativeService->explainBehavioralPatterns($analytics) }}
                                @else
                                    The account reflects generally stable day-to-day balance management with no frequent negative balance events detected during the analysis period.
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="risk-box">
                            <div class="card-title" style="margin-bottom: 6px; color: #991b1b;">Risk Assessment</div>
                            <div class="risk-grade">Grade {{ $riskGrade }}</div>
                            <div class="risk-score">Risk Score: {{ $riskScore }}/100</div>
                            <ul class="driver-list" style="margin-top: 0;">
                                @if($analytics && $analytics->cash_flow_volatility_score > 30)
                                    <li>High income fluctuation increases repayment uncertainty.</li>
                                @endif
                                @if($analytics && $analytics->income_stability_score < 50)
                                    <li>Low income consistency suggests irregular monthly inflows.</li>
                                @endif
                                @if($assessment->dti_ratio > 40)
                                    <li>Elevated debt-to-income ratio reduces repayment buffer.</li>
                                @endif
                                @if($analytics && $analytics->negative_balance_days > 5)
                                    <li>Multiple negative balance events detected during analysis.</li>
                                @endif
                                @if(count($riskFactors) <= 1)
                                    <li>Financial profile demonstrates acceptable risk levels for mortgage lending.</li>
                                @endif
                            </ul>
                            <div class="narrative" style="color: #7f1d1d;">
                                These risks do not necessarily disqualify the application, but they justify additional checks,
                                documentation, or compensating factors before final approval.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LTV -->
            @if($prospect->property_value && $prospect->requested_amount)
                <div class="section">
                    <h2 class="section-title">Loan-to-Value Analysis</h2>
                    <div class="highlight-panel">
                        @php
                            $ltvRatio = ($prospect->requested_amount / $prospect->property_value) * 100;
                            $downPayment = $prospect->property_value - $prospect->requested_amount;
                        @endphp
                        <div class="highlight-grid">
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">Property Value</div>
                                    <div class="mini-value" style="font-size: 13px;">TZS {{ number_format($prospect->property_value) }}</div>
                                </div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">Loan Requested</div>
                                    <div class="mini-value" style="font-size: 13px;">TZS {{ number_format($prospect->requested_amount) }}</div>
                                </div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">Down Payment</div>
                                    <div class="mini-value" style="font-size: 13px;">TZS {{ number_format($downPayment) }}</div>
                                </div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">LTV Ratio</div>
                                    <div class="mini-value">{{ number_format($ltvRatio, 1) }}%</div>
                                </div>
                            </div>
                        </div>
                        <div class="narrative" style="margin-top: 6px;">
                            {{ $narrativeService->explainLTV($ltvRatio, $prospect->requested_amount, $prospect->property_value) }}
                        </div>
                    </div>
                </div>
            @endif

            <!-- CONDITIONS -->
            @php
                $conditions = is_array($assessment->conditions) 
                    ? $assessment->conditions 
                    : (is_string($assessment->conditions) ? json_decode($assessment->conditions, true) : []);
                $conditions = is_array($conditions) ? $conditions : [];
            @endphp

            @if(!empty($conditions) && count($conditions) > 0)
                <div class="section">
                    <h2 class="section-title">Conditions for Approval</h2>
                    <div class="conditions-box">
                        <div style="font-weight: bold; margin-bottom: 6px;">The following conditions should be satisfied before full mortgage approval:</div>
                        <ol>
                            @foreach($conditions as $index => $condition)
                                @php
                                    // Extract condition text from array or string
                                    $rawCondition = is_array($condition) 
                                        ? ($condition['label'] ?? $condition['condition'] ?? $condition['text'] ?? 'Unspecified condition') 
                                        : $condition;
                                    
                                    // Humanize condition label
                                    $conditionText = $narrativeService->humanizeCondition($rawCondition);
                                @endphp
                                <li>{{ $conditionText }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            <!-- FINAL RECOMMENDATION -->
            <div class="section">
                <div class="recommendation-box">
                    <div class="recommendation-head">Final System Recommendation</div>
                    <div class="recommendation-body">
                        @php
                            $maxInstallment = $assessment->max_installment_from_income ?? 0;
                            $hasCalculationError = $maxInstallment <= 0 && ($assessment->final_max_loan ?? 0) > 0;
                        @endphp
                        <div class="recommendation-status">
                            Result: {{ $decisionText }}
                        </div>

                        <div class="recommendation-grid">
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">Recommended Loan Amount</div>
                                    <div class="mini-value" style="font-size: 13px;">
                                        TZS {{ number_format($assessment->final_max_loan ?? 0) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-inner">
                                    <div class="mini-label">Estimated Monthly Installment</div>
                                    <div class="mini-value" style="font-size: 13px; {{ $hasCalculationError ? 'color: #dc2626;' : '' }}">
                                        @if($hasCalculationError)
                                            NOT AVAILABLE
                                        @else
                                            TZS {{ number_format($maxInstallment) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="narrative">
                            {{ $narrativeService->explainFinalRecommendation(
                                $assessment->system_decision,
                                $assessment->final_max_loan ?? 0,
                                $maxInstallment,
                                $riskGrade,
                                $conditions
                            ) }}
                        </div>

                        <div class="disclaimer">
                            <strong>Important Disclaimer:</strong>
                            This pre-qualification result is indicative and based solely on automated analysis of bank transaction data.
                            It does not constitute a binding loan offer or guarantee of mortgage approval. Final approval remains subject to
                            income verification, property valuation, legal review, KYC checks, credit policy compliance, and full underwriting assessment.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            Report generated by {{ config('app.name', 'Mortgage Intelligence Platform') }}
            | {{ now()->format('d M Y, H:i:s') }}
            <br>
            This is a system-generated document and does not require a signature.
        </div>
    </div>
</div>
</body>
</html>
