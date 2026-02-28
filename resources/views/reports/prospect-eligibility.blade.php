<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pre-Qualification Report - {{ $prospect->first_name }} {{ $prospect->last_name }}</title>

    <style>
        /* Print setup (A4) */
        @page {
            size: A4;
            margin: 12mm;
        }

        :root {
            --ink: #0f172a;          /* slate-900 */
            --muted: #64748b;        /* slate-500 */
            --line: #e2e8f0;         /* slate-200 */
            --card: #f8fafc;         /* slate-50 */
            --brand: #0a3d62;        /* deep blue */
            --ok: #16a34a;           /* green */
            --warn: #f59e0b;         /* amber */
            --bad: #ef4444;          /* red */
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff;
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, 'Noto Sans', 'Helvetica Neue', sans-serif;
            font-size: 12px;
            line-height: 1.35;
        }

        .page {
            width: 100%;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            vertical-align: top;
            width: 68%;
        }

        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 32%;
            text-align: right;
        }

        .title {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.2px;
            font-weight: 800;
        }

        .meta {
            margin-top: 2px;
            color: var(--muted);
            font-size: 11px;
            white-space: nowrap;
        }

        .meta div {
            margin-bottom: 2px;
        }

        .meta-institution {
            margin-top: 4px;
        }

        .top-kpis {
            margin-top: 8px;
        }

        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 11px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            margin-right: 8px;
            vertical-align: middle;
        }

        .pill-approved {
            background: #f0fdf4;
            color: #166534;
        }

        .pill-conditional {
            background: #fff7ed;
            color: #9a3412;
        }

        .pill-rejected {
            background: #fef2f2;
            color: #991b1b;
        }

        .kpi {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--line);
            vertical-align: middle;
            min-width: 200px;
        }

        .kpi .label {
            color: var(--muted);
            font-size: 11px;
            display: block;
        }

        .kpi .value {
            font-size: 14px;
            font-weight: 800;
            color: var(--brand);
            display: block;
            margin-top: 2px;
        }
        
        /* Breadcrumb */
        .breadcrumb-path {
            padding: 8px 0;
            margin-bottom: 10px;
            font-size: 11px;
            color: var(--muted);
            border-bottom: 1px solid var(--line);
        }
        
        .breadcrumb-path span {
            display: inline;
        }
        
        .breadcrumb-path .separator {
            margin: 0 6px;
            opacity: 0.5;
        }
        
        .breadcrumb-path .current {
            font-weight: 600;
            color: var(--ink);
        }

        /* Grid */
        .grid-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .grid-col {
            display: table-cell;
            width: 49%;
            vertical-align: top;
            padding-right: 1%;
        }

        .grid-col:last-child {
            padding-right: 0;
            padding-left: 1%;
        }

        /* Sections */
        .section {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 10px;
            background: #fff;
        }

        .section-title {
            margin: 0 0 6px 0;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table td {
            padding: 4px 0;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table td:first-child {
            width: 42%;
            color: var(--muted);
            padding-right: 8px;
        }

        .table td:last-child {
            font-weight: 600;
            word-break: break-word;
        }

        /* Risk section */
        .risk-row {
            margin-bottom: 8px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 11px;
            border: 1px solid var(--line);
            background: #fff;
        }

        .badge-a {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .badge-b {
            background: #d9f99d;
            border-color: #bef264;
            color: #365314;
        }

        .badge-c {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #9a3412;
        }

        .badge-d {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .score-container {
            margin-top: 6px;
        }

        .score-top {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .score-label {
            display: table-cell;
            font-size: 11px;
            color: var(--muted);
        }

        .score-value {
            display: table-cell;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
        }

        .bar {
            position: relative;
            height: 8px;
            border-radius: 999px;
            background: #eef2ff;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .bar-fill {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            height: 100%;
        }

        .bar-fill-low {
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .bar-fill-medium {
            background: linear-gradient(90deg, #fbbf24, #fb923c);
        }

        .bar-fill-high {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        /* Conditions */
        .conditions {
            margin: 6px 0 0 0;
            padding-left: 16px;
        }

        .conditions li {
            margin: 2px 0;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 10.5px;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        /* Print adjustments */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            a {
                color: inherit;
                text-decoration: none;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb-path">
            <span>Prospects</span>
            <span class="separator">›</span>
            <span>{{ $prospect->first_name }} {{ $prospect->last_name }}</span>
            <span class="separator">›</span>
            <span class="current">Pre-Qualification Report</span>
        </div>
        
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <h1 class="title">PRE-QUALIFICATION REPORT</h1>
                <div class="top-kpis">
                    @php
                        $statusClass = 'pill-conditional';
                        $statusText = 'STATUS: CONDITIONALLY PRE-QUALIFIED';
                        
                        if ($assessment->system_decision === 'approved') {
                            $statusClass = 'pill-approved';
                            $statusText = 'STATUS: PRE-QUALIFIED';
                        } elseif ($assessment->system_decision === 'rejected') {
                            $statusClass = 'pill-rejected';
                            $statusText = 'STATUS: NOT PRE-QUALIFIED';
                        }
                        
                        $eligibleAmount = $assessment->final_max_loan 
                            ? number_format($assessment->final_max_loan, 0) 
                            : 'N/A';
                    @endphp
                    
                    <span class="pill {{ $statusClass }}">{{ $statusText }}</span>
                    
                    <div class="kpi">
                        <div class="label">Eligible Loan Limit</div>
                        <div class="value">TZS {{ $eligibleAmount }}</div>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <div class="meta">
                    <div><b>Report ID:</b> #{{ str_pad($prospect->id, 3, '0', STR_PAD_LEFT) }}</div>
                    <div><b>Generated:</b> {{ now()->format('d M Y') }}</div>
                    @if($institution)
                        <div class="meta-institution">{{ $institution->name }}</div>
                    @else
                        <div class="meta-institution">White-Label Platform Provider</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- GRID SECTIONS -->
        <div class="grid-row">
            <!-- Applicant Profile -->
            <div class="grid-col">
                <section class="section">
                    <h2 class="section-title">Applicant Profile</h2>
                    <table class="table">
                        <tr>
                            <td>Full Name</td>
                            <td>{{ $prospect->first_name }} {{ $prospect->last_name }}</td>
                        </tr>
                        <tr>
                            <td>Customer Type</td>
                            <td>{{ $prospect->customer_type ? $prospect->customer_type->label() : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>ID Number</td>
                            <td>{{ $prospect->id_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Phone</td>
                            <td>{{ $prospect->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>{{ $prospect->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Date Applied</td>
                            <td>{{ $prospect->created_at->format('d M Y') }}</td>
                        </tr>
                    </table>
                </section>
            </div>

            <!-- Loan Request Overview -->
            <div class="grid-col">
                <section class="section">
                    <h2 class="section-title">Loan Request Overview</h2>
                    <table class="table">
                        @php
                            // Use prospect data, fallback to assessment if needed
                            $requestedAmountProspect = $prospect->requested_amount > 0 
                                ? $prospect->requested_amount 
                                : ($assessment->requested_amount ?? 0);
                            $tenureMonthsProspect = $prospect->requested_tenure > 0 
                                ? $prospect->requested_tenure 
                                : ($assessment->requested_tenure_months ?? 0);
                            $propertyValueProspect = $prospect->property_value > 0 
                                ? $prospect->property_value 
                                : ($assessment->property_value ?? 0);
                        @endphp
                        <tr>
                            <td>Requested Amount</td>
                            <td>TZS {{ number_format($requestedAmountProspect, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Tenure</td>
                            <td>{{ $tenureMonthsProspect }} months</td>
                        </tr>
                        <tr>
                            <td>Property Value</td>
                            <td>TZS {{ number_format($propertyValueProspect, 0) }}</td>
                        </tr>
                        @php
                            $ltv = $propertyValueProspect > 0 
                                ? ($requestedAmountProspect / $propertyValueProspect) * 100 
                                : 0;
                        @endphp
                        <tr>
                            <td>LTV Ratio</td>
                            <td>{{ number_format($ltv, 1) }}%</td>
                        </tr>
                        <tr>
                            <td>Loan Product</td>
                            <td>{{ $product->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Property Location</td>
                            <td>{{ $prospect->property_location ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </section>
            </div>
        </div>

        <div class="grid-row">
            <!-- Financial Assessment -->
            <div class="grid-col">
                <section class="section">
                    <h2 class="section-title">Financial Assessment</h2>
                    <table class="table">
                        @php
                            $analytics = $prospect->statementImport->analytics ?? null;
                            // Use assessment data as primary source, analytics as fallback
                            $income = $assessment->net_monthly_income ?? ($analytics->avg_monthly_income ?? 0);
                            $debt = $assessment->total_monthly_debt ?? ($analytics->avg_monthly_debt_obligations ?? 0);
                            $dti = $assessment->dti_ratio ?? 0;
                            $dsr = $assessment->dsr_ratio ?? 0;
                        @endphp
                        <tr>
                            <td>Avg. Monthly Income</td>
                            <td>TZS {{ number_format($income, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Avg. Monthly Debt</td>
                            <td>TZS {{ number_format($debt, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Debt-to-Income Ratio</td>
                            <td>{{ number_format($dti, 1) }}%</td>
                        </tr>
                        <tr>
                            <td>Debt Service Ratio</td>
                            <td>{{ number_format($dsr, 1) }}%</td>
                        </tr>
                        @if($analytics && $analytics->cash_flow_volatility)
                        <tr>
                            <td>Cash Flow Volatility</td>
                            <td>{{ number_format($analytics->cash_flow_volatility, 1) }}%</td>
                        </tr>
                        @endif
                        @if($analytics && $analytics->avg_monthly_balance)
                        <tr>
                            <td>Avg. Monthly Balance</td>
                            <td>TZS {{ number_format($analytics->avg_monthly_balance, 0) }}</td>
                        </tr>
                        @endif
                    </table>
                </section>
            </div>

            <!-- Risk & Conditions -->
            <div class="grid-col">
                <section class="section">
                    <h2 class="section-title">Risk & Conditions</h2>
                    
                    @php
                        $riskGrade = strtoupper($assessment->risk_grade ?? 'N');
                        $riskScore = $assessment->risk_score ?? 0;
                        $riskClass = 'badge-c';
                        
                        if ($riskGrade === 'A') $riskClass = 'badge-a';
                        elseif ($riskGrade === 'B') $riskClass = 'badge-b';
                        elseif ($riskGrade === 'C') $riskClass = 'badge-c';
                        elseif ($riskGrade === 'D') $riskClass = 'badge-d';
                        
                        $barFillClass = 'bar-fill-medium';
                        if ($riskScore >= 70) $barFillClass = 'bar-fill-low';
                        elseif ($riskScore < 40) $barFillClass = 'bar-fill-high';
                    @endphp

                    <div class="risk-row">
                        <span class="badge {{ $riskClass }}">RISK GRADE: {{ $riskGrade }}</span>
                    </div>

                    @if($riskScore > 0)
                    <div class="score-container">
                        <div class="score-top">
                            <span class="score-label">Risk Score</span>
                            <span class="score-value"><b>{{ number_format($riskScore, 0) }}</b>/100</span>
                        </div>
                        <div class="bar" aria-label="Risk score bar">
                            <span class="bar-fill {{ $barFillClass }}" style="width: {{ $riskScore }}%;"></span>
                        </div>
                    </div>
                    @endif

                    @if($assessment->system_decision === 'conditionally_approved' && !empty($assessment->conditions))
                        @php
                            $conditions = is_array($assessment->conditions) 
                                ? $assessment->conditions 
                                : json_decode($assessment->conditions, true);
                        @endphp
                        @if(is_array($conditions) && count($conditions) > 0)
                        <ul class="conditions">
                            @foreach($conditions as $condition)
                                @php
                                    $conditionText = is_array($condition) ? ($condition['description'] ?? $condition['condition'] ?? implode(': ', $condition)) : $condition;
                                @endphp
                                <li>{{ $conditionText }}</li>
                            @endforeach
                        </ul>
                        @endif
                    @endif
                </section>
            </div>
        </div>

        <!-- Policy Breaches -->
        @php
            $policyBreaches = is_array($assessment->policy_breaches) 
                ? $assessment->policy_breaches 
                : ($assessment->policy_breaches ? json_decode($assessment->policy_breaches, true) : []);
        @endphp
        @if($policyBreaches && count($policyBreaches) > 0)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Policy Breaches</h2>
                <div style="background: #fef2f2; padding: 10px; border-left: 3px solid #ef4444;">
                    <div style="font-weight: 700; color: #991b1b; margin-bottom: 6px; font-size: 11px;">⚠️ Policy Requirements Not Met:</div>
                    <ul style="margin-left: 16px; color: #7f1d1d; font-size: 11px;">
                        @foreach($policyBreaches as $breach)
                        @php
                            $breachText = is_array($breach) ? ($breach['description'] ?? $breach['breach'] ?? implode(': ', $breach)) : $breach;
                        @endphp
                        <li style="margin-bottom: 3px;">{{ $breachText }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>
        @endif

        <!-- Risk Factors Breakdown -->
        @if($assessment->risk_factors)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Risk Factors Analysis</h2>
                <table class="table">
                    <thead style="background: #f1f5f9;">
                        <tr>
                            <th style="padding: 6px 10px; text-align: left; font-size: 10px;">Risk Factor</th>
                            <th style="padding: 6px 10px; text-align: center; font-size: 10px;">Score</th>
                            <th style="padding: 6px 10px; text-align: center; font-size: 10px;">Weight</th>
                            <th style="padding: 6px 10px; text-align: center; font-size: 10px;">Impact</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $riskFactors = is_array($assessment->risk_factors) 
                                ? $assessment->risk_factors 
                                : ($assessment->risk_factors ? json_decode($assessment->risk_factors, true) : []);
                        @endphp
                        @foreach($riskFactors ?? [] as $riskItem)
                        <tr>
                            @php
                                if (is_array($riskItem)) {
                                    $factorName = $riskItem['factor'] ?? 'Unknown';
                                    $factorValue = $riskItem['value'] ?? 0;
                                    $factorWeight = $riskItem['weight'] ?? 0;
                                } else {
                                    $factorName = 'Unknown';
                                    $factorValue = 0;
                                    $factorWeight = 0;
                                }
                                $displayName = ucwords(str_replace('_', ' ', $factorName));
                                $displayValue = is_numeric($factorValue) ? number_format($factorValue, 1) : $factorValue;
                                
                                $impactClass = 'badge-approved';
                                $impactText = 'Low';
                                if(is_numeric($factorValue) && $factorValue >= 70) {
                                    $impactClass = 'badge-rejected';
                                    $impactText = 'High';
                                } elseif(is_numeric($factorValue) && $factorValue >= 40) {
                                    $impactClass = 'badge-conditional';
                                    $impactText = 'Medium';
                                }
                            @endphp
                            <td style="padding: 6px 10px;">{{ $displayName }}</td>
                            <td style="padding: 6px 10px; text-align: center;">{{ $displayValue }}</td>
                            <td style="padding: 6px 10px; text-align: center;">{{ $factorWeight }}%</td>
                            <td style="padding: 6px 10px; text-align: center;">
                                <span class="pill {{ $impactClass }}" style="font-size: 9px; padding: 2px 8px;">{{ $impactText }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
        @endif

        <!-- Risk Assessment Explanation -->
        @if(isset($assessment->risk_explanation) && $assessment->risk_explanation)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Risk Assessment Explanation</h2>
                @php
                    $riskExplanation = is_array($assessment->risk_explanation) 
                        ? $assessment->risk_explanation 
                        : json_decode($assessment->risk_explanation, true);
                @endphp
                
                @if(isset($riskExplanation['primary_risk_drivers']) && count($riskExplanation['primary_risk_drivers']) > 0)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: 600; margin-bottom: 8px; color: #1e293b;">Primary Risk Drivers:</div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 11px;">
                        @foreach($riskExplanation['primary_risk_drivers'] as $driver)
                        <li style="margin-bottom: 5px;">
                            {{ is_array($driver) ? $driver['factor'] : $driver }}
                            @if(is_array($driver) && isset($driver['points']))
                            <span style="background: #6c757d; color: white; padding: 2px 5px; border-radius: 3px; font-size: 9px; margin-left: 5px;">{{ $driver['points'] }} pts</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(isset($riskExplanation['risk_grade_reasoning']))
                <div style="margin-bottom: 15px; padding: 10px; background: #f8fafc; border-left: 3px solid #3b82f6;">
                    <div style="font-weight: 600; margin-bottom: 5px; color: #1e293b;">Risk Grade Reasoning:</div>
                    <p style="margin: 0; font-size: 11px;">{{ $riskExplanation['risk_grade_reasoning'] }}</p>
                </div>
                @endif

                @if(isset($riskExplanation['loan_limit_determination']))
                <div style="padding: 10px; background: #f8fafc; border-left: 3px solid #10b981;">
                    <div style="font-weight: 600; margin-bottom: 5px; color: #1e293b;">Loan Limit Determination:</div>
                    <p style="margin: 0; font-size: 11px;">{{ $riskExplanation['loan_limit_determination'] }}</p>
                </div>
                @endif
            </section>
        </div>
        @endif

        <!-- Transaction Anomaly Detection -->
        @if(isset($analytics->pass_through_count) && $analytics->pass_through_count > 0)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Transaction Anomaly Detection</h2>
                <div style="padding: 12px; background: {{ $analytics->pass_through_risk_flag ? '#fee2e2' : '#fef3c7' }}; border-left: 4px solid {{ $analytics->pass_through_risk_flag ? '#dc2626' : '#f59e0b' }}; margin-bottom: 10px;">
                    <div style="font-weight: 600; margin-bottom: 8px; color: {{ $analytics->pass_through_risk_flag ? '#991b1b' : '#92400e' }};">
                        @if($analytics->pass_through_risk_flag)
                        ⚠️ High Pass-Through Activity Detected
                        @else
                        ℹ️ Pass-Through Activity Detected
                        @endif
                    </div>
                    <table class="table" style="font-size: 10px; margin-bottom: 10px;">
                        <tr>
                            <td style="font-weight: 600; width: 50%;">Pass-Through Transactions:</td>
                            <td>{{ $analytics->pass_through_count }} instances</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Total Amount:</td>
                            <td>TZS {{ number_format($analytics->pass_through_total_amount ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Pass-Through Ratio:</td>
                            <td>{{ number_format($analytics->pass_through_ratio ?? 0, 1) }}% of total credits</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Risk Status:</td>
                            <td>
                                <span class="pill {{ $analytics->pass_through_risk_flag ? 'badge-rejected' : 'badge-conditional' }}" style="font-size: 9px;">
                                    {{ $analytics->pass_through_risk_flag ? 'High Risk' : 'Monitored' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <p style="margin: 0; font-size: 9px; color: #64748b;">
                        <strong>Note:</strong> Pass-through transactions indicate "money in → money out" patterns within short time windows, 
                        which may suggest suspicious cash-out behavior or money flow irregularities.
                    </p>
                </div>
            </section>
        </div>
        @endif

        <!-- Income & Affordability Breakdown -->
        @if($assessment->gross_monthly_income || $assessment->max_loan_from_affordability)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Income & Affordability Analysis</h2>
                <table class="table">
                    @if($assessment->gross_monthly_income)
                    <tr>
                        <td style="font-weight: 600;">Gross Monthly Income</td>
                        <td>TZS {{ number_format($assessment->gross_monthly_income, 0) }}</td>
                    </tr>
                    @endif
                    @if($assessment->net_monthly_income)
                    <tr>
                        <td style="font-weight: 600;">Net Monthly Income</td>
                        <td>TZS {{ number_format($assessment->net_monthly_income, 0) }}</td>
                    </tr>
                    @endif
                    @if($assessment->total_monthly_debt)
                    <tr>
                        <td style="font-weight: 600;">Total Monthly Debt</td>
                        <td>TZS {{ number_format($assessment->total_monthly_debt, 0) }} ({{ $assessment->detected_debt_count ?? 0 }} obligations)</td>
                    </tr>
                    @endif
                    @if($assessment->net_disposable_income)
                    <tr>
                        <td style="font-weight: 600;">Disposable Income</td>
                        <td>TZS {{ number_format($assessment->net_disposable_income, 0) }}</td>
                    </tr>
                    @endif
                    @if($assessment->max_installment_from_income)
                    <tr style="background: #f0f9ff;">
                        <td style="font-weight: 700;">Max Affordable Installment</td>
                        <td style="font-weight: 700;">TZS {{ number_format($assessment->max_installment_from_income, 0) }}</td>
                    </tr>
                    @endif
                    @if($assessment->max_loan_from_affordability)
                    <tr style="background: #f0f9ff;">
                        <td style="font-weight: 700;">Max Affordable Loan Amount</td>
                        <td style="font-weight: 700;">TZS {{ number_format($assessment->max_loan_from_affordability, 0) }}</td>
                    </tr>
                    @endif
                    @if($assessment->max_loan_from_ltv)
                    <tr>
                        <td style="font-weight: 600;">Max Loan from LTV</td>
                        <td>TZS {{ number_format($assessment->max_loan_from_ltv, 0) }} ({{ number_format($assessment->ltv_ratio, 1) }}% LTV)</td>
                    </tr>
                    @endif
                    @if($assessment->final_max_loan)
                    <tr style="background: #dbeafe;">
                        <td style="font-weight: 700; color: #1e40af;">Final Maximum Loan</td>
                        <td style="font-weight: 700; color: #1e40af;">TZS {{ number_format($assessment->final_max_loan, 0) }}</td>
                    </tr>
                    @endif
                </table>
            </section>
        </div>
        @endif

        <!-- Financial Details (if available) -->
        @php
            $analytics = $prospect->statementImport->analytics ?? null;
        @endphp
        @if($analytics && ($analytics->total_deposits > 0 || $analytics->total_withdrawals > 0))
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Bank Statement Analysis</h2>
                <table class="table">
                    @if($analytics->statement_start_date && $analytics->statement_end_date)
                    <tr>
                        <td>Statement Period</td>
                        <td colspan="3">{{ $analytics->statement_start_date->format('d M Y') }} - {{ $analytics->statement_end_date->format('d M Y') }} ({{ $analytics->statement_months ?? 'N/A' }} months)</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Total Deposits</td>
                        <td>TZS {{ number_format($analytics->total_deposits, 0) }}</td>
                        <td>Total Withdrawals</td>
                        <td>TZS {{ number_format($analytics->total_withdrawals, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Deposit Count</td>
                        <td>{{ number_format($analytics->deposit_count, 0) }}</td>
                        <td>Withdrawal Count</td>
                        <td>{{ number_format($analytics->withdrawal_count, 0) }}</td>
                    </tr>
                    @if($analytics->average_monthly_income)
                    <tr>
                        <td>Avg Monthly Income</td>
                        <td>TZS {{ number_format($analytics->average_monthly_income, 0) }}</td>
                        <td>Avg Monthly Expenses</td>
                        <td>TZS {{ number_format($analytics->average_monthly_expense ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->opening_balance)
                    <tr>
                        <td>Opening Balance</td>
                        <td>TZS {{ number_format($analytics->opening_balance, 0) }}</td>
                        <td>Closing Balance</td>
                        <td>TZS {{ number_format($analytics->closing_balance ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->average_balance)
                    <tr>
                        <td>Average Balance</td>
                        <td>TZS {{ number_format($analytics->average_balance, 0) }}</td>
                        <td>Minimum Balance</td>
                        <td>TZS {{ number_format($analytics->minimum_balance ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->income_volatility_percentage)
                    <tr>
                        <td>Income Volatility</td>
                        <td>{{ number_format($analytics->income_volatility_percentage, 1) }}%</td>
                        <td>Cash Flow Volatility</td>
                        <td>{{ number_format($analytics->cash_flow_volatility ?? 0, 1) }}%</td>
                    </tr>
                    @endif
                    @if($analytics->bounce_count || $analytics->account_bounce_count)
                    <tr>
                        <td>Bounce Count</td>
                        <td>{{ $analytics->bounce_count ?? $analytics->account_bounce_count ?? 0 }}</td>
                        <td>NSF Count</td>
                        <td>{{ $analytics->nsf_count ?? 0 }}</td>
                    </tr>
                    @endif
                    @if($analytics->gambling_transaction_count && $analytics->gambling_transaction_count > 0)
                    <tr style="background: #fef2f2;">
                        <td style="color: #991b1b; font-weight: 600;">Gambling Transactions</td>
                        <td style="color: #991b1b;">{{ $analytics->gambling_transaction_count }}</td>
                        <td style="color: #991b1b; font-weight: 600;">Gambling Amount</td>
                        <td style="color: #991b1b;">TZS {{ number_format($analytics->gambling_amount ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->loan_repayment_count)
                    <tr>
                        <td>Loan Repayments Detected</td>
                        <td>{{ $analytics->loan_repayment_count }}</td>
                        <td>Total Loan Payments</td>
                        <td>TZS {{ number_format($analytics->total_loan_repayments ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->salary_income_count)
                    <tr>
                        <td>Salary Payments</td>
                        <td>{{ $analytics->salary_income_count }}</td>
                        <td>Business Income Entries</td>
                        <td>{{ $analytics->business_income_count ?? 0 }}</td>
                    </tr>
                    @endif
                </table>
            </section>
        </div>
        @endif

        <!-- Transaction Summary (NEW) -->
        @if($analytics)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Transaction Summary</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th style="text-align: center;">Count</th>
                            <th style="text-align: right;">Total Amount</th>
                            <th style="text-align: right;">Average Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Credits (Inflows)</strong></td>
                            <td style="text-align: center;">{{ number_format($analytics->total_credit_count ?? 0) }}</td>
                            <td style="text-align: right; color: #059669;">TZS {{ number_format($analytics->total_credits ?? 0, 0) }}</td>
                            <td style="text-align: right;">TZS {{ number_format($analytics->avg_credit_amount ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Debits (Outflows)</strong></td>
                            <td style="text-align: center;">{{ number_format($analytics->total_debit_count ?? 0) }}</td>
                            <td style="text-align: right; color: #dc2626;">TZS {{ number_format($analytics->total_debits ?? 0, 0) }}</td>
                            <td style="text-align: right;">TZS {{ number_format($analytics->avg_debit_amount ?? 0, 0) }}</td>
                        </tr>
                        <tr style="background: #f9fafb; font-weight: 600;">
                            <td><strong>Net Position</strong></td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: right;">
                                @php
                                    $netPosition = ($analytics->total_credits ?? 0) - ($analytics->total_debits ?? 0);
                                @endphp
                                <strong style="color: {{ $netPosition >= 0 ? '#059669' : '#dc2626' }}">
                                    TZS {{ number_format($netPosition, 0) }}
                                </strong>
                            </td>
                            <td style="text-align: right;">-</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
        @endif

        <!-- Loan & Repayment Detection (NEW) -->
        @if($analytics && ($analytics->detected_loan_count > 0 || $analytics->loan_stacking_detected))
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Loan & Repayment Detection</h2>
                
                <table class="table">
                    <tr>
                        <td style="width: 30%;"><strong>Detected Loans</strong></td>
                        <td>
                            {{ $analytics->detected_loan_count ?? 0 }} loan(s)
                            @if($analytics->loan_stacking_detected)
                                <span style="color: #dc2626; font-weight: 600; margin-left: 8px;">⚠️ LOAN STACKING DETECTED</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Total Monthly Repayment</strong></td>
                        <td style="color: #dc2626; font-weight: 600;">TZS {{ number_format($analytics->detected_monthly_loan_repayment ?? 0, 0) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Detection Confidence</strong></td>
                        <td>
                            @php
                                $confidence = $analytics->loan_detection_confidence ?? 'none';
                                $confidenceColors = ['high' => '#059669', 'medium' => '#f59e0b', 'low' => '#6b7280', 'none' => '#9ca3af'];
                            @endphp
                            <span style="color: {{ $confidenceColors[$confidence] ?? '#6b7280' }}; font-weight: 600;">
                                {{ strtoupper($confidence) }}
                            </span>
                        </td>
                    </tr>
                    @if($analytics->loan_inflows > 0)
                    <tr>
                        <td><strong>Loan Disbursements Received</strong></td>
                        <td style="color: #f59e0b; font-weight: 600;">TZS {{ number_format($analytics->loan_inflows ?? 0, 0) }}</td>
                    </tr>
                    @endif
                </table>

                @if($analytics->detected_loans && is_array($analytics->detected_loans) && count($analytics->detected_loans) > 0)
                <div style="margin-top: 10px;">
                    <div style="font-weight: 600; margin-bottom: 6px; font-size: 10px; color: #374151;">Detected Loan Details:</div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lender</th>
                                <th>Description</th>
                                <th style="text-align: center;">Occurrences</th>
                                <th style="text-align: right;">Monthly Amount</th>
                                <th style="text-align: center;">Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analytics->detected_loans as $loan)
                            <tr>
                                <td><strong>{{ $loan['lender_name'] ?? 'Unknown' }}</strong></td>
                                <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($loan['description'] ?? 'N/A', 35) }}</td>
                                <td style="text-align: center;">{{ $loan['occurrences'] ?? 0 }}</td>
                                <td style="text-align: right;">TZS {{ number_format($loan['monthly_amount'] ?? 0, 0) }}</td>
                                <td style="text-align: center;">
                                    @php
                                        $loanConfidence = $loan['confidence'] ?? 'low';
                                        $confidenceColor = $loanConfidence === 'high' ? '#059669' : ($loanConfidence === 'medium' ? '#f59e0b' : '#6b7280');
                                    @endphp
                                    <span style="color: {{ $confidenceColor }}; font-weight: 600; font-size: 9px;">
                                        {{ strtoupper($loanConfidence) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    @if($analytics->loan_stacking_detected)
                    <div style="background: #fef2f2; padding: 10px; border-left: 3px solid #dc2626; margin-top: 10px;">
                        <div style="font-weight: 600; color: #991b1b; font-size: 10px; margin-bottom: 4px;">⚠️ LOAN STACKING ALERT</div>
                        <div style="font-size: 9px; color: #7f1d1d;">
                            Multiple active loans detected ({{ $analytics->detected_loan_count }} loans). This significantly increases credit risk and may affect debt-to-income ratio calculations.
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </section>
        </div>
        @endif

        <!-- Income Source Composition (NEW) -->
        @if($analytics && ($analytics->salary_income > 0 || $analytics->business_income > 0))
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Income Source Composition</h2>
                
                <table class="table">
                    @if($analytics->salary_income > 0)
                    <tr>
                        <td style="width: 30%;"><strong>Salary Income</strong></td>
                        <td style="color: #059669; font-weight: 600;">TZS {{ number_format($analytics->salary_income ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->business_income > 0)
                    <tr>
                        <td><strong>Business Income</strong></td>
                        <td style="color: #0891b2; font-weight: 600;">TZS {{ number_format($analytics->business_income ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->transfer_inflows > 0)
                    <tr>
                        <td><strong>Transfer Inflows</strong></td>
                        <td>TZS {{ number_format($analytics->transfer_inflows ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->loan_inflows > 0)
                    <tr>
                        <td><strong>Loan Inflows</strong></td>
                        <td style="color: #f59e0b; font-weight: 600;">TZS {{ number_format($analytics->loan_inflows ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->bulk_deposits > 0)
                    <tr>
                        <td><strong>Bulk Deposits</strong></td>
                        <td style="color: #8b5cf6; font-weight: 600;">TZS {{ number_format($analytics->bulk_deposits ?? 0, 0) }}</td>
                    </tr>
                    @endif
                    @if($analytics->other_income > 0)
                    <tr>
                        <td><strong>Other Income</strong></td>
                        <td>TZS {{ number_format($analytics->other_income ?? 0, 0) }}</td>
                    </tr>
                    @endif
                </table>

                @if($analytics->suspicious_deposits_flagged)
                <div style="background: #fef2f2; padding: 10px; border-left: 3px solid #dc2626; margin-top: 10px;">
                    <div style="font-weight: 600; color: #991b1b; font-size: 10px; margin-bottom: 4px;">⚠️ SUSPICIOUS DEPOSITS FLAGGED</div>
                    <div style="font-size: 9px; color: #7f1d1d;">
                        Large unexplained deposits detected. {{ $analytics->bulk_deposit_count ?? 0 }} bulk deposit(s) identified with unknown source.
                    </div>
                </div>
                @endif
            </section>
        </div>
        @endif

        <!-- Behavioral Analysis (NEW) -->
        @if($analytics && $analytics->behavioral_risk_level)
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Behavioral Analysis</h2>
                
                <table class="table">
                    <tr>
                        <td style="width: 30%;"><strong>Transaction Pattern</strong></td>
                        <td>{{ ucfirst($analytics->transaction_pattern ?? 'Unknown') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Behavioral Risk Level</strong></td>
                        <td>
                            @php
                                $behavioralRisk = $analytics->behavioral_risk_level ?? 'low';
                                $riskColors = ['high' => '#dc2626', 'medium' => '#f59e0b', 'low' => '#059669'];
                            @endphp
                            <span style="color: {{ $riskColors[$behavioralRisk] ?? '#6b7280' }}; font-weight: 600;">
                                {{ strtoupper($behavioralRisk) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Transaction Frequency</strong></td>
                        <td>{{ number_format($analytics->transaction_frequency_score ?? 0, 1) }}/100</td>
                    </tr>
                    <tr>
                        <td><strong>Cash Withdrawal Ratio</strong></td>
                        <td>{{ number_format($analytics->cash_withdrawal_ratio ?? 0, 1) }}%</td>
                    </tr>
                    <tr>
                        <td><strong>Income Volatility</strong></td>
                        <td>{{ number_format($analytics->income_volatility_coefficient ?? 0, 1) }}%</td>
                    </tr>
                </table>

                @if($analytics->behavioral_flags && is_array($analytics->behavioral_flags) && count($analytics->behavioral_flags) > 0)
                <div style="margin-top: 10px;">
                    <div style="font-weight: 600; margin-bottom: 6px; font-size: 10px; color: #374151;">Behavioral Flags:</div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 9px;">
                        @foreach($analytics->behavioral_flags as $flag)
                            <li style="margin-bottom: 4px; color: {{ ($flag['severity'] ?? 'low') === 'high' ? '#dc2626' : '#f59e0b' }}">
                                <strong>{{ ucwords(str_replace('_', ' ', $flag['flag'] ?? 'Unknown')) }}</strong>
                                @if(isset($flag['value']))
                                    - {{ $flag['value'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </section>
        </div>
        @endif

        <!-- Recommendations & Next Steps -->
        <div style="margin-bottom: 10px;">
            <section class="section">
                <h2 class="section-title">Recommendations & Next Steps</h2>
                <div style="background: #f0f9ff; padding: 12px; border-left: 3px solid var(--brand);">
                    @php
                        $systemDecision = $assessment->system_decision ?? 'pending';
                        $isRecommendable = $assessment->is_recommendable ?? false;
                        $hasConditions = isset($conditions) && is_array($conditions) && count($conditions) > 0;
                        $hasBreaches = isset($policyBreaches) && is_array($policyBreaches) && count($policyBreaches) > 0;
                    @endphp
                    
                    <div style="font-weight: 700; color: #1e40af; margin-bottom: 8px; font-size: 12px;">Recommended Actions:</div>
                    
                    <ul style="margin-left: 16px; color: #1e3a8a; line-height: 1.5; font-size: 11px;">
                        @if($systemDecision === 'approved' || $systemDecision === 'eligible')
                            @if($hasConditions)
                                <li><strong>PRE-QUALIFIED (CONDITIONAL):</strong> Customer meets eligibility criteria subject to conditions listed above.</li>
                                <li>Proceed to full loan application with required documentation.</li>
                                <li>Verify all conditions before final approval.</li>
                            @else
                                <li><strong>PRE-QUALIFIED:</strong> Customer meets all basic eligibility requirements.</li>
                                <li>Invite customer to submit complete loan application.</li>
                                <li>Provide list of required documentation (ID, proof of income, property documents).</li>
                            @endif
                            <li>Schedule property valuation and inspection.</li>
                            <li>Conduct full credit bureau check during formal application.</li>
                        @elseif($systemDecision === 'conditionally_approved' || $systemDecision === 'conditional')
                            <li><strong>CONDITIONAL PRE-QUALIFICATION:</strong> Review conditions and policy breaches carefully.</li>
                            @if($hasBreaches)
                                <li>Policy breaches identified - may require senior review or policy exception.</li>
                                <li>Consider loan restructuring: adjust amount to {{ isset($assessment->final_max_loan) ? 'TZS ' . number_format($assessment->final_max_loan, 0) : 'recommended level' }} or extend tenure.</li>
                            @endif
                            <li>Request additional supporting documents from customer.</li>
                            <li>Discuss alternative loan structures that may better fit customer profile.</li>
                        @elseif($systemDecision === 'rejected' || $systemDecision === 'declined' || $systemDecision === 'outside_policy')
                            <li><strong>NOT PRE-QUALIFIED:</strong> {{ $assessment->decision_reason ?? 'Does not meet minimum eligibility requirements.' }}</li>
                            @php
                                $compareRequestedAmount = $prospect->requested_amount > 0 ? $prospect->requested_amount : ($assessment->requested_amount ?? 0);
                            @endphp
                            @if(isset($assessment->final_max_loan) && $assessment->final_max_loan > 0 && $assessment->final_max_loan < $compareRequestedAmount)
                                <li>Customer may qualify for reduced loan of TZS {{ number_format($assessment->final_max_loan, 0) }}. Discuss counter-offer.</li>
                            @endif
                            <li>Provide clear feedback on reasons for decline.</li>
                            <li>Suggest steps to improve eligibility (e.g., reduce debt, increase down payment, extend tenure).</li>
                            <li>Consider alternative financing options or products better suited to customer profile.</li>
                        @else
                            <li>Complete full application review including document verification.</li>
                            <li>Request official bank statements and income verification.</li>
                            <li>Conduct property valuation and legal verification.</li>
                        @endif
                        
                        @php
                            $compareTenure = $prospect->requested_tenure > 0 ? $prospect->requested_tenure : ($assessment->requested_tenure_months ?? 0);
                        @endphp
                        @if($assessment->optimal_tenure_months && $assessment->optimal_tenure_months != $compareTenure)
                            <li><strong>Optimization:</strong> Consider extending tenure to {{ $assessment->optimal_tenure_months }} months for improved affordability.</li>
                        @endif
                        
                        @php
                            $analytics = $prospect->statementImport->analytics ?? null;
                        @endphp
                        @if($analytics && isset($analytics->gambling_transaction_count) && $analytics->gambling_transaction_count > 0)
                            <li><strong>Note:</strong> {{ $analytics->gambling_transaction_count }} gambling transactions detected. Assess customer's financial discipline during full application.</li>
                        @endif
                        
                        @if($analytics && isset($analytics->bounce_count) && $analytics->bounce_count > 0)
                            <li><strong>Note:</strong> {{ $analytics->bounce_count }} bounced transactions detected. Review during formal underwriting.</li>
                        @endif
                        
                        @if($systemDecision === 'approved' || $systemDecision === 'conditionally_approved')
                            <li><strong>Next Step:</strong> This pre-qualification is valid for 30 days. Customer should submit full application within this period.</li>
                        @endif
                    </ul>
                    
                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #bfdbfe; font-size: 10px; color: #64748b;">
                        <strong>Important:</strong> This is a preliminary automated assessment. Final loan approval requires complete application, document verification, property valuation, and compliance with all regulatory requirements.
                    </div>
                </div>
            </section>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    This automated pre-qualification report is valid for 30 days.
                </div>
                <div class="footer-right">
                    Final approval subject to full application review and verification.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
