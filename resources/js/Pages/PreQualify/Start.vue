<template>
    <AppLayout>
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Pre-Qualification Check</h1>
                            <p class="text-muted mb-0">
                                Complete this quick assessment to check loan eligibility before full application.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        1
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Basic Information</div>
                                        <small class="text-muted">Personal & loan details</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        2
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold text-muted">Bank Statement</div>
                                        <small class="text-muted">Upload & analyze</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        3
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold text-muted">Eligibility Results</div>
                                        <small class="text-muted">View outcome</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Personal Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person me-2"></i>Personal Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label">
                                            First Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="first_name"
                                            v-model="form.first_name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.first_name }"
                                            required
                                        />
                                        <div v-if="form.errors.first_name" class="invalid-feedback">
                                            {{ form.errors.first_name }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input
                                            id="middle_name"
                                            v-model="form.middle_name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.middle_name }"
                                        />
                                        <div v-if="form.errors.middle_name" class="invalid-feedback">
                                            {{ form.errors.middle_name }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="last_name" class="form-label">
                                            Last Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="last_name"
                                            v-model="form.last_name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.last_name }"
                                            required
                                        />
                                        <div v-if="form.errors.last_name" class="invalid-feedback">
                                            {{ form.errors.last_name }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="id_number" class="form-label">
                                            National ID Number <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="id_number"
                                            v-model="form.id_number"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.id_number }"
                                            required
                                        />
                                        <div v-if="form.errors.id_number" class="invalid-feedback">
                                            {{ form.errors.id_number }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="phone" class="form-label">
                                            Phone Number <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.phone }"
                                            placeholder="+255..."
                                            required
                                        />
                                        <div v-if="form.errors.phone" class="invalid-feedback">
                                            {{ form.errors.phone }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.email }"
                                        />
                                        <div v-if="form.errors.email" class="invalid-feedback">
                                            {{ form.errors.email }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="customer_type" class="form-label">
                                            Employment Type <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="customer_type"
                                            v-model="form.customer_type"
                                            class="form-select"
                                            :class="{ 'is-invalid': form.errors.customer_type }"
                                            required
                                        >
                                            <option value="">Select employment type...</option>
                                            <option value="salary">Salaried Employee</option>
                                            <option value="business">Self-Employed / Business Owner</option>
                                            <option value="mixed">Mixed Income</option>
                                        </select>
                                        <div v-if="form.errors.customer_type" class="invalid-feedback">
                                            {{ form.errors.customer_type }}
                                        </div>
                                        <small class="text-muted">
                                            Salary: 12-month statements required. Business/Mixed: 24-month statements required.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loan Request Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-cash-stack me-2"></i>Loan Request Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="loan_purpose" class="form-label">
                                            Loan Purpose <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="loan_purpose"
                                            v-model="form.loan_purpose"
                                            class="form-select"
                                            :class="{ 'is-invalid': form.errors.loan_purpose }"
                                            required
                                        >
                                            <option value="">Select loan purpose...</option>
                                            <option value="home_purchase">Home Purchase</option>
                                            <option value="home_refinance">Home Refinance</option>
                                            <option value="home_completion">Home Completion</option>
                                            <option value="home_construction">Home Construction</option>
                                            <option value="home_equity_release">Home Equity Release</option>
                                        </select>
                                        <div v-if="form.errors.loan_purpose" class="invalid-feedback">
                                            {{ form.errors.loan_purpose }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="loan_product_id" class="form-label">Loan Product</label>
                                        <select
                                            id="loan_product_id"
                                            v-model="form.loan_product_id"
                                            class="form-select"
                                            :class="{ 'is-invalid': form.errors.loan_product_id }"
                                        >
                                            <option value="">Auto-select best product</option>
                                            <option v-for="product in loanProducts" :key="product.id" :value="product.id">
                                                {{ product.name }} ({{ product.annual_interest_rate }}% p.a.)
                                            </option>
                                        </select>
                                        <div v-if="form.errors.loan_product_id" class="invalid-feedback">
                                            {{ form.errors.loan_product_id }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="requested_amount" class="form-label">
                                            Requested Loan Amount (TZS) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="requested_amount"
                                            v-model.number="form.requested_amount"
                                            type="number"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.requested_amount }"
                                            step="1000"
                                            min="1000"
                                            required
                                        />
                                        <div v-if="form.errors.requested_amount" class="invalid-feedback">
                                            {{ form.errors.requested_amount }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="requested_tenure" class="form-label">
                                            Loan Tenure (Months) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="requested_tenure"
                                            v-model.number="form.requested_tenure"
                                            type="number"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.requested_tenure }"
                                            min="1"
                                            max="360"
                                            required
                                        />
                                        <div v-if="form.errors.requested_tenure" class="invalid-feedback">
                                            {{ form.errors.requested_tenure }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="property_value" class="form-label">Property Value (TZS)</label>
                                        <input
                                            id="property_value"
                                            v-model.number="form.property_value"
                                            type="number"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.property_value }"
                                            step="1000"
                                        />
                                        <div v-if="form.errors.property_value" class="invalid-feedback">
                                            {{ form.errors.property_value }}
                                        </div>
                                        <small class="text-muted">Optional - for LTV ratio calculation</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="property_location" class="form-label">Property Location</label>
                                        <input
                                            id="property_location"
                                            v-model="form.property_location"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.property_location }"
                                            placeholder="e.g., Kinondoni, Dar es Salaam"
                                        />
                                        <div v-if="form.errors.property_location" class="invalid-feedback">
                                            {{ form.errors.property_location }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-between mb-4">
                            <Link href="/dashboard" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </Link>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Continue to Bank Statement Upload
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="col-lg-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-info-circle me-2"></i>What You Need
                                </h6>
                                <ul class="mb-3">
                                    <li>Valid National ID</li>
                                    <li>Bank statement (Excel/CSV format):
                                        <ul>
                                            <li>Salaried: Last 12 months</li>
                                            <li>Self-employed: Last 24 months</li>
                                        </ul>
                                    </li>
                                    <li>Property details (if available)</li>
                                </ul>

                                <h6 class="card-title mt-4">
                                    <i class="bi bi-shield-check me-2"></i>This Pre-Qualification:
                                </h6>
                                <ul class="mb-0">
                                    <li>Takes 5-10 minutes</li>
                                    <li>Does not affect credit score</li>
                                    <li>Provides instant eligibility decision</li>
                                    <li>Shows maximum loan amount you qualify for</li>
                                    <li>Identifies any issues before full application</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    loanProducts: Array,
});

const form = useForm({    first_name: '',
    middle_name: '',
    last_name: '',
    phone: '',
    email: '',
    id_number: '',
    customer_type: '',
    loan_purpose: '',
    requested_amount: null,
    requested_tenure: null,
    loan_product_id: '',
    property_location: '',
    property_value: null,
});

const submit = () => {
    console.log('Submit function called');
    console.log('Form data:', form.data());
    
    form.post('/pre-qualify', {
        onSuccess: () => {
            console.log('Form submitted successfully');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
    });
};
</script>

<style scoped>
.card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}
</style>
