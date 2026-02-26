<template>
  <AppLayout breadcrumb="Executive Dashboard">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Executive Dashboard</h2>
        <p class="text-muted mb-0">Overview of key metrics and performance indicators</p>
      </div>
      <div>
        <button class="btn btn-primary" @click="$inertia.reload()">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
      </div>
    </div>

    <!-- Quick Links Section -->
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">
          <i class="bi bi-lightning-fill text-primary me-2"></i>Quick Access
        </h6>
        <div class="row g-3">
          <!-- Eligibility Check -->
          <div class="col-md-3 col-sm-6">
            <a href="/pre-qualify" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-primary bg-opacity-10">
                    <i class="bi bi-check-circle text-primary fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Eligibility Check</div>
                  <small class="text-muted">Pre-qualify customer</small>
                </div>
              </div>
            </a>
          </div>

          <!-- New Application -->
          <div class="col-md-3 col-sm-6">
            <a href="/applications/create" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-success bg-opacity-10">
                    <i class="bi bi-file-earmark-plus text-success fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">New Application</div>
                  <small class="text-muted">Create loan application</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Underwriting -->
          <div class="col-md-3 col-sm-6">
            <a href="/underwriting/pending-reviews" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-primary bg-opacity-10">
                    <i class="bi bi-clipboard-check text-primary fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Underwriting</div>
                  <small class="text-muted">Review decisions</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Customers -->
          <div class="col-md-3 col-sm-6">
            <a href="/customers" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-info bg-opacity-10">
                    <i class="bi bi-people text-info fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Customers</div>
                  <small class="text-muted">Manage customers</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Loan Products -->
          <div class="col-md-3 col-sm-6">
            <a href="/loan-products" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-warning bg-opacity-10">
                    <i class="bi bi-box-seam text-warning fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Loan Products</div>
                  <small class="text-muted">Product configuration</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Active Loans -->
          <div class="col-md-3 col-sm-6">
            <a href="/loans" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-success bg-opacity-10">
                    <i class="bi bi-cash-coin text-success fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Active Loans</div>
                  <small class="text-muted">View all loans</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Collections -->
          <div class="col-md-3 col-sm-6">
            <a href="/collections" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-danger bg-opacity-10">
                    <i class="bi bi-collection text-danger fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Collections</div>
                  <small class="text-muted">Arrears management</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Prospects -->
          <div class="col-md-3 col-sm-6">
            <a href="/prospects" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-secondary bg-opacity-10">
                    <i class="bi bi-person-lines-fill text-secondary fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Prospects</div>
                  <small class="text-muted">View prospects list</small>
                </div>
              </div>
            </a>
          </div>

          <!-- Reports -->
          <div class="col-md-3 col-sm-6">
            <a href="/reports/portfolio" class="text-decoration-none">
              <div class="d-flex align-items-center p-3 bg-light rounded hover-shadow transition">
                <div class="flex-shrink-0 me-3">
                  <div class="icon-circle bg-primary bg-opacity-10">
                    <i class="bi bi-graph-up text-primary fs-4"></i>
                  </div>
                </div>
                <div>
                  <div class="fw-semibold text-dark">Reports</div>
                  <small class="text-muted">Portfolio analytics</small>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Primary KPI Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Applications -->
      <div class="col-md-3">
        <div class="card border-start border-primary border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Total Applications</p>
                <h3 class="mb-0">{{ formatNumber(stats?.applications_total || 0) }}</h3>
                <small class="text-muted">
                  Approved: {{ stats?.applications_approved || 0 }} | Pending: {{ stats?.applications_pending || 0 }}
                </small>
              </div>
              <i class="bi bi-file-earmark-text text-primary opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Loans -->
      <div class="col-md-3">
        <div class="card border-start border-success border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Active Loans</p>
                <h3 class="mb-0">{{ formatNumber(stats?.loans_active || 0) }}</h3>
                <small class="text-danger" v-if="stats?.loans_in_arrears > 0">
                  <i class="bi bi-exclamation-triangle"></i> {{ stats?.loans_in_arrears }} in arrears
                </small>
                <small class="text-success" v-else>
                  <i class="bi bi-check-circle"></i> All current
                </small>
              </div>
              <i class="bi bi-cash-coin text-success opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Portfolio Value -->
      <div class="col-md-3">
        <div class="card border-start border-info border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Portfolio Outstanding</p>
                <h3 class="mb-0">{{ formatCurrency(stats?.portfolio_value || 0) }}</h3>
                <small class="text-muted">
                  Disbursed: {{ formatCurrency(stats?.portfolio_disbursed || 0) }}
                </small>
              </div>
              <i class="bi bi-wallet2 text-info opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Collection Rate -->
      <div class="col-md-3">
        <div class="card border-start border-warning border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Collection Rate</p>
                <h3 class="mb-0">{{ stats?.collection_rate || 0 }}%</h3>
                <small class="text-muted">
                  Collected: {{ formatCurrency(stats?.portfolio_collected || 0) }}
                </small>
              </div>
              <i class="bi bi-percent text-warning opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="row g-3 mb-4">
      <!-- Portfolio Quality -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-3 text-muted">Portfolio Quality</h6>
            <div class="row g-2">
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">PAR 30</div>
                  <div class="h5 mb-0" :class="stats?.par30_ratio > 5 ? 'text-danger' : 'text-success'">
                    {{ stats?.par30_ratio || 0 }}%
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">NPL Ratio</div>
                  <div class="h5 mb-0" :class="stats?.npl_ratio > 3 ? 'text-danger' : 'text-success'">
                    {{ stats?.npl_ratio || 0 }}%
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Total Arrears</div>
                  <div class="h5 mb-0 text-danger">
                    {{ formatCurrency(stats?.portfolio_arrears || 0) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Collections Queue -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-3 text-muted">Collections Queue</h6>
            <div class="row g-2">
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">In Queue</div>
                  <div class="h5 mb-0">{{ stats?.collections_queue || 0 }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Critical</div>
                  <div class="h5 mb-0 text-danger">{{ stats?.collections_critical || 0 }}</div>
                </div>
              </div>
              <div class="col-12">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Avg DPD</div>
                  <div class="h5 mb-0">{{ Math.round(stats?.avg_dpd || 0) }} days</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Application Stats -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-3 text-muted">Application Stats</h6>
            <div class="row g-2">
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Approval Rate</div>
                  <div class="h5 mb-0 text-success">{{ stats?.approval_rate || 0 }}%</div>
                </div>
              </div>
              <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Declined</div>
                  <div class="h5 mb-0 text-danger">{{ stats?.applications_declined || 0 }}</div>
                </div>
              </div>
              <div class="col-12">
                <div class="text-center p-2 bg-light rounded">
                  <div class="text-muted small">Total Loans</div>
                  <div class="h5 mb-0">{{ stats?.loans_total || 0 }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Applications Table -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Applications</h5>
          <a href="/applications" class="btn btn-sm btn-outline-primary">
            View All <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Application #</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!recentApplications || recentApplications.length === 0">
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  No recent applications
                </td>
              </tr>
              <tr v-for="app in recentApplications" :key="app.id">
                <td>
                  <a :href="`/applications/${app.id}`" class="text-decoration-none fw-semibold">
                    {{ app.application_number }}
                  </a>
                </td>
                <td>
                  <div class="fw-semibold">{{ app.customer_name }}</div>
                  <div class="small text-muted">{{ app.customer_email }}</div>
                </td>
                <td>{{ app.product }}</td>
                <td>{{ formatCurrency(app.amount) }}</td>
                <td>
                  <span class="badge" :class="'bg-' + getStatusVariant(app.status)">
                    {{ formatStatus(app.status) }}
                  </span>
                </td>
                <td>{{ app.created_at }}</td>
                <td class="text-end">
                  <a :href="`/applications/${app.id}`" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
  stats: Object,
  recentApplications: Array,
  trends: Array
});

const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num);
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0
  }).format(amount);
};

const getStatusVariant = (status) => {
  const variants = {
    'pending': 'warning',
    'approved': 'success',
    'declined': 'danger',
    'rejected': 'danger',
    'under_review': 'info'
  };
  return variants[status] || 'secondary';
};

const formatStatus = (status) => {
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};
</script>

<style scoped>
.hover-shadow {
  transition: all 0.3s ease;
}

.hover-shadow:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
  transform: translateY(-2px);
}

.icon-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.transition {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
