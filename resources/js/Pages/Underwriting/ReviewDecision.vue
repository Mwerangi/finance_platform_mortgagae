<template>
  <AppLayout :breadcrumb="`Underwriting / Review / ${decision.decision_number}`">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Review Decision: {{ decision.decision_number }}</h2>
        <p class="text-muted mb-0">Application: {{ decision.application?.application_number }}</p>
      </div>
      <div class="d-flex gap-2">
        <Link href="/underwriting/pending-reviews" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Back to Queue
        </Link>
      </div>
    </div>

    <!-- Decision Status Alert -->
    <div v-if="decision.decision_status === 'under_review' && decision.reviewed_by !== $page.props.auth.user.id" class="alert alert-warning mb-4">
      <i class="bi bi-exclamation-triangle me-2"></i>
      This decision is currently being reviewed by {{ decision.reviewer?.name }}
    </div>

    <!-- Top Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <Card>
          <div class="text-center">
            <h6 class="text-muted small mb-2">Requested Amount</h6>
            <h3 class="mb-0">{{ formatCurrency(decision.requested_amount) }}</h3>
            <p class="text-muted small mb-0">{{ decision.requested_tenure_months }} months</p>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="text-center">
            <h6 class="text-muted small mb-2">System Recommendation</h6>
            <Badge :variant="getSystemDecisionVariant(decision.eligibility_assessment?.system_decision)">
              {{ formatSystemDecision(decision.eligibility_assessment?.system_decision) }}
            </Badge>
            <p class="text-muted small mb-0 mt-2">
              Max: {{ formatCurrency(decision.eligibility_assessment?.max_loan_amount) }}
            </p>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="text-center">
            <h6 class="text-muted small mb-2">Risk Grade</h6>
            <h3 class="mb-0" :class="getRiskGradeClass(decision.eligibility_assessment?.risk_grade)">
              {{ decision.eligibility_assessment?.risk_grade || 'N/A' }}
            </h3>
            <p class="text-muted small mb-0">{{ decision.eligibility_assessment?.risk_score || 0 }}/100</p>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="text-center">
            <h6 class="text-muted small mb-2">DTI / DSR</h6>
            <h3 class="mb-0">{{ formatPercentage(decision.eligibility_assessment?.dti_ratio) }}</h3>
            <p class="text-muted small mb-0">
              DSR: {{ formatPercentage(decision.eligibility_assessment?.dsr_ratio) }}
            </p>
          </div>
        </Card>
      </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
      <!-- Left Column: Customer & Application Info -->
      <div class="col-lg-7">
        <!-- Customer Information -->
        <Card class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-person-circle me-2"></i>Customer Information
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Customer Name</small>
                <div class="fw-bold">{{ decision.application?.customer?.full_name }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Customer Code</small>
                <div>
                  <code>{{ decision.application?.customer?.customer_code }}</code>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Customer Type</small>
                <div>
                  <Badge variant="info">{{ formatCustomerType(decision.application?.customer?.customer_type) }}</Badge>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Phone</small>
                <div>{{ decision.application?.customer?.phone }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Email</small>
                <div>{{ decision.application?.customer?.email || 'N/A' }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">National ID</small>
                <div>{{ decision.application?.customer?.national_id }}</div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Application Details -->
        <Card class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-file-earmark-text me-2"></i>Application Details
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Loan Product</small>
                <div class="fw-bold">{{ decision.application?.loan_product?.name }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Interest Rate</small>
                <div>{{ decision.application?.loan_product?.annual_rate }}% per annum</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Interest Method</small>
                <div>
                  <Badge variant="primary">{{ formatInterestMethod(decision.application?.loan_product?.interest_method) }}</Badge>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <small class="text-muted">Submitted Date</small>
                <div>{{ formatDate(decision.application?.submitted_at) }}</div>
              </div>
            </div>
            <div v-if="decision.application?.property_value" class="col-12">
              <div class="bg-light p-3 rounded">
                <h6 class="small fw-bold mb-2">Property Details</h6>
                <div class="row">
                  <div class="col-md-6">
                    <small class="text-muted">Type:</small> {{ decision.application?.property_type }}
                  </div>
                  <div class="col-md-6">
                    <small class="text-muted">Value:</small> {{ formatCurrency(decision.application?.property_value) }}
                  </div>
                  <div class="col-12 mt-2">
                    <small class="text-muted">Address:</small> {{ decision.application?.property_address }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Bank Statement Analytics -->
        <Card v-if="decision.application?.statement_analytics?.length > 0" class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-graph-up me-2"></i>Bank Statement Analytics
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="bg-success bg-opacity-10 p-3 rounded">
                <small class="text-muted">Average Monthly Inflow</small>
                <h5 class="mb-0 text-success">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.avg_monthly_inflow) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-danger bg-opacity-10 p-3 rounded">
                <small class="text-muted">Average Monthly Outflow</small>
                <h5 class="mb-0 text-danger">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.avg_monthly_outflow) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-info bg-opacity-10 p-3 rounded">
                <small class="text-muted">Net Monthly Surplus</small>
                <h5 class="mb-0 text-info">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.net_monthly_surplus) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-warning bg-opacity-10 p-3 rounded">
                <small class="text-muted">Estimated Debt Obligations</small>
                <h5 class="mb-0 text-warning">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.estimated_debt_obligations) }}
                </h5>
              </div>
            </div>
            <div class="col-md-4">
              <small class="text-muted d-block">Income Stability</small>
              <div class="progress mt-1" style="height: 8px;">
                <div
                  class="progress-bar bg-success"
                  :style="{ width: ((decision.application.statement_analytics[0]?.income_stability_score || 0) * 100) + '%' }"
                ></div>
              </div>
              <small class="text-muted">{{ ((decision.application.statement_analytics[0]?.income_stability_score || 0) * 100).toFixed(0) }}%</small>
            </div>
            <div class="col-md-4">
              <small class="text-muted d-block">Income Consistency</small>
              <div class="progress mt-1" style="height: 8px;">
                <div
                  class="progress-bar bg-info"
                  :style="{ width: ((decision.application.statement_analytics[0]?.income_consistency_score || 0) * 100) + '%' }"
                ></div>
              </div>
              <small class="text-muted">{{ ((decision.application.statement_analytics[0]?.income_consistency_score || 0) * 100).toFixed(0) }}%</small>
            </div>
            <div class="col-md-4">
              <small class="text-muted d-block">Cash Flow Volatility</small>
              <div class="progress mt-1" style="height: 8px;">
                <div
                  class="progress-bar bg-warning"
                  :style="{ width: (decision.application.statement_analytics[0]?.cash_flow_volatility_score || 0) + '%' }"
                ></div>
              </div>
              <small class="text-muted">{{ (decision.application.statement_analytics[0]?.cash_flow_volatility_score || 0).toFixed(0) }}%</small>
            </div>
          </div>

          <!-- Risk Flags -->
          <div v-if="hasRiskFlags(decision.application.statement_analytics[0])" class="mt-3 alert alert-warning mb-0">
            <h6 class="alert-heading small mb-2">
              <i class="bi bi-exclamation-triangle me-1"></i>Risk Flags
            </h6>
            <ul class="mb-0 small">
              <li v-if="decision.application.statement_analytics[0]?.negative_balance_frequency > 0">
                Negative balance occurrences: {{ decision.application.statement_analytics[0].negative_balance_frequency }}
              </li>
              <li v-if="decision.application.statement_analytics[0]?.bounced_transactions > 0">
                Bounced transactions: {{ decision.application.statement_analytics[0].bounced_transactions }}
              </li>
              <li v-if="decision.application.statement_analytics[0]?.gambling_transactions > 0">
                Gambling transactions detected: {{ decision.application.statement_analytics[0].gambling_transactions }}
              </li>
            </ul>
          </div>
        </Card>
      </div>

      <!-- Right Column: Eligibility & Decision -->
      <div class="col-lg-5">
        <!-- Eligibility Assessment -->
        <Card v-if="decision.eligibility_assessment" class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-check2-circle me-2"></i>Eligibility Assessment
          </h5>
          
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-muted">System Decision</small>
              <Badge :variant="getSystemDecisionVariant(decision.eligibility_assessment.system_decision)">
                {{ formatSystemDecision(decision.eligibility_assessment.system_decision) }}
              </Badge>
            </div>
            <p class="small text-muted mb-0">
              {{ decision.eligibility_assessment.system_explanation }}
            </p>
          </div>

          <hr />

          <div class="row g-3 mb-3">
            <div class="col-6">
              <small class="text-muted d-block">Max Loan Amount</small>
              <div class="fw-bold">{{ formatCurrency(decision.eligibility_assessment.max_loan_amount) }}</div>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Proposed Installment</small>
              <div class="fw-bold">{{ formatCurrency(decision.eligibility_assessment.proposed_installment) }}</div>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Total Interest</small>
              <div>{{ formatCurrency(decision.eligibility_assessment.total_interest) }}</div>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Total Repayment</small>
              <div>{{ formatCurrency(decision.eligibility_assessment.total_repayment) }}</div>
            </div>
          </div>

          <hr />

          <!-- Ratios -->
          <div class="row g-3 mb-3">
            <div class="col-6">
              <small class="text-muted d-block">DTI Ratio</small>
              <div class="fw-bold" :class="getRatioClass(decision.eligibility_assessment.dti_ratio, 40)">
                {{ formatPercentage(decision.eligibility_assessment.dti_ratio) }}
              </div>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">DSR Ratio</small>
              <div class="fw-bold" :class="getRatioClass(decision.eligibility_assessment.dsr_ratio, 50)">
                {{ formatPercentage(decision.eligibility_assessment.dsr_ratio) }}
              </div>
            </div>
            <div v-if="decision.eligibility_assessment.ltv_ratio" class="col-6">
              <small class="text-muted d-block">LTV Ratio</small>
              <div class="fw-bold" :class="getRatioClass(decision.eligibility_assessment.ltv_ratio, 80)">
                {{ formatPercentage(decision.eligibility_assessment.ltv_ratio) }}
              </div>
            </div>
          </div>

          <!-- Conditions -->
          <div v-if="decision.eligibility_assessment.system_conditions?.length > 0" class="alert alert-info mb-0">
            <h6 class="alert-heading small mb-2">Conditions</h6>
            <ul class="mb-0 small">
              <li v-for="(condition, index) in decision.eligibility_assessment.system_conditions" :key="index">
                {{ condition }}
              </li>
            </ul>
          </div>
        </Card>

        <!-- Review Form -->
        <Card v-if="canReview && decision.decision_status !== 'pending_approval'">
          <h5 class="card-title mb-3">
            <i class="bi bi-pencil-square me-2"></i>Credit Officer Review
          </h5>
          
          <form @submit.prevent="submitReview">
            <div class="mb-3">
              <label class="form-label small fw-bold">Approved Amount</label>
              <input
                v-model.number="reviewForm.approved_amount"
                type="number"
                class="form-control"
                step="0.01"
                required
              />
              <small class="text-muted">Max recommended: {{ formatCurrency(decision.eligibility_assessment?.max_loan_amount) }}</small>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Approved Tenure (months)</label>
              <input
                v-model.number="reviewForm.approved_tenure_months"
                type="number"
                class="form-control"
                min="1"
                required
              />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Approved Interest Rate (%)</label>
              <input
                v-model.number="reviewForm.approved_interest_rate"
                type="number"
                class="form-control"
                step="0.01"
                required
              />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Reviewer Notes</label>
              <textarea
                v-model="reviewForm.reviewer_notes"
                class="form-control"
                rows="4"
                placeholder="Add your review comments..."
              ></textarea>
            </div>

            <div class="mb-3">
              <div class="form-check">
                <input
                  v-model="reviewForm.is_expedited"
                  class="form-check-input"
                  type="checkbox"
                  id="expeditedCheck"
                />
                <label class="form-check-label" for="expeditedCheck">
                  Mark as expedited
                </label>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="submitting"
              >
                <span v-if="submitting">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Submitting...
                </span>
                <span v-else>
                  <i class="bi bi-check-circle me-2"></i>
                  Submit for Approval
                </span>
              </button>
              <button
                type="button"
                @click="saveDraft"
                class="btn btn-outline-secondary"
                :disabled="submitting"
              >
                <i class="bi bi-save me-2"></i>Save as Draft
              </button>
            </div>
          </form>
        </Card>

        <!-- Already Reviewed Alert -->
        <Card v-else-if="decision.decision_status === 'pending_approval'">
          <div class="alert alert-success mb-0">
            <i class="bi bi-check-circle me-2"></i>
            This decision has been reviewed and is pending approval.
          </div>
          <div v-if="decision.reviewer_notes" class="mt-3">
            <h6 class="small fw-bold mb-2">Reviewer Notes:</h6>
            <p class="text-muted small mb-0">{{ decision.reviewer_notes }}</p>
          </div>
          <div class="mt-3">
            <div class="row g-2 text-center">
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Approved Amount</small>
                  <div class="fw-bold small">{{ formatCurrency(decision.approved_amount) }}</div>
                </div>
              </div>
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Tenure</small>
                  <div class="fw-bold small">{{ decision.approved_tenure_months }}m</div>
                </div>
              </div>
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Rate</small>
                  <div class="fw-bold small">{{ decision.approved_interest_rate }}%</div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';

