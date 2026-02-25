<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';

const props = defineProps({
    application: Object,
    customers: Array,
    loanProducts: Array
});

const form = useForm({
    customer_id: props.application.customer_id,
    loan_product_id: props.application.loan_product_id,
    requested_amount: props.application.requested_amount,
    requested_tenure_months: props.application.requested_tenure_months,
    property_type: props.application.property_type || '',
    property_value: props.application.property_value || '',
    property_address: props.application.property_address || '',
    notes: props.application.notes || '',
    save_as_draft: true
});

const selectedProduct = computed(() => {
    if (!form.loan_product_id) return null;
    return props.loanProducts.find(p => p.id === form.loan_product_id);
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        minimumFractionDigits: 0
    }).format(amount);
};

const formatInterestModel = (model) => {
    const models = {
        'fixed': 'Fixed Rate',
        'reducing_balance': 'Reducing Balance',
        'flat_rate': 'Flat Rate'
    };
    return models[model] || model;
};

const saveDraft = () => {
    form.save_as_draft = true;
    form.put(`/applications/${props.application.id}`, {
        preserveScroll: true
    });
};

const submit = () => {
    form.save_as_draft = false;
    form.put(`/applications/${props.application.id}`, {
        preserveScroll: true
    });
};
</script>

<template>
    <AppLayout title="Edit Application">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Edit Application</h3>
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

                    <form @submit.prevent="submit">
                        <div class="row">
                            <!-- Customer Information -->
                            <div class="col-lg-8">
                                <Card>
                                    <template #header>
                                        <h5 class="mb-0">Customer Information</h5>
                                    </template>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <Select
                                                v-model="form.customer_id"
                                                label="Customer *"
                                                :error="form.errors.customer_id"
                                                :options="customers.map(c => ({
                                                    value: c.id,
                                                    label: `${c.full_name} (${c.customer_code})`
                                                }))"
                                                placeholder="Select customer"
                                            />
                                        </div>
                                    </div>
                                </Card>

                                <!-- Loan Product -->
                                <Card class="mt-3">
                                    <template #header>
                                        <h5 class="mb-0">Loan Product</h5>
                                    </template>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <Select
                                                v-model="form.loan_product_id"
                                                label="Loan Product *"
                                                :error="form.errors.loan_product_id"
                                                :options="loanProducts.map(p => ({
                                                    value: p.id,
                                                    label: `${p.name} (${p.product_code})`
                                                }))"
                                                placeholder="Select loan product"
                                            />
                                        </div>

                                        <div v-if="selectedProduct" class="col-12">
                                            <div class="alert alert-info">
                                                <strong>Product Details:</strong><br>
                                                Interest Rate: {{ selectedProduct.interest_rate }}% ({{ formatInterestModel(selectedProduct.interest_model) }})<br>
                                                Loan Amount: {{ formatCurrency(selectedProduct.min_loan_amount) }} - {{ formatCurrency(selectedProduct.max_loan_amount) }}<br>
                                                Tenure: {{ selectedProduct.min_tenure_months }} - {{ selectedProduct.max_tenure_months }} months<br>
                                                Max LTV: {{ selectedProduct.max_ltv }}%
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                <!-- Loan Details -->
                                <Card class="mt-3">
                                    <template #header>
                                        <h5 class="mb-0">Loan Details</h5>
                                    </template>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <Input
                                                v-model="form.requested_amount"
                                                type="number"
                                                label="Requested Amount (TZS) *"
                                                :error="form.errors.requested_amount"
                                                placeholder="Enter amount"
                                                step="0.01"
                                            />
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <Input
                                                v-model="form.requested_tenure_months"
                                                type="number"
                                                label="Requested Tenure (Months) *"
                                                :error="form.errors.requested_tenure_months"
                                                placeholder="Enter tenure"
                                            />
                                        </div>
                                    </div>
                                </Card>

                                <!-- Property Information -->
                                <Card class="mt-3">
                                    <template #header>
                                        <h5 class="mb-0">Property Information</h5>
                                    </template>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <Input
                                                v-model="form.property_type"
                                                type="text"
                                                label="Property Type"
                                                :error="form.errors.property_type"
                                                placeholder="e.g., Residential, Commercial"
                                            />
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <Input
                                                v-model="form.property_value"
                                                type="number"
                                                label="Property Value (TZS)"
                                                :error="form.errors.property_value"
                                                placeholder="Enter property value"
                                                step="0.01"
                                            />
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Property Address</label>
                                            <textarea
                                                v-model="form.property_address"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.property_address }"
                                                rows="3"
                                                placeholder="Enter property address"
                                            ></textarea>
                                            <div v-if="form.errors.property_address" class="invalid-feedback">
                                                {{ form.errors.property_address }}
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                <!-- Additional Information -->
                                <Card class="mt-3">
                                    <template #header>
                                        <h5 class="mb-0">Additional Information</h5>
                                    </template>

                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea
                                                v-model="form.notes"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.notes }"
                                                rows="4"
                                                placeholder="Add any additional notes or comments"
                                            ></textarea>
                                            <div v-if="form.errors.notes" class="invalid-feedback">
                                                {{ form.errors.notes }}
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                <!-- Form Actions -->
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button
                                        type="button"
                                        @click="saveDraft"
                                        class="btn btn-outline-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing && form.save_as_draft">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Saving...
                                        </span>
                                        <span v-else>Save as Draft</span>
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing && !form.save_as_draft">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Submitting...
                                        </span>
                                        <span v-else>Update & Submit Application</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Sidebar Help -->
                            <div class="col-lg-4">
                                <Card>
                                    <template #header>
                                        <h6 class="mb-0">Application Status</h6>
                                    </template>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        This application is currently in <strong>DRAFT</strong> status. 
                                        You can save changes as draft or submit for review.
                                    </div>
                                </Card>

                                <Card class="mt-3">
                                    <template #header>
                                        <h6 class="mb-0">Guidelines</h6>
                                    </template>
                                    <ul class="mb-0 ps-3">
                                        <li class="mb-2">Verify customer details are correct</li>
                                        <li class="mb-2">Ensure requested amount is within product limits</li>
                                        <li class="mb-2">Property value should be accurately assessed</li>
                                        <li class="mb-2">LTV ratio will be calculated automatically</li>
                                        <li>Once submitted, the application cannot be edited</li>
                                    </ul>
                                </Card>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
