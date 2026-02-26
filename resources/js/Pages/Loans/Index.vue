<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    loans: Object,
    stats: Object,
    filters: Object
});

const filterForm = ref({
    status: props.filters.status || '',
    aging_bucket: props.filters.aging_bucket || '',
    overdue_only: props.filters.overdue_only || false,
    npl_only: props.filters.npl_only || false,
    search: props.filters.search || ''
});

const paginationPages = computed(() => {
    const pages = [];
    const total = props.loans.last_page;
    const current = props.loans.current_page;
    
    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - 1 && i <= current + 1)) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }
    return pages;
});

const applyFilters = () => {
    router.get('/loans', filterForm.value, { preserveState: true });
};

const getStatusVariant = (status) => {
    const variants = {
        'pending_disbursement': 'primary',
        'active': 'success',
        'fully_paid': 'info',
        'closed': 'secondary',
        'defaulted': 'danger',
        'written_off': 'dark'
    };
    return variants[status] || 'secondary';
};

const getAgingBucketVariant = (bucket) => {
    const variants = {
        'current': 'success',
        'bucket_30': 'warning',
        'bucket_60': 'warning',
        'bucket_90': 'danger',
        'bucket_180': 'danger',
        'npl': 'danger'
    };
    return variants[bucket] || 'secondary';
};

const formatStatus = (status) => {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const formatAgingBucket = (bucket) => {
    if (bucket === 'current') return 'Current';
    if (bucket === 'npl') return 'NPL';
    return bucket.replace('bucket_', '') + ' Days';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        minimumFractionDigits: 0
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout title="Loan Portfolio" breadcrumb="Loans">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Loan Portfolio</h3>
                            <p class="text-muted mb-0">Manage and monitor loan portfolio</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="bi bi-cash-stack text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Loans</div>
                                <div class="fs-5 fw-semibold">{{ stats.total }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                    <i class="bi bi-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Active</div>
                                <div class="fs-5 fw-semibold">{{ stats.active }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                    <i class="bi bi-hourglass-split text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Pending</div>
                                <div class="fs-5 fw-semibold">{{ stats.pending_disbursement }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                    <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Overdue</div>
                                <div class="fs-5 fw-semibold">{{ stats.overdue }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                    <i class="bi bi-x-circle text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">NPL</div>
                                <div class="fs-5 fw-semibold">{{ stats.npl }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-4 col-lg-2">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="bi bi-wallet2 text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Portfolio</div>
                                <div class="fs-6 fw-semibold">{{ formatCurrency(stats.total_portfolio) }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="form-control"
                            placeholder="Loan #, Customer name..."
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select v-model="filterForm.status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending_disbursement">Pending Disbursement</option>
                            <option value="active">Active</option>
                            <option value="fully_paid">Fully Paid</option>
                            <option value="closed">Closed</option>
                            <option value="defaulted">Defaulted</option>
                            <option value="written_off">Written Off</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Aging Bucket</label>
                        <select v-model="filterForm.aging_bucket" class="form-select">
                            <option value="">All Buckets</option>
                            <option value="current">Current</option>
                            <option value="bucket_30">30 Days</option>
                            <option value="bucket_60">60 Days</option>
                            <option value="bucket_90">90 Days</option>
                            <option value="bucket_180">180 Days</option>
                            <option value="npl">NPL</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input
                                v-model="filterForm.overdue_only"
                                type="checkbox"
                                class="form-check-input"
                                id="overdueOnly"
                            />
                            <label class="form-check-label" for="overdueOnly">
                                Overdue Only
                            </label>
                        </div>
                        <div class="form-check">
                            <input
                                v-model="filterForm.npl_only"
                                type="checkbox"
                                class="form-check-input"
                                id="nplOnly"
                            />
                            <label class="form-check-label" for="nplOnly">
                                NPL Only
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button @click="applyFilters" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </Card>

            <!-- Loans Table -->
            <Card>
                <template #header>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Loans</h5>
                        <span class="text-muted">
                            Showing {{ loans.from }} to {{ loans.to }} of {{ loans.total }} loans
                        </span>
                    </div>
                </template>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Loan Account</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Principal</th>
                                <th>Outstanding</th>
                                <th>DPD</th>
                                <th>Aging</th>
                                <th>Status</th>
                                <th>Disbursed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="loan in loans.data" :key="loan.id">
                                <td>
                                    <div class="fw-semibold">{{ loan.loan_account_number }}</div>
                                    <small class="text-muted" v-if="loan.external_reference_number">
                                        {{ loan.external_reference_number }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ loan.customer?.first_name }} {{ loan.customer?.last_name }}</div>
                                    <small class="text-muted">{{ loan.customer?.customer_code }}</small>
                                </td>
                                <td>
                                    <div>{{ loan.loan_product?.name }}</div>
                                    <small class="text-muted">{{ loan.loan_product?.product_code }}</small>
                                </td>
                                <td>{{ formatCurrency(loan.approved_amount) }}</td>
                                <td>
                                    <div>{{ formatCurrency(loan.total_outstanding) }}</div>
                                    <small class="text-muted" v-if="loan.status === 'active'">
                                        {{ Math.round(loan.outstanding_percentage) }}% remaining
                                    </small>
                                </td>
                                <td>
                                    <span v-if="loan.days_past_due > 0" class="badge bg-danger">
                                        {{ loan.days_past_due }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <Badge
                                        v-if="loan.aging_bucket"
                                        :variant="getAgingBucketVariant(loan.aging_bucket)"
                                    >
                                        {{ formatAgingBucket(loan.aging_bucket) }}
                                    </Badge>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <Badge :variant="getStatusVariant(loan.status)">
                                        {{ formatStatus(loan.status) }}
                                    </Badge>
                                </td>
                                <td>{{ formatDate(loan.disbursement_date) }}</td>
                                <td>
                                    <Link
                                        :href="`/loans/${loan.id}`"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!loans.data || loans.data.length === 0">
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No loans found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="loans.last_page > 1" class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing {{ loans.from }} to {{ loans.to }} of {{ loans.total }} results
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: !loans.prev_page_url }">
                                <Link v-if="loans.prev_page_url" :href="loans.prev_page_url" class="page-link">Previous</Link>
                                <span v-else class="page-link">Previous</span>
                            </li>
                            <template v-for="(page, index) in paginationPages" :key="index">
                                <li v-if="page === '...'" class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <li v-else class="page-item" :class="{ active: page === loans.current_page }">
                                    <Link :href="`${loans.path}?page=${page}`" class="page-link">{{ page }}</Link>
                                </li>
                            </template>
                            <li class="page-item" :class="{ disabled: !loans.next_page_url }">
                                <Link v-if="loans.next_page_url" :href="loans.next_page_url" class="page-link">Next</Link>
                                <span v-else class="page-link">Next</span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
