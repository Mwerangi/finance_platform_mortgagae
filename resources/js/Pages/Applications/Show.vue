<template>
  <AppLayout :breadcrumb="[
    { label: 'Applications', href: '/applications' },
    { label: application.application_number }
  ]">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h2 class="mb-1">Application {{ application.application_number }}</h2>
            <div class="d-flex align-items-center gap-2 mt-2">
              <Badge :variant="getStatusVariant(application.status)">
                {{ formatStatus(application.status) }}
              </Badge>
              <span class="text-muted">•</span>
              <span class="text-muted">Created {{ formatDate(application.created_at) }}</span>
            </div>
          </div>
          <div class="d-flex gap-2">
            <Link
              v-if="application.status === 'draft'"
              :href="`/applications/${application.id}/edit`"
              class="btn btn-warning"
            >
              <i class="bi bi-pencil me-1"></i>Edit
            </Link>
            <button
              v-if="application.status === 'submitted'"
              @click="startReview"
              class="btn btn-info"
            >
              <i class="bi bi-play-circle me-1"></i>Start Review
            </button>
            <button
              v-if="application.status === 'under_review' && canApprove"
              @click="approveApplication"
              class="btn btn-success"
            >
              <i class="bi bi-check-circle me-1"></i>Approve
            </button>
            <button
              v-if="application.status === 'under_review' && canApprove"
              @click="rejectApplication"
              class="btn btn-danger"
            >
              <i class="bi bi-x-circle me-1"></i>Reject
            </button>
            <Link
              v-if="application.status === 'approved' && !application.latestLoan"
              :href="`/applications/${application.id}/disburse`"
              class="btn btn-success"
            >
              <i class="bi bi-cash-stack me-1"></i>Disburse Loan
            </Link>
            <Link
              v-if="application.latestLoan"
              :href="`/loans/${application.latestLoan.id}`"
              class="btn btn-outline-primary"
            >
              <i class="bi bi-eye me-1"></i>View Loan
            </Link>
          </div>
        </div>

        <!-- KYC Warning Banner -->
        <div v-if="application.customer && !application.customer.kyc_verified" class="alert alert-warning d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">Customer KYC Not Verified</h5>
            <p class="mb-0">Please complete KYC verification before proceeding with this application.</p>
          </div>
          <Link 
            :href="`/customers/${application.customer.id}`"
            class="btn btn-warning ms-3"
          >
            <i class="bi bi-person-check me-1"></i>Complete KYC
          </Link>
        </div>

        <div class="row g-4">
          <!-- Left Column -->
          <div class="col-lg-7">
            <!-- Customer Information -->
            <Card header="Customer Information" class="mb-4">
              <div class="d-flex align-items-center mb-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                  <span class="fw-bold fs-4">{{ getInitials(application.customer) }}</span>
                </div>
                <div>
                  <h5 class="mb-0">{{ application.customer.full_name }}</h5>
                  <div class="text-muted">
                    <small><code>{{ application.customer.customer_code }}</code></small>
                    <span class="mx-2">•</span>
                    <Badge :variant="application.customer.kyc_verified ? 'success' : 'warning'">
                      {{ application.customer.kyc_verified ? 'KYC Verified' : 'KYC Pending' }}
                    </Badge>
                  </div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Phone</label>
                  <div>{{ application.customer.phone_primary }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Email</label>
                  <div>{{ application.customer.email || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Customer Type</label>
                  <div class="text-capitalize">{{ application.customer.customer_type }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">National ID</label>
                  <div>{{ application.customer.national_id }}</div>
                </div>
              </div>
            </Card>

            <!-- Loan Product Information -->
            <Card header="Loan Product" class="mb-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="mb-1">{{ application.loan_product.name }}</h5>
                  <div class="text-muted"><code>{{ application.loan_product.code }}</code></div>
                </div>
                <Badge :variant="application.loan_product.status === 'active' ? 'success' : 'secondary'">
                  {{ application.loan_product.status }}
                </Badge>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Interest Rate</label>
                  <div class="fw-bold">{{ application.loan_product.annual_interest_rate }}%</div>
                  <small class="text-muted">{{ formatInterestModel(application.loan_product.interest_model) }}</small>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Allowed Tenure</label>
                  <div>{{ application.loan_product.min_tenure_months }} - {{ application.loan_product.max_tenure_months }} months</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Loan Amount Range</label>
                  <div>{{ formatCurrency(application.loan_product.min_loan_amount) }} - {{ formatCurrency(application.loan_product.max_loan_amount) }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Max LTV</label>
                  <div>{{ application.loan_product.max_ltv_percentage }}%</div>
                </div>
              </div>
            </Card>

            <!-- Property Information -->
            <Card header="Property Information" class="mb-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Property Type</label>
                  <div>{{ application.property_type || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Property Value</label>
                  <div class="fw-bold">{{ application.property_value ? formatCurrency(application.property_value) : 'N/A' }}</div>
                </div>
                <div class="col-12">
                  <label class="text-muted small d-block mb-1">Property Address</label>
                  <div>{{ application.property_address || 'N/A' }}</div>
                </div>
                <div v-if="application.property_value" class="col-12">
                  <label class="text-muted small d-block mb-1">Loan-to-Value (LTV) Ratio</label>
                  <div class="fw-bold">{{ calculateLTV(application) }}%</div>
                </div>
              </div>
            </Card>

            <!-- Notes -->
            <Card v-if="application.notes" header="Notes" class="mb-4">
              <p class="mb-0">{{ application.notes }}</p>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="col-lg-5">
            <!-- Application Summary -->
            <Card header="Application Summary" class="mb-4">
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Requested Amount</label>
                <h3 class="mb-0">{{ formatCurrency(application.requested_amount) }}</h3>
              </div>
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Requested Tenure</label>
                <h4 class="mb-0">{{ application.requested_tenure_months }} months</h4>
              </div>
              <div>
                <label class="text-muted small d-block mb-1">Status</label>
                <Badge :variant="getStatusVariant(application.status)" class="fs-6">
                  {{ formatStatus(application.status) }}
                </Badge>
              </div>
            </Card>

            <!-- Timeline -->
            <Card header="Application Timeline" class="mb-4">
              <div class="timeline">
                <div v-if="application.created_at" class="timeline-item">
                  <div class="timeline-marker bg-primary"></div>
                  <div class="timeline-content">
                    <small class="text-muted">Created</small>
                    <div>{{ formatDateTime(application.created_at) }}</div>
                    <small v-if="application.creator">by {{ application.creator.name }}</small>
                  </div>
                </div>
                <div v-if="application.submitted_at" class="timeline-item">
                  <div class="timeline-marker bg-info"></div>
                  <div class="timeline-content">
                    <small class="text-muted">Submitted</small>
                    <div>{{ formatDateTime(application.submitted_at) }}</div>
                  </div>
                </div>
                <div v-if="application.reviewed_at" class="timeline-item">
                  <div class="timeline-marker bg-warning"></div>
                  <div class="timeline-content">
                    <small class="text-muted">Under Review</small>
                    <div>{{ formatDateTime(application.reviewed_at) }}</div>
                    <small v-if="application.reviewer">by {{ application.reviewer.name }}</small>
                  </div>
                </div>
                <div v-if="application.approved_at" class="timeline-item">
                  <div class="timeline-marker bg-success"></div>
                  <div class="timeline-content">
                    <small class="text-muted">Approved</small>
                    <div>{{ formatDateTime(application.approved_at) }}</div>
                    <small v-if="application.approver">by {{ application.approver.name }}</small>
                  </div>
                </div>
                <div v-if="application.rejected_at" class="timeline-item">
                  <div class="timeline-marker bg-danger"></div>
                  <div class="timeline-content">
                    <small class="text-muted">Rejected</small>
                    <div>{{ formatDateTime(application.rejected_at) }}</div>
                    <small v-if="application.reviewer">by {{ application.reviewer.name }}</small>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Underwriting Decision -->
            <Card v-if="latestUnderwriting" header="Underwriting Decision" class="mb-4">
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Decision</label>
                <Badge :variant="getDecisionVariant(latestUnderwriting.decision_status)">
                  {{ formatStatus(latestUnderwriting.decision_status) }}
                </Badge>
              </div>
              <div v-if="latestUnderwriting.risk_grade" class="mb-3">
                <label class="text-muted small d-block mb-1">Risk Grade</label>
                <div class="fw-bold">{{ latestUnderwriting.risk_grade }}</div>
              </div>
              <div v-if="latestUnderwriting.recommended_amount">
                <label class="text-muted small d-block mb-1">Recommended Amount</label>
                <div class="fw-bold">{{ formatCurrency(latestUnderwriting.recommended_amount) }}</div>
              </div>
            </Card>

            <!-- Bank Statement -->
            <Card header="Bank Statement" class="mb-4">
              <!-- Display uploaded statements -->
              <div v-if="bankStatementImports && bankStatementImports.length > 0" class="mb-3">
                <div 
                  v-for="statement in bankStatementImports" 
                  :key="statement.id"
                  class="border rounded p-3 mb-2"
                >
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <div class="fw-bold">{{ statement.file_name }}</div>
                      <small class="text-muted">{{ statement.bank_name || 'Bank statement' }}</small>
                    </div>
                    <Badge :variant="getStatementStatusVariant(statement.import_status)">
                      {{ formatStatementStatus(statement.import_status) }}
                    </Badge>
                  </div>
                  <div class="small text-muted">
                    <div>Uploaded: {{ formatDateTime(statement.created_at) }}</div>
                    <div v-if="statement.statement_start_date && statement.statement_end_date">
                      Period: {{ formatDate(statement.statement_start_date) }} - {{ formatDate(statement.statement_end_date) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Upload new statement -->
              <div v-if="!application.status !== 'closed' && !application.status !== 'rejected'">
                <button 
                  class="btn btn-sm btn-outline-primary w-100" 
                  @click="showUploadModal = true"
                >
                  <i class="bi bi-upload me-1"></i>
                  {{ bankStatementImports && bankStatementImports.length > 0 ? 'Upload Another Statement' : 'Upload Bank Statement' }}
                </button>
              </div>
            </Card>
          </div>
        </div>

        <!-- Eligibility Assessment Report - Full Width -->
        <div class="row" v-if="latestEligibility">
          <div class="col-12">
            <Card header="Eligibility Assessment" class="mb-4">
              <div class="alert mb-3" :class="{
                'alert-success': latestEligibility.system_decision === 'approved',
                'alert-danger': latestEligibility.system_decision === 'rejected',
                'alert-warning': latestEligibility.system_decision === 'conditional'
              }">
                <div class="d-flex align-items-center">
                  <i class="bi fs-4 me-2" :class="{
                    'bi-check-circle-fill': latestEligibility.system_decision === 'approved',
                    'bi-x-circle-fill': latestEligibility.system_decision === 'rejected',
                    'bi-exclamation-triangle-fill': latestEligibility.system_decision === 'conditional'
                  }"></i>
                  <div>
                    <strong>System Decision: {{ latestEligibility.system_decision?.toUpperCase() }}</strong>
                    <div v-if="latestEligibility.decision_reason" class="small">{{ latestEligibility.decision_reason }}</div>
                  </div>
                </div>
              </div>

              <!-- Key Metrics -->
              <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                  <div class="border rounded p-3 h-100">
                    <label class="text-muted small d-block mb-1">Monthly Income</label>
                    <div class="fw-bold fs-5">{{ formatCurrency(latestEligibility.net_monthly_income) }}</div>
                    <small class="text-muted">Net Monthly</small>
                  </div>
                </div>
                <div class="col-md-6 col-lg-3">
                  <div class="border rounded p-3 h-100">
                    <label class="text-muted small d-block mb-1">Monthly Debt</label>
                    <div class="fw-bold fs-5">{{ formatCurrency(latestEligibility.total_monthly_debt) }}</div>
                    <small class="text-muted">{{ latestEligibility.detected_debt_count }} debts detected</small>
                  </div>
                </div>
                <div class="col-md-4 col-lg-2">
                  <div class="border rounded p-3 h-100">
                    <label class="text-muted small d-block mb-1">DTI Ratio</label>
                    <div class="fw-bold fs-5" :class="{
                      'text-success': latestEligibility.dti_ratio <= 40,
                      'text-warning': latestEligibility.dti_ratio > 40 && latestEligibility.dti_ratio <= 50,
                      'text-danger': latestEligibility.dti_ratio > 50
                    }">{{ latestEligibility.dti_ratio }}%</div>
                  </div>
                </div>
                <div class="col-md-4 col-lg-2">
                  <div class="border rounded p-3 h-100">
                    <label class="text-muted small d-block mb-1">DSR Ratio</label>
                    <div class="fw-bold fs-5" :class="{
                      'text-success': latestEligibility.dsr_ratio <= 40,
                      'text-warning': latestEligibility.dsr_ratio > 40 && latestEligibility.dsr_ratio <= 50,
                      'text-danger': latestEligibility.dsr_ratio > 50
                    }">{{ latestEligibility.dsr_ratio }}%</div>
                  </div>
                </div>
                <div class="col-md-4 col-lg-2">
                  <div class="border rounded p-3 h-100">
                    <label class="text-muted small d-block mb-1">LTV Ratio</label>
                    <div class="fw-bold fs-5" :class="{
                      'text-success': latestEligibility.ltv_ratio <= 80,
                      'text-warning': latestEligibility.ltv_ratio > 80 && latestEligibility.ltv_ratio <= 90,
                      'text-danger': latestEligibility.ltv_ratio > 90
                    }">{{ latestEligibility.ltv_ratio }}%</div>
                  </div>
                </div>
              </div>

              <!-- Loan Affordability -->
              <div class="mb-4">
                <h6 class="mb-3">Loan Affordability Analysis</h6>
                <div class="row g-3">
                  <div class="col-md-6 col-lg-3">
                    <label class="text-muted small d-block mb-1">Proposed Monthly Installment</label>
                    <div class="fw-bold">{{ formatCurrency(latestEligibility.proposed_installment) }}</div>
                  </div>
                  <div class="col-md-6 col-lg-3">
                    <label class="text-muted small d-block mb-1">Net Surplus After Loan</label>
                    <div class="fw-bold" :class="{
                      'text-success': latestEligibility.net_surplus_after_loan > 0,
                      'text-danger': latestEligibility.net_surplus_after_loan <= 0
                    }">{{ formatCurrency(latestEligibility.net_surplus_after_loan) }}</div>
                  </div>
                  <div class="col-md-6 col-lg-3">
                    <label class="text-muted small d-block mb-1">Max Loan from Affordability</label>
                    <div class="fw-bold">{{ formatCurrency(latestEligibility.max_loan_from_affordability) }}</div>
                  </div>
                  <div class="col-md-6 col-lg-3">
                    <label class="text-muted small d-block mb-1">Max Loan from LTV</label>
                    <div class="fw-bold">{{ formatCurrency(latestEligibility.max_loan_from_ltv) }}</div>
                  </div>
                  <div class="col-12">
                    <div class="alert alert-info mb-0">
                      <strong>Final Maximum Loan:</strong> {{ formatCurrency(latestEligibility.final_max_loan) }}
                      <span v-if="latestEligibility.optimal_tenure_months"> • Optimal Tenure: {{ latestEligibility.optimal_tenure_months }} months</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Risk Assessment -->
              <div class="mb-4" v-if="latestEligibility.risk_grade || latestEligibility.risk_score">
                <h6 class="mb-3">Risk Assessment</h6>
                <div class="row g-3">
                  <div class="col-md-6" v-if="latestEligibility.risk_grade">
                    <label class="text-muted small d-block mb-1">Risk Grade</label>
                    <Badge :variant="getRiskGradeVariant(latestEligibility.risk_grade)" class="fs-6">
                      {{ latestEligibility.risk_grade }}
                    </Badge>
                  </div>
                  <div class="col-md-6" v-if="latestEligibility.risk_score">
                    <label class="text-muted small d-block mb-1">Risk Score</label>
                    <div class="fw-bold">{{ latestEligibility.risk_score }}</div>
                  </div>
                  <div class="col-12" v-if="latestEligibility.risk_factors && latestEligibility.risk_factors.length">
                    <label class="text-muted small d-block mb-2">Risk Factors:</label>
                    <div class="d-flex flex-wrap gap-2">
                      <Badge v-for="(factor, index) in latestEligibility.risk_factors" :key="index" variant="warning">
                        {{ formatRiskFactor(factor) }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Policy Breaches -->
              <div class="mb-4" v-if="latestEligibility.policy_breaches && latestEligibility.policy_breaches.length">
                <h6 class="mb-3">
                  <i class="bi bi-shield-exclamation me-2"></i>Policy Breaches
                </h6>
                <div class="alert alert-danger">
                  <div v-for="(breach, index) in latestEligibility.policy_breaches" :key="index" 
                       class="mb-3 pb-3" 
                       :class="{ 'border-bottom': index < latestEligibility.policy_breaches.length - 1 }">
                    <div class="row align-items-center">
                      <div class="col-md-5">
                        <h6 class="text-danger mb-1">
                          <i class="bi bi-x-circle me-1"></i>{{ formatPolicyRule(breach.rule) }}
                        </h6>
                      </div>
                      <div class="col-md-7" v-if="breach.threshold && breach.actual">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <small class="text-muted d-block">Required Threshold</small>
                            <strong>{{ formatThreshold(breach.rule, breach.threshold) }}</strong>
                          </div>
                          <div class="text-end">
                            <small class="text-muted d-block">Actual Value</small>
                            <strong class="text-danger">{{ formatThreshold(breach.rule, breach.actual) }}</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Conditions -->
              <div class="mb-4" v-if="latestEligibility.conditions && latestEligibility.conditions.length">
                <h6 class="mb-3">
                  <i class="bi bi-info-circle me-2"></i>Approval Conditions
                </h6>
                <div class="alert alert-warning">
                  <div v-for="(condition, index) in latestEligibility.conditions" :key="index" 
                       class="mb-3 pb-3" 
                       :class="{ 'border-bottom': index < latestEligibility.conditions.length - 1 }">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h6 class="text-dark mb-0">
                        <i class="bi bi-dot"></i>{{ formatConditionTitle(condition.condition) }}
                      </h6>
                      <Badge v-if="condition.severity" :variant="getSeverityBadgeClass(condition.severity)">
                        {{ condition.severity.toUpperCase() }}
                      </Badge>
                    </div>
                    <p v-if="condition.recommendation" class="text-muted mb-0 ms-3">
                      <i class="bi bi-arrow-right-short"></i>
                      <strong>Recommendation:</strong> {{ condition.recommendation }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Assessment Info -->
              <div class="border-top pt-3 mt-3">
                <div class="row g-2 small text-muted">
                  <div class="col-md-6 col-lg-3" v-if="latestEligibility.assessed_at">
                    <i class="bi bi-calendar-event me-1"></i>
                    Assessed: {{ formatDateTime(latestEligibility.assessed_at) }}
                  </div>
                  <div class="col-md-6 col-lg-3" v-if="latestEligibility.assessor">
                    <i class="bi bi-person me-1"></i>
                    By: {{ latestEligibility.assessor.name }}
                  </div>
                  <div class="col-md-6 col-lg-3" v-if="latestEligibility.income_classification">
                    <i class="bi bi-briefcase me-1"></i>
                    Income Type: {{ latestEligibility.income_classification }}
                  </div>
                  <div class="col-md-6 col-lg-3" v-if="latestEligibility.assessment_version">
                    <i class="bi bi-code-square me-1"></i>
                    Version: {{ latestEligibility.assessment_version }}
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <div 
      class="modal fade" 
      :class="{ 'show d-block': showApproveModal }" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
      v-if="showApproveModal"
      @click.self="showApproveModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle me-2"></i>Approve Application
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showApproveModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info mb-3">
              <div><strong>Application:</strong> {{ application.application_number }}</div>
              <div><strong>Customer:</strong> {{ application.customer.full_name }}</div>
              <div><strong>Amount:</strong> {{ formatCurrency(application.requested_amount) }}</div>
            </div>
            
            <p class="mb-3">Are you sure you want to approve this loan application?</p>
            
            <div class="bg-light p-3 rounded mb-3">
              <h6 class="mb-2">This will:</h6>
              <ul class="mb-0">
                <li>Mark the application as <strong class="text-success">Approved</strong></li>
                <li>Allow the application to proceed to loan disbursement</li>
                <li>Record your approval decision in the system</li>
                <li>Notify relevant parties of the approval</li>
              </ul>
            </div>
            
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Note:</strong> Please ensure all underwriting checks and KYC verification are complete before approving.
            </div>
          </div>
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="showApproveModal = false"
              :disabled="approving"
            >
              <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-success" 
              @click="confirmApprove"
              :disabled="approving"
            >
              <span v-if="approving" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-check-circle me-1"></i>
              {{ approving ? 'Approving...' : 'Yes, Approve Application' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div 
      class="modal fade" 
      :class="{ 'show d-block': showRejectModal }" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
      v-if="showRejectModal"
      @click.self="showRejectModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">
              <i class="bi bi-x-circle me-2"></i>Reject Application
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showRejectModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info mb-3">
              <div><strong>Application:</strong> {{ application.application_number }}</div>
              <div><strong>Customer:</strong> {{ application.customer.full_name }}</div>
              <div><strong>Amount:</strong> {{ formatCurrency(application.requested_amount) }}</div>
            </div>
            
            <p class="mb-3">Please provide a detailed reason for rejecting this application:</p>
            
            <div class="mb-3">
              <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
              <textarea 
                v-model="rejectForm.notes"
                class="form-control" 
                :class="{ 'is-invalid': rejectErrors.notes }"
                rows="4"
                placeholder="Explain why this application is being rejected (minimum 10 characters)..."
                required
              ></textarea>
              <small class="text-muted">Minimum 10 characters required</small>
              <div v-if="rejectErrors.notes" class="invalid-feedback">
                {{ rejectErrors.notes[0] }}
              </div>
            </div>
            
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Warning:</strong> This action will reject the application and the customer will need to reapply.
            </div>
          </div>
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="showRejectModal = false"
              :disabled="rejecting"
            >
              <i class="bi bi-arrow-left me-1"></i>Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="confirmReject"
              :disabled="rejecting || !rejectForm.notes || rejectForm.notes.length < 10"
            >
              <span v-if="rejecting" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-x-circle me-1"></i>
              {{ rejecting ? 'Rejecting...' : 'Reject Application' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bank Statement Upload Modal -->
    <div v-if="showUploadModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Upload Bank Statement</h5>
            <button type="button" class="btn-close" @click="closeUploadModal"></button>
          </div>
          <form @submit.prevent="submitBankStatement">
            <div class="modal-body">
              <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                Upload bank statement in Excel (.xlsx, .xls) or CSV format
              </div>

              <div class="mb-3">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input
                  id="bank_name"
                  v-model="uploadForm.bank_name"
                  type="text"
                  class="form-control"
                  placeholder="e.g., CRDB Bank, NMB Bank"
                />
              </div>

              <div class="mb-3">
                <label for="account_number" class="form-label">Account Number</label>
                <input
                  id="account_number"
                  v-model="uploadForm.account_number"
                  type="text"
                  class="form-control"
                />
              </div>

              <div class="mb-3">
                <label for="file" class="form-label">Bank Statement File <span class="text-danger">*</span></label>
                <input
                  id="file"
                  ref="fileInput"
                  type="file"
                  class="form-control"
                  accept=".xlsx,.xls,.csv"
                  @change="handleFileChange"
                  required
                />
                <small class="text-muted">Max file size: 10MB</small>
              </div>

              <div v-if="selectedFile" class="alert alert-success">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                Selected: {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeUploadModal" :disabled="uploading">
                Cancel
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="uploading || !selectedFile"
              >
                <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-upload me-1"></i>
                {{ uploading ? 'Uploading...' : 'Upload' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  application: Object,
  latestUnderwriting: Object,
  latestEligibility: Object,
  canApprove: Boolean,
  bankStatementImports: Array
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showUploadModal = ref(false);
const approving = ref(false);
const rejecting = ref(false);
const uploading = ref(false);
const rejectForm = ref({ notes: '' });
const rejectErrors = ref({});
const selectedFile = ref(null);
const fileInput = ref(null);
const uploadForm = ref({
  bank_name: '',
  account_number: '',
  file: null
});

const getInitials = (customer) => {
  const first = customer.first_name?.[0] || '';
  const last = customer.last_name?.[0] || '';
  return (first + last).toUpperCase();
};

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    submitted: 'primary',
    under_review: 'warning',
    approved: 'success',
    rejected: 'danger',
    disbursed: 'info'
  };
  return variants[status] || 'secondary';
};

const getDecisionVariant = (decision) => {
  const variants = {
    pending_review: 'secondary',
    under_review: 'warning',
    approved: 'success',
    rejected: 'danger',
    pending_approval: 'info'
  };
  return variants[decision] || 'secondary';
};

const getRiskGradeVariant = (grade) => {
  const lowerGrade = grade?.toLowerCase() || '';
  if (lowerGrade.includes('a') || lowerGrade.includes('low')) return 'success';
  if (lowerGrade.includes('b') || lowerGrade.includes('medium')) return 'warning';
  if (lowerGrade.includes('c') || lowerGrade.includes('high')) return 'danger';
  return 'secondary';
};

const formatStatus = (status) => {
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const formatInterestModel = (model) => {
  return model === 'reducing_balance' ? 'Reducing Balance' : 'Flat Rate';
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0
  }).format(amount);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Format risk factor from snake_case to human readable
const formatRiskFactor = (factor) => {
  // Handle both string and object formats
  const factorKey = typeof factor === 'string' ? factor : (factor?.factor || factor?.name || factor?.type || '');
  
  const factors = {
    'high_dti': 'High Debt-to-Income Ratio',
    'high_dsr': 'High Debt Service Ratio',
    'unstable_income': 'Unstable Income Pattern',
    'high_volatility': 'High Cash Flow Volatility',
    'high_cash_flow_volatility': 'High Cash Flow Volatility',
    'low_income': 'Low Monthly Income',
    'irregular_deposits': 'Irregular Deposit Pattern',
    'high_bounce_rate': 'High Transaction Bounce Rate',
    'bounced_transactions_detected': 'Bounced Transactions Detected',
    'low_balance': 'Low Account Balance',
    'negative_balance_days': 'Negative Balance Days Detected',
    'new_account': 'New Bank Account',
    'insufficient_transaction_history': 'Insufficient Transaction History',
    'high_loan_to_value': 'High Loan-to-Value Ratio',
    'insufficient_collateral': 'Insufficient Collateral',
  };
  
  if (!factorKey) return 'Unknown Factor';
  return factors[factorKey] || factorKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Format policy rule from snake_case to human readable
const formatPolicyRule = (rule) => {
  // Handle both string and object formats
  const ruleKey = typeof rule === 'string' ? rule : (rule?.rule || rule?.name || '');
  
  const rules = {
    'max_dti_exceeded': 'Debt-to-Income Ratio Exceeded',
    'max_dsr_exceeded': 'Debt Service Ratio Exceeded',
    'insufficient_surplus': 'Insufficient Monthly Surplus',
    'ltv_exceeded': 'Loan-to-Value Ratio Exceeded',
    'min_income_not_met': 'Minimum Income Requirement Not Met',
    'max_tenure_exceeded': 'Maximum Tenure Exceeded',
    'high_risk_grade': 'High Risk Grade',
    'negative_cash_flow': 'Negative Cash Flow Detected',
  };
  
  if (!ruleKey) return 'Unknown Rule';
  return rules[ruleKey] || ruleKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Format condition title from snake_case to human readable
const formatConditionTitle = (condition) => {
  // Handle both string and object formats
  const conditionKey = typeof condition === 'string' ? condition : (condition?.condition || condition?.name || '');
  
  const titles = {
    'low_income_stability': 'Low Income Stability',
    'high_cash_flow_volatility': 'High Cash Flow Volatility',
    'insufficient_collateral': 'Insufficient Collateral',
    'high_debt_burden': 'High Debt Burden',
    'low_credit_history': 'Low Credit History',
    'bounced_transactions_detected': 'Bounced Transactions Detected',
    'require_guarantor': 'Guarantor Required',
    'additional_documentation_required': 'Additional Documentation Required',
  };
  
  if (!conditionKey) return 'Unknown Condition';
  return titles[conditionKey] || conditionKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Format threshold values based on rule type
const formatThreshold = (rule, value) => {
  if (rule.includes('dti') || rule.includes('dsr') || rule.includes('ltv')) {
    return `${parseFloat(value).toFixed(2)}%`;
  } else if (rule.includes('surplus') || rule.includes('income') || rule.includes('amount')) {
    return `TZS ${Number(value).toLocaleString()}`;
  } else if (rule.includes('tenure')) {
    return `${value} months`;
  }
  return value;
};

// Get severity badge class
const getSeverityBadgeClass = (severity) => {
  const classes = {
    'high': 'danger',
    'medium': 'warning',
    'low': 'info',
  };
  return classes[severity] || 'secondary';
};

const formatDateTime = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const calculateLTV = (application) => {
  if (!application.property_value || application.property_value == 0) return 'N/A';
  return ((application.requested_amount / application.property_value) * 100).toFixed(2);
};

const startReview = () => {
  if (confirm('Start reviewing this application? This will mark it as under review.')) {
    router.post(`/applications/${props.application.id}/start-review`);
}
};

const approveApplication = () => {
  showApproveModal.value = true;
};

const confirmApprove = () => {
  approving.value = true;
  router.post(`/applications/${props.application.id}/approve`, {}, {
    onFinish: () => {
      approving.value = false;
      showApproveModal.value = false;
    }
  });
};

const rejectApplication = () => {
  rejectForm.value.notes = '';
  rejectErrors.value = {};
  showRejectModal.value = true;
};

const confirmReject = () => {
  if (!rejectForm.value.notes || rejectForm.value.notes.length < 10) {
    rejectErrors.value = {
      notes: ['Rejection reason must be at least 10 characters']
    };
    return;
  }
  
  rejecting.value = true;
  rejectErrors.value = {};
  
  router.post(`/applications/${props.application.id}/reject`, rejectForm.value, {
    onError: (errors) => {
      rejectErrors.value = errors;
    },
    onFinish: () => {
      rejecting.value = false;
    },
    onSuccess: () => {
      showRejectModal.value = false;
      rejectForm.value.notes = '';
    }
  });
};

const closeUploadModal = () => {
  showUploadModal.value = false;
  selectedFile.value = null;
  uploadForm.value = {
    bank_name: '',
    account_number: '',
    file: null
  };
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedFile.value = file;
    uploadForm.value.file = file;
  }
};

const submitBankStatement = () => {
  if (!selectedFile.value) {
    alert('Please select a file to upload');
    return;
  }

  uploading.value = true;

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('bank_name', uploadForm.value.bank_name);
  formData.append('account_number', uploadForm.value.account_number);

  router.post(`/applications/${props.application.id}/upload-statement`, formData, {
    onSuccess: () => {
      closeUploadModal();
    },
    onError: (errors) => {
      alert('Error uploading file: ' + (errors.file || 'Unknown error'));
    },
    onFinish: () => {
      uploading.value = false;
    },
    forceFormData: true,
  });
};

const formatStatementStatus = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'processing': 'Processing',
    'completed': 'Completed',
    'failed': 'Failed'
  };
  return statusMap[status] || status;
};

const getStatementStatusVariant = (status) => {
  const variantMap = {
    'pending': 'secondary',
    'processing': 'warning',
    'completed': 'success',
    'failed': 'danger'
  };
  return variantMap[status] || 'secondary';
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<style scoped>
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline-item {
  position: relative;
  padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: -24px;
  top: 10px;
  bottom: -10px;
  width: 2px;
  background: #ddd;
}

.timeline-marker {
  position: absolute;
  left: -30px;
  top: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.timeline-content {
  padding-left: 0;
}
</style>