const props = defineProps({
  decision: Object,
  canReview: Boolean,
});

const reviewForm = ref({
  approved_amount: props.decision.requested_amount,
  approved_tenure_months: props.decision.requested_tenure_months,
  approved_interest_rate: props.decision.application?.loan_product?.annual_rate || 0,
  reviewer_notes: '',
  is_expedited: props.decision.is_expedited || false,
});

const submitting = ref(false);

const submitReview = () => {
  submitting.value = true;
  
  router.post(`/underwriting/${props.decision.id}/complete-review`, {
    ...reviewForm.value,
    approved_interest_method: props.decision.application?.loan_product?.interest_method,
  }, {
    onSuccess: () => {
      // Redirect to pending approvals or back to queue
      router.visit('/underwriting/pending-reviews');
    },
    onError: (errors) => {
      submitting.value = false;
      alert(Object.values(errors).join('\n'));
    },
  });
};

const saveDraft = () => {
  // TODO: Implement save as draft
  alert('Draft save not yet implemented');
};

const hasRiskFlags = (analytics) => {
  if (!analytics) return false;
  return analytics.negative_balance_frequency > 0 ||
         analytics.bounced_transactions > 0 ||
         analytics.gambling_transactions > 0;
};

const formatCurrency = (amount) => {
  if (!amount) return 'TZS 0';
  return new Intl.NumberFormat('en-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(amount);
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const formatPercentage = (value) => {
  if (!value) return '0%';
  return `${value.toFixed(2)}%`;
};

const formatCustomerType = (type) => {
  return type?.charAt(0).toUpperCase() + type?.slice(1) || 'N/A';
};

const formatInterestMethod = (method) => {
  return method === 'reducing_balance' ? 'Reducing Balance' : 'Flat Rate';
};

const formatSystemDecision = (decision) => {
  const labels = {
    eligible: 'Eligible',
    conditional: 'Conditional',
    outside_policy: 'Outside Policy',
  };
  return labels[decision] || 'N/A';
};

const getSystemDecisionVariant = (decision) => {
  const variants = {
    eligible: 'success',
    conditional: 'warning',
    outside_policy: 'danger',
  };
  return variants[decision] || 'secondary';
};

const getRiskGradeClass = (grade) => {
  const classes = {
    A: 'text-success',
    B: 'text-info',
    C: 'text-warning',
    D: 'text-danger',
    E: 'text-danger',
    F: 'text-danger',
  };
  return classes[grade] || 'text-muted';
};

const getRatioClass = (ratio, threshold) => {
  if (!ratio) return 'text-muted';
  return ratio > threshold ? 'text-danger' : 'text-success';
};
</script>
