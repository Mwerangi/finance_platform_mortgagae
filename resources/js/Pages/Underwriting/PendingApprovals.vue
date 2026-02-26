<template>
  <AppLayout breadcrumb="Underwriting / Pending Approvals">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Pending Approvals</h2>
        <p class="text-muted mb-0">Review and approve underwriting decisions</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Pending</p>
              <h3 class="mb-0">{{ stats.total }}</h3>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded">
              <i class="bi bi-file-earmark-check fs-3 text-primary"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-4">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">High Value</p>
              <h3 class="mb-0">{{ stats.high_value }}</h3>
            </div>
            <div class="bg-warning bg-opacity-10 p-3 rounded">
              <i class="bi bi-star-fill fs-3 text-warning"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-4">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Requires Override</p>
              <h3 class="mb-0">{{ stats.requires_override }}</h3>
            </div>
            <div class="bg-danger bg-opacity-10 p-3 rounded">
              <i class="bi bi-shield-exclamation fs-3 text-danger"></i>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <div class="form-check form-switch">
            <input
              v-model="localFilters.high_value_only"
              class="form-check-input"
              type="checkbox"
              id="highValueFilter"
              @change="applyFilters"
            />
            <label class="form-check-label" for="highValueFilter">
              High value only
            </label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check form-switch">
            <input
              v-model="localFilters.override_only"
              class="form-check-input"
              type="checkbox"
              id="overrideFilter"
              @change="applyFilters"
            />
            <label class="form-check-label" for="overrideFilter">
              Requires override only
            </label>
          </div>
        </div>
        <div class="col-md-4 text-end">
          <button @click="clearFilters" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Clear Filters
          </button>
        </div>
      </div>
    </Card>

    <!-- Decisions Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Decision #</th>
              <th>Application</th>
              <th>Customer</th>
              <th>Requested</th>
              <th>Recommended</th>
              <th>Reviewed By</th>
              <th>Flags</th>
              <th>Reviewed</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="decisions.data.length === 0">
              <td colspan="9" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No pending approvals found
              </td>
            </tr>
            <tr v-for="decision in decisions.data" :key="decision.id">
              <td>
                <code>{{ decision.decision_number }}</code>
              </td>
              <td>
                <div>
                  <code class="small">{{ decision.application?.application_number }}</code>
                </div>
              </td>
              <td>
                <div>
                  <div class="fw-bold">{{ decision.application?.customer?.full_name }}</div>
                  <small class="text-muted">{{ decision.application?.customer?.customer_code }}</small>
                </div>
              </td>
              <td>
                <div>
                  <div class="fw-bold">{{ formatCurrency(decision.requested_amount) }}</div>
                  <small class="text-muted">{{ decision.requested_tenure_months }}m</small>
                </div>
              </td>
              <td>
                <div>
                  <div class="fw-bold text-primary">{{ formatCurrency(decision.approved_amount) }}</div>
                  <small class="text-muted">{{ decision.approved_tenure_months }}m @ {{ decision.approved_interest_rate }}%</small>
                </div>
              </td>
              <td>
                <div>
                  {{ decision.reviewer?.name || 'N/A' }}
                </div>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <Badge v-if="decision.is_high_value" variant="warning" class="small">
                    <i class="bi bi-star-fill"></i> High
                  </Badge>
                  <Badge v-if="decision.requires_override" variant="danger" class="small">
                    <i class="bi bi-shield-exclamation"></i> Override
                  </Badge>
                  <Badge v-if="decision.is_expedited" variant="danger" class="small">
                    <i class="bi bi-lightning-fill"></i> Urgent
                  </Badge>
                  <Badge v-if="isAmountAdjusted(decision)" variant="info" class="small">
                    <i class="bi bi-pencil"></i> Adjusted
                  </Badge>
                </div>
              </td>
              <td>
                <small>{{ formatDate(decision.reviewed_at) }}</small>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link
                    :href="`/underwriting/${decision.id}/approve`"
                    class="btn btn-outline-primary"
                    title="Review & Approve"
                  >
                    <i class="bi bi-eye"></i>
                  </Link>
                  <button
                    @click="quickApprove(decision.id)"
                    class="btn btn-success"
                    title="Quick Approve"
                    :disabled="processing[decision.id]"
                  >
                    <span v-if="processing[decision.id]">
                      <span class="spinner-border spinner-border-sm"></span>
                    </span>
                    <i v-else class="bi bi-check-lg"></i>
                  </button>
                  <button
                    @click="showDeclineModal(decision)"
                    class="btn btn-danger"
                    title="Decline"
                  >
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="decisions.data.length > 0" class="card-footer">
        <Pagination :data="decisions" />
      </div>
    </Card>

    <!-- Decline Modal -->
    <div v-if="showDecline" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Decline Decision</h5>
            <button type="button" class="btn-close" @click="closeDeclineModal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to decline decision <strong>{{ selectedDecision?.decision_number }}</strong>?</p>
            <div class="mb-3">
              <label class="form-label">Reason for Decline <span class="text-danger">*</span></label>
              <textarea
                v-model="declineReason"
                class="form-control"
                rows="4"
                placeholder="Provide a detailed reason for declining this decision..."
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
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  decisions: Object,
  stats: Object,
  filters: Object,
});

const localFilters = ref({
  high_value_only: props.filters?.high_value_only || false,
  override_only: props.filters?.override_only || false,
});

const processing = ref({});
const showDecline = ref(false);
const selectedDecision = ref(null);
const declineReason = ref('');
const declining = ref(false);

const applyFilters = () => {
  router.get('/underwriting/pending-approvals', localFilters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  localFilters.value = {
    high_value_only: false,
    override_only: false,
  };
  applyFilters();
};

const isAmountAdjusted = (decision) => {
  return decision.approved_amount !== decision.requested_amount ||
         decision.approved_tenure_months !== decision.requested_tenure_months;
};

const quickApprove = (decisionId) => {
  if (!confirm('Are you sure you want to approve this decision?')) {
    return;
  }

  processing.value[decisionId] = true;
  
  router.post(`/underwriting/${decisionId}/approve-decision`, {
    approver_notes: 'Quick approval',
  }, {
    onSuccess: () => {
      // Refresh the page
      router.reload();
    },
    onError: (errors) => {
      processing.value[decisionId] = false;
      alert(Object.values(errors).join('\n'));
    },
  });
};

const showDeclineModal = (decision) => {
  selectedDecision.value = decision;
  showDecline.value = true;
  declineReason.value = '';
};

const closeDeclineModal = () => {
  showDecline.value = false;
  selectedDecision.value = null;
  declineReason.value = '';
};

const confirmDecline = () => {
  if (!declineReason.value) {
    alert('Please provide a reason for declining');
    return;
  }

  declining.value = true;
  
  router.post(`/underwriting/${selectedDecision.value.id}/decline-decision`, {
    decision_reason: declineReason.value,
  }, {
    onSuccess: () => {
      closeDeclineModal();
      router.reload();
    },
    onError: (errors) => {
      declining.value = false;
      alert(Object.values(errors).join('\n'));
    },
  });
};

const formatCurrency = (amount) => {
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
</script>
