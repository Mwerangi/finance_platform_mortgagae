<template>
  <AppLayout breadcrumb="Prospects">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Loan Prospects</h2>
        <p class="text-muted mb-0">Manage pre-qualification prospects</p>
      </div>
      <Link href="/pre-qualify" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Prospect
      </Link>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <div class="row g-3">
        <div class="col-md-6">
          <Input
            v-model="searchQuery"
            placeholder="Search by name, phone, ID number..."
            @input="handleSearch"
          >
            <template #prefix>
              <i class="bi bi-search"></i>
            </template>
          </Input>
        </div>
        <div class="col-md-4">
          <Select
            v-model="statusFilter"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
            @change="handleFilterChange"
          />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button @click="clearFilters" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-circle me-1"></i>Clear
          </button>
        </div>
      </div>
    </Card>

    <!-- Prospects Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Contact</th>
              <th>ID Number</th>
              <th>Customer Type</th>
              <th>Requested Amount</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="prospects.data.length === 0">
              <td colspan="8" class="text-center py-5">
                <div class="text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  <p class="mb-0">No prospects found</p>
                </div>
              </td>
            </tr>
            <tr v-for="prospect in prospects.data" :key="prospect.id">
              <td>
                <div>
                  <div class="fw-bold">{{ prospect.first_name }} {{ prospect.last_name }}</div>
                  <small v-if="prospect.middle_name" class="text-muted">{{ prospect.middle_name }}</small>
                </div>
              </td>
              <td>
                <div class="small">
                  <div><i class="bi bi-phone me-1"></i>{{ prospect.phone }}</div>
                  <div v-if="prospect.email" class="text-muted">
                    <i class="bi bi-envelope me-1"></i>{{ prospect.email }}
                  </div>
                </div>
              </td>
              <td>
                <code>{{ prospect.id_number }}</code>
              </td>
              <td>
                <Badge :variant="getCustomerTypeVariant(prospect.customer_type)">
                  {{ formatCustomerType(prospect.customer_type) }}
                </Badge>
              </td>
              <td class="fw-bold">{{ formatCurrency(prospect.requested_amount) }}</td>
              <td>
                <Badge :variant="getStatusVariant(prospect.status)">
                  {{ formatStatus(prospect.status) }}
                </Badge>
              </td>
              <td>
                <small>{{ formatDate(prospect.created_at) }}</small>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link 
                    :href="`/prospects/${prospect.id}`" 
                    class="btn btn-outline-primary" 
                    title="View"
                  >
                    <i class="bi bi-eye"></i>
                  </Link>
                  <button
                    v-if="prospect.status === 'eligibility_passed'"
                    @click="convertToApplication(prospect.id)"
                    class="btn btn-outline-success"
                    title="Convert to Application"
                  >
                    <i class="bi bi-arrow-right-circle"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="prospects.data.length > 0" class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
          Showing {{ prospects.from }} to {{ prospects.to }} of {{ prospects.total }} prospects
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !prospects.prev_page_url }">
              <Link 
                :href="prospects.prev_page_url || '#'" 
                class="page-link"
                :disabled="!prospects.prev_page_url"
              >
                Previous
              </Link>
            </li>
            <li 
              v-for="page in paginationPages" 
              :key="page"
              class="page-item" 
              :class="{ active: page === prospects.current_page }"
            >
              <Link 
                :href="getPageUrl(page)" 
                class="page-link"
              >
                {{ page }}
              </Link>
            </li>
            <li class="page-item" :class="{ disabled: !prospects.next_page_url }">
              <Link 
                :href="prospects.next_page_url || '#'" 
                class="page-link"
                :disabled="!prospects.next_page_url"
              >
                Next
              </Link>
            </li>
          </ul>
        </nav>
      </div>
    </Card>

    <!-- Convert to Application Modal -->
    <div v-if="showConvertModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-arrow-right-circle me-2"></i>Convert to Full Application
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeConvertModal"></button>
          </div>
          <div class="modal-body" v-if="selectedProspect">
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
                    <p class="mb-2"><strong>Name:</strong><br>{{ selectedProspect.first_name }} {{ selectedProspect.middle_name }} {{ selectedProspect.last_name }}</p>
                    <p class="mb-2"><strong>Phone:</strong> {{ selectedProspect.phone }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ selectedProspect.email || 'N/A' }}</p>
                  </div>
                  <div class="col-md-6">
                    <p class="mb-2"><strong>ID Number:</strong> {{ selectedProspect.id_number }}</p>
                    <p class="mb-2"><strong>Customer Type:</strong> <Badge :variant="getCustomerTypeVariant(selectedProspect.customer_type)">{{ formatCustomerType(selectedProspect.customer_type) }}</Badge></p>
                    <p class="mb-0"><strong>Status:</strong> <Badge :variant="getStatusVariant(selectedProspect.status)">{{ formatStatus(selectedProspect.status) }}</Badge></p>
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-success">
              <h6 class="mb-2"><i class="bi bi-check-circle me-2"></i>Loan Details</h6>
              <p class="mb-1"><strong>Requested Amount:</strong> {{ formatCurrency(selectedProspect.requested_amount) }}</p>
              <p class="mb-0"><strong>Requested Tenure:</strong> {{ selectedProspect.requested_tenure }} months</p>
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
            <button type="button" class="btn btn-secondary" @click="closeConvertModal">
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
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  prospects: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const showConvertModal = ref(false);
const selectedProspect = ref(null);
const converting = ref(false);

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'initial', label: 'Initial' },
  { value: 'statement_uploaded', label: 'Statement Uploaded' },
  { value: 'statement_processing', label: 'Processing' },
  { value: 'eligibility_passed', label: 'Eligible' },
  { value: 'eligibility_failed', label: 'Not Eligible' },
  { value: 'converted', label: 'Converted' },
];

const paginationPages = computed(() => {
  const pages = [];
  const current = props.prospects.current_page;
  const last = props.prospects.last_page;
  
  // Show max 5 pages around current
  let start = Math.max(1, current - 2);
  let end = Math.min(last, current + 2);
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const handleSearch = () => {
  applyFilters();
};

const handleFilterChange = () => {
  applyFilters();
};

const applyFilters = () => {
  router.get('/prospects', {
    search: searchQuery.value,
    status: statusFilter.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  statusFilter.value = '';
  applyFilters();
};

const getPageUrl = (page) => {
  const url = new URL(window.location.href);
  url.searchParams.set('page', page);
  if (searchQuery.value) url.searchParams.set('search', searchQuery.value);
  if (statusFilter.value) url.searchParams.set('status', statusFilter.value);
  return url.pathname + url.search;
};

const convertToApplication = (prospectId) => {
  selectedProspect.value = props.prospects.data.find(p => p.id === prospectId);
  showConvertModal.value = true;
};

const closeConvertModal = () => {
  showConvertModal.value = false;
  selectedProspect.value = null;
};

const confirmConversion = () => {
  if (!selectedProspect.value) return;
  
  converting.value = true;
  
  router.post(`/pre-qualify/${selectedProspect.value.id}/convert`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      closeConvertModal();
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
</script>
