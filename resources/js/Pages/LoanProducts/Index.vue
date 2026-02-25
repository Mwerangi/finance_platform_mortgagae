<template>
  <AppLayout breadcrumb="Loan Products">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Loan Products</h2>
        <p class="text-muted mb-0">Configure and manage loan products</p>
      </div>
      <Link href="/loan-products/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add New Product
      </Link>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <Input
            v-model="filterForm.search"
            placeholder="Search by name or code..."
          >
            <template #prefix>
              <i class="bi bi-search"></i>
            </template>
          </Input>
        </div>
        <div class="col-md-3">
          <Select
            v-model="filterForm.status"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
          />
        </div>
        <div class="col-md-3">
          <Select
            v-model="filterForm.interest_model"
            label="Interest Model"
            :options="interestModelOptions"
            placeholder="All Models"
          />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button @click="applyFilters" class="btn btn-primary w-100">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
        </div>
      </div>
    </Card>

    <!-- Loan Products Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Product Name</th>
              <th>Code</th>
              <th>Interest Model</th>
              <th>Rate</th>
              <th>Tenure Range</th>
              <th>Amount Range</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in (products.data || [])" :key="product.id">
              <td>
                <div>
                  <div class="fw-bold">{{ product.name }}</div>
                  <small class="text-muted">{{ product.description }}</small>
                </div>
              </td>
              <td>
                <code>{{ product.code }}</code>
              </td>
              <td>
                <Badge :variant="product.interest_model === 'reducing_balance' ? 'primary' : 'secondary'">
                  {{ formatInterestModel(product.interest_model) }}
                </Badge>
              </td>
              <td class="fw-bold">{{ product.annual_interest_rate }}%</td>
              <td>
                <small>{{ product.min_tenure_months }} - {{ product.max_tenure_months }} months</small>
              </td>
              <td>
                <small>{{ formatCurrency(product.min_loan_amount) }} - {{ formatCurrency(product.max_loan_amount) }}</small>
              </td>
              <td>
                <Badge :variant="getStatusVariant(product.status)">
                  {{ product.status }}
                </Badge>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link :href="`/loan-products/${product.id}`" class="btn btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                  </Link>
                  <Link :href="`/loan-products/${product.id}/edit`" class="btn btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </Link>
                  <button
                    @click="toggleStatus(product)"
                    class="btn btn-outline-secondary"
                    :title="product.status === 'active' ? 'Deactivate' : 'Activate'"
                  >
                    <i :class="product.status === 'active' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data || products.data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                No loan products found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="products.data && products.data.length > 0" class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
          Showing {{ products.from }} to {{ products.to }} of {{ products.total }} products
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !products.prev_page_url }">
              <Link v-if="products.prev_page_url" :href="products.prev_page_url" class="page-link">Previous</Link>
              <span v-else class="page-link">Previous</span>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === products.current_page }"
            >
              <Link :href="`${products.path}?page=${page}`" class="page-link">{{ page }}</Link>
            </li>
            <li class="page-item" :class="{ disabled: !products.next_page_url }">
              <Link v-if="products.next_page_url" :href="products.next_page_url" class="page-link">Next</Link>
              <span v-else class="page-link">Next</span>
            </li>
          </ul>
        </nav>
      </div>
    </Card>

    <!-- Activate Modal -->
    <div v-if="showActivateModal && selectedProduct" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
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
                <h6 class="mb-2">{{ selectedProduct.name }}</h6>
                <div class="d-flex gap-3">
                  <div>
                    <small class="text-muted d-block">Product Code</small>
                    <code>{{ selectedProduct.code }}</code>
                  </div>
                  <div>
                    <small class="text-muted d-block">Interest Rate</small>
                    <strong>{{ selectedProduct.annual_interest_rate }}%</strong>
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
    <div v-if="showDeactivateModal && selectedProduct" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
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
                <h6 class="mb-2">{{ selectedProduct.name }}</h6>
                <div class="d-flex gap-3">
                  <div>
                    <small class="text-muted d-block">Product Code</small>
                    <code>{{ selectedProduct.code }}</code>
                  </div>
                  <div>
                    <small class="text-muted d-block">Current Status</small>
                    <Badge variant="success">Active</Badge>
                  </div>
                </div>
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
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  products: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const filterForm = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  interest_model: props.filters?.interest_model || ''
});

const showActivateModal = ref(false);
const showDeactivateModal = ref(false);
const selectedProduct = ref(null);

const activateForm = useForm({});
const deactivateForm = useForm({});

const openActivateModal = (product) => {
  selectedProduct.value = product;
  showActivateModal.value = true;
};

const openDeactivateModal = (product) => {
  selectedProduct.value = product;
  showDeactivateModal.value = true;
};

const submitActivate = () => {
  if (!selectedProduct.value) return;
  
  activateForm.put(`/loan-products/${selectedProduct.value.id}/activate`, {
    preserveScroll: true,
    onSuccess: () => {
      showActivateModal.value = false;
      selectedProduct.value = null;
    }
  });
};

const submitDeactivate = () => {
  if (!selectedProduct.value) return;
  
  deactivateForm.put(`/loan-products/${selectedProduct.value.id}/deactivate`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeactivateModal.value = false;
      selectedProduct.value = null;
    }
  });
};

const toggleStatus = (product) => {
  if (product.status === 'active') {
    openDeactivateModal(product);
  } else {
    openActivateModal(product);
  }
};

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const interestModelOptions = [
  { value: '', label: 'All Models' },
  { value: 'reducing_balance', label: 'Reducing Balance' },
  { value: 'flat_rate', label: 'Flat Rate' }
];

const paginationPages = computed(() => {
  if (!props.products || !props.products.current_page) return [];
  
  const pages = [];
  const current = props.products.current_page;
  const last = props.products.last_page;
  const delta = 2;

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i);
  }

  return pages;
});

const applyFilters = () => {
  router.get('/loan-products', filterForm.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const getStatusVariant = (status) => {
  return status === 'active' ? 'success' : 'warning';
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
</script>
