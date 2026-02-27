<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eligibility Assessment Report</title>
    <style>
        @page { margin: 10mm; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #1a1a1a;
        }
        
        /* Compact Header */
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 18px 20px;
            margin: -10mm -10mm 15px -10mm;
            color: white;
        }
        
        .header-flex {
            display: table;
            width: 100%;
        }
        
        .header-left, .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        
        .header-right { text-align: right; }
        
        .header h1 {
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }
        
        .header .sub { font-size: 9pt; opacity: 0.9; }
        
        .report-id {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 7pt;
            display: inline-block;
            margin-top: 3px;
        }
        
        /* Decision Banner */
        .decision {
            margin: 12px 0;
            padding: 15px 20px;
            border-radius: 4px;
            text-align: center;
            border-left: 5px solid;
        }
        
        .decision.approved,
        .decision.eligible {
            background: #ecfdf5;
            border-color: #10b981;
        }
        
        .decision.rejected,
        .decision.declined,
        .decision.outside_policy {
            background: #fef2f2;  
            border-color: #ef4444;
        }
        
        .decision.conditional,
        .decision.pending {
            background: #fffbeb;
            border-color: #f59e0b;
        }
        
        .decision-icon {
            font-size: 28pt;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .decision.approved .decision-icon,
        .decision.eligible .decision-icon { color: #10b981; }
        .decision.rejected .decision-icon,
        .decision.declined .decision-icon,
        .decision.outside_policy .decision-icon { color: #ef4444; }
        .decision.conditional .decision-icon,
        .decision.pending .decision-icon { color: #f59e0b; }
        
        .decision-title {
            font-size: 14pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .decision.approved .decision-title,
        .decision.eligible .decision-title { color: #065f46; }
        .decision.rejected .decision-title,
        .decision.declined .decision-title,
        .decision.outside_policy .decision-title { color: #991b1b; }
        .decision.conditional .decision-title,
        .decision.pending .decision-title { color: #92400e; }
        
        .decision-amount {
            font-size: 13pt;
            font-weight: 700;
            margin-top: 8px;
            padding: 8px 16px;
            background: white;
            border-radius: 20px;
            display: inline-block;
            color: #1a1a1a;
        }
        
        /* Sections */
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 10pt;
            font-weight: 800;
            color: #2563eb;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #dbeafe;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Compact Info Grid */
        .grid {
            display: table;
            width: 100%;
            border-spacing: 4px;
        }
        
        .grid-row {
            display: table-row;
        }
        
        .grid-cell {
            display: table-cell;
            width: 50%;
            padding: 6px 10px;
            background: #f8fafc;
            border-left: 2px solid #2563eb;
        }
        
        .label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
        }
        
        .value {
            font-size: 9pt;
            color: #0f172a;
            font-weight: 500;
        }
        
        /* Grid 2-column layout */
        .grid-2 {
            display: table;
            width: 100%;
            border-spacing: 6px;
        }
        
        .grid-2 > div {
            display: table-cell;
            width: 50%;
            padding: 6px 10px;
            background: #f8fafc;
            border-left: 2px solid #2563eb;
            vertical-align: top;
        }
        
        /* Metrics - Inline */
        .metrics {
            display: table;
            width: 100%;
            margin: 10px 0;
            border-spacing: 6px 0;
        }
        
        .metric {
            display: table-cell;
            width: 25%;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 4px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .metric-label {
            font-size: 7pt;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .metric-value {
            font-size: 13pt;
            font-weight: 800;
            color: #2563eb;
        }
        
        /* Risk Badge */
        .risk-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 9pt;
            letter-spacing: 0.5px;
        }
        
        .risk-badge.grade-a { background: #10b981; color: white; }
        .risk-badge.grade-b { background: #84cc16; color: white; }
        .risk-badge.grade-c { background: #f59e0b; color: white; }
        .risk-badge.grade-d { background: #ef4444; color: white; }
        
        /* Compact Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 8pt;
        }
        
        table thead th {
            background: #2563eb;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        table tbody tr.highlight {
            background: #ecfdf5 !important;
            font-weight: 700;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 7pt;
            font-weight: 700;
        }
        
        .status-badge.success { background: #10b981; color: white; }
        .status-badge.warning { background: #f59e0b; color: white; }
        .status-badge.danger { background: #ef4444; color: white; }
        
        /* Conditions */
        .conditions {
            background: #fffbeb;
            padding: 10px 15px;
            border-left: 3px solid #f59e0b;
            border-radius: 3px;
            margin: 8px 0;
        }
        
        .conditions-title {
            font-size: 8pt;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 6px;
        }
        
        .conditions ul {
            margin: 0;
            padding-left: 16px;
        }
        
        .conditions li {
            margin-bottom: 3px;
            color: #92400e;
            font-size: 8pt;
            line-height: 1.3;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding: 10px 0;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #64748b;
        }
        
        .footer-brand {
            font-size: 9pt;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 4px;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                <h1>ELIGIBILITY ASSESSMENT REPORT</h1>
                @php
                    $headerInstitutionName = $institution->name ?? 'Financial Institution';
                    $headerInstitutionName = is_array($headerInstitutionName) ? (isset($headerInstitutionName[0]) ? $headerInstitutionName[0] : 'Financial Institution') : $headerInstitutionName;
                @endphp
                <div class="sub">{{ $headerInstitutionName }}</div>
            </div>
            <div class="header-right">
                <div>{{ $generated_at->format('d M Y, H:i') }}</div>
                @php
                    $appNumber = $application->application_number ?? 'N/A';
                    $appNumber = is_array($appNumber) ? implode('-', $appNumber) : $appNumber;
                @endphp
                <div class="report-id">APP: #{{ $appNumber }}</div>
            </div>
        </div>
    </div>

    <!-- Decision Banner -->
    @php
        $decisionClass = strtolower($assessment->system_decision ?? 'pending');
        $decisionClass = is_array($decisionClass) ? 'pending' : $decisionClass;
        $approvedAmount = is_numeric($assessment->final_max_loan) ? $assessment->final_max_loan : 0;
    @endphp
    <div class="decision {{ $decisionClass }}">
        <div class="decision-icon">
            @if(in_array($decisionClass, ['approved', 'eligible']))
                ✓
            @elseif(in_array($decisionClass, ['conditional', 'pending']))
                !
            @else
                ✗
            @endif
        </div>
        <div class="decision-title">{{ strtoupper(str_replace('_', ' ', $decisionClass)) }}</div>
        @if($approvedAmount > 0)
            <div class="decision-amount">TZS {{ number_format($approvedAmount, 0) }}</div>
        @endif
    </div>

    <!-- Key Metrics -->
    <div class="section">
        <div class="section-title">Key Metrics</div>
        <div class="metrics">
            @php
                // Try application requested_amount first, then assessment requested_amount
                $requestedAmount = 0;
                if (is_numeric($application->requested_amount) && $application->requested_amount > 0) {
                    $requestedAmount = $application->requested_amount;
                } elseif (is_numeric($assessment->requested_amount) && $assessment->requested_amount > 0) {
                    $requestedAmount = $assessment->requested_amount;
                }
                
                $metricNetIncome = is_numeric($assessment->net_monthly_income) ? $assessment->net_monthly_income : 0;
                $metricDti = is_numeric($assessment->dti_ratio) ? $assessment->dti_ratio : 0;
                $metricDsr = is_numeric($assessment->dsr_ratio) ? $assessment->dsr_ratio : 0;
            @endphp
            <div class="metric">
                <div class="metric-label">Requested</div>
                <div class="metric-value">{{ number_format($requestedAmount / 1000000, 2) }}M</div>
            </div>
            <div class="metric">
                <div class="metric-label">Income</div>
                <div class="metric-value">{{ number_format($metricNetIncome / 1000, 0) }}K</div>
            </div>
            <div class="metric">
                <div class="metric-label">DTI</div>
                <div class="metric-value">{{ number_format($metricDti, 1) }}%</div>
            </div>
            <div class="metric">
                <div class="metric-label">DSR</div>
                <div class="metric-value">{{ number_format($metricDsr, 1) }}%</div>
            </div>
        </div>
    </div>

    <!-- Application Information -->
    <div class="section">
        <div class="section-title">Application Information</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Application Number</span>
                    <span class="value">{{ $appNumber }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">Application Date</span>
                    <span class="value">{{ $application->created_at->format('d M Y') }}</span>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Customer Name</span>
                    @php
                        $customerFullName = $customer->full_name ?? 'N/A';
                        $customerFullName = is_array($customerFullName) ? implode(' ', $customerFullName) : $customerFullName;
                    @endphp
                    <span class="value">{{ $customerFullName }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">ID Number</span>
                    @php
                        $customerIdNumber = $customer->id_number ?? 'N/A';
                        $customerIdNumber = is_array($customerIdNumber) ? (isset($customerIdNumber[0]) ? $customerIdNumber[0] : 'N/A') : $customerIdNumber;
                    @endphp
                    <span class="value">{{ $customerIdNumber }}</span>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Customer Type</span>
                    <span class="value">{{ $customer->customer_type ? $customer->customer_type->label() : 'N/A' }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">Status</span>
                    <span class="value">{{ $application->status ? ucfirst($application->status->value) : 'Pending' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Request -->
    <div class="section">
        <div class="section-title">Loan Request Details</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Requested Amount</span>
                    <span class="value">TZS {{ number_format($requestedAmount, 0) }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">Tenure</span>
                    @php
                        // Try application tenure first, then assessment tenure
                        $requestedTenure = 0;
                        if (is_numeric($application->requested_tenure_months) && $application->requested_tenure_months > 0) {
                            $requestedTenure = $application->requested_tenure_months;
                        } elseif (is_numeric($assessment->requested_tenure_months) && $assessment->requested_tenure_months > 0) {
                            $requestedTenure = $assessment->requested_tenure_months;
                        }
                        $requestedTenure = is_array($requestedTenure) ? (isset($requestedTenure[0]) ? $requestedTenure[0] : 0) : $requestedTenure;
                    @endphp
                    <span class="value">{{ $requestedTenure }} months</span>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Loan Product</span>
                    @php
                        $productName = $product->name ?? 'N/A';
                        $productName = is_array($productName) ? (isset($productName[0]) ? $productName[0] : 'N/A') : $productName;
                    @endphp
                    <span class="value">{{ $productName }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">Interest Rate</span>
                    @php
                        $annualRate = $product->annual_interest_rate ?? 0;
                        $annualRate = is_array($annualRate) ? (isset($annualRate[0]) ? $annualRate[0] : 0) : $annualRate;
                    @endphp
                    <span class="value">{{ $annualRate }}% p.a.</span>
                </div>
            </div>
            @if(($application->property_value && $application->property_value > 0) || ($assessment->property_value && $assessment->property_value > 0))
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Property Type</span>
                    @php
                        $propertyType = $application->property_type ?? 'N/A';
                        $propertyType = is_array($propertyType) ? (isset($propertyType[0]) ? $propertyType[0] : 'N/A') : $propertyType;
                    @endphp
                    <span class="value">{{ ucfirst((string)$propertyType) }}</span>
                </div>
                <div class="grid-cell">
                    <span class="label">Property Value</span>
                    @php
                        // Try application property_value first, then assessment property_value
                        $propertyValue = 0;
                        if (is_numeric($application->property_value) && $application->property_value > 0) {
                            $propertyValue = $application->property_value;
                        } elseif (is_numeric($assessment->property_value) && $assessment->property_value > 0) {
                            $propertyValue = $assessment->property_value;
                        }
                        $propertyValue = is_array($propertyValue) ? (isset($propertyValue[0]) ? $propertyValue[0] : 0) : $propertyValue;
                    @endphp
                    <span class="value">TZS {{ number_format($propertyValue, 0) }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Risk Assessment -->
    @if($assessment->risk_grade)
    <div class="section">
        <div class="section-title">Risk Profile</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <span class="label">Risk Grade</span>
                    @php
                        $riskGrade = $assessment->risk_grade ?? 'N/A';
                        $riskGrade = is_array($riskGrade) ? 'N/A' : $riskGrade;
                    @endphp
                    <span class="value">
                        <span class="risk-badge grade-{{ strtolower($riskGrade) }}">
                            GRADE {{ strtoupper($riskGrade) }}
                        </span>
                    </span>
                </div>
                <div class="grid-cell">
                    <span class="label">Risk Score</span>
                    @php
                        $riskScore = is_numeric($assessment->risk_score) ? $assessment->risk_score : 0;
                    @endphp
                    <span class="value">{{ number_format($riskScore, 1) }}/100</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Financial Details -->
    @php
        $grossIncome = is_numeric($assessment->gross_monthly_income) ? $assessment->gross_monthly_income : 0;
        $netIncome = is_numeric($assessment->net_monthly_income) ? $assessment->net_monthly_income : 0;
        $totalDebt = is_numeric($assessment->total_monthly_debt) ? $assessment->total_monthly_debt : 0;
        $metricMaxAffordable = is_numeric($assessment->max_affordable_amount) ? $assessment->max_affordable_amount : 0;
    @endphp
    @if($netIncome > 0 || $totalDebt > 0)
    <div class="section">
        <div class="section-title">Financial Details</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th class="text-right">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @if($grossIncome > 0)
                <tr>
                    <td><strong>Gross Income</strong></td>
                    <td>Monthly income verified</td>
                    <td class="text-right"><strong>{{ number_format($grossIncome, 0) }}</strong></td>
                </tr>
                @endif
                @if($netIncome > 0)
                <tr>
                    <td><strong>Net Income</strong></td>
                    <td>After deductions</td>
                    <td class="text-right"><strong>{{ number_format($netIncome, 0) }}</strong></td>
                </tr>
                @endif
                @if($totalDebt > 0)
                <tr>
                    <td><strong>Total Debt</strong></td>
                    <td>Existing monthly obligations</td>
                    <td class="text-right"><strong>{{ number_format($totalDebt, 0) }}</strong></td>
                </tr>
                @endif
                @if($metricMaxAffordable > 0)
                <tr class="highlight">
                    <td><strong>Max Affordable</strong></td>
                    <td>Based on income & debt profile</td>
                    <td class="text-right"><strong>{{ number_format($metricMaxAffordable, 0) }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- Risk Factors -->
    @if($assessment->risk_factors)
    <div class="section">
        <div class="section-title">Risk Factors</div>
        <table>
            <thead>
                <tr>
                    <th>Risk Factor</th>
                    <th class="text-center">Score</th>
                    <th class="text-center">Weight</th>
                    <th class="text-center">Status</th>
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
                        $displayValue = is_numeric($factorValue) ? number_format($factorValue, 2) : $factorValue;
                    @endphp
                    <td>{{ $displayName }}</td>
                    <td class="text-center">{{ $displayValue }}</td>
                    <td class="text-center">{{ $factorWeight }}%</td>
                    <td class="text-center">
                        @if(is_numeric($factorValue) && $factorValue < 40)
                        <span class="status-badge success">Low</span>
                        @elseif(is_numeric($factorValue) && $factorValue < 70)
                        <span class="status-badge warning">Medium</span>
                        @elseif(is_numeric($factorValue))
                        <span class="status-badge danger">High</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Conditions -->
    @php
        $conditions = is_array($assessment->conditions) 
            ? $assessment->conditions 
            : ($assessment->conditions ? json_decode($assessment->conditions, true) : []);
    @endphp
    @if($conditions && count($conditions) > 0)
    <div class="section">
        <div class="section-title">Conditions</div>
        <div class="conditions">
            <div class="conditions-title">The following conditions must be met:</div>
            <ul>
                @foreach($conditions as $condition)
                @php
                    $conditionText = is_array($condition) ? ($condition['description'] ?? $condition['condition'] ?? implode(': ', $condition)) : $condition;
                @endphp
                <li>{{ $conditionText }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Policy Breaches -->
    @php
        $policyBreaches = is_array($assessment->policy_breaches) 
            ? $assessment->policy_breaches 
            : ($assessment->policy_breaches ? json_decode($assessment->policy_breaches, true) : []);
    @endphp
    @if($policyBreaches && count($policyBreaches) > 0)
    <div class="section">
        <div class="section-title">Policy Breaches</div>
        <div style="background: #fef2f2; padding: 12px; border-left: 4px solid #ef4444; border-radius: 3px;">
            <div style="font-weight: 600; color: #991b1b; margin-bottom: 8px; font-size: 8pt;">⚠️ The following policy requirements were not met:</div>
            <ul style="margin-left: 16px; color: #7f1d1d;">
                @foreach($policyBreaches as $breach)
                @php
                    $breachText = is_array($breach) ? ($breach['description'] ?? $breach['breach'] ?? implode(': ', $breach)) : $breach;
                @endphp
                <li style="margin-bottom: 4px;">{{ $breachText }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Income & Affordability Analysis -->
    @if($assessment->gross_monthly_income || $assessment->net_monthly_income || $assessment->max_loan_from_affordability)
    <div class="section">
        <div class="section-title">Income & Affordability Analysis</div>
        <table>
            <tbody>
                @if($assessment->gross_monthly_income)
                <tr>
                    <td style="width: 40%;"><strong>Gross Monthly Income</strong></td>
                    <td style="width: 35%;">Total income before deductions</td>
                    <td style="width: 25%; text-align: right;"><strong>{{ number_format($assessment->gross_monthly_income, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->net_monthly_income)
                <tr>
                    <td><strong>Net Monthly Income</strong></td>
                    <td>After taxes and deductions</td>
                    <td class="text-right"><strong>{{ number_format($assessment->net_monthly_income, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->total_monthly_debt)
                <tr>
                    <td><strong>Total Monthly Debt</strong></td>
                    <td>Existing obligations ({{ $assessment->detected_debt_count ?? 0 }} detected)</td>
                    <td class="text-right"><strong>{{ number_format($assessment->total_monthly_debt, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->net_disposable_income)
                <tr>
                    <td><strong>Disposable Income</strong></td>
                    <td>After all obligations</td>
                    <td class="text-right"><strong>{{ number_format($assessment->net_disposable_income, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->max_installment_from_income)
                <tr class="highlight">
                    <td><strong>Max Affordable Installment</strong></td>
                    <td>Based on income capacity</td>
                    <td class="text-right"><strong>{{ number_format($assessment->max_installment_from_income, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->max_loan_from_affordability)
                <tr class="highlight">
                    <td><strong>Max Affordable Loan</strong></td>
                    <td>From affordability analysis</td>
                    <td class="text-right"><strong>{{ number_format($assessment->max_loan_from_affordability, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->max_loan_from_ltv && $assessment->property_value)
                <tr>
                    <td><strong>Max Loan from LTV</strong></td>
                    <td>Based on property value ({{ number_format($assessment->ltv_ratio, 1) }}% LTV)</td>
                    <td class="text-right"><strong>{{ number_format($assessment->max_loan_from_ltv, 0) }}</strong></td>
                </tr>
                @endif
                @if($assessment->final_max_loan)
                <tr style="background: #f0f9ff; font-weight: 700;">
                    <td><strong>Final Maximum Loan</strong></td>
                    <td>Considering all constraints</td>
                    <td class="text-right" style="color: #2563eb;"><strong>{{ number_format($assessment->final_max_loan, 0) }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- Statement Analytics Summary -->
    @if(isset($analytics) && $analytics)
    <div class="section">
        <div class="section-title">Bank Statement Analysis</div>
        <div class="grid-2">
            <div>
                <div class="label">Statement Period</div>
                <div class="value">{{ $analytics->statement_start_date?->format('d M Y') }} - {{ $analytics->statement_end_date?->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Analysis Period</div>
                <div class="value">{{ $analytics->statement_months ?? 'N/A' }} months</div>
            </div>
            @if($analytics->average_monthly_income)
            <div>
                <div class="label">Avg Monthly Income</div>
                <div class="value">{{ number_format($analytics->average_monthly_income, 0) }}</div>
            </div>
            @endif
            @if($analytics->average_monthly_expense)
            <div>
                <div class="label">Avg Monthly Expenses</div>
                <div class="value">{{ number_format($analytics->average_monthly_expense, 0) }}</div>
            </div>
            @endif
            @if($analytics->average_balance)
            <div>
                <div class="label">Avg Account Balance</div>
                <div class="value">{{ number_format($analytics->average_balance, 0) }}</div>
            </div>
            @endif
            @if($analytics->income_volatility_percentage)
            <div>
                <div class="label">Income Volatility</div>
                <div class="value">{{ number_format($analytics->income_volatility_percentage, 1) }}%</div>
            </div>
            @endif
            @if($analytics->account_bounce_count !== null)
            <div>
                <div class="label">Bounced Transactions</div>
                <div class="value">{{ $analytics->account_bounce_count }}</div>
            </div>
            @endif
            @if($analytics->gambling_transaction_count !== null && $analytics->gambling_transaction_count > 0)
            <div>
                <div class="label">Gambling Transactions</div>
                <div class="value">{{ $analytics->gambling_transaction_count }} ({{ number_format($analytics->gambling_amount ?? 0, 0) }})</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Underwriting Decision -->
    @if(isset($underwritingDecision) && $underwritingDecision)
    <div class="section">
        <div class="section-title">Underwriting Decision</div>
        <div class="grid-2">
            <div>
                <div class="label">Decision Status</div>
                <div class="value">{{ ucwords(str_replace('_', ' ', $underwritingDecision->decision_status ?? 'N/A')) }}</div>
            </div>
            <div>
                <div class="label">Final Decision</div>
                <div class="value">{{ ucwords(str_replace('_', ' ', $underwritingDecision->final_decision ?? 'N/A')) }}</div>
            </div>
            @if($underwritingDecision->approved_amount)
            <div>
                <div class="label">Approved Amount</div>
                <div class="value">{{ number_format($underwritingDecision->approved_amount, 0) }}</div>
            </div>
            @endif
            @if($underwritingDecision->approved_tenure_months)
            <div>
                <div class="label">Approved Tenure</div>
                <div class="value">{{ $underwritingDecision->approved_tenure_months }} months</div>
            </div>
            @endif
            @if($underwritingDecision->approved_interest_rate)
            <div>
                <div class="label">Approved Interest Rate</div>
                <div class="value">{{ number_format($underwritingDecision->approved_interest_rate, 2) }}%</div>
            </div>
            @endif
            @if($underwritingDecision->final_monthly_installment)
            <div>
                <div class="label">Monthly Installment</div>
                <div class="value">{{ number_format($underwritingDecision->final_monthly_installment, 0) }}</div>
            </div>
            @endif
        </div>
        @if($underwritingDecision->decision_reason)
        <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border-left: 3px solid #2563eb; border-radius: 3px;">
            <div style="font-size: 7pt; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Reason</div>
            <div style="font-size: 8pt; color: #1e293b;">{{ $underwritingDecision->decision_reason }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Recommendations & Next Steps -->
    <div class="section">
        <div class="section-title">Recommendations & Next Steps</div>
        <div style="background: #f0f9ff; padding: 14px; border-left: 4px solid #2563eb; border-radius: 3px;">
            @php
                $systemDecision = $assessment->system_decision ?? 'pending';
                $isRecommendable = $assessment->is_recommendable ?? false;
                $hasConditions = $conditions && count($conditions) > 0;
                $hasBreaches = $policyBreaches && count($policyBreaches) > 0;
            @endphp
            
            <div style="font-weight: 700; color: #1e40af; margin-bottom: 10px; font-size: 9pt;">Recommended Actions:</div>
            
            <ul style="margin-left: 16px; color: #1e3a8a; line-height: 1.6;">
                @if($systemDecision === 'approved' || $systemDecision === 'eligible')
                    @if($hasConditions)
                        <li><strong>CONDITIONAL APPROVAL:</strong> Proceed with loan processing subject to the conditions listed above being satisfied.</li>
                        <li>Verify and collect documentation for all specified conditions before final disbursement.</li>
                    @else
                        <li><strong>APPROVED:</strong> Customer meets all eligibility criteria. Proceed to loan documentation and disbursement stage.</li>
                        <li>Prepare loan agreement and disclosure documents.</li>
                    @endif
                    <li>Conduct final verification of customer identity and property details.</li>
                    <li>Complete credit bureau check and verify no adverse changes since assessment.</li>
                @elseif($systemDecision === 'conditional' || $systemDecision === 'pending')
                    <li><strong>CONDITIONAL:</strong> Review the conditions and policy breaches listed above.</li>
                    @if($hasBreaches)
                        <li>Escalate to senior underwriter for review of policy breaches and potential override.</li>
                        <li>Document justification for any policy exceptions or waivers.</li>
                    @endif
                    <li>Request additional documentation or clarification from customer.</li>
                    <li>Consider adjusting loan amount to {{ isset($assessment->final_max_loan) ? number_format($assessment->final_max_loan, 0) : 'recommended level' }} or tenure to meet policy requirements.</li>
                @elseif($systemDecision === 'rejected' || $systemDecision === 'declined' || $systemDecision === 'outside_policy')
                    <li><strong>DECLINED:</strong> {{ $assessment->decision_reason ?? 'Application does not meet minimum eligibility requirements.' }}</li>
                    @if(isset($assessment->final_max_loan) && $assessment->final_max_loan > 0 && $assessment->final_max_loan < $assessment->requested_amount)
                        <li>Customer may qualify for reduced loan amount of {{ number_format($assessment->final_max_loan, 0) }}. Consider counter-offer.</li>
                    @endif
                    <li>Provide clear feedback to customer on reasons for decline and steps to improve eligibility.</li>
                    <li>Suggest alternative products or deferred consideration after addressing identified issues.</li>
                @else
                    <li>Complete full underwriting review including manual assessment of application.</li>
                    <li>Verify all customer information and supporting documents.</li>
                    <li>Conduct property valuation and legal due diligence.</li>
                @endif
                
                @if($assessment->optimal_tenure_months && $assessment->optimal_tenure_months != $assessment->requested_tenure_months)
                    <li>Consider optimal tenure of {{ $assessment->optimal_tenure_months }} months for better affordability.</li>
                @endif
                
                @if($analytics && isset($analytics->gambling_transaction_count) && $analytics->gambling_transaction_count > 0)
                    <li><strong>Note:</strong> Gambling transactions detected in bank statements. Assess impact on affordability and repayment capacity.</li>
                @endif
                
                @if($analytics && isset($analytics->account_bounce_count) && $analytics->account_bounce_count > 0)
                    <li><strong>Note:</strong> {{ $analytics->account_bounce_count }} bounced transactions detected. Review customer's account management and financial discipline.</li>
                @endif
            </ul>
            
            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #bfdbfe; font-size: 7pt; color: #64748b;">
                <strong>Important:</strong> This report is generated by automated analysis and serves as a preliminary assessment. Final lending decision requires human review, verification of all information, and compliance with all regulatory and internal policy requirements.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-brand">{{ $headerInstitutionName }}</div>
        <div>This eligibility assessment is based on the information provided and automated analysis. Final approval subject to further verification.</div>
        <div style="margin-top: 6px; color: #94a3b8;">Report #{{ $assessment->id }} • Generated {{ $generated_at->format('d M Y, H:i:s') }}</div>
    </div>
</body>
</html>
