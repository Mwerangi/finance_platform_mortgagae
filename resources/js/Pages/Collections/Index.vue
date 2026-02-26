<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    queue: Object,
    stats: Object,
    officers: Array,
    filters: Object
});

const filterForm = ref({
    status: props.filters.status || '',
    priority_level: props.filters.priority_level || '',
    delinquency_bucket: props.filters.delinquency_bucket || '',
    assigned_to: props.filters.assigned_to || '',
    search: props.filters.search || ''
});

const paginationPages = computed(() => {
    const pages = [];
    const total = props.queue.last_page;
    const current = props.queue.current_page;
    
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
    router.get('/collections', filterForm.value, { preserveState: true });
};

const generateQueue = () => {
    if (confirm('Generate/refresh collections queue? This will identify all overdue loans.')) {
        router.post('/collections/generate', {}, {
            preserveScroll: true,
        });
    }
};

const getPriorityVariant = (level) => {
    const variants = {
        'critical': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'secondary'
    };
    return variants[level] || 'secondary';
};

const getStatusVariant = (status) => {
    const variants = {
        'pending': 'warning',
        'assigned': 'info',
        'in_progress': 'primary',
        'resolved': 'success',
        'escalated': 'danger'
    };
    return variants[status] || 'secondary';
};

const getBucketVariant = (bucket) => {
    const variants = {
        '1-30': 'warning',
        '31-60': 'warning',
        '61-90': 'danger',
        '91-180': 'danger',
        '180+': 'danger'
    };
    return variants[bucket] || 'secondary';
};

const formatStatus = (status) => {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const formatPriority = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1);
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
    <AppLayout title="Collections Queue" breadcrumb="Collections">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Collections Queue</h3>
                            <p class="text-muted mb-0">Manage overdue loan collections</p>
                        </div>
                        <div>
                            <button @click="generateQueue" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Generate Queue
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="bi bi-list-task text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Queue</div>
                                <div class="fs-4 fw-semibold">{{ stats.total }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-3">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                    <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Critical Priority</div>
                                <div class="fs-4 fw-semibold">{{ stats.critical }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-3">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                    <i class="bi bi-exclamation-circle text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">High Priority</div>
                                <div class="fs-4 fw-semibold">{{ stats.high }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div class="col-md-3">
                    <Card>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                    <i class="bi bi-currency-dollar text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Arrears</div>
                                <div class="fs-6 fw-semibold">{{ formatCurrency(stats.total_arrears) }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select v-model="filterForm.status" class="form-select" @change="applyFilters">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="escalated">Escalated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Priority</label>
                        <select v-model="filterForm.priority_level" class="form-select" @change="applyFilters">
                            <option value="">All Priorities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Assigned To</label>
                        <select v-model="filterForm.assigned_to" class="form-select" @change="applyFilters">
                            <option value="">All Officers</option>
                            <option v-for="officer in officers" :key="officer.id" :value="officer.id">
                                {{ officer.first_name }} {{ officer.last_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Search</label>
                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="form-control"
                            placeholder="Loan or customer..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
            </Card>

            <!-- Collections Queue Table -->
            <Card>
                <template #header>
                    <h5 class="mb-0">Collections Queue ({{ queue.total }} items)</h5>
                </template>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Loan</th>
                                <th>Customer</th>
                                <th>DPD</th>
                                <th>Arrears</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Last Action</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="queue.data.length === 0">
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No items in collections queue
                                </td>
                            </tr>
                            <tr v-for="item in queue.data" :key="item.id">
                                <td>
                                    <Link :href="`/loans/${item.loan.id}`" class="text-decoration-none">
                                        <div class="fw-semibold">{{ item.loan.loan_account_number }}</div>
                                        <div class="small text-muted">{{ item.loan.loan_product?.name }}</div>
                                    </Link>
                                </td>
                                <td>
                                    <Link :href="`/customers/${item.customer.id}`" class="text-decoration-none">
                                        <div class="fw-semibold">
                                            {{ item.loan.customer.first_name }} {{ item.loan.customer.last_name }}
                                        </div>
                                        <div class="small text-muted">{{ item.customer_phone }}</div>
                                    </Link>
                                </td>
                                <td>
                                    <span class="badge" :class="'bg-' + (item.days_past_due > 90 ? 'danger' : item.days_past_due > 30 ? 'warning' : 'info')">
                                        {{ item.days_past_due }} days
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ formatCurrency(item.total_arrears) }}</div>
                                    <div class="small text-muted" v-if="item.has_active_ptp">
                                        <i class="bi bi-check-circle text-success"></i> Has PTP
                                    </div>
                                </td>
                                <td>
                                    <Badge :variant="getPriorityVariant(item.priority_level)">
                                        {{ formatPriority(item.priority_level) }}
                                    </Badge>
                                </td>
                                <td>
                                    <Badge :variant="getStatusVariant(item.status)">
                                        {{ formatStatus(item.status) }}
                                    </Badge>
                                </td>
                                <td>
                                    <span v-if="item.assigned_to">
                                        {{ item.assigned_to.first_name }} {{ item.assigned_to.last_name }}
                                    </span>
                                    <span v-else class="text-muted">Unassigned</span>
                                </td>
                                <td>
                                    <span v-if="item.last_action_at" class="small">
                                        {{ formatDate(item.last_action_at) }}
                                    </span>
                                    <span v-else class="text-muted small">No action yet</span>
                                </td>
                                <td>
                                    <Link :href="`/loans/${item.loan.id}`" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="queue.last_page > 1">
                    <div class="text-muted small">
                        Showing {{ queue.from }} to {{ queue.to }} of {{ queue.total }} items
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: queue.current_page === 1 }">
                                <Link
                                    :href="queue.prev_page_url || '#'"
                                    class="page-link"
                                    :preserve-scroll="true"
                                >
                                    Previous
                                </Link>
                            </li>
                            <template v-for="(page, index) in paginationPages" :key="index">
                                <li v-if="page === '...'" class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <li v-else class="page-item" :class="{ active: page === queue.current_page }">
                                    <Link
                                        :href="queue.path + '?page=' + page"
                                        class="page-link"
                                        :preserve-scroll="true"
                                    >
                                        {{ page }}
                                    </Link>
                                </li>
                            </template>
                            <li class="page-item" :class="{ disabled: queue.current_page === queue.last_page }">
                                <Link
                                    :href="queue.next_page_url || '#'"
                                    class="page-link"
                                    :preserve-scroll="true"
                                >
                                    Next
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
