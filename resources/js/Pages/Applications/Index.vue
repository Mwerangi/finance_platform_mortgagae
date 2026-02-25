<template>
  <AppLayout breadcrumb="Applications">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Loan Applications</h2>
        <p class="text-muted mb-0">Manage and track loan applications</p>
      </div>
      <Link href="/applications/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Application
      </Link>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Applications</p>
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
              <p class="text-muted small mb-1">Pending Review</p>
              <h3 class="mb-0">{{ stats.pending }}</h3>
            </div>
            <div class="bg-warning bg-opacity-10 p-3 rounded">
              <i class="bi bi-clock-history fs-3 text-warning"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Approved</p>
              <h3 class="mb-0">{{ stats.approved }}</h3>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded">
              <i class="bi bi-check-circle fs-3 text-success"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Disbursed</p>
              <h3 class="mb-0">{{ stats.disbursed }}</h3>
            </div>
            <div class="bg-info bg-opacity-10 p-3 rounded">
              <i class="bi bi-cash-coin fs-3 text-info"></i>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <Input
            v-model="filters.search"
            placeholder="Search by application #, customer name..."
          >
            <template #prefix>
              <i class="bi bi-search"></i>
            </template>
          </Input>
        </div>
        <div class="col-md-3">
          <Select
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
          />
        </div>
        <div class="col-md-3">
          <Select
            v-model="filters.loan_product_id"
            label="Loan Product"
            :options="loanProductOptions"
            placeholder="All Products"
          />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button @click="applyFilters" class="btn btn-primary w-100">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
        </div>
      </div>
    </Card>

    <!-- Applications Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Application #</th>
              <th>Customer</th>
              <th>Loan Product</th>
              <th>Requested Amount</th>
              <th>Tenure</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="application in applications.data" :key="application.id">
              <td>
                <code>{{ application.application_number }}</code>
              </td>
              <td>
                <div>
                  <div class="fw-bold">{{ application.customer?.full_name }}</div>
                  <small class="text-muted">{{ application.customer?.customer_code }}</small>
                </div>
              </td>
              <td>
                <div>{{ application.loan_product?.name }}</div>
                <small class="text-muted">{{ application.loan_product?.code }}</small>
              </td>
              <td class="fw-bold">{{ formatCurrency(application.requested_amount) }}</td>
              <td>{{ application.requested_tenure_months }} months</td>
              <td>
                <Badge :variant="getStatusVariant(application.status)">
                  {{ formatStatus(application.status) }}
                </Badge>
              </td>
              <td>
                <small>{{ application.submitted_at ? formatDate(application.submitted_at) : 'Not submitted' }}</small>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link :href="`/applications/${application.id}`" class="btn btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                  </Link>
                  <Link 
                    v-if="application.status === 'draft'"
                    :href="`/applications/${application.id}/edit`" 
                    class="btn btn-outline-warning" 
                    title="Edit"
                  >
                    <i class="bi bi-pencil"></i>
                  </Link>
                  <button
                    v-if="application.status === 'submitted'"
                    @click="startReview(application)"
                    class="btn btn-outline-info"
                    title="Start Review"
                  >
                    <i class="bi bi-play-circle"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="applications.data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                No applications found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="applications.data.length > 0" class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
          Showing {{ applications.from }} to {{ applications.to }} of {{ applications.total }} applications
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !applications.prev_page_url }">
              <Link v-if="applications.prev_page_url" :href="applications.prev_page_url" class="page-link">Previous</Link>
              <span v-else class="page-link">Previous</span>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === applications.current_page }"
            >
              <Link :href="`${applications.path}?page=${page}`" class="page-link">{{ page }}</Link>
            </li>
            <li class="page-item" :class="{ disabled: !applications.next_page_url }">
              <Link v-if="applications.next_page_url" :href="applications.next_page_url" class="page-link">Next</Link>
              <span v-else class="page-link">Next</span>
            </li>
          </ul>
        </nav>
      </div>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  applications: Object,
  stats: Object,
  loanProducts: Array,
  filters: Object
});

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  loan_product_id: props.filters?.loan_product_id || ''
});

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'disbursed', label: 'Disbursed' }
];

const loanProductOptions = computed(() => {
  return [
    { value: '', label: 'All Products' },
    ...(props.loanProducts || []).map(p => ({ value: p.id, label: p.name }))
  ];
});

const paginationPages = computed(() => {
  const pages = [];
  const current = props.applications.current_page;
  const last = props.applications.last_page;
  const delta = 2;

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i);
  }

  return pages;
});

const applyFilters = () => {
  router.get('/applications', filters.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const startReview = (application) => {
  if (confirm('Start reviewing this application?')) {
    router.post(`/applications/${application.id}/start-review`, {}, {
      preserveScroll: true
    });
  }
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

const formatStatus = (status) => {
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
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
    month: 'short',
    day: 'numeric'
  });
};
</script>
