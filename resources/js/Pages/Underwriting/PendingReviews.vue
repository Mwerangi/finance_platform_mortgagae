<template>
  <AppLayout breadcrumb="Underwriting / Pending Reviews">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Pending Reviews</h2>
        <p class="text-muted mb-0">Review and process underwriting decisions</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Pending</p>
              <h3 class="mb-0">{{ stats.total }}</h3>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded">
              <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">My Queue</p>
              <h3 class="mb-0">{{ stats.my_queue }}</h3>
            </div>
            <div class="bg-info bg-opacity-10 p-3 rounded">
              <i class="bi bi-person-check fs-3 text-info"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">High Value</p>
              <h3 class="mb-0">{{ stats.high_value }}</h3>
            </div>
            <div class="bg-warning bg-opacity-10 p-3 rounded">
              <i class="bi bi-exclamation-triangle fs-3 text-warning"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Expedited</p>
              <h3 class="mb-0">{{ stats.expedited }}</h3>
            </div>
            <div class="bg-danger bg-opacity-10 p-3 rounded">
              <i class="bi bi-lightning-charge fs-3 text-danger"></i>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <div class="form-check form-switch">
            <input
              v-model="localFilters.my_queue"
              class="form-check-input"
              type="checkbox"
              id="myQueueFilter"
              @change="applyFilters"
            />
            <label class="form-check-label" for="myQueueFilter">
              Show only my queue
            </label>
          </div>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3">
          <div class="form-check form-switch">
            <input
              v-model="localFilters.expedited_only"
              class="form-check-input"
              type="checkbox"
              id="expeditedFilter"
              @change="applyFilters"
            />
            <label class="form-check-label" for="expeditedFilter">
              Expedited only
            </label>
          </div>
        </div>
        <div class="col-md-3 text-end">
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
              <th>Requested Amount</th>
              <th>Status</th>
              <th>Flags</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="decisions.data.length === 0">
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No pending reviews found
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
              <td class="fw-bold">{{ formatCurrency(decision.requested_amount) }}</td>
              <td>
                <Badge :variant="getStatusVariant(decision.decision_status)">
                  {{ formatStatus(decision.decision_status) }}
                </Badge>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <Badge v-if="decision.is_high_value" variant="warning" class="small">
                    <i class="bi bi-star-fill"></i> High Value
                  </Badge>
                  <Badge v-if="decision.is_expedited" variant="danger" class="small">
                    <i class="bi bi-lightning-fill"></i> Expedited
                  </Badge>
                  <Badge v-if="decision.requires_override" variant="secondary" class="small">
                    <i class="bi bi-shield-exclamation"></i> Override
                  </Badge>
                </div>
              </td>
              <td>
                <small>{{ formatDate(decision.created_at) }}</small>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link
                    :href="`/underwriting/${decision.id}/review`"
                    class="btn btn-outline-primary"
                    title="Review"
                  >
                    <i class="bi bi-eye"></i>
                  </Link>
                  <button
                    v-if="decision.decision_status === 'pending_review'"
                    @click="startReview(decision.id)"
                    class="btn btn-primary"
                    title="Start Review"
                    :disabled="processing[decision.id]"
                  >
                    <span v-if="processing[decision.id]">
                      <span class="spinner-border spinner-border-sm me-1"></span>
                    </span>
                    <i v-else class="bi bi-play-fill"></i>
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
  my_queue: props.filters?.my_queue || false,
  high_value_only: props.filters?.high_value_only || false,
  expedited_only: props.filters?.expedited_only || false,
});

const processing = ref({});

const applyFilters = () => {
  router.get('/underwriting/pending-reviews', localFilters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  localFilters.value = {
    my_queue: false,
    high_value_only: false,
    expedited_only: false,
  };
  applyFilters();
};

const startReview = (decisionId) => {
  processing.value[decisionId] = true;
  
  router.post(`/underwriting/${decisionId}/start-review`, {}, {
    onSuccess: () => {
      router.visit(`/underwriting/${decisionId}/review`);
    },
    onError: (errors) => {
      processing.value[decisionId] = false;
      alert(errors.message || 'Failed to start review');
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

const formatStatus = (status) => {
  return status
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const getStatusVariant = (status) => {
  const variants = {
    pending_review: 'warning',
    under_review: 'info',
    pending_approval: 'primary',
    approved: 'success',
    declined: 'danger',
    cancelled: 'secondary',
  };
  return variants[status] || 'secondary';
};
</script>
