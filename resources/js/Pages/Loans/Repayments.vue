<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    repayments: Object,
    stats: Object,
    filters: Object
});

const filterForm = ref({
    search: props.filters.search || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    payment_method: props.filters.payment_method || ''
});

const applyFilters = () => {
    router.get('/loans/repayments', filterForm.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    filterForm.value = {
        search: '',
        date_from: '',
        date_to: '',
        payment_method: ''
    };
    applyFilters();
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        minimumFractionDigits: 0
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatPaymentMethod = (method) => {
    const methods = {
        'cash': 'Cash',
        'bank_transfer': 'Bank Transfer',
        'mobile_money': 'Mobile Money',
        'cheque': 'Cheque',
        'standing_order': 'Standing Order'
    };
    return methods[method] || method;
};
</script>

<template>
    <AppLayout title="Loan Repayments" breadcrumb="Loan Repayments">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="h3 mb-3">Loan Repayments</h1>
                    
                    <!-- Stats Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <Card class="h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted small mb-1">Total Repayments</p>
                                        <h3 class="mb-0">{{ stats.total_count }}</h3>
                                    </div>
                                    <div class="text-primary fs-1">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                </div>
                            </Card>
                        </div>
                        <div class="col-md-3">
                            <Card class="h-100">
                                <p class="text-muted small mb-1">Total Amount</p>
                                <h3 class="mb-0">{{ formatCurrency(stats.total_amount) }}</h3>
                            </Card>
                        </div>
                        <div class="col-md-3">
                            <Card class="h-100">
                                <p class="text-muted small mb-1">Principal Received</p>
                                <h3 class="mb-0 text-success">{{ formatCurrency(stats.total_principal) }}</h3>
                            </Card>
                        </div>
                        <div class="col-md-3">
                            <Card class="h-100">
                                <p class="text-muted small mb-1">Interest Received</p>
                                <h3 class="mb-0 text-info">{{ formatCurrency(stats.total_interest) }}</h3>
                            </Card>
                        </div>
                    </div>

                    <Card>
                        <!-- Filters -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <input
                                    v-model="filterForm.search"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search..."
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                            <div class="col-md-2">
                                <input
                                    v-model="filterForm.date_from"
                                    type="date"
                                    class="form-control"
                                    placeholder="From Date"
                                />
                            </div>
                            <div class="col-md-2">
                                <input
                                    v-model="filterForm.date_to"
                                    type="date"
                                    class="form-control"
                                    placeholder="To Date"
                                />
                            </div>
                            <div class="col-md-3">
                                <select v-model="filterForm.payment_method" class="form-select">
                                    <option value="">All Payment Methods</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="standing_order">Standing Order</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button @click="applyFilters" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>

                        <div v-if="filters.search || filters.date_from || filters.date_to || filters.payment_method" class="mb-3">
                            <button @click="clearFilters" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Clear Filters
                            </button>
                        </div>

                        <!-- Repayments Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Loan Account</th>
                                        <th>Customer</th>
                                        <th>Payment Date</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Principal</th>
                                        <th class="text-end">Interest</th>
                                        <th>Method</th>
                                        <th>Collected By</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="repayment in repayments.data" :key="repayment.id">
                                        <td>
                                            <span class="font-monospace small">{{ repayment.reference_number }}</span>
                                        </td>
                                        <td>
                                            <Link :href="`/loans/${repayment.loan.id}`" class="text-decoration-none">
                                                {{ repayment.loan.loan_account_number }}
                                            </Link>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ repayment.loan.customer.first_name }} {{ repayment.loan.customer.last_name }}</div>
                                                <small class="text-muted">{{ repayment.loan.customer.customer_code }}</small>
                                            </div>
                                        </td>
                                        <td>{{ formatDate(repayment.payment_date) }}</td>
                                        <td class="text-end fw-semibold">{{ formatCurrency(repayment.amount) }}</td>
                                        <td class="text-end text-success">{{ formatCurrency(repayment.principal_amount) }}</td>
                                        <td class="text-end text-info">{{ formatCurrency(repayment.interest_amount) }}</td>
                                        <td>
                                            <Badge variant="secondary">{{ formatPaymentMethod(repayment.payment_method) }}</Badge>
                                        </td>
                                        <td>
                                            <span v-if="repayment.recorded_by">{{ repayment.recorded_by.name }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/loans/${repayment.loan.id}`" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="repayments.data.length === 0">
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No repayments found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="repayments.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                Showing {{ repayments.from }} to {{ repayments.to }} of {{ repayments.total }} repayments
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: !repayments.prev_page_url }">
                                        <Link :href="repayments.prev_page_url || '#'" class="page-link">Previous</Link>
                                    </li>
                                    <li class="page-item" :class="{ disabled: !repayments.next_page_url }">
                                        <Link :href="repayments.next_page_url || '#'" class="page-link">Next</Link>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
