<template>
  <AppLayout breadcrumb="Applications / Details">
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
              v-if="application.status === 'approved' && !application.latest_loan"
              :href="`/applications/${application.id}/disburse`"
              class="btn btn-success"
            >
              <i class="bi bi-cash-stack me-1"></i>Disburse Loan
            </Link>
            <Link
              v-if="application.latest_loan"
              :href="`/loans/${application.latest_loan.id}`"
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
            <Card title="Customer Information" class="mb-4">
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
            <Card title="Loan Product" class="mb-4">
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
            <Card title="Property Information" class="mb-4">
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
            <Card v-if="application.notes" title="Notes" class="mb-4">
              <p class="mb-0">{{ application.notes }}</p>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="col-lg-5">
            <!-- Application Summary -->
            <Card title="Application Summary" class="mb-4">
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
            <Card title="Application Timeline" class="mb-4">
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
            <Card v-if="latestUnderwriting" title="Underwriting Decision" class="mb-4">
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
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

defineProps({
  application: Object,
  latestUnderwriting: Object,
  canApprove: Boolean
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
  if (confirm('Approve this application? This action will allow the application to proceed to loan disbursement.')) {
    router.post(`/applications/${props.application.id}/approve`);
  }
};

const rejectApplication = () => {
  const notes = prompt('Reason for rejection:');
  if (notes !== null) {
    router.post(`/applications/${props.application.id}/reject`, { notes });
  }
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
