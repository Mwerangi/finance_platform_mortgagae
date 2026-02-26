<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    loans: Object,
    filters: Object
});

const filterForm = ref({
    search: props.filters.search || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || ''
});

const applyFilters = () => {
    router.get('/loans/disbursements', filterForm.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    filterForm.value = {
        search: '',
        date_from: '',
        date_to: ''
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

const getStatusBadgeVariant = (status) => {
    const variants = {
        'active': 'success',
        'disbursed': 'info',
        'closed': 'secondary',
        'written_off': 'danger',
        'defaulted': 'warning'
    };
    return variants[status] || 'secondary';
};
</script>

<template>
    <AppLayout title="Loan Disbursements" breadcrumb="Loan Disbursements">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="h3 mb-3">Loan Disbursements</h1>
                    
                    <Card>
                        <!-- Filters -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <input
                                    v-model="filterForm.search"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search by account number or customer..."
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                            <div class="col-md-3">
                                <input
                                    v-model="filterForm.date_from"
                                    type="date"
                                    class="form-control"
                                    placeholder="From Date"
                                />
                            </div>
                            <div class="col-md-3">
                                <input
                                    v-model="filterForm.date_to"
                                    type="date"
                                    class="form-control"
                                    placeholder="To Date"
                                />
                            </div>
                            <div class="col-md-2">
                                <button @click="applyFilters" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>

                        <div v-if="filters.search || filters.date_from || filters.date_to" class="mb-3">
                            <button @click="clearFilters" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Clear Filters
                            </button>
                        </div>

                        <!-- Disbursements Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan Account</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Disbursement Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Disbursed By</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="loan in loans.data" :key="loan.id">
                                        <td>
                                            <Link :href="`/loans/${loan.id}`" class="text-decoration-none fw-semibold">
                                                {{ loan.loan_account_number }}
                                            </Link>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-medium">{{ loan.customer.first_name }} {{ loan.customer.last_name }}</div>
                                                    <small class="text-muted">{{ loan.customer.customer_code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ loan.loan_product?.name }}</td>
                                        <td>{{ formatDate(loan.disbursement_date) }}</td>
                                        <td class="text-end fw-semibold">{{ formatCurrency(loan.principal_amount) }}</td>
                                        <td>
                                            <span v-if="loan.disburser">{{ loan.disburser.name }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <Badge :variant="getStatusBadgeVariant(loan.status)">
                                                {{ loan.status?.replace('_', ' ').toUpperCase() }}
                                            </Badge>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/loans/${loan.id}`" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="loans.data.length === 0">
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No disbursements found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="loans.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                Showing {{ loans.from }} to {{ loans.to }} of {{ loans.total }} disbursements
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: !loans.prev_page_url }">
                                        <Link :href="loans.prev_page_url || '#'" class="page-link">Previous</Link>
                                    </li>
                                    <li class="page-item" :class="{ disabled: !loans.next_page_url }">
                                        <Link :href="loans.next_page_url || '#'" class="page-link">Next</Link>
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
