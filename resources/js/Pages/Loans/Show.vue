<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    loan: Object,
    canDisburse: Boolean,
    canWriteOff: Boolean,
    nextInstallment: Object
});

const showActivateModal = ref(false);
const showWriteOffModal = ref(false);
const showDefaultModal = ref(false);
const showCloseModal = ref(false);
const showPaymentModal = ref(false);

// Pagination for installment schedule
const currentPage = ref(1);
const perPage = 7;

const activateForm = useForm({
    activation_date: new Date().toISOString().split('T')[0],
    first_installment_date: ''
});

const writeOffForm = useForm({
    reason: '',
    amount: props.loan.total_outstanding
});

const defaultForm = useForm({
    reason: ''
});

const closeForm = useForm({
    reason: ''
});

const paymentForm = useForm({
    payment_date: new Date().toISOString().split('T')[0],
    amount: '',
    payment_method: 'cash',
    reference_number: '',
    notes: '',
    installment_id: null
});

// Get all unpaid/partially paid installments for selection
const getUnpaidInstallments = () => {
    if (!props.loan.schedules) return [];
    return props.loan.schedules.filter(schedule => 
        ['pending', 'partially_paid', 'overdue'].includes(schedule.status)
    );
};

// Auto-populate amount when modal opens
const openPaymentModal = () => {
    if (props.nextInstallment) {
        paymentForm.amount = props.nextInstallment.balance_remaining;
        paymentForm.installment_id = props.nextInstallment.id; // Default to next installment
    } else {
        // If no schedules, suggest a reasonable amount based on tenure
        // This helps users but doesn't enforce it
        if (props.loan.tenure_months > 0 && props.loan.total_outstanding > 0) {
            paymentForm.amount = Math.round(props.loan.total_outstanding / props.loan.tenure_months);
        } else {
            paymentForm.amount = '';
        }
        paymentForm.installment_id = null;
    }
    showPaymentModal.value = true;
};

