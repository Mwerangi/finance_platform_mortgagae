<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';

const props = defineProps({
    application: Object
});

const form = useForm({
    disbursed_amount: props.application.requested_amount,
    disbursement_date: new Date().toISOString().split('T')[0],
    disbursement_method: 'bank_transfer',
    disbursement_reference: '',
    disbursement_notes: '',
    activation_date: new Date().toISOString().split('T')[0],
    first_installment_date: ''
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        minimumFractionDigits: 0
    }).format(amount || 0);
};

const formatInterestMethod = (method) => {
    const methods = {
        'fixed': 'Fixed Rate',
        'reducing_balance': 'Reducing Balance',
        'flat_rate': 'Flat Rate'
    };
    return methods[method] || method;
};

const getInitials = (firstName, lastName) => {
    return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase();
};

const submit = () => {
    form.post(`/applications/${props.application.id}/disburse`);
};
</script>

<template>
    <AppLayout title="Disburse Loan">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Disburse Loan</h3>
                            <p class="text-muted mb-0">Application #{{ application.application_number }}</p>
                        </div>
                        <div>
                            <Link
                                :href="`/applications/${application.id}`"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="row">
                    <!-- Left Column - Form -->
                    <div class="col-lg-8">
                        <!-- Disbursement Details -->
                        <Card class="mb-3">
                            <template #header>
                                <h5 class="mb-0">Disbursement Details</h5>
                            </template>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <Input
                                        v-model="form.disbursed_amount"
                                        type="number"
                                        label="Disbursed Amount (TZS) *"
                                        :error="form.errors.disbursed_amount"
                                        step="0.01"
                                        required
                                    />
                                    <small class="text-muted">
                                        Approved: {{ formatCurrency(application.requested_amount) }}
                                    </small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <Input
                                        v-model="form.disbursement_date"
                                        type="date"
                                        label="Disbursement Date *"
                                        :error="form.errors.disbursement_date"
                                        required
                                    />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <Select
                                        v-model="form.disbursement_method"
                                        label="Disbursement Method *"
                                        :error="form.errors.disbursement_method"
                                        :options="[
                                            { value: 'bank_transfer', label: 'Bank Transfer' },
                                            { value: 'cash', label: 'Cash' },
                                            { value: 'cheque', label: 'Cheque' },
                                            { value: 'mobile_money', label: 'Mobile Money' }
                                        ]"
                                        required
                                    />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <Input
                                        v-model="form.disbursement_reference"
                                        type="text"
                                        label="Reference Number"
                                        :error="form.errors.disbursement_reference"
                                        placeholder="Transaction reference"
                                    />
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Disbursement Notes</label>
                                    <textarea
                                        v-model="form.disbursement_notes"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.disbursement_notes }"
                                        rows="3"
                                        placeholder="Additional notes about the disbursement"
                                    ></textarea>
                                    <div v-if="form.errors.disbursement_notes" class="invalid-feedback">
                                        {{ form.errors.disbursement_notes }}
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Loan Activation -->
                        <Card class="mb-3">
                            <template #header>
                                <h5 class="mb-0">Loan Activation</h5>
                            </template>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <Input
                                        v-model="form.activation_date"
                                        type="date"
                                        label="Activation Date *"
                                        :error="form.errors.activation_date"
                                        required
                                    />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <Input
                                        v-model="form.first_installment_date"
                                        type="date"
                                        label="First Installment Date *"
                                        :error="form.errors.first_installment_date"
                                        required
                                    />
                                    <small class="text-muted">
                                        Tenure: {{ application.requested_tenure_months }} months
                                    </small>
                                </div>
                            </div>
                        </Card>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <Link
                                :href="`/applications/${application.id}`"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Disbursing...
                                </span>
                                <span v-else>Disburse Loan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Right Column - Application Details -->
                    <div class="col-lg-4">
                        <!-- Customer Information -->
                        <Card class="mb-3">
                            <template #header>
                                <h6 class="mb-0">Customer</h6>
                            </template>

                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                                    style="width: 50px; height: 50px;">
                                    <span class="fw-semibold text-primary">
                                        {{ getInitials(application.customer?.first_name, application.customer?.last_name) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-semibold">
                                        {{ application.customer?.first_name }} {{ application.customer?.last_name }}
                                    </div>
                                    <small class="text-muted">{{ application.customer?.customer_code }}</small>
                                </div>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Phone:</span>
                                    <span>{{ application.customer?.phone }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Email:</span>
                                    <span class="text-break">{{ application.customer?.email }}</span>
                                </div>
                            </div>
                        </Card>

                        <!-- Loan Product -->
                        <Card class="mb-3">
                            <template #header>
                                <h6 class="mb-0">Loan Product</h6>
                            </template>

                            <div class="mb-3">
                                <div class="fw-semibold">{{ application.loan_product?.name }}</div>
                                <small class="text-muted">{{ application.loan_product?.product_code }}</small>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Interest Rate:</span>
                                    <span>{{ application.loan_product?.interest_rate }}% p.a.</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Method:</span>
                                    <span>{{ formatInterestMethod(application.loan_product?.interest_model) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Max LTV:</span>
                                    <span>{{ application.loan_product?.max_ltv }}%</span>
                                </div>
                            </div>
                        </Card>

                        <!-- Application Summary -->
                        <Card class="mb-3">
                            <template #header>
                                <h6 class="mb-0">Application Summary</h6>
                            </template>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Requested Amount:</span>
                                <span class="fw-semibold">{{ formatCurrency(application.requested_amount) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Tenure:</span>
                                <span>{{ application.requested_tenure_months }} months</span>
                            </div>
                            <div v-if="application.property_value" class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Property Value:</span>
                                <span>{{ formatCurrency(application.property_value) }}</span>
                            </div>
                            <div v-if="application.ltv_ratio" class="d-flex justify-content-between">
                                <span class="text-muted">LTV Ratio:</span>
                                <span>{{ application.ltv_ratio }}%</span>
                            </div>
                        </Card>

                        <!-- Underwriting Decision -->
                        <Card v-if="application.latest_underwriting_decision">
                            <template #header>
                                <h6 class="mb-0">Underwriting Decision</h6>
                            </template>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Decision:</span>
                                <span class="badge bg-success">
                                    {{ application.latest_underwriting_decision.decision_status }}
                                </span>
                            </div>
                            <div v-if="application.latest_underwriting_decision.risk_grade" class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Risk Grade:</span>
                                <span>{{ application.latest_underwriting_decision.risk_grade }}</span>
                            </div>
                            <div v-if="application.latest_underwriting_decision.recommended_amount" class="d-flex justify-content-between">
                                <span class="text-muted">Recommended:</span>
                                <span>{{ formatCurrency(application.latest_underwriting_decision.recommended_amount) }}</span>
                            </div>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
