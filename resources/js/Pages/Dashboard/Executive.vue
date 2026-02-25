<template>
  <AppLayout breadcrumb="Executive Dashboard">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Executive Dashboard</h2>
        <p class="text-muted mb-0">Overview of key metrics and performance indicators</p>
      </div>
      <div>
        <button class="btn btn-outline-primary me-2">
          <i class="bi bi-download me-1"></i>Export Report
        </button>
        <button class="btn btn-primary">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Applications -->
      <div class="col-md-3">
        <div class="card border-start border-primary border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Total Applications</p>
                <h3 class="mb-0">{{ formatNumber(stats?.applications_total || 0) }}</h3>
                <small class="text-success">
                  <i class="bi bi-arrow-up"></i> +12.5% from last month
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
                <small class="text-success">
                  <i class="bi bi-arrow-up"></i> +8.3% from last month
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
                <p class="text-muted mb-1 small">Portfolio Value</p>
                <h3 class="mb-0">{{ formatCurrency(stats?.portfolio_value || 0) }}</h3>
                <small class="text-success">
                  <i class="bi bi-arrow-up"></i> +15.2% from last month
                </small>
              </div>
              <i class="bi bi-wallet2 text-info opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Collections Rate -->
      <div class="col-md-3">
        <div class="card border-start border-warning border-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small">Collection Rate</p>
                <h3 class="mb-0">{{ stats?.collection_rate || 0 }}%</h3>
                <small class="text-danger">
                  <i class="bi bi-arrow-down"></i> -2.1% from last month
                </small>
              </div>
              <i class="bi bi-percent text-warning opacity-25 display-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
      <!-- Application Trends -->
      <div class="col-md-8">
        <Card header="Application Trends">
          <div style="height: 300px;">
            <canvas ref="lineChartCanvas"></canvas>
          </div>
        </Card>
      </div>

      <!-- Loan Status Distribution -->
      <div class="col-md-4">
        <Card header="Loan Status">
          <div style="height: 300px;">
            <canvas ref="pieChartCanvas"></canvas>
          </div>
        </Card>
      </div>
    </div>

    <!-- Recent Applications Table -->
    <Card header="Recent Applications">
      <template #header>
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Applications</h5>
          <a href="/applications" class="btn btn-sm btn-outline-primary">
            View All
          </a>
        </div>
      </template>

      <Table
        :columns="applicationColumns"
        :data="recentApplications"
        :loading="false"
      >
        <template #cell-status="{ value }">
          <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
        </template>
        <template #cell-amount="{ value }">
          {{ formatCurrency(value) }}
        </template>
        <template #cell-actions="{ row }">
          <button class="btn btn-sm btn-outline-primary me-1">
            <i class="bi bi-eye"></i>
          </button>
          <button class="btn btn-sm btn-outline-success">
            <i class="bi bi-check-circle"></i>
          </button>
        </template>
      </Table>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Table from '@/Components/UI/Table.vue';
import Badge from '@/Components/UI/Badge.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
  stats: Object,
  recentApplications: Array
});

const lineChartCanvas = ref(null);
const pieChartCanvas = ref(null);

const applicationColumns = [
  { key: 'id', label: 'ID' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Date' },
  { key: 'actions', label: 'Actions', headerClass: 'text-end', cellClass: 'text-end' }
];

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
    'rejected': 'danger',
    'under_review': 'info'
  };
  return variants[status] || 'secondary';
};

onMounted(() => {
  // Line Chart - Application Trends
  if (lineChartCanvas.value) {
    new Chart(lineChartCanvas.value, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
          label: 'Applications',
          data: [120, 150, 180, 220, 210, 250],
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
  }

  // Pie Chart - Loan Status Distribution
  if (pieChartCanvas.value) {
    new Chart(pieChartCanvas.value, {
      type: 'doughnut',
      data: {
        labels: ['Active', 'Paid Off', 'Defaulted', 'Pending'],
        datasets: [{
          data: [45, 30, 10, 15],
          backgroundColor: [
            '#198754',
            '#0dcaf0',
            '#dc3545',
            '#ffc107'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
  }
});
</script>
