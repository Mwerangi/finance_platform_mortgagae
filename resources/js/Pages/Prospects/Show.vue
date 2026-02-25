<template>
  <AppLayout :breadcrumb="`Prospects / ${prospect.first_name} ${prospect.last_name}`">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">{{ prospect.first_name }} {{ prospect.last_name }}</h2>
        <p class="text-muted mb-0">Prospect Details</p>
      </div>
      <div class="d-flex gap-2">
        <Link href="/prospects" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Back to Prospects
        </Link>
        <button
          v-if="prospect.status === 'eligibility_passed'"
          @click="showConvertModal = true"
          class="btn btn-success"
        >
          <i class="bi bi-arrow-right-circle me-1"></i>Convert to Application
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- Personal Information -->
      <div class="col-md-6">
        <Card>
          <template #header>
            <h5 class="mb-0">Personal Information</h5>
          </template>
          <div class="row g-3">
            <div class="col-12">
              <label class="text-muted small">Full Name</label>
              <div class="fw-medium">
                {{ prospect.first_name }} 
                <span v-if="prospect.middle_name">{{ prospect.middle_name }} </span>
                {{ prospect.last_name }}
              </div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Phone</label>
              <div class="fw-medium">
                <i class="bi bi-phone me-1"></i>{{ prospect.phone }}
              </div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Email</label>
              <div class="fw-medium">
                <i class="bi bi-envelope me-1"></i>{{ prospect.email || 'N/A' }}
              </div>
            </div>
            <div class="col-6">
              <label class="text-muted small">ID Number</label>
              <div class="fw-medium">
                <code>{{ prospect.id_number }}</code>
              </div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Customer Type</label>
              <div>
                <Badge :variant="getCustomerTypeVariant(prospect.customer_type)">
                  {{ formatCustomerType(prospect.customer_type) }}
                </Badge>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Loan Request Information -->
      <div class="col-md-6">
        <Card>
          <template #header>
            <h5 class="mb-0">Loan Request</h5>
          </template>
          <div class="row g-3">
            <div class="col-6">
              <label class="text-muted small">Requested Amount</label>
              <div class="fw-bold fs-5 text-primary">
                {{ formatCurrency(prospect.requested_amount) }}
              </div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Tenure</label>
              <div class="fw-bold fs-5">
                {{ prospect.requested_tenure }} months
              </div>
            </div>
            <div class="col-12">
              <label class="text-muted small">Loan Product</label>
              <div class="fw-medium">
                {{ prospect.loan_product?.name || 'Default Product' }}
              </div>
            </div>
            <div class="col-12">
              <label class="text-muted small">Loan Purpose</label>
              <div class="fw-medium">{{ prospect.loan_purpose || 'N/A' }}</div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Property Location</label>
              <div class="fw-medium">{{ prospect.property_location || 'N/A' }}</div>
            </div>
            <div class="col-6">
              <label class="text-muted small">Property Value</label>
              <div class="fw-medium">
                {{ prospect.property_value ? formatCurrency(prospect.property_value) : 'N/A' }}
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Status -->
      <div class="col-12">
        <Card>
          <template #header>
            <h5 class="mb-0">Status & Timeline</h5>
          </template>
          <div class="row g-3 align-items-center">
            <div class="col-md-3">
              <label class="text-muted small">Current Status</label>
              <div>
                <Badge :variant="getStatusVariant(prospect.status)" class="px-3 py-2">
                  {{ formatStatus(prospect.status) }}
                </Badge>
              </div>
            </div>
            <div class="col-md-3">
              <label class="text-muted small">Created</label>
              <div class="fw-medium">{{ formatDate(prospect.created_at) }}</div>
            </div>
            <div class="col-md-3">
              <label class="text-muted small">Created By</label>
              <div class="fw-medium">{{ prospect.created_by?.name || 'System' }}</div>
            </div>
            <div class="col-md-3">
              <label class="text-muted small">Source</label>
              <div class="fw-medium text-capitalize">{{ prospect.source || 'Direct' }}</div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Eligibility Assessment -->
      <div v-if="prospect.eligibility_assessment" class="col-12">
        <Card>
          <template #header>
            <h5 class="mb-0">
              <i class="bi bi-clipboard-check me-2"></i>Eligibility Assessment
            </h5>
          </template>
          <div class="row g-4">
            <div class="col-md-3">
              <div class="text-center">
                <div class="text-muted small mb-1">Decision</div>
                <Badge 
                  :variant="prospect.eligibility_assessment.is_eligible ? 'success' : 'danger'" 
                  class="px-3 py-2"
                >
                  {{ prospect.eligibility_assessment.is_eligible ? 'ELIGIBLE' : 'NOT ELIGIBLE' }}
                </Badge>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="text-muted small mb-1">Risk Grade</div>
                <div class="fw-bold fs-4">{{ prospect.eligibility_assessment.risk_grade }}</div>
                <div class="small text-muted">Score: {{ prospect.eligibility_assessment.risk_score }}/100</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="text-muted small mb-1">DTI Ratio</div>
                <div class="fw-bold fs-4" :class="getDtiClass(prospect.eligibility_assessment.dti_ratio)">
                  {{ parseFloat(prospect.eligibility_assessment.dti_ratio).toFixed(1) }}%
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <div class="text-muted small mb-1">Max Eligible Amount</div>
                <div class="fw-bold text-success">
                  {{ formatCurrency(prospect.eligibility_assessment.final_max_loan) }}
                </div>
              </div>
            </div>
            <div v-if="prospect.eligibility_assessment.decision_reason" class="col-12">
              <div class="alert alert-info mb-0">
                <strong>Reason:</strong> {{ prospect.eligibility_assessment.decision_reason }}
              </div>
            </div>
            <div class="col-12">
              <Link 
                :href="`/pre-qualify/${prospect.id}/results`" 
                class="btn btn-outline-primary w-100"
              >
                <i class="bi bi-file-earmark-text me-2"></i>View Full Assessment Report
              </Link>
            </div>
          </div>
        </Card>
      </div>

      <!-- Notes -->
      <div v-if="prospect.notes" class="col-12">
        <Card>
          <template #header>
            <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Notes</h5>
          </template>
          <p class="mb-0">{{ prospect.notes }}</p>
        </Card>
      </div>

      <!-- Converted Info -->
      <div v-if="prospect.converted_to_customer" class="col-12">
        <Card>
          <div class="alert alert-success mb-0">
            <div class="row align-items-center">
              <div class="col-md-8">
                <strong><i class="bi bi-check-circle me-2"></i>Converted to Customer</strong>
                <p class="mb-0 mt-2">
                  This prospect has been converted to customer: 
                  <strong>{{ prospect.converted_to_customer.customer_code }}</strong>
                  on {{ formatDate(prospect.converted_at) }}
                </p>
              </div>
              <div class="col-md-4 text-end">
                <Link 
                  :href="`/customers/${prospect.converted_to_customer.id}`" 
                  class="btn btn-sm btn-primary"
                >
                  View Customer Profile
                </Link>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Convert to Application Modal -->
    <div v-if="showConvertModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-arrow-right-circle me-2"></i>Convert to Full Application
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showConvertModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Pre-Qualification Complete!</strong><br>
              This prospect has passed the eligibility assessment and is ready to proceed to full application.
            </div>

            <div class="card mb-3">
              <div class="card-header">
                <strong>Prospect Information</strong>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p class="mb-2"><strong>Name:</strong><br>{{ prospect.first_name }} {{ prospect.middle_name }} {{ prospect.last_name }}</p>
                    <p class="mb-2"><strong>Phone:</strong> {{ prospect.phone }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ prospect.email || 'N/A' }}</p>
                  </div>
                  <div class="col-md-6">
                    <p class="mb-2"><strong>ID Number:</strong> {{ prospect.id_number }}</p>
                    <p class="mb-2"><strong>Customer Type:</strong> {{ formatCustomerType(prospect.customer_type) }}</p>
                    <p class="mb-0"><strong>Status:</strong> <Badge :variant="getStatusVariant(prospect.status)">{{ formatStatus(prospect.status) }}</Badge></p>
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-success">
              <h6 class="mb-2"><i class="bi bi-check-circle me-2"></i>Loan Details</h6>
              <p class="mb-1"><strong>Requested Amount:</strong> {{ formatCurrency(prospect.requested_amount) }}</p>
              <p class="mb-0"><strong>Requested Tenure:</strong> {{ prospect.requested_tenure }} months</p>
            </div>

            <p class="text-muted mb-0">
              <i class="bi bi-arrow-right-circle me-2"></i>
              <strong>Next Steps:</strong> Converting this prospect to a customer will:
            </p>
            <ul class="text-muted mt-2">
              <li>Create a full customer profile</li>
              <li>Allow collection of KYC documents</li>
              <li>Enable formal loan application creation</li>
              <li>Start the credit approval workflow</li>
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
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  prospect: {
    type: Object,
    required: true,
  },
});

