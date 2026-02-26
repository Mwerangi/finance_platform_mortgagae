<template>
  <AppLayout :breadcrumb="`Underwriting / Approve / ${decision.decision_number}`">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Approve Decision: {{ decision.decision_number }}</h2>
        <p class="text-muted mb-0">Application: {{ decision.application?.application_number }}</p>
      </div>
      <div class="d-flex gap-2">
        <Link href="/underwriting/pending-approvals" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Back to Queue
        </Link>
      </div>
    </div>

    <!-- Flags Alert -->
    <div v-if="decision.requires_override" class="alert alert-warning mb-4">
      <h6 class="alert-heading">
        <i class="bi bi-shield-exclamation me-2"></i>Policy Override Required
      </h6>
      <p class="mb-0">This decision requires supervisor approval due to policy exceptions.</p>
    </div>

    <!-- Comparison Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <Card class="border-2 border-secondary">
          <div class="text-center">
            <h6 class="text-muted small mb-2">
              <i class="bi bi-file-earmark-text me-1"></i>Requested
            </h6>
            <h3 class="mb-1">{{ formatCurrency(decision.requested_amount) }}</h3>
            <p class="text-muted small mb-0">{{ decision.requested_tenure_months }} months</p>
          </div>
        </Card>
      </div>
      <div class="col-md-4">
        <Card class="border-2 border-primary">
          <div class="text-center">
            <h6 class="text-muted small mb-2">
              <i class="bi bi-robot me-1"></i>System Recommended
            </h6>
            <h3 class="mb-1 text-primary">{{ formatCurrency(decision.eligibility_assessment?.max_loan_amount) }}</h3>
            <p class="text-muted small mb-0">
              <Badge :variant="getSystemDecisionVariant(decision.eligibility_assessment?.system_decision)">
                {{ formatSystemDecision(decision.eligibility_assessment?.system_decision) }}
              </Badge>
            </p>
          </div>
        </Card>
      </div>
      <div class="col-md-4">
        <Card class="border-2 border-success">
          <div class="text-center">
            <h6 class="text-muted small mb-2">
              <i class="bi bi-person-check me-1"></i>Reviewer Approved
            </h6>
            <h3 class="mb-1 text-success">{{ formatCurrency(decision.approved_amount) }}</h3>
            <p class="text-muted small mb-0">{{ decision.approved_tenure_months }} months @ {{ decision.approved_interest_rate }}%</p>
          </div>
        </Card>
      </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
      <!-- Left Column: Details -->
      <div class="col-lg-8">
        <!-- Customer & Application Info -->
        <Card class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-info-circle me-2"></i>Application Summary
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="bg-light p-3 rounded">
                <h6 class="small fw-bold mb-2">Customer</h6>
                <div class="fw-bold">{{ decision.application?.customer?.full_name }}</div>
                <small class="text-muted">{{ decision.application?.customer?.customer_code }}</small>
                <div class="mt-2">
                  <Badge variant="info">{{ formatCustomerType(decision.application?.customer?.customer_type) }}</Badge>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-light p-3 rounded">
                <h6 class="small fw-bold mb-2">Loan Product</h6>
                <div class="fw-bold">{{ decision.application?.loan_product?.name }}</div>
                <small class="text-muted">{{ decision.application?.loan_product?.interest_method }}</small>
                <div class="mt-2">
                  <Badge variant="primary">{{ decision.application?.loan_product?.annual_rate }}% p.a.</Badge>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">National ID</small>
              <div class="fw-bold">{{ decision.application?.customer?.national_id || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Phone</small>
              <div class="fw-bold">{{ decision.application?.customer?.phone }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Email</small>
              <div>{{ decision.application?.customer?.email || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Submitted Date</small>
              <div>{{ formatDate(decision.application?.submitted_at) }}</div>
            </div>
          </div>
        </Card>

        <!-- Reviewer's Assessment -->
        <Card class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-person-badge me-2"></i>Credit Officer Review
          </h5>
          
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <small class="text-muted d-block">Reviewed By</small>
              <div class="fw-bold">{{ decision.reviewer?.name || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Reviewed Date</small>
              <div>{{ formatDateTime(decision.reviewed_at) }}</div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">Amount</small>
                <div class="fw-bold">{{ formatCurrency(decision.approved_amount) }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">Tenure</small>
                <div class="fw-bold">{{ decision.approved_tenure_months }}m</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">Rate</small>
                <div class="fw-bold">{{ decision.approved_interest_rate }}%</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">Method</small>
                <div class="fw-bold small">{{ formatInterestMethod(decision.approved_interest_method) }}</div>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <small class="text-muted d-block mb-1">Monthly Installment</small>
              <div class="h5 mb-0">{{ formatCurrency(decision.final_monthly_installment) }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block mb-1">Total Repayment</small>
              <div class="h5 mb-0">{{ formatCurrency(decision.final_total_repayment) }}</div>
            </div>
          </div>

          <div v-if="decision.reviewer_notes" class="mt-3 p-3 bg-light rounded">
            <h6 class="small fw-bold mb-2">Reviewer Notes:</h6>
            <p class="mb-0 small">{{ decision.reviewer_notes }}</p>
          </div>
        </Card>

        <!-- Eligibility Assessment -->
        <Card class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-calculator me-2"></i>Eligibility Assessment
          </h5>
          
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="text-center p-3 rounded" :class="getRiskGradeBg(decision.eligibility_assessment?.risk_grade)">
                <small class="text-muted d-block mb-1">Risk Grade</small>
                <h2 class="mb-0" :class="getRiskGradeClass(decision.eligibility_assessment?.risk_grade)">
                  {{ decision.eligibility_assessment?.risk_grade || 'N/A' }}
                </h2>
                <small class="text-muted">Score: {{ decision.eligibility_assessment?.risk_score || 0 }}/100</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">DTI Ratio</small>
                <h4 class="mb-0" :class="getRatioClass(decision.eligibility_assessment?.dti_ratio, 40)">
                  {{ formatPercentage(decision.eligibility_assessment?.dti_ratio) }}
                </h4>
                <small class="text-muted">Limit: 40%</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">DSR Ratio</small>
                <h4 class="mb-0" :class="getRatioClass(decision.eligibility_assessment?.dsr_ratio, 50)">
                  {{ formatPercentage(decision.eligibility_assessment?.dsr_ratio) }}
                </h4>
                <small class="text-muted">Limit: 50%</small>
              </div>
            </div>
          </div>

          <div v-if="decision.eligibility_assessment?.ltv_ratio" class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="text-center bg-light p-3 rounded">
                <small class="text-muted d-block mb-1">LTV Ratio</small>
                <h4 class="mb-0" :class="getRatioClass(decision.eligibility_assessment?.ltv_ratio, 80)">
                  {{ formatPercentage(decision.eligibility_assessment?.ltv_ratio) }}
                </h4>
                <small class="text-muted">Limit: 80%</small>
              </div>
            </div>
            <div class="col-md-8">
              <small class="text-muted d-block mb-1">Property Value</small>
              <div class="h5 mb-0">{{ formatCurrency(decision.application?.property_value) }}</div>
            </div>
          </div>

          <div class="alert alert-info mb-0">
            <h6 class="alert-heading small mb-2">System Explanation</h6>
            <p class="mb-0 small">{{ decision.eligibility_assessment?.system_explanation || 'No explanation provided' }}</p>
          </div>
        </Card>

        <!-- Bank Statement Summary -->
        <Card v-if="decision.application?.statement_analytics?.length > 0" class="mb-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-bar-chart me-2"></i>Financial Analysis
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="bg-success bg-opacity-10 p-3 rounded">
                <small class="text-muted d-block mb-1">Avg Monthly Inflow</small>
                <h5 class="mb-0 text-success">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.avg_monthly_inflow) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-danger bg-opacity-10 p-3 rounded">
                <small class="text-muted d-block mb-1">Avg Monthly Outflow</small>
                <h5 class="mb-0 text-danger">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.avg_monthly_outflow) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-info bg-opacity-10 p-3 rounded">
                <small class="text-muted d-block mb-1">Net Monthly Surplus</small>
                <h5 class="mb-0 text-info">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.net_monthly_surplus) }}
                </h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-warning bg-opacity-10 p-3 rounded">
                <small class="text-muted d-block mb-1">Debt Obligations</small>
                <h5 class="mb-0 text-warning">
                  {{ formatCurrency(decision.application.statement_analytics[0]?.estimated_debt_obligations) }}
                </h5>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Right Column: Approval Form -->
      <div class="col-lg-4">
        <!-- Approval Decision Form -->
        <Card v-if="canApprove && decision.decision_status === 'pending_approval'" class="sticky-top" style="top: 20px;">
          <h5 class="card-title mb-3">
            <i class="bi bi-check-circle me-2"></i>Supervisor Approval
          </h5>
          
          <form @submit.prevent="approveDecision">
            <div class="mb-3">
              <label class="form-label small fw-bold">Approval Notes</label>
              <textarea
                v-model="approvalForm.approver_notes"
                class="form-control"
                rows="4"
                placeholder="Add your approval comments..."
              ></textarea>
            </div>

            <div v-if="decision.requires_override" class="alert alert-warning mb-3">
              <div class="form-check">
                <input
                  v-model="approvalForm.override_approved"
                  class="form-check-input"
                  type="checkbox"
                  id="overrideCheck"
                  required
                />
                <label class="form-check-label small" for="overrideCheck">
                  I authorize this policy override
                </label>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button
                type="submit"
                class="btn btn-success btn-lg"
                :disabled="submitting"
              >
                <span v-if="submitting">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Approving...
                </span>
                <span v-else>
                  <i class="bi bi-check-circle me-2"></i>
                  Approve Decision
                </span>
              </button>
              <button
                type="button"
                @click="showDeclineModal"
                class="btn btn-outline-danger"
                :disabled="submitting"
              >
                <i class="bi bi-x-circle me-2"></i>Decline Decision
              </button>
              <button
                type="button"
                @click="requestModifications"
                class="btn btn-outline-secondary"
                :disabled="submitting"
              >
                <i class="bi bi-arrow-left-right me-2"></i>Request Changes
              </button>
            </div>
          </form>

          <!-- Decision Flags -->
          <div class="mt-3">
            <h6 class="small fw-bold mb-2">Decision Flags:</h6>
            <div class="d-flex gap-2 flex-wrap">
              <Badge v-if="decision.is_high_value" variant="warning">
                <i class="bi bi-star-fill me-1"></i>High Value
              </Badge>
              <Badge v-if="decision.is_expedited" variant="danger">
                <i class="bi bi-lightning-fill me-1"></i>Expedited
              </Badge>
              <Badge v-if="decision.requires_override" variant="danger">
                <i class="bi bi-shield-exclamation me-1"></i>Override Required
              </Badge>
              <Badge v-if="isAmountAdjusted" variant="info">
                <i class="bi bi-pencil me-1"></i>Amount Adjusted
              </Badge>
            </div>
          </div>
        </Card>

        <!-- Already Approved/Declined -->
        <Card v-else>
          <div :class="['alert', decision.decision_status === 'approved' ? 'alert-success' : 'alert-danger', 'mb-0']">
            <h6 class="alert-heading">
              <i :class="['bi', decision.decision_status === 'approved' ? 'bi-check-circle' : 'bi-x-circle', 'me-2']"></i>
              Decision {{ decision.decision_status === 'approved' ? 'Approved' : 'Declined' }}
            </h6>
            <p class="mb-0">
              <small>
                By: {{ decision.approver?.name || 'N/ A' }}<br />
                Date: {{ formatDateTime(decision.approved_at || decision.declined_at) }}
              </small>
            </p>
          </div>
          <div v-if="decision.approver_notes" class="mt-3">
            <h6 class="small fw-bold mb-2">Approver Notes:</h6>
            <p class="text-muted small mb-0">{{ decision.approver_notes }}</p>
          </div>
        </Card>
      </div>
    </div>

    <!-- Decline Modal -->
    <div v-if="showDecline" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Decline Decision</h5>
            <button type="button" class="btn-close" @click="closeDeclineModal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to decline this underwriting decision?</p>
            <div class="mb-3">
              <label class="form-label">Reason for Decline <span class="text-danger">*</span></label>
              <textarea
                v-model="declineReason"
                class="form-control"
                rows="4"
                placeholder="Provide a detailed reason..."
                required
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeDeclineModal">Cancel</button>
            <button
              type="button"
              class="btn btn-danger"
              @click="confirmDecline"
              :disabled="!declineReason || declining"
            >
              <span v-if="declining">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Declining...
              </span>
              <span v-else>
                <i class="bi bi-x-circle me-1"></i>Confirm Decline
              </span>
            </button>
          </div>
        </div>
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
  canApprove: Boolean,
});

const approvalForm = ref({
  approver_notes: '',
  override_approved: false,
});

const submitting = ref(false);
const showDecline = ref(false);
const declineReason = ref('');
const declining = ref(false);

const isAmountAdjusted = computed(() => {
  return props.decision.approved_amount !== props.decision.requested_amount ||
         props.decision.approved_tenure_months !== props.decision.requested_tenure_months;
});

const approveDecision = () => {
  if (props.decision.requires_override && !approvalForm.value.override_approved) {
    alert('Please authorize the policy override to proceed');
    return;
  }

  submitting.value = true;
  
  router.post(`/underwriting/${props.decision.id}/approve-decision`, approvalForm.value, {
    onSuccess: () => {
      router.visit('/underwriting/pending-approvals');
    },
    onError: (errors) => {
      submitting.value = false;
      alert(Object.values(errors).join('\n'));
    },
  });
};

const showDeclineModal = () => {
  showDecline.value = true;
  declineReason.value = '';
};

const closeDeclineModal = () => {
  showDecline.value = false;
  declineReason.value = '';
};

const confirmDecline = () => {
  if (!declineReason.value) {
    alert('Please provide a reason for declining');
    return;
  }

  declining.value = true;
  
  router.post(`/underwriting/${props.decision.id}/decline-decision`, {
    decision_reason: declineReason.value,
  }, {
    onSuccess: () => {
      closeDeclineModal();
      router.visit('/underwriting/pending-approvals');
    },
    onError: (errors) => {
      declining.value = false;
      alert(Object.values(errors).join('\n'));
    },
  });
};

const requestModifications = () => {
  // TODO: Implement request modifications
  alert('Request modifications feature not yet implemented');
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

const formatDateTime = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
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

const getRiskGradeBg = (grade) => {
  const classes = {
    A: 'bg-success bg-opacity-10',
    B: 'bg-info bg-opacity-10',
    C: 'bg-warning bg-opacity-10',
    D: 'bg-danger bg-opacity-10',
    E: 'bg-danger bg-opacity-10',
    F: 'bg-danger bg-opacity-10',
  };
  return classes[grade] || 'bg-light';
};

const getRatioClass = (ratio, threshold) => {
  if (!ratio) return 'text-muted';
  return ratio > threshold ? 'text-danger' : 'text-success';
};
</script>
