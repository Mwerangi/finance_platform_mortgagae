<template>
    <AppLayout>
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Eligibility Assessment Results</h1>
                            <p class="text-muted mb-0">
                                Your loan eligibility has been assessed based on bank statement analysis.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-check-lg fw-bold"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Basic Information</div>
                                        <small class="text-success">Completed</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-check-lg fw-bold"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Bank Statement</div>
                                        <small class="text-success">Completed</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        3
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Eligibility Results</div>
                                        <small class="text-muted">Current step</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amendment History Banner (if amended) -->
            <div v-if="assessment?.calculation_details?.amendment_history" class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info border-start border-5 border-warning">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                            <div>
                                <h6 class="mb-1">
                                    <i class="bi bi-pencil-square me-1"></i>Loan Parameters Amended
                                </h6>
                                <p class="mb-1 small">
                                    <strong>Amended by:</strong> {{ assessment.calculation_details.amendment_history.amended_by }} 
                                    on {{ formatDate(assessment.calculation_details.amendment_history.amended_at) }}
                                </p>
                                <p class="mb-1 small">
                                    <strong>Changes:</strong> 
                                    Amount: TZS {{ Number(assessment.calculation_details.amendment_history.previous_amount).toLocaleString() }} 
                                    → TZS {{ Number(prospect.requested_amount).toLocaleString() }} | 
                                    Tenure: {{ assessment.calculation_details.amendment_history.previous_tenure }}
                                    → {{ prospect.requested_tenure }} months
                                </p>
                                <p class="mb-0 small">
                                    <strong>Reason:</strong> {{ assessment.calculation_details.amendment_history.reason }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    <div 
                        class="alert" 
                        :class="{
                            'alert-success': isEligible,
                            'alert-warning': isConditional,
                            'alert-danger': isDeclined
                        }"
                        role="alert"
                    >
                        <div class="d-flex align-items-center">
                            <div class="fs-1 me-3">
                                <i v-if="isEligible" class="bi bi-check-circle-fill"></i>
                                <i v-else-if="isConditional" class="bi bi-exclamation-triangle-fill"></i>
                                <i v-else class="bi bi-x-circle-fill"></i>
                            </div>
                            <div>
                                <h4 class="alert-heading mb-2">
                                    <span v-if="isEligible">Congratulations! You're Eligible</span>
                                    <span v-else-if="isConditional">Conditionally Eligible</span>
                                    <span v-else>Not Eligible at This Time</span>
                                </h4>
                                <p class="mb-0">{{ assessment?.decision_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Maximum Loan Details -->
                    <div v-if="isEligible || isConditional" class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cash-stack me-2"></i>Maximum Loan Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <div class="border-end">
                                        <div class="text-muted small">Maximum Loan Amount</div>
                                        <div class="h3 text-primary mb-0">
                                            TZS {{ Number(assessment.final_max_loan).toLocaleString() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border-end">
                                        <div class="text-muted small">Risk Grade</div>
                                        <div class="h3 mb-0">
                                            <span :class="getRiskGradeClass(assessment.risk_grade)">
                                                {{ assessment.risk_grade }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div>
                                        <div class="text-muted small">Monthly Installment</div>
                                        <div class="h3 text-success mb-0">
                                            TZS {{ Number(assessment.proposed_installment).toLocaleString() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="prospect.requested_amount > assessment.final_max_loan" class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Your requested amount (TZS {{ Number(prospect.requested_amount).toLocaleString() }}) 
                                exceeds the maximum qualified amount. Consider applying for the maximum amount or reducing your request.
                            </div>
                        </div>
                    </div>

                    <!-- Income Analysis -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up me-2"></i>Income Analysis
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Gross Monthly Income:</span>
                                        <strong>TZS {{ Number(assessment.gross_monthly_income).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Net Monthly Income:</span>
                                        <strong>TZS {{ Number(assessment.net_monthly_income).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Income Stability Score:</span>
                                        <strong>{{ assessment.income_stability_score }}%</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Income Classification:</span>
                                        <span class="badge bg-info">{{ assessment.income_classification }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Ratios -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calculator me-2"></i>Financial Ratios
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Debt-to-Income (DTI)</div>
                                        <div class="h4" :class="getRatioClass(assessment.dti_ratio, 40)">
                                            {{ assessment.dti_ratio }}%
                                        </div>
                                        <small class="text-muted">Policy Max: 40%</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Debt Service Ratio (DSR)</div>
                                        <div class="h4" :class="getRatioClass(assessment.dsr_ratio, 50)">
                                            {{ assessment.dsr_ratio }}%
                                        </div>
                                        <small class="text-muted">Policy Max: 50%</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Loan-to-Value (LTV)</div>
                                        <div class="h4" :class="getRatioClass(assessment.ltv_ratio, 90)">
                                            {{ assessment.ltv_ratio || 0 }}%
                                        </div>
                                        <small class="text-muted">Policy Max: 90%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Debt Analysis -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-credit-card me-2"></i>Existing Debt Analysis
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Monthly Debt:</span>
                                        <strong>TZS {{ Number(assessment.total_monthly_debt).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Detected Debt Accounts:</span>
                                        <strong>{{ assessment.detected_debt_count }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Net Disposable Income:</span>
                                        <strong>TZS {{ Number(assessment.net_disposable_income).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Surplus After Loan:</span>
                                        <strong :class="assessment.net_surplus_after_loan < 0 ? 'text-danger' : 'text-success'">
                                            TZS {{ Number(assessment.net_surplus_after_loan).toLocaleString() }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Summary -->
                    <div v-if="analytics" class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up-arrow me-2"></i>Transaction Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <div class="border-end">
                                        <div class="text-muted small">Total Credits</div>
                                        <div class="h4 text-success mb-1">
                                            TZS {{ Number(analytics.total_credits || 0).toLocaleString() }}
                                        </div>
                                        <small class="text-muted">{{ analytics.total_credit_count || 0 }} transactions</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border-end">
                                        <div class="text-muted small">Total Debits</div>
                                        <div class="h4 text-danger mb-1">
                                            TZS {{ Number(analytics.total_debits || 0).toLocaleString() }}
                                        </div>
                                        <small class="text-muted">{{ analytics.total_debit_count || 0 }} transactions</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div>
                                        <div class="text-muted small">Net Position</div>
                                        <div class="h4 mb-1" :class="(analytics.total_credits - analytics.total_debits) >= 0 ? 'text-success' : 'text-danger'">
                                            TZS {{ Number((analytics.total_credits || 0) - (analytics.total_debits || 0)).toLocaleString() }}
                                        </div>
                                        <small class="text-muted">Avg Credit: TZS {{ Number(analytics.avg_credit_amount || 0).toLocaleString() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loan & Repayment Detection -->
                    <div v-if="analytics && analytics.detected_loan_count > 0" class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-circle me-2"></i>Loan & Repayment Detection
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Detected Loans:</span>
                                        <strong class="text-warning">{{ analytics.detected_loan_count }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Monthly Repayment:</span>
                                        <strong class="text-danger">TZS {{ Number(analytics.detected_monthly_loan_repayment || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Confidence:</span>
                                        <strong>{{ analytics.loan_detection_confidence || 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Loan Stacking Alert -->
                            <div v-if="analytics.loan_stacking_detected" class="alert alert-danger mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>LOAN STACKING DETECTED!</strong>
                                <p class="mb-0 small">Multiple active loans detected. This significantly increases repayment risk.</p>
                            </div>

                            <!-- Detected Loans Table -->
                            <div v-if="analytics.detected_loans && analytics.detected_loans.length > 0" class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Lender/Keyword</th>
                                            <th>Detected Amount</th>
                                            <th>Occurrences</th>
                                            <th>Confidence</th>
                                            <th>First Detected</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(loan, idx) in analytics.detected_loans" :key="idx">
                                            <td>{{ loan.lender || loan.keyword }}</td>
                                            <td>TZS {{ Number(loan.amount).toLocaleString() }}</td>
                                            <td>{{ loan.occurrences || 1 }}</td>
                                            <td>
                                                <span class="badge" :class="{
                                                    'bg-success': loan.confidence === 'high',
                                                    'bg-warning': loan.confidence === 'medium',
                                                    'bg-secondary': loan.confidence === 'low'
                                                }">
                                                    {{ loan.confidence || 'N/A' }}
                                                </span>
                                            </td>
                                            <td><small>{{ loan.first_detected || 'N/A' }}</small></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Income Source Composition -->
                    <div v-if="analytics && (analytics.salary_income > 0 || analytics.business_income > 0)" class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-pie-chart me-2"></i>Income Source Composition
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div v-if="analytics.salary_income > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Salary Income:</span>
                                        <strong class="text-success">TZS {{ Number(analytics.salary_income || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div v-if="analytics.business_income > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Business Income:</span>
                                        <strong class="text-info">TZS {{ Number(analytics.business_income || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div v-if="analytics.transfer_inflows > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Transfer Inflows:</span>
                                        <strong>TZS {{ Number(analytics.transfer_inflows || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div v-if="analytics.loan_inflows > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Loan Inflows:</span>
                                        <strong class="text-warning">TZS {{ Number(analytics.loan_inflows || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div v-if="analytics.bulk_deposits > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Bulk Deposits:</span>
                                        <strong class="text-purple">TZS {{ Number(analytics.bulk_deposits || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                                <div v-if="analytics.other_income > 0" class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Other Income:</span>
                                        <strong>TZS {{ Number(analytics.other_income || 0).toLocaleString() }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Suspicious Deposits Alert -->
                            <div v-if="analytics.suspicious_deposits_flagged" class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-diamond me-2"></i>
                                <strong>SUSPICIOUS DEPOSITS FLAGGED</strong>
                                <p class="mb-0 small">
                                    {{ analytics.bulk_deposit_count || 0 }} large unexplained deposit(s) detected. 
                                    Manual verification recommended.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Behavioral Analysis -->
                    <div v-if="analytics && analytics.behavioral_pattern" class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-activity me-2"></i>Behavioral Analysis
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Transaction Pattern:</span>
                                        <strong>{{ analytics.behavioral_pattern || 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Behavioral Risk:</span>
                                        <span class="badge" :class="{
                                            'bg-success': analytics.behavioral_risk_level === 'low',
                                            'bg-warning': analytics.behavioral_risk_level === 'medium',
                                            'bg-danger': analytics.behavioral_risk_level === 'high'
                                        }">
                                            {{ analytics.behavioral_risk_level || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Frequency Score:</span>
                                        <strong>{{ analytics.transaction_frequency_score || 0 }}/100</strong>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Cash Withdrawal Ratio:</span>
                                        <strong>{{ Number(analytics.cash_withdrawal_ratio || 0).toFixed(2) }}%</strong>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Income Volatility:</span>
                                        <strong>{{ Number(analytics.income_volatility || 0).toFixed(2) }}%</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Behavioral Flags -->
                            <div v-if="analytics.behavioral_flags && analytics.behavioral_flags.length > 0">
                                <h6 class="text-muted small mb-2">Behavioral Flags:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span v-for="(flag, idx) in analytics.behavioral_flags" :key="idx" class="badge bg-warning text-dark">
                                        {{ flag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conditions (if any) -->
                    <div v-if="assessment.conditions && assessment.conditions.length > 0" class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-diamond me-2"></i>Conditions to Meet
                            </h5>
                        </div>
                        <div class="card-body">
                            <div v-for="(condition, index) in assessment.conditions" :key="index" class="mb-3 pb-3" :class="{ 'border-bottom': index < assessment.conditions.length - 1 }">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="text-dark mb-0">
                                        <i class="bi bi-dot"></i>{{ formatConditionTitle(condition.condition) }}
                                    </h6>
                                    <span class="badge" :class="getSeverityBadgeClass(condition.severity)">
                                        {{ condition.severity.toUpperCase() }}
                                    </span>
                                </div>
                                <p class="text-muted mb-0 ms-3">
                                    <i class="bi bi-arrow-right-short"></i>
                                    <strong>Recommendation:</strong> {{ condition.recommendation }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Policy Breaches (if declined) -->
                    <div v-if="isDeclined && assessment.policy_breaches && assessment.policy_breaches.length > 0" class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-shield-exclamation me-2"></i>Policy Issues
                            </h5>
                        </div>
                        <div class="card-body">
                            <div v-for="(breach, index) in assessment.policy_breaches" :key="index" class="mb-3 pb-3" :class="{ 'border-bottom': index < assessment.policy_breaches.length - 1 }">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <h6 class="text-danger mb-1">
                                            <i class="bi bi-x-circle me-1"></i>{{ formatPolicyRule(breach.rule) }}
                                        </h6>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted d-block">Required Threshold</small>
                                                <strong>{{ formatThreshold(breach.rule, breach.threshold) }}</strong>
                                            </div>
                                            <i class="bi bi-arrow-right text-muted mx-3"></i>
                                            <div>
                                                <small class="text-muted d-block">Actual Value</small>
                                                <strong class="text-danger">{{ formatThreshold(breach.rule, breach.actual) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mb-4">
                        <Link href="/dashboard" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>Back to Dashboard
                        </Link>
                        <div class="d-flex gap-2">
                            <!-- Override Policy Button (for declined applications, senior management only) -->
                            <button
                                v-if="isDeclined && canOverridePolicy"
                                @click="showOverrideModal = true"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-shield-exclamation me-2"></i>Override Policy Decision
                            </button>
                            
                            <!-- Amend Loan Button (for declined applications, privileged users only) -->
                            <button
                                v-if="isDeclined && canAmendLoan"
                                @click="showAmendModal = true"
                                class="btn btn-warning"
                            >
                                <i class="bi bi-pencil-square me-2"></i>Amend Loan Parameters
                            </button>
                            
                            <button
                                v-if="!isDeclined && prospect.can_convert_to_customer"
                                @click="showConvertModal = true"
                                class="btn btn-success"
                                :disabled="converting"
                            >
                                <i class="bi bi-person-plus me-2"></i>
                                Continue to Customer Registration
                            </button>
                            <button class="btn btn-outline-info" @click="downloadReport">
                                <i class="bi bi-download me-2"></i>Download Report
                            </button>
                        </div>
                    </div>

                    <!-- Amendment Modal -->
                    <div v-if="showAmendModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-pencil-square me-2"></i>Amend Loan Parameters
                                    </h5>
                                    <button type="button" class="btn-close" @click="closeAmendModal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Current Application:</strong><br>
                                        Requested Amount: TZS {{ Number(prospect.requested_amount).toLocaleString() }}<br>
                                        Tenure: {{ prospect.requested_tenure }} months<br>
                                        <span class="text-danger">Max Eligible: TZS {{ Number(assessment.final_max_loan).toLocaleString() }}</span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">New Loan Amount (TZS)</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            v-model="amendForm.amount"
                                            :max="assessment.final_max_loan"
                                            min="1000000"
                                            step="100000"
                                        />
                                        <small class="text-muted">
                                            Recommended: Up to TZS {{ Number(assessment.final_max_loan).toLocaleString() }}
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">New Tenure (Months)</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            v-model="amendForm.tenure"
                                            min="3"
                                            :max="prospect.loan_product?.max_tenure_months || 360"
                                            step="1"
                                        />
                                        <small class="text-muted">
                                            Range: 3 to {{ prospect.loan_product?.max_tenure_months || 360 }} months
                                        </small>
                                    </div>

                                    <div class="alert alert-warning" v-if="amendForm.amount && amendForm.tenure">
                                        <strong>Estimated New Monthly Installment:</strong><br>
                                        TZS {{ Number(estimatedInstallment).toLocaleString() }}
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Reason for Amendment</label>
                                        <textarea 
                                            class="form-control" 
                                            v-model="amendForm.reason"
                                            rows="3"
                                            placeholder="Explain why you're amending the loan parameters..."
                                            required
                                        ></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" @click="closeAmendModal">
                                        Cancel
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-primary" 
                                        @click="submitAmendment"
                                        :disabled="!amendForm.amount || !amendForm.tenure || !amendForm.reason || amending"
                                    >
                                        <span v-if="amending" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-check-circle me-2"></i>
                                        Re-run Assessment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Convert to Customer Modal -->
                    <div v-if="showConvertModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title">
                                        <i class="bi bi-person-check me-2"></i>Convert to Full Application
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" @click="showConvertModal = false"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Pre-Qualification Complete!</strong><br>
                                        This prospect has passed the eligibility assessment and is ready to proceed.
                                    </div>

                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <strong>Applicant Information</strong>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-2"><strong>Name:</strong> {{ prospect.first_name }} {{ prospect.middle_name }} {{ prospect.last_name }}</p>
                                            <p class="mb-2"><strong>Phone:</strong> {{ prospect.phone }}</p>
                                            <p class="mb-2"><strong>Email:</strong> {{ prospect.email || 'N/A' }}</p>
                                            <p class="mb-0"><strong>ID Number:</strong> {{ prospect.id_number }}</p>
                                        </div>
                                    </div>

                                    <div class="alert alert-success">
                                        <h6 class="mb-2"><i class="bi bi-check-circle me-2"></i>Assessment Results</h6>
                                        <p class="mb-1"><strong>Decision:</strong> <span class="badge bg-success">{{ assessment.system_decision.toUpperCase() }}</span></p>
                                        <p class="mb-1"><strong>Approved Amount:</strong> TZS {{ Number(assessment.final_max_loan).toLocaleString() }}</p>
                                        <p class="mb-0"><strong>Risk Grade:</strong> {{ assessment.risk_grade }}</p>
                                    </div>

                                    <p class="text-muted mb-0">
                                        <i class="bi bi-arrow-right-circle me-2"></i>
                                        <strong>Next Steps:</strong> Converting this prospect to a customer will create a full customer profile and allow you to:
                                    </p>
                                    <ul class="text-muted mt-2">
                                        <li>Collect KYC documents</li>
                                        <li>Complete due diligence</li>
                                        <li>Create formal loan application</li>
                                        <li>Process final approval</li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" @click="showConvertModal = false">
                                        Cancel
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-success" 
                                        @click="confirmConversion"
                                        :disabled="converting"
                                    >
                                        <span v-if="converting" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-check-circle me-2"></i>
                                        {{ converting ? 'Converting...' : 'Convert to Customer' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Override Policy Modal -->
                    <div v-if="showOverrideModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">
                                        <i class="bi bi-shield-exclamation me-2"></i>Override Policy Decision
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" @click="closeOverrideModal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning mb-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Warning:</strong> You are about to override the automated policy decision. 
                                        This action requires senior management approval and will be logged for audit purposes.
                                    </div>

                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <strong>Current Assessment Summary</strong>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Decision:</strong> <span class="badge bg-danger">{{ assessment.system_decision.toUpperCase() }}</span></p>
                                                    <p class="mb-1"><strong>Risk Grade:</strong> {{ assessment.risk_grade }} ({{ assessment.risk_score }}/100)</p>
                                                    <p class="mb-0"><strong>DTI Ratio:</strong> {{ assessment.dti_ratio }}%</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Requested:</strong> TZS {{ Number(prospect.requested_amount).toLocaleString() }}</p>
                                                    <p class="mb-1"><strong>Max Eligible:</strong> TZS {{ Number(assessment.final_max_loan).toLocaleString() }}</p>
                                                    <p class="mb-0"><strong>Policy Breaches:</strong> {{ assessment.policy_breaches?.length || 0 }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Approved Loan Amount (TZS)</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            v-model="overrideForm.approved_amount"
                                            min="1000000"
                                            step="100000"
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Approved Tenure (Months)</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            v-model="overrideForm.approved_tenure"
                                            min="3"
                                            max="360"
                                            step="1"
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-danger">Override Justification (Required) *</label>
                                        <textarea 
                                            class="form-control" 
                                            v-model="overrideForm.override_reason"
                                            rows="4"
                                            placeholder="Provide detailed justification for overriding the policy decision. Include:&#10;- Specific mitigating factors&#10;- Additional collateral or guarantees&#10;- Business/relationship considerations&#10;- Risk mitigation measures"
                                            required
                                        ></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Special Conditions (Optional)</label>
                                        <textarea 
                                            class="form-control" 
                                            v-model="overrideForm.conditions"
                                            rows="3"
                                            placeholder="Any special conditions or requirements for approval (e.g., guarantor required, additional collateral, etc.)"
                                        ></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" @click="closeOverrideModal">
                                        Cancel
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-danger" 
                                        @click="submitOverride"
                                        :disabled="!overrideForm.override_reason || overriding"
                                    >
                                        <span v-if="overriding" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-shield-check me-2"></i>
                                        Approve with Override
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Sidebar -->
                <div class="col-lg-4">
                    <!-- Prospect Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>Applicant Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Name:</strong><br>
                                {{ prospect.first_name }} {{ prospect.middle_name }} {{ prospect.last_name }}
                            </p>
                            <p class="mb-2">
                                <strong>Phone:</strong> {{ prospect.phone }}
                            </p>
                            <p class="mb-2">
                                <strong>ID Number:</strong> {{ prospect.id_number }}
                            </p>
                            <p class="mb-2">
                                <strong>Customer Type:</strong><br>
                                <span class="badge bg-info">{{ formatCustomerType(prospect.customer_type) }}</span>
                            </p>
                            <p class="mb-2">
                                <strong>Loan Purpose:</strong><br>
                                {{ formatLoanPurpose(prospect.loan_purpose) }}
                            </p>
                            <p class="mb-2">
                                <strong>Requested Amount:</strong><br>
                                TZS {{ Number(prospect.requested_amount).toLocaleString() }}
                            </p>
                            <p class="mb-0">
                                <strong>Requested Tenure:</strong><br>
                                {{ prospect.requested_tenure }} months
                            </p>
                        </div>
                    </div>

                    <!-- Risk Factors -->
                    <div v-if="assessment.risk_factors && assessment.risk_factors.length > 0" class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-shield-exclamation me-2"></i>Risk Factors Identified
                            </h6>
                        </div>
                        <div class="card-body">
                            <div v-for="(factor, index) in assessment.risk_factors" :key="index" class="mb-3 pb-3" :class="{ 'border-bottom': index < assessment.risk_factors.length - 1 }">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="text-dark mb-1">
                                            <i class="bi bi-exclamation-triangle me-2" :class="getRiskFactorIconClass(factor.weight)"></i>
                                            {{ formatRiskFactor(factor.factor) }}
                                        </h6>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted d-block">Impact Score</small>
                                                <strong>{{ formatRiskValue(factor.factor, factor.value) }}</strong>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">Weight</small>
                                                <div class="progress" style="width: 80px; height: 20px;">
                                                    <div 
                                                        class="progress-bar" 
                                                        :class="getRiskWeightClass(factor.weight)"
                                                        :style="{ width: factor.weight + '%' }"
                                                    >
                                                        {{ factor.weight }}%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-list-check me-2"></i>Next Steps
                            </h6>
                            <div v-if="isEligible || isConditional">
                                <p class="small mb-2">If you proceed with the application, you'll need to provide:</p>
                                <ul class="small">
                                    <li>Complete KYC documents</li>
                                    <li v-if="prospect.customer_type === 'salary'">
                                        Employment verification documents
                                    </li>
                                    <li v-else-if="prospect.customer_type === 'business'">
                                        Business registration & financial statements
                                    </li>
                                    <li v-else>
                                        Employment & business documents
                                    </li>
                                    <li>Property title deed</li>
                                    <li>Property valuation report</li>
                                    <li>Property insurance</li>
                                    <li v-if="prospect.loan_purpose === 'home_construction' || prospect.loan_purpose === 'home_completion'">
                                        Building permit & approved drawings
                                    </li>
                                </ul>
                            </div>
                            <div v-else>
                                <p class="small mb-2">To improve your eligibility:</p>
                                <ul class="small mb-0">
                                    <li>Reduce existing debt obligations</li>
                                    <li>Increase income stability</li>
                                    <li>Consider a longer loan tenure</li>
                                    <li>Increase property value/down payment</li>
                                    <li>Apply with a co-borrower</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    prospect: Object,
    assessment: Object,
    analytics: Object,
});

const page = usePage();
const converting = ref(false);
const showAmendModal = ref(false);
const showOverrideModal = ref(false);
const showConvertModal = ref(false);
const amending = ref(false);
const overriding = ref(false);
const amendForm = ref({
    amount: props.assessment?.final_max_loan || props.prospect?.requested_amount,
    tenure: props.prospect?.requested_tenure,
    reason: ''
});
const overrideForm = ref({
    override_reason: '',
    conditions: '',
    approved_amount: props.prospect?.requested_amount,
    approved_tenure: props.prospect?.requested_tenure
});

const isEligible = computed(() => props.assessment?.system_decision === 'eligible');
const isConditional = computed(() => props.assessment?.system_decision === 'conditional');
const isDeclined = computed(() => props.assessment?.system_decision === 'declined' || props.assessment?.system_decision === 'outside_policy');

// Check if user can amend loan (managers, credit officers, admins)
const canAmendLoan = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    // Check role slugs
    const allowedRoles = [
        'provider-super-admin',
        'institution-admin',
        'credit-manager',
        'credit-officer'
    ];
    
    return allowedRoles.includes(user.role) ||
           user.permissions?.includes('amend_loan_assessment') ||
           user.permissions?.includes('applications.make-decisions');
});

// Check if user can override policy (higher privilege)
const canOverridePolicy = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    const allowedRoles = [
        'provider-super-admin',
        'institution-admin',
        'credit-manager'
    ];
    
    return allowedRoles.includes(user.role) ||
           user.permissions?.includes('applications.approve-overrides');
});

// Calculate estimated installment for amended loan
const estimatedInstallment = computed(() => {
    if (!amendForm.value.amount || !amendForm.value.tenure) return 0;
    const principal = parseFloat(amendForm.value.amount);
    const months = parseInt(amendForm.value.tenure);
    const annualRate = props.prospect?.loan_product?.annual_interest_rate || 18;
    const monthlyRate = annualRate / 100 / 12;
    
    // Calculate reducing balance installment
    const installment = (principal * monthlyRate * Math.pow(1 + monthlyRate, months)) / 
                       (Math.pow(1 + monthlyRate, months) - 1);
    return installment;
});

const getRiskGradeClass = (grade) => {
    const classes = {
        'A': 'badge bg-success',
        'B': 'badge bg-primary',
        'C': 'badge bg-warning',
        'D': 'badge bg-danger',
    };
    return classes[grade] || 'badge bg-secondary';
};

const getRatioClass = (ratio, max) => {
    if (ratio <= max * 0.7) return 'text-success';
    if (ratio <= max) return 'text-warning';
    return 'text-danger';
};

const formatCustomerType = (type) => {
    const types = {
        'salary': 'Salaried Employee',
        'business': 'Self-Employed / Business',
        'mixed': 'Mixed Income'
    };
    return types[type] || type;
};

const formatLoanPurpose = (purpose) => {
    const map = {
        home_purchase: 'Home Purchase',
        home_refinance: 'Home Refinance',
        home_completion: 'Home Completion',
        home_construction: 'Home Construction',
        home_equity_release: 'Home Equity Release',
    };
    return map[purpose] || purpose;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const confirmConversion = () => {
    converting.value = true;
    
    router.post(`/pre-qualify/${props.prospect.id}/convert`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showConvertModal.value = false;
        },
        onError: (errors) => {
            alert('Error: ' + (errors.message || 'Failed to convert prospect'));
        },
        onFinish: () => {
            converting.value = false;
        }
    });
};

const downloadReport = () => {
    window.location.href = `/pre-qualify/${props.prospect.id}/report`;
};

const closeAmendModal = () => {
    showAmendModal.value = false;
    // Reset form
    amendForm.value = {
        amount: props.assessment?.final_max_loan || props.prospect?.requested_amount,
        tenure: props.prospect?.requested_tenure,
        reason: ''
    };
};

const submitAmendment = () => {
    if (!amendForm.value.amount || !amendForm.value.tenure || !amendForm.value.reason) {
        alert('Please fill in all fields');
        return;
    }
    
    amending.value = true;
    
    router.post(`/pre-qualify/${props.prospect.id}/amend-and-reassess`, {
        requested_amount: amendForm.value.amount,
        requested_tenure: amendForm.value.tenure,
        amendment_reason: amendForm.value.reason
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAmendModal.value = false;
            amending.value = false;
        },
        onError: (errors) => {
            amending.value = false;
            alert('Error: ' + (errors.message || 'Failed to re-run assessment'));
        }
    });
};

const closeOverrideModal = () => {
    showOverrideModal.value = false;
    overrideForm.value = {
        override_reason: '',
        conditions: '',
        approved_amount: props.prospect?.requested_amount,
        approved_tenure: props.prospect?.requested_tenure
    };
};

const submitOverride = () => {
    if (!overrideForm.value.override_reason) {
        alert('Please provide justification for the override');
        return;
    }
    
    if (!confirm('Are you sure you want to override the policy decision? This action will be audited.')) {
        return;
    }
    
    overriding.value = true;
    
    router.post(`/pre-qualify/${props.prospect.id}/override-decision`, {
        override_reason: overrideForm.value.override_reason,
        conditions: overrideForm.value.conditions,
        approved_amount: overrideForm.value.approved_amount,
        approved_tenure: overrideForm.value.approved_tenure
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showOverrideModal.value = false;
            overriding.value = false;
        },
        onError: (errors) => {
            overriding.value = false;
            alert('Error: ' + (errors.message || 'Failed to override decision'));
        }
    });
};

const formatConditionTitle = (condition) => {
    const titles = {
        'low_income_stability': 'Low Income Stability',
        'high_cash_flow_volatility': 'High Cash Flow Volatility',
        'insufficient_collateral': 'Insufficient Collateral',
        'high_debt_burden': 'High Debt Burden',
        'low_credit_history': 'Low Credit History',
    };
    return titles[condition] || condition.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getSeverityBadgeClass = (severity) => {
    const classes = {
        'high': 'bg-danger',
        'medium': 'bg-warning',
        'low': 'bg-info',
    };
    return classes[severity] || 'bg-secondary';
};

const formatPolicyRule = (rule) => {
    const rules = {
        'max_dti_exceeded': 'Debt-to-Income Ratio Exceeded',
        'max_dsr_exceeded': 'Debt Service Ratio Exceeded',
        'insufficient_surplus': 'Insufficient Monthly Surplus',
        'ltv_exceeded': 'Loan-to-Value Ratio Exceeded',
        'min_income_not_met': 'Minimum Income Not Met',
        'max_tenure_exceeded': 'Maximum Tenure Exceeded',
    };
    return rules[rule] || rule.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatThreshold = (rule, value) => {
    // Format based on rule type
    if (rule.includes('dti') || rule.includes('dsr') || rule.includes('ltv')) {
        return `${parseFloat(value).toFixed(2)}%`;
    } else if (rule.includes('surplus') || rule.includes('income') || rule.includes('amount')) {
        return `TZS ${Number(value).toLocaleString()}`;
    } else if (rule.includes('tenure')) {
        return `${value} months`;
    }
    return value;
};

const formatRiskFactor = (factor) => {
    const factors = {
        'high_dti': 'High Debt-to-Income Ratio',
        'unstable_income': 'Unstable Income Pattern',
        'high_volatility': 'High Cash Flow Volatility',
        'low_income': 'Low Monthly Income',
        'irregular_deposits': 'Irregular Deposit Pattern',
        'high_bounce_rate': 'High Transaction Bounce Rate',
        'low_balance': 'Low Account Balance',
        'new_account': 'New Bank Account',
    };
    return factors[factor] || factor.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatRiskValue = (factor, value) => {
    // Format value based on factor type
    if (factor.includes('dti') || factor.includes('dsr') || factor.includes('rate') || factor.includes('volatility')) {
        return `${parseFloat(value).toFixed(2)}%`;
    } else if (factor.includes('score') || factor.includes('stability')) {
        return `${parseFloat(value).toFixed(2)} / 100`;
    }
    return value;
};

const getRiskFactorIconClass = (weight) => {
    if (weight >= 30) return 'text-danger';
    if (weight >= 20) return 'text-warning';
    return 'text-info';
};

const getRiskWeightClass = (weight) => {
    if (weight >= 30) return 'bg-danger';
    if (weight >= 20) return 'bg-warning';
    return 'bg-info';
};

</script>

<style scoped>
.card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

.text-purple {
    color: #8b5cf6 !important;
}

@media (max-width: 768px) {
    .border-end {
        border-right: none !important;
    }
}
</style>