const showConvertModal = ref(false);
const converting = ref(false);

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

const formatCurrency = (amount) => {
  if (!amount) return 'TZS 0.00';
  return new Intl.NumberFormat('en-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatStatus = (status) => {
  const statusMap = {
    'initial': 'Initial',
    'statement_uploaded': 'Statement Uploaded',
    'statement_processing': 'Processing',
    'eligibility_passed': 'Eligible',
    'eligibility_failed': 'Not Eligible',
    'converted': 'Converted',
  };
  return statusMap[status] || status;
};

const getStatusVariant = (status) => {
  const variantMap = {
    'initial': 'secondary',
    'statement_uploaded': 'info',
    'statement_processing': 'warning',
    'eligibility_passed': 'success',
    'eligibility_failed': 'danger',
    'converted': 'primary',
  };
  return variantMap[status] || 'secondary';
};

const formatCustomerType = (type) => {
  const typeMap = {
    'salary': 'Salaried',
    'salaried': 'Salaried',
    'business': 'Business',
    'self_employed': 'Self Employed',
    'mixed': 'Mixed Income',
  };
  return typeMap[type] || type;
};

const getCustomerTypeVariant = (type) => {
  const variantMap = {
    'salary': 'primary',
    'salaried': 'primary',
    'business': 'success',
    'self_employed': 'success',
    'mixed': 'info',
  };
  return variantMap[type] || 'secondary';
};

const getDtiClass = (dti) => {
  const dtiValue = parseFloat(dti);
  if (dtiValue < 40) return 'text-success';
  if (dtiValue < 50) return 'text-warning';
  return 'text-danger';
};
</script>
