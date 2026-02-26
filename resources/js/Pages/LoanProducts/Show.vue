<template>
  <AppLayout :breadcrumb="[
    { label: 'Loan Products', href: '/loan-products' },
    { label: loanProduct.name }
  ]">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h2 class="mb-1">{{ product.name }}</h2>
            <div class="d-flex align-items-center gap-2">
              <Badge :variant="product.status === 'active' ? 'success' : 'warning'">
                {{ product.status }}
              </Badge>
              <span class="text-muted">•</span>
              <code>{{ product.code }}</code>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button
              v-if="product.status !== 'active'"
              @click="showActivateModal = true"
              class="btn btn-success"
            >
              <i class="bi bi-check-circle me-1"></i>Activate
            </button>
            <button
              v-if="product.status === 'active'"
              @click="showDeactivateModal = true"
              class="btn btn-warning"
            >
              <i class="bi bi-pause-circle me-1"></i>Deactivate
            </button>
            <Link :href="`/loan-products/${product.id}/edit`" class="btn btn-primary">
              <i class="bi bi-pencil me-1"></i>Edit
            </Link>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left Column -->
          <div class="col-lg-6">
            <!-- Basic Information -->
            <Card title="Basic Information" class="mb-4">
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Product Name</label>
                <div class="fw-bold">{{ product.name }}</div>
              </div>
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Product Code</label>
                <code>{{ product.code }}</code>
              </div>
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Description</label>
                <div>{{ product.description || 'N/A' }}</div>
              </div>
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Status</label>
                <Badge :variant="product.status === 'active' ? 'success' : 'warning'">
                  {{ product.status }}
                </Badge>
              </div>
            </Card>

            <!-- Interest Configuration -->
            <Card title="Interest Configuration" class="mb-4">
              <div class="row g-3">
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Interest Model</label>
                  <Badge :variant="product.interest_model === 'reducing_balance' ? 'primary' : 'secondary'">
                    {{ formatInterestModel(product.interest_model) }}
                  </Badge>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Annual Interest Rate</label>
                  <div class="fw-bold fs-5">{{ product.annual_interest_rate }}%</div>
                </div>
                <div class="col-12">
                  <label class="text-muted small d-block mb-1">Rate Type</label>
                  <div>{{ product.rate_type || 'N/A' }}</div>
                </div>
              </div>
            </Card>

            <!-- Tenure & Amount Limits -->
            <Card title="Tenure & Amount Limits" class="mb-4">
              <div class="row g-3">
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Tenure Range</label>
                  <div class="fw-bold">{{ product.min_tenure_months }} - {{ product.max_tenure_months }} months</div>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Loan Amount Range</label>
                  <div class="fw-bold">{{ formatCurrency(product.min_loan_amount) }} - {{ formatCurrency(product.max_loan_amount) }}</div>
                </div>
              </div>
            </Card>

            <!-- Fees -->
            <Card title="Fees" class="mb-4">
              <div v-if="product.fees && product.fees.length > 0">
                <div v-for="(fee, index) in product.fees" :key="index" class="mb-3 pb-3 border-bottom">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-bold">{{ fee.type }}</div>
                      <small class="text-muted">{{ fee.frequency }}</small>
                    </div>
                    <div class="fw-bold">{{ formatCurrency(fee.amount) }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-muted text-center py-3">
                No fees configured
              </div>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="col-lg-6">
            <!-- Policy Rules -->
            <Card title="Policy Rules" class="mb-4">
              <div class="row g-3">
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Max LTV</label>
                  <div class="fw-bold">{{ product.max_ltv_percentage || 'N/A' }}<span v-if="product.max_ltv_percentage">%</span></div>
                  <small class="text-muted">Loan-to-Value</small>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Max DTI</label>
                  <div class="fw-bold">{{ product.max_dti_percentage || 'N/A' }}<span v-if="product.max_dti_percentage">%</span></div>
                  <small class="text-muted">Debt-to-Income</small>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Max DSR (Salary)</label>
                  <div class="fw-bold">{{ product.max_dsr_salary_percentage || 'N/A' }}<span v-if="product.max_dsr_salary_percentage">%</span></div>
                  <small class="text-muted">Debt Service Ratio</small>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Max DSR (Business)</label>
                  <div class="fw-bold">{{ product.max_dsr_business_percentage || 'N/A' }}<span v-if="product.max_dsr_business_percentage">%</span></div>
                  <small class="text-muted">Debt Service Ratio</small>
                </div>
                <div class="col-12">
                  <label class="text-muted small d-block mb-1">Business Safety Factor</label>
                  <div class="fw-bold">{{ product.business_safety_factor || 'N/A' }}</div>
                  <small class="text-muted">Safety margin for business income calculations</small>
                </div>
              </div>
            </Card>

            <!-- Penalties -->
            <Card title="Penalties" class="mb-4">
              <div v-if="product.penalties && product.penalties.length > 0">
                <div v-for="(penalty, index) in product.penalties" :key="index" class="mb-3 pb-3 border-bottom">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-bold">{{ penalty.type }}</div>
                      <small class="text-muted">{{ penalty.trigger }}</small>
                    </div>
                    <div class="fw-bold">{{ formatCurrency(penalty.amount) }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-muted text-center py-3">
                No penalties configured
              </div>
            </Card>

            <!-- Status Information -->
            <Card title="Status Information" class="mb-4">
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Created</label>
                <div>{{ formatDate(product.created_at) }}</div>
              </div>
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Last Updated</label>
                <div>{{ formatDate(product.updated_at) }}</div>
              </div>
              <div v-if="product.activated_at" class="mb-3">
                <label class="text-muted small d-block mb-1">Activated</label>
                <div>{{ formatDate(product.activated_at) }}</div>
              </div>
              <div v-if="product.deactivated_at">
                <label class="text-muted small d-block mb-1">Deactivated</label>
                <div>{{ formatDate(product.deactivated_at) }}</div>
              </div>
            </Card>

            <!-- Usage Statistics (if available) -->
            <Card v-if="stats" title="Usage Statistics" class="mb-4">
              <div class="row g-3">
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Total Applications</label>
                  <div class="fw-bold fs-4">{{ stats.total_applications || 0 }}</div>
                </div>
                <div class="col-6">
                  <label class="text-muted small d-block mb-1">Active Loans</label>
                  <div class="fw-bold fs-4">{{ stats.active_loans || 0 }}</div>
                </div>
                <div class="col-12">
                  <label class="text-muted small d-block mb-1">Total Disbursed</label>
                  <div class="fw-bold fs-4">{{ formatCurrency(stats.total_disbursed || 0) }}</div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Activate Modal -->
    <div v-if="showActivateModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Activate Loan Product</h5>
            <button type="button" class="btn-close" @click="showActivateModal = false"></button>
          </div>
          <form @submit.prevent="submitActivate">
            <div class="modal-body">
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Activating this product will allow it to be used for new loan applications.
              </div>
              
              <div class="p-3 bg-light rounded">
                <h6 class="mb-3">{{ product.name }}</h6>
                <div class="row g-2">
                  <div class="col-6">
                    <small class="text-muted d-block">Product Code</small>
                    <code>{{ product.code }}</code>
                  </div>
                  <div class="col-6">
                    <small class="text-muted d-block">Interest Rate</small>
                    <strong>{{ product.annual_interest_rate }}%</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted d-block">Loan Range</small>
                    <strong>{{ formatCurrency(product.min_loan_amount) }} - {{ formatCurrency(product.max_loan_amount) }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted d-block">Tenure Range</small>
                    <strong>{{ product.min_tenure_months }} - {{ product.max_tenure_months }} months</strong>
                  </div>
                </div>
              </div>

              <p class="mt-3 mb-0 text-muted">
                Are you sure you want to activate this loan product?
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showActivateModal = false">Cancel</button>
              <button type="submit" class="btn btn-success" :disabled="activateForm.processing">
                <span v-if="activateForm.processing">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Activating...
                </span>
                <span v-else>
                  <i class="bi bi-check-circle me-1"></i>
                  Activate Product
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Deactivate Modal -->
    <div v-if="showDeactivateModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Deactivate Loan Product</h5>
            <button type="button" class="btn-close" @click="showDeactivateModal = false"></button>
          </div>
          <form @submit.prevent="submitDeactivate">
            <div class="modal-body">
              <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> Deactivating this product will prevent new loan applications from being created with it.
              </div>
              
              <div class="p-3 bg-light rounded">
                <h6 class="mb-3">{{ product.name }}</h6>
                <div class="row g-2">
                  <div class="col-6">
                    <small class="text-muted d-block">Product Code</small>
                    <code>{{ product.code }}</code>
                  </div>
                  <div class="col-6">
                    <small class="text-muted d-block">Current Status</small>
                    <Badge variant="success">Active</Badge>
                  </div>
                </div>
              </div>

              <div v-if="stats.active_loans > 0" class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> There are {{ stats.active_loans }} active loan(s) using this product. They will not be affected.
              </div>

              <p class="mt-3 mb-0 text-muted">
                Are you sure you want to deactivate this loan product?
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showDeactivateModal = false">Cancel</button>
              <button type="submit" class="btn btn-warning" :disabled="deactivateForm.processing">
                <span v-if="deactivateForm.processing">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Deactivating...
                </span>
                <span v-else>
                  <i class="bi bi-pause-circle me-1"></i>
                  Deactivate Product
                </span>
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
import { router, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  product: Object,
  stats: Object
});

const showActivateModal = ref(false);
const showDeactivateModal = ref(false);

const activateForm = useForm({});
const deactivateForm = useForm({});

const submitActivate = () => {
  activateForm.put(`/loan-products/${props.product.id}/activate`, {
    preserveScroll: true,
    onSuccess: () => {
      showActivateModal.value = false;
    }
  });
};

const submitDeactivate = () => {
  deactivateForm.put(`/loan-products/${props.product.id}/deactivate`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeactivateModal.value = false;
    }
  });
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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