// Update amount when installment selection changes
const onInstallmentChange = () => {
    if (paymentForm.installment_id) {
        const selectedInstallment = props.loan.schedules.find(
            schedule => schedule.id === parseInt(paymentForm.installment_id)
        );
        if (selectedInstallment) {
            paymentForm.amount = selectedInstallment.balance_remaining;
        }
    }
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

const formatInterestMethod = (method) => {
    const methods = {
        'fixed': 'Fixed Rate',
        'reducing_balance': 'Reducing Balance',
        'flat_rate': 'Flat Rate'
    };
    return methods[method] || method;
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

const calculateMaturityDate = (firstInstallmentDate, tenureMonths) => {
    if (!firstInstallmentDate || !tenureMonths) return null;
    const date = new Date(firstInstallmentDate);
    date.setMonth(date.getMonth() + tenureMonths);
    return date.toISOString().split('T')[0];
};

const getInitials = (firstName, lastName) => {
    return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase();
};

const submitActivate = () => {
    activateForm.post(`/loans/${props.loan.id}/activate`, {
        onSuccess: () => {
            showActivateModal.value = false;
            activateForm.reset();
        }
    });
};

const submitWriteOff = () => {
    writeOffForm.post(`/loans/${props.loan.id}/write-off`, {
        onSuccess: () => {
            showWriteOffModal.value = false;
            writeOffForm.reset();
        }
    });
};

const submitDefault = () => {
    defaultForm.post(`/loans/${props.loan.id}/mark-defaulted`, {
        onSuccess: () => {
            showDefaultModal.value = false;
            defaultForm.reset();
        }
    });
};

const submitClose = () => {
    closeForm.post(`/loans/${props.loan.id}/close`, {
        onSuccess: () => {
            showCloseModal.value = false;
            closeForm.reset();
        }
    });
};

const submitPayment = () => {
    paymentForm.post(`/loans/${props.loan.id}/add-payment`, {
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
            paymentForm.payment_date = new Date().toISOString().split('T')[0];
            paymentForm.payment_method = 'cash';
            paymentForm.installment_id = null;
        }
    });
};

// Installment schedule helper functions
const getInstallmentStatusVariant = (status) => {
    const variants = {
        'pending': 'warning',
        'partially_paid': 'info',
        'fully_paid': 'success',
        'overdue': 'danger',
        'waived': 'secondary'
    };
    return variants[status] || 'secondary';
};

const formatInstallmentStatus = (status) => {
    const statuses = {
        'pending': 'Pending',
        'partially_paid': 'Partial',
        'fully_paid': 'Paid',
        'overdue': 'Overdue',
        'waived': 'Waived'
    };
    return statuses[status] || status;
};

const isUpcoming = (dueDate) => {
    const due = new Date(dueDate);
    const today = new Date();
    const daysUntilDue = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
    return daysUntilDue >= 0 && daysUntilDue <= 7;
};

const isOverdue = (installment) => {
    return installment.status === 'overdue' || installment.days_past_due > 0;
};

const getPaymentProgress = (installment) => {
    if (installment.total_due === 0) return 0;
    return Math.round((installment.total_paid / installment.total_due) * 100);
};

const getProgressBarClass = (installment) => {
    const progress = getPaymentProgress(installment);
    if (progress === 100) return 'bg-success';
    if (progress > 0) return 'bg-info';
    if (installment.status === 'overdue') return 'bg-danger';
    return 'bg-warning';
};

const getTotalPrincipal = () => {
    return props.loan.schedules?.reduce((sum, s) => sum + parseFloat(s.principal_due || 0), 0) || 0;
};

const getTotalInterest = () => {
    return props.loan.schedules?.reduce((sum, s) => sum + parseFloat(s.interest_due || 0), 0) || 0;
};

const getTotalDue = () => {
    return props.loan.schedules?.reduce((sum, s) => sum + parseFloat(s.total_due || 0), 0) || 0;
};

const getTotalPaid = () => {
    return props.loan.schedules?.reduce((sum, s) => sum + parseFloat(s.total_paid || 0), 0) || 0;
};

const getTotalBalance = () => {
    return props.loan.schedules?.reduce((sum, s) => sum + parseFloat(s.balance_remaining || 0), 0) || 0;
};

// Pagination helper functions
const getPaginatedSchedules = () => {
    if (!props.loan.schedules) return [];
    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;
    return props.loan.schedules.slice(start, end);
};

const getTotalPages = () => {
    if (!props.loan.schedules) return 0;
    return Math.ceil(props.loan.schedules.length / perPage);
};

const goToPage = (page) => {
    if (page >= 1 && page <= getTotalPages()) {
        currentPage.value = page;
    }
};

const nextPage = () => {
    if (currentPage.value < getTotalPages()) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};
</script>

<template>
    <AppLayout title="Loan Details" :breadcrumb="[
        { label: 'Loans', href: '/loans' },
        { label: loan.loan_account_number }
    ]">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <Link
                                    href="/loans"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    <i class="bi bi-arrow-left"></i>
                                </Link>
                                <div>
                                    <h3 class="mb-0">{{ loan.loan_account_number }}</h3>
                                    <small class="text-muted" v-if="loan.external_reference_number">
                                        Ref: {{ loan.external_reference_number }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button
                                v-if="loan.status === 'pending_disbursement'"
                                @click="showActivateModal = true"
                                class="btn btn-success"
                            >
                                <i class="bi bi-check-circle me-1"></i>
                                Activate Loan
                            </button>
                            <button
                                v-if="loan.status === 'active' && canWriteOff"
                                @click="showDefaultModal = true"
                                class="btn btn-warning"
                            >
                                Mark as Defaulted
                            </button>
                            <button
                                v-if="(loan.status === 'active' || loan.status === 'defaulted') && canWriteOff"
                                @click="showWriteOffModal = true"
                                class="btn btn-danger"
                            >
                                Write Off
                            </button>
                            <button
                                v-if="(loan.status === 'fully_paid' || loan.status === 'written_off') && canWriteOff"
                                @click="showCloseModal = true"
                                class="btn btn-secondary"
                            >
                                Close Loan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Loan Summary -->
                    <Card class="mb-3">
                        <template #header>
                            <h5 class="mb-0">Loan Summary</h5>
                        </template>

                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Approved Amount</div>
                                <div class="fs-5 fw-semibold">{{ formatCurrency(loan.approved_amount) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Total Repayment</div>
                                <div class="fs-5 fw-semibold">{{ formatCurrency(loan.total_repayment) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Monthly Payment</div>
                                <div class="fs-5 fw-semibold">{{ formatCurrency(loan.monthly_installment) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Status</div>
                                <Badge :variant="getStatusVariant(loan.status)" class="fs-6">
                                    {{ formatStatus(loan.status) }}
                                </Badge>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Repayment Progress</span>
                                <span class="fw-semibold">{{ Math.round(loan.repayment_progress || 0) }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div
                                    class="progress-bar"
                                    :class="{
                                        'bg-success': loan.repayment_progress >= 75,
                                        'bg-info': loan.repayment_progress >= 50 && loan.repayment_progress < 75,
                                        'bg-warning': loan.repayment_progress >= 25 && loan.repayment_progress < 50,
                                        'bg-danger': loan.repayment_progress < 25
                                    }"
                                    :style="{ width: (loan.repayment_progress || 0) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Total Paid</div>
                                <div class="fw-semibold">{{ formatCurrency(loan.total_paid) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Total Outstanding</div>
                                <div class="fw-semibold">{{ formatCurrency(loan.total_outstanding) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Days Past Due</div>
                                <div class="fw-semibold" :class="{ 'text-danger': loan.days_past_due > 0 }">
                                    {{ loan.days_past_due || 0 }} days
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Customer Information -->
                    <Card class="mb-3">
                        <template #header>
                            <h5 class="mb-0">Customer Information</h5>
                        </template>

                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                                style="width: 60px; height: 60px;">
                                <span class="fw-semibold text-primary">
                                    {{ getInitials(loan.customer?.first_name, loan.customer?.last_name) }}
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-1">{{ loan.customer?.first_name }} {{ loan.customer?.middle_name }} {{ loan.customer?.last_name }}</h6>
                                <div class="text-muted small">{{ loan.customer?.customer_code }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Phone</div>
                                <div>{{ loan.customer?.phone || 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Email</div>
                                <div>{{ loan.customer?.email || 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Customer Type</div>
                                <div class="text-capitalize">{{ loan.customer?.customer_type || 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">National ID</div>
                                <div>{{ loan.customer?.national_id || 'N/A' }}</div>
                            </div>
                        </div>
                    </Card>

                    <!-- Loan Product -->
                    <Card class="mb-3">
                        <template #header>
                            <h5 class="mb-0">Loan Product Details</h5>
                        </template>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Product Name</div>
                                <div class="fw-semibold">{{ loan.loan_product?.name }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Product Code</div>
                                <div>{{ loan.loan_product?.product_code }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Interest Rate</div>
                                <div>{{ loan.approved_interest_rate }}% p.a.</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Interest Method</div>
                                <div>{{ formatInterestMethod(loan.interest_method) }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Tenure</div>
                                <div>{{ loan.approved_tenure_months }} months</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Total Interest</div>
                                <div>{{ formatCurrency(loan.total_interest) }}</div>
                            </div>
                        </div>
                    </Card>

                    <!-- Property Information -->
                    <Card v-if="loan.property_type" class="mb-3">
                        <template #header>
                            <h5 class="mb-0">Property Information</h5>
                        </template>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Property Type</div>
                                <div>{{ loan.property_type }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Property Value</div>
                                <div>{{ formatCurrency(loan.property_value) }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">LTV Ratio</div>
                                <div>{{ loan.ltv_ratio }}%</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Title Number</div>
                                <div>{{ loan.property_title_number || 'N/A' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small">Property Address</div>
                                <div>{{ loan.property_address || 'N/A' }}</div>
                            </div>
                        </div>
                    </Card>

                    <!-- Repayment Schedule -->
                    <Card v-if="loan.schedules && loan.schedules.length > 0" class="mb-3">
                        <template #header>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Repayment Schedule</h5>
                                <div class="text-muted small">
                                    <span class="me-3">
                                        <i class="bi bi-check-circle text-success"></i>
                                        {{ loan.schedules.filter(s => s.status === 'fully_paid').length }} Paid
                                    </span>
                                    <span class="me-3">
                                        <i class="bi bi-clock text-warning"></i>
                                        {{ loan.schedules.filter(s => s.status === 'pending' || s.status === 'partially_paid').length }} Pending
                                    </span>
                                    <span v-if="loan.schedules.filter(s => s.status === 'overdue').length > 0">
                                        <i class="bi bi-exclamation-circle text-danger"></i>
                                        {{ loan.schedules.filter(s => s.status === 'overdue').length }} Overdue
                                    </span>
                                </div>
                            </div>
                        </template>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px">#</th>
                                        <th>Due Date</th>
                                        <th class="text-end">Principal</th>
                                        <th class="text-end">Interest</th>
                                        <th class="text-end">Total Due</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Balance</th>
                                        <th style="width: 120px">Status</th>
                                        <th style="width: 100px">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="installment in getPaginatedSchedules()" 
                                        :key="installment.id"
                                        :class="{
                                            'table-success': installment.status === 'fully_paid',
                                            'table-warning': installment.status === 'partially_paid',
                                            'table-danger': installment.status === 'overdue'
                                        }"
                                    >
                                        <td class="fw-bold">{{ installment.installment_number }}</td>
                                        <td>
                                            {{ formatDate(installment.due_date) }}
                                            <span v-if="isUpcoming(installment.due_date)" class="badge bg-info ms-1" style="font-size: 0.7rem">
                                                Soon
                                            </span>
                                            <span v-if="isOverdue(installment)" class="badge bg-danger ms-1" style="font-size: 0.7rem">
                                                {{ installment.days_past_due }}d overdue
                                            </span>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(installment.principal_due) }}</td>
                                        <td class="text-end">{{ formatCurrency(installment.interest_due) }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(installment.total_due) }}</td>
                                        <td class="text-end">{{ formatCurrency(installment.total_paid) }}</td>
                                        <td class="text-end">
                                            <span :class="installment.balance_remaining > 0 ? 'text-danger fw-bold' : 'text-success'">
                                                {{ formatCurrency(installment.balance_remaining) }}
                                            </span>
                                        </td>
                                        <td>
                                            <Badge :variant="getInstallmentStatusVariant(installment.status)">
                                                {{ formatInstallmentStatus(installment.status) }}
                                            </Badge>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div 
                                                    class="progress-bar" 
                                                    :class="getProgressBarClass(installment)"
                                                    :style="{ width: getPaymentProgress(installment) + '%' }"
                                                    role="progressbar"
                                                ></div>
                                            </div>
                                            <small class="text-muted">{{ getPaymentProgress(installment) }}%</small>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total</td>
                                        <td class="text-end">{{ formatCurrency(getTotalPrincipal()) }}</td>
                                        <td class="text-end">{{ formatCurrency(getTotalInterest()) }}</td>
                                        <td class="text-end">{{ formatCurrency(getTotalDue()) }}</td>
                                        <td class="text-end">{{ formatCurrency(getTotalPaid()) }}</td>
                                        <td class="text-end text-danger">{{ formatCurrency(getTotalBalance()) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="getTotalPages() > 1" class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                            <div class="text-muted small">
                                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, loan.schedules.length) }} of {{ loan.schedules.length }} installments
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="prevPage">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <li 
                                        v-for="page in getTotalPages()" 
                                        :key="page"
                                        class="page-item" 
                                        :class="{ active: currentPage === page }"
                                    >
                                        <a class="page-link" href="#" @click.prevent="goToPage(page)">
                                            {{ page }}
                                        </a>
                                    </li>
                                    
                                    <li class="page-item" :class="{ disabled: currentPage === getTotalPages() }">
                                        <a class="page-link" href="#" @click.prevent="nextPage">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </Card>

                    <!-- Recent Repayments -->
                    <Card>
                        <template #header>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Repayments</h5>
                                <button 
                                    v-if="loan.status === 'active' || loan.status === 'defaulted'"
                                    type="button" 
                                    class="btn btn-primary btn-sm" 
                                    @click="openPaymentModal"
                                >
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Add Payment
                                </button>
                            </div>
                        </template>

                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Principal</th>
                                        <th>Interest</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="repayment in loan.repayments" :key="repayment.id">
                                        <td>{{ formatDate(repayment.payment_date) }}</td>
                                        <td>{{ formatCurrency(repayment.amount) }}</td>
                                        <td>{{ formatCurrency(repayment.principal_amount) }}</td>
                                        <td>{{ formatCurrency(repayment.interest_amount) }}</td>
                                        <td class="text-capitalize">{{ repayment.payment_method }}</td>
                                        <td><small>{{ repayment.transaction_reference || 'N/A' }}</small></td>
                                    </tr>
                                    <tr v-if="!loan.repayments || loan.repayments.length === 0">
                                        <td colspan="6" class="text-center text-muted">No repayments recorded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Disbursement Information -->
                    <Card class="mb-3">
                        <template #header>
                            <h6 class="mb-0">Disbursement Details</h6>
                        </template>

                        <div class="mb-3">
                            <div class="text-muted small">Disbursed Amount</div>
                            <div class="fw-semibold">{{ formatCurrency(loan.disbursed_amount) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Disbursement Date</div>
                            <div>{{ formatDate(loan.disbursement_date) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Method</div>
                            <div class="text-capitalize">{{ loan.disbursement_method?.replace('_', ' ') || 'N/A' }}</div>
                        </div>
                        <div v-if="loan.disbursement_reference" class="mb-3">
                            <div class="text-muted small">Reference</div>
                            <div><small>{{ loan.disbursement_reference }}</small></div>
                        </div>
                        <div v-if="loan.disburser">
                            <div class="text-muted small">Disbursed By</div>
                            <div>{{ loan.disburser.name }}</div>
                        </div>
                    </Card>

                    <!-- Key Dates -->
                    <Card class="mb-3">
                        <template #header>
                            <h6 class="mb-0">Key Dates</h6>
                        </template>

                        <div class="mb-3">
                            <div class="text-muted small">Activation Date</div>
                            <div>{{ formatDate(loan.activation_date) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">First Installment</div>
                            <div>{{ formatDate(loan.first_installment_date) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Maturity Date</div>
                            <div>{{ formatDate(loan.maturity_date) }}</div>
                        </div>
                        <div v-if="loan.next_payment_due_date" class="mb-3">
                            <div class="text-muted small">Next Payment Due</div>
                            <div class="fw-semibold">{{ formatDate(loan.next_payment_due_date) }}</div>
                        </div>
                        <div v-if="loan.last_payment_date">
                            <div class="text-muted small">Last Payment</div>
                            <div>{{ formatDate(loan.last_payment_date) }}</div>
                        </div>
                    </Card>

                    <!-- Outstanding Breakdown -->
                    <Card class="mb-3">
                        <template #header>
                            <h6 class="mb-0">Outstanding Breakdown</h6>
                        </template>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Principal</span>
                                <span class="fw-semibold">{{ formatCurrency(loan.principal_outstanding) }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Interest</span>
                                <span class="fw-semibold">{{ formatCurrency(loan.interest_outstanding) }}</span>
                            </div>
                        </div>
                        <div v-if="loan.penalties_outstanding > 0" class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Penalties</span>
                                <span class="fw-semibold text-danger">{{ formatCurrency(loan.penalties_outstanding) }}</span>
                            </div>
                        </div>
                        <div v-if="loan.fees_outstanding > 0" class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Fees</span>
                                <span class="fw-semibold">{{ formatCurrency(loan.fees_outstanding) }}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Total Outstanding</span>
                            <span class="fw-bold fs-5">{{ formatCurrency(loan.total_outstanding) }}</span>
                        </div>
                    </Card>

                    <!-- Risk & Aging -->
                    <Card v-if="loan.aging_bucket || loan.risk_classification">
                        <template #header>
                            <h6 class="mb-0">Risk & Aging</h6>
                        </template>

                        <div v-if="loan.aging_bucket" class="mb-3">
                            <div class="text-muted small mb-2">Aging Bucket</div>
                            <Badge :variant="getAgingBucketVariant(loan.aging_bucket)">
                                {{ loan.aging_bucket.replace('_', ' ').toUpperCase() }}
                            </Badge>
                        </div>
                        <div v-if="loan.risk_classification">
                            <div class="text-muted small mb-2">Risk Classification</div>
                            <div class="text-capitalize fw-semibold">{{ loan.risk_classification.replace('_', ' ') }}</div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Activate Modal -->
        <div v-if="showActivateModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Activate Loan</h5>
                        <button type="button" class="btn-close" @click="showActivateModal = false"></button>
                    </div>
                    <form @submit.prevent="submitActivate">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Set the activation date and first installment date to activate this loan.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Loan Details</label>
                                <div class="p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Approved Amount</small>
                                            <strong>{{ formatCurrency(loan.approved_amount) }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tenure</small>
                                            <strong>{{ loan.approved_tenure_months }} months</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Activation Date *</label>
                                <input
                                    v-model="activateForm.activation_date"
                                    type="date"
                                    class="form-control"
                                    :class="{ 'is-invalid': activateForm.errors.activation_date }"
                                    required
                                />
                                <div v-if="activateForm.errors.activation_date" class="invalid-feedback">
                                    {{ activateForm.errors.activation_date }}
                                </div>
                                <small class="text-muted">The date when the loan becomes active</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">First Installment Date *</label>
                                <input
                                    v-model="activateForm.first_installment_date"
                                    type="date"
                                    class="form-control"
                                    :class="{ 'is-invalid': activateForm.errors.first_installment_date }"
                                    :min="activateForm.activation_date"
                                    required
                                />
                                <div v-if="activateForm.errors.first_installment_date" class="invalid-feedback">
                                    {{ activateForm.errors.first_installment_date }}
                                </div>
                                <small class="text-muted">The date of the first repayment installment</small>
                            </div>

                            <div v-if="activateForm.first_installment_date" class="alert alert-success mb-0">
                                <small>
                                    <i class="bi bi-calendar-check me-2"></i>
                                    <strong>Maturity Date:</strong> 
                                    {{ formatDate(calculateMaturityDate(activateForm.first_installment_date, loan.approved_tenure_months)) }}
                                </small>
                            </div>
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
                                    Activate Loan
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Write Off Modal -->
        <div v-if="showWriteOffModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Write Off Loan</h5>
                        <button type="button" class="btn-close" @click="showWriteOffModal = false"></button>
                    </div>
                    <form @submit.prevent="submitWriteOff">
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                This action cannot be undone. The loan will be marked as written off.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount to Write Off</label>
                                <input
                                    v-model="writeOffForm.amount"
                                    type="number"
                                    class="form-control"
                                    :class="{ 'is-invalid': writeOffForm.errors.amount }"
                                    step="0.01"
                                    required
                                />
                                <div v-if="writeOffForm.errors.amount" class="invalid-feedback">
                                    {{ writeOffForm.errors.amount }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason *</label>
                                <textarea
                                    v-model="writeOffForm.reason"
                                    class="form-control"
                                    :class="{ 'is-invalid': writeOffForm.errors.reason }"
                                    rows="3"
                                    required
                                ></textarea>
                                <div v-if="writeOffForm.errors.reason" class="invalid-feedback">
                                    {{ writeOffForm.errors.reason }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showWriteOffModal = false">Cancel</button>
                            <button type="submit" class="btn btn-danger" :disabled="writeOffForm.processing">
                                <span v-if="writeOffForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </span>
                                <span v-else>Write Off Loan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Default Modal -->
        <div v-if="showDefaultModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark Loan as Defaulted</h5>
                        <button type="button" class="btn-close" @click="showDefaultModal = false"></button>
                    </div>
                    <form @submit.prevent="submitDefault">
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                This will mark the loan as defaulted. Collections actions may be required.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason *</label>
                                <textarea
                                    v-model="defaultForm.reason"
                                    class="form-control"
                                    :class="{ 'is-invalid': defaultForm.errors.reason }"
                                    rows="3"
                                    required
                                ></textarea>
                                <div v-if="defaultForm.errors.reason" class="invalid-feedback">
                                    {{ defaultForm.errors.reason }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showDefaultModal = false">Cancel</button>
                            <button type="submit" class="btn btn-warning" :disabled="defaultForm.processing">
                                <span v-if="defaultForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </span>
                                <span v-else>Mark as Defaulted</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Close Modal -->
        <div v-if="showCloseModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Close Loan</h5>
                        <button type="button" class="btn-close" @click="showCloseModal = false"></button>
                    </div>
                    <form @submit.prevent="submitClose">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Reason (Optional)</label>
                                <textarea
                                    v-model="closeForm.reason"
                                    class="form-control"
                                    :class="{ 'is-invalid': closeForm.errors.reason }"
                                    rows="3"
                                ></textarea>
                                <div v-if="closeForm.errors.reason" class="invalid-feedback">
                                    {{ closeForm.errors.reason }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCloseModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="closeForm.processing">
                                <span v-if="closeForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </span>
                                <span v-else>Close Loan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Payment Modal -->
        <div v-if="showPaymentModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Manual Payment</h5>
                        <button type="button" class="btn-close" @click="showPaymentModal = false"></button>
                    </div>
                    <form @submit.prevent="submitPayment">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Outstanding Balance:</strong> {{ formatCurrency(loan.total_outstanding) }}
                                    </div>
                                </div>
                                <div v-if="nextInstallment" class="mt-2 pt-2 border-top">
                                    <small class="d-block">
                                        <strong>Next Installment #{{ nextInstallment.installment_number }}:</strong>
                                        {{ formatCurrency(nextInstallment.balance_remaining) }} 
                                        (Due: {{ formatDate(nextInstallment.due_date) }})
                                    </small>
                                    <small class="text-muted d-block mt-1">
                                        Principal: {{ formatCurrency(nextInstallment.principal_due - nextInstallment.principal_paid) }} | 
                                        Interest: {{ formatCurrency(nextInstallment.interest_due - nextInstallment.interest_paid) }}
                                    </small>
                                </div>
                                <div v-else class="mt-2 pt-2 border-top">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i>
                                        No installment schedule available. Payment will be allocated to interest first, then principal.
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3" v-if="getUnpaidInstallments().length > 0">
                                <label class="form-label">Select Installment *</label>
                                <select
                                    v-model="paymentForm.installment_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': paymentForm.errors.installment_id }"
                                    @change="onInstallmentChange"
                                >
                                    <option :value="null" disabled>Choose installment to pay</option>
                                    <option 
                                        v-for="installment in getUnpaidInstallments()" 
                                        :key="installment.id" 
                                        :value="installment.id"
                                    >
                                        #{{ installment.installment_number }} - 
                                        {{ formatDate(installment.due_date) }} - 
                                        {{ formatCurrency(installment.balance_remaining) }}
                                        <span v-if="installment.status === 'overdue'" class="text-danger"> (Overdue)</span>
                                        <span v-else-if="installment.status === 'partially_paid'" class="text-info"> (Partial)</span>
                                    </option>
                                </select>
                                <div v-if="paymentForm.errors.installment_id" class="invalid-feedback">
                                    {{ paymentForm.errors.installment_id }}
                                </div>
                                <small class="text-muted">
                                    Select which installment to pay. Amount will auto-populate with the installment balance.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Date *</label>
                                <input
                                    v-model="paymentForm.payment_date"
                                    type="date"
                                    class="form-control"
                                    :class="{ 'is-invalid': paymentForm.errors.payment_date }"
                                    required
                                />
                                <div v-if="paymentForm.errors.payment_date" class="invalid-feedback">
                                    {{ paymentForm.errors.payment_date }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Amount *</label>
                                <input
                                    v-model="paymentForm.amount"
                                    type="number"
                                    class="form-control"
                                    :class="{ 'is-invalid': paymentForm.errors.amount }"
                                    step="0.01"
                                    :min="nextInstallment ? nextInstallment.balance_remaining : 0.01"
                                    placeholder="Enter payment amount"
                                    required
                                />
                                <div v-if="paymentForm.errors.amount" class="invalid-feedback">
                                    {{ paymentForm.errors.amount }}
                                </div>
                                <small v-if="nextInstallment" class="text-muted">
                                    Minimum: {{ formatCurrency(nextInstallment.balance_remaining) }}. 
                                    Pay more to advance to next installments.
                                </small>
                                <small v-else class="text-muted">
                                    Enter any amount. Payment will be allocated to interest first, then principal.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method *</label>
                                <select
                                    v-model="paymentForm.payment_method"
                                    class="form-select"
                                    :class="{ 'is-invalid': paymentForm.errors.payment_method }"
                                    required
                                >
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="standing_order">Standing Order</option>
                                </select>
                                <div v-if="paymentForm.errors.payment_method" class="invalid-feedback">
                                    {{ paymentForm.errors.payment_method }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input
                                    v-model="paymentForm.reference_number"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': paymentForm.errors.reference_number }"
                                    placeholder="Receipt or transaction reference"
                                />
                                <div v-if="paymentForm.errors.reference_number" class="invalid-feedback">
                                    {{ paymentForm.errors.reference_number }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea
                                    v-model="paymentForm.notes"
                                    class="form-control"
                                    :class="{ 'is-invalid': paymentForm.errors.notes }"
                                    rows="3"
                                    placeholder="Optional notes about this payment"
                                ></textarea>
                                <div v-if="paymentForm.errors.notes" class="invalid-feedback">
                                    {{ paymentForm.errors.notes }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showPaymentModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="paymentForm.processing">
                                <span v-if="paymentForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </span>
                                <span v-else>
                                    <i class="bi bi-check-circle me-1"></i>
                                    Record Payment
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
