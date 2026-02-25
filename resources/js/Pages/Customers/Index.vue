<template>
  <AppLayout breadcrumb="Customers">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Customers</h2>
        <p class="text-muted mb-0">Manage customer profiles and KYC</p>
      </div>
      <Link href="/customers/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add New Customer
      </Link>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total Customers</p>
              <h3 class="mb-0">{{ stats.total }}</h3>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded">
              <i class="bi bi-people fs-3 text-primary"></i>
            </div>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">KYC Verified</p>
              <h3 class="mb-0">{{ stats.kyc_verified }}</h3>
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
              <p class="text-muted small mb-1">Pending KYC</p>
              <h3 class="mb-0">{{ stats.pending_kyc }}</h3>
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
              <p class="text-muted small mb-1">Active</p>
              <h3 class="mb-0">{{ stats.active }}</h3>
            </div>
            <div class="bg-info bg-opacity-10 p-3 rounded">
              <i class="bi bi-person-check fs-3 text-info"></i>
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
            placeholder="Search by name, code, or ID..."
          >
            <template #prefix>
              <i class="bi bi-search"></i>
            </template>
          </Input>
        </div>
        <div class="col-md-2">
          <Select
            v-model="filters.customer_type"
            label="Customer Type"
            :options="customerTypeOptions"
            placeholder="All Types"
          />
        </div>
        <div class="col-md-2">
          <Select
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
          />
        </div>
        <div class="col-md-2">
          <Select
            v-model="filters.kyc_verified"
            label="KYC Status"
            :options="kycOptions"
            placeholder="All"
          />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button @click="applyFilters" class="btn btn-primary w-100">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
        </div>
      </div>
    </Card>

    <!-- Customers Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Customer</th>
              <th>Code</th>
              <th>Type</th>
              <th>Contact</th>
              <th>KYC Status</th>
              <th>Profile</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in customers.data" :key="customer.id">
              <td>
                <div class="d-flex align-items-center">
                  <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                       style="width: 40px; height: 40px;">
                    <span class="fw-bold">{{ getInitials(customer) }}</span>
                  </div>
                  <div>
                    <div class="fw-bold">{{ customer.full_name }}</div>
                    <small class="text-muted">{{ customer.national_id || 'No ID' }}</small>
                  </div>
                </div>
              </td>
              <td>
                <code>{{ customer.customer_code }}</code>
              </td>
              <td>
                <Badge :variant="getTypeVariant(customer.customer_type)">
                  {{ formatCustomerType(customer.customer_type) }}
                </Badge>
              </td>
              <td>
                <div>
                  <div><i class="bi bi-telephone me-1"></i>{{ customer.phone_primary }}</div>
                  <small class="text-muted"><i class="bi bi-envelope me-1"></i>{{ customer.email || 'N/A' }}</small>
                </div>
              </td>
              <td>
                <Badge :variant="customer.kyc_verified ? 'success' : 'warning'">
                  <i :class="customer.kyc_verified ? 'bi bi-check-circle' : 'bi bi-clock-history'" class="me-1"></i>
                  {{ customer.kyc_verified ? 'Verified' : 'Pending' }}
                </Badge>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="progress flex-grow-1 me-2" style="height: 8px;">
                    <div 
                      class="progress-bar" 
                      :class="getProgressClass(customer.profile_completion_percentage)"
                      :style="{ width: customer.profile_completion_percentage + '%' }"
                    ></div>
                  </div>
                  <small class="text-muted">{{ customer.profile_completion_percentage }}%</small>
                </div>
              </td>
              <td>
                <Badge :variant="customer.status === 'active' ? 'success' : 'secondary'">
                  {{ customer.status }}
                </Badge>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link :href="`/customers/${customer.id}`" class="btn btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                  </Link>
                  <Link :href="`/customers/${customer.id}/edit`" class="btn btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </Link>
                  <button
                    @click="toggleStatus(customer)"
                    class="btn btn-outline-secondary"
                    :title="customer.status === 'active' ? 'Deactivate' : 'Activate'"
                  >
                    <i :class="customer.status === 'active' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="customers.data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                No customers found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="customers.data.length > 0" class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
          Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !customers.prev_page_url }">
              <Link v-if="customers.prev_page_url" :href="customers.prev_page_url" class="page-link">Previous</Link>
              <span v-else class="page-link">Previous</span>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === customers.current_page }"
            >
              <Link :href="`${customers.path}?page=${page}`" class="page-link">{{ page }}</Link>
            </li>
            <li class="page-item" :class="{ disabled: !customers.next_page_url }">
              <Link v-if="customers.next_page_url" :href="customers.next_page_url" class="page-link">Next</Link>
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
  customers: Object,
  stats: Object,
  filters: Object
});

const filters = ref({
  search: props.filters?.search || '',
  customer_type: props.filters?.customer_type || '',
  status: props.filters?.status || '',
  kyc_verified: props.filters?.kyc_verified || ''
});

const customerTypeOptions = [
  { value: '', label: 'All Types' },
  { value: 'salary', label: 'Salary Client' },
  { value: 'business', label: 'Business Client' },
  { value: 'mixed', label: 'Mixed Income' }
];

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const kycOptions = [
  { value: '', label: 'All' },
  { value: '1', label: 'Verified' },
  { value: '0', label: 'Pending' }
];

const paginationPages = computed(() => {
  const pages = [];
  const current = props.customers.current_page;
  const last = props.customers.last_page;
  const delta = 2;

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i);
  }

  return pages;
});

const applyFilters = () => {
  router.get('/customers', filters.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const toggleStatus = (customer) => {
  const newStatus = customer.status === 'active' ? 'inactive' : 'active';
  if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this customer?`)) {
    router.put(`/customers/${customer.id}/status`, {
      status: newStatus
    }, {
      preserveScroll: true
    });
  }
};

const getInitials = (customer) => {
  const first = customer.first_name?.[0] || '';
  const last = customer.last_name?.[0] || '';
  return (first + last).toUpperCase();
};

const getTypeVariant = (type) => {
  const variants = {
    salary: 'primary',
    business: 'success',
    mixed: 'info'
  };
  return variants[type] || 'secondary';
};

const formatCustomerType = (type) => {
  const labels = {
    salary: 'Salary',
    business: 'Business',
    mixed: 'Mixed'
  };
  return labels[type] || type;
};

const getProgressClass = (percentage) => {
  if (percentage >= 80) return 'bg-success';
  if (percentage >= 50) return 'bg-warning';
  return 'bg-danger';
};
</script>
